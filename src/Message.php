<?php

namespace Dnkfk;

/**
 * 消息载体
 *
 * @author Dino
 * @slice  2024-09-25 13:44
 */
class Message
{
    private string $payload;

    private string $uniqueKey;

    public function __construct()
    {
        $this->uniqueKey = str_replace('.', '', uniqid(random_int(1, 10000), true));
    }

    /**
     * 设置消息载体
     *
     * @param string $payload
     * @return void
     */
    public function setPayload(string $payload)
    {
        $this->payload = $payload;
    }

    /**
     * 获取消息载体
     *
     * @return string
     */
    public function getPayload(): string
    {
        return $this->payload;
    }

    /**
     * 获取消息唯一标识
     *
     * @return string
     */
    public function getUniqueKey(): string
    {
        return $this->uniqueKey;
    }
}