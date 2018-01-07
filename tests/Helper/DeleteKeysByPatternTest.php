<?php

namespace Tests\Helper;

use Redisko\Helper\DeleteKeysByPattern;
use Tests\AbstractTestCase;


/**
 * Tests for the {@link \Redisko\Helper\DeleteKeysByPatternTest} class
 * .tests
 */
class DeleteKeysByPatternTest extends AbstractTestCase
{
    /**
     * Tests the basic functionality
     * @throws \Exception
     */
    public function testBasics()
    {
        $redis = $this->getConnection();
        $serviceDeleteKeysByPattern = new DeleteKeysByPattern($redis);
        $prefix = 'delete_keys_by_pattern_test:';
        $this->assertTrue($redis->set($prefix.'key1', 'value'));
        $this->assertTrue($redis->exists($prefix.'key1'));
        $this->assertTrue($redis->set($prefix.'key11', 'value'));
        $this->assertTrue($redis->exists($prefix.'key11'));
        $this->assertTrue($redis->set($prefix.'key2', 'value'));
        $this->assertTrue($redis->exists($prefix.'key2'));
        $this->assertSame(2, $serviceDeleteKeysByPattern->execute($prefix.'key1*'));
        $this->assertFalse($redis->exists($prefix.'key1'));
        $this->assertFalse($redis->exists($prefix.'key11'));
        $this->assertTrue($redis->exists($prefix.'key2'));

        $this->assertSame(1, $serviceDeleteKeysByPattern->execute($prefix.'key2*'));
        $this->assertFalse($redis->exists('key2'));
        $this->assertSame(false, $serviceDeleteKeysByPattern->execute($prefix.'*'));
    }
}
