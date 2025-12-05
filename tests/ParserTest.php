<?php

// Copyright (c) 2025 Vivian Burkhard Voss
// SPDX-License-Identifier: Apache-2.0

declare(strict_types=1);

namespace GBLN\Tests;

use GBLN\Parser;
use GBLN\Exceptions\ParseException;
use GBLN\Exceptions\IOException;
use PHPUnit\Framework\TestCase;

final class ParserTest extends TestCase
{
    public function testParseSimpleValue(): void
    {
        $gbln = 'name<s64>(Alice)';
        $result = Parser::parse($gbln);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('name', $result);
        $this->assertSame('Alice', $result['name']);
    }

    public function testParseObject(): void
    {
        $gbln = 'user{id<u32>(12345)name<s64>(Alice)age<i8>(25)}';
        $result = Parser::parse($gbln);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('user', $result);
        $this->assertIsArray($result['user']);
        $this->assertSame(12345, $result['user']['id']);
        $this->assertSame('Alice', $result['user']['name']);
        $this->assertSame(25, $result['user']['age']);
    }

    public function testParseArray(): void
    {
        $gbln = 'tags<s16>[rust python golang]';
        $result = Parser::parse($gbln);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('tags', $result);
        $this->assertIsArray($result['tags']);
        $this->assertCount(3, $result['tags']);
        $this->assertSame('rust', $result['tags'][0]);
        $this->assertSame('python', $result['tags'][1]);
        $this->assertSame('golang', $result['tags'][2]);
    }

    public function testParseBoolean(): void
    {
        $gbln = 'active<b>(t)';
        $result = Parser::parse($gbln);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('active', $result);
        $this->assertTrue($result['active']);
    }

    public function testParseNull(): void
    {
        $gbln = 'optional<n>()';
        $result = Parser::parse($gbln);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('optional', $result);
        $this->assertNull($result['optional']);
    }

    public function testParseInteger(): void
    {
        $gbln = 'count<i32>(42)';
        $result = Parser::parse($gbln);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('count', $result);
        $this->assertSame(42, $result['count']);
    }

    public function testParseFloat(): void
    {
        $gbln = 'price<f32>(19.99)';
        $result = Parser::parse($gbln);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('price', $result);
        $this->assertIsFloat($result['price']);
        $this->assertEqualsWithDelta(19.99, $result['price'], 0.01);
    }

    public function testParseNested(): void
    {
        $gbln = 'company{name<s64>(TechCorp)employees[{id<u32>(1)name<s32>(Alice)}{id<u32>(2)name<s32>(Bob)}]}';
        $result = Parser::parse($gbln);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('company', $result);
        $this->assertArrayHasKey('name', $result['company']);
        $this->assertSame('TechCorp', $result['company']['name']);
        $this->assertArrayHasKey('employees', $result['company']);
        $this->assertIsArray($result['company']['employees']);
        $this->assertCount(2, $result['company']['employees']);
    }

    public function testParseWithComments(): void
    {
        $gbln = ":| This is a comment\nuser{id<u32>(12345):| User ID\nname<s64>(Alice)}";
        $result = Parser::parse($gbln);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('user', $result);
        $this->assertSame(12345, $result['user']['id']);
    }

    public function testParseInvalidSyntax(): void
    {
        $this->expectException(ParseException::class);
        Parser::parse('invalid{syntax');
    }

    public function testParseEmptyString(): void
    {
        $this->expectException(ParseException::class);
        Parser::parse('');
    }

    public function testParseFile(): void
    {
        $path = __DIR__ . '/fixtures/simple.gbln';
        $result = Parser::parseFile($path);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('user', $result);
        $this->assertSame(12345, $result['user']['id']);
        $this->assertSame('Alice Johnson', $result['user']['name']);
    }

    public function testParseFileNotFound(): void
    {
        $this->expectException(IOException::class);
        $this->expectExceptionMessage('File does not exist');
        Parser::parseFile('/nonexistent/file.gbln');
    }

    public function testIsValidSyntax(): void
    {
        $validGbln = 'name<s64>(Alice)';
        $this->assertTrue(Parser::isValidSyntax($validGbln));

        $invalidGbln = 'invalid{';
        $this->assertFalse(Parser::isValidSyntax($invalidGbln));
    }

    public function testParseUnicodeString(): void
    {
        $gbln = 'city<s8>(北京)';
        $result = Parser::parse($gbln);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('city', $result);
        $this->assertSame('北京', $result['city']);
    }

    public function testParseMultipleTypes(): void
    {
        $gbln = 'data{i8<i8>(127)u16<u16>(65535)f64<f64>(3.14159)str<s32>(hello)}';
        $result = Parser::parse($gbln);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('data', $result);
        $this->assertSame(127, $result['data']['i8']);
        $this->assertSame(65535, $result['data']['u16']);
        $this->assertIsFloat($result['data']['f64']);
        $this->assertSame('hello', $result['data']['str']);
    }
}
