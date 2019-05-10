<?php

namespace models;


use units\AppUnits;

class SystemModel extends BaseModel
{

    public function __construct()
    {
        parent::__construct();
    }

    public function accessTokenToUserInfo(array $params)
    {
        $javaApi = rtrim(AppUnits::getParams('javaAuthApiUrl'),'/') . '/modules/user/operator/operatorByToken.html';
        return $this->execute($javaApi, $params);
    }

    public function queryCurrentUserMenu($params)
    {
        $javaApi = rtrim(AppUnits::getParams('javaAuthApiUrl'),'/') . '/modules/user/menu/getListMenu.html';
        return $this->execute($javaApi, $params);
    }

    public function authValidate($params)
    {
        $javaApi = rtrim(AppUnits::getParams('javaAuthApiUrl'),'/') . '/modules/user/jurisdiction/validate.html';
        return $this->execute($javaApi, $params);
    }

    public function addOperateLog($params)
    {
        $javaApi = rtrim(AppUnits::getParams('javaLogApiUrl'),'/') . '/operatorLog/addOperateLog';
        return $this->execute($javaApi, ['model' => $params]);
    }


    public function getAreaInfo($params)
    {
        $javaApi = rtrim(AppUnits::getParams('javaApiNewUrl'),'/') . '/car-service/carAreaInfo/findCarAreaInfoList';
        return $this->setEncryptMethod('aes')->execute($javaApi, $params);
    }


    public function findBatchDictListByNid($params)
    {
        $javaApi = rtrim(AppUnits::getParams('javaApiUrl'),'/') . '/modules/user/findBatchDictList.html';
        return $this->execute($javaApi, $params);
    }

    public function findConfigValueByNid($params)
    {
        $javaApi = rtrim(AppUnits::getParams('javaApiNewUrl'),'/') . '/user-service/config/getValueByNid';
        return $this->setEncryptMethod('aes')->execute($javaApi, $params);
    }
}