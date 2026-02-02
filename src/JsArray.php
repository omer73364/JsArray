<?php

namespace JsArray;

class JsArray
{
    private array $items;
    public int $length;

    public function __construct(array $items = [])
    {
        $this->items = $items;
        $this->length = count($items);
    }

    public static function from(array $items): self
    {
        return new self($items);
    }

    public static function of(...$items): self
    {
        return new self($items);
    }

    public function map(callable $callback): self
    {
        $result = [];
        foreach ($this->items as $key => $value) {
            $result[$key] = $callback($value, $key, $this);
        }
        return new self($result);
    }

    public function filter(callable $callback): self
    {
        $result = [];
        foreach ($this->items as $key => $value) {
            if ($callback($value, $key, $this)) {
                $result[$key] = $value;
            }
        }

        return new self($result);
    }

    public function reduce(callable $callback, $initial = null)
    {
        $accumulator = $initial;
        $first = true;

        foreach ($this->items as $key => $value) {
            if ($first && $initial === null) {
                $accumulator = $value;
                $first = false;
                continue;
            }
            $accumulator = $callback($accumulator, $value, $key, $this);
            $first = false;
        }

        return $accumulator;
    }

    public function flat(): self
    {
        $result = [];
        foreach ($this->items as $value) {
            if (is_array($value)) {
                foreach ($value as $item) {
                    $result[] = $item;
                }
            } else {
                $result[] = $value;
            }
        }
        return new self($result);
    }

    public function flatMap(callable $callback): self
    {
        return $this->map($callback)->flat();
    }

    public function concat(self ...$arrays): self
    {
        $result = $this->items;

        foreach ($arrays as $array) {
            foreach ($array->items as $value) {
                $result[] = $value;
            }
        }

        return new self($result);
    }
}
