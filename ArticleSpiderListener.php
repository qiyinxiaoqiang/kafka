<?php

namespace App\Listeners;

use App\Events\ArticleSpiderEvent;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;

//生产消息
class ArticleSpiderListener
{
    public function __construct(){
        
    }
    
    public function handle(ArticleSpiderEvent $event){
        
        $data = $event->data;
        
        $validator = \Validator::make($data,[
            'topic'=>'required'
        ]); 
        if($validator->fails()){ 
            return $validator->errors()->all();
        } 
        
        $validator = self::kafkaValidate($data);//正则验证 
        if($validator['code'] != 200){
            return $validator;
        }
     
     
        $broker = "219.238.250.46:9092"; 
        $kafka = new \RdKafka\Producer();
        $kafka->setLogLevel(LOG_DEBUG);
        $num = $kafka->addBrokers($broker);  
        $topic = $kafka->newTopic($data['topic']);  //进入kafka对应的话题队列
        $topic->produce(RD_KAFKA_PARTITION_UA, 0, json_encode($data,JSON_UNESCAPED_UNICODE));
        
    }
    
    protected static function kafkaValidate($data){
        //对不同的话题数据采取不同的验证规则
        $topic = $data['topic'];
        
        $rule = array(
            'title'=>'required',
            'webauthor'=>'required',
    ); 
     
        if($topic && $rule){ 
            $validator = \Validator::make($data,$rule); 
            if($validator->fails()){
                //话题数据不符合对应的规则要求 
                $valid = array(
                    'code'=>400,
                    'msg'=>$validator->errors()->all() 
                );
                return $valid;
            }
            $valid = array(
                'code'=>200,
                'msg'=>'ok'
            );
            return $valid;
            
        }
        $valid =[ 
            'code'=>400,
            'msg'=>'话题或规则不存在',
        ];
        return $valid;
    }
    
    
}


