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
    public static function serialise(mixed $value, bool $mini = true): string
    {
        $ffi = FfiWrapper::getInstance();

        // Create C value from PHP value
        $valuePtr = ValueConversion::toC($value);

        try {
            // Call C serialisation function
            $resultPtr = $mini
                ? $ffi->gbln_to_string($valuePtr)
                : $ffi->gbln_to_string_pretty($valuePtr);

            if ($resultPtr === null) {
                throw new SerialiseException('Failed to serialise value: C function returned null');
            }

            // Convert C string to PHP string
            $gblnString = \FFI::string($resultPtr);

            // Free the C string
            $ffi->gbln_string_free($resultPtr);

            return $gblnString;
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
        return self::serialise($value, mini: true);
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
        // Note: indent parameter kept for API compatibility but C library uses fixed 2-space indent
        return self::serialise($value, mini: false);
    }

    /**
     * Serialises a PHP value and returns it with statistics about the output.
     *
     * Useful for debugging and optimisation.
     *
     * @param mixed $value The PHP value to serialise
     * @param bool $mini Use mini mode (default: true)
     *
     * @return array{gbln: string, bytes: int, lines: int, tokens_estimate: int}
     *
     * @throws SerialiseException If the value cannot be serialised
     */
    public static function serialiseWithStats(mixed $value, bool $mini = true): array
    {
        $gbln = self::serialise($value, $mini);

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
