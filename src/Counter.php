<?php

namespace Redisko;

use Redisko\Exception\SerializerIsNotSupportedException;
use Redisko\Serializer\SerializerInterface;


/**
 * Represents a redis counter that can be atomically incremented and decremented.
 * <pre>
 * $counter = new Redisko\Counter("totalPageViews");
 * $counter->increment();
 * echo $counter->getValue();
 * </pre>

 */
class Counter extends Entity
{
    /**
     * The value of the counter
     * @var integer
     */
    protected $_value;

    /**
     * Removes all the items from the entity
     * @return Counter
     * @throws \Exception
     */
    public function clear(): Counter
    {
        $this->_value = null;
        $this->redis->delete($this->name);

        return $this;
    }

    /**
     * Gets the value of the counter
     * @param boolean $forceRefresh whether to fetch the data from redis again or not
     * @return int the value of the counter
     */
    public function getValue($forceRefresh = false): int
    {
        if ($this->_value === null || $forceRefresh) {
            $this->_value = (int)$this->redis->get($this->name);
        }

        return (int)$this->_value;
    }

    /**
     * Increments the counter by the given amount
     * @param integer $byAmount the amount to increment by, defaults to 1
     * @return int the new value of the counter
     */
    public function increment($byAmount = 1): int
    {
        return $this->_value = (int)$this->redis->incrBy($this->name, $byAmount);
    }

    /**
     * Decrements the counter by the given amount
     * @param integer $byAmount the amount to decrement by, defaults to 1
     * @return int the new value of the counter
     */
    public function decrement($byAmount = 1): int
    {
        return $this->_value = (int)$this->redis->decrBy($this->name, $byAmount);
    }

    /**
     * Gets the value of the counter
     * @return string the value of the counter
     */
    public function __toString()
    {
        return (string)$this->getValue();
    }

    /**
     * @param SerializerInterface $serializer
     * @throws SerializerIsNotSupportedException
     */
    public function setSerializer(SerializerInterface $serializer)
    {
        throw new SerializerIsNotSupportedException('Method '.__METHOD__.' forbidden');
    }
}