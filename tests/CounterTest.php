<?php

namespace Tests;

use Redisko\Counter;
use Redisko\Serializer\JsonArray;


/**
 * Tests for the {@link Redisko\Counter} class
 * .tests
 */
class CounterTest extends AbstractTestCase
{
    /**
     * Tests the basic functionality
     * @throws \Exception
     */
    public function testBasics()
    {
        $redis = $this->getConnection();
        $counter = new Counter('TestCounter:'.uniqid('', true), $redis);
        $this->assertEquals(0, $counter->getValue());
        $this->assertEquals(1, $counter->increment());
        $this->assertEquals(11, $counter->increment(10));
        $this->assertEquals(11, $counter->getValue());
        $this->assertEquals('11', (string)$counter);
        $this->assertEquals(10, $counter->decrement());
        $this->assertEquals(5, $counter->decrement(5));
        $this->assertEquals(0, $counter->decrement(5));

        $counter->clear();
        $this->assertFalse($redis->exists($counter->getName()));
    }

    /**
     * @expectedException \Redisko\Exception\SerializerIsNotSupportedException
     */
    public function testSetSerializer()
    {
        $redis = $this->getConnection();
        new Counter('TestCounter:'.uniqid('', true), $redis, new JsonArray);
    }


}
