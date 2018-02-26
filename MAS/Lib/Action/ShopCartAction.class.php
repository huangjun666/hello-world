<?php
/**
 * 商城购物车模块
 * @author 黄俊
 * date 2017-7-25
 */
class ShopCartAction extends BaseAction{

	/**
	 * 首页---订单列表
	 * @author 黄俊
	 * date 2017-7-4
	 */
	public function index(){
		//权限判断
		if( !in_array($_SESSION['role'], array(2,3,4,5)) ){
			$this->error('权限不够！');
		}
	
		import('ORG.Util.Page');//引入分页类

		$name=I('name','');
		$status=I('status',0,'intval');

		$where='';//范围

		//生活馆馆主,只能查看自己的
		if( in_array($_SESSION['role'], array(2)) ){
			$where=' `user` = "'.$_SESSION['user'].'" ';
		}

		//财务、系统管理员、仓库管理
		if( in_array($_SESSION['role'], array(3,4,5)) && !empty($name) ){
			$where=' `name` LIKE "%'.$name.'%" ';
		}
		// echo $where;die();

		/*分页*/
		$count=SERVICE('Shop')->getShopOrderCount($where,$status);//总数量
		$page=new Page($count,20);
		$limit=$page->firstRow.','.$page->listRows;

		//订单列表
		$shopOrder=SERVICE('Shop')->getList($where,$limit,$status);

		// P($shopOrder);die();

		$this->page=$page->show();//获得页码
		$this->sort=$page->firstRow;//序号
		$this->assign('shopOrder',$shopOrder);
		
		$this->display();
		
	}

	/**
	 * 订单编辑页面
	 * @author 黄俊
	 * date 2017-7-25
	 */
	public function edit(){
		//权限判断
		if( !in_array($_SESSION['role'], array(2,3,4,5)) ){
			$this->error('权限不够！');
		}

		//获取参数 
		$shop_order_id=I('shop_order_id',0,'intval');

		// 订单信息
		$shopOrder=SERVICE('Shop')->getShopOrder($shop_order_id);

		if(empty($shopOrder)){
			$this->error('警告，非法操作！');
		}

		#判断用户是否可以编辑该订单
		#检查用户是否有权限编辑，防止绕过流程，直接访问
		if( !in_array($shopOrder['status'], array(1,2,3)) ){
			$this->redirect('ShopCart/view',array('shop_order_id'=>$shop_order_id));
			// $this->error('非法错误1！');
		}

		//如果生活馆主,只能操作下单中的，自己的例外
		if( in_array($_SESSION['role'], array(2)) ){

			if( $_SESSION['user'] !=$shopOrder['user'] || $shopOrder['status'] != 1 ){
				$this->redirect('ShopCart/view',array('shop_order_id'=>$shop_order_id));
				// $this->error('非法错误！');
			}
		}

		//仓库管理员，只能操作 2、准备发货 3、发货中 的订单，自己的除外
		if( in_array($_SESSION['role'], array(5)) ){

			if( $_SESSION['user'] !=$shopOrder['user'] && !in_array($shopOrder['status'], array(2,3)) ){
				$this->redirect('ShopCart/view',array('shop_order_id'=>$shop_order_id));
				// $this->error('非法错误！');
			}
		}

		//订单基本信息
		$this->shopOrder=$shopOrder;
		//订单商品列表
		$this->orderGoodsList=SERVICE('Shop')->getOrderGoodsList($shop_order_id);
		//物流公司信息
		$this->logisticsCompany=SERVICE('DeliverGoods')->getLogisticsCompany();

		$this->display();
	}

	/**
	 * 订单详情页
	 * @author 黄俊
	 * date 2017-7-4
	 */
	public function view(){
		//权限判断
		if( !in_array($_SESSION['role'], array(2,3,4,5)) ){
			$this->error('权限不够！');
		}

		//获取参数 
		$shop_order_id=I('shop_order_id',0,'intval');

		// 订单信息
		$shopOrder=SERVICE('Shop')->getShopOrder($shop_order_id);

		if(empty($shopOrder)){
			$this->error('警告，非法操作！');
		}

		#判断用户是否可以访问该订单
		#检查用户是否有权限访问，防止绕过流程，直接访问

		//如果生活馆主,只能操作下单中的，自己的例外
		if( in_array($_SESSION['role'], array(2)) ){

			if( $_SESSION['user'] !=$shopOrder['user']){
				$this->error('非法错误！');
			}
		}

		//订单基本信息
		$this->shopOrder=$shopOrder;
		//订单商品列表
		$this->orderGoodsList=SERVICE('Shop')->getOrderGoodsList($shop_order_id);
		//物流公司信息
		$this->logisticsCompany=SERVICE('DeliverGoods')->getLogisticsCompany();

		$this->display();
	}

	/**
	 * 保存订单
	 * @author 黄俊
	 * date 2017-7-28
	 */
	public function save(){
		//权限判断
		if( !in_array($_SESSION['role'], array(2,3,4,5)) ){
			$this->error('权限不够！');
		}

		if(IS_AJAX){

			$shop_order_id=I('shop_order_id',0,'intval');//订单id
			$save=I('save',0,'intval');// 操作方式：0、保存 1、保存并提交 2、保存并发货
 
			// 判断参数合法性
			if($shop_order_id==0){
				$rs['status']=2;
				$rs['msg']="非法操作！";
				$this->ajaxReturn($rs);
			}

			// 订单信息
			$shopOrder=SERVICE('Shop')->getShopOrder($shop_order_id);

			//检查订单状态
			if( !in_array($shopOrder['status'], array(1,2,3)) ){
				$rs['status']=2;
				$rs['msg']="非法操作！";
				$this->ajaxReturn($rs);
			}

			//如果是馆主： 检查订单状态
			if( $_SESSION['role']==2 && !in_array($shopOrder['status'], array(1)) ){
				$rs['status']=2;
				$rs['msg']="订单已经提交！";
				$this->ajaxReturn($rs);
			}

			//要保存的数据
			$data['shop_order_id']=$shop_order_id;
			$data['logistics_company']=I('logistics_company','');
			$data['waybill_number']=I('waybill_number','');
			$data['remarks']=I('remarks','');
			$data['update_time']=date('Y-m-d H:i:s');

			// 判断是否保存并提交
			if($save==1){

				$data['status']=2;//status:1、下单中 2、准备发货

				// 检查订单状态
				if(in_array($shopOrder['status'], array(2))){
					$rs['status']=2;
					$rs['msg']="不可以重复提交！";
					$this->ajaxReturn($rs);
				}

				// 验证收货地址
				if( $shopOrder['shop_order_address_id'] == 0 ){
					$rs['status']=2;
					$rs['msg']="请提供收货地址！";
					$this->ajaxReturn($rs);
				}

				// 验证订单额度
				if( $shopOrder['money'] < 1000 ){
					$rs['status']=2;
					$rs['msg']="订单最低额度，1000元起步！";
					$this->ajaxReturn($rs);
				}

				//验证订单中是否有商品，空订单不可以提交
				$OrderGoodsList=SERVICE('Shop')->getOrderGoodsList($shop_order_id);
				if( empty($OrderGoodsList) ){
					$rs['status']=2;
					$rs['msg']="不可以提交空订单！";
					$this->ajaxReturn($rs);
				}

			}

			// 判断是否保存并发货
			if($save==2){

				$data['status']=4;//status:1、下单中 2、准备发货 3、发货中 4、已发货

				// 检查订单状态
				if(in_array($shopOrder['status'], array(4))){
					$rs['status']=2;
					$rs['msg']="不可以重复提交！";
					$this->ajaxReturn($rs);
				}

				// 验证收货地址
				// if( $shopOrder['shop_order_address_id'] == 0 ){
				// 	$rs['status']=2;
				// 	$rs['msg']="请提供收货地址！";
				// 	$this->ajaxReturn($rs);
				// }

				//验证订单中是否有商品，空订单不可以提交
				$OrderGoodsList=SERVICE('Shop')->getOrderGoodsList($shop_order_id);
				if( empty($OrderGoodsList) ){
					$rs['status']=2;
					$rs['msg']="不可以提交空订单！";
					$this->ajaxReturn($rs);
				}

				//检查物流公司和运单号
				if( !SERVICE('DeliverGoods')->isCanDeliver($data['logistics_company'],$data['waybill_number']) ){
					$rs['status']=2;
					$rs['msg']="发货失败，请输入有效的物流公司和运单号！！";
					$this->ajaxReturn($rs);
				}

				// 记录仓库发货日志
				SERVICE('Shop')->writeDeliverGoodsLog($shop_order_id);

			}

			//保存
			if(M('shop_order')->save($data)){

				// 判断是否保存并提交,发短信通知用户，真实的下单金额
				$true_money_msg='';
				if($save==1){

					$dx=duanxin2($shopOrder['tel'],$shopOrder['name'],$shopOrder['true_money']);
					#不能通知，以免经销商看到
					//如果发送短信失败，直接输出真实订单金额
					// if( !$dx->result->success ){
					// 	$true_money_msg.=',短信发送失败，您此次下单的真实金额为'.$money['true_money'];
					// }
				}

				$rs['status']=1;
				$rs['msg']="保存成功！";
				if($save){
					$rs['msg']="提交成功！".$true_money_msg;
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
	 * 订单完结
	 * @author 黄俊
	 * date 2017-7-28
	 */
	public function end(){
		//权限判断
		if( !in_array($_SESSION['role'], array(3,4,5)) ){
			$this->error('权限不够！');
		}

		if(IS_AJAX){
			
			$shop_order_id=I('shop_order_id',0,'intval');//订单id

			if( empty($shop_order_id) ){
				$rs['status']=2;
				$rs['msg']="非法操作！！";
				$this->ajaxReturn($rs);
			}

			$data['status']=5;//完结
			$data['update_time']=date('Y-m-d H:i:s');//更新时间

			if( M('shop_order')->where(array('shop_order_id'=>$shop_order_id))->save($data) ){
				$rs['status']=1;
				$rs['msg']="成功！！";
				$this->ajaxReturn($rs);
			}else{
				$rs['status']=2;
				$rs['msg']="网络不好，请重试！！";
				$this->ajaxReturn($rs);
			}
		}
	}

	/**
	 * 地址管理--列表
	 * @author 黄俊
	 * date 2017-7-28
	 */
	public function addressList(){
		//权限判断
		if( !in_array($_SESSION['role'], array(2,3,4,5)) ){
			$this->error('权限不够！');
		}

		$member_id=$_SESSION['uid'];//会员id

		import('ORG.Util.Page');//引入分页类

		/*分页*/
		$count=M('shop_order_address')->where(array('member_id'=>$member_id))->count();//总数量
		$page=new Page($count,20);
		$limit=$page->firstRow.','.$page->listRows;

		//订单列表
		$addressList=M('shop_order_address')->where(array('member_id'=>$member_id))->order('status DESC')->limit($limit)->select();

		$this->page=$page->show();//获得页码
		$this->sort=$page->firstRow;//序号
		$this->assign('addressList',$addressList);

		$this->display();
	}

	/**
	 * 地址管理---添加地址
	 * @author 黄俊
	 * date 2017-7-28
	 */
	public function addressAdd(){
		//权限判断
		if( !in_array($_SESSION['role'], array(2,3,4,5)) ){
			$this->error('权限不够！');
		}

		if(IS_POST){

			$data['member_id']=$_SESSION['uid'];//会员id

			//验证收货人姓名
			$data['name']=I('name','');
			if(empty($data['name'])){
				$this->assign('error','收货人姓名必填');
				$this->display();
				exit();
			}

			//验证收货人联系方式
			$data['tel']=I('tel','');
			if( !is_tel($data['tel']) ){
				$this->assign('error','手机号不正确');
				$this->display();
				exit();
			}

			//验证收货人地址
			$data['address']=I('address','');
			if(empty($data['address'])){
				$this->assign('error','收货人地址必填');
				$this->display();
				exit();
			}

			$data['update_time']=date('Y-m-d H:i:s');

			//进行添加操作
			if( M('shop_order_address')->add($data) ){
				//调转至地址列表
				$this->success('添加成功！',U('addressList'));
			}else{
				$this->assign('error','非法错误，请重试');
				$this->display();
			}

		}else{

			$this->display();
		}
		
	}

	/**
	 * 地址管理--编辑地址
	 * @author 黄俊
	 * date 2017-7-28
	 */
	public function addressEdit(){
		//权限判断
		if( !in_array($_SESSION['role'], array(2,3,4,5)) ){
			$this->error('权限不够！');
		}

		if(IS_POST){

			$data['shop_order_address_id']=I('shop_order_address_id',0,'intval');

			//验证收货人姓名
			$data['name']=I('name','');
			if(empty($data['name'])){
				$this->assign('error','收货人姓名必填');
				$this->display();
				exit();
			}

			//验证收货人联系方式
			$data['tel']=I('tel','');
			if( !is_tel($data['tel']) ){
				$this->assign('error','手机号不正确');
				$this->display();
				exit();
			}

			//验证收货人地址
			$data['address']=I('address','');
			if(empty($data['address'])){
				$this->assign('error','收货人地址必填');
				$this->display();
				exit();
			}

			$data['update_time']=date('Y-m-d H:i:s');

			//进行添加操作
			if( M('shop_order_address')->save($data) ){
				//调转至地址列表
				$this->success('添加成功！',U('addressList'));
			}else{
				$this->assign('error','非法错误，请重试');
				$this->display();
			}

		}else{

			//id
			$shop_order_address_id=I('shop_order_address_id',0,'intval');

			//查询
			$this->shop_order_address=M('shop_order_address')->where(array('shop_order_address_id'=>$shop_order_address_id))->find();

			$this->display();
		}

	}

	/**
	 * 地址管理--设为默认地址
	 * @author 黄俊
	 * date 2017-7-29
	 */
	public function addressDefault(){
		//权限判断
		if( !in_array($_SESSION['role'], array(2,3,4,5)) ){
			$this->error('权限不够！');
		}

		$shop_order_address_id=I('shop_order_address_id',0,'intval');//地址id

		/*查询出地址信息*/
		$shop_order_address=M('shop_order_address')->where(array('shop_order_address_id'=>$shop_order_address_id))->find();
		// P($shop_order_address);die();
		//地址信息为空
		if( empty($shop_order_address) ){
			$this->error('非法错误！');
		}

		/*修改订单状态*/
		#1、修改该会员所有的status=2的地址变为status=1
		$data1['status']=1;
		$data1['update_time']=date('Y-m-d H:i:s');

		$h1=M('shop_order_address')->where(array('member_id'=>$shop_order_address['member_id'],'status'=>2))->save($data1);

		// echo M()->getLastSql();

		#2、修改当前地址status=2
		$data2['status']=2;
		$data2['update_time']=date('Y-m-d H:i:s');

		$h2=M('shop_order_address')->where(array('shop_order_address_id'=>$shop_order_address['shop_order_address_id']))->save($data2);
		// echo M()->getLastSql();

		/*修改该会员下单中的订单地址id*/
		$data3['shop_order_address_id']=$shop_order_address['shop_order_address_id'];
		$data3['update_time']=date('Y-m-d H:i:s');

		$h3=M('shop_order')->where(array('member_id'=>$shop_order_address['member_id'],'status'=>1))->save($data3);
		// echo M()->getLastSql();

		// die();
		//调转至地址列表
		// if($h1 && $h2 && $h3){
			$this->success('设置成功！',U('addressList'));
		// }else{
		// 	$this->success('设置失败，请重试！',U('addressList'));
		// }
		

	}

	/**
	 * 地址管理--返回订单
	 * @author 黄俊
	 * date 2017-7-29
	 */
	public function backOrder(){
		//权限判断
		if( !in_array($_SESSION['role'], array(2,3,4,5)) ){
			$this->error('权限不够！');
		}

		$member_id=$_SESSION['uid'];//会员id

		//找到当前活到的 订单：下单中 status=1
		$shop_order_id=M('shop_order')->where(array('member_id'=>$member_id,'status'=>1))->getField('shop_order_id');

		$this->redirect('ShopCart/edit',array('shop_order_id'=>$shop_order_id));
	}

	/**
	 * 订单下载
	 * @author 黄俊
	 * date 2017-7-29
	 */
	public function download(){
		//权限判断
		if( !in_array($_SESSION['role'], array(3,4,5)) ){
			$this->error('权限不够！');
		}

		$shop_order_id=I('shop_order_id',0,'intval');

		//订单基本信息
		$shopOrder=SERVICE('Shop')->getShopOrder($shop_order_id);
		//订单商品list
		$orderGoodsList=SERVICE('Shop')->getOrderGoodsList($shop_order_id);
		//物流公司信息
		$logisticsCompany=SERVICE('DeliverGoods')->getLogisticsCompany();

		if( empty($orderGoodsList) ){//如果没有商品
			$this->error('订单内，没有商品，不能进行下载！');
		}

		/*订单状态为2以上，统计下载数次*/
		if( $shopOrder['status']>=2 ){

			#数据更新
			$data['shop_order_id']=$shopOrder['shop_order_id'];

			//如果订单状态为2(准备发货)，并且从未被下载过，在统计时，同时改变订单状态
			if( $shopOrder['download_num'] ==0 && $shopOrder['status']==2 ){
				$data['status']=3;//发货中
			}

			$data['download_num']=$shopOrder['download_num']+1;

			if( !M('shop_order')->save($data) ){
				echo "下载出错,请重试";die();
			}

		}
		

		//引入PHPExcel库文件
		import('Class.PHPExcel',APP_PATH);

		//创建对象
		$excel = new PHPExcel();
		$objActSheet = $excel->getActiveSheet();

		//设置宽度 
		$w=17;
		$objActSheet->getColumnDimension('A')->setWidth(10);
		$objActSheet->getColumnDimension('B')->setWidth(25);
		$objActSheet->getColumnDimension('C')->setWidth(20);
		$objActSheet->getColumnDimension('D')->setWidth(10);
		$objActSheet->getColumnDimension('E')->setWidth(10);
		$objActSheet->getColumnDimension('F')->setWidth(12);
		$objActSheet->getColumnDimension('G')->setWidth(12);
		$objActSheet->getColumnDimension('H')->setWidth(8);
		$objActSheet->getColumnDimension('I')->setWidth(8);
		$objActSheet->getColumnDimension('J')->setWidth(18);

		//Excel表格式
		$letter = array('A','B','C','D','E','F','G','H','I','J');

		# 第1行
		$objActSheet->setCellValue('A1', '重销订单');    
		//合并单元格   
		$objActSheet->mergeCells('A1:J1');    
		//设置样式   
		$objStyleA1 = $objActSheet->getStyle('A1');       
		$objStyleA1->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);   
		$objStyleA1->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);   
		$objFontA1 = $objStyleA1->getFont();
		// $objFontA1->setName('微软雅黑');
		// $objFontA1->setSize(16);
		$objFontA1->setBold(true);
		// $objFontA1->getColor()->setRGB('ffffff');
		// $objStyleA1->getFill()->setFillType(PHPExcel_Style_Fill::FILL_SOLID);
		// $objStyleA1->getFill()->getStartColor()->setRGB('0A8E55');
		$objActSheet->getRowDimension('1')->setRowHeight(28);

		# 第2行
		//合并单元格   
		$objActSheet->mergeCells('A2:C2');
		$objActSheet->mergeCells('D2:E2');
		$objActSheet->mergeCells('F2:H2');
		$objActSheet->mergeCells('I2:J2');

		$objActSheet->setCellValue('A2', ' 会员帐号：'.$shopOrder['user']);
		$objActSheet->setCellValue('D2', ' 姓名：'.$shopOrder['name']);
		$objActSheet->setCellValue('F2', ' 联系方式：'.$shopOrder['tel']);
		$objActSheet->setCellValue('I2', ' 日期：'.$shopOrder['add_time']);
		//设置样式   
		$objStyleA2 = $objActSheet->getStyle('A2');       
		$objStyleA2->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);   
		$objFontA2 = $objStyleA2->getFont();
		$objStyleC2 = $objActSheet->getStyle('D2');       
		$objStyleC2->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);   
		$objFontC2 = $objStyleC2->getFont();
		$objStyleE2 = $objActSheet->getStyle('F2');       
		$objStyleE2->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);   
		$objFontE2 = $objStyleE2->getFont();
		$objStyleH2 = $objActSheet->getStyle('I2');       
		$objStyleH2->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);   
		$objFontH2 = $objStyleH2->getFont();
		$objActSheet->getRowDimension('2')->setRowHeight(22);

		# 第3行
		//合并单元格   
		$objActSheet->mergeCells('A3:E3');
		$objActSheet->mergeCells('F3:H3');
		$objActSheet->mergeCells('I3:J3');
		// 设置内容
		$objActSheet->setCellValue('A3', ' 收货地址：'.$shopOrder['agent_address']);
		//物流公司
		$logistics_company=SERVICE('DeliverGoods')->getCompanyName($shopOrder['logistics_company']);
		$objActSheet->setCellValue('F3', ' 物流公司：'.$logistics_company);
		$objActSheet->setCellValue('I3', ' 运单号：'.$shopOrder['waybill_number']);
		// $objActSheet->setCellValue('J3', ' 提交限制：'.$reward_config['order_discount'].'元');
		//设置样式   
		$objStyleA3 = $objActSheet->getStyle('A3');       
		$objStyleA3->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);   
		$objFontA3 = $objStyleA3->getFont();
		$objStyleE3 = $objActSheet->getStyle('F3');       
		$objStyleE3->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);   
		$objFontE3 = $objStyleE3->getFont();
		$objStyleG3 = $objActSheet->getStyle('I3');       
		$objStyleG3->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);   
		$objFontG3 = $objStyleG3->getFont();
		$objActSheet->getRowDimension('3')->setRowHeight(22);

		# 第4行
		//合并单元格   
		$objActSheet->mergeCells('A4:E4');
		$objActSheet->mergeCells('F4:H4');
		$objActSheet->mergeCells('I4:J4');
		// 设置内容
		$objActSheet->setCellValue('A4', ' 收货人姓名：'.$shopOrder['agent_name']);
		$objActSheet->setCellValue('F4', ' 收货人联系方式：'.$shopOrder['agent_tel']);
		$objActSheet->setCellValue('I4', ' 订单金额：'.$shopOrder['money']);
		//设置样式   
		$objStyleA4 = $objActSheet->getStyle('A4');       
		$objStyleA4->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);   
		$objFontA4 = $objStyleA4->getFont();
		$objStyleE4 = $objActSheet->getStyle('F4');       
		$objStyleE4->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);   
		$objFontE4 = $objStyleE4->getFont();
		$objStyleG4 = $objActSheet->getStyle('I4');       
		$objStyleG4->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);   
		$objFontG4 = $objStyleG4->getFont();
		$objActSheet->getRowDimension('4')->setRowHeight(22);

		# 第5行
		// 合并单元格
		$objActSheet->mergeCells('B5:C5');
		// 设置内容
		$objActSheet->setCellValue('A5', ' 货号');
		$objActSheet->setCellValue('B5', ' 货品');
		$objActSheet->setCellValue('D5', ' 尺码');
		$objActSheet->setCellValue('E5', ' 颜色');
		$objActSheet->setCellValue('F5', ' 单价(元)');
		$objActSheet->setCellValue('G5', ' 折扣');
		$objActSheet->setCellValue('H5', ' 数量');
		$objActSheet->setCellValue('I5', ' 单位');
		$objActSheet->setCellValue('J5', ' 合计（元）');
		//设置样式
		for ($i=0; $i < 10; $i++) { //$letter
			$objStyle = $objActSheet->getStyle($letter[$i].'5');       
			$objStyle->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);   
			$objFont = $objStyle->getFont();
			$objFont->setBold(true);
		}
		$objActSheet->getRowDimension('5')->setRowHeight(22);

		// 货品列表
		$k=6;
		$numTotal=0;//商品总件数
		$totalMoney=0;//总金额
		$deliverMoney=0;//已发货总金额
		foreach ($orderGoodsList as $key => $value) {
			// 合并单元格
			$objActSheet->mergeCells('B'.$k.':C'.$k);
			// 设置内容
			$objActSheet->setCellValue("A".$k,' '.$value['number'].' ');
			$objActSheet->setCellValue("B".$k,' '.$value['name']);
			$objActSheet->setCellValue("D".$k,' '.$value['size'].' ');
			$objActSheet->setCellValue("E".$k,' '.$value['color'].' ');
			$objActSheet->setCellValue("F".$k,' '.floatval($value['price']).' ');
			$objActSheet->setCellValue("G".$k,' '.$value['discount'].'%');
			$numTotal+=$value['num'];//数量统计
			$objActSheet->setCellValue("H".$k,' '.$value['num'].' ');
			$objActSheet->setCellValue("I".$k,' '.$value['num_unit']);

			$money=floatval($value['price']*($value['num'])*$value['discount']*0.01);//单项总价
			$objActSheet->setCellValue("J".$k,' '.$money.' ');
			$totalMoney+=$money;//总价统计
			$k++;
		}
		$k++;
		$k++;
		//合并单元格   
		$objActSheet->mergeCells('B'.$k.':F'.$k);

		// 设置内容
		$objActSheet->setCellValue('A'.$k, ' 状态：');
		$status=SERVICE('Shop')->getStatus($shopOrder['status']);
		$objActSheet->setCellValue('B'.$k, ' '.$status);
		$objActSheet->setCellValue('G'.$k, ' 总件数:');
		$objActSheet->setCellValue('H'.$k, ' '.$numTotal.'件');
		$objActSheet->setCellValue('I'.$k, ' 总计:');
		$objActSheet->setCellValue('J'.$k, ' '.$totalMoney.'元');

		//设置样式  
		for ($i=0; $i < 10; $i++) { //$letter
			$objStyle = $objActSheet->getStyle($letter[$i].$k);       
			$objStyle->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);   
			$objFont = $objStyle->getFont();
			// $objFont->setSize(14);
		} 
		$objActSheet->getRowDimension($k)->setRowHeight(25);
		$k++;

		//合并单元格   
		$objActSheet->mergeCells('B'.$k.':J'.$k);

		// 设置内容
		$objActSheet->setCellValue('A'.$k, ' 备注：');
		$objActSheet->setCellValue('B'.$k, ' '.$shopOrder['remarks']);

		//设置样式  
		for ($i=0; $i < 10; $i++) { //$letter
			$objStyle = $objActSheet->getStyle($letter[$i].$k);       
			$objStyle->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);   
		}

		$objActSheet->getRowDimension($k)->setRowHeight(22);
		$k++;

		// # 温馨提示
		// //合并单元格   
		// $objActSheet->mergeCells('B'.$k.':K'.$k);
		// // 设置内容
		// $objActSheet->setCellValue('A'.$k, ' 温馨提示：');
		// $orderGoods_msg=SERVICE('Block')->getBlock('orderGoods_msg');//取出温馨提示的内容
		// $objActSheet->setCellValue('B'.$k, ' '.$orderGoods_msg);
		// //设置样式  
		// for ($i=0; $i < 11; $i++) { //$letter
		// 	$objStyle = $objActSheet->getStyle($letter[$i].$k);       
		// 	$objStyle->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);   
		// }

		// $k++;


		$objActSheet->getRowDimension($k)->setRowHeight(22);

		//合并单元格   
		$objActSheet->mergeCells('B'.$k.':I'.$k);
		$objActSheet->setCellValue('A'.$k, ' 打印日期：');
		$objActSheet->setCellValue('B'.$k, ' '.date('Y-m-d H:i:s'));
		//设置样式  
		for ($i=0; $i < 10; $i++) { //$letter
			$objStyle = $objActSheet->getStyle($letter[$i].$k);       
			$objStyle->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);   
		}

		$objActSheet->getRowDimension($k)->setRowHeight(22);
		$k++;

		//创建Excel输入对象
		$filename='重销商城订单--'.$shopOrder['name'].'--'.date('Y-m-d');
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

	/**
	 * 查看订单物流信息
	 * @author 黄俊
	 * date 2017-7-29
	 */
	public function showLogistics(){

		//权限判断
		if( !in_array($_SESSION['role'], array(1,2,3,4,5)) ){
			$this->error('权限不够！');
		}

		if(IS_AJAX){
			$shop_order_id=I('shop_order_id',0,'intval');

			// 订单信息
			$shopOrder=SERVICE('Shop')->getShopOrder($shop_order_id);

			//获得物流信息	
			$logisticsInfo=SERVICE('Api')->getLogisticsInfo($shopOrder['logistics_company'],$shopOrder['waybill_number']);

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
	 * date 2017-7-29
	 */
	public function receipt(){
		//权限判断
		if( !in_array($_SESSION['role'], array(1,2,3,4,5)) ){
			$this->error('权限不够！');
		}
		if(IS_AJAX){

			$shop_order_id=I('shop_order_id',0,'intval');//订单id

			// 订单信息
			$shopOrder=SERVICE('Shop')->getShopOrder($shop_order_id);

			#收货条件：必须是本人确认,并且订单处于 4、已发货 状态【LSMT除外】
			if( ($_SESSION['user'] != "LSMT" && $shopOrder['member_id'] != $_SESSION['uid']) || $shopOrder['status'] != 4 ){
				$rs['status']=2;
				$rs['msg']="操作失败：非法操作！！";
				$this->ajaxReturn($rs);
			}

			$data['shop_order_id']=$shop_order_id;
			$data['status']=5;
			$data['update_time']=date('Y-m-d H:i:s');

			//更新状态
			if(M('shop_order')->save($data)){
				
				$rs['status']=1;
				$rs['msg']="成功收货！！";
				$this->ajaxReturn($rs);

			}else{
				$rs['status']=2;
				$rs['msg']="网络不好，请重试！！";
				$this->ajaxReturn($rs);
			}

		}
	}


}

?>