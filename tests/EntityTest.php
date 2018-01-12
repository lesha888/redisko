<?php

namespace Tests;

use Redisko\Key;


/**
 * Tests for the {@link Redisko\Entity} class
 * .tests
 */
class EntityTest extends AbstractTestCase
{
    /**
     * @throws \Exception
     * @throws \PHPUnit_Framework_AssertionFailedError
     */
    public function testExpire()
    {
        $redis = $this->redis;
        $key = new Key('TestKey:'.uniqid('', true), $redis);

        $this->assertTrue($key->set('value'));
        $this->assertTrue($key->exists());
        $this->assertTrue($key->expire(1));
        $this->assertTrue($key->exists());
        sleep(1.3);
        $this->assertFalse($key->exists());
    }

    /**
     * @throws \Exception
     * @throws \PHPUnit_Framework_AssertionFailedError
     */
    public function testSomeFunctions()
    {
        $redis = $this->redis;
        $key = new Key('TestKey:'.uniqid('', true), $redis);

        $this->assertTrue($key->set('value'));
        $this->assertTrue($key->expire(10));
        $this->assertTrue($key->persist());
        $this->assertSame(-1, $key->ttl());
        $this->assertTrue($key->delete());
        $this->assertFalse($key->delete());


        $keyNotExists = new Key('TestKey:'.uniqid('', true), $redis);
        $this->assertSame(-2, $keyNotExists->ttl());
    }

    /**
     * @expectedException \Redisko\Exception\InvalidArgumentException
     */
    public function testEmptyNameInConstructor()
    {
        new Key('', $this->redis);
    }

}