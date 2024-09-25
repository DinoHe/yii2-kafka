<?php

namespace Dnkfk;

interface ConsumerInterface
{
    /**
     * 执行消费
     *
     * @param Message $message
     * @return mixed
     */
    public function execute(Message $message);
}