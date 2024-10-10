<?php

namespace Dnkfk;

use RdKafka\Producer;
use RdKafka\ProducerTopic;
use RdKafka\Conf;

/**
 * 生产者
 *
 * @slice  2024-10-08 17:04
 */
class KafkaProducer
{
    /**
     * @var string 主题
     */
    private string $topic;

    private ProducerTopic $producerTopic;

    private Producer $producer;

    /**
     * @param string $topic 消息主题
     * @param Conf $conf 配置
     */
    public function __construct(string $topic, Conf $conf)
    {
        $this->topic         = $topic;
        $this->producer      = new Producer($conf);
        $this->producerTopic = $this->producer->newTopic($topic);
    }

    /**
     * 发布消息
     *
     * @param mixed $msg
     * @param int $partition
     * @return void
     */
    public function produceMsg($msg, int $partition = RD_KAFKA_PARTITION_UA)
    {
        $this->producerTopic->produce($partition, 0, !is_string($msg) ? json_encode($msg) : $msg);
    }

    /**
     * 等待发送完成
     *
     * @return void
     */
    public function wait()
    {
        //等待消息发送完成（获取队列长度，大于0表示消息还未发送完）
        while ($this->producer->getOutQLen() > 0) {
            $this->producer->poll(100);
        }
    }

}