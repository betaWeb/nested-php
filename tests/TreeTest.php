<?php

use PHPUnit\Framework\TestCase;
use BetaWeb\Tree;
use BetaWeb\Node;

class TreeTest extends TestCase
{

    /** @var $tree Tree */
    private $tree = null;

    public function setUp()
    {
        $collection = json_decode(file_get_contents(__DIR__ . '/fixtures/collection.json'));
        $this->tree = new Tree($collection);
    }

    public function testHasTreeInstance()
    {
        $this->assertInstanceOf(Tree::class, $this->tree);
    }

    public function testHasValidTree()
    {
        $this->assertNotEmpty($this->tree->getCollection());
        $this->assertInstanceOf(Node::class, $this->tree->getCollection()[0]);
    }

    public function testRetrieveNodesByName()
    {
        $nodes = $this->tree->retrieveNodesBy('name', 'entry 231');
        $this->assertCount(1, $nodes);
        $this->assertEquals('entry 231', $nodes->first()->name);
    }

    public function testRetrieveNodesByDepth()
    {
        $nodes = $this->tree->retrieveNodesByDepth(2);
        $this->assertCount(6, $nodes);
        $this->assertEquals(3, mb_strlen($nodes->first()->id));
        $this->assertNotEmpty($nodes->findBy('name', 'entry 231'));
        var_dump($nodes->findBy('name', 'entry 231')[0]->name);
    }

}