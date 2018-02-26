<?php
/**
 * 发货管理
 * @author 黄俊
 * date 2016-11-31
 */
class DeliverGoodsAction extends BaseAction{

	/**
	 * 首页--列表页
	 * @author 黄俊
	 * date 2016-11-31
	 */
	public function index(){
		//权限判断
		if( !in_array($_SESSION['role'], array(1,2,3,4,5)) ){
			$this->error('权限不够！');
		}

		if(IS_POST){
			//权限判断
			if( !in_array($_SESSION['role'], array(2,3,4,5)) ){
				$this->error('权限不够！');
			}

			$name=I('name','');

			//关键词为空
			if(empty($name)){
				$this->redirect('index');
			}

			//生活馆馆主
			if( in_array($_SESSION['role'], array(2)) ){
				$where=' `name` LIKE "%'.$name.'%" AND (recommend_user="'.$_SESSION['user'].'"  OR place_user="'.$_SESSION['user'].'" or handle="'.$_SESSION['user'].'" or id_card="'.$_SESSION['id_card'].'")';
			}

			//财务、系统管理员、仓库管理
			if( in_array($_SESSION['role'], array(3,4,5)) ){
				$where=' `name` LIKE "%'.$name.'%" ';
			}

			//发货单列表
			$deliverGoods=SERVICE('DeliverGoods')->getList($where);
			$this->sort=0;//序号
			$this->assign('deliverGoods',$deliverGoods);

		}else{
			import('ORG.Util.Page');//引入分页类

			$name=I('name','');
			$status=I('status',0,'intval');

			$where='';//范围

			//普通经销商
			if( !in_array($_SESSION['role'], array(2,3,4,5)) ){
				$where=' recommend_user="'.$_SESSION['user'].'"  OR place_user="'.$_SESSION['user'].'" or id_card="'.$_SESSION['id_card'].'"';
			}

			//生活馆馆主
			if( !in_array($_SESSION['role'], array(1,3,4,5)) ){
				$where=' recommend_user="'.$_SESSION['user'].'"  OR place_user="'.$_SESSION['user'].'" or handle="'.$_SESSION['user'].'" or id_card="'.$_SESSION['id_card'].'"';
			}

			//生活馆馆主
			if( in_array($_SESSION['role'], array(2)) && !empty($name)  ){
				$where=' `name` LIKE "%'.$name.'%" AND (recommend_user="'.$_SESSION['user'].'"  OR place_user="'.$_SESSION['user'].'" or handle="'.$_SESSION['user'].'" or id_card="'.$_SESSION['id_card'].'")';
			}

			//财务、系统管理员、仓库管理
			if( in_array($_SESSION['role'], array(3,4,5)) && !empty($name)  ){
				$where=' `name` LIKE "%'.$name.'%" ';
			}
			// echo $where;die();

			/*分页*/
			$count=SERVICE('DeliverGoods')->getDeliverGoodsCount($where,$status);//总数量
			$page=new Page($count,20);
			$limit=$page->firstRow.','.$page->listRows;

			//发货单列表
			$deliverGoods=SERVICE('DeliverGoods')->getList($where,$limit,$status);

			//为每一列，加上会员账号信息
			foreach ($deliverGoods as $key => $value) {
				$memberInfo=SERVICE('Member')->getMemberByIdCard($value['id_card']);
				$deliverGoods[$key]['user']=$memberInfo['user'];
			}
			// P($deliverGoods);die();

			$this->page=$page->show();//获得页码
			$this->sort=$page->firstRow;//序号
			$this->assign('deliverGoods',$deliverGoods);
		}
		
		$this->display();
		
	}

	/**
	 * 编辑发货单
	 * @author 黄俊
	 * date 2016-11-31
	 */
	public function edit(){
		//权限判断
		if( !in_array($_SESSION['role'], array(3,4,5)) ){
			$this->error('权限不够！');
		}

		$order_id=I('order_id',0,'intval');//订单id
		$deliver_id=I('deliver_id',0,'intval');//发货单id--从列表页点编辑过来

		//验证发货单状态
		if(!empty($deliver_id)){
			$deliverGoods=M('deliver_goods')->where(array('deliver_id'=>$deliver_id))->find();
			if( $deliverGoods['status']!=1 ){
				$this->redirect('view', array('deliver_id'=>$deliver_id));
			}
		}

		#1、查询出订货单信息，
		$orderGoods=SERVICE('OrderGoods')->getOrderGoods($order_id,true);

		if( empty($orderGoods) ){
			$this->error('非法操作：订货单不存在！');
		}

		//验证订单是否通过一审，处于待二审状态
		// if( $orderGoods['orderStatus']!=5 ){
		// 	$this->error('非法操作：订单通过一审才可以发货！');
		// }

		//验证订货单，是否处于待二审(发货中)状态
		// if( $orderGoods['status']!=2 ){
		// 	$this->error('非法操作：订货单处于发货中才可以发货！');
		// }

		#2、查询该订货单下面有没处于待发货状态的发货单，没有则新建
		$deliverGoods=M('deliver_goods')->where(array('order_id'=>$order_id,'status'=>1))->find();

		//为空，则根据订货单 自动创建发货单
		if(empty($deliverGoods)){

			//根据订货单内未发货数量，判断是否可以创建
			$isEmpty=SERVICE('OrderGoods')->isEmptyGoods($orderGoods['order_goods_id']);
			if( $isEmpty ){//如果不可以
				$this->error('订货单货物已经发完！');
			}
			$deliverGoods=SERVICE('DeliverGoods')->createDeliverGoods($orderGoods);
		}

		$deliverGoods=SERVICE('DeliverGoods')->getDeliverGoods($deliverGoods['deliver_id']);//发货单信息
		$this->memberInfo=SERVICE('Member')->getMemberByIdCard($deliverGoods['id_card']);//会员信息
		$this->deliverGoods=$deliverGoods;
		$this->deliverGoodsList=SERVICE('DeliverGoods')->getDeliverGoodsList($deliverGoods['deliver_id']);//发货单货物信息
		$this->logisticsCompany=SERVICE('DeliverGoods')->getLogisticsCompany();//物流公司信息

		$this->display();
		
	}

	/**
	 * 详情页
	 * @author 黄俊
	 * date 2016-11-31
	 */
	public function view(){
		//权限判断
		if( !in_array($_SESSION['role'], array(1,2,3,4,5)) ){
			$this->error('权限不够！');
		}
		
		$deliver_id=I('deliver_id',0,'intval');

		$deliverGoods=SERVICE('DeliverGoods')->getDeliverGoods($deliver_id);//发货单信息

		//判断用户是否可以访问该订单的发货单
		//如果不是财务或系统管理员，检查用户是否有权限访问，防止绕过流程，直接访问
		if( !in_array($_SESSION['role'], array(3,4,5)) ){

			//筛选条件
			$rule=array();
			$rule[]=$deliverGoods['recommend_user'];
			$rule[]=$deliverGoods['place_user'];
			$rule[]=$deliverGoods['handle'];
			$rule[]=$deliverGoods['id_card'];

			//判断
			if( !in_array($_SESSION['user'], $rule) && !in_array($_SESSION['id_card'], $rule) ){
				$this->error('警告！！非法操作！');
			}

		}

		$this->memberInfo=SERVICE('Member')->getMemberByIdCard($deliverGoods['id_card']);//会员信息
		$this->deliverGoods=$deliverGoods;//发货单信息
		$this->deliverGoodsList=SERVICE('DeliverGoods')->getDeliverGoodsList($deliverGoods['deliver_id']);//发货单货物信息
		$this->display();
		
	}

	/**
	 * 发货
	 * @author 黄俊
	 * date 2016-11-31
	 */
	public function deliverGoods(){
		//权限判断
		if( !in_array($_SESSION['role'], array(3,4,5)) ){
			$this->error('权限不够！');
		}
		
		if(IS_AJAX){

			// 接收参数
			$num=I('num',0,'intval');
			$order_id=I('order_id',0,'intval');
			$goods_id=I('goods_id',0,'intval');
			$action=I('action','');//add、del

			#0、验证参数合法性
			//$action
			if( !in_array($action, array('del','add')) ){//del:加入发货单 add:退货 【del、add是相对订货单列表而言】
				$rs['status']=2;
				$rs['msg']='非法操作：action非法！';
				$this->ajaxReturn($rs);
			}

			// $num
			if( $num<=0 ){
				$rs['status']=2;
				$rs['msg']='非法操作:数目不对！';
				$this->ajaxReturn($rs);
			}
			// 货品出库入库记录数据
			$num=$action=='add'?-$num:$num;//变动数量
			// echo $num;die();
			#1、查询出订货单信息，
			$orderGoods=SERVICE('OrderGoods')->getOrderGoods($order_id,true);

			if( empty($orderGoods) ){
				$rs['status']=2;
				$rs['msg']='非法操作：订货单不存在！';
				$this->ajaxReturn($rs);
			}

			//验证订单是否通过一审
			if( in_array($orderGoods['orderStatus'], array(1,4) ) ){
				$rs['status']=2;
				$rs['msg']='非法操作：订单通过一审才可以发货！';
				$this->ajaxReturn($rs);
			}

			//验证订货单，是否处于待二审(发货中)状态
			if( in_array($orderGoods['status'], array(1,7) ) ){
				$rs['status']=2;
				$rs['msg']='非法操作：订货单发货中状态才可以！';
				$this->ajaxReturn($rs);
			}

			#2、查询该订货单下面有没处于待发货状态的发货单，没有则新建
			$deliverGoods=M('deliver_goods')->where(array('order_id'=>$order_id,'status'=>1))->find();

			//为空，则根据订货单 自动创建发货单
			if(empty($deliverGoods)){
				$deliverGoods=SERVICE('DeliverGoods')->createDeliverGoods($orderGoods);
			}

			#3、将对应的货物和数量加入到发货单列表中，同时记录订货单货物的已发货数量
			//订货单货品列表中，即将发货的那件货物
			$orderGoodsList=M('order_goods_list')->where(array('goods_id'=>$goods_id,'order_goods_id'=>$deliverGoods['order_goods_id']))->find();
			// P($orderGoodsList);die();

			//查看订单货该货物数量是否满足发货需求
			if( $orderGoodsList['num'] < $num ){
				$rs['status']=2;
				$rs['msg']='数量不够！';
				$this->ajaxReturn($rs);
			}

			
			/*将对应的货物和数量加入到发货单列表中*/
			//是否有相同的货品，已经被加入到该发货单:
			$deliverGoodsList=M('deliver_goods_list')->where(array('deliver_id'=>$deliverGoods['deliver_id'],'goods_id'=>$goods_id))->find();

			if( empty($deliverGoodsList) ){//没有，执行保存操作
				// 如果是退回操作
				if($action=='add'){
					$rs['status']=2;
					$rs['msg']='警告：非法操作，该发货单内，没有该货物！';
					$this->ajaxReturn($rs);
				}
				$deliverGoodsListData['deliver_id']=$deliverGoods['deliver_id'];
				$deliverGoodsListData['goods_id']=$goods_id;
				$deliverGoodsListData['num']=$num;
				$deliverGoodsListData['add_time']=date('Y-m-d H:i:s');
				$deliverGoodsListData['update_time']=date('Y-m-d H:i:s');

				if(!M('deliver_goods_list')->add($deliverGoodsListData)){
					$rs['status']=2;
					$rs['msg']='加入到发货单列表失败';
					$this->ajaxReturn($rs);
				}
			}else{//有，执行更新操作

				$deliverGoodsListData['list_id']=$deliverGoodsList['list_id'];
				$deliverGoodsListData['num']=$deliverGoodsList['num']+$num;
				$deliverGoodsListData['update_time']=date('Y-m-d H:i:s');

				#判断改变后的num:
				if( $deliverGoodsListData['num']<0 ){//操作不合法：没有那么多货物数量
					$rs['status']=2;
					$rs['msg']='没有那么多货物数量';
					$this->ajaxReturn($rs);
				}else if($deliverGoodsListData['num']==0 ){//数量为0，执行删除操作
					if( !M('deliver_goods_list')->where(array('list_id'=>$deliverGoodsListData['list_id']))->delete() ){
						$rs['status']=2;
						$rs['msg']='网络不好，请重试!';
						$this->ajaxReturn($rs);
					}
				}else{//其余，执行更新操作
					if(!M('deliver_goods_list')->save($deliverGoodsListData)){
						$rs['status']=2;
						$rs['msg']='加入到订货单列表失败';
						$this->ajaxReturn($rs);
					}
				}
			}

			/*记录订货单该货物的已发货数量*/ 
			$orderGoodsListData['list_id']=$orderGoodsList['list_id'];
			$orderGoodsListData['num']=$orderGoodsList['num']-$num;
			$orderGoodsListData['deliver_num']=$orderGoodsList['deliver_num']+$num;
			$orderGoodsListData['update_time']=date('Y-m-d H:i:s');

			//保存，更新
			$count=M('order_goods_list')->save($orderGoodsListData);
			if ( $count ) {
				$rs['status']=1;
				$rs['num']=$num;
				$rs['msg']='成功';
				$this->ajaxReturn($rs);
			}else{
				$rs['status']=2;
				$rs['msg']='记录订货单该货物的已发货数量失败！！！';
				$this->ajaxReturn($rs);
			}

		}
		
	}

	/**
	 * 保存发货单
	 * @author 黄俊
	 * date 2016-12-11
	 */
	public function save(){
		//权限判断
		if( !in_array($_SESSION['role'], array(3,4,5)) ){
			$this->error('权限不够！');
		}

		if(IS_AJAX){
			$deliver_id=I('deliver_id',0,'intval');

			// 判断参数合法性
			if($deliver_id==0){
				$rs['status']=2;
				$rs['msg']='非法操作！';
				$this->ajaxReturn($rs);
			}

			//验证订货单是否可以执行保存操作
			$deliverGoods=M('deliver_goods')->where(array('deliver_id'=>$deliver_id))->find();

			if( empty($deliverGoods) ){
				$rs['status']=2;
				$rs['msg']='非法操作！';
				$this->ajaxReturn($rs);
			}

			if($deliverGoods['status']!=1){
				$rs['status']=2;
				$rs['msg']='已发货，不可以编辑！';
				$this->ajaxReturn($rs);
			}

			//要保存的数据
			$data['deliver_id']=$deliver_id;
			$data['deliver_address']=I('deliver_address','');
			$data['deliver_name']=I('deliver_name','');
			$data['deliver_tel']=I('deliver_tel','');
			$data['logistics_company']=I('logistics_company','');
			$data['waybill_number']=I('waybill_number','');
			$data['remarks']=I('remarks','');
			$data['update_time']=date('Y-m-d H:i:s');

			// 判断是否保存并提交
			$save=I('save',0,'intval');
			if($save){

				$data['status']=2;//status:2、已发货

				// 验证收货地址
				if(empty($data['deliver_address'])){
					$rs['status']=2;
					$rs['msg']="发货失败，收货地址不能为空！！";
					$this->ajaxReturn($rs);
				}

				// 验证收货人姓名
				if(empty($data['deliver_name'])){
					$rs['status']=2;
					$rs['msg']="发货失败，收货人姓名不能为空！！";
					$this->ajaxReturn($rs);
				}

				// 验证联系方式
				if( !is_tel($data['deliver_tel']) && !is_fixedTel($data['deliver_tel']) ){
					$rs['status']=2;
					$rs['msg']="发货失败，请输入正确的电话号码！！";
					$this->ajaxReturn($rs);
				}

				//检查物流公司和运单号
				if($save==1){//只有当save=1时，才需要验证物流
					if( !SERVICE('DeliverGoods')->isCanDeliver($data['logistics_company'],$data['waybill_number']) ){
						$rs['status']=2;
						$rs['msg']="发货失败，请输入有效的物流公司和运单号！！";
						$this->ajaxReturn($rs);
					}	
				}
				

			}

			//保存
			if(M('deliver_goods')->save($data)){
				$rs['status']=1;
				$rs['msg']="保存成功！";
				if($save){

					//发货成功时，检查订货单内是否还有货
					if( !SERVICE('OrderGoods')->isEmptyGoods($deliverGoods['order_goods_id']) ){

						//还有未发货，则改变订货单状态为4【待补发】
						$orderGoodsData['order_goods_id']=$deliverGoods['order_goods_id'];
						$orderGoodsData['status']=4;
						$orderGoodsData['update_time']=date('Y-m-d H:i:s');

						//保存
						M('order_goods')->save($orderGoodsData);
					}

					// 记录仓库发货日志
					SERVICE('DeliverGoods')->writeDeliverGoodsLog($deliver_id);

					$rs['msg']="发货成功！";
				}
				$this->ajaxReturn($rs);
			}else{
				$rs['status']=2;
				$rs['msg']="失败！！";
				$this->ajaxReturn($rs);
			}

		}else{
			$this->error('非法操作！');
		}
		

	}

	/**
	 * 订货单下发货单管理
	 * @author 黄俊
	 * date 2016-12-11
	 */
	public function deliverGoodsManage(){
		//权限判断
		if( !in_array($_SESSION['role'], array(1,2,3,4,5)) ){
			$this->error('权限不够！');
		}

		//订单id
		$order_id=I('order_id',0,'intval');

		//条件
		$where=' order_id='.$order_id;
		
		//发货单列表
		$deliverGoods=SERVICE('DeliverGoods')->getList($where);
		$this->sort=0;//序号
		$this->assign('deliverGoods',$deliverGoods);
		$this->display('index');
	}

	/**
	 * 查看发货单货物信息
	 * @author 黄俊
	 * date 2016-12-11
	 */
	public function showLogistics(){

		//权限判断
		if( !in_array($_SESSION['role'], array(1,2,3,4,5)) ){
			$this->error('权限不够！');
		}

		if(IS_AJAX){
			$deliver_id=I('deliver_id',0,'intval');

			$deliverGoods=SERVICE('DeliverGoods')->getDeliverGoods($deliver_id);//发货单信息

			//判断用户是否可以访问该订单的发货单
			//如果不是财务或系统管理员或仓库管理员，检查用户是否有权限访问，防止绕过流程，直接访问
			if( !in_array($_SESSION['role'], array(3,4,5)) ){

				//筛选条件
				$rule=array();
				$rule[]=$deliverGoods['recommend_user'];
				$rule[]=$deliverGoods['place_user'];
				$rule[]=$deliverGoods['handle'];
				$rule[]=$deliverGoods['id_card'];

				//判断
				if( !in_array($_SESSION['user'], $rule) && !in_array($_SESSION['id_card'], $rule) ){
					$rs['status']=2;
					$rs['msg']="警告！！非法操作！";
					$this->ajaxReturn($rs);
				}

			}

			//获得物流信息	
			$logisticsInfo=SERVICE('Api')->getLogisticsInfo($deliverGoods['logistics_company'],$deliverGoods['waybill_number']);

			//处理获得的原始数据
			$newLogisticsInfo=SERVICE('DeliverGoods')->handleLogisticsInfo($logisticsInfo);

			if($newLogisticsInfo){
				$rs['status']=1;
				$rs['msg']="成功！！";
				$rs['list']=$newLogisticsInfo;
				$this->ajaxReturn($rs);
			}else{
				$rs['status']=2;
				$rs['msg']="查看物流失败，请重试！！";
				$this->ajaxReturn($rs);
			}
		}
		
	}

	/**
	 * 用户确认收货
	 * @author 黄俊
	 * date 2016-12-11
	 */
	public function receipt(){
		//权限判断
		if( !in_array($_SESSION['role'], array(1,2,3,4,5)) ){
			$this->error('权限不够！');
		}
		if(IS_AJAX){

			$deliver_id=I('deliver_id',0,'intval');//发货单id

			$deliverGoods=SERVICE('DeliverGoods')->getDeliverGoods($deliver_id);//发货单信息

			#收货条件：必须是本人确认,并且发货单处于已发货状态【LSMT除外】
			if( ($_SESSION['user'] != "LSMT" && $deliverGoods['id_card'] != $_SESSION['id_card']) || $deliverGoods['status'] != 2 ){
				$rs['status']=2;
				$rs['msg']="操作失败：非法操作！！";
				$this->ajaxReturn($rs);
			}

			$data['deliver_id']=$deliver_id;
			$data['status']=3;
			$data['update_time']=date('Y-m-d H:i:s');

			//更新状态
			if(M('deliver_goods')->save($data)){
				//检查发货单对应的订货单，是否满足切换到二审状态的条件
				$isCanSecondAudit=SERVICE('DeliverGoods')->isCanSecondAudit($deliverGoods['order_goods_id']);
				
				if($isCanSecondAudit){//满足条件，更新订货单状态
					
					$orderGoodsData['order_goods_id']=$deliverGoods['order_goods_id'];
					$orderGoodsData['status']=7;//完结 //原来是3
					$orderGoodsData['update_time']=date('Y-m-d H:i:s');
					if(M('order_goods')->save($orderGoodsData)){
						$rs['status']=1;
						// $rs['msg']="成功收货！！----提示：已收到全部货物，订货单进入二审阶段。请留意订货单状态变化！";
						$rs['msg']="成功收货！！----提示：已收到全部货物！";
						$this->ajaxReturn($rs);	
					}else{
						$rs['status']=2;
						$rs['msg']="网络不好，请重试！！";
						$this->ajaxReturn($rs);
					}
				}else{//不满足二审条件
					$rs['status']=1;
					$rs['msg']="成功收货！！";
					$this->ajaxReturn($rs);
				}

			}else{
				$rs['status']=2;
				$rs['msg']="网络不好，请重试！！";
				$this->ajaxReturn($rs);
			}

		}
	}

	/**
	 * 检查发货单货物是否缺货
	 * @author 黄俊
	 * date 2016-12-11
	 */
	public function downloadCheck(){
		//权限判断
		if( !in_array($_SESSION['role'], array(3,4,5)) ){
			$this->error('权限不够！');
		}

		if(IS_AJAX){

			$deliver_id=I('deliver_id',0,'intval');

			//发货单信息
			$deliverGoods=SERVICE('DeliverGoods')->getDeliverGoods($deliver_id);

			//如果发货单已经发货，则不需要检查是否缺货
			if( $deliverGoods['status'] != 1 ){
				$rs['status']=1;
				$rs['msg']="通过！";
				$this->ajaxReturn($rs);
			}

			// 验证是否缺货，反回数组：缺货清单
			$goods=SERVICE('DeliverGoods')->checkDeliver($deliver_id);

			if(empty($goods)){//没有缺货
				$rs['status']=1;
				$rs['msg']="通过！";
				$this->ajaxReturn($rs);
			}else{//缺货
				$rs['status']=2;
				$rs['msg']="提示：仓库货物不足！";
				$rs['list']=$goods;
				$this->ajaxReturn($rs);
			}
		}
	}

	/**
	 * 发货单下载
	 * @author 黄俊
	 * date 2016-12-11
	 */
	public function download(){
		//权限判断
		if( !in_array($_SESSION['role'], array(3,4,5)) ){
			$this->error('权限不够！');
		}

		$deliver_id=I('deliver_id',0,'intval');

		//发货单基本信息
		$deliverGoods=SERVICE('DeliverGoods')->getDeliverGoods($deliver_id);
		//发货单货物list
		$deliverGoodsList=SERVICE('DeliverGoods')->getDeliverGoodsList($deliver_id);

		if( empty($deliverGoodsList) ){//如果没有货物
			$this->error('发货单内，没有货物，不能进行下载！');
		}

		//引入PHPExcel库文件
		import('Class.PHPExcel',APP_PATH);

		//创建对象
		$excel = new PHPExcel();
		$objActSheet = $excel->getActiveSheet();

		//设置宽度 
		$w=17;
		$objActSheet->getColumnDimension('A')->setWidth(20);
		$objActSheet->getColumnDimension('B')->setWidth(25);
		$objActSheet->getColumnDimension('C')->setWidth(20);
		$objActSheet->getColumnDimension('D')->setWidth(10);
		$objActSheet->getColumnDimension('E')->setWidth($w);
		$objActSheet->getColumnDimension('F')->setWidth(12);
		$objActSheet->getColumnDimension('G')->setWidth(12);
		$objActSheet->getColumnDimension('H')->setWidth(12);
		$objActSheet->getColumnDimension('I')->setWidth(20);

		//Excel表格式
		$letter = array('A','B','C','D','E','F','G','H','I');

		# 第1行
		$objActSheet->setCellValue('A1', '浪莎发货单');    
		//合并单元格   
		$objActSheet->mergeCells('A1:I1');    
		//设置样式   
		$objStyleA1 = $objActSheet->getStyle('A1');       
		$objStyleA1->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);   
		$objStyleA1->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);   
		$objFontA1 = $objStyleA1->getFont();
		// $objFontA1->setName('微软雅黑');
		$objFontA1->setSize(16);
		$objFontA1->setBold(true);
		// $objFontA1->getColor()->setRGB('ffffff');
		// $objStyleA1->getFill()->setFillType(PHPExcel_Style_Fill::FILL_SOLID);
		// $objStyleA1->getFill()->getStartColor()->setRGB('0A8E55');
		$objActSheet->getRowDimension('1')->setRowHeight(28);

		# 第2行
		//合并单元格   
		$objActSheet->mergeCells('A2:B2');
		$objActSheet->mergeCells('C2:D2');
		$objActSheet->mergeCells('E2:G2');
		$objActSheet->mergeCells('H2:I2');
		// 设置内容
		$memberInfo=SERVICE('Member')->getMemberByIdCard($deliverGoods['id_card']);//会员信息

		// 会员帐号
		$user='';
		if(empty($memberInfo)){
			$user.='暂无';
		}else{
			$user.=$memberInfo['user'].'('.$memberInfo['name'].')';
		}

		$objActSheet->setCellValue('A2', ' 会员帐号：'.$user);
		$objActSheet->setCellValue('C2', ' 姓名：'.$deliverGoods['name']);
		$objActSheet->setCellValue('E2', ' 联系方式：'.$deliverGoods['tel']);
		$objActSheet->setCellValue('H2', ' 日期：'.$deliverGoods['add_time']);
		//设置样式   
		$objStyleA2 = $objActSheet->getStyle('A2');       
		$objStyleA2->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);   
		$objFontA2 = $objStyleA2->getFont();
		$objStyleC2 = $objActSheet->getStyle('C2');       
		$objStyleC2->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);   
		$objFontC2 = $objStyleC2->getFont();
		$objStyleE2 = $objActSheet->getStyle('E2');       
		$objStyleE2->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);   
		$objFontE2 = $objStyleE2->getFont();
		$objStyleH2 = $objActSheet->getStyle('H2');       
		$objStyleH2->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);   
		$objFontH2 = $objStyleH2->getFont();
		$objActSheet->getRowDimension('2')->setRowHeight(22);

		# 第3行
		//合并单元格   
		$objActSheet->mergeCells('A3:D3');
		$objActSheet->mergeCells('E3:F3');
		$objActSheet->mergeCells('G3:I3');
		// 设置内容
		$objActSheet->setCellValue('A3', ' 收货地址：'.$deliverGoods['deliver_address']);
		$logistics_company=SERVICE('DeliverGoods')->getCompanyName($deliverGoods['logistics_company']);
		$objActSheet->setCellValue('E3', ' 物流公司：'.$logistics_company);
		$objActSheet->setCellValue('G3', ' 运单号：'.$deliverGoods['waybill_number']);
		//设置样式   
		$objStyleA3 = $objActSheet->getStyle('A3');       
		$objStyleA3->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);   
		$objFontA3 = $objStyleA3->getFont();
		$objStyleE3 = $objActSheet->getStyle('E3');       
		$objStyleE3->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);   
		$objFontE3 = $objStyleE3->getFont();
		$objStyleG3 = $objActSheet->getStyle('G3');       
		$objStyleG3->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);   
		$objFontG3 = $objStyleG3->getFont();
		$objActSheet->getRowDimension('3')->setRowHeight(22);

		# 第4行
		//合并单元格   
		$objActSheet->mergeCells('A4:D4');
		$objActSheet->mergeCells('E4:I4');
		// 设置内容
		$objActSheet->setCellValue('A4', ' 代收货人姓名：'.$deliverGoods['deliver_name']);
		$objActSheet->setCellValue('E4', ' 代收货人联系方式：'.$deliverGoods['deliver_tel']);
		//设置样式   
		$objStyleA4 = $objActSheet->getStyle('A4');       
		$objStyleA4->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);   
		$objFontA4 = $objStyleA4->getFont();
		$objStyleE4 = $objActSheet->getStyle('E4');       
		$objStyleE4->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);   
		$objFontE4 = $objStyleE4->getFont();
		$objActSheet->getRowDimension('4')->setRowHeight(22);

		# 第5行
		// 设置内容
		$objActSheet->setCellValue('A5', ' 货号');
		$objActSheet->setCellValue('B5', ' 货品');
		$objActSheet->setCellValue('C5', ' 尺码');
		$objActSheet->setCellValue('D5', ' 颜色');
		$objActSheet->setCellValue('E5', ' 单价（元）');
		$objActSheet->setCellValue('F5', ' 折扣');
		$objActSheet->setCellValue('G5', ' 数量');
		$objActSheet->setCellValue('H5', ' 单位');
		$objActSheet->setCellValue('I5', ' 合计（元）');
		//设置样式
		for ($i=0; $i < 9; $i++) { //$letter
			$objStyle = $objActSheet->getStyle($letter[$i].'5');       
			$objStyle->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);   
			$objFont = $objStyle->getFont();
			$objFont->setBold(true);
		}
		$objActSheet->getRowDimension('5')->setRowHeight(22);

		// 货品列表
		$k=6;
		$numTotal=0;
		$totalMoney=0;
		foreach ($deliverGoodsList as $key => $value) {
			$objActSheet->setCellValue("A".$k,' '.$value['number'].' ');
			$objActSheet->setCellValue("B".$k,' '.$value['name']);
			$objActSheet->setCellValue("C".$k,' '.$value['size'].' ');
			$objActSheet->setCellValue("D".$k,' '.$value['color'].' ');
			$objActSheet->setCellValue("E".$k,' '.floatval($value['price']).' ');
			$objActSheet->setCellValue("F".$k,' '.$value['discount'].'%');
			$numTotal+=$value['num'];//数量统计
			$objActSheet->setCellValue("G".$k,' '.$value['num'].' ');
			$objActSheet->setCellValue("H".$k,' '.$value['num_unit']);

			$money=floatval($value['price']*$value['num']*$value['discount']*0.01);//单项总价
			$objActSheet->setCellValue("I".$k,' '.$money.' ');
			$totalMoney+=$money;//总价统计
			$k++;
		}
		$k++;
		$k++;
		//合并单元格   
		$objActSheet->mergeCells('B'.$k.':E'.$k);

		// 设置内容
		$objActSheet->setCellValue('A'.$k, ' 发货单状态：');
		$status=SERVICE('DeliverGoods')->getStatus($deliverGoods['status']);
		$objActSheet->setCellValue('B'.$k, ' '.$status);
		$objActSheet->setCellValue('F'.$k, ' 总件数:');
		$objActSheet->setCellValue('G'.$k, ' '.$numTotal.'件');
		$objActSheet->setCellValue('H'.$k, ' 总计:');
		$objActSheet->setCellValue('I'.$k, ' '.$totalMoney.'元');

		//设置样式  
		for ($i=0; $i < 9; $i++) { //$letter
			$objStyle = $objActSheet->getStyle($letter[$i].$k);       
			$objStyle->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);   
			$objFont = $objStyle->getFont();
			$objFont->setSize(14);
		} 
		$objActSheet->getRowDimension($k)->setRowHeight(25);
		$k++;

		//合并单元格   
		$objActSheet->mergeCells('B'.$k.':I'.$k);

		// 设置内容
		$objActSheet->setCellValue('A'.$k, ' 备注：');
		$objActSheet->setCellValue('B'.$k, ' '.$deliverGoods['remarks']);

		//设置样式  
		for ($i=0; $i < 9; $i++) { //$letter
			$objStyle = $objActSheet->getStyle($letter[$i].$k);       
			$objStyle->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);   
		}

		$objActSheet->getRowDimension($k)->setRowHeight(22);
		$k++;

		//合并单元格   
		$objActSheet->mergeCells('B'.$k.':I'.$k);
		$objActSheet->setCellValue('A'.$k, ' 打印日期：');
		$objActSheet->setCellValue('B'.$k, ' '.date('Y-m-d H:i:s'));
		//设置样式  
		for ($i=0; $i < 9; $i++) { //$letter
			$objStyle = $objActSheet->getStyle($letter[$i].$k);       
			$objStyle->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);   
		}

		$objActSheet->getRowDimension($k)->setRowHeight(22);
		$k++;

		//创建Excel输入对象
		$filename='发货单--'.$deliverGoods['name'].'--'.date('Y-m-d');
		$write = new PHPExcel_Writer_Excel5($excel);
		header("Pragma: public");
		header("Expires: 0");
		header("Cache-Control:must-revalidate, post-check=0, pre-check=0");
		header("Content-Type:application/force-download");
		header("Content-Type:application/vnd.ms-execl");
		header("Content-Type:application/octet-stream");
		header("Content-Type:application/download");
		header('Content-Disposition:attachment;filename="'.$filename.'.xls"');
		header("Content-Transfer-Encoding:binary");
		$write->save('php://output');
	}

}
?>