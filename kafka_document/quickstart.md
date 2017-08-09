# producer
php发送消息示例

```php

<?php
    public function handle() 
    {
        $title = "传唤华为，没有赢家的阻击战";
        $content = "美国政府继3月份对中兴下重手处罚之后，开始瞄准华为。";
                    //$host_list = "172.16.88.12:9092";
        //$host_list = "172.16.88.11:2181/kafka/q-ksg2na7l";
        $broker = "172.16.88.12:9092";
        //$broker = "localhost:9092";
        $kafka = new \RdKafka\Producer();
        $kafka->setLogLevel(LOG_DEBUG);
        $num = $kafka->addBrokers($broker);
        echo "added $num brokers \r\n";
        $topic = $kafka->newTopic("topic_article_publish");
        for($i = 0; $i < 20; $i++){
            $msg = [
                'header'=>[
                    'type'=>'article_publish',
                    'time'=>time(),
                ],
                'body'=>[
                    'title'=>$i.'--'.$title,
                    'content'=>$i.'__'.$content,
                ],
                
            ];
            $topic->produce(RD_KAFKA_PARTITION_UA, 0, json_encode($msg,JSON_UNESCAPED_UNICODE));
            echo "the $i message sended successfully \r\n";
        }
        
        
    }
?>
```

# consumer
php接收消息示例

```php
<?php
    public function handle()
    {
        $broker = "172.16.88.12:9092";
        //$broker = "localhost:9092";
        $rk = new \RdKafka\Consumer();
        $rk->setLogLevel(LOG_DEBUG);
        $num = $rk->addBrokers($broker);
        $topic = $rk->newTopic("topic_article_publish");
        $topic->consumeStart(0, RD_KAFKA_OFFSET_END);
        //RD_KAFKA_OFFSET_BEGINNING，从partition消息队列的开始进行consume；
        //RD_KAFKA_OFFSET_END，从partition中的将要produce的下一条信息开始（忽略即当前所有的消息）
        //rd_kafka_offset_tail(5)，consume 5 messages from the end，取最新5条
        while (true) {
            $msg = $topic->consume(0, 1000);
            if(null === $msg){
                
            } else {
                if ($msg->err) {
                    echo $msg->errstr(), "\n";
                    sleep(10);
                    
                } else {
                    //var_dump($msg);
                    echo $msg->payload, "\n";
                    echo $msg->offset,"\n";
                }
            }
        }
        
    }
?>

```