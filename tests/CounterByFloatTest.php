<?php

namespace Tests;

use \Redis;
use Redisko\Counter;
use Redisko\CounterByFloat;
use Redisko\Serializer\JsonArray;


/**
 * Tests for the {@link Redisko\CounterByFloat} class
 * .tests
 */
class CounterByFloatTest extends AbstractTestCase
{
    /**
     * Tests the basic functionality
     * @throws \Exception
     */
    public function testBasics()
    {
        $redis = $this->getConnection();
        $counter = new CounterByFloat('TestCounter:'.uniqid('', true), $redis);
        $this->assertEquals(0, $counter->getValue());
        $this->assertEquals(1, $counter->increment());
        $this->assertEquals(11.1, $counter->increment(10.1));
        $this->assertEquals(11.1, $counter->getValue());
        $this->assertEquals('11.1', (string)$counter);
        $this->assertEquals(10.1, $counter->decrement());
        $this->assertEquals(5.1, $counter->decrement(5));
        $this->assertEquals(0, $counter->decrement(5.1));

        $counter->clear();
        $this->assertFalse($redis->exists($counter->getName()));
    }

    /**
     * @expectedException \Redisko\Exception\SerializerIsNotSupportedException
     */
    public function testSetSerializer()
    {
        $redis = $this->getConnection();
        new CounterByFloat('TestCounter:'.uniqid('', true), $redis, new JsonArray);
    }


}
