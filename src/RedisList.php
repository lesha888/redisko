<?php

namespace Redisko;

use Redis;
use Redisko\Exception\NotSupportedException;
use Redisko\Exception\RedisException;
use Traversable;


/**
 * Represents a redis list.
 * <pre>
 * $list = new Redisko\RedisList("myList");
 * $list[] = "an item"; // instantly saved to redis
 * $list[] = "another item"; // instantly saved to redis
 * echo count($list); // 2
 * echo $list->pop() // "another item"
 * echo count($list); // 1
 * </pre>
 */
class RedisList extends IterableEntity
{
    /**
     * Adds an item to the list
     * @param mixed $item the item to add
     * @return bool true if the item was added, otherwise false
     */
    public function add($item): bool
    {
        if ($this->redis->rPush($this->name, $this->serialize($item)) === false) {
            return false;
        }
        $this->clearState();

        return true;
    }

    /**
     * Adds an item to the list
     * @param array $items
     * @return  int|false     The new length of the list in case of success, FALSE in case of Failure.
     */
    public function addMulti(... $items)
    {
        $this->clearState();
        $items = $this->serializeMany($items);

        return $this->redis->rPush($this->name, ...$items);
    }

    public function set(string $index, $item)
    {
        if (!$this->redis->lSet($this->name, $index, $this->serialize($item))) {
            return false;
        }
        $this->clearState();

        return true;
    }

    /**
     * @param string $pivot
     * @param $item
     * @return  int     The number of the elements in the list, -1 if the pivot didn't exists.
     */
    public function insertBefore(string $pivot, $item): int
    {
        $this->clearState();

        return $this->redis->lInsert($this->name, Redis::BEFORE, $pivot, $this->serialize($item));
    }

    /**
     * @param string $pivot
     * @param $item
     * @return  int     The number of the elements in the list, -1 if the pivot didn't exists.
     */
    public function insertAfter(string $pivot, $item): int
    {
        $this->clearState();

        return $this->redis->lInsert($this->name, Redis::AFTER, $pivot, $this->serialize($item));
    }

    /**
     * @param $key
     * @throws NotSupportedException
     */
    public function remove($key)
    {
        throw new NotSupportedException('Method '.__METHOD__.' not supported');
    }

    /**
     * Removes the first count occurences of the value element from the list.
     * If count is zero, all the matching elements are removed. If count is negative,
     * elements are removed from tail to head.
     * @param mixed $item the item to remove
     * @param int $count
     * @return bool true if the item was removed, otherwise false
     */
    public function removeItem($item, int $count = 1)
    {
        if (!$this->redis->lRem($this->name, $this->serialize($item), $count)) {
            return false;
        }
        $this->clearState();

        return true;
    }

    /**
     * Adds an item to the end of the list
     * @param mixed $item the item to add
     * @return boolean true if the item was added, otherwise false
     */
    public function push($item)
    {
        return $this->add($item);
    }

    /**
     * Adds an item to the start of the list
     * @param mixed $item the item to add
     * @return bool true if the item was added, otherwise false
     */
    public function unshift($item)
    {
        if ($this->redis->lPush($this->name, $this->serialize($item)) === false) {
            return false;
        }
        $this->clearState();

        return true;
    }

    /**
     * Removes and returns the first item from the list
     * @return mixed the item that was removed from the list
     */
    public function shift()
    {
        $item = $this->redis->lPop($this->name);
        $item = $this->deserialize($item);
        $this->clearState();

        return $item;
    }

    /**
     * Removes and returns the last item from the list
     * @return mixed the item that was removed from the list
     */
    public function pop()
    {
        $item = $this->redis->rPop($this->name);
        $item = $this->deserialize($item);
        $this->clearState();

        return $item;
    }

    /**
     * Gets a range of items in the list
     * @param integer $start the 0 based index to start from
     * @param integer $stop the 0 based index to end at
     * @return array the items in the range
     */
    public function range($start = 0, $stop = -1): array
    {
        $result = $this->redis->lRange($this->name, $start, $stop);

        return $this->deserializeMany($result);

    }

    /**
     * Trims the list so that it will only contain the specified range of items
     * @param integer $start the 0 based index to start from
     * @param integer $stop the 0 based index to end at
     * @return bool true if the trim was successful
     */
    public function trim(int $start, int $stop): bool
    {
        $this->clearState();
        return $this->redis->lTrim($this->name, $start, $stop) ? true : false;
    }

    /**
     * Gets the number of items in the list
     * @return int the number of items in the list
     */
    public function getCount($forceRefresh = false): int
    {
        if ($forceRefresh) {
            $this->clearState();
        }

        if ($this->_count === null) {
            $this->_count = (int)$this->redis->lSize($this->name);
        }

        return $this->_count;
    }

    /**
     * Gets all the members in the list
     * @param boolean $forceRefresh whether to force a refresh or not
     * @return array the members in the list
     */
    public function getData(bool $forceRefresh = false): array
    {
        if ($forceRefresh) {
            $this->clearState();
        }

        if ($this->_data === null) {
            $this->_data = $this->range(0, -1);
            $this->_data = $this->deserializeMany($this->_data);
            $this->_count = \count($this->_data);
        }

        return $this->_data;
    }

    /**
     * Copies iterable data into the list.
     * Note, existing data in the list will be cleared first.
     * @param mixed $data the data to be copied from, must be an array or object implementing Traversable
     * @throws RedisException If data is neither an array nor a Traversable.
     * @throws RedisException
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
     * @param int $offset
     * @param mixed $item
     * @throws \Exception
     */
    public function offsetSet($offset, $item)
    {
        if ($offset === null) {
            $this->add($item);
        } else {
            parent::offsetSet($offset, $item);
        }
    }

}