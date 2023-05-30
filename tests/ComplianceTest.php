<?php
namespace JmesPath\Tests;

use JmesPath\AstRuntime;
use JmesPath\CompilerRuntime;
use JmesPath\SyntaxErrorException;
use PHPUnit\Framework\TestCase;

class ComplianceTest extends TestCase
{
    private static $path;

    private $incomplete = [
        'arithmetic' => [0 => [8]],
        'benchmarks' => [2 => [3]],
        'boolean' => [2 => [32]],
        'filters' => [8 => [2, 3, 4]],
        'function_group_by' => [0 => [3, 4]],
        'functions' => [0 => [40, 41, 43, 124, 147, 148]],
        'functions_strings' => [0 => true],
        'letexpr' => [0 => true, 1 => true, 2 => true, 3 => true, 4 => true],
        'literal' => [2 => [11]],
        'pipe' => [2 => [1, 2]],
        'slice' => [3 => true],
        'syntax' => [15 => [0, 2]],
        'unicode' => [6 => true],
    ];

    public static function setUpBeforeClass(): void
    {
        self::$path = __DIR__ . '/../../compiled';
        array_map('unlink', glob(self::$path . '/jmespath_*.php'));
    }

    public static function tearDownAfterClass(): void
    {
        array_map('unlink', glob(self::$path . '/jmespath_*.php'));
    }

    private function isIncomplete($file, $suite, $case): bool
    {
        if (!isset($this->incomplete[$file][$suite])) {
            return false;
        }

        return $this->incomplete[$file][$suite] === true || in_array($case, $this->incomplete[$file][$suite], true);
    }

    /**
     * @dataProvider complianceProvider
     */
    public function testPassesCompliance(
        $data,
        $expression,
        $result,
        $error,
        $file,
        $suite,
        $case,
        $asAssoc
    ) {
        $this->compliance($data, $expression, $result, $error, $file, $suite, $case, false, $asAssoc);
    }

    /**
     * @dataProvider complianceProvider
     */
    public function testPassesComplianceCompiled(
        $data,
        $expression,
        $result,
        $error,
        $file,
        $suite,
        $case,
        $asAssoc
    ) {
        $this->compliance($data, $expression, $result, $error, $file, $suite, $case, true, $asAssoc);
    }

    public function compliance(
        $data,
        $expression,
        $result,
        $error,
        $file,
        $suite,
        $case,
        $compiled,
        $asAssoc
    ) {
        $evalResult = null;
        $failed = false;
        $failureMsg = '';
        $failure = '';
        $compiledStr = '';

        if ($this->isIncomplete($file, $suite, $case)) {
            $this->markTestIncomplete();
        }

        try {
            if ($compiled) {
                $compiledStr = \JmesPath\Env::COMPILE_DIR . '=on ';
                $runtime = new CompilerRuntime(self::$path);
            } else {
                $runtime = new AstRuntime();
            }
            $evalResult = $runtime($expression, $data);
        } catch (\Exception $e) {
            $failed = $e instanceof SyntaxErrorException ? 'syntax' : 'runtime';
            $failureMsg = sprintf(
                '%s (%s line %d)',
                $e->getMessage(),
                $e->getFile(),
                $e->getLine()
            );
        }

        $file = __DIR__ . '/compliance/' . $file . '.json';
        $failure .= "\n{$compiledStr}php bin/jp.php --file {$file} --suite {$suite} --case {$case}\n\n"
            . "Given: " . $this->prettyJson($data) . "\n\n"
            . "Expression: $expression\n\n"
            . "Result: " . $this->prettyJson($evalResult) . "\n\n"
            . "Expected: " . $this->prettyJson($result) . "\n\n";
        $failure .= 'Associative? ' . var_export($asAssoc, true) . "\n\n";

        if (!$error && $failed) {
            $this->fail("Should not have failed\n{$failure}=> {$failed} {$failureMsg}");
        } elseif ($error && !$failed) {
            $this->fail("Should have failed\n{$failure}");
        }

        $this->assertEquals(
            $this->convertAssoc($result),
            $this->convertAssoc($evalResult),
            $failure
        );
    }

    public function complianceProvider()
    {
        $cases = [];

        $files = array_map(function ($f) {
            return basename($f, '.json');
        }, glob(__DIR__ . '/compliance/tests/*.json'));

        foreach ($files as $name) {
            $contents = file_get_contents(__DIR__ . "/compliance/tests/{$name}.json");
            foreach ([true, false] as $asAssoc) {
                $json = json_decode($contents, true);
                $jsonObj = json_decode($contents);
                foreach ($json as $suiteNumber => $suite) {
                    $given = $asAssoc ? $suite['given'] : $jsonObj[$suiteNumber]->given;
                    foreach ($suite['cases'] as $caseNumber => $case) {
                        $cases["$name / suite $suiteNumber / case $caseNumber"] = [
                            $given,
                            $case['expression'],
                            isset($case['result']) ? $case['result'] : null,
                            isset($case['error']) ? $case['error'] : false,
                            $name,
                            $suiteNumber,
                            $caseNumber,
                            $asAssoc
                        ];
                    }
                }
            }
        }

        return $cases;
    }

    private function convertAssoc($data)
    {
        if ($data instanceof \stdClass) {
            return $this->convertAssoc((array) $data);
        } elseif (is_array($data)) {
            return array_map([$this, 'convertAssoc'], $data);
        } else {
            return $data;
        }
    }

    private function prettyJson($json)
    {
        if (defined('JSON_PRETTY_PRINT')) {
            return json_encode($json, JSON_PRETTY_PRINT);
        }

        return json_encode($json);
    }
}
