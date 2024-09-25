<?php

namespace common\tools;

/**
 * 消息载体
 *
 * @author Dino
 * @slice  2024-09-25 13:44
 */
class Message
{
    private string $payload;

    public function setPayload(string $payload)
    {
        $this->payload = $payload;
    }

    public function payload(): string
    {
        return $this->payload;
    }
}