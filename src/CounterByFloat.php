<?php

namespace Redisko;

use Redisko\Exception\SerializerIsNotSupportedException;
use Redisko\Serializer\SerializerInterface;


/**
 * Represents a redis counter that can be atomically incremented and decremented.
 * <pre>
 * $counter = new Redisko\CounterByFloat("totalPageViews");
 * $counter->increment();
 * echo $counter->getValue();
 * </pre>

 */
class CounterByFloat extends Entity
{
    /**
     * The value of the counter
     * @var integer
     */
    protected $_value;

    /**
     * Removes all the items from the entity
     * @return $this
     * @throws \Exception
     */
    public function clear()
    {
        $this->_value = null;
        $this->redis->delete($this->name);

        return $this;
    }

    /**
     * Gets the value of the counter
     * @param boolean $forceRefresh whether to fetch the data from redis again or not
     * @return float the value of the counter
     */
    public function getValue($forceRefresh = false): float
    {
        if ($this->_value === null || $forceRefresh) {
            $this->_value = $this->redis->get($this->name);
        }

        return (float)$this->_value;
    }

    /**
     * Increments the counter by the given amount
     * @param float|string|int $byAmount the amount to increment by, defaults to 1
     * @return string|false the new value of the counter
     */
    public function increment($byAmount = 1)
    {
        return $this->_value = $this->redis->incrByFloat($this->name, $byAmount);
    }

    /**
     * Decrements the counter by the given amount
     * @param float|string|int $byAmount the amount to decrement by, defaults to 1
     * @return string|false the new value of the counter
     */
    public function decrement($byAmount = 1)
    {
        return $this->_value = $this->redis->incrByFloat($this->name, 0 - $byAmount);
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