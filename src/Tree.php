<?php

namespace BetaWeb;

class Tree
{

    public $options = [];

    private $_count = 0;

    /** @var array|NodeList $collection */
    private $collection = [];

    public static $PROPERTIES = [
        'ROOT_ID' => '__rootid',
        'NODE_ID' => '__nodeid',
        'PARENT_ID' => '__parentid',
        'PREV_ID' => '__previd',
        'NEXT_ID' => '__nextid',
        'DEPTH' => '__depth',
        'NODE_ID_PREFIX' => 'node-'
    ];

    private static $DEFAULT_OPTIONS = [
        'CHILDREN_KEY' => 'children'
    ];

    /**
     * @param array $data
     * @param array $options
     */
    public function __construct($data = [], $options = [])
    {
        $this->options = array_merge($options, static::$DEFAULT_OPTIONS);
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
     * @return NodeList
     */
    public function getCollection()
    {
        return $this->collection;
    }

    /**
     * @param string $nodeId
     * @param NodeList|null $collection
     * @return Node|null
     */
    public function retrieveNode(string $nodeId, ?NodeList $collection = null): ?Node
    {
        $needle = null;
        if (is_null($collection)) {
            $collection = $this->collection;
        }
        foreach ($collection as $node) {
            if ($node->getId() === $nodeId) {
                $needle = $node;
                break;
            } elseif ($node->hasChildNodes()) {
                $needle = $this->retrieveNode($nodeId, $node->childNodes());
            } else {
                $needle = null;
            }
        }
        return $needle;
    }

    /**
     * @param string $key
     * @param mixed $value
     * @param NodeList|null $collection
     * @return NodeList
     */
    public function retrieveNodesBy(string $key, $value, ?NodeList $collection = null): NodeList
    {
        return $this->_retrieve(function ($acc, $node) use ($key, $value) {
            /** @var $node Node */
            if (isset($node[$key]) && $node[$key] === $value) {
                /** @var NodeList $acc */
                $acc->push($node);
            }
            if ($node->hasChildNodes()) {
                $acc->merge($this->retrieveNodesBy($key, $value, $node->childNodes()));
            }
            return $acc;
        }, $collection);
    }

    /**
     * @param int $depth
     * @param NodeList|null $collection
     * @return NodeList
     */
    public function retrieveNodesByDepth(int $depth, ?NodeList $collection = null): NodeList
    {
        return $this->_retrieve(function ($acc, $node) use ($depth) {
            /** @var $node Node */
            if ($node->getDepth() === $depth) {
                /** @var NodeList $acc */
                $acc->push($node);
            }
            if ($node->hasChildNodes()) {
                $acc->merge($this->retrieveNodesByDepth($depth, $node->childNodes()));
            }
            return $acc;
        }, $collection);
    }

    /**
     * @param array $data
     * @param int|null $parentId
     * @param int|null $rootId
     * @param int $depth
     * @return NodeList
     */
    private function buildTree($data = [], $parentId = null, $rootId = null, $depth = 0): NodeList
    {
        if (is_null($parentId)) {
            $depth = 0;
        } else {
            $depth += 1;
        }

        $_instance = &$this;

        $tree = array_reduce($data, function ($acc, $node) use ($_instance, $parentId, $rootId, $depth) {
            if (!($node instanceof Node)) {
                $node = new Node($node, $_instance);
            }

            $this->_count += 1;

            $node->set(static::$PROPERTIES['NODE_ID'], uniqid());
            $node->set(static::$PROPERTIES['PARENT_ID'], $parentId);

            if (is_null($parentId)) {
                $rootId = $node->getId();
                $node->set(static::$PROPERTIES['ROOT_ID'], null);
            } else {
                $node->set(static::$PROPERTIES['ROOT_ID'], $rootId);
            }

            if ($node->hasChildNodes()) {
                $node->set($_instance->options['CHILDREN_KEY'], $_instance->buildTree($node->childNodes(), $node->getId(), $rootId, $depth));
            }

            array_push($acc, $node);

            return $acc;
        }, []);

        for ($i = 0; $i < count($tree); $i++) {
            /** @var Node $node */
            $node = $tree[$i] ?? null;

            /** @var Node $prevNode */
            $prevNode = $tree[$i - 1] ?? null ;

            /** @var Node $nextNode */
            $nextNode = $tree[$i + 1] ?? null;

            $hasPrevNode = !is_null($prevNode) && $prevNode instanceof Node;
            $hasNextNode = !is_null($nextNode) && $nextNode instanceof Node;

            $node->set(static::$PROPERTIES['PREV_ID'], $hasPrevNode ? $prevNode->getId() : null);
            $node->set(static::$PROPERTIES['NEXT_ID'], $hasNextNode ? $nextNode->getId() : null);
            $node->set(static::$PROPERTIES['DEPTH'], $depth);
        }

        return new NodeList($tree);
    }

    private function _retrieve(Callable $callback, ?NodeList $collection = null)
    {
        if (is_null($collection)) {
            $collection = $this->collection;
        }

        if ($collection instanceof NodeList) {
            $collection = $collection->all();
        }

        return array_reduce($collection, $callback, new NodeList);
    }

}