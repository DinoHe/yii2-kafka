<?php

namespace Dnkfk;

use Dnkfk\exception\ValidateBindingException;
use Dnkfk\exception\ValidateConsumerException;
use RdKafka\Conf;
use RdKafka\KafkaConsumer;
use RdKafka\Message as KafkaMessage;
use RdKafka\Producer;
use RdKafka\ProducerTopic;
use yii\base\Component;

/**
 * kafka连接
 *
 * @slice  2024-09-24 17:05
 */
class KafkaConnection extends Component
{
    /**
     * @var array 连接主机：端口
     */
    public array $conn = [];

    /**
     * @var int 生产者或消费者请求超时时间，毫秒
     */
    public int $requestTimeout = 60000;

    /**
     * @var string 消息确认，默认生产者需要所有副本都确认接收消息
     */
    public string $acks = 'all';

    /**
     * @var bool 是否允许消费者自动提交，默认不允许，即手动提交
     */
    public bool $enableAutoSubmit = false;

    /**
     * @var string 分区消费者分配策略，默认均匀分配
     */
    public string $partitionAssigmentStrategy = 'roundrobin';

    /**
     * @var int 最大拉取消息等待时间，毫秒
     */
    public int $maxFetchWait = 1000;

    /**
     * @var int 每 60 秒更新一次元数据
     */
    public int $maxMetadataAge = 60000;

    /**
     * @var int 费者会话超时设置为 30 秒
     */
    public int $sessionTimeOut = 30000;

    /**
     * @var int 最大重连间隔设置为 1 秒
     */
    public int $maxReconnectBackoff = 1000;

    /**
     * @var array 主题配置
     */
    public array $topics = [];

    /**
     * @var array 消费者主题绑定
     * 绑定示例：
     * [
     *  'consumer' => 'test_consumer',
     *  'topics'   => ['test_topic'],
     *  'group'    => 'test_group',
     *  'callback' => TestConsumer::class
     * ]
     */
    public array $bindings = [];

    private ProducerTopic $producerTopic;

    private Producer $producer;

    /**
     * 投递消息到队列
     *
     * @param mixed $msg 发送的消息
     * @param string $topic 消息主题
     * @param int $partition 分区，默认随机分区
     * @return void
     * @throws ValidateBindingException
     */
    public function produce($msg, string $topic, int $partition = RD_KAFKA_PARTITION_UA)
    {
        if (!in_array($topic, $this->topics)) {
            throw new ValidateBindingException(sprintf('主题未配置：%s', $topic));
        }

        //构建生产者主题
        $this->buildProducerTopic($topic);

        //发布消息到主题
        $this->produceMsg($msg, $partition);

        $this->wait();
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

    /**
     * 构建生产者
     *
     * @return Producer
     */
    private function buildProducer(): Producer
    {
        if (!isset($this->producer)) {
            $conf = $this->initProducerConf();

            $this->producer = new Producer($conf);
        }

        return $this->producer;
    }

    /**
     * 构建生产者主题
     *
     * @param string $topic
     * @return KafkaConnection
     */
    public function buildProducerTopic(string $topic): KafkaConnection
    {
        if (!isset($this->producerTopic)) {
            $producer = $this->buildProducer();

            $this->producerTopic = $producer->newTopic($topic);
        }

        return $this;
    }

    /**
     * 初始化生产者配置
     *
     * @return Conf
     */
    private function initProducerConf(): Conf
    {
        $conf = new Conf();
        $conf->set('metadata.broker.list', implode(',', $this->conn));
        $conf->set('request.timeout.ms', (string)$this->requestTimeout); //请求超时时间
        $conf->set('acks', $this->acks); //确认模式

        return $conf;
    }

    /**
     * 消费
     *
     * @param string $consumer
     * @return void
     * @throws \Exception
     */
    public function consume(string $consumer)
    {
        echo sprintf('%s consumer start!', date('Y-m-d H:i:s')) . PHP_EOL;

        //绑定验证
        $this->validateBinding();

        //获取绑定配置
        $bindingConf = $this->getBindingConf($consumer);

        //构建消费者
        $consumer = $this->buildConsumer($bindingConf);

        //订阅主题
        $consumer->subscribe($bindingConf['topics']);

        $call = \Yii::createObject($bindingConf['callback']);
        if (!$call instanceof ConsumerInterface) {
            throw new ValidateConsumerException(sprintf('%s不是%s实例', $bindingConf['callback'], ConsumerInterface::class));
        }

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
     * 构建消费者
     *
     * @param array $bindingConf
     * @return KafkaConsumer
     */
    private function buildConsumer(array $bindingConf): KafkaConsumer
    {
        $conf = $this->initConsumerConf($bindingConf);

        return new KafkaConsumer($conf);
    }

    /**
     * 初始化消费者配置
     *
     * @param array $consumerConf
     * @return Conf
     */
    private function initConsumerConf(array $consumerConf): Conf
    {
        $conf = new Conf();
        $conf->set('group.id', $consumerConf['group']);
        $conf->set('metadata.broker.list', implode(',', $this->conn));
        $conf->set('enable.auto.commit', $this->enableAutoSubmit ? 'true' : 'false');//自动提交
        $conf->set('partition.assignment.strategy', $this->partitionAssigmentStrategy); //均匀分配策略
        $conf->set('fetch.wait.max.ms', (string)$this->maxFetchWait); // 最大拉取消息等待时间
        $conf->set('metadata.max.age.ms', (string)$this->maxMetadataAge); // 每 x 秒更新一次元数据
        $conf->set('session.timeout.ms', (string)$this->sessionTimeOut); // 消费者会话超时时间
        $conf->set('reconnect.backoff.max.ms', (string)$this->maxReconnectBackoff); // 最大重连间隔

        return $conf;
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

    /**
     * 获取消费者绑定配置
     *
     * @param string $consumer
     * @return array|mixed
     * @throws ValidateBindingException
     */
    private function getBindingConf(string $consumer)
    {
        foreach ($this->bindings as $binding) {
            if ($binding['consumer'] == $consumer) {
                return $binding;
            }
        }

        throw new ValidateBindingException(sprintf('%s消费者绑定信息未配置', $consumer));
    }

    /**
     * 绑定配置验证
     *
     * @return void
     * @throws ValidateBindingException
     */
    private function validateBinding()
    {
        foreach ($this->bindings as $binding) {
            if (!isset($binding['consumer'])) throw new ValidateBindingException('消费者未配置');
            if (!isset($binding['group'])) throw new ValidateBindingException('消费者组未配置');
            if (!isset($binding['topics'])) throw new ValidateBindingException('主题未配置');
            if (!is_array($binding['topics'])) throw new ValidateBindingException('主题配置必须是数组');
            if (!isset($binding['callback'])) throw new ValidateBindingException('回调未配置');
        }
    }

}