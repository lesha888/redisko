<?php

namespace Tests;

use PHPUnit\Framework\TestCase;
use Redis;
use Redisko\Entity;
use Redisko\Factory;
use Redisko\Serializer\SerializerInterface;

abstract class AbstractTestCase extends TestCase
{
    /**
     * @var Redis
     */
    protected $redis;

    protected function setUp()
    {
        $this->redis = new Redis();
        $this->redis->connect('localhost');
        $this->redis->setOption(Redis::OPT_PREFIX, 'tests_redis_abstract:');
        $this->assertEquals('+PONG', $this->redis->ping());
    }

    public function getConnection(): Redis
    {
        return $this->redis;
    }

    /**
     * @param string $class
     * @param string|null $name
     * @param SerializerInterface|null $serializer
     * @return Entity
     */
    public function makeEntity(string $class, string $name = null, SerializerInterface $serializer = null): Entity
    {
        if ($name === null) {
            $name = 'Entity:'.uniqid('', true);
        }

        return new $class($name, $this->redis, $serializer);
    }

}