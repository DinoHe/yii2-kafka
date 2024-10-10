<?php

namespace Dnkfk;

use Dnkfk\exception\ValidateConsumerException;
use RdKafka\Conf;
use RdKafka\KafkaConsumer as RdKafkaConsumer;
use RdKafka\Message as KafkaMessage;

/**
 * kafka消费者
 *
 * @slice  2024-10-10 15:26
 */
class KafkaConsumer
{
    private array $topics;

    private string $callbackClass;

    private bool $enableAutoSubmit;

    private Conf $conf;

    /**
     * @param array $topics 订阅的主题
     * @param string $callbackClass 回调类
     * @param Conf $conf 配置
     * @param bool $enableAutoSubmit 是否自动提交
     */
    public function __construct(array $topics, string $callbackClass, Conf $conf, bool $enableAutoSubmit = false)
    {
        $this->topics           = $topics;
        $this->callbackClass    = $callbackClass;
        $this->enableAutoSubmit = $enableAutoSubmit;
        $this->conf             = $conf;
    }

    /**
     * 启动消费
     *
     * @return mixed
     * @throws ValidateConsumerException
     */
    public function startConsume()
    {
        $consumer = new RdKafkaConsumer($this->conf);

        //订阅主题
        $consumer->subscribe($this->topics);

        $call = \Yii::createObject($this->callbackClass);
        if (!$call instanceof ConsumerInterface) {
            throw new ValidateConsumerException(sprintf('%s不是%s实例', $this->callbackClass, ConsumerInterface::class));
        }

        echo sprintf('%s consumer start!', date('Y-m-d H:i:s')) . PHP_EOL;

        while (true) {
            $message = $consumer->consume(120000);
            switch ($message->err) {
                case RD_KAFKA_RESP_ERR_NO_ERROR:

                    $t = microtime(true);

                    $call->execute($this->buildMessage($message));

                    if (!$this->enableAutoSubmit) {
                        $consumer->commit();
                    }

                    echo sprintf('%s consume successful! %ss', date('Y-m-d H:i:s'), round(microtime(true) - $t, 4)) . PHP_EOL;
                    break;
                default:
                    break;
            }
        }
    }

    /**
     * 构建消息实例
     *
     * @param KafkaMessage $message
     * @return Message
     */
    private function buildMessage(KafkaMessage $message): Message
    {
        $msg = new Message();
        $msg->setPayload($message->payload);

        return $msg;
    }
}