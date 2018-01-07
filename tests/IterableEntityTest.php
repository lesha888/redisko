<?php

namespace Tests;

use PHPUnit_Framework_AssertionFailedError;
use PHPUnit_Framework_Exception;
use Redisko\HashTable;

/**
 * Tests for the {@link Redisko\Hash} class
 * .tests
 */
class IterableEntityTest extends AbstractTestCase
{
    /**
     * Tests the basic functionality
     * @throws PHPUnit_Framework_AssertionFailedError
     */
    public function testGenerator()
    {
        $data = ['key1' => 'value1', 'key2' => 'value2'];
        $redis = $this->redis;
        $set = new HashTable('TestHash:'.uniqid('', true), $redis);
        foreach ($data as $k => $v) {
            $set[$k] = $v;
        }
        $generator = $set->getGenerator();
        foreach ($generator as $k => $v) {
            $this->assertSame($data[$k], $v);
        }
        $set->clearState();
        $this->assertSame($set->get($k), $v);
        $this->assertSame($set->toArray(), $data);
        $this->assertTrue($set->contains($v));
        return;
        $v = 'newvalue2';
        $set[$k] = $v;
        $this->assertSame($set[$k], $v);
    }


}