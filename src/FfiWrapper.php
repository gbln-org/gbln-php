<?php

// Copyright (c) 2025 Vivian Burkhard Voss
// SPDX-License-Identifier: Apache-2.0

declare(strict_types=1);

namespace GBLN;

use FFI;
use GBLN\Exceptions\GblnException;

/**
 * FFI wrapper for libgbln C library.
 *
 * Provides PHP bindings to the C FFI layer, handling:
 * - Platform detection and library loading
 * - C function declarations
 * - Memory management
 *
 * @internal
 */
final class FfiWrapper
{
    private static ?FFI $ffi = null;

    /**
     * Get FFI instance (singleton).
     * Loads libgbln on first call.
     */
    public static function getInstance(): FFI
    {
        if (self::$ffi === null) {
            self::$ffi = self::loadLibrary();
        }

        return self::$ffi;
    }

    /**
     * Load libgbln library with C function declarations.
     *
     * Search order:
     * 1. GBLN_LIBRARY_PATH environment variable
     * 2. Relative path to ../../core/ffi/libs/{platform}/
     * 3. System library paths
     *
     * @throws GblnException if library cannot be loaded
     */
    private static function loadLibrary(): FFI
    {
        $libPath = self::findLibrary();

        if ($libPath === null) {
            throw new GblnException(
                'Cannot find libgbln. Please ensure libgbln is installed or set GBLN_LIBRARY_PATH.'
            );
        }

        $headerDef = self::getHeaderDefinitions();

        try {
            return FFI::cdef($headerDef, $libPath);
        } catch (\FFI\Exception $e) {
            throw new GblnException("Failed to load libgbln: {$e->getMessage()}");
        }
    }

    /**
     * Find library in system paths.
     *
     * @return string|null Path to library or null if not found
     */
    private static function findLibrary(): ?string
    {
        // 1. Environment variable
        $envPath = getenv('GBLN_LIBRARY_PATH');
        if ($envPath !== false && file_exists($envPath)) {
            return $envPath;
        }

        // 2. Platform detection
        $libName = self::getLibraryName();
        $platform = self::getPlatformDir();

        // 3. Development path (../../core/ffi/libs/{platform}/)
        $devPath = __DIR__ . '/../../core/ffi/libs/' . $platform . '/' . $libName;
        if (file_exists($devPath)) {
            return $devPath;
        }

        // 4. Try system library name (rely on system loader)
        return $libName;
    }

    /**
     * Get platform-specific library name.
     */
    private static function getLibraryName(): string
    {
        if (PHP_OS_FAMILY === 'Darwin') {
            return 'libgbln.dylib';
        }

        if (PHP_OS_FAMILY === 'Windows') {
            return 'gbln.dll';
        }

        return 'libgbln.so';
    }

    /**
     * Get platform directory name for library location.
     */
    private static function getPlatformDir(): string
    {
        $os = PHP_OS_FAMILY;
        $arch = php_uname('m');

        if ($os === 'Darwin') {
            return ($arch === 'arm64' || $arch === 'aarch64')
                ? 'macos-arm64'
                : 'macos-x64';
        }

        if ($os === 'Linux') {
            return ($arch === 'aarch64' || $arch === 'arm64')
                ? 'linux-arm64'
                : 'linux-x64';
        }

        if ($os === 'BSD') {
            return ($arch === 'aarch64' || $arch === 'arm64')
                ? 'freebsd-arm64'
                : 'freebsd-x64';
        }

        if ($os === 'Windows') {
            return 'windows-x64';
        }

        throw new GblnException("Unsupported platform: {$os} {$arch}");
    }

    /**
     * Get C header definitions for FFI.
     *
     * Declares all functions from C FFI (Ticket #005 + #005B).
     */
    private static function getHeaderDefinitions(): string
    {
        return <<<'HEADER'
        typedef struct GblnValue GblnValue;
        typedef struct GblnConfig GblnConfig;

        typedef enum {
            GBLN_OK = 0,
            GBLN_PARSE_ERROR = 1,
            GBLN_VALIDATION_ERROR = 2,
            GBLN_IO_ERROR = 3,
            GBLN_SERIALISE_ERROR = 4,
            GBLN_MEMORY_ERROR = 5,
            GBLN_NULL_POINTER = 6,
            GBLN_INVALID_UTF8 = 7,
            GBLN_INTEGER_OVERFLOW = 8,
            GBLN_STRING_TOO_LONG = 9,
            GBLN_DUPLICATE_KEY = 10,
            GBLN_TYPE_MISMATCH = 11,
            GBLN_UNKNOWN_ERROR = 99
        } GblnErrorCode;

        typedef enum {
            GBLN_TYPE_I8 = 0,
            GBLN_TYPE_I16 = 1,
            GBLN_TYPE_I32 = 2,
            GBLN_TYPE_I64 = 3,
            GBLN_TYPE_U8 = 4,
            GBLN_TYPE_U16 = 5,
            GBLN_TYPE_U32 = 6,
            GBLN_TYPE_U64 = 7,
            GBLN_TYPE_F32 = 8,
            GBLN_TYPE_F64 = 9,
            GBLN_TYPE_STR = 10,
            GBLN_TYPE_BOOL = 11,
            GBLN_TYPE_NULL = 12,
            GBLN_TYPE_OBJECT = 13,
            GBLN_TYPE_ARRAY = 14
        } GblnValueType;

        // Core functions
        int gbln_parse(const char* input, GblnValue** out_value);
        char* gbln_to_string(const GblnValue* value);
        char* gbln_to_string_pretty(const GblnValue* value);
        void gbln_value_free(GblnValue* value);
        void gbln_string_free(char* str);
        const char* gbln_last_error_message(void);
        const char* gbln_last_error_suggestion(void);

        // Type introspection
        int gbln_value_type(const GblnValue* value);
        bool gbln_value_is_null(const GblnValue* value);

        // Value extractors
        int8_t gbln_value_as_i8(const GblnValue* value, int* error);
        int16_t gbln_value_as_i16(const GblnValue* value, int* error);
        int32_t gbln_value_as_i32(const GblnValue* value, int* error);
        int64_t gbln_value_as_i64(const GblnValue* value, int* error);
        uint8_t gbln_value_as_u8(const GblnValue* value, int* error);
        uint16_t gbln_value_as_u16(const GblnValue* value, int* error);
        uint32_t gbln_value_as_u32(const GblnValue* value, int* error);
        uint64_t gbln_value_as_u64(const GblnValue* value, int* error);
        float gbln_value_as_f32(const GblnValue* value, int* error);
        double gbln_value_as_f64(const GblnValue* value, int* error);
        bool gbln_value_as_bool(const GblnValue* value, int* error);
        const char* gbln_value_as_string(const GblnValue* value, int* error);

        // Object operations
        char** gbln_object_keys(const GblnValue* value, size_t* out_len);
        size_t gbln_object_len(const GblnValue* value);
        GblnValue* gbln_object_get(const GblnValue* value, const char* key);
        void gbln_keys_free(char** keys, size_t len);

        // Array operations
        size_t gbln_array_len(const GblnValue* value);
        GblnValue* gbln_array_get(const GblnValue* value, size_t index);

        // Value constructors
        GblnValue* gbln_value_new_null(void);
        GblnValue* gbln_value_new_bool(bool value);
        GblnValue* gbln_value_new_i8(int8_t value);
        GblnValue* gbln_value_new_i16(int16_t value);
        GblnValue* gbln_value_new_i32(int32_t value);
        GblnValue* gbln_value_new_i64(int64_t value);
        GblnValue* gbln_value_new_u8(uint8_t value);
        GblnValue* gbln_value_new_u16(uint16_t value);
        GblnValue* gbln_value_new_u32(uint32_t value);
        GblnValue* gbln_value_new_u64(uint64_t value);
        GblnValue* gbln_value_new_f32(float value);
        GblnValue* gbln_value_new_f64(double value);
        GblnValue* gbln_value_new_str(const char* value, size_t max_len);

        // Complex type builders
        GblnValue* gbln_value_new_object(void);
        int gbln_object_insert(GblnValue* obj, const char* key, GblnValue* value);
        GblnValue* gbln_value_new_array(void);
        int gbln_array_push(GblnValue* arr, GblnValue* value);

        // Config operations
        GblnConfig* gbln_config_new(bool mini_mode, bool compress, uint8_t compression_level, size_t indent, bool strip_comments);
        GblnConfig* gbln_config_new_io(void);
        GblnConfig* gbln_config_new_source(void);
        void gbln_config_free(GblnConfig* config);

        // I/O operations
        int gbln_write_io(const GblnValue* value, const char* path, const GblnConfig* config);
        int gbln_read_io(const char* path, GblnValue** out_value);
        HEADER;
    }
}
