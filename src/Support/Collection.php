<?php

namespace Ugarit\Installer\Console\Support;

use ArrayAccess;
use ArrayIterator;
use Countable;
use IteratorAggregate;
use Traversable;

/**
 * Lightweight, zero-dependency collection implementation for the installer CLI.
 *
 * @template TKey of array-key
 * @template TValue
 * @implements ArrayAccess<TKey, TValue>
 * @implements IteratorAggregate<TKey, TValue>
 */
class Collection implements ArrayAccess, Countable, IteratorAggregate
{
    /**
     * The items contained in the collection.
     *
     * @var array<TKey, TValue>
     */
    protected array $items = [];

    /**
     * Create a new collection instance.
     *
     * @param  mixed  $items
     */
    public function __construct(mixed $items = [])
    {
        $this->items = $this->getArrayableItems($items);
    }

    /**
     * Results array of items from Collection or Arrayable.
     *
     * @param  mixed  $items
     * @return array<TKey, TValue>
     */
    protected function getArrayableItems(mixed $items): array
    {
        if (is_array($items)) {
            return $items;
        }

        if ($items instanceof self) {
            return $items->all();
        }

        if ($items instanceof Traversable) {
            return iterator_to_array($items);
        }

        return (array) $items;
    }

    /**
     * Get all of the items in the collection.
     *
     * @return array<TKey, TValue>
     */
    public function all(): array
    {
        return $this->items;
    }

    /**
     * Run a map over each of the items.
     *
     * @template TMapValue
     * @param  callable(TValue, TKey): TMapValue  $callback
     * @return self<TKey, TMapValue>
     */
    public function map(callable $callback): self
    {
        $keys = array_keys($this->items);

        try {
            $items = array_map($callback, $this->items, $keys);
        } catch (\ArgumentCountError) {
            $items = array_map($callback, $this->items);
        }

        return new self(array_combine($keys, $items));
    }

    /**
     * Run a filter over each of the items.
     *
     * @param  (callable(TValue, TKey): bool)|null  $callback
     * @return self<TKey, TValue>
     */
    public function filter(?callable $callback = null): self
    {
        if ($callback) {
            return new self(array_filter($this->items, $callback, ARRAY_FILTER_USE_BOTH));
        }

        return new self(array_filter($this->items));
    }

    /**
     * Filter items by the given callback, returning items where callback is falsey.
     *
     * @param  callable|mixed  $callback
     * @return self<TKey, TValue>
     */
    public function reject(mixed $callback): self
    {
        if (is_callable($callback)) {
            return $this->filter(fn ($item, $key) => ! $callback($item, $key));
        }

        return $this->filter(fn ($item) => $item !== $callback);
    }

    /**
     * Reset the keys on the underlying array.
     *
     * @return self<int, TValue>
     */
    public function values(): self
    {
        return new self(array_values($this->items));
    }

    /**
     * Get the keys of the collection items.
     *
     * @return self<int, TKey>
     */
    public function keys(): self
    {
        return new self(array_keys($this->items));
    }

    /**
     * Get the first item from the collection passing the given truth test.
     *
     * @param  (callable(TValue, TKey): bool)|null  $callback
     * @param  mixed  $default
     * @return TValue|mixed
     */
    public function first(?callable $callback = null, mixed $default = null): mixed
    {
        if (is_null($callback)) {
            if (empty($this->items)) {
                return $default instanceof \Closure ? $default() : $default;
            }

            foreach ($this->items as $item) {
                return $item;
            }
        }

        foreach ($this->items as $key => $value) {
            if ($callback($value, $key)) {
                return $value;
            }
        }

        return $default instanceof \Closure ? $default() : $default;
    }

    /**
     * Sort the collection using the given callback.
     *
     * @param  callable(TValue, TKey): mixed  $callback
     * @return self<TKey, TValue>
     */
    public function sortBy(callable $callback): self
    {
        $results = [];

        foreach ($this->items as $key => $value) {
            $results[$key] = $callback($value, $key);
        }

        asort($results);

        foreach (array_keys($results) as $key) {
            $results[$key] = $this->items[$key];
        }

        return new self($results);
    }

    /**
     * Determine if the collection is empty or not.
     *
     * @return bool
     */
    public function isEmpty(): bool
    {
        return empty($this->items);
    }

    /**
     * Determine if the collection is not empty.
     *
     * @return bool
     */
    public function isNotEmpty(): bool
    {
        return ! $this->isEmpty();
    }

    /**
     * Concatenate values of a given key as a string.
     *
     * @param  string  $glue
     * @param  string  $finalGlue
     * @return string
     */
    public function join(string $glue, string $finalGlue = ''): string
    {
        if ($finalGlue === '') {
            return implode($glue, $this->items);
        }

        if (count($this->items) === 0) {
            return '';
        }

        if (count($this->items) === 1) {
            return (string) reset($this->items);
        }

        $items = array_values($this->items);
        $finalItem = array_pop($items);

        return implode($glue, $items).$finalGlue.$finalItem;
    }

    /**
     * Count the number of items in the collection.
     *
     * @return int
     */
    public function count(): int
    {
        return count($this->items);
    }

    /**
     * Get an iterator for the items.
     *
     * @return ArrayIterator<TKey, TValue>
     */
    public function getIterator(): Traversable
    {
        return new ArrayIterator($this->items);
    }

    public function offsetExists(mixed $offset): bool
    {
        return array_key_exists($offset, $this->items);
    }

    public function offsetGet(mixed $offset): mixed
    {
        return $this->items[$offset];
    }

    public function offsetSet(mixed $offset, mixed $value): void
    {
        if (is_null($offset)) {
            $this->items[] = $value;
        } else {
            $this->items[$offset] = $value;
        }
    }

    public function offsetUnset(mixed $offset): void
    {
        unset($this->items[$offset]);
    }
}

