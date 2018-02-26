<?php
/**
 * 树形结构服务service
 * @author 黄俊
 * @date 2016-11-30
 */
class TreeService extends BaseService{

	/**
	 * 判断树形接口的数据接口是否从member表查询
	 * @author 黄俊
	 * 2016-12-7
	 * return bool 是：true
	 */
	public function isMemberSelect($unit,$time){

		#以查询的方式为维度

		//如果方式为：总业绩
		if( $unit==3 ){
			return true;//该方式下，只能查询member表
		}

		// 如果方式为：月业绩
		if( $unit==2 ){

			/*判断时间节点在不在当月*/
			$month=getMonthFirst();

			if( strtotime($time) >= strtotime($month[0]) && strtotime($time) < strtotime($month[1]) ){
				return true;
			}else{
				return false;
			}
			
		}

		// 如果方式为：周业绩业绩
		if( $unit==1 ){

			/*判断时间节点在不在当周*/
			$week=getWeekInfo();

			if( strtotime($time) >= strtotime($week['now_start']) && strtotime($time) < strtotime($week['now_end']) ){
				return true;
			}else{
				return false;
			}
			
		}

		return true;
	}

}

?>