<?php

use PHPUnit\Framework\TestCase;
use BetaWeb\Branch;
use BetaWeb\Tree;
use BetaWeb\Leaf;

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
        $this->assertInstanceOf(Leaf::class, $this->tree->getCollection()[0]);
    }

    public function testRetrieveNodesByName()
    {
        $branch = $this->tree->retrieveNodesBy('name', 'entry 231');
        $this->assertCount(1, $branch);
        $this->assertEquals('entry 231', $branch->first()->name);
    }

    public function testRetrieveNodesByDepth()
    {
        $branch = $this->tree->retrieveNodesByDepth(2);
        $this->assertCount(6, $branch);
        $this->assertEquals(3, mb_strlen($branch->first()->id));
        $this->assertNotEmpty($branch->findBy('name', 'entry 231'));
    }

    public function testHasBreadcrumbMethod()
    {
        $leaf = $this->tree->retrieveNodesBy('name', 'entry 231')->first();
        $this->assertTrue(method_exists($leaf, 'breadcrumb'));
    }

    public function testHasValidBreadcrumb()
    {
        /** @var Leaf $leaf */
        $leaf = $this->tree->retrieveNodesBy('name', 'entry 231')->first();
        /** @var Branch $breadcrumb */
        $breadcrumb = $leaf->breadcrumb();
        $this->assertCount(3, $breadcrumb);
        /** @var Leaf $entry */
        $entry = $breadcrumb->nth(1);
        $this->assertEquals('entry 23', $entry->name);
        /** @var Leaf $entry2 */
        $entry2 = $breadcrumb->first();
        $this->assertEquals($entry2->getId(), $breadcrumb->nth(1)->getParentId());
    }

    public function testNewCreateBranch()
    {
        $leaf = new Leaf(["id" => 33, "name" => "entry 33"], $this->tree);
        $branch = $this->tree->createBranch([$leaf]);
        $this->assertInstanceOf(Branch::class, $branch);
        $this->assertInstanceOf(Leaf::class, $branch->first());
    }

    public function testNewCreateBranchWithoutInstanciateLeaves()
    {
        $leaves = [["id" => 33, "name" => "entry 33"], ["id" => 34, "name" => "entry 34"]];
        $branch = $this->tree->createBranch($leaves);
        $this->assertInstanceOf(Branch::class, $branch);
        $this->assertInstanceOf(Leaf::class, $branch->first());
    }

    public function testCreateAndMergeNewBranch()
    {
        $leaf = new Leaf(["id" => 33, "name" => "entry 33"], $this->tree);
        $branch = $this->tree->createBranch([$leaf]);
        $this->tree->mergeBranch($branch);
        $leaf = $this->tree->retrieveNodesBy('name', 'entry 33')->first();
        $this->assertEquals(33, $leaf->id);
        $this->assertEquals('entry 33', $leaf->name);
    }

    public function testRemoveNode()
    {
        /** @var Leaf $leaf */
        $leaf = $this->tree->retrieveNodesBy('name', 'entry 312')->first();
        $removed = $this->tree->removeNode($leaf->getId());
        $this->assertEmpty($this->tree->retrieveNodesBy('name', 'entry 312'));
        $this->assertTrue($removed);
    }

    public function testRemoveNodeThatNotExists()
    {
        $removed = $this->tree->removeNode('1234');
        $this->assertFalse($removed);
    }

    public function testRemoveNodesByName()
    {
        /** @var Branch $removed */
        $removed = $this->tree->removeNodesBy('name', 'entry 312');
        $this->assertNotEmpty($removed);
        /** @var Leaf $leaf */
        $leaf = $removed->first();
        $this->assertEquals('entry 312', $leaf->name);
    }

    public function tearDown()
    {
        $this->tree = null;
    }

}