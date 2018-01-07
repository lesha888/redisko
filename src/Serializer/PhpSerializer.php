<?php

namespace Redisko\Serializer;

class PhpSerializer implements SerializerInterface
{
    /**
     * @param $value
     * @return string
     */
    public function serialize($value): string
    {
        return serialize($value);
    }

    /**
     * @param $value
     * @return mixed
     */
    public function deserialize($value)
    {
        return unserialize($value, ['allowed_classes' => true]);
    }
}