<?php

namespace BetaWeb;

class Leaf implements \ArrayAccess
{

    protected $leaf = [];

    private $treeInstance = null;

    /**
     * Leaf constructor.
     * @param array $leaf
     * @param Tree|null $treeInstance
     */
    public function __construct($leaf, $treeInstance = null)
    {
        $this->leaf = $leaf;
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
     * @return Leaf
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
     * @return string|null
     */
    public function getId(): ?string
    {
        return $this->get(Tree::PROPERTIES['NODE_ID']);
    }

    /**
     * @return string|null
     */
    public function getParentId(): ?string
    {
        return $this->get(Tree::PROPERTIES['PARENT_ID']);
    }

    /**
     * @return string|null
     */
    public function getPreviousId(): ?string
    {
        return $this->get(Tree::PROPERTIES['PREV_ID']);
    }

    /**
     * @return string|null
     */
    public function getNextId(): ?string
    {
        return $this->get(Tree::PROPERTIES['NEXT_ID']);
    }

    /**
     * @return string|null
     */
    public function getRootId(): ?string
    {
        return $this->get(Tree::PROPERTIES['ROOT_ID']);
    }

    public function getDepth(): int
    {
        return $this->get(Tree::PROPERTIES['DEPTH']);
    }

    /**
     * @return Branch
     */
    public function childNodes()
    {
        if (!\is_null($this->treeInstance)) {
            return $this->get($this->treeInstance->options['CHILDREN_KEY'], new Branch);
        }
        return new Branch;
    }

    /**
     * @return bool
     */
    public function hasChildNodes(): bool
    {
        $children = $this->childNodes();
        if ($children instanceof Branch) {
            return $children->notEmpty();
        }
        if (\is_array($children)) {
            return isset($children) && !empty($children);
        }
        return false;
    }

    /**
     * @return bool
     */
    public function hasParentId(): bool
    {
        return !\is_null($this->getParentId());
    }

    /**
     * @return bool
     */
    public function hasPreviousId(): bool
    {
        return !\is_null($this->getPreviousId());
    }

    /**
     * @return bool
     */
    public function hasNextId(): bool
    {
        return !\is_null($this->getNextId());
    }

    /**
     * @return bool
     */
    public function hasRootId(): bool
    {
        return !\is_null($this->getRootId());
    }

    /**
     * @return Branch
     */
    public function breadcrumb(): Branch
    {
        if (!\is_null($this->treeInstance)) {
            return $this->treeInstance->getBreadcrumb($this)->reverse();
        }
        return new Branch;
    }

    /**
     * @param string $key
     * @return mixed|null
     */
    public function __get(string $key)
    {
        return array_key_exists($key, $this->leaf)
            ? $this->leaf->{$key}
            : null;
    }

    private function mapProperties()
    {
        foreach ($this->leaf as $propertyName => $propertyValue) {
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