<?php

// Copyright (c) 2025 Vivian Burkhard Voss
// SPDX-License-Identifier: Apache-2.0

declare(strict_types=1);

namespace GBLN;

/**
 * Configuration for GBLN I/O operations.
 *
 * Provides settings for serialisation and file I/O:
 * - mini_mode: Compact output (no whitespace)
 * - compress: Enable XZ compression
 * - compression_level: XZ compression level (0-9)
 * - indent: Spaces per indent level (0-8)
 * - strip_comments: Remove comments during serialisation
 */
final class Config
{
    public bool $miniMode;
    public bool $compress;
    public int $compressionLevel;
    public int $indent;
    public bool $stripComments;

    /**
     * Create a new configuration.
     *
     * @param bool $miniMode Compact output (default: true)
     * @param bool $compress Enable compression (default: true)
     * @param int $compressionLevel XZ level 0-9 (default: 6)
     * @param int $indent Spaces per indent 0-8 (default: 2)
     * @param bool $stripComments Remove comments (default: true)
     *
     * @throws \InvalidArgumentException if parameters are out of range
     */
    public function __construct(
        bool $miniMode = true,
        bool $compress = true,
        int $compressionLevel = 6,
        int $indent = 2,
        bool $stripComments = true
    ) {
        if ($compressionLevel < 0 || $compressionLevel > 9) {
            throw new \InvalidArgumentException(
                "compressionLevel must be between 0 and 9, got: {$compressionLevel}"
            );
        }

        if ($indent < 0 || $indent > 8) {
            throw new \InvalidArgumentException(
                "indent must be between 0 and 8, got: {$indent}"
            );
        }

        $this->miniMode = $miniMode;
        $this->compress = $compress;
        $this->compressionLevel = $compressionLevel;
        $this->indent = $indent;
        $this->stripComments = $stripComments;
    }

    /**
     * Default configuration for I/O format (.io.gbln.xz).
     *
     * - MINI format (compact)
     * - XZ compression enabled (level 6)
     * - Comments stripped
     */
    public static function ioDefault(): self
    {
        return new self(
            miniMode: true,
            compress: true,
            compressionLevel: 6,
            indent: 2,
            stripComments: true
        );
    }

    /**
     * Default configuration for source files (.gbln).
     *
     * - Pretty format (with whitespace)
     * - No compression
     * - Comments preserved
     */
    public static function sourceDefault(): self
    {
        return new self(
            miniMode: false,
            compress: false,
            compressionLevel: 0,
            indent: 2,
            stripComments: false
        );
    }
}
