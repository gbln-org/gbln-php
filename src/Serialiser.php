<?php

// Copyright (c) 2025 Vivian Burkhard Voss
// SPDX-License-Identifier: Apache-2.0

declare(strict_types=1);

namespace GBLN;

use GBLN\Exceptions\SerialiseException;

/**
 * GBLN Serialiser - Converts PHP values to GBLN format strings.
 *
 * This class provides methods to serialise native PHP values into GBLN
 * (Goblin Bounded Lean Notation) format using the underlying C FFI library.
 */
final class Serialiser
{
    /**
     * Serialises a PHP value to a GBLN string.
     *
     * @param mixed $value The PHP value to serialise (array, int, float, string, bool, null)
     * @param Config|null $config Optional configuration (defaults to mini mode)
     *
     * @return string The GBLN-formatted string
     *
     * @throws SerialiseException If the value cannot be serialised
     */
    public static function serialise(mixed $value, ?Config $config = null): string
    {
        $ffi = FfiWrapper::getInstance();

        // Create C value from PHP value
        $valuePtr = ValueConversion::toC($value);

        try {
            // Create C config
            $configPtr = self::createCConfig($config ?? Config::ioDefault());

            try {
                // Call C serialisation function
                $resultPtr = $ffi->gbln_serialise($valuePtr, $configPtr);

                if ($resultPtr === null) {
                    throw new SerialiseException('Failed to serialise value: C function returned null');
                }

                // Convert C string to PHP string
                $gblnString = \FFI::string($resultPtr);

                // Free the C string
                $ffi->gbln_string_free($resultPtr);

                return $gblnString;
            } finally {
                // Free the C config
                $ffi->gbln_config_free($configPtr);
            }
        } finally {
            // Always free the C value
            $ffi->gbln_value_free($valuePtr);
        }
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
        $config = new Config(
            miniMode: true,
            compress: true,
            compressionLevel: 6,
            indent: 0,
            stripComments: true
        );

        return self::serialise($value, $config);
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
        $config = new Config(
            miniMode: false,
            compress: false,
            compressionLevel: 0,
            indent: $indent,
            stripComments: false
        );

        return self::serialise($value, $config);
    }

    /**
     * Creates a C config structure from a PHP Config object.
     *
     * @param Config $config The PHP configuration
     *
     * @return \FFI\CData The C config pointer
     *
     * @throws SerialiseException If config creation fails
     */
    private static function createCConfig(Config $config): \FFI\CData
    {
        $ffi = FfiWrapper::getInstance();

        // Create new C config
        $configPtr = $ffi->gbln_config_new(
            $config->miniMode ? 1 : 0,
            $config->compress ? 1 : 0,
            $config->compressionLevel,
            $config->indent,
            $config->stripComments ? 1 : 0
        );

        if ($configPtr === null) {
            throw new SerialiseException('Failed to create C config: C function returned null');
        }

        return $configPtr;
    }

    /**
     * Serialises a PHP value and returns it with statistics about the output.
     *
     * Useful for debugging and optimisation.
     *
     * @param mixed $value The PHP value to serialise
     * @param Config|null $config Optional configuration
     *
     * @return array{gbln: string, bytes: int, lines: int, tokens_estimate: int}
     *
     * @throws SerialiseException If the value cannot be serialised
     */
    public static function serialiseWithStats(mixed $value, ?Config $config = null): array
    {
        $gbln = self::serialise($value, $config);

        $bytes = strlen($gbln);
        $lines = substr_count($gbln, "\n") + 1;

        // Rough token estimate (whitespace-separated words + special chars)
        $tokensEstimate = count(preg_split('/\s+/', $gbln, -1, PREG_SPLIT_NO_EMPTY))
            + substr_count($gbln, '{')
            + substr_count($gbln, '}')
            + substr_count($gbln, '[')
            + substr_count($gbln, ']')
            + substr_count($gbln, '(')
            + substr_count($gbln, ')');

        return [
            'gbln' => $gbln,
            'bytes' => $bytes,
            'lines' => $lines,
            'tokens_estimate' => $tokensEstimate,
        ];
    }

    /**
     * Converts a PHP value to GBLN and back to verify round-trip compatibility.
     *
     * Useful for testing and validation.
     *
     * @param mixed $value The PHP value to test
     *
     * @return bool True if round-trip succeeds and values match
     */
    public static function verifyRoundTrip(mixed $value): bool
    {
        try {
            $gbln = self::serialise($value);
            $restored = Parser::parse($gbln);

            return $value === $restored;
        } catch (\Exception) {
            return false;
        }
    }
}
