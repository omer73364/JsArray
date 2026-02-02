# JsArray - JavaScript-accurate Array for PHP

A PHP implementation of JavaScript Array methods with immutable operations and identical API behavior.

## Installation

```bash
composer require jsarray/jsarray
```

## Features

- ✅ **JavaScript-accurate API** - All methods behave exactly like their JavaScript counterparts
- ✅ **Immutable operations** - Every method returns a new JsArray instance
- ✅ **Supports both numeric and associative arrays** - Handles PHP arrays naturally
- ✅ **Performance optimized** - Uses foreach loops, avoids unnecessary array operations
- ✅ **PHP 8+ compatible** - Modern PHP with type hints
- ✅ **Zero dependencies** - Lightweight, pure PHP implementation

## Quick Start

```php
use JsArray\JsArray;

// Numeric arrays - works just like JavaScript
$result = JsArray::from([10, 20, 30])
    ->map(fn($value, $index, $array) => $value * $index)
    ->filter(fn($value, $index, $array) => $value > 10)
    ->push(100)
    ->toArray();
// [20, 60, 100]

// Associative arrays - preserves keys
$result = JsArray::from(['a' => 5, 'b' => 10])
    ->map(fn($value, $key, $array) => "$key:$value")
    ->toArray();
// ['a' => 'a:5', 'b' => 'b:10']
```

## API Reference

### Properties

#### `length`
The `length` property returns the number of elements in the array.

```php
$array = JsArray::from([1, 2, 3]);
echo $array->length; // 3
```

### Creation Methods

```php
use JsArray\JsArray;

// Static methods
$jsArray = JsArray::from([1, 2, 3]);
$jsArray = JsArray::of(1, 2, 3);
```

### Iteration & Transformation

```php
// Execute a function for each element
$jsArray->forEach(function($value, $index, $array) {
    // Your code here
});

$jsArray->map(fn($value, $index, $array) => $value * 2)
$jsArray->filter(fn($value, $index, $array) => $value > 10)
$jsArray->reduce(fn($accumulator, $value, $index, $array) => $accumulator + $value, 0)
$jsArray->flat()
$jsArray->flatMap(fn($value, $index, $array) => [$value, $value * 2])
$jsArray->slice(1, 3)
$jsArray->concat(JsArray::from([4, 5]), JsArray::from([6]))
```

### Search & Validation

```php
$jsArray->find(fn($value, $index, $array) => $value > 10)
$jsArray->findIndex(fn($value, $index, $array) => $value > 10)  // Returns -1 for numeric, null for associative
$jsArray->includes(5)
$jsArray->some(fn($value, $index, $array) => $value > 10)
$jsArray->every(fn($value, $index, $array) => $value > 0)
```

### Stack-like Operations

```php
$jsArray->push(4, 5)
$result = $jsArray->pop()
// Returns ['array' => new JsArray([...]), 'value' => last_value]
```

### Utility Methods

```php
$jsArray->keys()      // Returns JsArray of keys
$jsArray->values()    // Returns JsArray of values
$jsArray->first()     // Returns first element or null
$jsArray->last()      // Returns last element or null
$jsArray->toArray()   // Returns native PHP array
```

## Callback Signature

All callback-based methods use the JavaScript signature:

```php
callback($value, $indexOrKey, $array)
```

- `$value` - The current value (always first, like JS)
- `$indexOrKey` - Numeric index for arrays, string key for associative
- `$array` - The original JsArray instance (immutable reference)

## Behavioral Notes

### Numeric vs Associative Arrays

- **Numeric arrays**: Automatically reindexed when JavaScript behavior dictates (like `filter()`)
- **Associative arrays**: Keys preserved unless explicitly modified

### findIndex() Returns

```php
// Numeric array: returns -1 when not found
JsArray::from([1, 2, 3])->findIndex(fn($value, $index, $array) => $value > 10);  // -1

// Associative array: returns null when not found
JsArray::from(['a' => 1, 'b' => 2])->findIndex(fn($value, $key, $array) => $value > 10);  // null
```

### Immutability

Every operation returns a new JsArray instance:

```php
$original = JsArray::from([1, 2, 3]);
$modified = $original->push(4);

echo $original->toArray();  // [1, 2, 3] (unchanged)
echo $modified->toArray();  // [1, 2, 3, 4] (new instance)
```

## Requirements

- PHP 8.0+
- Composer

## License

MIT License - see LICENSE file for details.

## Contributing

1. Fork the repository
2. Create a feature branch
3. Make your changes
4. Add tests for new functionality
5. Submit a pull request

## Why JsArray?

- **Familiar API** - If you know JavaScript Arrays, you know JsArray
- **Type Safety** - Full PHP 8+ type hints
- **Performance** - Optimized for PHP's strengths
- **Predictable** - Immutable operations prevent side effects
- **Flexible** - Works with both numeric and associative arrays naturally
