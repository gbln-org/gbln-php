<?php

// Copyright (c) 2025 Vivian Burkhard Voss
// SPDX-License-Identifier: Apache-2.0

declare(strict_types=1);

namespace GBLN\Tests;

use GBLN\Serialiser;
use GBLN\Config;
use GBLN\Exceptions\SerialiseException;
use PHPUnit\Framework\TestCase;

final class SerialiserTest extends TestCase
{
    public function testSerialiseString(): void
    {
        $value = ['name' => 'Alice'];
        $result = Serialiser::serialise($value);

        $this->assertIsString($result);
        $this->assertStringContainsString('name', $result);
        $this->assertStringContainsString('Alice', $result);
    }

    public function testSerialiseInteger(): void
    {
        $value = ['count' => 42];
        $result = Serialiser::serialise($value);

        $this->assertIsString($result);
        $this->assertStringContainsString('count', $result);
        $this->assertStringContainsString('42', $result);
    }

    public function testSerialiseFloat(): void
    {
        $value = ['price' => 19.99];
        $result = Serialiser::serialise($value);

        $this->assertIsString($result);
        $this->assertStringContainsString('price', $result);
    }

    public function testSerialiseBoolean(): void
    {
        $value = ['active' => true];
        $result = Serialiser::serialise($value);

        $this->assertIsString($result);
        $this->assertStringContainsString('active', $result);
    }

    public function testSerialiseNull(): void
    {
        $value = ['optional' => null];
        $result = Serialiser::serialise($value);

        $this->assertIsString($result);
        $this->assertStringContainsString('optional', $result);
    }

    public function testSerialiseArray(): void
    {
        $value = ['tags' => ['rust', 'python', 'golang']];
        $result = Serialiser::serialise($value);

        $this->assertIsString($result);
        $this->assertStringContainsString('tags', $result);
        $this->assertStringContainsString('rust', $result);
        $this->assertStringContainsString('python', $result);
    }

    public function testSerialiseObject(): void
    {
        $value = [
            'user' => [
                'id' => 12345,
                'name' => 'Alice',
                'age' => 25
            ]
        ];
        $result = Serialiser::serialise($value);

        $this->assertIsString($result);
        $this->assertStringContainsString('user', $result);
        $this->assertStringContainsString('id', $result);
        $this->assertStringContainsString('12345', $result);
    }

    public function testSerialiseMini(): void
    {
        $value = [
            'user' => [
                'id' => 12345,
                'name' => 'Alice'
            ]
        ];
        $result = Serialiser::serialiseMini($value);

        $this->assertIsString($result);
        // Mini mode should have no unnecessary whitespace
        $this->assertStringNotContainsString("\n", $result);
    }

    public function testSerialisePretty(): void
    {
        $value = [
            'user' => [
                'id' => 12345,
                'name' => 'Alice'
            ]
        ];
        $result = Serialiser::serialisePretty($value);

        $this->assertIsString($result);
        // Pretty mode should have newlines for readability
        $this->assertStringContainsString("\n", $result);
    }

    public function testSerialiseMiniMode(): void
    {
        $value = ['name' => 'Alice'];
        $result = Serialiser::serialise($value, mini: true);

        $this->assertIsString($result);
        $this->assertStringNotContainsString("\n", $result);
    }

    public function testSerialiseNested(): void
    {
        $value = [
            'company' => [
                'name' => 'TechCorp',
                'employees' => [
                    ['id' => 1, 'name' => 'Alice'],
                    ['id' => 2, 'name' => 'Bob']
                ]
            ]
        ];
        $result = Serialiser::serialise($value);

        $this->assertIsString($result);
        $this->assertStringContainsString('company', $result);
        $this->assertStringContainsString('employees', $result);
    }

    public function testSerialiseWithStats(): void
    {
        $value = ['name' => 'Alice'];
        $stats = Serialiser::serialiseWithStats($value);

        $this->assertIsArray($stats);
        $this->assertArrayHasKey('gbln', $stats);
        $this->assertArrayHasKey('bytes', $stats);
        $this->assertArrayHasKey('lines', $stats);
        $this->assertArrayHasKey('tokens_estimate', $stats);
        $this->assertIsString($stats['gbln']);
        $this->assertIsInt($stats['bytes']);
        $this->assertGreaterThan(0, $stats['bytes']);
    }

    public function testVerifyRoundTrip(): void
    {
        $value = ['name' => 'Alice', 'age' => 25, 'active' => true];
        $result = Serialiser::verifyRoundTrip($value);

        $this->assertTrue($result);
    }

    public function testSerialiseUnicode(): void
    {
        $value = ['city' => '北京'];
        $result = Serialiser::serialise($value);

        $this->assertIsString($result);
        $this->assertStringContainsString('北京', $result);
    }

    public function testSerialiseEmptyArray(): void
    {
        $value = ['items' => []];
        $result = Serialiser::serialise($value);

        $this->assertIsString($result);
    }

    public function testSerialiseEmptyObject(): void
    {
        $value = ['data' => []];
        $result = Serialiser::serialise($value);

        $this->assertIsString($result);
    }

    public function testSerialisePrettyWithCustomIndent(): void
    {
        $value = ['user' => ['name' => 'Alice']];
        $result = Serialiser::serialisePretty($value, 4);

        $this->assertIsString($result);
        $this->assertStringContainsString("\n", $result);
    }
}
