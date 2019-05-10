<?php

namespace units;

class RedisUnits
{
    private const REDIS_ADDRESS = '127.0.0.1';
    private const REDIS_PORT    = 6379;
    private const KEY_PREFIX    = 'assetmgnt';
    private $redis = null;
    private static $RedisCache  = null;

    public function __construct()
    {
        $this->redis = new \Redis();
        $this->redis->connect(static::REDIS_ADDRESS,static::REDIS_PORT);
    }

    public static function getSelf()
    {

        if(static::$RedisCache == null ){
            static::$RedisCache = new static();
        }

        return static::$RedisCache;
    }

    public function setCache($hash_key = null , $key = null , $values = [] , $time = 3600)
    {
        if(empty($hash_key) || empty($key) || empty($values)){
            return false;
        }

        if($this->redis->hExists($this->getKey($hash_key),$this->getKey($key))){
            $this->redis->hDel($this->getKey($hash_key),$this->getKey($key));
        }

        if($this->redis->hSet($this->getKey($hash_key),$this->getKey($key),json_encode($values,JSON_UNESCAPED_UNICODE))){

            $this->redis->setTimeout($this->getKey($hash_key),$time);

            AppUnits::debug($this->getKey($hash_key));
            AppUnits::debug($this->getKey($key));
            AppUnits::debug($values);

            return ['hash_key' => $this->getKey($hash_key) , 'key' => $this->getKey($key)];

        }

        return false;
    }

    public function getCache($hash_key = null , $key = null , $defalut = null)
    {

        AppUnits::debug($this->getKey($hash_key));
        AppUnits::debug($this->getKey($key));

        if(empty($key) || empty($hash_key)){
            return $defalut;
        }

        $value = $this->redis->hGet($this->getKey($hash_key),$this->getKey($key));

        return empty($value) ? $defalut : json_decode($value,true) ;
    }

    public function delCache($hash_key = null , $key = null)
    {

        AppUnits::debug($this->getKey($hash_key));
        AppUnits::debug($this->getKey($key));

        if(empty($key) || empty($hash_key)){
            return false;
        }

        if($this->redis->hExists($this->getKey($hash_key),$this->getKey($key))){
            return $this->redis->hDel($this->getKey($hash_key),$this->getKey($key));
        }

        return false;
    }

    public function delAllCache($hash_key = null){

//        AppUnits::debug($this->getKey($hash_key));

        if(empty($hash_key)){
            return false;
        }

        if($this->redis->exists($this->getKey($hash_key))){
            return $this->redis->del($this->getKey($hash_key));
        }

        return false;

    }

    public function getKey($key = null)
    {
        return  md5(self::KEY_PREFIX . $key);
    }

    public function close()
    {
        if($this->redis != null){
            $this->redis->close();
            static::$RedisCache = null;
            $this->redis = null;
        }
    }

    public function __destruct ()
    {
        $this->close();
    }

}
