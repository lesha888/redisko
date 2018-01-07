<?php

namespace Redisko;

use Redisko\Exception\NotSupportedException;
use Redisko\Exception\SerializerIsNotSupportedException;
use Redisko\Serializer\SerializerInterface;
use RedisException;
use Traversable;


/**
 * Represents a redis set.
 * Redis Sets are an unordered collection of Strings. It is possible to add, remove, and test for existence of members in O(1) (constant time regardless of the number of elements contained inside the Set).
 *
 * <pre>
 * $set = new Redisko\Set("mySet");
 * $set->add(1);
 * $set->add(2);
 * $set->add(3);
 *
 * $otherSet = new Redisko\Set("myOtherSet");
 * $otherSet->add(2);
 *
 * print_r($set->diff($otherSet)); // the difference between the sets
 * </pre>
 *
 *

 */
class Set extends IterableEntity
{
    /**
     * Adds an item to the set
     * @param mixed $item the item to add
     * @return boolean true if the item was added, otherwise false
     * @throws \Exception
     */
    public function add($item)
    {
        if (!$this->redis->sAdd($this->name, $this->serialize($item))) {
            return false;
        }
        $this->clearState();

        return true;
    }

    /**
     * Removes an item from the set
     * @param mixed $item the item to remove
     * @return boolean true if the item was removed, otherwise false
     * @throws \Exception
     */
    public function remove($item)
    {
        if (!$this->redis->sRem($this->name, $this->serialize($item))) {
            return false;
        }
        $this->clearState();

        return true;
    }

    /**
     * Removes and returns a random item from the set
     * @return mixed the item that was removed from the set
     * @throws \Exception
     */
    public function pop()
    {
        $member = $this->redis->sPop($this->name);
        $this->deserialize($member);
        $this->clearState();

        return $member;
    }

    /**
     * Gets a random member of the set
     * @return mixed a random member of the set
     * @throws \Exception
     */
    public function random()
    {
        $item = $this->redis->sRandMember($this->name);

        return $this->deserialize($item);
    }

    /**
     * Gets the difference between this set and the given set(s) and returns it
     * @param mixed $set , $set2 The sets to compare to, either Redisko\Set instances or their names
     * @return array the difference between this set and the given sets
     * @throws \Exception
     */
    public function diff(... $parameters)
    {
        foreach ($parameters as $n => $set) {
            if ($set instanceof Set) {
                $parameters[$n] = $set->name;
            }
        }
        array_unshift($parameters, $this->name);

        $result = \call_user_func_array(
            array(
                $this->redis,
                'sdiff',
            ),
            $parameters
        );

        return $this->deserializeMany($result);
    }

    /**
     * Gets the difference between this set and the given set(s), stores it in a new set and returns it
     * @param Set|string $destination the destination to store the result in
     * @param mixed $set , $set2 The sets to compare to, either Redisko\Set instances or their names
     * @return Set a set that contains the difference between this set and the given sets
     * @throws \Exception
     */
    public function diffStore(Set $destination, ... $parameters): Set
    {
        $destination->clearState();

        foreach ($parameters as $n => $set) {
            if ($set instanceof Set) {
                $parameters[$n] = $set->name;
            }
        }

        array_unshift($parameters, $this->name);
        array_unshift($parameters, $destination->name);
        \call_user_func_array(
            array(
                $this->redis,
                'sdiffstore',
            ),
            $parameters
        );

        return $destination;
    }

    /**
     * Gets the intersection between this set and the given set(s) and returns it
     * @param mixed $set , $set2 The sets to compare to, either Redisko\Set instances or their names
     * @return array the intersection between this set and the given sets
     * @throws \Exception
     */
    public function inter(...$parameters)
    {
        foreach ($parameters as $k => $v) {
            if ($v instanceof Set) {
                $parameters[$k] = $v->name;
            }
        }
        array_unshift($parameters, $this->name);// redundantly

        $members = \call_user_func_array(
            array(
                $this->redis,
                'sinter',
            ),
            $parameters
        );

        return $this->deserializeMany($members);
    }

    /**
     * Gets the intersection between this set and the given set(s), stores it in a new set and returns it
     * @param Set|string $destination the destination to store the result in
     * @param mixed $set , $set2 The sets to compare to, either Redisko\Set instances or their names
     * @return Set a set that contains the intersection between this set and the given sets
     * @throws \Exception
     */
    public function interStore(Set $destination, ... $parameters): Set
    {
        $destination->clearState();
        foreach ($parameters as $n => $set) {
            if ($set instanceof Set) {
                $parameters[$n] = $set->getName();
            }
        }

        array_unshift($parameters, $this->name);// redundantly
        array_unshift($parameters, $destination->getName());// redundantly
        \call_user_func_array(
            [
                $this->redis,
                'sinterstore',
            ],
            $parameters
        );

        return $destination;
    }

    /**
     * Gets the union of this set and the given set(s) and returns it
     * @param mixed $set , $set2 The sets to compare to, either Redisko\Set instances or their names
     * @return array the union of this set and the given sets
     * @throws \Exception
     */
    public function union(...$parameters)
    {
        foreach ($parameters as $n => $set) {
            if ($set instanceof Set) {
                $parameters[$n] = $set->name;
            }
        }
        array_unshift($parameters, $this->name);// redundantly

        return \call_user_func_array(
            array(
                $this->redis,
                'sunion',
            ),
            $parameters
        );
    }

    /**
     * Gets the union of this set and the given set(s), stores it in a new set and returns it
     * @param Set|string $destination the destination to store the result in
     * @param mixed $set , $set2 The sets to compare to, either Redisko\Set instances or their names
     * @return Set a set that contains the union of this set and the given sets
     * @throws \Exception
     */
    public function unionStore(Set $destination, ...$parameters): Set
    {
        $destination->clearState();

        foreach ($parameters as $n => $set) {
            if ($set instanceof Set) {
                $parameters[$n] = $set->name;
            }
        }

        array_unshift($parameters, $this->name);
        array_unshift($parameters, $destination->getName());
        \call_user_func_array(
            array(
                $this->redis,
                'sunionstore',
            ),
            $parameters
        );

        return $destination;
    }

    /**
     * Moves an item from this redis set to another
     * @param Set|string $destination the set to move the item to
     * @param mixed $item the item to move
     * @return boolean true if the item was moved successfully
     * @throws \Exception
     */
    public function move(Set $destination, $item)
    {
        $destination->clearState();

        return $this->redis->sMove($this->name, $destination->getName(), $this->serialize($item));
    }


    /**
     * Gets the number of items in the set
     * @return integer the number of items in the set
     * @throws \Exception
     */
    public function getCount(bool $forceRefresh = false): int
    {
        if ($forceRefresh) {
            $this->clearState();
        }
        if ($this->_count === null) {
            $this->_count = $this->redis->sCard($this->name);
        }

        return $this->_count;
    }

    /**
     * Gets all the members in the set
     * @param boolean $forceRefresh whether to force a refresh or not
     * @return array the members in the set
     * @throws \Exception
     */
    public function getData(bool $forceRefresh = false): array
    {
        if ($forceRefresh || $this->_data === null) {
            $this->_data = $this->redis->sMembers($this->name);
            $this->deserializeMany($this->_data);
        }

        return $this->_data;
    }

    /**
     * Copies iterable data into the list.
     * Note, existing data in the list will be cleared first.
     * @param mixed $data the data to be copied from, must be an array or object implementing Traversable
     * @throws RedisException If data is neither an array nor a Traversable.
     * @throws \Exception
     */
    public function copyFrom($data)
    {
        if (\is_array($data) || ($data instanceof Traversable)) {
            if ($this->_count > 0) {
                $this->clear();
            }
            foreach ($data as $item) {
                $this->add($item);
            }
        } else {
            if ($data !== null) {
                throw new \Redisko\Exception\RedisException('List data must be an array or an object implementing Traversable.');
            }
        }
    }

    /**
     * Determines whether the item is contained in the entity
     * @param mixed $item the item to check for
     * @return boolean true if the item exists in the entity, otherwise false
     * @throws \Exception
     */
    public function contains($item): bool
    {
        return $this->redis->sIsMember($this->name, $this->serialize($item));
    }

    /**
     * Returns whether there is an item at the specified offset.
     * This method is required by the interface ArrayAccess.
     * @param integer $offset the offset to check on
     * @return boolean
     * @throws \Exception
     */
    public function offsetExists($offset)
    {
        return isset($this->getData()[$offset]);
    }

    /**
     * Returns the item at the specified offset.
     * This method is required by the interface ArrayAccess.
     * @param integer $offset the offset to retrieve item.
     * @return mixed the item at the offset
     * @throws \Exception
     */
    public function offsetGet($offset)
    {
        return $this->getData()[$offset]??null;
    }

    /**
     * Sets the item at the specified offset.
     * This method is required by the interface ArrayAccess.
     * @param integer $offset the offset to set item
     * @param mixed $item the item value
     * @throws \Exception
     */
    public function offsetSet($offset, $item)
    {
        if (!empty($offset)) {
            throw new SerializerIsNotSupportedException('Method '.__METHOD__.' not supported');
        }

        $this->add($item);
    }

    /**
     * Unsets the item at the specified offset.
     * This method is required by the interface ArrayAccess.
     * @param integer $offset the offset to unset item
     * @throws \Exception
     */
    public function offsetUnset($offset)
    {
        $this->remove($this->_data[$offset]);
    }

    /**
     * @param string $key
     * @param $value
     * @throws NotSupportedException
     */
    public function set(string $key, $value)
    {
        throw new NotSupportedException('Method '.__METHOD__.' not supported');
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