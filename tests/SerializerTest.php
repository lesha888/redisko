<?php

namespace Tests;

use PHPUnit_Framework_AssertionFailedError;
use Redisko\HashTable;
use Redisko\RedisList;
use Redisko\Serializer\JsonArray;
use Redisko\Serializer\PhpSerializer;
use Redisko\Set;
use Redisko\SortedSet;

class SerializerTest extends AbstractTestCase
{
    /**
     * Tests the basic functionality
     * @throws PHPUnit_Framework_AssertionFailedError
     * @throws \Exception
     */
    public function testHashTableWithJson()
    {
        $data = [
            'one' => [1.40],
            'two' => ['price' => 2.40],
            'three' => ['null' => null, 'false' => false, 'true' => true, '1' => 1, '0' => 0, 'string' => 'some_text'],
            'four' => null,
        ];
        $set = new HashTable('TestHash:'.uniqid('', true), $this->redis, new JsonArray);

        foreach ($data as $key => $value) {
            $this->assertTrue($set->set($key, $value));
            $this->assertSame($value, $set->get($key));
            $this->assertSame($value, $set[$key]);
        }
        $this->assertEquals(count($data), count($set->getData(true)));
        $this->assertEquals(count($data), count($set));
        $this->assertEquals(count($data), $set->getCount());
        $this->assertEquals(count($data), $set->getCount(true));
        $set->clear();
    }

    /**
     * @expectedException \Redisko\Exception\LogicException
     */
    public function testHashTableIncrementWithJson()
    {
        $set = new HashTable('TestHash:'.uniqid('', true), $this->redis, new JsonArray);
        $set->increment('key');
        $set->clear();

    }

    /**
     * @expectedException \Redisko\Exception\LogicException
     */
    public function testHashTableIncrementByFloatWithJson()
    {
        $set = new HashTable('TestHash:'.uniqid('', true), $this->redis, new JsonArray);
        $set->setSerializer(new JsonArray);
        $set->incrementByFloat('key', 0.1);
        $set->clear();
    }

    /**
     * @throws PHPUnit_Framework_AssertionFailedError
     */
    public function testListWithJson()
    {
        $data = [
            [1.40],
            ['price' => 2.40],
            ['null' => null, 'false' => false, 'true' => true, '1' => 1, '0' => 0, 'string' => 'some_text'],
            null,
        ];
        $set = new RedisList('TestList:'.uniqid('', true), $this->redis, new JsonArray);

        foreach ($data as $key => $value) {
            $this->assertTrue($set->add($value));
            $this->assertSame($value, $set->pop());
        }
        $set->clear();
    }

    /**
     * @expectedException \Redisko\Exception\NotEncodableValueException
     * @throws PHPUnit_Framework_AssertionFailedError
     */
    public function testNotEncodableValueExceptionWithJson()
    {
        $redis = $this->getConnection();
        $hashTable = new HashTable('not_encodable_value_exception_test:hash'.uniqid('', true), $redis);
        $hashTableSerialized = new HashTable($hashTable->getName(), $redis, new JsonArray);
        $this->assertTrue($hashTable->set('key', 'value'));
        $hashTableSerialized->get('key');
    }

    /**
     * Tests the basic functionality
     * @throws PHPUnit_Framework_AssertionFailedError
     * @throws \Exception
     */
    public function testHashTableWithPhpSerializer()
    {
        $redis = $this->redis;

        $data = [
            'one' => [1.40],
            'two' => ['price' => 2.40],
            'three' => ['null' => null, 'false' => false, 'true' => true, '1' => 1, '0' => 0, 'string' => 'some_text'],
            'four' => new \DateTime(),
        ];
        $set = new HashTable('TestHash:'.uniqid('', true), $redis, new PhpSerializer());

        foreach ($data as $key => $value) {
            $this->assertTrue($set->set($key, $value));
            $this->assertEquals($value, $set->get($key));
            $this->assertEquals($value, $set[$key]);
        }
        $this->assertFalse($set->get('not_found'));

        $this->assertEquals(\count($data), \count($set->getData()));
        $this->assertEquals(\count($data), $set->getCount());
        $this->assertEquals(\count($data), $set->getCount(true));
        $set->clear();
    }

    /**
     * @throws PHPUnit_Framework_AssertionFailedError
     * @throws \PHPUnit_Framework_Exception
     */
    public function testListWithPhpSerializer()
    {
        $data = [
            [1.40],
            ['price' => 2.40],
            ['null' => null, 'false' => false, 'true' => true, '1' => 1, '0' => 0, 'string' => 'some_text'],
        ];
        $set = new RedisList('TestList:'.uniqid('', true), $this->redis, new PhpSerializer());

        foreach ($data as $key => $value) {
            $this->assertTrue($set->add($value));
            $this->assertSame($value, $set->pop());
        }
        $value = new \DateTime();
        $this->assertTrue($set->add($value));
        $this->assertInstanceOf(\DateTime::class, $value);
        $this->assertEquals($value, $set->pop());

        $set->clear();
    }
    /**
     * @throws PHPUnit_Framework_AssertionFailedError
     * @throws \PHPUnit_Framework_Exception
     */
    public function testListWithBasic()
    {
        $data = [
            [1.40],
            ['price' => 2.40],
            ['null' => null, 'false' => false, 'true' => true, '1' => 1, '0' => 0, 'string' => 'some_text'],
        ];
        $set = new RedisList('TestList:'.uniqid('', true), $this->redis, new PhpSerializer());

        $set->addMulti(...$data);
        foreach ($data as $key => $value) {
            $this->assertSame($value, $set->shift());
        }
        $value = new \DateTime();
        $this->assertTrue($set->add($value));
        $this->assertInstanceOf(\DateTime::class, $value);
        $this->assertEquals($value, $set->pop());

        $set->clear();
    }

    /**
     * @throws \Exception
     * @expectedException \TypeError
     */
    public function testSerializerCallDeserializeMany()
    {
        $list = new RedisList('TestHashTable:'.uniqid('', true), $this->redis);
        $this->redis->set($list->getName(), 'value');
        $list->range();
        $list->delete();
    }

    /**
     * @expectedException \Redisko\Exception\SerializerIsNotSupportedException
     */
    public function testSetSetSerializer()
    {
        $redis = $this->getConnection();
        new Set('TestSet:'.uniqid('', true), $redis, new JsonArray);
    }

    /**
     * @expectedException \Redisko\Exception\SerializerIsNotSupportedException
     */
    public function testSortedSetSetSerializer()
    {
        $redis = $this->getConnection();
        new SortedSet('TestSortedSet:'.uniqid('', true), $redis, new JsonArray);
    }

}