**Requirements**

1、yii2

2、php >= 7.4

3、RdKafka扩展，扩展安装如下 

    1、安装librdkafka

    git clone https://github.com/edenhill/librdkafka.git
    cd librdkafka
    ./configure
    make && make install
    
    2、安装php-rdkafka扩展
    
    git clone https://github.com/arnaud-lb/php-rdkafka.git
    cd php-rdkafka
    phpize
    ./configure --with-php-config=/usr/local/php7.0/bin/php-config
    make && make install
    
    3、php.ini配置
    
    extension = rdkafka.so`

kafka服务使用docker镜像文件构建，示例：tmp-docker-compose.yml

**安装**

`composer require dnkfk/yii2-kafka`