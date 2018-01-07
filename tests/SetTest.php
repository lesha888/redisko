<?php

namespace Tests;

use \Redis;
use Redisko\Exception\RedisException;
use Redisko\Exception\SerializerIsNotSupportedException;
use Redisko\Set;

/**
 * Tests for the {@link \Redisko\Set} class
 * .tests
 */
class SetTest extends AbstractTestCase
{
    /**
     * Tests the basic functionality
     * @throws \PHPUnit_Framework_AssertionFailedError
     * @throws \Exception
     */
    public function testBasics()
    {
        $redis = $this->getConnection();
        $set = new Set('TestSet:'.uniqid('', true), $redis);
        $this->assertTrue($set->add('fish'));
        $this->assertTrue($set->add('chips'));
        $this->assertEquals(2, $set->getCount());
        $this->assertEquals(2, $set->getCount(true));
        $this->assertTrue($set->contains('fish'));
        $this->assertTrue($set->contains('chips'));
        $this->assertTrue($set->remove('fish'));
        $this->assertTrue($set->remove('chips'));
        $this->assertFalse($set->contains('fish'));
        $this->assertFalse($set->contains('chips'));
        $this->assertTrue($set->add('fish'));
        $this->assertTrue($set->add('chips'));
        $this->assertTrue(in_array($set->random(), array('fish', 'chips')));
        $this->assertTrue(in_array($set->pop(), array('fish', 'chips')));
        $this->assertTrue(in_array($set->pop(), array('fish', 'chips')));
        $this->assertFalse($set->contains('fish'));
        $this->assertFalse($set->contains('chips'));

        $redis->set($set->getName(), 'stub');
        $this->assertFalse($set->add('some'));
        $this->assertFalse($set->remove('some'));
        $set->clear();
    }


    /**
     * @throws \Exception
     * @throws \PHPUnit_Framework_AssertionFailedError
     */
    public function testDiff()
    {
        $redis = $this->getConnection();
        $set1 = new Set('TestSet1:'.uniqid('', true), $redis);
        $set2 = new Set('TestSet2:'.uniqid('', true), $redis);

        try {
            $this->assertTrue($set1->add('1'));
        } catch (\PHPUnit_Framework_AssertionFailedError $e) {
        } catch (\Exception $e) {
        }
        $this->assertTrue($set2->add('1'));
        $this->assertTrue($set1->add('5'));
        $this->assertTrue($set2->add('10'));
        $this->assertTrue($set1->add('20'));
        $this->assertTrue($set2->add('20'));
        $this->assertEquals(array(5), $set1->diff($set2->getName()));
        $this->assertEquals(array(10), $set2->diff($set1->getName()));
        $newSet = $set1->diffStore($this->makeEntity(Set::class), $set2);
        $this->assertEquals(array(5), $newSet->getData());
        $newSet->clear();
        $set1->clear();
        $set2->clear();
    }

    /**
     * @throws \Exception
     * @throws \PHPUnit_Framework_AssertionFailedError
     */
    public function testInter()
    {
        $redis = $this->getConnection();
        $set1 = new Set('TestSet1:'.uniqid('', true), $redis);
        $set2 = new Set('TestSet2:'.uniqid('', true), $redis);

        $this->assertTrue($set1->add('1'));
        $this->assertTrue($set2->add('1'));
        $this->assertTrue($set1->add('5'));
        $this->assertTrue($set2->add('10'));
        $this->assertTrue($set1->add('20'));
        $this->assertTrue($set2->add('20'));
        $this->assertEquals(array(1, 20), $set1->inter($set2->getName()));
        $this->assertEquals(array(1, 20), $set2->inter($set1->getName()));
        $newSet = $set1->interStore($this->makeEntity(Set::class), $set2);
        $this->assertEquals(array(1, 20), $newSet->getData());
        $newSet->clear();
        $set1->clear();
        $set2->clear();
    }

    /**
     * @throws \Exception
     * @throws \PHPUnit_Framework_AssertionFailedError
     */
    public function testUnion()
    {
        $redis = $this->getConnection();
        $set1 = new Set('TestSet1:'.uniqid('', true), $redis);
        $set2 = new Set('TestSet2:'.uniqid('', true), $redis);

        $this->assertTrue($set1->add('1'));
        $this->assertTrue($set2->add('1'));
        $this->assertTrue($set1->add('5'));
        $this->assertTrue($set2->add('10'));
        $this->assertTrue($set1->add('20'));
        $this->assertTrue($set2->add('20'));
        $sorted = $set1->union($set2);
        sort($sorted);
        $this->assertEquals(array(1, 5, 10, 20), $sorted);
        $newSet = $set1->unionStore($this->makeEntity(Set::class), $set2);
        $sorted = $newSet->getData();
        sort($sorted);
        $this->assertEquals(array(1, 5, 10, 20), $sorted);
        $newSet->clear();
        $set1->clear();
        $set2->clear();
    }

    /**
     * @throws \Exception
     * @throws \PHPUnit_Framework_AssertionFailedError
     */
    public function testMove()
    {
        $redis = $this->getConnection();
        $set1 = new Set('TestSet1:'.uniqid('', true), $redis);
        $set2 = new Set('TestSet2:'.uniqid('', true), $redis);

        $this->assertTrue($set1->add('1'));
        $this->assertTrue($set2->add('1'));
        $this->assertTrue($set1->add('5'));
        $this->assertTrue($set1->add('10'));

        $this->assertTrue($set1->move($set2, '1'));
        $this->assertFalse($set1->move($set2, '1'));
        $this->assertTrue($set1->move($set2, '5'));
        $this->assertTrue($set1->move($set2, '10'));
        $this->assertEquals(0, $set1->getCount());
        $this->assertEquals(3, $set2->getCount());
        $set1->clear();
        $set2->clear();
    }

    /**
     * @throws \PHPUnit_Framework_AssertionFailedError
     * @throws \PHPUnit_Framework_Exception
     */
    public function testInterfaces()
    {
        $redis = $this->getConnection();
        $set = new Set('TestSet:'.uniqid('', true), $redis);

        $this->assertCount(0, $set);
        $set[] = 'test';
        $this->assertSame('test', $set[0]);
        $set[] = 'test2';
        $this->assertTrue(isset($set[1]));
        $this->assertFalse(isset($set[2]));
        $this->assertCount(2, $set);
        foreach ($set as $item) {
            $this->assertTrue($item === 'test' || $item === 'test2');
        }
        unset($set[0]);
        $this->assertCount(1, $set);
        $set->clear();
    }

    /**
     * @throws \PHPUnit_Framework_Exception
     * @throws \RedisException
     * @throws \Exception
     */
    public function testCopyFrom()
    {
        $redis = $this->getConnection();
        $data = ['Hello', 'World', '!'];
        $set = new Set('RedisList:testInsert:'.uniqid('', true), $redis);
        $set->copyFrom($data);
        $this->assertCount(\count($data), $set);
        foreach ($data as $v) {
            $this->assertTrue($set->contains($v));
        }
        $set->copyFrom($data);
        $this->assertCount(\count($data), $set);
        $set->clear();
    }

    /**
     * @throws \Redisko\Exception\RedisException
     * @expectedException \Redisko\Exception\RedisException
     */
    public function testCopyFromFalse()
    {
        $redis = $this->getConnection();
        $set = new Set('RedisList:testInsert:'.uniqid('', true), $redis);
        $set->copyFrom(false);
    }

    /**
     * @throws \Exception
     * @throws \RedisException
     * @expectedException \Redisko\Exception\RedisException
     */
    public function testCopyFromTrue()
    {
        $redis = $this->getConnection();
        $set = new Set('RedisList:testInsert:'.uniqid('', true), $redis);
        $set->copyFrom(true);
    }

    /**
     * @throws \Exception
     * @throws \PHPUnit_Framework_AssertionFailedError
     * @throws \RedisException
     */
    public function testCopyFromEmpty()
    {
        $redis = $this->getConnection();
        $set = new Set('RedisList:testInsert:'.uniqid('', true), $redis);
        $set->copyFrom([]);
        $set->copyFrom(null);
        $this->assertFalse($set->exists());
    }

    /**
     * @expectedException \Redisko\Exception\SerializerIsNotSupportedException
     * @throws \PHPUnit_Framework_Exception
     */
    public function testInterfacesOffsetWrong()
    {
        $redis = $this->getConnection();
        $set = new Set('TestSet:'.uniqid('', true), $redis);

        $this->assertCount(0, $set);
        $set[10] = 'test';
    }

    /**
     * @expectedException \Redisko\Exception\NotSupportedException
     */
    public function testSet()
    {
        $redis = $this->getConnection();
        $set = new Set('TestSet:'.uniqid('', true), $redis);
        $set->set('key', 'value');
    }


}