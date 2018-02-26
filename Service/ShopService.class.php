<?php
/**
 * 重销商城服务service
 * @author 黄俊
 * @date 2017-7-27
 */
class ShopService extends BaseService{

	/**
	 * 创建重销订单
	 * @author 黄俊
	 * 2017-7-27
	 * return array()
	 */
	public function createShopOrder(){

		//数据
		$data['member_id']=$_SESSION['uid'];
		$data['download_num']=0;
		$data['money']=0;
		$data['add_time']=date('Y-m-d H:i:s');
		$data['update_time']=date('Y-m-d H:i:s');
		$orderAddress=$this->getOrderAddress($_SESSION['uid']);
		$data['shop_order_address_id']=$orderAddress['shop_order_address_id'];

		//新建订单
		$shop_order_id=0;
		$i=0;
		while ( !$shop_order_id && $i < 5 ) {
			$shop_order_id=M('shop_order')->add($data);
			$i++;
		}

		//返回的发货单信息
		$shopOrder=$data;
		$shopOrder['shop_order_id']=$shop_order_id;

		return $shopOrder;
		
	}

	/**
	 * 获得重销订单的默认地址信息
	 * @author 黄俊
	 * 2017-7-27
	 * return array()
	 */
	public function getOrderAddress($member_id){

		$orderAddress=array();

		//取status默认最大的那个
		$orderAddress=M('shop_order_address')->where(array('member_id'=>$member_id))->order('status DESC')->find();

		// 如果没有地址信息
		if( empty($orderAddress) ){
			$orderAddress['shop_order_address_id']=0;
			$orderAddress['address']='无';
			$orderAddress['name']='无';
			$orderAddress['tel']='无';
		}

		// 返回
		return $orderAddress;

	}


	/**
	 * 通过where条件统计订单总数
	 * @author 黄俊
	 * date 2017-7-27
	 * return： int
	 */
	public function getShopOrderCount($where='',$status=0){

		//实例化模型
		$m=new Model();
		$sql='';

		//判断where是否为空
		if(empty($where)){
			$sql.='SELECT count(*) total FROM `shop_order` so ';
			//根据状态筛选订货单
			if( $status!=0 ){
				$sql.=' where so.status='.$status;
			}
		}else{
			$sql.='SELECT count(*) total FROM (select member_id,user,name,tel from `member` where '.$where.') m ';
			$sql.=' LEFT JOIN `shop_order` so ON so.`member_id`=m.`member_id`';
			$sql.=' where so.`shop_order_id` IS NOT NULL ';

			//根据状态筛选订货单
			if( $status!=0 ){
				$sql.=' and so.status='.$status;
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
	 * date 2017-7-27
	 * return： array()
	 */
	public function getList($where='',$limit='',$status=0){

		//实例化模型
		$m=new Model();
		$sql='';

		//判断where是否为空
		if(empty($where)){

			$sql.='SELECT so.*,soa.name agent_name,soa.tel agent_tel,soa.address agent_address,m.name,m.tel,m.user FROM `shop_order` so ';
			$sql.=' LEFT JOIN `member` m ON so.`member_id`=m.`member_id`';
			$sql.=' LEFT JOIN `shop_order_address` soa ON soa.`shop_order_address_id`=so.`shop_order_address_id`';

			//根据状态筛选订货单
			if( $status!=0 ){
				$sql.=' where so.status='.$status;
			}
		}else{
			$sql.='SELECT so.*,soa.name agent_name,soa.tel agent_tel,soa.address agent_address,m.name,m.tel,m.user FROM (select member_id,user,name,tel from `member` where '.$where.') m ';
			$sql.=' LEFT JOIN `shop_order` so ON so.`member_id`=m.`member_id`';
			$sql.=' LEFT JOIN `shop_order_address` soa ON soa.`shop_order_address_id`=so.`shop_order_address_id`';
			$sql.=' where so.`shop_order_id` IS NOT NULL ';

			//根据状态筛选订货单
			if( $status!=0 ){
				$sql.=' and so.status='.$status;
			}
		}

		//order by 排序
		$sql.=' order by field(so.`status`,1,2,3,4,5),add_time DESC,update_time DESC';

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
	 * 通过shop_order_id获得重销订单信息
	 * @author 黄俊
	 * date 2017-7-28
	 * return： array()
	 */
	public function getShopOrder($shop_order_id){

		//实例化模型
		$m=new Model();

		// 查询sql
		$sql='';
		$sql.='SELECT ';

		//返回的字段
		$sql.='so.*,soa.name agent_name,soa.tel agent_tel,soa.address agent_address,m.name,m.tel,m.user ';

		//先查询出一条订单，避免生成的临时表过多
		$sql.=' FROM (SELECT * FROM  `shop_order` WHERE shop_order_id='.$shop_order_id.') so';

		//订单关联的会员信息
		$sql.=' LEFT JOIN `member` m ON m.`member_id`=so.`member_id`';

		//订单的发货地址相关信息
		$sql.=' LEFT JOIN `shop_order_address` soa ON soa.`shop_order_address_id`=so.`shop_order_address_id`';

		// echo $sql;die();
		//查询
		$rs=$m->query($sql);

		// 返回结果
		return $rs[0];
	}

	/**
	 * 通过shop_order_id获得重销订单商品列表信息
	 * @author 黄俊
	 * date 2017-7-28
	 * return： array()
	 */
	public function getOrderGoodsList($shop_order_id){

		//实例化模型
		$m=new Model();

		// 查询sql
		$sql='';
		$sql.='SELECT ';
		$sql.=' sogl.list_id,';
		$sql.=' sogl.num,';
		$sql.=' g.goods_id,';
		$sql.=' g.cate_id,';
		$sql.=' g.name,';
		$sql.=' g.number,';
		$sql.=' g.size,';
		$sql.=' g.color,';
		$sql.=' g.price,';
		$sql.=' g.price_unit,';
		$sql.=' g.discount,';
		$sql.=' g.sort,';
		$sql.=' g.num_unit';
		$sql.=' FROM (SELECT * FROM  `shop_order_goods_list` WHERE shop_order_id='.$shop_order_id.') sogl';
		$sql.=' LEFT JOIN `shop_goods` g ON g.`goods_id`=sogl.`goods_id`';
		$sql.=' order by g.`sort` DESC';

		// echo $sql;die();
		//查询
		$rs=$m->query($sql);

		// 返回结果
		return $rs;
	}

	/**
	 * 根据订单状态值，返回对应的状态
	 * @author 黄俊
	 * date 2016-12-11
	 */
	public function getStatus($status){
		$str='';
		switch ($status) {
			case 1:
				$str='下单中';
				break;
			case 2:
				$str='准备发货';
				break;
			case 3:
				$str='发货中';
				break;
			case 4:
				$str='已发货';
				break;
			case 5:
				$str='完结';
				break;
			
			default:
				$str='未知错误';
				break;
		}

		return $str;
	}


	/**
	 * 记录仓库发货日志
	 * @author 黄俊
	 * date 2017-7-31
	 */
	public function writeDeliverGoodsLog($shop_order_id){
		
		//获得发货单内的货物清单
		$goodsList=M('shop_order_goods_list')->where(array('shop_order_id'=>$shop_order_id))->select();

		/*循环处理*/
		$deliverGoodsLog=array();//用来存放日志--二维数组

		foreach ($goodsList as $key => $value) {

			$deliverGoodsLog[$key]['shop_order_id']=$value['shop_order_id'];
			$deliverGoodsLog[$key]['goods_id']=$value['goods_id'];
			$deliverGoodsLog[$key]['num']=$value['num'];
			$deliverGoodsLog[$key]['add_time']=date('Y-m-d H:i:s');
			$deliverGoodsLog[$key]['update_time']=date('Y-m-d H:i:s');
			$deliverGoodsLog[$key]['handle_user']=$_SESSION['user'];
			$deliverGoodsLog[$key]['handle_name']=$_SESSION['name'];
		}

		/*批量写入*/
		M('shop_deliver_goods_log')->addAll($deliverGoodsLog);
		
	}



	
}

?>