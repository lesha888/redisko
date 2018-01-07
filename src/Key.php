<?php

namespace Redisko;


/**
 * Represents a redis counter that can be atomically incremented and decremented.
 * <pre>
 * $counter = new Redisko\Counter("totalPageViews");
 * $counter->increment();
 * echo $counter->getValue();
 * </pre>
 */
class Key extends Entity
{
    /**
     * @return bool|string
     */
    public function get()
    {
        return $this->deserialize($this->redis->get($this->name));
    }

    /**
     * @param $value
     * @return bool
     */
    public function set($value): bool
    {
        return $this->redis->set($this->name, $this->serialize($value));
    }

    /**
     * @param $value
     * @return bool
     */
    public function setnx($value): bool
    {
        return $this->redis->setnx($this->name, $this->serialize($value));
    }

    /**
     * Gets the value of the counter
     * @return string the value of the counter
     */
    public function __toString()
    {
        return (string)$this->get();
    }
}