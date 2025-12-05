<?php

// Copyright (c) 2025 Vivian Burkhard Voss
// SPDX-License-Identifier: Apache-2.0

declare(strict_types=1);

namespace GBLN;

use GBLN\Exceptions\IOException;
use GBLN\Exceptions\ParseException;
use GBLN\Exceptions\SerialiseException;

/**
 * GBLN I/O Operations - File reading and writing with optional compression.
 *
 * This class provides high-level methods for reading and writing GBLN files
 * with automatic compression/decompression support (.gbln and .gbln.xz).
 */
final class Io
{
    /**
     * Reads a GBLN file and returns the parsed PHP value.
     *
     * Supports both plain (.gbln) and XZ-compressed (.gbln.xz) files.
     *
     * @param string $path The path to the GBLN file
     *
     * @return mixed The parsed PHP value
     *
     * @throws IOException If the file cannot be read
     * @throws ParseException If the file content is invalid GBLN
     */
    public static function read(string $path): mixed
    {
        return Parser::parseFile($path);
    }

    /**
     * Writes a PHP value to a GBLN file.
     *
     * @param string $path The path to write to (supports .gbln and .gbln.xz)
     * @param mixed $value The PHP value to write
     * @param Config|null $config Optional configuration (defaults to I/O defaults)
     *
     * @return void
     *
     * @throws SerialiseException If the value cannot be serialised
     * @throws IOException If the file cannot be written
     */
    public static function write(string $path, mixed $value, ?Config $config = null): void
    {
        // Use I/O defaults if no config provided
        $config = $config ?? Config::ioDefault();

        // Serialise to GBLN string
        $gblnString = Serialiser::serialise($value, $config);

        // Check if compression is requested
        $shouldCompress = str_ends_with($path, '.xz');

        if ($shouldCompress) {
            $gblnString = self::compressXz($gblnString, $config->compressionLevel);
        }

        // Write to file
        $result = @file_put_contents($path, $gblnString);

        if ($result === false) {
            throw new IOException("Failed to write file: {$path}");
        }
    }

    /**
     * Writes a PHP value to a GBLN file in mini mode (compact).
     *
     * @param string $path The path to write to
     * @param mixed $value The PHP value to write
     * @param bool $compress Whether to compress the file (add .xz extension automatically)
     *
     * @return void
     *
     * @throws SerialiseException If the value cannot be serialised
     * @throws IOException If the file cannot be written
     */
    public static function writeMini(string $path, mixed $value, bool $compress = false): void
    {
        $config = new Config(
            miniMode: true,
            compress: $compress,
            compressionLevel: 6,
            indent: 0,
            stripComments: true
        );

        if ($compress && !str_ends_with($path, '.xz')) {
            $path .= '.xz';
        }

        self::write($path, $value, $config);
    }

    /**
     * Writes a PHP value to a GBLN file in pretty mode (formatted).
     *
     * @param string $path The path to write to
     * @param mixed $value The PHP value to write
     * @param int $indent Number of spaces for indentation (0-8, default: 2)
     *
     * @return void
     *
     * @throws SerialiseException If the value cannot be serialised
     * @throws IOException If the file cannot be written
     */
    public static function writePretty(string $path, mixed $value, int $indent = 2): void
    {
        $config = new Config(
            miniMode: false,
            compress: false,
            compressionLevel: 0,
            indent: $indent,
            stripComments: false
        );

        self::write($path, $value, $config);
    }

    /**
     * Converts a JSON file to GBLN format.
     *
     * @param string $jsonPath The path to the JSON file
     * @param string $gblnPath The path to write the GBLN file to
     * @param Config|null $config Optional configuration
     *
     * @return void
     *
     * @throws IOException If file operations fail
     * @throws SerialiseException If conversion fails
     */
    public static function convertFromJson(string $jsonPath, string $gblnPath, ?Config $config = null): void
    {
        if (!file_exists($jsonPath)) {
            throw new IOException("JSON file does not exist: {$jsonPath}");
        }

        $jsonContent = @file_get_contents($jsonPath);

        if ($jsonContent === false) {
            throw new IOException("Failed to read JSON file: {$jsonPath}");
        }

        try {
            $phpValue = json_decode($jsonContent, true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException $e) {
            throw new IOException("Invalid JSON in file '{$jsonPath}': {$e->getMessage()}");
        }

        self::write($gblnPath, $phpValue, $config);
    }

    /**
     * Converts a GBLN file to JSON format.
     *
     * @param string $gblnPath The path to the GBLN file
     * @param string $jsonPath The path to write the JSON file to
     * @param bool $pretty Whether to format the JSON output
     *
     * @return void
     *
     * @throws IOException If file operations fail
     * @throws ParseException If GBLN parsing fails
     */
    public static function convertToJson(string $gblnPath, string $jsonPath, bool $pretty = true): void
    {
        $phpValue = self::read($gblnPath);

        $flags = JSON_THROW_ON_ERROR;
        if ($pretty) {
            $flags |= JSON_PRETTY_PRINT;
        }

        try {
            $jsonContent = json_encode($phpValue, $flags);
        } catch (\JsonException $e) {
            throw new IOException("Failed to encode JSON: {$e->getMessage()}");
        }

        $result = @file_put_contents($jsonPath, $jsonContent);

        if ($result === false) {
            throw new IOException("Failed to write JSON file: {$jsonPath}");
        }
    }

    /**
     * Compresses data using XZ compression.
     *
     * @param string $data The data to compress
     * @param int $level Compression level (0-9, default: 6)
     *
     * @return string The compressed data
     *
     * @throws IOException If compression fails
     */
    private static function compressXz(string $data, int $level = 6): string
    {
        if (!function_exists('xzencode')) {
            throw new IOException('XZ compression support not available (install php-xz extension)');
        }

        $compressed = @xzencode($data, $level);

        if ($compressed === false) {
            throw new IOException('Failed to compress data with XZ');
        }

        return $compressed;
    }

    /**
     * Checks if a file exists and is a GBLN file.
     *
     * @param string $path The path to check
     *
     * @return bool True if the file exists and has a .gbln or .gbln.xz extension
     */
    public static function isGblnFile(string $path): bool
    {
        return file_exists($path) && (
            str_ends_with($path, '.gbln') || str_ends_with($path, '.gbln.xz')
        );
    }

    /**
     * Gets the size of a GBLN file in bytes.
     *
     * @param string $path The path to the GBLN file
     *
     * @return int The file size in bytes
     *
     * @throws IOException If the file cannot be accessed
     */
    public static function getFileSize(string $path): int
    {
        if (!file_exists($path)) {
            throw new IOException("File does not exist: {$path}");
        }

        $size = @filesize($path);

        if ($size === false) {
            throw new IOException("Failed to get file size: {$path}");
        }

        return $size;
    }

    /**
     * Compares file sizes between GBLN and JSON formats.
     *
     * Useful for demonstrating GBLN's size efficiency.
     *
     * @param mixed $value The PHP value to compare
     *
     * @return array{gbln_bytes: int, json_bytes: int, savings_percent: float}
     *
     * @throws SerialiseException If serialisation fails
     */
    public static function compareFormats(mixed $value): array
    {
        $gblnString = Serialiser::serialiseMini($value);
        $gblnBytes = strlen($gblnString);

        $jsonString = json_encode($value, JSON_THROW_ON_ERROR);
        $jsonBytes = strlen($jsonString);

        $savingsPercent = $jsonBytes > 0
            ? round((1 - ($gblnBytes / $jsonBytes)) * 100, 2)
            : 0.0;

        return [
            'gbln_bytes' => $gblnBytes,
            'json_bytes' => $jsonBytes,
            'savings_percent' => $savingsPercent,
        ];
    }
}
