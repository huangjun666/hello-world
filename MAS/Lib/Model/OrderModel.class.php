<?php

/**
 * 订单模型
 * 说明：处理订单模块数据，及提供订单模块相关的服务
 * @author 黄俊
 * date 2016-6-28
 */

class OrderModel extends BaseModel{

	/**
	 * 根据order_id获得订单信息
	 * @author 黄俊
	 * date 2016-7-1
	 */

	public function getOrder($order_id){

		$m=new Model();//实例化模型

		$sql='';//sql语句
		$sql.='SELECT o.*,p.`areaname` province,c.`areaname` city,d.`areaname` district FROM `order` o';
		$sql.=' LEFT JOIN `shop_area` p ON p.`id`=o.`ProvinceID`';
		$sql.=' LEFT JOIN `shop_area` c ON c.`id`=o.`CityID`';
		$sql.=' LEFT JOIN `shop_area` d ON d.`id`=o.`DistrictID`';
		$sql.=' WHERE o.order_id='.$order_id;
		$rs=$m->query($sql);
		
		if( empty($rs) ){
			return false;
		}else{
			return $rs[0];
		}

	}

}

?>