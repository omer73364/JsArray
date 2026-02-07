<?php

namespace JsArray;

use Countable;
use Iterator;
use JsonException;
use JsonSerializable;
use ReflectionFunction;

/**
 * A PHP implementation of JavaScript Array methods with FLEXIBLE immutability.
 * 
 * Supports both:
 * - Immutable mode (safe, default) - Returns new instances
 * - Mutable mode (fast, for large datasets) - Modifies in-place
 * 
 * Choose based on your use case:
 * JsArray::from([...]);              // Immutable (safe) ✅
 * JsArray::mutable([...]);           // Mutable (fast) ⚡
 * JsArray::createMutable([...]);     // Alias for mutable()
 * 
 * @property-read int $length The number of elements in the array
 * @property-read bool $isMutable Whether array is in mutable mode
 */
class JsArray implements Countable, Iterator, JsonSerializable
{
    /** @var array<mixed> The internal array storage */
    private array $items;

    /** @var bool Whether this instance is mutable (modifies in-place) */
    private bool $mutable;

    /**
     * Constructor
     * 
     * @param array<mixed> $items Initial array items
     * @param bool $mutable Whether to allow mutations (default: false for safety)
     */
    public function __construct(array $items = [], bool $mutable = false)
    {
        $this->items = $this->normalizeArray($items);
        $this->mutable = $mutable;
    }

    /**
     * Create an IMMUTABLE JsArray (safe, default)
     * 
     * Use when:
     * - You care about safety
     * - Array is < 10,000 items
     * - You need to preserve original
     * - You want pure functions
     * 
     * @param array<mixed> $items
     * @return self
     */
    public static function from(array $items): self
    {
        return new self($items, false);  // immutable
    }

    /**
     * Create a MUTABLE JsArray (fast, modifies in-place)
     * 
     * Use when:
     * - Performance is critical
     * - Array is > 10,000 items
     * - You're building/accumulating data
     * - Memory efficiency matters
     * 
     * ⚠️ WARNING: Modifies array in-place! Be careful!
     * 
     * @param array<mixed> $items
     * @return self
     */
    public static function mutable(array $items): self
    {
        return new self($items, true);  // mutable
    }

    /**
     * Alias for mutable() - more explicit
     * 
     * @param array<mixed> $items
     * @return self
     */
    public static function createMutable(array $items): self
    {
        return self::mutable($items);
    }

    /**
     * Create from individual arguments (immutable)
     * 
     * @param mixed ...$items
     * @return self
     */
    public static function of(mixed ...$items): self
    {
        return new self($items, false);  // always immutable
    }

    /**
     * Get the length property (read-only)
     * 
     * @return int The number of items in the array
     */
    public function __get(string $name): mixed
    {
        return match ($name) {
            'length' => $this->count(),
            'isMutable' => $this->mutable,
            'isImmutable' => !$this->mutable,
            default => throw new \InvalidArgumentException("Undefined property: {$name}")
        };
    }

    /**
     * Prevent setting properties directly
     */
    public function __set(string $name, mixed $value): void
    {
        throw new \RuntimeException("Cannot set property '{$name}'. Use array operations instead.");
    }

    /**
     * Convert to immutable mode
     * Creates a copy and returns immutable instance
     * 
     * @return self New immutable instance
     */
    public function toImmutable(): self
    {
        return new self($this->items, false);
    }

    /**
     * Convert to mutable mode
     * Switches this instance to mutable (modifies in-place)
     * 
     * ⚠️ WARNING: After this, operations modify the original!
     * 
     * @return self This instance (now mutable)
     */
    public function toMutable(): self
    {
        $this->mutable = true;
        return $this;
    }

    /**
     * Get mutable copy for performance-critical operations
     * Creates a shallow copy and makes it mutable
     * 
     * Use when:
     * - You need to do many operations on large array
     * - Original should not be modified
     * - You want performance without risk
     * 
     * @return self New mutable instance
     */
    public function getMutableCopy(): self
    {
        return new self($this->items, true);  // Copy + mutable
    }

    /**
     * Get immutable copy
     * 
     * @return self New immutable instance
     */
    public function getImmutableCopy(): self
    {
        return new self($this->items, false);  // Copy + immutable
    }

    /**
     * Create a new array with results of calling callback on every element
     * 
     * Supports flexible signatures:
     * - map(fn($value) => ...)
     * - map(fn($value, $index) => ...)
     * - map(fn($value, $index, $array) => ...)
     * 
     * @param callable $callback
     * @return self New array (immutable) or modified (mutable)
     */
    public function map(callable $callback): self
    {
        $result = [];
        $paramCount = $this->getCallableParamCount($callback);

        foreach ($this->items as $key => $value) {
            $result[] = $this->invokeCallback($callback, $value, $key, $paramCount);
        }

        if ($this->mutable) {
            $this->items = $result;  // Modify in-place
            return $this;
        } else {
            return new self($result, false);  // Return new instance
        }
    }

    /**
     * Create a new array with elements that pass the test
     * Automatically re-indexes numeric arrays (JavaScript-compatible)
     * 
     * Supports flexible signatures:
     * - filter(fn($value) => ...)
     * - filter(fn($value, $index) => ...)
     * - filter(fn($value, $index, $array) => ...)
     * 
     * @param callable $callback
     * @return self New array (immutable) or modified (mutable)
     */
    public function filter(callable $callback): self
    {
        $result = [];
        $paramCount = $this->getCallableParamCount($callback);

        foreach ($this->items as $key => $value) {
            if ($this->invokeCallback($callback, $value, $key, $paramCount)) {
                $result[] = $value;  // Always re-index
            }
        }

        if ($this->mutable) {
            $this->items = $result;  // Modify in-place
            return $this;
        } else {
            return new self($result, false);  // Return new instance
        }
    }

    /**
     * Reduce the array to a single value
     * 
     * Supports flexible signatures:
     * - reduce(fn($acc, $value) => ...)
     * - reduce(fn($acc, $value, $index) => ...)
     * - reduce(fn($acc, $value, $index, $array) => ...)
     * 
     * @param callable $callback
     * @param mixed $initial Initial accumulator value
     * @return mixed The reduced value
     */
    public function reduce(callable $callback, mixed $initial = null): mixed
    {
        $accumulator = $initial;
        $first = true;
        $paramCount = $this->getCallableParamCount($callback);

        foreach ($this->items as $key => $value) {
            if ($first && $initial === null) {
                $accumulator = $value;
                $first = false;
                continue;
            }

            $accumulator = match ($paramCount) {
                1 => $callback($accumulator),
                2 => $callback($accumulator, $value),
                3 => $callback($accumulator, $value, $key),
                default => $callback($accumulator, $value, $key, $this)
            };

            $first = false;
        }

        return $accumulator;
    }

    /**
     * Flatten the array by one level (or specified depth)
     * 
     * @param int $depth How many levels deep to flatten (default: 1)
     * @return self New array (immutable) or modified (mutable)
     */
    public function flat(int $depth = 999): self
    {
        if ($depth <= 0) {
            if ($this->mutable) {
                return $this;
            }
            return new self($this->items, false);
        }

        $result = [];
        foreach ($this->items as $value) {
            if (is_array($value)) {
                $flattened = (new self($value))->flat($depth - 1)->toArray();
                foreach ($flattened as $item) {
                    $result[] = $item;
                }
            } elseif ($value instanceof JsArray) {
                $flattened = $value->flat($depth - 1)->toArray();
                foreach ($flattened as $item) {
                    $result[] = $item;
                }
            } else {
                $result[] = $value;
            }
        }

        if ($this->mutable) {
            $this->items = $result;  // Modify in-place
            return $this;
        } else {
            return new self($result, false);  // Return new instance
        }
    }

    /**
     * Map each element using callback and flatten by one level
     * 
     * Supports flexible signatures:
     * - flatMap(fn($value) => ...)
     * - flatMap(fn($value, $index) => ...)
     * - flatMap(fn($value, $index, $array) => ...)
     * 
     * @param callable $callback
     * @return self New array (immutable) or modified (mutable)
     */
    public function flatMap(callable $callback): self
    {
        return $this->map($callback)->flat(1);
    }

    /**
     * Concatenate multiple arrays
     * 
     * @param self ...$arrays
     * @return self New array (immutable) or modified (mutable)
     */
    public function concat(self ...$arrays): self
    {
        $result = $this->items;

        foreach ($arrays as $array) {
            foreach ($array->items as $key => $value) {
                if ($this->isNumericArray()) {
                    $result[] = $value;  // Re-index numeric arrays
                } else {
                    $result[$key] = $value;
                }
            }
        }

        if ($this->mutable) {
            $this->items = $result;  // Modify in-place
            return $this;
        } else {
            return new self($result, false);  // Return new instance
        }
    }

    /**
     * Return the first element that satisfies the testing function
     * 
     * Supports flexible signatures:
     * - find(fn($value) => ...)
     * - find(fn($value, $index) => ...)
     * - find(fn($value, $index, $array) => ...)
     * 
     * @param callable $callback
     * @return mixed|null
     */
    public function find(callable $callback): mixed
    {
        $paramCount = $this->getCallableParamCount($callback);

        foreach ($this->items as $key => $value) {
            if ($this->invokeCallback($callback, $value, $key, $paramCount)) {
                return $value;
            }
        }
        return null;
    }

    /**
     * Return the index of the first element that satisfies the testing function
     * 
     * Supports flexible signatures:
     * - findIndex(fn($value) => ...)
     * - findIndex(fn($value, $index) => ...)
     * - findIndex(fn($value, $index, $array) => ...)
     * 
     * @param callable $callback
     * @return int|string Returns -1 if not found in numeric arrays, null in associative
     */
    public function findIndex(callable $callback): int|string|null
    {
        $index = 0;
        $paramCount = $this->getCallableParamCount($callback);

        foreach ($this->items as $key => $value) {
            if ($this->invokeCallback($callback, $value, $key, $paramCount)) {
                return $this->isNumericArray() ? $index : $key;
            }
            if ($this->isNumericArray()) {
                $index++;
            }
        }
        return $this->isNumericArray() ? -1 : null;
    }

    /**
     * Check if array contains a specific value (strict comparison)
     * 
     * @param mixed $value
     * @return bool
     */
    public function includes(mixed $value): bool
    {
        foreach ($this->items as $item) {
            if ($item === $value) {
                return true;
            }
        }
        return false;
    }

    /**
     * Test whether at least one element passes the test
     * 
     * @param callable $callback
     * @return bool
     */
    public function some(callable $callback): bool
    {
        $paramCount = $this->getCallableParamCount($callback);

        foreach ($this->items as $key => $value) {
            if ($this->invokeCallback($callback, $value, $key, $paramCount)) {
                return true;
            }
        }
        return false;
    }

    /**
     * Test whether all elements pass the test
     * 
     * @param callable $callback
     * @return bool
     */
    public function every(callable $callback): bool
    {
        $paramCount = $this->getCallableParamCount($callback);

        foreach ($this->items as $key => $value) {
            if (!$this->invokeCallback($callback, $value, $key, $paramCount)) {
                return false;
            }
        }
        return true;
    }

    /**
     * Add one or more elements to the end
     * 
     * @param mixed ...$values
     * @return self Modified (mutable) or new array (immutable)
     */
    public function push(mixed ...$values): self
    {
        if ($this->mutable) {
            foreach ($values as $value) {
                $this->items[] = $value;
            }
            return $this;
        } else {
            $result = $this->items;
            foreach ($values as $value) {
                $result[] = $value;
            }
            return new self($result, false);
        }
    }

    /**
     * Remove and return the last element
     * 
     * @return array{array: self, value: mixed}
     */
    public function pop(): array
    {
        if (empty($this->items)) {
            return [
                'array' => $this->mutable ? $this : new self([], $this->mutable),
                'value' => null
            ];
        }

        if ($this->mutable) {
            $value = array_pop($this->items);
            return [
                'array' => $this,
                'value' => $value
            ];
        } else {
            $result = $this->items;
            $value = array_pop($result);
            return [
                'array' => new self($result, false),
                'value' => $value
            ];
        }
    }

    /**
     * Remove and return the first element
     * 
     * @return array{array: self, value: mixed}
     */
    public function shift(): array
    {
        if (empty($this->items)) {
            return [
                'array' => $this->mutable ? $this : new self([], $this->mutable),
                'value' => null
            ];
        }

        if ($this->mutable) {
            $value = array_shift($this->items);
            return [
                'array' => $this,
                'value' => $value
            ];
        } else {
            $result = $this->items;
            $value = array_shift($result);
            return [
                'array' => new self($result, false),
                'value' => $value
            ];
        }
    }

    /**
     * Add one or more elements to the beginning
     * 
     * @param mixed ...$values
     * @return self Modified (mutable) or new array (immutable)
     */
    public function unshift(mixed ...$values): self
    {
        if ($this->mutable) {
            foreach (array_reverse($values) as $value) {
                array_unshift($this->items, $value);
            }
            return $this;
        } else {
            $result = $this->items;
            foreach (array_reverse($values) as $value) {
                array_unshift($result, $value);
            }
            return new self($result, false);
        }
    }

    /**
     * Return a new array with all array keys
     * 
     * @return self Always immutable for keys view
     */
    public function keys(): self
    {
        return new self(array_keys($this->items), false);
    }

    /**
     * Return a new array with all array values (re-indexed)
     * 
     * @return self Always immutable for values view
     */
    public function values(): self
    {
        return new self(array_values($this->items), false);
    }

    /**
     * Get the first element (or null if empty)
     * 
     * @return mixed
     */
    public function first(): mixed
    {
        if (empty($this->items)) {
            return null;
        }
        return reset($this->items);
    }

    /**
     * Get the last element (or null if empty)
     * 
     * @return mixed
     */
    public function last(): mixed
    {
        if (empty($this->items)) {
            return null;
        }
        return end($this->items);
    }

    /**
     * Get element at index (supports negative indexing)
     * 
     * @param int $index Positive or negative index
     * @return mixed|null
     */
    public function at(int $index): mixed
    {
        if (empty($this->items)) {
            return null;
        }

        $normalizedIndex = $index < 0 ? count($this->items) + $index : $index;
        if ($normalizedIndex < 0 || $normalizedIndex >= count($this->items)) {
            return null;
        }

        $itemsArray = array_values($this->items);
        return $itemsArray[$normalizedIndex];
    }

    /**
     * Join array elements into a string
     * 
     * @param string $separator Default: ','
     * @return string
     */
    public function join(string $separator = ','): string
    {
        return implode($separator, $this->items);
    }

    /**
     * Execute a function for each element (for side effects only)
     * 
     * Supports flexible signatures:
     * - forEach(fn($value) {...})
     * - forEach(fn($value, $index) {...})
     * - forEach(fn($value, $index, $array) {...})
     * 
     * @param callable $callback
     * @return void
     */
    public function forEach(callable $callback): void
    {
        $paramCount = $this->getCallableParamCount($callback);

        foreach ($this->items as $key => $value) {
            $this->invokeCallback($callback, $value, $key, $paramCount);
        }
    }

    /**
     * Extract a section of the array
     * Automatically re-indexes numeric arrays (JavaScript-compatible)
     * 
     * @param int $start Start index (negative counts from end)
     * @param int|null $end End index (negative counts from end)
     * @return self New array (immutable) or modified (mutable)
     */
    public function slice(int $start, ?int $end = null): self
    {
        $length = count($this->items);
        $start = $this->normalizeIndex($start, $length);
        $end = $end === null ? $length : $this->normalizeIndex($end, $length);

        $result = [];
        $index = 0;
        foreach ($this->items as $value) {
            if ($index >= $start && $index < $end) {
                $result[] = $value;
            }
            $index++;
        }

        if ($this->mutable) {
            $this->items = $result;
            return $this;
        } else {
            return new self($result, false);
        }
    }

    /**
     * Change contents by removing/replacing elements
     * 
     * @param int $start Start position
     * @param int|null $deleteCount Number of items to delete
     * @param array<mixed> $items Items to insert
     * @return array{deleted: self, array: self}
     */
    public function splice(int $start, ?int $deleteCount = null, array $items = []): array
    {
        $length = count($this->items);
        $start = $this->normalizeIndex($start, $length);

        if ($deleteCount === null) {
            $deleteCount = $length - $start;
        } else {
            $deleteCount = max(0, min($deleteCount, $length - $start));
        }

        $deletedItems = array_slice($this->items, $start, $deleteCount, true);
        $before = array_slice($this->items, 0, $start, true);
        $after = array_slice($this->items, $start + $deleteCount, null, true);

        $result = array_merge($before, $items, $after);

        if ($this->isNumericArray()) {
            $result = array_values($result);
            $deletedItems = array_values($deletedItems);
        }

        if ($this->mutable) {
            $this->items = $result;
            return [
                'deleted' => new self($deletedItems, false),
                'array' => $this
            ];
        } else {
            return [
                'deleted' => new self($deletedItems, false),
                'array' => new self($result, false)
            ];
        }
    }

    /**
     * Find first index of value
     * 
     * @param mixed $searchElement
     * @param int $fromIndex Start index for search
     * @return int Returns -1 if not found
     */
    public function indexOf(mixed $searchElement, int $fromIndex = 0): int
    {
        $index = 0;
        foreach ($this->items as $value) {
            if ($index >= $fromIndex && $value === $searchElement) {
                return $index;
            }
            $index++;
        }
        return -1;
    }

    /**
     * Find last index of value
     * 
     * @param mixed $searchElement
     * @param int|null $fromIndex Start index for search (from end if null)
     * @return int Returns -1 if not found
     */
    public function lastIndexOf(mixed $searchElement, ?int $fromIndex = null): int
    {
        $items = array_values($this->items);
        if ($fromIndex === null) {
            $fromIndex = count($items) - 1;
        }

        for ($i = $fromIndex; $i >= 0; $i--) {
            if (isset($items[$i]) && $items[$i] === $searchElement) {
                return $i;
            }
        }
        return -1;
    }

    /**
     * Reverse the array
     * 
     * @return self Modified (mutable) or new array (immutable)
     */
    public function reverse(): self
    {
        $result = array_reverse($this->items, $this->isNumericArray() ? false : true);

        if ($this->mutable) {
            $this->items = $result;
            return $this;
        } else {
            return new self($result, false);
        }
    }

    /**
     * Sort the array
     * 
     * @param callable|null $callback Custom comparator or null for default sort
     * @return self Modified (mutable) or new array (immutable)
     */
    public function sort(?callable $callback = null): self
    {
        $result = $this->items;

        if ($callback === null) {
            sort($result);
        } else {
            usort($result, $callback);
        }

        if ($this->mutable) {
            $this->items = $result;
            return $this;
        } else {
            return new self($result, false);
        }
    }

    /**
     * Convert to native PHP array
     * 
     * @return array<mixed>
     */
    public function toArray(): array
    {
        return $this->items;
    }

    /**
     * Get array representation for debugging
     * 
     * @return array
     */
    public function __debugInfo(): array
    {
        return [
            'items' => $this->items,
            'length' => count($this->items),
            'isMutable' => $this->mutable,
            'isNumeric' => $this->isNumericArray()
        ];
    }

    /**
     * ===== HELPER METHODS =====
     */

    /**
     * Get the number of parameters a callable accepts
     * 
     * @param callable $callback
     * @return int Number of parameters (1-4)
     */
    private function getCallableParamCount(callable $callback): int
    {
        try {
            if (is_array($callback)) {
                $reflection = new \ReflectionMethod($callback[0], $callback[1]);
            } elseif (is_string($callback) && strpos($callback, '::') !== false) {
                [$class, $method] = explode('::', $callback);
                $reflection = new \ReflectionMethod($class, $method);
            } else {
                $reflection = new ReflectionFunction($callback);
            }

            $paramCount = $reflection->getNumberOfParameters();
            return min(4, max(1, $paramCount));
        } catch (\ReflectionException $e) {
            return 2;
        }
    }

    /**
     * Invoke callback with flexible parameter count
     * 
     * @param callable $callback
     * @param mixed $value Current value
     * @param int|string $key Current key/index
     * @param int $paramCount Number of parameters expected
     * @return mixed
     */
    private function invokeCallback(callable $callback, mixed $value, int|string $key, int $paramCount): mixed
    {
        return match ($paramCount) {
            1 => $callback($value),
            2 => $callback($value, $key),
            3 => $callback($value, $key, $this),
            default => $callback($value, $key, $this)
        };
    }

    /**
     * Check if this is a numeric array (sequential keys starting from 0)
     * 
     * @return bool
     */
    private function isNumericArray(): bool
    {
        if (empty($this->items)) {
            return true;
        }
        return array_keys($this->items) === range(0, count($this->items) - 1);
    }

    /**
     * Normalize index to valid range
     * Handles negative indices (counting from end)
     * 
     * @param int $index
     * @param int $length
     * @return int
     */
    private function normalizeIndex(int $index, int $length): int
    {
        if ($index < 0) {
            $index = $length + $index;
        }
        return max(0, min($index, $length));
    }

    /**
     * Normalize array to ensure consistency
     * Re-indexes numeric arrays to be sequential
     * 
     * @param array<mixed> $items
     * @return array<mixed>
     */
    private function normalizeArray(array $items): array
    {
        if (empty($items)) {
            return [];
        }

        $keys = array_keys($items);
        $isNumeric = ($keys === range(0, count($keys) - 1));

        if ($isNumeric) {
            return array_values($items);
        }

        return $items;
    }

    // ===== \Iterator implementation =====
    public function current(): mixed
    {
        return current($this->items);
    }

    public function next(): void
    {
        next($this->items);
    }

    public function key(): int|string|null
    {
        return key($this->items);
    }

    public function valid(): bool
    {
        return key($this->items) !== null;
    }

    public function rewind(): void
    {
        reset($this->items);
    }

    // ===== \Countable implementation =====
    public function count(): int
    {
        return count($this->items);
    }

    // ===== \JsonSerializable implementation =====
    public function jsonSerialize(): array
    {
        return $this->toArray();
    }

    /**
     * Create a JsArray from a JSON string
     *
     * @param string $json
     * @return self
     * @throws JsonException if the JSON is invalid or does not decode to an array
     */
    public static function fromJson(string $json): self
    {
        $data = json_decode($json, true, flags: JSON_THROW_ON_ERROR);
        return new self($data, false);
    }
}
