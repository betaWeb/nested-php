<?php

namespace BetaWeb;

class Node implements \ArrayAccess
{

    protected $node = [];

    private $treeInstance = null;

    /**
     * Node constructor.
     * @param array $node
     * @param Tree|null $treeInstance
     */
    public function __construct($node, $treeInstance = null)
    {
        $this->node = $node;
        $this->treeInstance = $treeInstance;
        $this->mapProperties();
    }

    public function has($key)
    {
        return property_exists($this, $key);
    }

    /**
     * @param string $key
     * @param mixed $value
     * @return Node
     */
    public function set(string $key, $value): self
    {
        $this->{$key} = $value;
        return $this;
    }

    /**
     * @param string $key
     * @param mixed|null $defaultValue
     * @return mixed
     */
    public function get(string $key, $defaultValue = null)
    {
        if ($this->has($key)) {
            return $this->{$key};
        }
        return $defaultValue;
    }

    public function getTreeInstance()
    {
        return $this->treeInstance ?? null;
    }

    /**
     * @return string
     */
    public function getId(): string
    {
        return $this->get(Tree::$PROPERTIES['NODE_ID']);
    }

    /**
     * @return string
     */
    public function getParentId(): string
    {
        return $this->get(Tree::$PROPERTIES['PARENT_ID']);
    }

    /**
     * @return string
     */
    public function getPreviousId(): string
    {
        return $this->get(Tree::$PROPERTIES['PREV_ID']);
    }

    /**
     * @return string
     */
    public function getNextId(): string
    {
        return $this->get(Tree::$PROPERTIES['NEXT_ID']);
    }

    /**
     * @return string
     */
    public function getRootId(): string
    {
        return $this->get(Tree::$PROPERTIES['ROOT_ID']);
    }

    public function getDepth(): int
    {
        return $this->get(Tree::$PROPERTIES['DEPTH']);
    }

    /**
     * @return NodeList
     */
    public function childNodes()
    {
        if (!is_null($this->treeInstance)) {
            return $this->get($this->treeInstance->options['CHILDREN_KEY'], new NodeList);
        }
        return new NodeList;
    }

    /**
     * @return bool
     */
    public function hasChildNodes(): bool
    {
        $children = $this->childNodes();
        if ($children instanceof NodeList) {
            return !$children->empty();
        }
        return isset($children) && !empty($children);
    }

    /**
     * @param string $key
     * @return mixed|null
     */
    public function __get(string $key)
    {
        return array_key_exists($key, $this->node)
            ? $this->node->{$key}
            : null;
    }

    private function mapProperties()
    {
        foreach ($this->node as $propertyName => $propertyValue) {
            $this->set($propertyName, $propertyValue);
        }
    }

    public function offsetExists($offset)
    {
        return property_exists($this, $offset);
    }

    public function offsetGet($offset)
    {
        return $this->{$offset};
    }

    public function offsetSet($offset, $value)
    {
        $this->{$offset} = $value;
    }

    public function offsetUnset($offset)
    {
        return true;
    }
}