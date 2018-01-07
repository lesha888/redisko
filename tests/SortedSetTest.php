<?php

namespace Tests;

use \Redis;
use Redisko\SortedSet;

/**
 * Tests for the {@link Redisko\SortedSet} class
 * .tests
 */
class SortedSetTest extends AbstractTestCase
{
    /**
     * Tests the basic functionality
     * @throws \PHPUnit_Framework_AssertionFailedError
     * @throws \Exception
     */
    public function testBasics()
    {
        $redis = $this->getConnection();
        $set = new SortedSet('TestSet:'.uniqid('', true), $redis);
        $this->assertTrue($set->add('oranges', 2.40));
        $this->assertTrue(isset($set['oranges']));
        $this->assertSame(2.40 ,$set['oranges']);
        $this->assertTrue($set->add('apples', 1.40));
        $this->assertTrue($set->add('strawberries', 3));
        $this->assertEquals($set->getCount(), 3);
        $this->assertTrue($set->add('carrots', 0.4));
        $this->assertEquals(4, $set->getCount());
        $this->assertTrue($set->remove('carrots'));
        $this->assertFalse($set->remove('carrots'));
        $this->assertEquals(3, $set->getCount());
        $set->clear();

        //$this->assertFalse($set->add('some', 'wrong'));

        $this->assertEquals(0, $set->getCount());
        $redis->set($set->getName(), 'stub');
        $this->assertFalse($set->add('some', 1));
        $this->assertFalse($set->remove('some'));
        $set->clear();
    }
    /**
     * Tests the basic functionality
     * @throws \PHPUnit_Framework_AssertionFailedError
     * @throws \Exception
     */
    public function testIncrement()
    {
        $redis = $this->getConnection();
        $set = new SortedSet('TestSet:'.uniqid('', true), $redis);
        $this->assertTrue($set->add('oranges', 2.40));
        $this->assertSame(3.4, $set->increment('oranges'));
        $this->assertSame(3.4, $set->getScore('oranges'));
        $this->assertSame(13.4, $set->increment('oranges', 10));
        $this->assertSame(13.4, $set->getScore('oranges'));
        $this->assertSame(99.1, $set->increment('apple',99.1));
        $set->clear();
        $this->assertEquals(0, $set->getCount());

        $redis->set($set->getName(), 'stub');
        $this->assertFalse($set->increment('apple',99.1));

        $set->clear();


    }


    public function testInterfaces()
    {
        $redis = $this->getConnection();
        $set = new SortedSet('TestSet:'.uniqid('', true), $redis);

        $this->assertEquals(0, count($set));
        $set['test'] = 24;
        $set['test2'] = 12;
        $this->assertEquals(2, count($set));
        $previous = 0;
        foreach ($set as $item => $value) {
            $this->assertTrue($item == 'test' || $item == 'test2');
            $this->assertGreaterThan($previous, $value);
            $previous = $value;
        }
        unset($set['test']);
        $this->assertEquals(1, count($set));
        $set->clear();
    }

    /**
     * @throws \Exception
     * @throws \PHPUnit_Framework_AssertionFailedError
     */
    public function testInterStore()
    {
        $redis = $this->getConnection();
        $set1 = new SortedSet('TestSet1:'.uniqid('', true), $redis);
        $set2 = new SortedSet('TestSet2:'.uniqid('', true), $redis);
        $this->assertTrue($set1->add('test 1', 1));
        $this->assertTrue($set2->add('test 1', 1));
        $this->assertTrue($set1->add('test 2', 2));
        $this->assertTrue($set2->add('test 3', 3));
        $this->assertTrue($set1->add('test 4', 4));
        $this->assertTrue($set2->add('test 4', 5));
        $destination = $this->makeEntity(SortedSet::class);
        $newSet = $set1->interStore($destination, $set2);
        $this->assertEquals(['test 1' => 2, 'test 4' => 9], $newSet->getData());

        $newSet = $set1->interStore($destination, [$set2]);
        $this->assertEquals(['test 1' => 2, 'test 4' => 9], $newSet->getData());

        $destination->clear();
        $newSet = $set1->interStore($destination, [$set2]);
        $this->assertEquals(['test 1' => 2, 'test 4' => 9], $newSet->getData());


        $newSet->clear();
        $set1->clear();
        $set2->clear();
    }

    /**
     * @throws \Exception
     * @throws \PHPUnit_Framework_AssertionFailedError
     */
    public function testUnionStore()
    {
        $redis = $this->getConnection();
        $set1 = new SortedSet('TestSet1:'.uniqid('', true), $redis);
        $set2 = new SortedSet('TestSet2:'.uniqid('', true), $redis);
        $this->assertTrue($set1->add('test 1', 1));
        $this->assertTrue($set2->add('test 1', 1));
        $this->assertTrue($set1->add('test 2', 2));
        $this->assertTrue($set2->add('test 3', 3));
        $this->assertTrue($set1->add('test 4', 4));
        $this->assertTrue($set2->add('test 4', 5));
        $newSet = $set1->unionStore($this->makeEntity(SortedSet::class), $set2);
        $this->assertEquals(
            array(
                'test 1' => 2,
                'test 2' => 2,
                'test 3' => 3,
                'test 4' => 9,
            ),
            $newSet->getData()
        );

        $newSet->clear();
        $set1->clear();
        $set2->clear();
    }
    /**
     * @throws \Exception
     * @throws \PHPUnit_Framework_AssertionFailedError
     */
    public function testUnionStore2()
    {
        $redis = $this->getConnection();
        $set1 = new SortedSet('TestSet1:'.uniqid('', true), $redis);
        $set2 = new SortedSet('TestSet2:'.uniqid('', true), $redis);
        $this->assertTrue($set1->add('test 1', 1));
        $this->assertTrue($set2->add('test 1', 1));
        $this->assertTrue($set1->add('test 2', 2));
        $this->assertTrue($set2->add('test 3', 3));
        $this->assertTrue($set1->add('test 4', 4));
        $this->assertTrue($set2->add('test 4', 5));
        $newSet = $set1->unionStore($this->makeEntity(SortedSet::class), [$set2]);
        $this->assertEquals(
            array(
                'test 1' => 2,
                'test 2' => 2,
                'test 3' => 3,
                'test 4' => 9,
            ),
            $newSet->getData()
        );

        $newSet->clear();
        $set1->clear();
        $set2->clear();
    }

    /**
     * @expectedException \Redisko\Exception\NotSupportedException
     */
    public function testSet()
    {
        $redis = $this->getConnection();
        $set = new SortedSet('TestSortedSet:'.uniqid('', true), $redis);
        $set->set('key', 'value');
    }


}