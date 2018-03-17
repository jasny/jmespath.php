<?php
namespace JmesPath\Tests;

use JmesPath\Lexer;
use JmesPath\SyntaxErrorException;
use PHPUnit\Framework\TestCase;

/**
 * @covers JmesPath\Lexer
 */
class LexerTest extends TestCase
{
    public function inputProvider()
    {
        return [
            ['0', 'number'],
            ['1', 'number'],
            ['2', 'number'],
            ['3', 'number'],
            ['4', 'number'],
            ['5', 'number'],
            ['6', 'number'],
            ['7', 'number'],
            ['8', 'number'],
            ['9', 'number'],
            ['-1', 'number'],
            ['-1.5', 'number'],
            ['109.5', 'number'],
            ['.', 'dot'],
            ['{', 'lbrace'],
            ['}', 'rbrace'],
            ['[', 'lbracket'],
            [']', 'rbracket'],
            [':', 'colon'],
            [',', 'comma'],
            ['||', 'or'],
            ['*', 'star'],
            ['foo', 'identifier'],
            ['"foo"', 'quoted_identifier'],
            ['`true`', 'literal'],
            ['`false`', 'literal'],
            ['`null`', 'literal'],
            ['`"true"`', 'literal']
        ];
    }

    /**
     * @dataProvider inputProvider
     */
    public function testTokenizesInput($input, $type)
    {
        $l = new Lexer();
        $tokens = $l->tokenize($input);
        $this->assertEquals($tokens[0]['type'], $type);
    }

    public function testTokenizesJsonLiterals()
    {
        $l = new Lexer();
        $tokens = $l->tokenize('`null`, `false`, `true`, `"abc"`, `"ab\\"c"`,'
            . '`0`, `0.45`, `-0.5`');
        $this->assertNull($tokens[0]['value']);
        $this->assertFalse($tokens[2]['value']);
        $this->assertTrue($tokens[4]['value']);
        $this->assertEquals('abc', $tokens[6]['value']);
        $this->assertEquals('ab"c', $tokens[8]['value']);
        $this->assertSame(0, $tokens[10]['value']);
        $this->assertSame(0.45, $tokens[12]['value']);
        $this->assertSame(-0.5, $tokens[14]['value']);
    }

    public function testTokenizesJsonNumbers()
    {
        $l = new Lexer();
        $tokens = $l->tokenize('`10`, `1.2`, `-10.20e-10`, `1.2E+2`');
        $this->assertEquals(10, $tokens[0]['value']);
        $this->assertEquals(1.2, $tokens[2]['value']);
        $this->assertEquals(-1.02E-9, $tokens[4]['value']);
        $this->assertEquals(120, $tokens[6]['value']);
    }

    public function testCanWorkWithElidedJsonLiterals()
    {
        $l = new Lexer();
        $tokens = $l->tokenize('`foo`');
        $this->assertEquals('foo', $tokens[0]['value']);
        $this->assertEquals('literal', $tokens[0]['type']);
    }

    public function testArithmetic()
    {
        $l = new Lexer();
        $tokens = $l->tokenize('3+5*2/2+4%3-2*2');
        $this->assertEquals('number', $tokens[0]['type']);
        $this->assertEquals('plus', $tokens[1]['type']);
        $this->assertEquals('number', $tokens[2]['type']);
        $this->assertEquals('multiply', $tokens[3]['type']);
        $this->assertEquals('number', $tokens[4]['type']);
        $this->assertEquals('divide', $tokens[5]['type']);
        $this->assertEquals('number', $tokens[6]['type']);
        $this->assertEquals('plus', $tokens[7]['type']);
        $this->assertEquals('number', $tokens[8]['type']);
        $this->assertEquals('mod', $tokens[9]['type']);
        $this->assertEquals('number', $tokens[10]['type']);
        $this->assertEquals('minus', $tokens[11]['type']);
        $this->assertEquals('number', $tokens[12]['type']);
        $this->assertEquals('multiply', $tokens[13]['type']);
        $this->assertEquals('number', $tokens[14]['type']);
    }
}
