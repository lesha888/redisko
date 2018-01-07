<?php

namespace Redisko;

use Redis;
use Redisko\Exception\InvalidArgumentException;
use Redisko\Serializer\SerializerInterface;

abstract class Entity
{
    use SerializebleTrait;

    /**
     * The name of the redis entity (key)
     * @var string
     */
    protected $name;

    /**
     * Holds the redis connection
     * @var Redis
     */
    protected $redis;

    /**
     * Constructor
     * @param string $name the name of the entity
     * @param Redis $redis the redis connection to use with this entity
     * @param SerializerInterface $serializer
     * @throws InvalidArgumentException
     */
    public function __construct(string $name, Redis $redis = null, SerializerInterface $serializer = null)
    {
        if ($name === '') {
            throw new InvalidArgumentException('Name is empty');
        }
        $this->name = $name;
        if ($redis) {
            $this->setRedis($redis);
        }
        if ($serializer) {
            $this->setSerializer($serializer);
        }
    }

    /**
     * Sets the expiration time in seconds to this entity
     * @param integer number of expiration for this entity in seconds
     * @return bool
     * @throws \Exception
     */
    public function expire(int $seconds): bool
    {
        return $this->redis->expire($this->name, $seconds);
    }

    /**
     * Remove the existing timeout on key
     * @return bool
     */
    public function persist(): bool
    {
        return $this->redis->persist($this->name);
    }

    /**
     * Returns the remaining time to live of a key that has a timeout, in seconds.
     * The command returns -2 if the key does not exist.
     * The command returns -1 if the key exists but has no associated expire.
     * @return  int     the time left to live in seconds.
     * @link    http://redis.io/commands/ttl
     * @example $redis->ttl('key');
     */
    public function ttl(): int
    {
        return $this->redis->ttl($this->name);
    }

    /**
     * Verify if the specified key exists
     * @return bool
     */
    public function exists(): bool
    {
        return $this->redis->exists($this->name);
    }


    /**
     * @return bool
     */
    public function delete(): bool
    {
        return $this->redis->delete($this->name);
    }

    /**
     * @param Redis $redis
     * @return $this
     */
    public function setRedis(Redis $redis)
    {
        $this->redis = $redis;

        return $this;
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }


}