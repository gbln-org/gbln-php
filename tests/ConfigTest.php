<?php

// Copyright (c) 2025 Vivian Burkhard Voss
// SPDX-License-Identifier: Apache-2.0

declare(strict_types=1);

namespace GBLN\Tests;

use GBLN\Config;
use PHPUnit\Framework\TestCase;

final class ConfigTest extends TestCase
{
    public function testDefaultConfig(): void
    {
        $config = new Config();

        $this->assertTrue($config->miniMode);
        $this->assertTrue($config->compress);
        $this->assertSame(6, $config->compressionLevel);
        $this->assertSame(2, $config->indent);
        $this->assertTrue($config->stripComments);
    }

    public function testCustomConfig(): void
    {
        $config = new Config(
            miniMode: false,
            compress: false,
            compressionLevel: 3,
            indent: 4,
            stripComments: false
        );

        $this->assertFalse($config->miniMode);
        $this->assertFalse($config->compress);
        $this->assertSame(3, $config->compressionLevel);
        $this->assertSame(4, $config->indent);
        $this->assertFalse($config->stripComments);
    }

    public function testIoDefault(): void
    {
        $config = Config::ioDefault();

        $this->assertTrue($config->miniMode);
        $this->assertTrue($config->compress);
        $this->assertSame(6, $config->compressionLevel);
        $this->assertSame(2, $config->indent);
        $this->assertTrue($config->stripComments);
    }

    public function testSourceDefault(): void
    {
        $config = Config::sourceDefault();

        $this->assertFalse($config->miniMode);
        $this->assertFalse($config->compress);
        $this->assertSame(0, $config->compressionLevel);
        $this->assertSame(2, $config->indent);
        $this->assertFalse($config->stripComments);
    }

    public function testCompressionLevelValidation(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('compressionLevel must be between 0 and 9');

        new Config(compressionLevel: 10);
    }

    public function testCompressionLevelNegative(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('compressionLevel must be between 0 and 9');

        new Config(compressionLevel: -1);
    }

    public function testIndentValidation(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('indent must be between 0 and 8');

        new Config(indent: 9);
    }

    public function testIndentNegative(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('indent must be between 0 and 8');

        new Config(indent: -1);
    }

    public function testValidCompressionLevels(): void
    {
        for ($level = 0; $level <= 9; $level++) {
            $config = new Config(compressionLevel: $level);
            $this->assertSame($level, $config->compressionLevel);
        }
    }

    public function testValidIndents(): void
    {
        for ($indent = 0; $indent <= 8; $indent++) {
            $config = new Config(indent: $indent);
            $this->assertSame($indent, $config->indent);
        }
    }
}
