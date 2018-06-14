<?php

namespace BetaWeb;

class Tree
{

    const PROPERTIES = [
        'ROOT_ID' => '__rootid',
        'NODE_ID' => '__nodeid',
        'PARENT_ID' => '__parentid',
        'PREV_ID' => '__previd',
        'NEXT_ID' => '__nextid',
        'DEPTH' => '__depth',
        'NODE_ID_PREFIX' => 'node-'
    ];

    const DEFAULT_OPTIONS = [
        'CHILDREN_KEY' => 'children'
    ];

    public $options = [];

    private $_count = 0;

    /** @var Leaf $currentNode */
    private $currentNode = null;

    /** @var array|Branch $collection */
    private $collection = [];

    /**
     * @param array $data
     * @param array $options
     */
    public function __construct($data = [], $options = [])
    {
        $this->options = array_merge($options, static::DEFAULT_OPTIONS);
        $this->collection = $this->buildTree($data);
    }

    /**
     * @return int
     */
    public function getTreeSize(): int
    {
        return $this->_count;
    }

    /**
     * @return Branch
     */
    public function getCollection()
    {
        return $this->collection;
    }

    /**
     * @param Leaf[]|array $leaves
     * @return Branch created branch
     */
    public function createBranch(array $leaves = [])
    {
        foreach ($leaves as $k => $leaf) {
            if (!($leaf instanceof Leaf)) {
                $leaves[$k] = new Leaf($leaf, $this);
            }
        }
        return new Branch($leaves);
    }

    /**
     * @param Branch $branch
     * @param Branch|null $destBranch
     * @return Branch
     */
    public function mergeBranch(Branch $branch, Branch $destBranch = null)
    {
        if (is_null($destBranch)) {
            $destBranch = $this->collection;
        }
        return $destBranch->merge($branch);
    }

    /**
     * @param string $leafId
     * @param Branch|null $collection
     * @return Leaf|null
     */
    public function retrieveNode(string $leafId, ?Branch $collection = null): ?Leaf
    {
        if ($this->currentNode !== null && $this->currentNode->getId() === $leafId) {
            return $this->currentNode;
        }
        if (\is_null($collection)) {
            $collection = $this->collection;
        }
        foreach ($collection as $leaf) {
            if ($leaf->getId() === $leafId) {
                $this->currentNode = $leaf;
                break;
            } elseif ($leaf->hasChildNodes()) {
                $this->currentNode = $this->retrieveNode($leafId, $leaf->childNodes());
            } else {
                $leaf = null;
            }
        }
        return $this->currentNode;
    }

    /**
     * @param string $key
     * @param mixed $value
     * @param Branch|null $collection
     * @return Branch
     */
    public function retrieveNodesBy(string $key, $value, ?Branch $collection = null): Branch
    {
        return $this->_retrieve(function ($acc, $leaf) use ($key, $value) {
            /** @var $leaf Leaf */
            if (isset($leaf[$key]) && $leaf[$key] === $value) {
                /** @var Branch $acc */
                $acc->push($leaf);
            }
            if ($leaf->hasChildNodes()) {
                $acc->merge($this->retrieveNodesBy($key, $value, $leaf->childNodes()));
            }
            return $acc;
        }, $collection);
    }

    /**
     * @param int $depth
     * @param Branch|null $collection
     * @return Branch
     */
    public function retrieveNodesByDepth(int $depth, ?Branch $collection = null): Branch
    {
        return $this->_retrieve(function ($acc, $leaf) use ($depth) {
            /** @var $leaf Leaf */
            if ($leaf->getDepth() === $depth) {
                /** @var Branch $acc */
                $acc->push($leaf);
            }
            if ($leaf->hasChildNodes()) {
                $acc->merge($this->retrieveNodesByDepth($depth, $leaf->childNodes()));
            }
            return $acc;
        }, $collection);
    }

    /**
     * @param string $leafId
     * @param Branch|null $branch
     * @return bool
     */
    public function removeNode(string $leafId, ?Branch $branch = null): bool
    {
        $removed = false;

        if (\is_null($branch)) {
            $branch = $this->collection;
        }

        foreach ($branch as $k => $leaf) {
            if ($leaf->getId() === $leafId) {
                unset($branch[$k]);
                $removed = true;
                break;
            } elseif ($leaf->hasChildNodes()) {
                $removed = $this->removeNode($leafId, $leaf->childNodes());
            }
        }

        return $removed;
    }

    /**
     * @param string $key
     * @param $value
     * @return Branch
     */
    public function removeNodesBy(string $key, $value): Branch
    {
        $removed = new Branch;
        $leaves = $this->retrieveNodesBy($key, $value);
        if ($leaves->notEmpty()) {
            foreach ($leaves as $leaf) {
                if ($this->removeNode($leaf->getId())) {
                    $removed->push($leaf);
                }
            }
        }
        return $removed;
    }

    /**
     * @param Leaf|string $leaf
     * @return Branch
     */
    public function getBreadcrumb($leaf): Branch
    {
        if (\is_string($leaf)) {
            $leaf = $this->retrieveNode($leaf);
        }
        $breadcrumb = new Branch([$leaf]);
        if (!\is_null($leaf) && $leaf->hasParentId()) {
            $breadcrumb = $breadcrumb->merge($this->getBreadcrumb($leaf->getParentId()));
        }
        return $breadcrumb;
    }

    /**
     * @param array $data
     * @param int|null $parentId
     * @param int|null $rootId
     * @param int $depth
     * @return Branch
     */
    private function buildTree($data = [], $parentId = null, $rootId = null, $depth = 0): Branch
    {
        if (\is_null($parentId)) {
            $depth = 0;
        } else {
            $depth += 1;
        }

        $_instance = &$this;

        $tree = array_reduce($data, function ($acc, $leaf) use ($_instance, $parentId, $rootId, $depth) {
            if (!($leaf instanceof Leaf)) {
                $leaf = new Leaf($leaf, $_instance);
            }

            $this->_count += 1;

            $leaf->set(static::PROPERTIES['NODE_ID'], uniqid());
            $leaf->set(static::PROPERTIES['PARENT_ID'], $parentId);

            if (\is_null($parentId)) {
                $rootId = $leaf->getId();
                $leaf->set(static::PROPERTIES['ROOT_ID'], null);
            } else {
                $leaf->set(static::PROPERTIES['ROOT_ID'], $rootId);
            }

            if ($leaf->hasChildNodes()) {
                $leaf->set($_instance->options['CHILDREN_KEY'], $_instance->buildTree($leaf->childNodes(), $leaf->getId(), $rootId, $depth));
            }

            array_push($acc, $leaf);

            return $acc;
        }, []);

        for ($i = 0; $i < count($tree); $i++) {
            /** @var Leaf $leaf */
            $leaf = $tree[$i] ?? null;

            /** @var Leaf $prevNode */
            $prevNode = $tree[$i - 1] ?? null;

            /** @var Leaf $nextNode */
            $nextNode = $tree[$i + 1] ?? null;

            $hasPrevNode = !\is_null($prevNode) && $prevNode instanceof Leaf;
            $hasNextNode = !\is_null($nextNode) && $nextNode instanceof Leaf;

            $leaf->set(static::PROPERTIES['PREV_ID'], $hasPrevNode ? $prevNode->getId() : null);
            $leaf->set(static::PROPERTIES['NEXT_ID'], $hasNextNode ? $nextNode->getId() : null);
            $leaf->set(static::PROPERTIES['DEPTH'], $depth);
        }

        return new Branch($tree);
    }

    private function _retrieve(Callable $callback, ?Branch $collection = null)
    {
        if (\is_null($collection)) {
            $collection = $this->collection;
        }

        if ($collection instanceof Branch) {
            $collection = $collection->all();
        }

        return array_reduce($collection, $callback, new Branch);
    }

}