<?php

namespace Dnkfk\controllers;

use yii\console\Controller;
use yii\console\ExitCode;

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
     * @return int
     */
    public function actionConsume(string $consumerName): int
    {
        $kafka = \Yii::$app->get('kafka');

        $kafka->consume($consumerName);

        return ExitCode::OK;
    }
}