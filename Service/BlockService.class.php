<?php
/**
 * block服务service
 * @author 黄俊
 * @date 2016-11-30
 */
class BlockService extends BaseService{

	/**
	 * 获得所有block
	 * @author 黄俊
	 * 2016-12-7
	 */
	public function getAllBlock(){
		
		//取得所有的信息
		$block=M('block')->select();

		//将二维数组转化为一维数组
		$allBlock=array();

		foreach ($block as $key => $value) {
			$allBlock[$value['key']]=$value['value'];
		}

		return $allBlock;
	}

	/**
	 * 根据key获得对应的block
	 * @author 黄俊
	 * 2016-12-7
	 */
	public function getBlock($key){

		//取得所有的配置信息
		$block=M('block')->where(array('key'=>$key))->find();

		// 返回value
		return $block['value'];
	}

}

?>