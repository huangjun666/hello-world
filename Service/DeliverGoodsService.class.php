<?php
/**
 * 发货服务service
 * @author 黄俊
 * @date 2016-11-30
 */
class DeliverGoodsService extends BaseService{

	/**
	 * 获得物流公司信息
	 * @author 黄俊
	 * 2016-12-7
	 */
	public function getLogisticsCompany(){
		//物流公司信息
		$logisticsCompany=M('logistics_company')->select();

		//获取失败的情况下，重复获取，最多5次
		$i=0;
		while ( empty($logisticsCompany) && $i < 5 ) {
			$logisticsCompany=M('logistics_company')->select();
			$i++;
		}
		return $logisticsCompany;
	}

	/**
	 * 物流查询--获得详细物流信息
	 * @author 黄俊
	 * 接口来源：阿里云
	 * 备注：【要花钱的】
	 * 参数：$companyType：物流公司，$number：物流单号
	 * date 2016-12-7
	 */
	public function getLogisticsInfo($companyType,$number){

		$logisticsInfo=SERVICE('Api')->getLogisticsInfo($companyType,$number);

		//获取失败的情况下，重复获取，最多3次
		$i=0;
		while ( empty($logisticsInfo) && $i < 3 ) {
			$logisticsInfo=SERVICE('Api')->getLogisticsInfo($companyType,$number);
			$i++;
		}
		
		return $logisticsInfo;
	}

	/**
	 * 根据字母编号获得物流公司名称
	 * @author 黄俊
	 * 2016-12-7
	 */
	public function getCompanyName($type){

		//物流公司信息
		$logisticsCompany=$this->getLogisticsCompany();

		$name='';//公司名称
		//循环查找
		foreach ($logisticsCompany as $key => $value) {
			if($value['type'] ==$type){
				$name=$value['name'];
				break;
			}
		}

		return $name;//返回结果
	}

	/**
	 * 更新数据库物流公司信息
	 * @author 黄俊
	 * 2016-12-7
	 */
	public function updateLogisticsCompany(){

		//从接口获得物流公司信息
		$logisticsCompany=SERVICE('Api')->getLogisticsCompany();
		$i=0;

		//获取失败的情况下，重复获取，最多5次
		while ( $logisticsCompany->status != 0 && $i < 5) {
			$logisticsCompany=SERVICE('Api')->getLogisticsCompany();
			$i++;
		}

		//失败
		if($logisticsCompany->status != 0){
			echo "更新失败！";
			exit();
		}

		//删除旧数据
		M('logistics_company')->where('id>=0')->delete();

		//新数据
		$data=array();
		foreach ($logisticsCompany->result as $key => $value) {
			$data[$key]['name']=$value->name;
			$data[$key]['type']=$value->type;
			$data[$key]['letter']=$value->letter;
			$data[$key]['tel']=$value->tel;
			$data[$key]['number']=$value->number;
		}

		//批量插入
		$count=M('logistics_company')->addAll($data);

		return $count;
	}

	/**
	 * 根据订货单，创建发货单
	 * @author 黄俊
	 * 2016-12-7
	 * return array() 发货单信息
	 */
	public function createDeliverGoods($orderGoods){

		//数据
		$data['order_id']=$orderGoods['order_id'];
		$data['order_goods_id']=$orderGoods['order_goods_id'];
		$data['deliver_address']=$orderGoods['goods_address'];
		$data['deliver_name']=$orderGoods['goods_name'];
		$data['deliver_tel']=$orderGoods['goods_tel'];
		$data['logistics_company']='0';
		$data['waybill_number']='';
		$data['add_time']=date('Y-m-d H:i:s');
		$data['update_time']=date('Y-m-d H:i:s');

		//新建发货单
		$deliver_id=0;
		$i=0;
		while ( !$deliver_id && $i < 5 ) {
			$deliver_id=M('deliver_goods')->add($data);
			$i++;
		}

		//返回的发货单信息
		$deliverGoods=$data;
		$deliverGoods['deliver_id']=$deliver_id;

		return $deliverGoods;
	}

	/**
	 * 根据发货单ID获得发货单信息
	 * @author 黄俊
	 * date 2016-12-3
	 * return： 一维array() 订货单信息
	 */
	public function getDeliverGoods($deliver_id){

		//实例化模型
		$m=new Model();

		// 查询sql
		$sql='';
		$sql.='SELECT ';
		$sql.=' dg.deliver_id,';
		$sql.=' dg.order_goods_id,';
		$sql.=' dg.order_id,';
		$sql.=' dg.deliver_address,';
		$sql.=' dg.deliver_name,';
		$sql.=' dg.deliver_tel,';
		$sql.=' dg.logistics_company,';
		$sql.=' dg.waybill_number,';
		$sql.=' dg.add_time,';
		$sql.=' dg.remarks,';
		$sql.=' dg.status,';
		$sql.=' o.`name`,';
		$sql.=' o.`tel`,';
		$sql.=' o.`money`,';
		$sql.=' o.`id_card`,';
		$sql.=' o.`handle`,';
		$sql.=' o.`recommend_user`,';
		$sql.=' o.`place_user`,';
		$sql.=' o.`status` orderStatus ';

		$sql.=' FROM (SELECT * FROM  `deliver_goods` WHERE deliver_id='.$deliver_id.') dg';
		
		$sql.=' LEFT JOIN `order` o ON o.`order_id`=dg.`order_id`';

		// echo $sql;die();
		//查询
		$rs=$m->query($sql);

		// 返回结果
		return $rs[0];
	}

	/**
	 * 根据发货单ID获得发货单的货品信息
	 * @author 黄俊
	 * date 2016-12-11
	 * return： 二维array() 发货单的货品list信息
	 */
	public function getDeliverGoodsList($deliver_id){
		//实例化模型
		$m=new Model();

		// 查询sql
		$sql='';
		$sql.='SELECT ';
		$sql.=' dgl.list_id,';
		$sql.=' dgl.num,';
		$sql.=' g.goods_id,';
		$sql.=' g.name,';
		$sql.=' g.number,';
		$sql.=' g.size,';
		$sql.=' g.color,';
		$sql.=' g.price,';
		$sql.=' g.price_unit,';
		$sql.=' g.discount,';
		$sql.=' g.sort,';
		$sql.=' g.num goodsNum,';
		$sql.=' g.num_unit';
		$sql.=' FROM (SELECT * FROM  `deliver_goods_list` WHERE deliver_id='.$deliver_id.') dgl';
		$sql.=' LEFT JOIN `goods` g ON g.`goods_id`=dgl.`goods_id`';
		$sql.=' order by g.`sort` DESC';

		//查询
		$rs=$m->query($sql);

		// 返回结果
		return $rs;
	}

	/**
	 * 验证物流单号是否有效
	 * @author 黄俊
	 * date 2016-12-11
	 * return： bool
	 */
	public function isCanDeliver($companyType,$number){
		

		//从接口获得物流公司信息
		$logisticsInfo=SERVICE('Api')->getLogisticsInfo($companyType,$number);
		$i=0;

		//获取失败的情况下，重复获取，最多2次
		while ( $logisticsInfo->status != 0 && $i < 2) {
			$logisticsInfo=SERVICE('Api')->getLogisticsInfo($companyType,$number);
			$i++;
		}

		//失败
		if($logisticsInfo->status != 0){
			return false;
		}

		return true;
	}

	/**
	 * 通过where条件统计总数
	 * @author 黄俊
	 * date 2016-12-3
	 * return： int
	 */
	public function getDeliverGoodsCount($where='',$status=0){

		//实例化模型
		$m=new Model();
		$sql='';

		//判断where是否为空
		if(empty($where)){
			$sql.='SELECT count(*) total FROM `deliver_goods` dg ';
			//根据状态筛选订货单
			if( $status!=0 ){
				$sql.=' where dg.status='.$status;
			}
		}else{
			$sql.='SELECT count(*) total FROM `deliver_goods` dg ';
			$sql.=' LEFT JOIN (select order_id,name,tel,money from `order` where '.$where.') o ON dg.`order_id`=o.`order_id`';
			$sql.=' where o.name IS NOT NULL ';
			//根据状态筛选订货单
			if( $status!=0 ){
				$sql.=' and dg.status='.$status;
			}
		}

		//查询
		$rs=$m->query($sql);

		// 返回结果
		return $rs[0]['total'];

	}

	/**
	 * 通过where条件,limit条件获得发货单list
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
			$sql.='SELECT dg.*,o.name,o.tel,o.money,o.id_card FROM `deliver_goods` dg ';
			$sql.=' LEFT JOIN `order` o ON dg.`order_id`=o.`order_id`';
			//根据状态筛选订货单
			if( $status!=0 ){
				$sql.=' where dg.status='.$status;
			}
		}else{
			$sql.='SELECT dg.*,o.name,o.tel,o.money,o.id_card FROM `deliver_goods` dg ';
			$sql.=' LEFT JOIN (select order_id,name,tel,money,id_card from `order` where '.$where.') o ON dg.`order_id`=o.`order_id`';
			$sql.=' where o.name IS NOT NULL ';
			//根据状态筛选订货单
			if( $status!=0 ){
				$sql.=' and dg.status='.$status;
			}
		}

		//order by 排序
		$sql.=' order by `status` ASC,update_time DESC,add_time DESC';

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
	 * 处理原始物流数据，得到需要的结果，
	 * @author 黄俊
	 * date 2016-12-3
	 * return： array() 物流时间节点和状态
	 */
	public function handleLogisticsInfo($logisticsInfo){

		$list=$logisticsInfo->result->list;//获得物流信息list
		$list=array_reverse($list);//顺序反转
		$newList=array();//新的list


		$ymd='';//临时存储年月日
		//循环处理时间
		foreach ($list as $key => $value) {

			$time=strtotime($value->time);//时间戳

			//时间处理，取得年月日
			if( $ymd != date('Y-m-d',$time) ){
				$ymd=date('Y-m-d',$time);
				$newList[$key]['ymd']=$ymd;
			}else{
				$newList[$key]['ymd']='';
			}

			$newList[$key]['time']=date('H:i:s',$time);
			$newList[$key]['status']=$value->status;

		}

		// 返回新的list
		return $newList;
	}


	/**
	 * 检查订货单，是否满足切换到二审状态的条件
	 * @author 黄俊
	 * date 2016-12-14
	 * return： bool 满足：true 不满足：false
	 */
	public function isCanSecondAudit($order_goods_id){

		// 奖励配置信息
		$reward_config=D('Web')->reward_config();

		# 1、订货单内的货物，全部发完
		if( !SERVICE('OrderGoods')->isEmptyGoods($order_goods_id) ){
			return false;
		}

		# 2、发货单全部被确认
		$deliverGoods=M('deliver_goods')->where('order_goods_id='.$order_goods_id.' AND `status` IN(1,2)')->select();
		if( !empty($deliverGoods) ){
			return false;
		}

		# 3、已发货物的总金额不少于订单金额
		//订货单信息
		$orderGoods=SERVICE('OrderGoods')->getOrderGoods($order_goods_id);
		//订货单当前总金额
		$currentGoodSMoney=SERVICE('OrderGoods')->getCurrentGoodSMoney($order_goods_id);

		if( ($orderGoods['money']-$reward_config['order_discount']) > $currentGoodSMoney ){
			return false;
		}

		//以上都满足，返回true
		return true;

	}

	/**
	 * 验证是否缺货
	 * @author 黄俊
	 * date 2016-12-11
	 * return： 数组：缺货清单
	 */
	public function checkDeliver($deliver_id){

		// 获得货物list
		$goodsList=$this->getDeliverGoodsList($deliver_id);

		// 结果
		$rs=array();

		//遍历检查
		foreach ($goodsList as $key => $value) {

			// 如果
			if( $value['goodsNum'] < 0 ){
				$rs[]=array(
					'name'=>$value['name'],
					'num'=>$value['goodsNum']
					);
			}
		}

		// 返回结果
		return $rs;
	}

	/**
	 * 根据发货单状态值，返回对应的状态
	 * @author 黄俊
	 * date 2016-12-11
	 */
	public function getStatus($status){
		$str='';
		switch ($status) {
			case 1:
				$str='待发货';
				break;
			case 2:
				$str='已发货';
				break;
			case 3:
				$str='已收到';
				break;
			case 4:
				$str='未收到';
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
	 * date 2017-3-3
	 */
	public function writeDeliverGoodsLog($deliver_id){
		
		//获得发货单内的货物清单
		$goodsList=M('deliver_goods_list')->where(array('deliver_id'=>$deliver_id))->select();

		/*循环处理*/
		$deliverGoodsLog=array();//用来存放日志--二维数组

		foreach ($goodsList as $key => $value) {

			$deliverGoodsLog[$key]['deliver_id']=$value['deliver_id'];
			$deliverGoodsLog[$key]['goods_id']=$value['goods_id'];
			$deliverGoodsLog[$key]['num']=$value['num'];
			$deliverGoodsLog[$key]['add_time']=date('Y-m-d H:i:s');
			$deliverGoodsLog[$key]['update_time']=date('Y-m-d H:i:s');
			$deliverGoodsLog[$key]['handle_user']=$_SESSION['user'];
			$deliverGoodsLog[$key]['handle_name']=$_SESSION['name'];
		}

		/*批量写入*/
		M('deliver_goods_log')->addAll($deliverGoodsLog);
		
	}

	/**
	 * 至少确认收过一次货
	 * @author 黄俊
	 * date 2017-3-3
	 * return: bool
	 */
	public function isFirstReceipt($order_id){
		
		#查询出已经确认收货的发货单
		$deliverGoods=M('deliver_goods')->where('order_id='.$order_id.' AND `status` IN(3)')->select();
		if( !empty($deliverGoods) ){
			return true;//收过
		}else{
			return false;//未收过
		}
		
	}
}

?>