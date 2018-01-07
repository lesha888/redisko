<?php

namespace Tests;

use PHPUnit_Framework_AssertionFailedError;
use PHPUnit_Framework_Exception;
use Redisko\HashTable;

/**
 * Tests for the {@link Redisko\Hash} class
 */
class HashTableTest extends AbstractTestCase
{
    /**
     * Tests the basic functionality
     * @throws PHPUnit_Framework_AssertionFailedError
     */
    public function testBasics()
    {
        $redis = $this->redis;
        $set = new HashTable('TestHash:'.uniqid('', true), $redis);
        $this->assertTrue($set->set('oranges', 2.40));
        $this->assertTrue($set->set('apples', 1.40));
        $this->assertTrue($set->set('strawberries', 3));
        $this->assertEquals(3, $set->get('strawberries'));
        $this->assertEquals(3, $set->getCount());
        $this->assertTrue($set->set('carrots', 0.4));
        $this->assertEquals(4, $set->getCount());
        $this->assertTrue($set->remove('carrots'));
        $this->assertFalse($set->remove('carrots'));
        $this->assertEquals(3, $set->getCount());
        $set->clear();
        $redis->set($set->getName(),'stub');
        $this->assertFalse($set->remove('carrots'));
        $this->assertFalse($set->set('key','value'));


        $set->clear();
        $this->assertEquals(0, $set->getCount());
    }

    /**
     * @throws PHPUnit_Framework_Exception
     */
    public function testInterfaces()
    {
        $redis = $this->redis;
        $set = new HashTable('TestHash:'.uniqid('', true), $redis);

        $this->assertCount(0, $set);
        $set['test'] = 24;
        $set['test2'] = 12;
        $this->assertCount(2, $set);
        foreach ($set as $item => $value) {
            $this->assertTrue($item === 'test' || $item === 'test2');
        }
        $this->assertTrue(isset($set['test']));
        unset($set['test']);
        $this->assertCount(1, $set);
        $set->clear();
    }

    /**
     * @throws PHPUnit_Framework_AssertionFailedError
     */
    public function testSetNx()
    {
        $key = uniqid('', true);
        $value = uniqid('', true);
        $set = new HashTable('TestHash:'.uniqid('', true), $this->redis);
        $this->assertTrue($set->setNx($key, $value));
        $this->assertSame($value, (string)$set->get($key));
        $this->assertFalse($set->setNx($key, uniqid('', true)));
        $this->assertSame($value, (string)$set->get($key));
        $set->clear();

    }

    /**
     *
     */
    public function testIncrement()
    {
        $key = 'counter';
        $set = new HashTable('TestHash:'.uniqid('', true), $this->redis);
        $set->increment($key);
        $this->assertSame('1', (string)$set->get($key));
        $set->increment($key, 2);
        $this->assertSame('3', (string)$set->get($key));
        $set->increment($key, -10);
        $this->assertSame('-7', (string)$set->get($key));
        $set->clear();
    }

    /**
     *
     * @throws PHPUnit_Framework_AssertionFailedError
     */
    public function testIncrementByFloat()
    {
        $key = 'counter';
        $set = new HashTable('TestHash:'.uniqid('', true), $this->redis);
        $set->incrementByFloat($key, 0.1);
        $this->assertSame('0.1', (string)$set->get($key));
        $set->incrementByFloat($key, '9.9');
        $this->assertSame('10', (string)$set->get($key));
        $set->incrementByFloat($key, -3);
        $this->assertSame('7', (string)$set->get($key));
        $set->clear();
        $this->assertFalse($set->exists());
        $this->assertEmpty($set->count());
    }
}