<?php

namespace Tests;

use Redisko\Key;


/**
 * Tests for the {@link Redisko\Key} class
 * .tests
 */
class KeyTest extends AbstractTestCase
{
    /**
     * @throws \PHPUnit_Framework_AssertionFailedError
     */
    public function testGet()
    {
        $key = new Key('key'.uniqid('', true), $this->redis);
        $value = uniqid('', true);
        $this->assertTrue($key->set($value));
        $this->assertSame($value, $key->get($value));
        $this->assertTrue($key->delete());
        $this->assertFalse($key->exists());
    }

    /**
     * @throws \PHPUnit_Framework_AssertionFailedError
     */
    public function testSetNx()
    {
        $key = new Key('key'.uniqid('', true), $this->redis);
        $value = uniqid('', true);
        $this->assertTrue($key->set($value));
        $this->assertFalse($key->setnx(uniqid('', true)));
        $this->assertSame($value,$key->get());
        $this->assertSame($value,(string)$key);
        $this->assertTrue($key->delete());
        $this->assertFalse($key->exists());
    }


}