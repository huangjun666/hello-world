<?php
/**
 * 仓库服务service
 * @author 黄俊
 * @date 2017-7-27
 */
class ShopDepotService extends BaseService{

	/**
	 * 获得货物出入库记录信息
	 * @author 黄俊
	 * 2017-7-4
	 */
	public function getGoodsLogList($where='',$limit=''){

		//实例化模型
		$m=new Model();

		// 查询sql
		$sql.='SELECT g.name,';
		$sql.=' g.number,';
		$sql.=' g.size,';
		$sql.=' g.color,';
		$sql.=' g.num_unit,';
		$sql.=' gl.*'; 
		$sql.=' FROM `shop_goods_log` gl';
		$sql.=' LEFT JOIN shop_goods g ON g.goods_id = gl.goods_id';

		//where条件
		if(!empty($where)){
			$sql.=' where '.$where;
		}

		//order by 排序
		$sql.=' order by update_time DESC,add_time DESC';

		//limit限制
		if(!empty($limit)){
			$sql.=' limit '.$limit;
		}

		//查询
		$rs=$m->query($sql);

		// 返回结果
		return $rs;
	}

	/**
	 * 获得货物出入库记录信息总量
	 * @author 黄俊
	 * 2017-7-4
	 */
	public function getGoodsLogCount($where=''){

		//实例化模型
		$m=new Model();

		// 查询sql
		$sql.='SELECT ';
		$sql.=' COUNT(*) total';
		$sql.=' FROM `shop_goods_log` gl';
		$sql.=' LEFT JOIN shop_goods g ON g.goods_id = gl.goods_id';

		//where条件
		if(!empty($where)){
			$sql.=' where '.$where;
		}


		//查询
		$rs=$m->query($sql);
		
		// 返回结果
		return $rs[0]['total'];
	}


	/**
	 * 获取某个时间段的仓库订货统计
	 * @author 黄俊
	 * 2017-7-30
	 */
	public function getGoodsTransactionLogList($start_time,$end_time,$limit=''){

		//实例化模型
		$m=new Model();

		//查询sql
		$sql='';

		//用来分组统计日志
		$goodLogSql='';
		$goodLogSql.='SELECT SUM(num) changeNum,goods_id,add_time FROM `shop_goods_log`';
		$goodLogSql.=' WHERE `type`=3 AND (add_time BETWEEN "'.$start_time.'" AND "'.$end_time.'")';
		$goodLogSql.=' GROUP BY goods_id';


		$sql.='SELECT g.name,g.number,g.size,g.color,g.sort,gl.* FROM ('.$goodLogSql.') gl';
		$sql.=' LEFT JOIN `shop_goods` g ON g.`goods_id`=gl.goods_id';

		//order by 排序
		$sql.=' ORDER BY g.sort DESC';


		//limit限制
		if(!empty($limit)){
			$sql.=' limit '.$limit;
		}

		// echo $sql;die();

		//查询
		$rs=$m->query($sql);

		// 返回结果
		return $rs;
	}

	/**
	 * 获取某个时间段的仓库订货统计
	 * @author 黄俊
	 * 2017-7-30
	 */
	public function getGoodsTransactionLogCount($start_time,$end_time){

		//实例化模型
		$m=new Model();

		// 查询sql
		$sql='SELECT COUNT(gl.goods_id) total FROM (SELECT SUM(num) changeNum,goods_id,add_time FROM `shop_goods_log`';
		$sql.=' WHERE `type`=3 AND (add_time BETWEEN "'.$start_time.'" AND "'.$end_time.'")';
		$sql.=' GROUP BY goods_id) gl';

		//查询
		$rs=$m->query($sql);
		
		// 返回结果
		return $rs[0]['total'];
		
	}


	/**
	 * 获取某个时间段的仓库订货统计【按栏目分类】
	 * @author 黄俊
	 * 2017-7-30
	 */
	public function getGoodsCateTransactionLogList($start_time,$end_time,$limit=''){

		//实例化模型
		$m=new Model();

		//查询sql
		$sql='';

		$sql.='SELECT g.`name`,SUM(gl.changeNum1) changeNum,g.`add_time` FROM';
		$sql.=' (SELECT SUM(num) changeNum1,goods_id,add_time FROM `shop_goods_log` WHERE `type`=3 AND (add_time BETWEEN "'.$start_time.'" AND "'.$end_time.'") GROUP BY goods_id) gl';
		$sql.=' LEFT JOIN `shop_goods` g ON g.`goods_id`=gl.goods_id';
		$sql.=' GROUP BY g.`name`';

		//order by 排序
		$sql.=' ORDER BY g.`sort` DESC';


		//limit限制
		if(!empty($limit)){
			$sql.=' limit '.$limit;
		}

		// echo $sql;die();

		//查询
		$rs=$m->query($sql);

		// 返回结果
		return $rs;
	}


	/**
	 * 获取某个时间段的仓库订货统计
	 * @author 黄俊
	 * 2017-7-30
	 */
	public function getGoodsCateTransactionLogCount($start_time,$end_time){

		//实例化模型
		$m=new Model();

		//查询sql
		$sql='';
		$sql.='SELECT COUNT(tgc.`name`) total FROM ( SELECT g.`name` FROM';
		$sql.=' (SELECT goods_id FROM `shop_goods_log` WHERE `type`=3 AND (add_time BETWEEN "'.$start_time.'" AND "'.$end_time.'") GROUP BY goods_id) gl';
		$sql.=' LEFT JOIN `shop_goods` g ON g.`goods_id`=gl.goods_id';
		$sql.=' GROUP BY g.`name`) tgc';

		// echo $sql;die();

		//查询
		$rs=$m->query($sql);
		
		// 返回结果
		return $rs[0]['total'];
		
	}


	/**
	 * 获取某个时间段的仓库发货统计
	 * @author 黄俊
	 * 2017-7-31
	 */
	public function getGoodsDeliverGoodsLogList($start_time,$end_time,$limit=''){

		//实例化模型
		$m=new Model();

		//查询sql
		$sql='';

		//用来分组统计日志
		$goodLogSql='';
		$goodLogSql.='SELECT SUM(num) changeNum,goods_id,add_time FROM `shop_deliver_goods_log`';
		$goodLogSql.=' WHERE (add_time BETWEEN "'.$start_time.'" AND "'.$end_time.'")';
		$goodLogSql.=' GROUP BY goods_id';


		$sql.='SELECT g.name,g.number,g.size,g.color,g.sort,gl.* FROM ('.$goodLogSql.') gl';
		$sql.=' LEFT JOIN `shop_goods` g ON g.`goods_id`=gl.goods_id';

		//order by 排序
		$sql.=' ORDER BY g.sort DESC';


		//limit限制
		if(!empty($limit)){
			$sql.=' limit '.$limit;
		}

		// echo $sql;die();

		//查询
		$rs=$m->query($sql);

		// 返回结果
		return $rs;
	}

	/**
	 * 获取某个时间段的仓库订货统计
	 * @author 黄俊
	 * 2017-7-31
	 */
	public function getGoodsDeliverGoodsLogCount($start_time,$end_time){

		//实例化模型
		$m=new Model();

		// 查询sql
		$sql='SELECT COUNT(gl.goods_id) total FROM (SELECT SUM(num) changeNum,goods_id,add_time FROM `shop_deliver_goods_log`';
		$sql.=' WHERE (add_time BETWEEN "'.$start_time.'" AND "'.$end_time.'")';
		$sql.=' GROUP BY goods_id) gl';

		//查询
		$rs=$m->query($sql);
		
		// 返回结果
		return $rs[0]['total'];
		
	}


	/**
	 * 获取某个时间段的仓库订货统计【按栏目分类】
	 * @author 黄俊
	 * 2017-7-31
	 */
	public function getGoodsCateDeliverGoodsLogList($start_time,$end_time,$limit=''){

		//实例化模型
		$m=new Model();

		//查询sql
		$sql='';

		$sql.='SELECT g.`name`,SUM(gl.changeNum1) changeNum,g.`add_time` FROM';
		$sql.=' (SELECT SUM(num) changeNum1,goods_id,add_time FROM `shop_deliver_goods_log` WHERE (add_time BETWEEN "'.$start_time.'" AND "'.$end_time.'") GROUP BY goods_id) gl';
		$sql.=' LEFT JOIN `shop_goods` g ON g.`goods_id`=gl.goods_id';
		$sql.=' GROUP BY g.`name`';

		//order by 排序
		$sql.=' ORDER BY g.`sort` DESC';


		//limit限制
		if(!empty($limit)){
			$sql.=' limit '.$limit;
		}

		// echo $sql;die();

		//查询
		$rs=$m->query($sql);

		// 返回结果
		return $rs;
	}


	/**
	 * 获取某个时间段的仓库订货统计
	 * @author 黄俊
	 * 2017-7-31
	 */
	public function getGoodsCateDeliverGoodsLogCount($start_time,$end_time){

		//实例化模型
		$m=new Model();

		//查询sql
		$sql='';
		$sql.='SELECT COUNT(tgc.`name`) total FROM ( SELECT g.`name` FROM';
		$sql.=' (SELECT goods_id FROM `shop_deliver_goods_log` WHERE (add_time BETWEEN "'.$start_time.'" AND "'.$end_time.'") GROUP BY goods_id) gl';
		$sql.=' LEFT JOIN `shop_goods` g ON g.`goods_id`=gl.goods_id';
		$sql.=' GROUP BY g.`name`) tgc';

		//查询
		$rs=$m->query($sql);
		
		// 返回结果
		return $rs[0]['total'];
		
	}

	
}

?>