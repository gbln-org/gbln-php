<?php

// Copyright (c) 2025 Vivian Burkhard Voss
// SPDX-License-Identifier: Apache-2.0

declare(strict_types=1);

namespace GBLN;

use GBLN\Exceptions\IOException;
use GBLN\Exceptions\ParseException;
use GBLN\Exceptions\SerialiseException;

/**
 * GBLN - Main entry point for the GBLN PHP library.
 *
 * This class provides a convenient, unified API for all GBLN operations.
 * It acts as a facade over the Parser, Serialiser, and Io classes.
 *
 * GBLN (Goblin Bounded Lean Notation) is an LLM-native serialisation format
 * that uses 86% fewer tokens than JSON whilst providing parse-time type validation.
 *
 * @version 1.0.0
 * @author Vivian Burkhard Voss
 * @license Apache-2.0
 */
final class GBLN
{
    /**
     * Library version.
     */
    public const VERSION = '1.0.0';

    /**
     * Parses a GBLN string into a PHP value.
     *
     * @param string $gblnString The GBLN-formatted string to parse
     *
     * @return mixed The parsed PHP value (array, int, float, string, bool, null)
     *
     * @throws ParseException If the GBLN string is invalid
     */
    public static function parse(string $gblnString): mixed
    {
        return Parser::parse($gblnString);
    }

    /**
     * Parses a GBLN file into a PHP value.
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
    public static function parseFile(string $path): mixed
    {
        return Parser::parseFile($path);
    }

    /**
     * Serialises a PHP value to a GBLN string.
     *
     * @param mixed $value The PHP value to serialise
     * @param Config|null $config Optional configuration (defaults to mini mode)
     *
     * @return string The GBLN-formatted string
     *
     * @throws SerialiseException If the value cannot be serialised
     */
    public static function serialise(mixed $value, ?Config $config = null): string
    {
        return Serialiser::serialise($value, $config);
    }

    /**
     * Serialises a PHP value to a GBLN string in mini mode (compact, no whitespace).
     *
     * Mini mode is optimised for LLM contexts and produces the most token-efficient output.
     *
     * @param mixed $value The PHP value to serialise
     *
     * @return string The compact GBLN-formatted string
     *
     * @throws SerialiseException If the value cannot be serialised
     */
    public static function serialiseMini(mixed $value): string
    {
        return Serialiser::serialiseMini($value);
    }

    /**
     * Serialises a PHP value to a GBLN string in pretty mode (formatted, with indentation).
     *
     * Pretty mode is optimised for human readability and source control.
     *
     * @param mixed $value The PHP value to serialise
     * @param int $indent Number of spaces for indentation (0-8, default: 2)
     *
     * @return string The formatted GBLN-formatted string
     *
     * @throws SerialiseException If the value cannot be serialised
     */
    public static function serialisePretty(mixed $value, int $indent = 2): string
    {
        return Serialiser::serialisePretty($value, $indent);
    }

    /**
     * Reads a GBLN file and returns the parsed PHP value.
     *
     * @param string $path The path to the GBLN file
     *
     * @return mixed The parsed PHP value
     *
     * @throws IOException If the file cannot be read
     * @throws ParseException If the file content is invalid GBLN
     */
    public static function readFile(string $path): mixed
    {
        return Io::read($path);
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
    public static function writeFile(string $path, mixed $value, ?Config $config = null): void
    {
        Io::write($path, $value, $config);
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
    public static function writeFileMini(string $path, mixed $value, bool $compress = false): void
    {
        Io::writeMini($path, $value, $compress);
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
    public static function writeFilePretty(string $path, mixed $value, int $indent = 2): void
    {
        Io::writePretty($path, $value, $indent);
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
        Io::convertFromJson($jsonPath, $gblnPath, $config);
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
        Io::convertToJson($gblnPath, $jsonPath, $pretty);
    }

    /**
     * Validates whether a string contains valid GBLN syntax.
     *
     * @param string $gblnString The GBLN string to validate
     *
     * @return bool True if the syntax is valid, false otherwise
     */
    public static function isValid(string $gblnString): bool
    {
        return Parser::isValidSyntax($gblnString);
    }

    /**
     * Verifies round-trip conversion (PHP → GBLN → PHP).
     *
     * Useful for testing and validation.
     *
     * @param mixed $value The PHP value to test
     *
     * @return bool True if round-trip succeeds and values match
     */
    public static function verifyRoundTrip(mixed $value): bool
    {
        return Serialiser::verifyRoundTrip($value);
    }

    /**
     * Compares file sizes between GBLN and JSON formats.
     *
     * @param mixed $value The PHP value to compare
     *
     * @return array{gbln_bytes: int, json_bytes: int, savings_percent: float}
     *
     * @throws SerialiseException If serialisation fails
     */
    public static function compareWithJson(mixed $value): array
    {
        return Io::compareFormats($value);
    }

    /**
     * Gets library version information.
     *
     * @return array{version: string, php_version: string, ffi_available: bool}
     */
    public static function version(): array
    {
        return [
            'version' => self::VERSION,
            'php_version' => PHP_VERSION,
            'ffi_available' => extension_loaded('ffi'),
        ];
    }

    /**
     * Creates a new Config object with I/O defaults.
     *
     * @return Config Configuration optimised for file I/O
     */
    public static function configIo(): Config
    {
        return Config::ioDefault();
    }

    /**
     * Creates a new Config object with source defaults.
     *
     * @return Config Configuration optimised for source code
     */
    public static function configSource(): Config
    {
        return Config::sourceDefault();
    }
}
