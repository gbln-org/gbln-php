# GBLN PHP Bindings

[![License](https://img.shields.io/badge/License-Apache%202.0-blue.svg)](https://opensource.org/licenses/Apache-2.0)
[![PHP Version](https://img.shields.io/badge/php-%3E%3D7.4-8892BF.svg)](https://php.net/)

PHP bindings for **GBLN (Goblin Bounded Lean Notation)** - the first LLM-native serialisation format with parse-time type validation.

## Features

- **Type-safe**: Parse-time validation with inline type hints
- **LLM-optimised**: 86% fewer tokens than JSON for AI contexts
- **Memory-efficient**: Bounded types prevent buffer overflows
- **Human-readable**: Text-based format with clear syntax
- **Git-friendly**: Meaningful diffs, ordered keys preserved
- **Zero dependencies**: Pure PHP using FFI extension

## Requirements

- **PHP 7.4 or higher**
- **FFI extension** (enabled by default in PHP 7.4+)
- **mbstring extension** (for UTF-8 support)
- **xz extension** (optional, for `.gbln.xz` compression)

## Installation

### Via Composer

```bash
composer require gbln/gbln
```

### Manual Installation

1. Clone the repository:
   ```bash
   git clone https://github.com/gbln-org/gbln-php.git
   ```

2. Ensure the C library is available:
   - The C FFI library (`libgbln.so`/`libgbln.dylib`/`gbln.dll`) must be installed
   - Set `GBLN_LIBRARY_PATH` environment variable if not in standard locations

3. Include the autoloader:
   ```php
   require_once 'vendor/autoload.php';
   ```

## Quick Start

### Parsing GBLN

```php
use GBLN\GBLN;

// Parse GBLN string
$gbln = 'user{id<u32>(12345)name<s64>(Alice)age<i8>(25)active<b>(t)}';
$data = GBLN::parse($gbln);

// Result: ['user' => ['id' => 12345, 'name' => 'Alice', 'age' => 25, 'active' => true]]
echo $data['user']['name']; // Alice
```

### Serialising to GBLN

```php
use GBLN\GBLN;

$data = [
    'user' => [
        'id' => 12345,
        'name' => 'Alice Johnson',
        'age' => 25,
        'active' => true
    ]
];

// Compact format (mini mode)
$miniGbln = GBLN::serialiseMini($data);
// Output: user{id<u32>(12345)name<s64>(Alice Johnson)age<i8>(25)active<b>(t)}

// Pretty format (human-readable)
$prettyGbln = GBLN::serialisePretty($data);
// Output:
// user{
//     id<u32>(12345)
//     name<s64>(Alice Johnson)
//     age<i8>(25)
//     active<b>(t)
// }
```

### File Operations

```php
use GBLN\GBLN;

// Read from file
$data = GBLN::readFile('config.gbln');

// Write to file (compact)
GBLN::writeFileMini('output.gbln', $data);

// Write to file (pretty)
GBLN::writeFilePretty('output.pretty.gbln', $data);

// Write compressed file
GBLN::writeFileMini('output.gbln.xz', $data, compress: true);
```

### Format Conversion

```php
use GBLN\GBLN;

// JSON to GBLN
GBLN::convertFromJson('data.json', 'data.gbln');

// GBLN to JSON
GBLN::convertToJson('data.gbln', 'data.json', pretty: true);
```

## GBLN Syntax

### Type System

| Type | Description | Range/Size | Example |
|------|-------------|------------|---------|
| `i8`, `i16`, `i32`, `i64` | Signed integers | -128 to 127, etc. | `age<i8>(25)` |
| `u8`, `u16`, `u32`, `u64` | Unsigned integers | 0 to 255, etc. | `id<u32>(12345)` |
| `f32`, `f64` | Floating-point | Single/Double precision | `price<f32>(19.99)` |
| `s2` to `s1024` | Strings | Max UTF-8 characters | `name<s64>(Alice)` |
| `b` | Boolean | `t`/`f` or `true`/`false` | `active<b>(t)` |
| `n` | Null | Empty value | `optional<n>()` |

### Basic Examples

**Single Value:**
```gbln
name<s64>(Alice)
```

**Object:**
```gbln
user{
    id<u32>(12345)
    name<s64>(Alice)
    age<i8>(25)
}
```

**Array:**
```gbln
tags<s16>[rust python golang]
```

**Comments:**
```gbln
:| This is a comment
user{
    id<u32>(12345)  :| User identifier
    name<s64>(Alice)
}
```

## API Reference

### Main Class: `GBLN`

#### Parsing

```php
// Parse GBLN string
mixed GBLN::parse(string $gblnString): mixed

// Parse GBLN file
mixed GBLN::parseFile(string $path): mixed

// Validate GBLN syntax
bool GBLN::isValid(string $gblnString): bool
```

#### Serialising

```php
// Serialise with custom config
string GBLN::serialise(mixed $value, ?Config $config = null): string

// Serialise in mini mode (compact)
string GBLN::serialiseMini(mixed $value): string

// Serialise in pretty mode (formatted)
string GBLN::serialisePretty(mixed $value, int $indent = 2): string
```

#### File I/O

```php
// Read GBLN file
mixed GBLN::readFile(string $path): mixed

// Write GBLN file
void GBLN::writeFile(string $path, mixed $value, ?Config $config = null): void

// Write GBLN file in mini mode
void GBLN::writeFileMini(string $path, mixed $value, bool $compress = false): void

// Write GBLN file in pretty mode
void GBLN::writeFilePretty(string $path, mixed $value, int $indent = 2): void
```

#### Conversion

```php
// Convert JSON to GBLN
void GBLN::convertFromJson(string $jsonPath, string $gblnPath, ?Config $config = null): void

// Convert GBLN to JSON
void GBLN::convertToJson(string $gblnPath, string $jsonPath, bool $pretty = true): void
```

#### Utilities

```php
// Verify round-trip conversion
bool GBLN::verifyRoundTrip(mixed $value): bool

// Compare GBLN vs JSON sizes
array GBLN::compareWithJson(mixed $value): array
// Returns: ['gbln_bytes' => int, 'json_bytes' => int, 'savings_percent' => float]

// Get library version info
array GBLN::version(): array
// Returns: ['version' => string, 'php_version' => string, 'ffi_available' => bool]
```

### Configuration: `Config`

```php
use GBLN\Config;

// Custom configuration
$config = new Config(
    miniMode: true,           // Compact output (no whitespace)
    compress: true,           // Enable compression logic
    compressionLevel: 6,      // 0-9 (higher = more compression)
    indent: 2,                // Spaces for indentation (0-8)
    stripComments: true       // Remove comments from output
);

// Predefined configurations
$ioConfig = Config::ioDefault();      // Optimised for file I/O
$sourceConfig = Config::sourceDefault(); // Optimised for source code
```

### Exceptions

- `GBLN\Exceptions\GblnException` - Base exception
- `GBLN\Exceptions\ParseException` - Parse errors
- `GBLN\Exceptions\ValidationException` - Type validation errors
- `GBLN\Exceptions\IOException` - File I/O errors
- `GBLN\Exceptions\SerialiseException` - Serialisation errors

## Advanced Usage

### Custom Configuration

```php
use GBLN\GBLN;
use GBLN\Config;

$config = new Config(
    miniMode: false,
    compress: false,
    compressionLevel: 0,
    indent: 4,
    stripComments: false
);

$gbln = GBLN::serialise($data, $config);
```

### Error Handling

```php
use GBLN\GBLN;
use GBLN\Exceptions\ParseException;
use GBLN\Exceptions\IOException;

try {
    $data = GBLN::parseFile('config.gbln');
} catch (IOException $e) {
    echo "File error: " . $e->getMessage();
} catch (ParseException $e) {
    echo "Parse error: " . $e->getMessage();
}
```

### Round-Trip Verification

```php
use GBLN\GBLN;

$originalData = ['user' => ['name' => 'Alice', 'age' => 25]];

if (GBLN::verifyRoundTrip($originalData)) {
    echo "Round-trip successful!";
} else {
    echo "Round-trip failed!";
}
```

### Format Comparison

```php
use GBLN\GBLN;

$data = ['users' => [/* ... large dataset ... */]];
$comparison = GBLN::compareWithJson($data);

echo "GBLN size: {$comparison['gbln_bytes']} bytes\n";
echo "JSON size: {$comparison['json_bytes']} bytes\n";
echo "Savings: {$comparison['savings_percent']}%\n";
```

## Testing

Run the test suite:

```bash
composer test
```

Generate coverage report:

```bash
composer test-coverage
```

Run static analysis:

```bash
composer phpstan
```

## Performance

### Size Comparison (1000 user records)

| Format | Size | vs JSON |
|--------|------|---------|
| JSON | 156 KB | baseline |
| GBLN | 30 KB | **81% smaller** |
| Protocol Buffers | 42 KB | 73% smaller |

### LLM Token Efficiency

| Format | Tokens | vs JSON |
|--------|--------|---------|
| JSON | 52,000 | baseline |
| GBLN (mini) | 8,300 | **84% fewer** |

## Troubleshooting

### FFI Extension Not Available

Ensure FFI is enabled in your `php.ini`:

```ini
extension=ffi
ffi.enable=true
```

Verify with:

```bash
php -m | grep ffi
```

### Library Not Found

Set the library path explicitly:

```bash
export GBLN_LIBRARY_PATH=/path/to/libgbln.so
```

Or in PHP:

```php
putenv('GBLN_LIBRARY_PATH=/path/to/libgbln.so');
```

### XZ Compression Not Available

Install the XZ extension:

```bash
# Ubuntu/Debian
sudo apt-get install php-xz

# macOS (via PECL)
pecl install xz
```

## Contributing

Contributions are welcome! Please see [CONTRIBUTING.md](https://github.com/gbln-org/gbln-php/blob/main/CONTRIBUTING.md) for details.

## License

This project is licensed under the Apache License 2.0 - see the [LICENSE](LICENSE) file for details.

## Links

- **Website**: [gbln.dev](https://gbln.dev)
- **Specification**: [github.com/gbln-org/gbln](https://github.com/gbln-org/gbln)
- **Issues**: [github.com/gbln-org/gbln-php/issues](https://github.com/gbln-org/gbln-php/issues)

## Author

**Vivian Burkhard Voss**  
Email: ask@vvoss.dev

---

*GBLN - Type-safe data that speaks clearly* 🦇
