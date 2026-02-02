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
}
