<?php

namespace Redisko\Helper;

use Redis;

class DeleteKeysByPattern
{
    /**
     * @var Redis
     */
    private $redis;

    /**
     * DeleteKeysByPattern constructor.
     * @param Redis $redis
     */
    public function __construct(Redis $redis)
    {
        $this->redis = $redis;
    }

    /**
     * @param string $pattern
     * @return int|false Return count deleted items. If nothing deleted returned false
     */
    public function execute(string $pattern)
    {
        return $this->redis->eval("return redis.call('del', unpack(redis.call('keys', ARGV[1])))", [$this->redis->_prefix($pattern)]);
        //return $this->redis->eval("for i, name in ipairs(redis.call('KEYS', ARGV[1])) do redis.call('DEL', name); end", [$pattern]);
    }
}