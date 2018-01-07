<?php

namespace Redisko\Helper;

use Redis;

class RenameKeysByPattern
{
    /**
     * @var Redis
     */
    private $redis;

    /**
     * RenameKeysByPattern constructor.
     * @param Redis $redis
     */
    public function __construct(Redis $redis)
    {
        $this->redis = $redis;
    }

    /**
     * @param string $findPattern
     * @param string $fromPattern
     * @param string $to
     * @return int|false
     */
    public function execute(string $findPattern, string $fromPattern, string $to)
    {
        return (int)$this->redis->eval(
            "local result=0; for i, name in ipairs(redis.call('KEYS',  ARGV[1])) do local x = string.gsub(name, ARGV[2], ARGV[3]); redis.call('RENAME', name, x); result = result + 1; end; return result;",
            [$this->redis->_prefix($findPattern), $fromPattern, $to]
        );
    }
}