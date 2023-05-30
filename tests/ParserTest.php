<?php
namespace JmesPath\Tests;

use JmesPath\Lexer;
use JmesPath\Parser;
use PHPUnit\Framework\TestCase;

/**
 * @covers JmesPath\Parser
 */
class ParserTest extends TestCase
{
    public function testMatchesFirstTokens()
    {
        $this->expectException(\JmesPath\SyntaxErrorException::class);
        $this->expectExceptionMessage("Syntax error at character 0");
        $p = new Parser(new Lexer());
        $result = $p->parse('.bar');
    }

    public function testThrowsSyntaxErrorForInvalidSequence()
    {
        $this->expectException(\JmesPath\SyntaxErrorException::class);
        $this->expectExceptionMessage("Syntax error at character 1");
        $p = new Parser(new Lexer());
        $p->parse('a,');
    }

    public function testMatchesAfterFirstToken()
    {
        $this->expectException(\JmesPath\SyntaxErrorException::class);
        $this->expectExceptionMessage("Syntax error at character 2");
        $p = new Parser(new Lexer());
        $p->parse('a.,');
    }

    public function testHandlesEmptyExpressions()
    {
        $this->expectException(\JmesPath\SyntaxErrorException::class);
        $this->expectExceptionMessage("Unexpected \"eof\" token");
        (new Parser(new Lexer()))->parse('');
    }

    /**
     * @dataProvider invalidExpressionProvider
     */
    public function testHandlesInvalidExpressions($expr, $token)
    {
        $this->expectException(\JmesPath\SyntaxErrorException::class);
        $this->expectExceptionMessage("Unexpected \"$token\" token (nud_$token).");
        (new Parser(new Lexer()))->parse($expr);
    }

    public function invalidExpressionProvider()
    {
        return [
            ['=', 'unknown'],
            ['<', 'comparator'],
            ['>', 'comparator'],
            ['|', 'pipe']
        ];
    }
}
