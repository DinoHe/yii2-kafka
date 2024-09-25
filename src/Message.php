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
}