<?php

namespace Redisko;


use Redisko\Serializer\SerializerInterface;

trait SerializebleTrait
{
    /**
     * @var SerializerInterface
     */
    protected $serializer;

    /**
     * @param SerializerInterface $serializer
     */
    public function setSerializer(SerializerInterface $serializer)
    {
        $this->serializer = $serializer;
    }

    /**
     * @param $value
     * @return mixed
     */
    public function serialize($value): string
    {
        return $this->serializer ? $this->serializer->serialize($value) : $value;
    }

    /**
     * @param $value
     * @return mixed
     */
    public function deserialize($value)
    {
        if ($value === false || $value === null) {
            return $value;
        }

        if ($this->serializer === null) {
            return $value;
        }

        return $this->serializer->deserialize($value);
    }

    /**
     * @param $array
     * @return mixed
     */
    public function serializeMany(array $array)
    {
        if ($this->serializer) {
            foreach ($array as $key => $value) {
                $array[$key] = $this->serializer->serialize($value);
            }
        }

        return $array;
    }

    /**
     * @param $array
     * @return mixed
     */
    public function deserializeMany($array)
    {
        if ($array === true || $array === false) {
            return $array;
        }
        if ($this->serializer) {
            foreach ($array ?? [] as $key => $value) {
                $array[$key] = $this->serializer->deserialize($value);
            }
        }

        return $array;
    }

}