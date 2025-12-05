<?php

// Copyright (c) 2025 Vivian Burkhard Voss
// SPDX-License-Identifier: Apache-2.0

declare(strict_types=1);

namespace GBLN;

use FFI;
use FFI\CData;
use GBLN\Exceptions\SerialiseException;

/**
 * Convert between PHP and C FFI values.
 *
 * Provides bidirectional conversion:
 * - toPhp(): GblnValue* → PHP types (array, int, float, string, bool, null)
 * - toC(): PHP types → GblnValue*
 *
 * Handles automatic type selection for integers and strings.
 *
 * @internal
 */
final class ValueConversion
{
    /**
     * Convert C GblnValue to PHP value.
     *
     * @param CData $valuePtr Pointer to GblnValue
     * @return mixed PHP value (array, int, float, string, bool, null)
     */
    public static function toPhp(CData $valuePtr): mixed
    {
        $ffi = FfiWrapper::getInstance();
        $type = $ffi->gbln_value_type($valuePtr);

        return match ($type) {
            0 => self::extractI8($valuePtr),    // GBLN_TYPE_I8
            1 => self::extractI16($valuePtr),   // GBLN_TYPE_I16
            2 => self::extractI32($valuePtr),   // GBLN_TYPE_I32
            3 => self::extractI64($valuePtr),   // GBLN_TYPE_I64
            4 => self::extractU8($valuePtr),    // GBLN_TYPE_U8
            5 => self::extractU16($valuePtr),   // GBLN_TYPE_U16
            6 => self::extractU32($valuePtr),   // GBLN_TYPE_U32
            7 => self::extractU64($valuePtr),   // GBLN_TYPE_U64
            8 => self::extractF32($valuePtr),   // GBLN_TYPE_F32
            9 => self::extractF64($valuePtr),   // GBLN_TYPE_F64
            10 => self::extractString($valuePtr), // GBLN_TYPE_STR
            11 => self::extractBool($valuePtr),  // GBLN_TYPE_BOOL
            12 => null,                         // GBLN_TYPE_NULL
            13 => self::convertObject($valuePtr), // GBLN_TYPE_OBJECT
            14 => self::convertArray($valuePtr),  // GBLN_TYPE_ARRAY
            default => throw new SerialiseException("Unknown type code: {$type}")
        };
    }

    /**
     * Convert PHP value to C GblnValue.
     *
     * @param mixed $value PHP value
     * @return CData Pointer to GblnValue (caller must free)
     */
    public static function toC(mixed $value): CData
    {
        $ffi = FfiWrapper::getInstance();

        if ($value === null) {
            return $ffi->gbln_value_new_null();
        }

        if (is_bool($value)) {
            return $ffi->gbln_value_new_bool($value);
        }

        if (is_int($value)) {
            return self::createOptimalInteger($value);
        }

        if (is_float($value)) {
            return $ffi->gbln_value_new_f64($value);
        }

        if (is_string($value)) {
            return self::createOptimalString($value);
        }

        if (is_array($value)) {
            // Associative array → Object, indexed array → Array
            return self::isAssociativeArray($value)
                ? self::createObject($value)
                : self::createArray($value);
        }

        throw new SerialiseException('Unsupported type: ' . get_debug_type($value));
    }

    // === Private: Extraction Methods ===

    private static function extractBool(CData $valuePtr): bool
    {
        $ffi = FfiWrapper::getInstance();
        $error = $ffi->new('int');
        $result = $ffi->gbln_value_as_bool($valuePtr, FFI::addr($error));

        if ($error->cdata == 0) {
            throw new SerialiseException('Failed to extract bool');
        }

        return $result;
    }

    private static function extractI8(CData $valuePtr): int
    {
        $ffi = FfiWrapper::getInstance();
        $error = $ffi->new('int');
        $result = $ffi->gbln_value_as_i8($valuePtr, FFI::addr($error));

        if ($error->cdata == 0) {
            throw new SerialiseException('Failed to extract i8');
        }

        return $result;
    }

    private static function extractI16(CData $valuePtr): int
    {
        $ffi = FfiWrapper::getInstance();
        $error = $ffi->new('int');
        $result = $ffi->gbln_value_as_i16($valuePtr, FFI::addr($error));

        if ($error->cdata == 0) {
            throw new SerialiseException('Failed to extract i16');
        }

        return $result;
    }

    private static function extractI32(CData $valuePtr): int
    {
        $ffi = FfiWrapper::getInstance();
        $error = $ffi->new('int');
        $result = $ffi->gbln_value_as_i32($valuePtr, FFI::addr($error));

        if ($error->cdata == 0) {
            throw new SerialiseException('Failed to extract i32');
        }

        return $result;
    }

    private static function extractI64(CData $valuePtr): int
    {
        $ffi = FfiWrapper::getInstance();
        $error = $ffi->new('int');
        $result = $ffi->gbln_value_as_i64($valuePtr, FFI::addr($error));

        if ($error->cdata == 0) {
            throw new SerialiseException('Failed to extract i64');
        }

        return $result;
    }

    private static function extractU8(CData $valuePtr): int
    {
        $ffi = FfiWrapper::getInstance();
        $error = $ffi->new('int');
        $result = $ffi->gbln_value_as_u8($valuePtr, FFI::addr($error));

        if ($error->cdata == 0) {
            throw new SerialiseException('Failed to extract u8');
        }

        return $result;
    }

    private static function extractU16(CData $valuePtr): int
    {
        $ffi = FfiWrapper::getInstance();
        $error = $ffi->new('int');
        $result = $ffi->gbln_value_as_u16($valuePtr, FFI::addr($error));

        if ($error->cdata == 0) {
            throw new SerialiseException('Failed to extract u16');
        }

        return $result;
    }

    private static function extractU32(CData $valuePtr): int
    {
        $ffi = FfiWrapper::getInstance();
        $error = $ffi->new('int');
        $result = $ffi->gbln_value_as_u32($valuePtr, FFI::addr($error));

        if ($error->cdata == 0) {
            throw new SerialiseException('Failed to extract u32');
        }

        return $result;
    }

    private static function extractU64(CData $valuePtr): int
    {
        $ffi = FfiWrapper::getInstance();
        $error = $ffi->new('int');
        $result = $ffi->gbln_value_as_u64($valuePtr, FFI::addr($error));

        if ($error->cdata == 0) {
            throw new SerialiseException('Failed to extract u64');
        }

        return $result;
    }

    private static function extractF32(CData $valuePtr): float
    {
        $ffi = FfiWrapper::getInstance();
        $error = $ffi->new('int');
        $result = $ffi->gbln_value_as_f32($valuePtr, FFI::addr($error));

        if ($error->cdata == 0) {
            throw new SerialiseException('Failed to extract f32');
        }

        return $result;
    }

    private static function extractF64(CData $valuePtr): float
    {
        $ffi = FfiWrapper::getInstance();
        $error = $ffi->new('int');
        $result = $ffi->gbln_value_as_f64($valuePtr, FFI::addr($error));

        if ($error->cdata == 0) {
            throw new SerialiseException('Failed to extract f64');
        }

        return $result;
    }

    private static function extractString(CData $valuePtr): string
    {
        $ffi = FfiWrapper::getInstance();
        $error = $ffi->new('int');
        $result = $ffi->gbln_value_as_string($valuePtr, FFI::addr($error));

        if ($error->cdata == 0) {
            throw new SerialiseException('Failed to extract string');
        }

        // gbln_value_as_string returns const char* directly (not a pointer to pointer)
        return $result;
    }

    private static function convertObject(CData $valuePtr): array
    {
        $ffi = FfiWrapper::getInstance();
        $len = $ffi->new('size_t');
        $keysPtr = $ffi->gbln_object_keys($valuePtr, FFI::addr($len));

        if (FFI::isNull($keysPtr)) {
            return [];
        }

        $result = [];
        $count = $len->cdata;

        for ($i = 0; $i < $count; $i++) {
            $keyPtr = $keysPtr[$i];
            $key = FFI::string($keyPtr);
            $childPtr = $ffi->gbln_object_get($valuePtr, $key);
            $result[$key] = self::toPhp($childPtr);
        }

        $ffi->gbln_keys_free($keysPtr, $count);

        return $result;
    }

    private static function convertArray(CData $valuePtr): array
    {
        $ffi = FfiWrapper::getInstance();
        $len = $ffi->gbln_array_len($valuePtr);

        $result = [];
        for ($i = 0; $i < $len; $i++) {
            $childPtr = $ffi->gbln_array_get($valuePtr, $i);
            $result[] = self::toPhp($childPtr);
        }

        return $result;
    }

    // === Private: Creation Methods ===

    private static function createOptimalInteger(int $value): CData
    {
        $ffi = FfiWrapper::getInstance();

        // Unsigned integers
        if ($value >= 0) {
            if ($value <= 255) {
                return $ffi->gbln_value_new_u8($value);
            }
            if ($value <= 65535) {
                return $ffi->gbln_value_new_u16($value);
            }
            if ($value <= 4294967295) {
                return $ffi->gbln_value_new_u32($value);
            }
            return $ffi->gbln_value_new_u64($value);
        }

        // Signed integers
        if ($value >= -128 && $value <= 127) {
            return $ffi->gbln_value_new_i8($value);
        }
        if ($value >= -32768 && $value <= 32767) {
            return $ffi->gbln_value_new_i16($value);
        }
        if ($value >= -2147483648 && $value <= 2147483647) {
            return $ffi->gbln_value_new_i32($value);
        }
        return $ffi->gbln_value_new_i64($value);
    }

    private static function createOptimalString(string $value): CData
    {
        $ffi = FfiWrapper::getInstance();

        // Count UTF-8 characters (not bytes)
        $charCount = mb_strlen($value, 'UTF-8');

        // Select appropriate string type based on character count
        if ($charCount <= 64) {
            return $ffi->gbln_value_new_str($value, 64);
        }
        if ($charCount <= 256) {
            return $ffi->gbln_value_new_str($value, 256);
        }
        if ($charCount <= 1024) {
            return $ffi->gbln_value_new_str($value, 1024);
        }

        throw new SerialiseException("String too long: {$charCount} characters (max 1024)");
    }

    private static function createObject(array $value): CData
    {
        $ffi = FfiWrapper::getInstance();
        $obj = $ffi->gbln_value_new_object();

        foreach ($value as $key => $val) {
            $childValue = self::toC($val);
            $error = $ffi->gbln_object_insert($obj, (string)$key, $childValue);

            if ($error !== 0) {
                $ffi->gbln_value_free($childValue);
                $ffi->gbln_value_free($obj);
                throw new SerialiseException("Failed to insert key '{$key}' into object");
            }
        }

        return $obj;
    }

    private static function createArray(array $value): CData
    {
        $ffi = FfiWrapper::getInstance();
        $arr = $ffi->gbln_value_new_array();

        foreach ($value as $val) {
            $childValue = self::toC($val);
            $error = $ffi->gbln_array_push($arr, $childValue);

            if ($error !== 0) {
                $ffi->gbln_value_free($childValue);
                $ffi->gbln_value_free($arr);
                throw new SerialiseException('Failed to push value to array');
            }
        }

        return $arr;
    }

    private static function isAssociativeArray(array $array): bool
    {
        if ($array === []) {
            return false;
        }

        return array_keys($array) !== range(0, count($array) - 1);
    }
}
