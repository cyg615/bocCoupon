<?php
//namespace Home\Org\Util;
class RedisClient{
    
    public static $instance;
    public static function instance(){
        if(self::$instance == NULL) {
            self::$instance = new self();
        }
        return  self::$instance;
    }
    
    
    //连接redis
    public function connect($host = '192.168.1.19', $port = '6379', $auth = '', $database = '4') {
        //连接redis
        $redis = new \Redis();
        $redis->connect($host, $port);
        $redis->auth($auth);
        $redis->select($database);
        return $redis;
    }
    
}