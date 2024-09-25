<?php

namespace Dnkfk\console;

use yii\console\Controller;

/**
 * kafka
 *
 * @slice  2024-09-25 09:08
 */
class KafkaController extends Controller
{
    /**
     * 运行消费者
     *
     * @param string $consumerName
     * @return void
     */
    public function consume(string $consumerName)
    {
        $kafka = \Yii::$app->get('kafka');

        $kafka->consume($consumerName);
    }
}