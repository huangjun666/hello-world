<?php
/**
 * 订货单服务service
 * @author 黄俊
 * @date 2016-11-30
 */
class OrderGoodsService extends BaseService{

	/**
	 * 根据订单信息，生成订货单
	 * @author 黄俊
	 * date 2016-12-3
	 * return：$order_goods_id 订货单主键id
	 */
	public function createOrderGoods($order){
		//数据
		$data['order_id']=$order['order_id'];
		$data['add_time']=date('Y-m-d H:i:s');
		$data['update_time']=date('Y-m-d H:i:s');

		//添加数据
		$order_goods_id=M('order_goods')->add($data);

		// 返回主键id
		return $order_goods_id;
	}

	/**
	 * 根据订货单ID或者order_id获得订货单信息
	 * @author 黄俊
	 * date 2016-12-3
	 * return： 一维array() 订货单信息
	 */
	public function getOrderGoods($order_goods_id,$is_order_id=false){
		//实例化模型
		$m=new Model();

		// 查询sql
		$sql='';
		$sql.='SELECT ';
		$sql.=' og.order_goods_id,';
		$sql.=' og.order_id,';
		$sql.=' og.goods_address,';
		$sql.=' og.goods_name,';
		$sql.=' og.goods_tel,';
		$sql.=' og.add_time,';
		$sql.=' og.remarks,';
		$sql.=' og.status,';
		$sql.=' og.second_audit,';
		$sql.=' og.download_num,';
		$sql.=' o.`name`,';
		$sql.=' o.`tel`,';
		$sql.=' o.`money`,';
		$sql.=' o.`handle`,';
		$sql.=' o.`id_card`,';
		$sql.=' o.`orderType`,';
		$sql.=' o.`update_time`,';
		$sql.=' o.`status` orderStatus ';

		//检查是否是order_id
		if($is_order_id){
			$sql.=' FROM (SELECT * FROM  `order_goods` WHERE order_id='.$order_goods_id.') og';
		}else{
			$sql.=' FROM (SELECT * FROM  `order_goods` WHERE order_goods_id='.$order_goods_id.') og';
		}
		
		$sql.=' LEFT JOIN `order` o ON o.`order_id`=og.`order_id`';

		//查询
		$rs=$m->query($sql);

		// 返回结果
		return $rs[0];
	}

	/**
	 * 根据订货单ID获得订货单的货品信息
	 * @author 黄俊
	 * date 2016-12-3
	 * return： 二维array() 订货单的货品list信息
	 */
	public function getOrderGoodsList($order_goods_id){
		//实例化模型
		$m=new Model();

		// 查询sql
		$sql='';
		$sql.='SELECT ';
		$sql.=' ogl.list_id,';
		$sql.=' ogl.num,';
		$sql.=' ogl.deliver_num,';
		$sql.=' g.goods_id,';
		$sql.=' g.name,';
		$sql.=' g.number,';
		$sql.=' g.size,';
		$sql.=' g.color,';
		$sql.=' g.price,';
		$sql.=' g.price_unit,';
		$sql.=' g.discount,';
		$sql.=' g.sort,';
		$sql.=' g.num_unit';
		$sql.=' FROM (SELECT * FROM  `order_goods_list` WHERE order_goods_id='.$order_goods_id.') ogl';
		$sql.=' LEFT JOIN `goods` g ON g.`goods_id`=ogl.`goods_id`';
		$sql.=' order by g.`sort` DESC';


		//查询
		$rs=$m->query($sql);

		// 返回结果
		return $rs;
	}

	/**
	 * 根据订货单ID获得当前货品的总额
	 * @author 黄俊
	 * date 2016-12-3
	 * return： 当前订货单货品总额
	 */
	public function getCurrentGoodSMoney($order_goods_id){

		//当前所有货品
		$goodsList=$this->getOrderGoodsList($order_goods_id);

		$currentMoney=0;//当前货品总额

		// 循环累加
		foreach ($goodsList as $key => $value) {
			$currentMoney+=($value['num']+$value['deliver_num'])*$value['price']*$value['discount']*0.01;
		}

		// 返回总额
		return floatval($currentMoney);
	}

	/**
	 * 根据订货单ID，检查该订货单货物是否全部发货完毕
	 * @author 黄俊
	 * 2016-12-7
	 * return bool
	 */
	public function isEmptyGoods($order_goods_id){

		//当前订货单所有货品
		$goodsList=$this->getOrderGoodsList($order_goods_id);

		//统计未发货的数量
		$numTotal=0;

		// 循环累加
		foreach ($goodsList as $key => $value) {
			$numTotal+=$value['num'];
		}

		//返回
		if($numTotal<=0){
			return true;
		}else{
			return false;
		}
		
	}

	/**
	 * 根据订货单ID，检查该订货单是否有已发出的货物【加入到发货单】
	 * @author 黄俊
	 * 2017-2-21
	 * return bool
	 */
	public function isDeliverGoods($order_goods_id){

		//当前订货单所有货品
		$goodsList=$this->getOrderGoodsList($order_goods_id);

		// 循环处理，检查
		foreach ($goodsList as $key => $value) {

			#发现已发货数量，则返回true，同时跳出循环
			if( $value['deliver_num'] > 0 ){
				return true;
				break;
			}
		}

		
		return false;
		
	}

	/**
	 * 通过where条件统计总数
	 * @author 黄俊
	 * date 2016-12-3
	 * return： int
	 */
	public function getOrderGoodsCount($where='',$status=0){

		//实例化模型
		$m=new Model();
		$sql='';

		//判断where是否为空
		if(empty($where)){
			$sql.='SELECT count(*) total FROM `order_goods` og ';
			//根据状态筛选订货单
			if( $status!=0 ){
				$sql.=' where og.status='.$status;
			}
		}else{
			$sql.='SELECT count(*) total FROM (select order_id,name,tel,money from `order` where '.$where.') o ';
			$sql.=' LEFT JOIN `order_goods` og ON og.`order_id`=o.`order_id`';
			$sql.=' where og.`order_goods_id` IS NOT NULL ';

			//根据状态筛选订货单
			if( $status!=0 ){
				$sql.=' and og.status='.$status;
			}
		}



		// echo $sql;die();

		//查询
		$rs=$m->query($sql);

		// 返回结果
		return $rs[0]['total'];

	}

	/**
	 * 通过where条件,limit条件获得订货单list
	 * @author 黄俊
	 * date 2016-12-3
	 * return： array()
	 */
	public function getList($where='',$limit='',$status=0){

		//实例化模型
		$m=new Model();
		$sql='';

		//判断where是否为空
		if(empty($where)){

			$sql.='SELECT og.*,o.name,o.tel,o.money,o.id_card FROM `order_goods` og ';
			$sql.=' LEFT JOIN `order` o ON og.`order_id`=o.`order_id`';

			//根据状态筛选订货单
			if( $status!=0 ){
				$sql.=' where og.status='.$status;
			}
		}else{
			$sql.='SELECT og.*,o.name,o.tel,o.money,o.id_card FROM (select order_id,name,tel,money,id_card from `order` where '.$where.') o ';
			$sql.=' LEFT JOIN `order_goods` og ON og.`order_id`=o.`order_id`';
			$sql.=' where og.`order_goods_id` IS NOT NULL ';

			//根据状态筛选订货单
			if( $status!=0 ){
				$sql.=' and og.status='.$status;
			}
		}

		

		//order by 排序
		$sql.=' order by field(og.`status`,4,3,2,1,5,6,7),add_time DESC,update_time DESC';

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
	 * 判断订货单在金额上是否满足提交条件
	 * @author 黄俊
	 * date 2016-12-3
	 * return： bool
	 */
	public function isCanSubmit($order_goods_id){

		// 奖励配置信息
		$reward_config=D('Web')->reward_config();

		//获得订单信息
		$orderGoods=$this->getOrderGoods($order_goods_id);

		//获得订单总额
		$totalMoney=$this->getCurrentGoodSMoney($order_goods_id);

		/*比较*/
		if( ($totalMoney+$reward_config['order_discount']) >= $orderGoods['money'] ){
			return true;
		}else{
			return false;
		}
		
	}

	/**
	 * 检查订货单对应的发货单状态，是否存在一个发货单状态处于2【已发货】或3【已确认】
	 * @author 黄俊
	 * date 2017-2-21
	 * return： bool
	 */
	public function isDelivering($order_goods_id){

		//发货单列表
		$deliverGoods=M('deliver_goods')->where(array('order_goods_id'=>$order_goods_id))->select();

		//循环处理
		foreach ($deliverGoods as $key => $value) {
			
			//找到状态为2或3的发货单
			if( $value['status']==2 || $value['status']==3 ){
				return true;
				break;
			}

		}

		return false;
	}

	/**
	 * 订货单状态重建
	 * @author 黄俊
	 * date 2017-2-21
	 * return： status的值
	 * 备注：因业务需要，订货单的需要状态重新设计。该函数用于
	 *		 根据丁货单的发货情况，更新到对应的状态
	 */
	public function StatusUpdate($orderGoods){

		$status=2;//默认待发货状态

		#1、检查订货单产品列表，存在发货数量deliver_num，则status=3
		if( $this->isDeliverGoods($orderGoods['order_goods_id']) ){
			$status=3;//发货中
		}

		#2、检查订货单对应的发货单状态，只要存在一个发货单状态处于2或3，则status=4
		if( $this->isDelivering($orderGoods['order_goods_id']) ){
			$status=4;//待补发
		}

		#3、使用自动完结机制，满足，status=7
		if( SERVICE('DeliverGoods')->isCanSecondAudit($orderGoods['order_goods_id']) ){
			$status=7;//完结
		}

		//返回结果
		return $status;
	}
}

?>