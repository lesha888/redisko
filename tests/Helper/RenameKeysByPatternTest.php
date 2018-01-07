<?php

namespace Tests\Helper;

use Redisko\Helper\DeleteKeysByPattern;
use Redisko\Helper\RenameKeysByPattern;
use Tests\AbstractTestCase;


/**
 * Tests for the {@link \Redisko\Helper\DeleteKeysByPatternTest} class
 * .tests
 */
class RenameKeysByPatternTest extends AbstractTestCase
{
    /**
     * Tests the basic functionality
     * @throws \Exception
     */
    public function testBasics()
    {
        $redis = $this->getConnection();
        $helper = new RenameKeysByPattern($redis);
        $prefix = 'rename_keys_by_pattern_test:';
        $this->assertTrue($redis->set($prefix.'key1', 'value'));
        $this->assertTrue($redis->exists($prefix.'key1'));
        $this->assertTrue($redis->set($prefix.'key11', 'value'));
        $this->assertTrue($redis->exists($prefix.'key11'));
        $this->assertTrue($redis->set($prefix.'key2', 'value'));
        $this->assertTrue($redis->exists($prefix.'key2'));
        $this->assertSame(2, $helper->execute($prefix.'key1*', $prefix.'key1', $prefix.'key_new1'));
        $this->assertFalse($redis->exists($prefix.'key1'));
        $this->assertFalse($redis->exists($prefix.'key11'));
        $this->assertTrue($redis->exists($prefix.'key_new1'));
        $this->assertTrue($redis->exists($prefix.'key_new11'));
        $this->assertTrue($redis->exists($prefix.'key2'));
        $this->assertSame(3, $this->redis->delete($prefix.'key_new1', $prefix.'key_new11', $prefix.'key2'));
    }
}
