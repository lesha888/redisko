<?php

namespace Redisko;

use ArrayAccess;
use Countable;
use Iterator;
use IteratorAggregate;

/**
 * A base class for iterable redis entities (lists, hashes, sets and sorted sets)
 */
abstract class IterableEntity extends Entity implements IteratorAggregate, ArrayAccess, Countable
{
    /**
     * The number of items in the entity
     * @var integer
     */
    protected $_count;

    /**
     * Holds the data in the entity
     * @var array
     */
    protected $_data;

    /**
     * Clear internal state
     */
    public function clearState()
    {
        $this->_count = null;
        $this->_data = null;
    }

    /**
     * Returns an iterator for traversing the items in the set.
     * This method is required by the interface IteratorAggregate.
     * @return Iterator an iterator for traversing the items in the set.
     */
    public function getIterator(): Iterator
    {
        $data = $this->getData();

        return new \ArrayIterator($data);
    }

    /**
     * Returns an iterator for traversing the items in the hash.
     * This method is required by the interface IteratorAggregate.
     * @return \Generator an iterator for traversing the items in the hash.
     */
    public function getGenerator(): \Generator
    {
        $data = $this->getData() ?? [];

        foreach ($data as $k => $v) {
            yield $k => $v;
        }
    }

    /**
     * Returns the number of items in the set.
     * This method is required by Countable interface.
     * @return integer number of items in the set.
     */
    public function count(): int
    {
        return $this->getCount();
    }

    /**
     * Gets a list of items in the set
     * @return array the list of items in array
     */
    public function toArray(): array
    {
        return $this->getData();
    }

    /**
     * Gets the number of items in the entity
     * @return integer the number of items in the entity
     */
    abstract public function getCount(): int;

    /**
     * Gets all the members in the entity
     * @param boolean $forceRefresh whether to force a refresh or not
     * @return array the members in the entity
     */
    abstract public function getData(bool $forceRefresh = false): array;

    /**
     * Determines whether the item is contained in the entity
     * @param mixed $item the item to check for
     * @return bool true if the item exists in the entity, otherwise false
     */
    public function contains($item): bool
    {
        return \in_array($item, $this->getData(), false);
    }

    /**
     * Removes all the items from the entity
     * @return IterableEntity the current entity
     */
    public function clear(): IterableEntity
    {
        $this->clearState();
        $this->redis->delete($this->name);

        return $this;
    }

    abstract public function remove($key);

    abstract public function set(string $key, $value);

    public function __set($key, $value)
    {
        $this->set($key, $value);
    }

    public function __unset($key)
    {
        $this->remove($key);
    }

    public function __get($key)
    {
        return $this->getData()[$key] ?? null;
    }

    public function __isset($key)
    {
        return isset($this->getData()[$key]);

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
        return $this->__isset($offset);
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
        return $this->__get($offset);
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
        $this->__set($offset, $item);
    }

    /**
     * Unsets the item at the specified offset.
     * This method is required by the interface ArrayAccess.
     * @param integer $offset the offset to unset item
     * @throws \Exception
     */
    public function offsetUnset($offset)
    {
        $this->__unset($offset);
    }

}
