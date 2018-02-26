<?php
/**
 * 订货管理
 * @author 黄俊
 * date 2016-11-31
 */
class OrderGoodsAction extends BaseAction{

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

			//订货单列表
			$orderGoods=SERVICE('OrderGoods')->getList($where);
			$this->sort=0;//序号
			$this->assign('orderGoods',$orderGoods);

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
			if( in_array($_SESSION['role'], array(2)) && !empty($name) ){
				$where=' `name` LIKE "%'.$name.'%" AND (recommend_user="'.$_SESSION['user'].'"  OR place_user="'.$_SESSION['user'].'" or handle="'.$_SESSION['user'].'" or id_card="'.$_SESSION['id_card'].'")';
			}

			//财务、系统管理员、仓库管理
			if( in_array($_SESSION['role'], array(3,4,5)) && !empty($name) ){
				$where=' `name` LIKE "%'.$name.'%" ';
			}
			// echo $where;die();

			/*分页*/
			$count=SERVICE('OrderGoods')->getOrderGoodsCount($where,$status);//总数量
			$page=new Page($count,20);
			$limit=$page->firstRow.','.$page->listRows;

			//订货单列表
			$orderGoods=SERVICE('OrderGoods')->getList($where,$limit,$status);

			//为每一列，加上会员账号信息
			foreach ($orderGoods as $key => $value) {
				$memberInfo=SERVICE('Member')->getMemberByIdCard($value['id_card']);
				$orderGoods[$key]['user']=$memberInfo['user'];
			}
			// P($orderGoods);die();

			$this->page=$page->show();//获得页码
			$this->sort=$page->firstRow;//序号
			$this->assign('orderGoods',$orderGoods);
		}
		
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

		//获取参数 
		$order_id=I('order_id',0,'intval');		

		// 订单信息
		$order=M('order')->where(array('order_id'=>$order_id))->find();
		
		//判断用户是否可以访问该订单的订货单
		//如果不是财务或系统管理员，检查用户是否有权限访问，防止绕过流程，直接访问
		if( !in_array($_SESSION['role'], array(3,4,5)) ){

			//筛选条件
			$rule=array();
			$rule[]=$order['recommend_user'];
			$rule[]=$order['place_user'];
			$rule[]=$order['handle'];
			$rule[]=$order['id_card'];

			//判断
			if( !in_array($_SESSION['user'], $rule) && !in_array($_SESSION['id_card'], $rule) ){
				$this->error('警告！！非法操作！');
			}

		}

		// 判断订货单是否存在
		$orderGoods=M('order_goods')->where(array('order_id'=>$order_id))->find();
		if(empty($orderGoods)){
			$this->error('订货单不存在！！');
		}

		//订货单基本信息
		$orderGoods=SERVICE('OrderGoods')->getOrderGoods($orderGoods['order_goods_id']);
		//会员信息
		$this->memberInfo=SERVICE('Member')->getMemberByIdCard($orderGoods['id_card']);
		$this->orderGoods=$orderGoods;
		//订货单货品列表
		$this->orderGoodsList=SERVICE('OrderGoods')->getOrderGoodsList($orderGoods['order_goods_id']);
		//系统配置
		$this->reward_config=D('Web')->reward_config();
		$this->display();
		
	}

	/**
	 * 编辑订货单
	 * @author 黄俊
	 * date 2016-12-3
	 */
	public function edit(){
		//权限判断
		if( !in_array($_SESSION['role'], array(1,2,3,4,5)) ){
			$this->error('权限不够！');
		}

		//获取参数 
		$order_id=I('order_id',0,'intval');

		//如果是普通经销商
		// if( in_array($_SESSION['role'], array(1)) ){
		// 	$this->redirect('view', array('order_id' => $order_id));
		// }

		// 订单信息
		$order=M('order')->where(array('order_id'=>$order_id))->find();

		if(empty($order)){
			$this->error('警告，非法操作！');
		}

		# 1、判断用户是否可以访问该订单的订货单
		//如果不是财务或系统管理员，检查用户是否有权限访问，防止绕过流程，直接访问
		if( !in_array($_SESSION['role'], array(3,4,5)) ){

			if( $_SESSION['user'] !=$order['recommend_user'] && $_SESSION['user'] !=$order['place_user'] && $_SESSION['user'] !=$order['handle'] && $_SESSION['id_card'] !=$order['id_card'] ){
				$this->error('非法错误！');
			}
		}

		# 2、判断订货单是否存在
		$orderGoods=M('order_goods')->where(array('order_id'=>$order_id))->find();

		# 3、不存在、则根据订单信息生成订货单
		if(empty($orderGoods)){
			// 检查订单是否通过审核，通过审核，则不创建订货单
			if( in_array($order['status'], array(2,3)) ){
				$this->error('没有订单货可以查看', U('Order/view',array('order_id'=>$order['order_id'])));
				// exit();
			}
			//根据订单信息，生成订货单
			$order_goods_id=SERVICE('OrderGoods')->createOrderGoods($order);
		}else{
			$order_goods_id=$orderGoods['order_goods_id'];
		}

		//订货单基本信息
		$orderGoods=SERVICE('OrderGoods')->getOrderGoods($order_goods_id);

		#根据不同角色，判断订单，跳转至详情页
		// 馆主
		if(in_array($_SESSION['role'], array(1,2)) && in_array($orderGoods['status'], array(2,3,4,5,6,7)) ){
			$this->redirect('view', array('order_id' => $order_id));
		}

		// 非馆主：财务、系统管理员、仓库管理员
		if(in_array($_SESSION['role'], array(3,4,5)) && in_array($orderGoods['status'], array(7)) ){
			$this->redirect('view', array('order_id' => $order_id));
		}

		
		//会员信息
		$this->memberInfo=SERVICE('Member')->getMemberByIdCard($orderGoods['id_card']);
		//订货单基本信息
		$this->orderGoods=$orderGoods;
		//订货单货品列表
		$this->orderGoodsList=SERVICE('OrderGoods')->getOrderGoodsList($order_goods_id);
		//系统配置
		$this->reward_config=D('Web')->reward_config();
		// P($orderGoodsList);die();
		$this->display();
		
	}

	/**
	 * 保存订货单
	 * @author 黄俊
	 * date 2016-12-3
	 */
	public function save(){
		//权限判断
		if( !in_array($_SESSION['role'], array(1,2,3,4,5)) ){
			$this->error('权限不够！');
		}

		if(IS_AJAX){

			$order_goods_id=I('order_goods_id',0,'intval');//订货单id
			$save=I('save',0,'intval');// 判断是否保存并提交

			// 判断参数合法性
			if($order_goods_id==0){
				$rs['status']=2;
				$rs['msg']="非法操作！";
				$this->ajaxReturn($rs);
			}

			// 订货单信息
			$order_goods=M('order_goods')->where(array('order_goods_id'=>$order_goods_id))->find();

			//如果是馆主： 检查订货单状态
			if( !in_array($_SESSION['role'], array(3,4,5)) && !in_array($order_goods['status'], array(1))){
				$rs['status']=2;
				$rs['msg']="订货单已经处于审核状态！";
				$this->ajaxReturn($rs);
			}

			/*如果是馆主，则需要判断该订货单对应的订单，馆主是否由权限编辑*/
			//订货单对应的订单信息
			$orderInfo=M('order')->where(array('order_id'=>$order_goods['order_id']))->find();

			if( !in_array($_SESSION['role'], array(3,4,5)) ){

				// 判断handle或id_card是否匹配
				// echo $_SESSION['id_card'].'<br>';
				// echo $_SESSION['user'].'<br>';
				// P($orderInfo);die();
				if( $orderInfo['handle'] != $_SESSION['user'] && $orderInfo['id_card'] != $_SESSION['id_card'] ){
					$rs['status']=2;
					$rs['msg']="非法操作，不可以编辑！";
					$this->ajaxReturn($rs);
				}

				// 判断订货单是否提交，提交过的订货单，馆主不可以保存操作
				if( in_array($order_goods['status'], array(2,3,4,5,6)) ){
					$rs['status']=2;
					$rs['msg']="非法操作，不可以编辑！";
					$this->ajaxReturn($rs);
				}
			}

			//要保存的数据
			$data['order_goods_id']=$order_goods_id;
			$data['goods_address']=I('goods_address','');
			$data['goods_name']=I('goods_name','');
			$data['goods_tel']=I('goods_tel','');
			$data['remarks']=I('remarks','');
			$data['update_time']=date('Y-m-d H:i:s');

			// 判断是否保存并提交
			if($save){

				$data['status']=2;//status:1、待提交 2、待二审

				// 订单未过一审，订货单不可以提交
				if( in_array($orderInfo['status'], array(1,4))){
					$rs['status']=2;
					$rs['msg']="订单未过一审，订货单不可以提交!";
					$this->ajaxReturn($rs);
				}

				// 检查订货单状态
				if(in_array($order_goods['status'], array(2))){
					$rs['status']=2;
					$rs['msg']="不可以重复提交！";
					$this->ajaxReturn($rs);
				}

				//检查订货单额度是否满足提交条件
				if( !SERVICE('OrderGoods')->isCanSubmit($order_goods_id) ){
					$rs['status']=2;
					$rs['msg']="提交失败，订货单总额不够！！";
					$this->ajaxReturn($rs);
				}

				// 验证收货地址
				if(empty($data['goods_address'])){
					$rs['status']=2;
					$rs['msg']="提交失败，收货地址不能为空！！";
					$this->ajaxReturn($rs);
				}

				// 验证收货人姓名
				if(empty($data['goods_name'])){
					$rs['status']=2;
					$rs['msg']="提交失败，收货人姓名不能为空！！";
					$this->ajaxReturn($rs);
				}

				// 验证联系方式
				if( !is_tel($data['goods_tel']) && !is_fixedTel($data['goods_tel']) ){
					$rs['status']=2;
					$rs['msg']="提交失败，请输入正确的电话号码！！";
					$this->ajaxReturn($rs);
				}

			}

			if(M('order_goods')->save($data)){
				$rs['status']=1;
				$rs['msg']="保存成功！";
				if($save){
					$rs['msg']="提交成功！";
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
	 * 订货单二审
	 * @author 黄俊
	 * date 2016-12-14
	 * 备注：后期需求调整，该方法不在使用
	 */
	public function secondAudit(){
		//权限判断
		if( !in_array($_SESSION['role'], array(3,4)) ){
			$this->error('权限不够！');
		}

		$order_id=I('order_id',0,'intval');//订单id
		$audit=I('audit',0,'intval');//审核操作：1、通过 2、不通过

		//验证audit
		if( !in_array($audit, array(1,2)) ){
			$this->error('非法操作：audit！');
		}

		//获得订货单信息
		$orderGoods=SERVICE('OrderGoods')->getOrderGoods($order_id,true);

		#验证订货单与订单的状态
		// 验证订单状态
		if( $orderGoods['orderStatus'] != 5 ){
			$this->error('【订单】处于待二审状态，才可以进行二审！');
		}
		// 验证订货单状态
		// if( $orderGoods['status'] != 3 ){
		// 	$this->error('【订货单】处于待二审状态，才可以进行二审！');
		// }

		#审核操作：1、通过 2、不通过
		if( $audit==1 ){//通过
			//改变订单的状态
			$orderData['order_id']=$orderGoods['order_id'];
			$orderData['status']=6;//待三审
			$orderData['update_time']=date('Y-m-d H:i:s');

			$h1=M('order')->save($orderData);

			//改变订货单的状态
			$orderGoodsData['order_goods_id']=$orderGoods['order_goods_id'];
			$orderGoodsData['status']=4;//待三审
			$orderGoodsData['second_audit']=$_SESSION['user'];//记录二审操作员
			$orderGoodsData['update_time']=date('Y-m-d H:i:s');

			$h2=M('order_goods')->save($orderGoodsData);

			if( $h1 && $h2 ){
				$this->success('操作成功，二审通过！',U('view',array('order_id'=>$order_id)));
			}else{
				$this->success('操作失败！',U('view',array('order_id'=>$order_id)));
			}

		}else{//不通过
			//改变订单的状态
			$orderData['order_id']=$orderGoods['order_id'];
			$orderData['status']=3;//不通过
			$orderData['update_time']=date('Y-m-d H:i:s');

			$h1=M('order')->save($orderData);

			//改变订货单的状态
			$orderGoodsData['order_goods_id']=$orderGoods['order_goods_id'];
			$orderGoodsData['status']=7;//完结
			$orderGoodsData['second_audit']=$_SESSION['user'];//记录二审操作员
			$orderGoodsData['update_time']=date('Y-m-d H:i:s');

			$h2=M('order_goods')->save($orderGoodsData);

			if( $h1 && $h2 ){
				//判断订单是首单还是补单
				if( $orderGoods['orderType'] == 1 ){//首单
					#如果是首单，则冻结会员
					$memberData['status']=2;
					$memberData['update_time']=date('Y-m-d H:i:s');//更新时间
					if( M('member')->where(array('id_card'=>$orderGoods['id_card']))->save($memberData) ){
						$this->success('操作成功，二审不通过，同时该经销商被冻结！',U('view',array('order_id'=>$order_id)));
					}else{
						$this->success('操作失败，冻结会员失败！',U('view',array('order_id'=>$order_id)));
					}
				}else{//补单
					#如果是补单，不冻结会员
					$this->success('操作成功，二审不通过！',U('view',array('order_id'=>$order_id)));
				}
			}else{
				$this->success('操作失败，订货单和订单更新不成功！',U('view',array('order_id'=>$order_id)));
			}
		}

	}

	/**
	 * 订货单三审
	 * @author 黄俊
	 * date 2016-12-14
	 * 备注：后期需求调整，该方法不在使用
	 */
	public function thirdAudit(){
		//权限判断
		if( !in_array($_SESSION['role'], array(3,4)) ){
			$this->error('权限不够！');
		}

		$order_id=I('order_id',0,'intval');//订单id
		$audit=I('audit',0,'intval');//审核操作：1、通过 2、不通过

		//验证audit
		if( !in_array($audit, array(1,2)) ){
			$this->error('非法操作：audit！');
		}

		//获得订货单信息
		$orderGoods=SERVICE('OrderGoods')->getOrderGoods($order_id,true);

		#验证订货单与订单的状态
		// 验证订单状态
		if( $orderGoods['orderStatus'] != 6 ){
			$this->error('【订单】处于待三审状态，才可以进行三审！');
		}
		// 验证订货单状态
		// if( $orderGoods['status'] != 4 ){
		// 	$this->error('【订货单】处于待三审状态，才可以进行三审！');
		// }
		// 验证三审和二审是否是同一人
		if( $_SESSION['user']==$orderGoods['second_audit'] ){
			$this->error('【订货单】二审和三审，不可以是同一人！');
		}

		#审核操作：1、通过 2、不通过
		if( $audit==1 ){//通过
			//改变订单的状态
			$orderData['order_id']=$orderGoods['order_id'];
			$orderData['status']=2;//通过
			$orderData['update_time']=date('Y-m-d H:i:s');

			$h1=M('order')->save($orderData);

			//改变订货单的状态
			$orderGoodsData['order_goods_id']=$orderGoods['order_goods_id'];
			$orderGoodsData['status']=5;//通过
			$orderGoodsData['update_time']=date('Y-m-d H:i:s');

			$h2=M('order_goods')->save($orderGoodsData);

			if( $h1 && $h2 ){//操作成功

				#计算真实的团队业绩和相关人员的推荐奖

				// 会员信息
				$memberInfo=M('member')->where( array('id_card'=>$orderGoods['id_card']) )->find();
				// 奖励配置信息
				$reward_config=D('Web')->reward_config();
				//财务模型
				$finance=D('Finance');

				// 计算真实的团队业绩和相关人员的推荐奖
				if(!$finance->calculateAch($orderGoods['money'],$memberInfo) || !$finance->rec_reward($orderGoods['money'],$memberInfo['recommend_user'],$reward_config)){
					$this->success('操作失败,真实业绩和奖励计算不成功！',U('view',array('order_id'=>$order_id)));
				}else{
					$this->success('操作成功，三审通过！',U('view',array('order_id'=>$order_id)));
				}
				
			}else{//操作失败
				$this->success('操作失败！',U('view',array('order_id'=>$order_id)));
			}

		}else{//不通过
			//改变订单的状态
			$orderData['order_id']=$orderGoods['order_id'];
			$orderData['status']=3;//不通过
			$orderData['update_time']=date('Y-m-d H:i:s');

			$h1=M('order')->save($orderData);

			//改变订货单的状态
			$orderGoodsData['order_goods_id']=$orderGoods['order_goods_id'];
			$orderGoodsData['status']=7;//完结
			$orderGoodsData['update_time']=date('Y-m-d H:i:s');

			$h2=M('order_goods')->save($orderGoodsData);

			if( $h1 && $h2 ){
				//判断订单是首单还是补单
				if( $orderGoods['orderType'] == 1 ){//首单
					#如果是首单，则冻结会员
					$memberData['status']=2;
					$memberData['update_time']=date('Y-m-d H:i:s');//更新时间
					if( M('member')->where(array('id_card'=>$orderGoods['id_card']))->save($memberData) ){
						$this->success('操作成功，三审不通过，同时该经销商被冻结！',U('view',array('order_id'=>$order_id)));
					}else{
						$this->success('操作失败，冻结会员失败！',U('view',array('order_id'=>$order_id)));
					}
				}else{//补单
					#如果是补单，不冻结会员
					$this->success('操作成功，三审不通过！',U('view',array('order_id'=>$order_id)));
				}
			}else{
				$this->success('操作失败，订货单和订单更新不成功！',U('view',array('order_id'=>$order_id)));
			}
		}
	}

	/**
	 * 订货单下载
	 * @author 黄俊
	 * date 2016-12-11
	 */
	public function download(){
		//权限判断
		if( !in_array($_SESSION['role'], array(3,4,5)) ){
			$this->error('权限不够！');
		}

		$order_goods_id=I('order_goods_id',0,'intval');

		//订货单基本信息
		$orderGoods=SERVICE('OrderGoods')->getOrderGoods($order_goods_id);
		//订货货单货物list
		$orderGoodsList=SERVICE('OrderGoods')->getOrderGoodsList($order_goods_id);
		//系统配置
		$reward_config=D('Web')->reward_config();

		if( empty($orderGoodsList) ){//如果没有货物
			$this->error('订货单内，没有货物，不能进行下载！');
		}

		/*订货单状态为2以上，统计下载数次*/
		if( $orderGoods['status']>=2 ){

			#数据更新
			$data['order_goods_id']=$orderGoods['order_goods_id'];

			//如果订货单状态为2(待发货)，并且从未被下载过，在统计时，同时改变订货单状态
			if( $orderGoods['download_num'] ==0 && $orderGoods['status']==2 ){
				$data['status']=3;//发货中
			}

			$data['download_num']=$orderGoods['download_num']+1;

			if( !M('order_goods')->save($data) ){
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
		$objActSheet->getColumnDimension('F')->setWidth(8);
		$objActSheet->getColumnDimension('G')->setWidth(8);
		$objActSheet->getColumnDimension('H')->setWidth(10);
		$objActSheet->getColumnDimension('I')->setWidth(13);
		$objActSheet->getColumnDimension('J')->setWidth(10);
		$objActSheet->getColumnDimension('K')->setWidth(12);

		//Excel表格式
		$letter = array('A','B','C','D','E','F','G','H','I','J','K');

		# 第1行
		$objActSheet->setCellValue('A1', '浪莎订货单');    
		//合并单元格   
		$objActSheet->mergeCells('A1:K1');    
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
		$objActSheet->mergeCells('A2:B2');
		$objActSheet->mergeCells('C2:E2');
		$objActSheet->mergeCells('F2:H2');
		$objActSheet->mergeCells('I2:K2');
		// 设置内容
		$memberInfo=SERVICE('Member')->getMemberByIdCard($orderGoods['id_card']);//会员信息

		// 会员帐号
		$user='';
		if(empty($memberInfo)){
			$user.='暂无';
		}else{
			$user.=$memberInfo['user'].'('.$memberInfo['name'].')';
		}

		$objActSheet->setCellValue('A2', ' 会员帐号：'.$user);
		$objActSheet->setCellValue('C2', ' 姓名：'.$orderGoods['name']);
		$objActSheet->setCellValue('F2', ' 联系方式：'.$orderGoods['tel']);
		$objActSheet->setCellValue('I2', ' 日期：'.$orderGoods['add_time']);
		//设置样式   
		$objStyleA2 = $objActSheet->getStyle('A2');       
		$objStyleA2->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);   
		$objFontA2 = $objStyleA2->getFont();
		$objStyleC2 = $objActSheet->getStyle('C2');       
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
		$objActSheet->mergeCells('A3:F3');
		$objActSheet->mergeCells('G3:I3');
		$objActSheet->mergeCells('J3:K3');
		// 设置内容
		$objActSheet->setCellValue('A3', ' 收货地址：'.$orderGoods['goods_address']);
		$objActSheet->setCellValue('G3', ' 报单金额：'.$orderGoods['money']);
		$objActSheet->setCellValue('J3', ' ');
		// $objActSheet->setCellValue('J3', ' 提交限制：'.$reward_config['order_discount'].'元');
		//设置样式   
		$objStyleA3 = $objActSheet->getStyle('A3');       
		$objStyleA3->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);   
		$objFontA3 = $objStyleA3->getFont();
		$objStyleE3 = $objActSheet->getStyle('G3');       
		$objStyleE3->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);   
		$objFontE3 = $objStyleE3->getFont();
		$objStyleG3 = $objActSheet->getStyle('J3');       
		$objStyleG3->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);   
		$objFontG3 = $objStyleG3->getFont();
		$objActSheet->getRowDimension('3')->setRowHeight(22);

		# 第4行
		//合并单元格   
		$objActSheet->mergeCells('A4:F4');
		$objActSheet->mergeCells('G4:K4');
		// 设置内容
		$objActSheet->setCellValue('A4', ' 代收货人姓名：'.$orderGoods['goods_name']);
		$objActSheet->setCellValue('G4', ' 代收货人联系方式：'.$orderGoods['goods_tel']);
		//设置样式   
		$objStyleA4 = $objActSheet->getStyle('A4');       
		$objStyleA4->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);   
		$objFontA4 = $objStyleA4->getFont();
		$objStyleE4 = $objActSheet->getStyle('G4');       
		$objStyleE4->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);   
		$objFontE4 = $objStyleE4->getFont();
		$objActSheet->getRowDimension('4')->setRowHeight(22);

		# 第5行
		// 合并单元格
		$objActSheet->mergeCells('J5:K5');
		// 设置内容
		$objActSheet->setCellValue('A5', ' 货号');
		$objActSheet->setCellValue('B5', ' 货品');
		$objActSheet->setCellValue('C5', ' 尺码');
		$objActSheet->setCellValue('D5', ' 颜色');
		$objActSheet->setCellValue('E5', ' 单价(元)');
		$objActSheet->setCellValue('F5', ' 折扣');
		$objActSheet->setCellValue('G5', ' 数量');
		$objActSheet->setCellValue('H5', ' 已发数量');
		$objActSheet->setCellValue('I5', ' 单位');
		$objActSheet->setCellValue('J5', ' 合计（元）');
		//设置样式
		for ($i=0; $i < 11; $i++) { //$letter
			$objStyle = $objActSheet->getStyle($letter[$i].'5');       
			$objStyle->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);   
			$objFont = $objStyle->getFont();
			$objFont->setBold(true);
		}
		$objActSheet->getRowDimension('5')->setRowHeight(22);

		// 货品列表
		$k=6;
		$numTotal=0;//货物总件数
		$totalMoney=0;//总金额
		$deliverMoney=0;//已发货总金额
		foreach ($orderGoodsList as $key => $value) {
			// 合并单元格
			$objActSheet->mergeCells('J'.$k.':K'.$k);
			// 设置内容
			$objActSheet->setCellValue("A".$k,' '.$value['number'].' ');
			$objActSheet->setCellValue("B".$k,' '.$value['name']);
			$objActSheet->setCellValue("C".$k,' '.$value['size'].' ');
			$objActSheet->setCellValue("D".$k,' '.$value['color'].' ');
			$objActSheet->setCellValue("E".$k,' '.floatval($value['price']).' ');
			$objActSheet->setCellValue("F".$k,' '.$value['discount'].'%');
			$numTotal+=($value['num']+$value['deliver_num']);//数量统计
			$objActSheet->setCellValue("G".$k,' '.$value['num'].' ');
			$objActSheet->setCellValue("H".$k,' '.$value['deliver_num'].' ');
			$objActSheet->setCellValue("I".$k,' '.$value['num_unit']);

			$money=floatval($value['price']*($value['num']+$value['deliver_num'])*$value['discount']*0.01);//单项总价
			$objActSheet->setCellValue("J".$k,' '.$money.' ');
			$totalMoney+=$money;//总价统计
			$deliverMoney+=floatval($value['price']*$value['deliver_num']*$value['discount']*0.01);//已发货总额统计
			$k++;
		}
		$k++;
		$k++;
		//合并单元格   
		$objActSheet->mergeCells('B'.$k.':E'.$k);

		// 设置内容
		$objActSheet->setCellValue('A'.$k, ' 状态：');
		$status=SERVICE('DeliverGoods')->getStatus($orderGoods['status']);
		$objActSheet->setCellValue('B'.$k, ' '.$status);
		$objActSheet->setCellValue('F'.$k, ' 总件数:');
		$objActSheet->setCellValue('G'.$k, ' '.$numTotal.'件');
		$objActSheet->setCellValue('H'.$k, ' 总计:');
		$objActSheet->setCellValue('I'.$k, ' '.$totalMoney.'元');
		$objActSheet->setCellValue('J'.$k, ' 已发货：');
		$objActSheet->setCellValue('K'.$k, ' '.$deliverMoney.'元');

		//设置样式  
		for ($i=0; $i < 11; $i++) { //$letter
			$objStyle = $objActSheet->getStyle($letter[$i].$k);       
			$objStyle->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);   
			$objFont = $objStyle->getFont();
			// $objFont->setSize(14);
		} 
		$objActSheet->getRowDimension($k)->setRowHeight(25);
		$k++;

		//合并单元格   
		$objActSheet->mergeCells('B'.$k.':K'.$k);

		// 设置内容
		$objActSheet->setCellValue('A'.$k, ' 备注：');
		$objActSheet->setCellValue('B'.$k, ' '.$orderGoods['remarks']);

		//设置样式  
		for ($i=0; $i < 11; $i++) { //$letter
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
		for ($i=0; $i < 11; $i++) { //$letter
			$objStyle = $objActSheet->getStyle($letter[$i].$k);       
			$objStyle->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);   
		}

		$objActSheet->getRowDimension($k)->setRowHeight(22);
		$k++;

		//创建Excel输入对象
		$filename='订货单--'.$orderGoods['name'].'--'.date('Y-m-d');
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
	 * 订货单完结
	 * @author 黄俊
	 * date 2016-12-11
	 */
	public function end(){
		//权限判断
		if( !in_array($_SESSION['role'], array(3,4,5)) ){
			$this->error('权限不够！');
		}

		if(IS_AJAX){
			
			$order_id=I('order_id',0,'intval');//订单id

			if( empty($order_id) ){
				$rs['status']=2;
				$rs['msg']="非法操作！！";
				$this->ajaxReturn($rs);
			}

			$data['status']=7;//完结
			$data['update_time']=date('Y-m-d H:i:s');//更新时间

			if( M('order_goods')->where(array('order_id'=>$order_id))->save($data) ){
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
	 * 订货单状态重建
	 * @author 黄俊
	 * date 2017-2-21
	 * 备注：因业务需要，订货单的需要状态重新设计。该函数用于
	 *		 根据丁货单的发货情况，更新到对应的状态
	 */
	public function orderGoodsStatusUpdate(){

		//权限判断
		if( !in_array($_SESSION['role'], array(4)) || $_SESSION['user'] !='admin' ){
			$this->error('权限不够！');
		}


		/*1、取出所有的订货单信息*/
		$allOrderGoods=M('order_goods')->where('status in(2,3,4,5,6)')->select();

		$count=M('order_goods')->where('status in(2,3,4,5,6)')->count();//总数
		$total=0;//用于统计更新了多少条数据
		// echo $count;
		// P($allOrderGoods);die();

		/*2、遍历每个订货单，返回相应的status*/
		foreach ($allOrderGoods as $key => $value) {

			//如果订单状态为1、2、7 则不处理
			if( !in_array($value['status'], array(1,7)) ){

				//获得新的status
				$data['status']=SERVICE('OrderGoods')->StatusUpdate($value);

				//更新该条订货单的status
				$data['order_goods_id']=$value['order_goods_id'];
				$data['update_time']=date('Y-m-d H:i:s');

				if( M('order_goods')->save($data) ){
					$total++;
				}else{
					echo "总数：".$count.',已更新：'.$total;
				}

			}

			
		}


		echo "更新成功！<br>总数：".$count.',已更新：'.$total;
		
	}

}
?>