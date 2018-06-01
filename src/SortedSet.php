<?php

namespace Redisko;

use CMapIterator;
use Iterator;
use Redisko\Exception\NotSupportedException;
use Redisko\Exception\SerializerIsNotSupportedException;
use Redisko\Serializer\SerializerInterface;


/**
 * Represents a redis sorted set.
 *
 * Redis Sorted Sets are, similarly to Redis Sets, non repeating collections of Strings. The difference is that every member of a Sorted Set is associated with score, that is used in order to take the sorted set ordered, from the smallest to the greatest score. While members are unique, scores may be repeated.
 *
 * <pre>
 * $set = new Redisko\SortedSet("mySortedSet");
 * $set->add("myThing", 0.5);
 * $set->add("myOtherThing", 0.6);
 *
 * foreach($set as $key => $score) {
 *    echo $key.":".$score."\n";
 * }
 * </pre>
 *

 */
class SortedSet extends IterableEntity
{

    /**
     * Adds an item to the set
     *
     * @param string  $key the key to add
     * @param integer $value the score for this key
     *
     * @return boolean true if the item was added, otherwise false
     * @throws \Exception
     */
    public function add($key, $score)
    {
        if (!$this->redis->zAdd($this->name, $score, $key)) {
            return false;
        }

        $this->clearState();

        return true;
    }

    /**
     * Removes an item from the set
     *
     * @param string $key the item to remove
     *
     * @return boolean true if the item was removed, otherwise false
     * @throws \Exception
     */
    public function remove($key)
    {
        if (!$this->redis->zRem($this->name, $this->serialize($key))) {
            return false;
        }

        $this->clearState();

        return true;
    }

    /**
     * Increment (or decrement if $byAmount is negative) the score of an item from the set
     *
     * @param         $key
     * @param integer $byAmount the amount to increment by, defaults to 1
     *
     * @return int the new value of the score if was incremented, otherwise false
     * @throws \Exception
     */
    public function increment($key, $byAmount = 1)
    {
        if (!($score = $this->redis->zIncrBy($this->name, $byAmount, $this->serialize($key)))) {
            return false;
        }
        $this->clearState();

        return $score;
    }

    /**
     * Gets the intersection between this set and the given set(s), stores it in a new set and returns it
     *
     * @param SortedSet|string $destination the destination to store the result in
     * @param mixed            $set The sets to compare to, either Redisko\SortedSet instances or their names
     * @param array            $weights the weights for the sets, if any
     *
     * @return SortedSet a set that contains the intersection between this set and the given sets
     * @throws \Exception
     */
    public function interStore(SortedSet $destination, $set, $weights = null): SortedSet
    {
        $destination->clearState();
        if (\is_array($set)) {
            $sets = $set;
        } else {
            $sets = array($set);
        }

        foreach ($sets as $n => $set) {
            if ($set instanceof SortedSet) {
                $sets[$n] = $set->name;
            }
        }

        array_unshift($sets, $this->name);
        $parameters = array(
            $destination->name,
            $sets,
        );
        if ($weights !== null) {
            $parameters[] = $weights;
        }
        $total = \call_user_func_array(
            array(
                $this->redis,
                'zinter',
            ),
            $parameters
        );
        $destination->_count = $total;

        return $destination;
    }

    /**
     * Gets the union of this set and the given set(s), stores it in a new set and returns it
     *
     * @param SortedSet|string $destination the destination to store the result in
     * @param mixed            $set The sets to compare to, either Redisko\SortedSet instances or their names
     * @param array            $weights the weights for the sets, if any
     *
     * @return SortedSet a set that contains the union of this set and the given sets
     */
    public function unionStore(SortedSet $destination, $set, $weights = null): SortedSet
    {
        $destination->clearState();
        if (\is_array($set)) {
            $sets = $set;
        } else {
            $sets = array($set);
        }

        foreach ($sets as $n => $set) {
            if ($set instanceof SortedSet) {
                $sets[$n] = $set->name;
            }
        }

        array_unshift($sets, $this->name);
        $parameters = array(
            $destination->name,
            $sets,
        );
        if ($weights !== null) {
            $parameters[] = $weights;
        }
        $total = \call_user_func_array(
            array(
                $this->redis,
                'zunion',
            ),
            $parameters
        );
        $destination->_count = $total;

        return $destination;
    }

    /**
     * @param string $start
     * @param string $stop
     * @param array  $options
     *
     * @return array
     *
     * @see Redis::zRangeByScore()
     */
    public function getRangeByScore(string $start, string $stop, array $options = []): array
    {
        return $this->redis->zRangeByScore($this->name, $start, $stop, $options);
    }

    /**
     * @param string $start
     * @param string $stop
     * @param array  $options
     *
     * @return array
     *
     * @see Redis::zRevRangeByScore()
     */
    public function getRevRangeByScore(string $start, string $stop, array $options = []): array
    {
        return $this->redis->zRevRangeByScore($this->name, $start, $stop, $options);
    }

    /**
     * @param      $start
     * @param      $end
     * @param bool $withscores
     *
     * @return array
     *
     * @see Redis::zRange()
     */
    public function getRange($start, $end, $withscores = false)
    {
        return $this->redis->zRange($this->name, $start, $end, $withscores);
    }


    /**
     */
    public function getFirst()
    {
        return $this->getRange(0, 0, false);
    }

    /**
     * @return array
     */
    public function getFirstWithScore()
    {
        return $this->getRange(0, 0, true);
    }
    /**
     * @param bool $withscores
     *
     * @return null
     */
    public function getMinScore(bool $withscores = false)
    {
        return array_values($this->getFirstWithScore())[0] ?? null;
    }

    /**
     * @param bool $withscores
     *
     * @return null
     */
    public function getMaxScore(bool $withscores = false)
    {
        return array_values($this->getLastWithScore())[0] ?? null;
    }

    /**
     */
    public function getLast()
    {
        return $this->getRange(-1, -1, false);
    }

    /**
     * @param bool $withscores
     *
     * @return array
     */
    public function getLastWithScore(bool $withscores = false)
    {
        return $this->getRange(-1, -1, true);
    }

    /**
     * Gets the number of items in the set
     *
     * @param boolean $forceRefresh whether to force a refresh or not
     *
     * @return integer the number of items in the set
     */
    public function getCount($forceRefresh = false): int
    {
        if ($forceRefresh || $this->_count === null) {
            $this->_count = $this->redis->zCard($this->name);
        }

        return (int)$this->_count;
    }

    /**
     * Gets all the members in the  sorted set
     *
     * @param boolean $forceRefresh whether to force a refresh or not
     *
     * @return array the members in the set
     */
    public function getData(bool $forceRefresh = false): array
    {
        if ($forceRefresh || $this->_data === null) {
            $this->_data = $this->redis->zRange($this->name, 0, -1, true);
            foreach ($this->_data ?? [] as $k) {

            }
        }

        return $this->_data;
    }

    /**
     * Returns the score of member in the sorted set at key.
     *
     * @param $member
     *
     * @return string|float
     */
    public function getScore($member)
    {
        return $this->redis->zScore($this->name, $member);
    }

    /**
     * Returns whether there is an item at the specified offset.
     * This method is required by the interface ArrayAccess.
     *
     * @param integer $offset the offset to check on
     *
     * @return boolean
     */
    public function offsetExists($offset): bool
    {
        return isset($this->getData()[$offset]);
    }

    /**
     * Returns the item at the specified offset.
     * This method is required by the interface ArrayAccess.
     *
     * @param integer $offset the offset to retrieve item.
     *
     * @return mixed the item at the offset
     */
    public function offsetGet($offset)
    {
        return $this->getData()[$offset] ?? [];
    }

    /**
     * Sets the item at the specified offset.
     * This method is required by the interface ArrayAccess.
     *
     * @param integer $offset the offset to set item
     * @param mixed   $item the item value
     *
     * @throws \Exception
     */
    public function offsetSet($offset, $item)
    {
        $this->add($offset, $item);
    }

    /**
     * Unsets the item at the specified offset.
     * This method is required by the interface ArrayAccess.
     *
     * @param integer $offset the offset to unset item
     *
     * @throws \Exception
     */
    public function offsetUnset($offset)
    {
        $this->remove($offset);
    }

    /**
     * @param string $key
     * @param        $value
     *
     * @throws NotSupportedException
     */
    public function set(string $key, $value)
    {
        throw new NotSupportedException('Method '.__METHOD__.' not supported');
    }


    /**
     * @param SerializerInterface $serializer
     *
     * @throws SerializerIsNotSupportedException
     */
    public function setSerializer(SerializerInterface $serializer)
    {
        throw new SerializerIsNotSupportedException('Method '.__METHOD__.' forbidden');
    }
}
