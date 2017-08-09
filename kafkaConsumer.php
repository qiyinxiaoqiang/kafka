<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

use App\Http\Controllers\KafkaSpiderController;


//消费消息
class kafkaConsumer extends Command
{
        protected $signature = 'consumer:spider{topic}';
    
        protected $description = '将队列中各个话题的数据刷到数据库';
        
        public function __construct() {
            parent::__construct();
        }
        
        public function handle(){
            
            $kafka_topic = $this->argument('topic');
            $broker = "xxx.xxx.xxx.xxx:9092";
            $rk = new \RdKafka\Consumer();
            $rk->setLogLevel(LOG_DEBUG);
            $num = $rk->addBrokers($broker);
            $topic = $rk->newTopic($kafka_topic);
            $topic->consumeStart(0, RD_KAFKA_OFFSET_END); 
             while (true) {
                $msg = $topic->consume(0, 1000);
                if (null === $msg) {

                } else {
                    if ($msg->err) { 
                        echo $msg->errstr(), "\n";
                        sleep(10);
                    } else { 
                        echo $msg->payload, "\n";
                        //\Log::info(json_decode($msg->payload,true)); 
                        $kafka_msg = $msg->payload; 
                        slef::kafkaTopic($kafka_msg,$kafka_topic); 
                    }
                }
            }
        }
        

		public static function kafkaTopic($data,$topic){
        
			$data = json_decode($data,true);
			//判断数据是否唯一
			$exist_params = array(
				'aid'=>$data['aid'],
				'topic'=>$topic
			);
			$exist_result = KafkaSpider::isExist($exist_params); 
			if($exist_result > 0){
				//topic中的消息已存在数据库中,不再存储 
				\Log::info('该数据已存在');
				exit;
			}
			
			//不存在做添加处理
			$params = array(
				'data'=>$data,
				'topic'=>$topic
			);
			$result = KafkaSpider::spiderStore($params);
			
			if($result){
				
			}
        
    }



	//KafkaSpider model层 
	public static function isExist($params){
         
        $total = \DB::connection('mongodb')
                    ->table('article')
                    ->where('aid',intval($params['aid']))
                    ->count(); 
        return $total;
    }



	public static function spiderStore($params){ 
        $data = $params['data'];
        $result = \DB::connection('mongodb')
                ->table('article')
                ->insert($data); 
        return $result;
    }
        
        
}

