<?php

// Copyright (c) 2025 Vivian Burkhard Voss
// SPDX-License-Identifier: Apache-2.0

declare(strict_types=1);

namespace GBLN\Tests;

use GBLN\Io;
use GBLN\Config;
use GBLN\Exceptions\IOException;
use GBLN\Exceptions\ParseException;
use PHPUnit\Framework\TestCase;

final class IoTest extends TestCase
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

    public function testReadFile(): void
    {
        $path = __DIR__ . '/fixtures/simple.gbln';
        $result = Io::read($path);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('user', $result);
    }

    public function testWriteFile(): void
    {
        $path = $this->tempDir . '/test.gbln';
        $value = ['name' => 'Alice', 'age' => 25];

        Io::write($path, $value);

        $this->assertFileExists($path);
        $content = file_get_contents($path);
        $this->assertIsString($content);
        $this->assertStringContainsString('name', $content);
    }

    public function testWriteAndReadRoundTrip(): void
    {
        $path = $this->tempDir . '/roundtrip.gbln';
        $originalValue = [
            'user' => [
                'id' => 12345,
                'name' => 'Alice Johnson',
                'age' => 25,
                'active' => true
            ]
        ];

        Io::write($path, $originalValue);
        $readValue = Io::read($path);

        $this->assertEquals($originalValue, $readValue);
    }

    public function testWriteMini(): void
    {
        $path = $this->tempDir . '/mini.gbln';
        $value = ['name' => 'Alice', 'age' => 25];

        Io::writeMini($path, $value, false);

        $this->assertFileExists($path);
        $content = file_get_contents($path);
        // Mini mode should be compact
        $this->assertStringNotContainsString("\n", trim($content));
    }

    public function testWritePretty(): void
    {
        $path = $this->tempDir . '/pretty.gbln';
        $value = ['name' => 'Alice', 'age' => 25];

        Io::writePretty($path, $value);

        $this->assertFileExists($path);
        $content = file_get_contents($path);
        // Pretty mode should have formatting
        $this->assertStringContainsString("\n", $content);
    }

    public function testIsGblnFile(): void
    {
        $gblnPath = __DIR__ . '/fixtures/simple.gbln';
        $this->assertTrue(Io::isGblnFile($gblnPath));

        $nonGblnPath = __DIR__ . '/IoTest.php';
        $this->assertFalse(Io::isGblnFile($nonGblnPath));

        $nonExistentPath = '/nonexistent/file.gbln';
        $this->assertFalse(Io::isGblnFile($nonExistentPath));
    }

    public function testGetFileSize(): void
    {
        $path = __DIR__ . '/fixtures/simple.gbln';
        $size = Io::getFileSize($path);

        $this->assertIsInt($size);
        $this->assertGreaterThan(0, $size);
    }

    public function testGetFileSizeNonExistent(): void
    {
        $this->expectException(IOException::class);
        $this->expectExceptionMessage('File does not exist');
        Io::getFileSize('/nonexistent/file.gbln');
    }

    public function testCompareFormats(): void
    {
        $value = ['name' => 'Alice', 'age' => 25, 'active' => true];
        $comparison = Io::compareFormats($value);

        $this->assertIsArray($comparison);
        $this->assertArrayHasKey('gbln_bytes', $comparison);
        $this->assertArrayHasKey('json_bytes', $comparison);
        $this->assertArrayHasKey('savings_percent', $comparison);
        $this->assertIsInt($comparison['gbln_bytes']);
        $this->assertIsInt($comparison['json_bytes']);
        $this->assertIsFloat($comparison['savings_percent']);
    }

    public function testWriteWithConfig(): void
    {
        $path = $this->tempDir . '/config.gbln';
        $value = ['name' => 'Alice'];
        $config = Config::sourceDefault();

        Io::write($path, $value, $config);

        $this->assertFileExists($path);
    }

    public function testWriteToNonWritableDirectory(): void
    {
        $this->expectException(IOException::class);
        $this->expectExceptionMessage('Failed to write file');

        // Try to write to a path that doesn't exist and can't be created
        Io::write('/nonexistent/invalid/path/test.gbln', ['data' => 'test']);
    }

    public function testReadNonExistentFile(): void
    {
        $this->expectException(IOException::class);
        $this->expectExceptionMessage('File does not exist');
        Io::read('/nonexistent/file.gbln');
    }
}
