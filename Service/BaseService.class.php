<?php
class BaseService {
	
	private static $_service=array(); //服务层对象

    /**
     * 返回服务实例对象
     * @author 黄俊
     * @date 2016-11-30
     */
    public static function service($serviceName=__CLASS__){
        if (isset(self::$_service[$serviceName]))
            return self::$_service[$serviceName];
        self::$_service[$serviceName] = new $serviceName();
        return self::$_service[$serviceName];
    }

    /**
     * 返回服务层对象
     * @author 黄俊
     * @date 2016-11-30
     */
    public function getService(){
    	return self::$_service;
    }
}