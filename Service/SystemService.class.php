<?php
/**
 * 系统配置服务service
 * @author 黄俊
 * @date 2017-8-4
 */
class SystemService extends BaseService{

	private static $_white_config=array(); //系统配置--白名单配置信息

	/**
	 * 返回奖励配置信息
	 * @author 黄俊
	 * 2016-6-29
	 */
	public function reward_config(){

		//取得所有的配置信息
		$conf=M('reward_config')->select();

		//将二维数组转化为一维数组
		$reward_config=array();

		foreach ($conf as $key => $value) {
			$reward_config[$value['key']]=$value['value'];
		}

		return $reward_config;
	}

	/**
	 * 返回白名单配置信息
	 * @author 黄俊
	 * 2017-8-4
	 */
	public function white_config(){

		//取得所有的配置信息
		$conf=M('white_list')->select();

		//将二维数组转化为一维数组
		$white_config=array();

		foreach ($conf as $key => $value) {
			$white_config[$value['key']]=$value['value'];
		}

		return $white_config;
	}

	/**
	 * 获得指定的白名单配置信息
	 * @author 黄俊
	 * date 2017-8-4
	 */
	public function get_white_config($configName=''){

		//初始化配置
		if( empty($_white_config) ) self::$_white_config=$this->white_config();

		//根据相应的参数返回对应的配置
		if( empty($configName) ){
			return self::$_white_config;//不给参数，返回所以的配置信息 array
		}else{
			return explode(',',self::$_white_config[$configName]);
		}

	}

}

?>