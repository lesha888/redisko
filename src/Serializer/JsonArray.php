<?php

namespace Redisko\Serializer;

use Redisko\Exception\NotEncodableValueException;

class JsonArray implements SerializerInterface
{
    /**
     * @param $value
     * @return string
     */
    public function serialize($value): string
    {
        return json_encode($value);
    }

    /**
     * @param $value
     * @return mixed
     * @throws NotEncodableValueException
     */
    public function deserialize($value)
    {
        $result = json_decode($value, true);

        if (JSON_ERROR_NONE !== json_last_error()) {
            throw new NotEncodableValueException(json_last_error_msg());
        }

        return $result;
    }
}