<?php

// Copyright (c) 2025 Vivian Burkhard Voss
// SPDX-License-Identifier: Apache-2.0

declare(strict_types=1);

namespace GBLN;

use GBLN\Exceptions\IOException;
use GBLN\Exceptions\ParseException;

/**
 * GBLN Parser - Parses GBLN strings and files into PHP values.
 *
 * This class provides methods to parse GBLN (Goblin Bounded Lean Notation)
 * format into native PHP values using the underlying C FFI library.
 */
final class Parser
{
    /**
     * Parses a GBLN string into a PHP value.
     *
     * @param string $gblnString The GBLN-formatted string to parse
     *
     * @return mixed The parsed PHP value (array, int, float, string, bool, null)
     *
     * @throws ParseException If the GBLN string is invalid or cannot be parsed
     */
    public static function parse(string $gblnString): mixed
    {
        $ffi = FfiWrapper::getInstance();

        // Create output pointer
        $valuePtr = $ffi->new('GblnValue*');

        // Call C parse function
        $result = $ffi->gbln_parse($gblnString, \FFI::addr($valuePtr));

        if ($result !== 0) { // GBLN_OK = 0
            $errorMsg = self::extractErrorMessage($result);
            throw new ParseException("Failed to parse GBLN string: {$errorMsg}");
        }

        try {
            // Convert C value to PHP
            $phpValue = ValueConversion::toPhp($valuePtr);

            return $phpValue;
        } finally {
            // Always free the C value
            if ($valuePtr !== null) {
                $ffi->gbln_value_free($valuePtr);
            }
        }
    }

    /**
     * Parses a GBLN file into a PHP value.
     *
     * @param string $path The path to the GBLN file (supports .gbln and .gbln.xz)
     *
     * @return mixed The parsed PHP value
     *
     * @throws IOException If the file cannot be read
     * @throws ParseException If the file content is invalid GBLN
     */
    public static function parseFile(string $path): mixed
    {
        if (!file_exists($path)) {
            throw new IOException("File does not exist: {$path}");
        }

        if (!is_readable($path)) {
            throw new IOException("File is not readable: {$path}");
        }

        // Read file content
        $content = @file_get_contents($path);

        if ($content === false) {
            throw new IOException("Failed to read file: {$path}");
        }

        // Check if file is XZ-compressed
        if (str_ends_with($path, '.xz')) {
            $content = self::decompressXz($content);
        }

        // Parse the content
        try {
            return self::parse($content);
        } catch (ParseException $e) {
            throw new ParseException(
                "Failed to parse file '{$path}': {$e->getMessage()}",
                $e->getCode(),
                $e
            );
        }
    }

    /**
     * Decompresses XZ-compressed data.
     *
     * @param string $compressedData The XZ-compressed data
     *
     * @return string The decompressed data
     *
     * @throws IOException If decompression fails
     */
    private static function decompressXz(string $compressedData): string
    {
        if (!function_exists('xzdecode')) {
            throw new IOException('XZ compression support not available (install php-xz extension)');
        }

        $decompressed = @xzdecode($compressedData);

        if ($decompressed === false) {
            throw new IOException('Failed to decompress XZ data');
        }

        return $decompressed;
    }

    /**
     * Extracts a human-readable error message from a GBLN error code.
     *
     * @param int $errorCode The GBLN error code
     *
     * @return string Human-readable error message
     */
    private static function extractErrorMessage(int $errorCode): string
    {
        return match ($errorCode) {
            1 => 'Parse error - invalid GBLN syntax',
            2 => 'Validation error - type constraint violation',
            3 => 'I/O error - file operation failed',
            4 => 'Serialisation error - cannot serialise value',
            5 => 'Memory allocation error',
            6 => 'Invalid argument',
            7 => 'Duplicate key in object',
            8 => 'Invalid type hint',
            9 => 'Value out of range',
            10 => 'String too long',
            11 => 'Invalid UTF-8 encoding',
            12 => 'Unexpected end of input',
            13 => 'Invalid escape sequence',
            14 => 'Invalid comment syntax',
            15 => 'Nested depth exceeded',
            default => "Unknown error (code: {$errorCode})",
        };
    }

    /**
     * Validates whether a string contains valid GBLN syntax without fully parsing it.
     *
     * This is a lightweight check that can be used before attempting a full parse.
     *
     * @param string $gblnString The GBLN string to validate
     *
     * @return bool True if the syntax appears valid, false otherwise
     */
    public static function isValidSyntax(string $gblnString): bool
    {
        try {
            self::parse($gblnString);
            return true;
        } catch (ParseException) {
            return false;
        }
    }
}
