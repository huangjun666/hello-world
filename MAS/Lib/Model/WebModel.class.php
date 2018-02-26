<?php

/**
 * 通用模型
 * 说明：提供通用的服务
 * @author 黄俊
 * date 2016-6-28
 */

class WebModel extends BaseModel{


	/**
	 * 返回全国省的信息
	 * @author 黄俊
	 * 2016-6-29
	 */
	public function province(){
		$rs=M('shop_area')->where('parentid=0')->field('id,areaname')->select();
		if($rs){
			return $rs;
		}else{
			return false;
		}
	}

	/**
	 * 返回省下一级市的信息
	 * @author 黄俊
	 * 2016-6-29
	 * 参数：provinceID 省ID号
	 */
	public function city($provinceID){

		$rs=M('shop_area')->where('parentid='.$provinceID)->field('id,areaname')->select();
		
		if($rs){
			return $rs;
		}else{
			return false;
		}
	}

	/**
	 * 返回省下一级县/区的信息
	 * @author 黄俊
	 * 2016-6-29
	 * 参数：cityID 市ID号	
	 */
	public function district($cityID){

		$rs=M('shop_area')->where('parentid='.$cityID)->field('id,areaname')->select();
		
		if($rs){
			return $rs;
		}else{
			return false;
		}
	}

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

}

?>