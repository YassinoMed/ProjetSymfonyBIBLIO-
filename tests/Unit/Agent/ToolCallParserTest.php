<?php

declare(strict_types=1);

namespace App\Tests\Unit\Agent;

use App\Agent\Tool\ParsedToolCall;
use App\Agent\Tool\ToolCallParser;
use PHPUnit\Framework\TestCase;

final class ToolCallParserTest extends TestCase
{
    private ToolCallParser $parser;

    protected function setUp(): void
    {
        $this->parser = new ToolCallParser();
    }

    public function testItParsesStrictJsonToolCall(): void
    {
        $parsed = $this->parser->parse('{"tool":"search_books","arguments":{"query":"Dune","availableOnly":true}}');

        self::assertInstanceOf(ParsedToolCall::class, $parsed);
        self::assertSame('search_books', $parsed->tool);
        self::assertSame(['query' => 'Dune', 'availableOnly' => true], $parsed->arguments);
    }

    public function testItParsesJsonWrappedInFencedCodeBlock(): void
    {
        $output = "```json\n{\"tool\": \"get_policy\", \"arguments\": {}}\n```";

        $parsed = $this->parser->parse($output);

        self::assertInstanceOf(ParsedToolCall::class, $parsed);
        self::assertSame('get_policy', $parsed->tool);
        self::assertSame([], $parsed->arguments);
    }

    public function testItDefaultsMissingArgumentsToEmptyArray(): void
    {
        $parsed = $this->parser->parse('{"tool":"get_my_loans"}');

        self::assertInstanceOf(ParsedToolCall::class, $parsed);
        self::assertSame('get_my_loans', $parsed->tool);
        self::assertSame([], $parsed->arguments);
    }

    public function testItReturnsNullForPlainTextResponse(): void
    {
        self::assertNull($this->parser->parse('La bibliothèque est ouverte du lundi au samedi.'));
    }

    public function testItReturnsNullForInvalidJsonOrMalformedToolCall(): void
    {
        self::assertNull($this->parser->parse('{"tool": "search_books", "arguments":'));
        self::assertNull($this->parser->parse('{"arguments": {"query": "Dune"}}'));
        self::assertNull($this->parser->parse('{"tool": "", "arguments": {}}'));
        self::assertNull($this->parser->parse('{"tool": "search_books", "arguments": "invalid"}'));
        self::assertNull($this->parser->parse('{"tool": "search_books", "arguments": ["a", "b"]}'));
        self::assertNull($this->parser->parse('Voici le résultat : {"tool": "search_books"}'));
    }
}
