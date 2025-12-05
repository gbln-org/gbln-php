<?php

// Copyright (c) 2025 Vivian Burkhard Voss
// SPDX-License-Identifier: Apache-2.0

declare(strict_types=1);

namespace GBLN\Tests;

use GBLN\GBLN;
use GBLN\Config;
use GBLN\Exceptions\ParseException;
use GBLN\Exceptions\IOException;
use PHPUnit\Framework\TestCase;

final class GBLNTest extends TestCase
{
    private string $tempDir;

    protected function setUp(): void
    {
        $this->tempDir = sys_get_temp_dir() . '/gbln_test_' . uniqid();
        mkdir($this->tempDir, 0777, true);
    }

    protected function tearDown(): void
    {
        if (is_dir($this->tempDir)) {
            $this->removeDirectory($this->tempDir);
        }
    }

    private function removeDirectory(string $dir): void
    {
        $files = array_diff(scandir($dir), ['.', '..']);
        foreach ($files as $file) {
            $path = $dir . '/' . $file;
            is_dir($path) ? $this->removeDirectory($path) : unlink($path);
        }
        rmdir($dir);
    }

    public function testParse(): void
    {
        $gbln = 'name<s64>(Alice)';
        $result = GBLN::parse($gbln);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('name', $result);
        $this->assertSame('Alice', $result['name']);
    }

    public function testParseFile(): void
    {
        $path = __DIR__ . '/fixtures/simple.gbln';
        $result = GBLN::parseFile($path);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('user', $result);
    }

    public function testSerialise(): void
    {
        $value = ['name' => 'Alice'];
        $result = GBLN::serialise($value);

        $this->assertIsString($result);
        $this->assertStringContainsString('name', $result);
    }

    public function testSerialiseMini(): void
    {
        $value = ['name' => 'Alice', 'age' => 25];
        $result = GBLN::serialiseMini($value);

        $this->assertIsString($result);
        $this->assertStringNotContainsString("\n", $result);
    }

    public function testSerialisePretty(): void
    {
        $value = ['name' => 'Alice', 'age' => 25];
        $result = GBLN::serialisePretty($value);

        $this->assertIsString($result);
        $this->assertStringContainsString("\n", $result);
    }

    public function testReadFile(): void
    {
        $path = __DIR__ . '/fixtures/simple.gbln';
        $result = GBLN::readFile($path);

        $this->assertIsArray($result);
    }

    public function testWriteFile(): void
    {
        $path = $this->tempDir . '/test.gbln';
        $value = ['name' => 'Alice'];

        GBLN::writeFile($path, $value);

        $this->assertFileExists($path);
    }

    public function testWriteFileMini(): void
    {
        $path = $this->tempDir . '/mini.gbln';
        $value = ['name' => 'Alice'];

        GBLN::writeFileMini($path, $value);

        $this->assertFileExists($path);
    }

    public function testWriteFilePretty(): void
    {
        $path = $this->tempDir . '/pretty.gbln';
        $value = ['name' => 'Alice'];

        GBLN::writeFilePretty($path, $value);

        $this->assertFileExists($path);
        $content = file_get_contents($path);
        $this->assertStringContainsString("\n", $content);
    }

    public function testIsValid(): void
    {
        $validGbln = 'name<s64>(Alice)';
        $this->assertTrue(GBLN::isValid($validGbln));

        $invalidGbln = 'invalid{';
        $this->assertFalse(GBLN::isValid($invalidGbln));
    }

    public function testVerifyRoundTrip(): void
    {
        $value = ['name' => 'Alice', 'age' => 25];
        $result = GBLN::verifyRoundTrip($value);

        $this->assertTrue($result);
    }

    public function testCompareWithJson(): void
    {
        $value = ['name' => 'Alice', 'age' => 25, 'active' => true];
        $comparison = GBLN::compareWithJson($value);

        $this->assertIsArray($comparison);
        $this->assertArrayHasKey('gbln_bytes', $comparison);
        $this->assertArrayHasKey('json_bytes', $comparison);
        $this->assertArrayHasKey('savings_percent', $comparison);
    }

    public function testVersion(): void
    {
        $version = GBLN::version();

        $this->assertIsArray($version);
        $this->assertArrayHasKey('version', $version);
        $this->assertArrayHasKey('php_version', $version);
        $this->assertArrayHasKey('ffi_available', $version);
        $this->assertSame('1.0.0', $version['version']);
        $this->assertSame(PHP_VERSION, $version['php_version']);
        $this->assertIsBool($version['ffi_available']);
    }

    public function testConfigIo(): void
    {
        $config = GBLN::configIo();

        $this->assertInstanceOf(Config::class, $config);
        $this->assertTrue($config->miniMode);
        $this->assertTrue($config->compress);
    }

    public function testConfigSource(): void
    {
        $config = GBLN::configSource();

        $this->assertInstanceOf(Config::class, $config);
        $this->assertFalse($config->miniMode);
        $this->assertFalse($config->compress);
    }

    public function testFullRoundTrip(): void
    {
        $originalValue = [
            'user' => [
                'id' => 12345,
                'name' => 'Alice Johnson',
                'age' => 25,
                'active' => true,
                'tags' => ['admin', 'developer']
            ]
        ];

        $gbln = GBLN::serialise($originalValue);
        $parsedValue = GBLN::parse($gbln);

        $this->assertEquals($originalValue, $parsedValue);
    }

    public function testFileRoundTrip(): void
    {
        $path = $this->tempDir . '/roundtrip.gbln';
        $originalValue = [
            'data' => [
                'name' => 'Test',
                'count' => 42,
                'active' => true
            ]
        ];

        GBLN::writeFile($path, $originalValue);
        $readValue = GBLN::readFile($path);

        $this->assertEquals($originalValue, $readValue);
    }
}
