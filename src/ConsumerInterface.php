<?php

namespace Dnkfk;

/**
 * interface
 *
 * @slice 2024-09-25 13:44
 */
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