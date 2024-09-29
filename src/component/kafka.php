<?php

/**
 * kafka连接配置
 */
return [
    'class'   => \Dnkfk\KafkaConnection::class,
    'conn'    => ['localhost:9092'],
    'topics'  => [],
    'binding' => []
];