<?php

namespace BetaWeb;

use ArrayAccess;
use ArrayIterator;
use Countable;
use IteratorAggregate;
use Traversable;

class NodeList implements ArrayAccess, Countable, IteratorAggregate
{
    private $nodes = [];

    public function __construct(array $nodes = [])
    {
        $this->nodes = $nodes;
    }

    public function empty()
    {
        return empty($this->nodes);
    }

    public function length()
    {
        return count($this->nodes);
    }

    public function all()
    {
        return $this->nodes;
    }

    public function first()
    {
        return reset($this->nodes);
    }

    public function last()
    {
        return end($this->nodes);
    }

    public function get(int $index)
    {
        return $this->nodes[$index];
    }

    /**
     * @param $value
     */
    public function push($value)
    {
        $this->nodes[] = $value;
    }

    /**
     * @param NodeList|array $nodes
     * @return NodeList
     */
    public function merge($nodes): self
    {
        if ($nodes instanceof NodeList) {
            $nodes = $nodes->all();
        }
        $this->nodes = array_merge($this->nodes, $nodes);
        return $this;
    }

    /**
     * @param string $key
     * @param mixed $value
     * @return NodeList
     */
    public function findBy(string $key, $value)
    {
        return new NodeList(array_filter($this->nodes, function ($node) use ($key, $value) {
            return isset($node[$key]) && $node[$key] === $value;
        }));
    }

    public function offsetExists($offset)
    {
        return array_key_exists($offset, $this->nodes);
    }

    public function offsetGet($offset)
    {
        return $this->nodes[$offset];
    }

    public function offsetSet($offset, $value)
    {
        $this->nodes[$offset] = $value;
    }

    public function offsetUnset($offset)
    {
        unset($this->nodes[$offset]);
    }

    public function count()
    {
        return count($this->nodes);
    }

    /**
     * @return ArrayIterator|Traversable
     */
    public function getIterator()
    {
        return new ArrayIterator($this->nodes);
    }
}