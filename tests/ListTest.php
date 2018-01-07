<?php

namespace Tests;

use Redisko\RedisList;

/**
 * Tests for the {@link Redisko\ARedisList} class
 * .tests
 */
class ListTest extends AbstractTestCase
{
    /**
     * Tests the basic functionality
     * @throws \Redisko\Exception\RedisException
     * @throws \PHPUnit_Framework_AssertionFailedError
     * @throws \PHPUnit_Framework_Exception
     */
    public function testBasics()
    {
        $redis = $this->getConnection();
        $list = new RedisList('TestSet:'.uniqid('', true), $redis);

        $list[] = 'Hello';
        $list[] = 'World_BAD';
        $list[1] = 'World';
        $this->assertEquals('World', $list->pop());
        $this->assertEquals('Hello', $list->pop());
        $this->assertEquals(0, $list->getCount());
        $this->assertFalse($redis->exists($list->getName()));

        $testData = array();
        for ($i = 0; $i < 100; $i++) {
            $testData[] = 'Test Item '.$i;
        }
        $list = new RedisList('Test_List'.uniqid('', true), $redis);
        $list->copyFrom($testData);
        $this->assertCount(100, $list);
        foreach ($list as $i => $item) {
            $this->assertEquals($testData[$i], $item);
        }
        $list->clear();

        $this->assertFalse($redis->exists($list->getName()));
    }

    public function testAddMulti()
    {
        $redis = $this->getConnection();
        $list = new RedisList('TestSet:'.uniqid('', true), $redis);
        $this->assertSame(2, $list->addMulti('Hello', 'World'));
        $this->assertEquals(2, $list->getCount());
        $this->assertSame(4, $list->addMulti('Hello', 'World'));

        $this->assertEquals('World', $list->pop());
        $this->assertEquals('Hello', $list->pop());
        $this->assertEquals('World', $list->pop());
        $this->assertEquals('Hello', $list->pop());

        $list->clear();
    }

    /**
     * @throws \PHPUnit_Framework_AssertionFailedError
     */
    public function testAdd()
    {
        $redis = $this->getConnection();
        $list = new RedisList('TestSet:'.uniqid('', true), $redis);
        $redis->set($list->getName(), 'some');
        $this->assertFalse($list->add('Hello'));
        $this->assertFalse($list->addMulti('Hello', 'World'));
        $this->assertFalse($list->set(0, 'Hello'));
        $this->assertFalse($list->set(1, 'Hello'));
        $list->delete();
    }

    /**
     * @throws \PHPUnit_Framework_AssertionFailedError
     * @throws \PHPUnit_Framework_Exception
     */
    public function testRemoveItem()
    {
        $redis = $this->getConnection();
        $list = new RedisList('RedisListTestRemove:'.uniqid('', true), $redis);
        $this->assertTrue($list->add('Hello'));
        $this->assertCount(1, $list);

        $this->assertFalse($list->removeItem('World'));
        $this->assertTrue($list->removeItem('Hello'));
        $this->assertFalse($list->removeItem('Hello'));
        $this->assertFalse($list->removeItem('World'));
        $this->assertCount(0, $list);
        $list->clear();
    }

    /**
     * @throws \PHPUnit_Framework_AssertionFailedError
     * @throws \PHPUnit_Framework_Exception
     */
    public function testInsert()
    {
        $redis = $this->getConnection();
        $list = new RedisList('RedisList:testInsert:'.uniqid('', true), $redis);
        $this->assertTrue($list->add('World'));
        $this->assertCount(1, $list);
        $this->assertSame(2, $list->insertBefore('World', 'Hello'));
        $this->assertCount(2, $list);
        $this->assertSame(3, $list->insertAfter('World', '!'));
        $this->assertCount(3, $list);

        $this->assertSame('!', $list->pop());
        $this->assertSame('World', $list->pop());
        $this->assertSame('Hello', $list->pop());
        $this->assertCount(0, $list);
        $list->clear();
    }

    /**
     * @throws \PHPUnit_Framework_AssertionFailedError
     * @throws \PHPUnit_Framework_Exception
     */
    public function testPush()
    {
        $redis = $this->getConnection();
        $list = new RedisList('RedisList:testInsert:'.uniqid('', true), $redis);
        $this->assertTrue($list->push('Hello'));
        $this->assertCount(1, $list);
        $this->assertTrue($list->push('World'));
        $this->assertSame('World', $list->pop());
        $this->assertSame('Hello', $list->pop());
        $this->assertCount(0, $list);
        $list->clear();
    }

    /**
     * @throws \PHPUnit_Framework_AssertionFailedError
     * @throws \PHPUnit_Framework_Exception
     */
    public function testshift()
    {
        $redis = $this->getConnection();
        $list = new RedisList('RedisList:testInsert:'.uniqid('', true), $redis);
        $this->assertTrue($list->unshift('First'));
        $this->assertCount(1, $list);
        $this->assertSame('First', $list->shift('First'));
        $this->assertCount(0, $list);
        $this->assertTrue($list->unshift('World'));
        $this->assertCount(1, $list);
        $this->assertTrue($list->unshift('Hello'));
        $this->assertSame('World', $list->pop());
        $this->assertSame('Hello', $list->pop());
        $this->assertCount(0, $list);
        $redis->set($list->getName(), 'value');
        $this->assertFalse($list->unshift('Hello'));
        $list->clear();
    }

    /**
     * @throws \PHPUnit_Framework_AssertionFailedError
     * @throws \PHPUnit_Framework_Exception
     */
    public function testTrim()
    {
        $redis = $this->getConnection();
        $list = new RedisList('RedisList:testInsert:'.uniqid('', true), $redis);
        $this->assertTrue($list->push('Hello'));
        $this->assertTrue($list->push('World'));
        $this->assertTrue($list->push('!'));
        $this->assertSame(['Hello', 'World', '!'], $list->getData());
        $this->assertSame(['Hello', 'World', '!'], $list->getData(true));
        $this->assertCount(3, $list);
        $this->assertTrue($list->trim(1, -1));
        $this->assertCount(2, $list);
        $this->assertSame(2, $list->getCount(true));
        $this->assertSame('!', $list->pop());
        $this->assertSame('World', $list->pop());
        $list->clear();
    }

    /**
     * @throws \Redisko\Exception\RedisException
     */
    public function testCopyFrom()
    {
        $redis = $this->getConnection();
        $data = ['Hello', 'World', '!'];
        $list = new RedisList('RedisList:testInsert:'.uniqid('', true), $redis);
        $list->copyFrom($data);
        $this->assertSame($data, $list->getData());
        $this->assertSame($data, $list->getData(true));
        $list->copyFrom($data);
        $this->assertSame($data + $data, $list->getData(true));
    }

    /**
     * @expectedException \Redisko\Exception\RedisException
     */
    public function testCopyFromDataFalse()
    {
        $redis = $this->getConnection();
        $list = new RedisList('RedisList:testInsert:'.uniqid('', true), $redis);
        $list->copyFrom(false);
    }

    /**
     * @throws \PHPUnit_Framework_AssertionFailedError
     * @throws \PHPUnit_Framework_Exception
     */
    public function testCount()
    {
        $redis = $this->getConnection();
        $list = new RedisList('RedisList:testInsert:'.uniqid('', true), $redis);
        $this->assertTrue($list->unshift('World'));
        $this->assertSame(1, $list->count());
        $this->assertTrue($list->unshift('Hello'));
        $this->assertSame(2, $list->count());
        $list->clear();
    }

    /**
     * @expectedException \Redisko\Exception\NotSupportedException
     * @throws \PHPUnit_Framework_AssertionFailedError
     */
    public function testRemove()
    {
        $redis = $this->getConnection();
        $list = new RedisList('RedisList:testInsert:'.uniqid('', true), $redis);
        $this->assertTrue($list->remove(0));
    }
}