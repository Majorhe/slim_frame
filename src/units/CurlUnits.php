<?php

namespace units;


use Curl\Curl;

class CurlUnits
{

    public $curlTimeLimit = 30;

    public function setCurlTimeLimit($time = 30)
    {
        $this->curlTimeLimit = $time ;
        return $this;
    }

    public function getDataByCurl($url, array $params = [], $method = 'get' , $return = false , $is_returntransfer = false)
    {

        if( ! empty($method)) {
            $method = strtoupper($method);
        }

        if( ! in_array($method , ['GET','POST','PUT','DELETE'])) {
            $method = 'GET';
        }

        AppUnits::debug('请求API的URL：' . $url);
        AppUnits::debug('请求API的参数：' . json_encode($params));

        try {

            $curl = new Curl();
            $curl->setOpt(CURLOPT_TIMEOUT, $this->curlTimeLimit);
            $curl->setOpt(CURLOPT_SSL_VERIFYPEER, false);
            $curl->setOpt(CURLOPT_SSL_VERIFYHOST, false);
            $curl->setOpt(CURLOPT_HTTPHEADER, array("Expect:"));

            if($is_returntransfer){
                $curl->setOpt(CURLOPT_RETURNTRANSFER,1);
            }

            switch ($method)
            {
                case 'GET':
                    $curl->get($url,$params);
                    break;
                case 'POST':
//                    $curl->setHeader('Content-Type','application/x-www-form-urlencoded');
                    $curl->setHeader('Content-Type','application/json');
                    $curl->post($url,$params);
                    break;
                case 'PUT':
                    $curl->put($url,$params);
                    break;
                case 'DELETE':
                    $curl->delete($url,$params);
                    break;
                default:
                    $curl->get($url,$params);
                    break;
            }

            $curl->close();

            if ($curl->error){
                return ['error' => 'CURL error msg : ' . $curl->error_message];
            }

        } catch (\ErrorException $e) {
            return ['error' => 'CURL error msg : ' . $e->getMessage()];
        }

        return $return ? $curl->response : $this->responseHandle($curl->response);
    }

    private function responseHandle($result)
    {
        if(empty($result)){
            return ['error' => 'API返回数据为空'];
        }

        AppUnits::debug('返回的数据：' . $result);

        $result = json_decode($result,true);

        if(empty($result)){
            return ['error' => 'API返回JSON数据异常'];
        }

        if(isset($result['errcode'])){
            return ['error' => isset($result['errmsg']) ? $result['errmsg'] : 'API返回JSON数据异常'];
        }

        if(isset($result['code']) && $result['code'] != 1000){
            return ['error' => isset($result['msg']) ? $result['msg'] : 'API返回数据错误' , 'result' => $result];
        }

        return isset($result['data']) ? $result['data'] : $result;
    }
}