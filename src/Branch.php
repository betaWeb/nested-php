<?php

namespace BetaWeb;

use ArrayAccess;
use ArrayIterator;
use Countable;
use IteratorAggregate;
use Traversable;

class Branch implements ArrayAccess, Countable, IteratorAggregate
{
    private $leaves = [];

    public function __construct(array $leaves = [])
    {
        $this->leaves = $leaves;
    }

    public function empty()
    {
        return empty($this->leaves);
    }

    public function notEmpty()
    {
        return !$this->empty();
    }

    public function length()
    {
        return $this->count();
    }

    public function all()
    {
        return $this->leaves;
    }

    public function first()
    {
        return reset($this->leaves) ?: null;
    }

    public function last()
    {
        return end($this->leaves) ?: null;
    }

    /**
     * @param int $index
     * @return Leaf|null
     */
    public function nth(int $index): ?Leaf
    {
        return $this->leaves[$index] ?: null;
    }

    /**
     * @param $value
     */
    public function push($value)
    {
        $this->leaves[] = $value;
    }

    /**
     * @param Branch|array $nodes
     * @return Branch
     */
    public function merge($nodes): self
    {
        if ($nodes instanceof Branch) {
            $nodes = $nodes->all();
        }
        $this->leaves = array_merge($this->leaves, $nodes);
        return $this;
    }

    public function reverse(): self
    {
        $this->leaves = array_reverse($this->leaves);
        return $this;
    }

    /**
     * @param string $key
     * @param mixed $value
     * @return Branch
     */
    public function findBy(string $key, $value)
    {
        return new Branch(array_filter($this->leaves, function ($node) use ($key, $value) {
            return isset($node[$key]) && $node[$key] === $value;
        }));
    }

    /**
     * @param string $key
     * @param $value
     * @return bool
     */
    public function contains(string $key, $value): bool
    {
        return $this->findBy($key, $value)->notEmpty();
    }

    public function offsetExists($offset)
    {
        return array_key_exists($offset, $this->leaves);
    }

    public function offsetGet($offset)
    {
        return $this->leaves[$offset];
    }

    public function offsetSet($offset, $value)
    {
        $this->leaves[$offset] = $value;
    }

    public function offsetUnset($offset)
    {
        unset($this->leaves[$offset]);
    }

    public function count()
    {
        return \count($this->leaves);
    }

    /**
     * @return ArrayIterator|Traversable
     */
    public function getIterator()
    {
        return new ArrayIterator($this->leaves);
    }
}