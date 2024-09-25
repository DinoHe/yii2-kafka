<?php

namespace Dnkfk;

use Dnkfk\controllers\KafkaController;
use mikemadisonweb\rabbitmq\Configuration;
use yii\base\BootstrapInterface;
use yii\console\Application;

/**
 * 依赖注入
 *
 * @slice  2024-09-25 17:56
 */
class DependencyInjection implements BootstrapInterface
{
    private string $kafkaAlias = 'kafka';

    /**
     * 注册脚本
     *
     * @param $app
     * @return void
     */
    public function bootstrap($app)
    {
        $this->addControllers($app);
    }

    /**
     * 注册控制台类
     *
     * @param $app
     * @return void
     */
    private function addControllers($app)
    {
        if($app instanceof Application) {
            $app->controllerMap[$this->kafkaAlias] = KafkaController::class;
        }
    }
}