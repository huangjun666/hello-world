<?php
/**
 * 订单模块
 * @author 黄俊
 * date 2016-6-28
 */
class OrderAction extends BaseAction{

	/**
	 * 首页
	 * @author 黄俊
	 * date 2016-6-28
	 */
	public function index(){
		
		//权限判断
		if( !in_array($_SESSION['role'], array(1,2,3,4)) ){
			$this->error('权限不够！');
		}

		import('ORG.Util.Page');//引入分页类

		$name=I('name','');

		$where='';//范围

		//普通经销商
		if( !in_array($_SESSION['role'], array(2,3,4)) ){
			$where=' recommend_user="'.$_SESSION['user'].'"  OR place_user="'.$_SESSION['user'].'"';
		}

		//生活馆馆主
		if( !in_array($_SESSION['role'], array(1,3,4)) ){
			$where=' recommend_user="'.$_SESSION['user'].'"  OR place_user="'.$_SESSION['user'].'" or handle="'.$_SESSION['user'].'"';
		}

		//生活馆馆主
		if( in_array($_SESSION['role'], array(2)) && !empty($name) ){
			$where=' `name` LIKE "%'.$name.'%" AND (recommend_user="'.$_SESSION['user'].'"  OR place_user="'.$_SESSION['user'].'" or handle="'.$_SESSION['user'].'" or id_card="'.$_SESSION['id_card'].'")';
		}

		//财务、系统管理员
		if( in_array($_SESSION['role'], array(3,4)) && !empty($name) ){
			$where=' `name` LIKE "%'.$name.'%" ';
		}

		/*分页*/
		$count=M('order')->where($where)->count();//总数量
		$page=new Page($count,20);
		$limit=$page->firstRow.','.$page->listRows;

		$order=M('order')->where($where)->limit($limit)->order('field(status,1,4,5,6,2,3),update_time DESC,add_time DESC')->select();

		$this->page=$page->show();//获得页码
		$this->sort=$page->firstRow;//序号
		$this->assign('order',$order);
		$this->display();
	}

	/**
	 * 添加订单
	 * @author 黄俊
	 * date 2016-6-28
	 */
	public function add(){

		//时间判断--0点至8点为禁止操作时间
		// if(1){
		// 	$this->error('系统维护中，该功能暂停使用！');
		// }

		//时间判断--0点至8点为禁止操作时间
		if( timeLimit(8) ){
			$this->error('0点至8点为禁止操作时间！');
		}

		//权限判断
		if( !in_array($_SESSION['role'], array(2,4)) ){
			$this->error('权限不够！');
		}

		if(IS_POST){

			/*防止表单重复提交*/
			$fkey=I('fkey','','strip_tags');
			if( $fkey != $_SESSION['fkey'] ){
				session('fkey',time());
				$this->assign('error','不可以重复提交！');
				$this->display();
				exit();
			}

			session('fkey',time());

			/*验证表单提交字段*/

			// 判断订单状态
			$save=I('save',0,'intval');
			$data['status']=$save==0?1:4;//status:1、待提交 4、待一审
			// echo $data['status'];die();

			//店铺名称不可以为空
			$data['recommend_shop']=I('recommend_shop','');
			if(empty($data['recommend_shop'])){
				$this->assign('error','店铺名称不可以为空');
				$this->display();
				exit();
			}

			//姓名不可以为空
			$data['name']=I('name','');
			if(empty($data['name'])){
				$this->assign('error','姓名不可以为空');
				$this->display();
				exit();
			}

			//性别不可以为0
			$data['sex']=I('sex',0,'intval');
			if(empty($data['sex'])){
				$this->assign('error','性别非法！');
				$this->display();
				exit();
			}

			//联系方式
			$data['tel']=I('tel','');
			if( !is_tel($data['tel']) ){
				$this->assign('error','手机号不正确');
				$this->display();
				exit();
			}

			//身份证
			$data['id_card']=I('id_card','');
			if( !is_idCard($data['id_card']) ){
				$this->assign('error','身份证不正确');
				$this->display();
				exit();
			}

			$data['bank_name']=I('bank_name','');
			$data['bank_user']=I('bank_user','');
			$data['bank_card']=I('bank_card',0);
			//银行信息必填
			if( empty($data['bank_name']) || empty($data['bank_user']) || empty($data['bank_card']) ){
				$this->assign('error','银行相关信息必填');
				$this->display();
				exit();
			}

			//银行账号格式
			if(!is_bank_card($data['bank_card'])){
				$this->assign('error','银行卡号格式不正确');
				$this->display();
				exit();
			}

			//推荐人不可以为空
			$data['recommend_user']=I('recommend_user','');
			if(empty($data['recommend_user'])){
				$this->assign('error','推荐人不可以为空');
				$this->display();
				exit();
			}

			//安置人不可以为空
			$data['place_user']=I('place_user','');
			if(empty($data['place_user'])){
				$this->assign('error','安置人不可以为空');
				$this->display();
				exit();
			}

			//详细地址不可以为空
			$data['adress']=I('adress','');
			if(empty($data['adress'])){
				$this->assign('error','详细地址不可以为空');
				$this->display();
				exit();
			}

			//订单详细地址不可以为空
			$data['order_adress']=I('order_adress','');
			if(empty($data['order_adress'])){
				$this->assign('error','订单详细地址不可以为空');
				$this->display();
				exit();
			}

			/*验证一致性*/
			//店铺名称和推荐人
			$rec_user=M('member')->where(array('user'=>$data['recommend_user'],'shop'=>$data['recommend_shop']))->find();

			if(empty($rec_user)){
				$this->assign('error','请检查店铺名称和推荐人是否正确');
				$this->display();
				exit();
			}

			//检查推荐人是否被冻结
			if( $rec_user['status'] == 2 ){
				$this->assign('error','推荐人被冻结!');
				$this->display();
				exit();
			}

			//安置人是否存在
			$place_user=M('member')->where(array('user'=>$data['place_user']))->find();
			if(empty($place_user)){
				$this->assign('error','安置人不存在');
				$this->display();
				exit();
			}

			//检查安置人是否被冻结
			if( $place_user['status'] == 2 ){
				$this->assign('error','安置人被冻结!');
				$this->display();
				exit();
			}

			$data['email']=I('email','');
			//验证邮箱
			if( !is_email($data['email']) ){
				$this->assign('error','邮箱格式不对');
				$this->display('add');
				exit();
			}
			

			//实例化订单模型
			$order=M('order');

			//检查订单是首单/补单
			$data['orderType']=I('orderType',0,'intval');

			//如果不是补单或者重销
			if( !in_array($data['orderType'], array(2,3))){
				$orderInfo=$order->where('status in (1,2,4,5,6) and id_card="'.$data['id_card'].'"')->find();
				if( empty($orderInfo) ){//之前没提交过订单，则是首单 
					$data['orderType']=1;
				}else{//补单
					$data['orderType']=2;
				}
			}
			
			$data['recommend_name']=M('member')->where(array('user'=>$data['recommend_user']))->getField('name');
			$data['place_name'] = $place_user['name'];
			$data['money'] = I('money',0,'intval');
			// $data['type'] = I('type',0,'intval');		
			$data['ProvinceID'] = I('province',0,'intval');
			$data['CityID'] = I('city',0,'intval');
			$data['DistrictID'] = I('district',0,'intval');
			$data['order_ProvinceID'] = I('order_province',0,'intval');
			$data['order_CityID'] = I('order_city',0,'intval');
			$data['order_DistrictID'] = I('order_district',0,'intval');

			//订单备注
			$data['remarks'] = I('remarks','');
			// 判断是否保存并提交---订单备注不可以为空
			if($save && empty($data['remarks'])){
				$this->assign('error','提交时，订单备注不可以为空');
				$this->display('add');
				exit();
			}

			//订单合同照片
			$data['contract_img1'] = I('contract_img1','');
			$data['contract_img2'] = I('contract_img2','');
			$data['contract_img3'] = I('contract_img3','');
			$data['contract_img4'] = I('contract_img4','');
			// 判断是否保存并提交---必须上传4张经销商合同照片
			if($save && empty($data['contract_img1'])){
				$this->assign('error','必须上传4张经销商合同照片');
				$this->display('add');
				exit();
			}

			// 判断是否保存并提交---必须上传4张经销商合同照片
			if($save && empty($data['contract_img2'])){
				$this->assign('error','必须上传4张经销商合同照片');
				$this->display('add');
				exit();
			}

			// 判断是否保存并提交---必须上传4张经销商合同照片
			if($save && empty($data['contract_img3'])){
				$this->assign('error','必须上传4张经销商合同照片');
				$this->display('add');
				exit();
			}

			// 判断是否保存并提交---必须上传4张经销商合同照片
			if($save && empty($data['contract_img4'])){
				$this->assign('error','必须上传4张经销商合同照片');
				$this->display('add');
				exit();
			}

			
			$save=I('save',0,'intval');
			

			$data['update_time']=date('Y-m-d H:i:s');
			$data['handle']=$_SESSION['user'];
			//保存
			$order_id=$order->add($data);
			if($order_id){//保存成功之后，跳转至订货单页面
				$this->success('添加成功！请填写订货单。。。',U('OrderGoods/edit',array('order_id'=>$order_id)));
			}else{
				$this->assign('error','非法错误，请重试');
				$this->display();
			}

		}else{
			session('fkey',time());

			//接收补单或重销参数
			$order_id=I('order_id',0,'intval');
			$orderType=I('orderType',0,'intval');

			//检查是否是通过补单或重销过来的
			if( $order_id!=0 && in_array($orderType, array(2,3))){
				$this->order=M('order')->where(array('order_id'=>$order_id))->find();
			}

			$this->display();
			
		}
	}

	/**
	 * 编辑订单
	 * @author 黄俊
	 * date 2016-6-28
	 */
	public function edit(){

		//权限判断
		if( !in_array($_SESSION['role'], array(2,3,4)) ){
			$this->error('权限不够！');
		}

		//如果是馆主，判断该订单，是否由该馆主添加的
		if( !in_array($_SESSION['role'], array(3,4)) ){

			$order_id=I('order_id',0,'intval');//订单id

			$orderInfo=M('order')->where(array('order'=>$order_id,'handle'=>$_SESSION['user']))->find();

			if( empty($orderInfo) ){
				$this->error('不是你添加的订单，不可以编辑！');
			}
			
		}

		if(IS_POST){

			/*验证表单提交字段*/
			$data['order_id']=I('order_id',0,'intval');

			$orderInfo=D('Order')->getOrder($data['order_id']);


			# 根据具体角色判断订单是否可以编辑
			//馆主
			if( in_array($_SESSION['role'], array(2)) ){
				if( $orderInfo['status'] != 1 ){
					$this->error('提交过的订单不可以编辑！');
				}
			}

			// 财务，系统管理员
			if( in_array($_SESSION['role'], array(3,4)) ){
				if( !in_array($orderInfo['status'], array(1,4)) ){
					$this->error('审核过的订单不可以编辑！');
				}
			}

			//店铺名称不可以为空
			$data['recommend_shop']=I('recommend_shop','');
			if(empty($data['recommend_shop'])){
				$this->assign('error','店铺名称不可以为空');
				$this->display();
				exit();
			}

			//姓名不可以为空
			$data['name']=I('name','');
			if(empty($data['name'])){
				$this->assign('error','姓名不可以为空');
				$this->display();
				exit();
			}

			//性别不可以为0
			$data['sex']=I('sex',0,'intval');
			if(empty($data['sex'])){
				$this->assign('error','性别非法！');
				$this->display();
				exit();
			}

			//联系方式
			$data['tel']=I('tel','');
			if( !is_tel($data['tel']) ){
				$this->assign('error','手机号不正确');
				$this->display();
				exit();
			}


			$data['bank_name']=I('bank_name','');
			$data['bank_user']=I('bank_user','');
			$data['bank_card']=I('bank_card',0);
			//银行信息必填
			if( empty($data['bank_name']) || empty($data['bank_user']) || empty($data['bank_card']) ){
				$this->assign('error','银行相关信息必填');
				$this->display();
				exit();
			}

			//推荐人不可以为空
			$data['recommend_user']=I('recommend_user','');
			if(empty($data['recommend_user'])){
				$this->assign('error','推荐人不可以为空');
				$this->display();
				exit();
			}

			//安置人不可以为空
			$data['place_user']=I('place_user','');
			if(empty($data['place_user'])){
				$this->assign('error','安置人不可以为空');
				$this->display();
				exit();
			}

			//详细地址不可以为空
			$data['adress']=I('adress','');
			if(empty($data['adress'])){
				$this->assign('error','详细地址不可以为空');
				$this->display();
				exit();
			}

			//订单详细地址不可以为空
			$data['order_adress']=I('order_adress','');
			if(empty($data['order_adress'])){
				$this->assign('error','订单详细地址不可以为空');
				$this->display();
				exit();
			}

			/*验证一致性*/
			//店铺名称和推荐人
			$rec_user=M('member')->where(array('user'=>$data['recommend_user'],'shop'=>$data['recommend_shop']))->find();
			if(empty($rec_user)){
				$this->assign('error','请检查店铺名称和推荐人是否正确');
				$this->display();
				exit();
			}

			//安置人是否存在
			$place_user=M('member')->where(array('user'=>$data['place_user']))->find();
			if(empty($place_user)){
				$this->assign('error','安置人不存在');
				$this->display();
				exit();
			}

			$data['email']=I('email','');
			//验证邮箱
			if( !is_email($data['email']) ){
				$this->assign('error','邮箱格式不对');
				$this->display('add');
				exit();
			}

			

			//实例化订单模型
			$order=M('order');

			$data['recommend_name']=M('member')->where(array('user'=>$data['recommend_user']))->getField('name');
			$data['place_name'] = $place_user['name'];
			$data['money'] = I('money',0,'intval');
			// $data['type'] = I('type',0,'intval');		
			$data['ProvinceID'] = I('province',0,'intval');
			$data['CityID'] = I('city',0,'intval');
			$data['DistrictID'] = I('district',0,'intval');
			$data['order_ProvinceID'] = I('order_province',0,'intval');
			$data['order_CityID'] = I('order_city',0,'intval');
			$data['order_DistrictID'] = I('order_district',0,'intval');

			$data['update_time']=date('Y-m-d H:i:s');

			// 判断是否保存并提交
			$save=I('save',0,'intval');
			if($save){
				$data['status']=4;//status:1、待提交 4、待一审
			}

			//订单备注
			$data['remarks'] = I('remarks','');
			// 判断是否保存并提交---订单备注不可以为空
			if($save && empty($data['remarks'])){
				$this->assign('error','提交时，订单备注不可以为空');
				$this->display('add');
				exit();
			}

			//订单合同照片
			$data['contract_img1'] = I('contract_img1','');
			$data['contract_img2'] = I('contract_img2','');
			$data['contract_img3'] = I('contract_img3','');
			$data['contract_img4'] = I('contract_img4','');
			// 判断是否保存并提交---必须上传4张经销商合同照片
			if($save && empty($data['contract_img1'])){
				$this->assign('error','必须上传4张经销商合同照片');
				$this->display('add');
				exit();
			}

			// 判断是否保存并提交---必须上传4张经销商合同照片
			if($save && empty($data['contract_img2'])){
				$this->assign('error','必须上传4张经销商合同照片');
				$this->display('add');
				exit();
			}

			// 判断是否保存并提交---必须上传4张经销商合同照片
			if($save && empty($data['contract_img3'])){
				$this->assign('error','必须上传4张经销商合同照片');
				$this->display('add');
				exit();
			}

			// 判断是否保存并提交---必须上传4张经销商合同照片
			if($save && empty($data['contract_img4'])){
				$this->assign('error','必须上传4张经销商合同照片');
				$this->display('add');
				exit();
			}
			

			//保存
			if($order->save($data)){
				if($save==0){
					$this->success('编辑成功！',U('index'));
				}else{//提交之后，跳转到订货单页面
					$this->success('添加成功！请填写订货单。。。',U('OrderGoods/edit',array('order_id'=>$data['order_id'])));
				}
				
			}else{
				$this->assign('error','非法错误，请重试');
				$this->display();
			}

		}else{
			//获得订单ID
			$order_id=I('order_id',0,'intval');

			$order=D('Order')->getOrder($order_id);

			# 根据具体角色判断订单是否可以编辑
			//馆主
			if( in_array($_SESSION['role'], array(2)) ){
				if( $order['status'] != 1 ){
					$this->error('提交过的订单不可以编辑！');
				}
			}

			// 财务，系统管理员
			if( in_array($_SESSION['role'], array(3,4)) ){
				if( !in_array($order['status'], array(1,4)) ){
					$this->error('审核过的订单不可以编辑！');
				}
			}

			$this->assign('order',$order);
			$this->display();
			
		}
	}

	/**
	 * 订单删除
	 * @author 黄俊
	 * date 2017-10-22
	 */
	public function del(){
		//权限判断
		if( !in_array($_SESSION['role'], array(2,3,4)) ){
			$this->error('权限不够！');
		}

		//获取参数 
		$order_id=I('order_id',0,'intval');		

		// 订单信息
		$order=M('order')->where(array('order_id'=>$order_id))->find();

		//无效订单
		if(empty($order)){
			$this->error('非法操作！');
		}

		//只能删除 待提交、待一审 的订单
		if(!in_array($order['status'], array(1,4))){
			$this->error('非法操作！');
		}

		//待提交时，如果是馆主只能删除自己填写的订单
		if($order['status']==1 && $_SESSION['role']==2){
			//权限判断
			if( $order['handle'] != $_SESSION['user'] ){
				$this->error('非法操作！');
			}
		}

		//待一审时，只有管理员和财务可以删除
		if($order['status']==4){
			//权限判断
			if( !in_array($_SESSION['role'], array(3,4)) ){
				$this->error('权限不够！');
			}
		}

		//满足条件，删除订单
		if(M('order')->where(array('order_id'=>$order_id))->delete()){
			//相应的删除订货单、发货单
			M('order_goods')->where(array('order_id'=>$order_id))->delete();
			M('deliver_goods')->where(array('order_id'=>$order_id))->delete();
			$this->success('删除成功！',U('index'));
		}else{
			$this->error('网络不好，请重试！');
		}

	}

	/**
	 * 订单详情
	 * @author 黄俊
	 * date 2016-6-28
	 */
	public function view(){
		//权限判断
		if( !in_array($_SESSION['role'], array(1,2,3,4)) ){
			$this->error('权限不够！');
		}

		//获取参数 
		$order_id=I('order_id',0,'intval');		

		// 订单信息
		$order=D('Order')->getOrder($order_id);

		//如果不是财务或系统管理员，检查用户是否有权限访问，防止绕过流程，直接访问
		if( !in_array($_SESSION['role'], array(3,4)) ){

			if( $_SESSION['user'] !=$order['recommend_user'] && $_SESSION['user'] !=$order['place_user'] && $_SESSION['user'] !=$order['handle'] ){
				$this->error('非法错误！');
			}
		}

		$this->order=$order;
		$this->display();
	}

	/**
	 * 打回订单，
	 * @author 黄俊
	 * date 2017-7-11
	 * 备注：打回订单，馆主重新编辑，提交
	 */
	public function back(){
		//只有财务或者系统管理员，才可以审核订单
		//权限判断
		if( !in_array($_SESSION['role'], array(3,4)) ){
			$this->error('权限不够！');
		}

		//获取参数 
		$order_id=I('order_id',0,'intval');		
		$back_msg=I('back_msg','');		

		// 订单信息
		$order=M('Order')->where(array("order_id"=>$order_id))->find();

		// 只有处于一审的订单，才可以进行打回操作
		if( empty($order) ){
			$this->error('非法操作！');
		}

		// 只有处于一审的订单，才可以进行打回操作
		if( $order['status'] != 4 ){
			$this->error('只有处于一审的订单，才可以进行打回操作');
		}

		$data['order_id']=$order_id;
		$data['status']=1;
		$data['back_msg']=$back_msg;
		$data['update_time']=date('Y-m-d H:i:s');

		//保存
		if(M('Order')->save($data)){
			// $this->success('打回成功！',U('edit',array('order_id'=>$order_id)));	
			$rs['status']=1;
			$rs['msg']='成功！';
			$this->ajaxReturn($rs);
		}else{
			$rs['status']=2;
			$rs['msg']='操作失败，请重试！';
			$this->ajaxReturn($rs);
		}
	}

	/**
	 * 审核订单
	 * @author 黄俊
	 * date 2016-6-28
	 */
	public function audit(){

		//时间判断--0点至8点为禁止操作时间
		// if(1){
		// 	$this->error('系统维护中，该功能暂停使用！');
		// }

		//时间判断--0点至8点为禁止操作时间
		if( timeLimit(8) ){
			$this->error('0点至8点为禁止操作时间！');
		}

		//只有财务或者系统管理员，才可以审核订单
		//权限判断
		if( !in_array($_SESSION['role'], array(3,4)) ){
			$this->error('权限不够！');
		}

		//获取参数
		$audit=I('audit',0,'intval');
		$order_id=I('order_id',0,'intval');
		$trans=0;

		//实例化模型
		$order=M('order');

		$orderInfo=$order->where(array('order_id'=>$order_id))->find();

		$update_time=$orderInfo['update_time'];//用于乐观锁，版本控制

		//检查订单是否可以被审核
		if( $orderInfo['status'] != 4 ){
			$this->error('订单已经被审核过');
		}

		//audit:1、执行通过操作  2、不通过操作
		if( $audit == 1 ){//通过

			$data['update_time']=date('Y-m-d H:i:s');
			$data['status']=5;//status=5，通过一审，待二审

			if( $order->where('order_id='.$order_id.' and update_time="'.$update_time.'"')->save($data) ){
				
				/*根据$orderInfo['id_card']判断是否有会员*/
				$memberModel=D('Member');
				$member=M('member');

				/*如果存在会员:提升会员的点位：2W,4W,6W*/
				$memberInfo=$memberModel->existMember($orderInfo['id_card']);
				if( $memberInfo ){//存在

					// $point=$memberModel->money_point($orderInfo['money']);//金额转化为点位级别
					$h=$memberModel->up_point($orderInfo['money'],$orderInfo['id_card'],$orderInfo['orderType']);//提升点位
					$trans=1;
					$transPoint=$memberInfo['point'];

					//如果操作失败，订单回退
					if(!$h){
						$order->where('order_id='.$order_id)->save(array('status'=>1));
						$this->error('提升点位失败，请重试！');
					}

				}else{//不存在会员，系统自动生成会员

					$memberInfo=$memberModel->createMember($orderInfo);
					$trans=2;
					//如果操作失败，订单回退
					if(!$memberInfo){
						$order->where('order_id='.$order_id)->save(array('status'=>1));
						$this->error('系统自动生成会员失败，请重试！');
					}
					$memberInfo['rec_reward']=0;
					$memberInfo['coach_reward']=0;

				}

				//财务模型
				$finance=D('Finance');

				/*计算会员上线【将来】的业绩：总业绩，月业绩，周业绩*/
				if(!$finance->calculateFutureAch($orderInfo['money'],$memberInfo)){
					// 如果操作失败
					if($trans==1){//回退点位
						$transData['point']=$transPoint;
						$member->where(array('user'=>$memberInfo['user']))->save($transData);
					}
					if($trans==2){//删除会员
						$member->where(array('user'=>$memberInfo['user']))->delete();
					}
				}
				// echo 789;die();
				/*奖励配置信息*/
				$reward_config=D('Web')->reward_config();
				
				if( $trans==2 ){

					/*1、添加辅导奖树形节点 2、暂时不计算推荐奖$finance->rec_reward($orderInfo['money'],$memberInfo['recommend_user'],$reward_config)*/
					if( $finance->coach_reward($memberInfo,$reward_config) ){
						
						/*发送短信提醒*/
						$tel=$memberInfo['tel'];
						$name=$memberInfo['name'];
						$user=$memberInfo['user'];
						$password=$memberInfo['password'];

						$dx=duanxin1($tel,$name,$user,$password);

						if( $dx->result->success ){//成功后发送跳转
							$this->success('操作成功，订单通过！',U('index'));
						}else{
							header("Content-type: text/html; charset=utf-8");
							echo '短信发送失败：<br>';
							echo '请记下该会员的会员账号：'.$user.',和密码：'.$password.';并告知该会员，谢谢！';
						}
					}
					
				}else{

					/*这种情况不用添加辅导奖树形节点，暂时也不用计算推荐奖*/
					// if( $finance->rec_reward($orderInfo['money'],$memberInfo['recommend_user'],$reward_config) ){
						$this->success('操作成功，订单通过！',U('index'));
					// }
					
				}
				

				// 
			}else{
				$this->error('网络不好，请重试！');
			}

		}else{//不通过
			
			$data['update_time']=date('Y-m-d H:i:s');
			$data['status']=3;//status=3，订单不通过

			if( $order->where('order_id='.$order_id.' and update_time="'.$update_time.'"')->save($data) ){
				// 同时完结订货单
				$orderGoodsData['status']=7;//完结
				$orderGoodsData['update_time']=date('Y-m-d H:i:s');
				M('order_goods')->where( array('order_id'=>$order_id) )->save($orderGoodsData);

				$this->success('操作成功，订单不通过！',U('index'));
			}else{
				$this->error('网络不好，请重试！');
			}

		}

	}

	/**
	 * 根据时间节点和单位，导出报表
	 * @author 黄俊
	 * date 2016-7-16
	 * 返回：下载Excel
	 */
	public function settlement_report(){

		//权限判断
		if( !in_array($_SESSION['role'], array(3,4)) ){
			$this->error('权限不够！');
		}

		$time=I('time','');//时间节点
		$unit=I('unit',0,'intval');//时间单位

		
		// 验证时间单位
		if( $unit != 1 && $unit != 2){
			$this->error('非法操作！');
		}

		$start_time='';
		$end_time='';

		//周
		if( $unit == 1 ){
			$time=getWeekFirst($time);
			$start_time=$time['now_start'];
			$end_time=$time['now_end'];
		}

		//月
		if( $unit == 2 ){
			$time=getMonthFirst($time);
			$start_time=$time[0].' 02:00:00';
			$end_time=$time[1].' 02:00:00';
		}

		//根据时间节点，查询出已经通过审核的订单
		$m=new Model();
		$sql='';
		$sql.=' select ';
		$sql.=' 
			 order_id,
			 recommend_shop,
			 NAME,
			 tel,
			 id_card,
			 recommend_name,
			 recommend_user,
			 place_name,
			 place_user,
			 update_time,
			 money qian,
			 status,
			 handle
			 FROM `order`
		';
		$sql.=' WHERE update_time BETWEEN "'.$start_time.'" AND "'.$end_time.'" AND STATUS in(2,6)';
 		
		// 查询出订单
 		$report=$m->query($sql);
		
		if( empty($report) ){//如果没有可结算的数据
			$this->error('该时间节点，没有订单数据！');
		}
		

		// P($report);die();
		//引入PHPExcel库文件
		import('Class.PHPExcel',APP_PATH);

		//创建对象
		$excel = new PHPExcel();
		$objActSheet = $excel->getActiveSheet();
		//设置宽度   
		$objActSheet->getColumnDimension('A')->setWidth(10);
		$objActSheet->getColumnDimension('B')->setWidth(20);
		$objActSheet->getColumnDimension('C')->setWidth(10);
		$objActSheet->getColumnDimension('D')->setWidth(20);
		$objActSheet->getColumnDimension('E')->setWidth(30);
		$objActSheet->getColumnDimension('F')->setWidth(20);
		$objActSheet->getColumnDimension('G')->setWidth(20);
		$objActSheet->getColumnDimension('H')->setWidth(20);
		$objActSheet->getColumnDimension('I')->setWidth(20);
		$objActSheet->getColumnDimension('J')->setWidth(30);
		$objActSheet->getColumnDimension('K')->setWidth(15);
		$objActSheet->getColumnDimension('L')->setWidth(15);
		$objActSheet->getColumnDimension('M')->setWidth(15);

		//Excel表格式
		$letter = array('A','B','C','D','E','F','G','H','I','J','K','L','M');

		//表头数组
		$tableheader = array('订单号','店铺名称','姓名','电话','身份证号码','推荐人姓名','推荐人账号','安置人姓名','安置人账号','审核通过时间','报单金额','状态','录单人员');
		
		//填充表头信息
		for($i = 0;$i < count($tableheader);$i++) {
			$objActSheet->setCellValue("$letter[$i]1","$tableheader[$i]");
		}

		$k=2;
		foreach ($report as $key => $value) {
			$objActSheet->setCellValue("A".$k,$value['order_id']);
			$objActSheet->setCellValue("B".$k,$value['recommend_shop']);
			$objActSheet->setCellValue("C".$k,$value['NAME']);
			$objActSheet->setCellValue("D".$k,$value['tel'].' ');
			$objActSheet->setCellValue("E".$k,$value['id_card'].' ');
			$objActSheet->setCellValue("F".$k,$value['recommend_name']);
			$objActSheet->setCellValue("G".$k,$value['recommend_user']);
			$objActSheet->setCellValue("H".$k,$value['place_name']);
			$objActSheet->setCellValue("I".$k,$value['place_user']);
			$objActSheet->setCellValue("J".$k,$value['update_time']);
			$objActSheet->setCellValue("K".$k,$value['qian']);

			$status='';
			switch ($value['status']) {
				case 2:
					$status='通过';
					break;
				case 6:
					$status='待三审';
					break;
				default:
					# code...
					break;
			}


			$objActSheet->setCellValue("L".$k,$status);
			$objActSheet->setCellValue("M".$k,$value['handle']);
			$k++;
		}

		//创建Excel输入对象
		$filename='订单报表'.date('Y-m-d');
		$write = new PHPExcel_Writer_Excel5($excel);
		header("Pragma: public");
		header("Expires: 0");
		header("Cache-Control:must-revalidate, post-check=0, pre-check=0");
		header("Content-Type:application/force-download");
		header("Content-Type:application/vnd.ms-execl");
		header("Content-Type:application/octet-stream");
		header("Content-Type:application/download");;
		header('Content-Disposition:attachment;filename="'.$filename.'.xls"');
		header("Content-Transfer-Encoding:binary");
		$write->save('php://output');

	}

	/**
	 * 订单二审
	 * @author 黄俊
	 * date 2016-12-14
	 */
	public function secondAudit(){

		//时间判断--0点至8点为禁止操作时间
		// if(1){
		// 	$this->error('系统维护中，该功能暂停使用！');
		// }

		//时间判断--0点至8点为禁止操作时间
		if( timeLimit(8) ){
			$this->error('0点至8点为禁止操作时间！');
		}
		// echo 123;die();
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
		$update_time=$orderGoods['update_time'];//用于乐观锁，版本控制
		// P($orderGoods);
		// die();

		#验证订货单与订单的状态
		// 验证订单状态
		if( $orderGoods['orderStatus'] != 5 ){
			$this->error('【订单】处于待二审状态，才可以进行二审！');
		}

		// 执行通过操作时，验证是否至少确认收过一次货
		if( $audit==1 && !SERVICE('DeliverGoods')->isFirstReceipt($order_id)  ){
			$this->error('客户至少确认收过一次货，才可以进行二审！');
		}

		// 验证订货单状态
		// if( $orderGoods['status'] != 3 ){
		// 	$this->error('【订货单】处于待二审状态，才可以进行二审！');
		// }

		#审核操作：1、通过 2、不通过
		if( $audit==1 ){//通过
			//改变订单的状态
			// $orderData['order_id']=$orderGoods['order_id'];
			$orderData['status']=6;//待三审
			$orderData['update_time']=date('Y-m-d H:i:s');

			$h1=M('order')->where('order_id='.$orderGoods['order_id'].' and update_time="'.$update_time.'"')->save($orderData);
			if(!$h1){
				$this->success('操作失败！',U('view',array('order_id'=>$order_id)));
				exit();
			}

			//改变订货单的状态
			$orderGoodsData['order_goods_id']=$orderGoods['order_goods_id'];
			// $orderGoodsData['status']=4;//待三审，【备注：由于后期需求调整，订单的相关审核操作，不在影响订货单状态，除飞订单不通过】
			$orderGoodsData['second_audit']=$_SESSION['user'];//记录二审操作员
			$orderGoodsData['update_time']=date('Y-m-d H:i:s');

			$h2=M('order_goods')->save($orderGoodsData);

			if( $h1 && $h2 ){
				#计算真实的团队业绩和相关人员的推荐奖

				// 会员信息
				$memberInfo=M('member')->where( array('id_card'=>$orderGoods['id_card']) )->find();
				// 奖励配置信息
				$reward_config=D('Web')->reward_config();
				//财务模型
				$finance=D('Finance');

				// 计算相关人员的推荐奖
				if(!$finance->rec_reward($orderGoods['money'],$memberInfo['recommend_user'],$reward_config)){
					$this->success('操作失败,推荐奖计算不成功！',U('view',array('order_id'=>$order_id)));
				}else{

					//计算该订单的推荐人，本月是否被推荐5次，在达到5次的门槛时，短信通知用户
					if( SERVICE('Finance')->rec_duanxin($memberInfo['recommend_user'],$reward_config) ){
						
						// 推荐人会员信息
						$recommend_user=M('member')->where( array('user'=>$memberInfo['recommend_user']) )->find();
						// P($recommend_user);die();
						rec_duanxin($recommend_user['tel'],$recommend_user['name'],$reward_config['rec_notice']);
					}
					// P($memberInfo);die();

					$this->success('操作成功，二审通过！',U('view',array('order_id'=>$order_id)));
				}
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
				
				// 会员信息
				$memberInfo=M('member')->where( array('id_card'=>$orderGoods['id_card']) )->find();

				/*扣除上线的业绩【将来】的业绩：总业绩，月业绩，周业绩*/
				D('Finance')->calculateFutureAch(0-$orderGoods['money'],$memberInfo);

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
					#进行降低点位操作
					D('Member')->down_point($orderGoods['money'],$orderGoods['id_card'],$orderGoods['orderType']);
					$this->success('操作成功，二审不通过！',U('view',array('order_id'=>$order_id)));
				}
			}else{
				$this->success('操作失败，订货单和订单更新不成功！',U('view',array('order_id'=>$order_id)));
			}
		}

	}

	/**
	 * 订单三审
	 * @author 黄俊
	 * date 2016-12-14
	 */
	public function thirdAudit(){
		//时间判断--0点至8点为禁止操作时间
		// if(1){
		// 	$this->error('系统维护中，该功能暂停使用！');
		// }

		//时间判断--0点至8点为禁止操作时间
		if( timeLimit(8) ){
			$this->error('0点至8点为禁止操作时间！');
		}

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
		$update_time=$orderGoods['update_time'];//用于乐观锁，版本控制

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
			// $orderData['order_id']=$orderGoods['order_id'];
			$orderData['status']=2;//通过
			$orderData['update_time']=date('Y-m-d H:i:s');

			$h1=M('order')->where('order_id='.$orderGoods['order_id'].' and update_time="'.$update_time.'"')->save($orderData);
			if(!$h1){
				$this->success('操作失败！',U('view',array('order_id'=>$order_id)));
				exit();
			}

			//改变订货单的状态
			$orderGoodsData['order_goods_id']=$orderGoods['order_goods_id'];
			// $orderGoodsData['status']=5;//通过【备注：由于后期需求调整，订单的相关审核操作，不在影响订货单状态，除飞订单不通过】
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
				if(!$finance->calculateAch($orderGoods['money'],$memberInfo)){
					$this->success('操作失败,真实业绩计算不成功！',U('view',array('order_id'=>$order_id)));
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
	 * 合同照片上传
	 * @author 黄俊
	 * date 2017-7-11
	 */
	public function contractUpload(){
		//权限判断
		if( !in_array($_SESSION['role'], array(1,2,3,4,5)) ){
			$this->error('权限不够！');
		}

		//必须上传文件，避免直接访问
		if(!isset($_FILES['picfile'])){
			$rs['status']=2;
			$rs['msg']='请选择上传文件！';
			echo '{status:"'.$rs['status'].'",msg:"'.$rs['msg'].'"}';//输出json格式的数据
			exit();
		}

		//检查文件是否上传，或者上传出错
        if($_FILES['picfile']['error']!=0){//不等于0，表示文件没上传，或上传出错
            $rs['status']=2;
			$rs['msg']='检查文件是否上传，或者上传出错！';
			echo '{status:"'.$rs['status'].'",msg:"'.$rs['msg'].'"}';//输出json格式的数据
			exit();
			// $this->ajaxReturn($rs);
        }

        // 检查图片格式是否满足
        $imgType=array('.gif','.png','.jpg');//限制格式
        $curType=substr($_FILES['picfile']['name'], strrpos($_FILES['picfile']['name'], '.'));

        //检查图片类型
        if(!in_array($curType, $imgType)){
            $rs['status']=2;
			$rs['msg']='图片类型不对！只能上传gif、png、jpg';
			echo '{status:"'.$rs['status'].'",msg:"'.$rs['msg'].'"}';//输出json格式的数据
			exit();
			$this->ajaxReturn($rs);
        }

        /*检查图片大小--1*1024*1024--1MB*/
        if( intval($_FILES['picfile']['size']) > 1*1024*1024 ){
            $rs['status']=2;
			$rs['msg']='图片这么大，会撑死的！不能超过1MB！';
			echo '{status:"'.$rs['status'].'",msg:"'.$rs['msg'].'"}';//输出json格式的数据
			exit();
			$this->ajaxReturn($rs);
        }

        /*上传图片*/
		import('ORG.Net.UploadFile');

		$upload=new UploadFile();

		//设置参数
		$upload->maxSize=1*1024*1024;//1MB
		// $upload->autoSub=true;//开启子目录保存
		// $upload->subType='date';//子目录创建方式

		//开始上传
		if(!$upload->upload('./upload/contract/')){
			$rs['status']=2;
			$rs['msg']='未知错误，文件上传失败';
			echo '{status:"'.$rs['status'].'",msg:"'.$rs['msg'].'"}';//输出json格式的数据
			exit();
			$this->ajaxReturn($rs);
		}
		
		//获得文件信息
		$info=$upload->getUploadFileInfo();
		$imgSrc=$info[0]['savepath'].$info[0]['savename'];
		$imgSrc=substr($imgSrc, 1);


		//成功，返回图片地址
		$rs['status']=1;
		$rs['imgSrc']=$imgSrc;
		$rs['msg']='成功！';
		echo '{status:"'.$rs['status'].'",msg:"'.$rs['msg'].'",imgSrc:"'.$rs['imgSrc'].'"}';//输出json格式的数据
		exit();
		$this->ajaxReturn($rs);
	}

	/**
	 * 合同照片下载
	 * @author 黄俊
	 * date 2017-7-11
	 */
	public function contractDownload(){
		//权限判断
		if( !in_array($_SESSION['role'], array(3,4)) ){
			$this->error('权限不够！');
		}

		//获取参数 
		$order_id=I('order_id',0,'intval');		
		$contract_type=I('contract_type',0,'intval');//下载类型	 1、下载第一张 2、下载第二张....依次类推

		// 订单信息
		$order=M('Order')->where(array("order_id"=>$order_id))->find();

		$file1='.'.$order['contract_img1'];
		$type1=get_extension($file1);

		$file2='.'.$order['contract_img2'];
		$type2=get_extension($file2);

		$file3='.'.$order['contract_img3'];
		$type3=get_extension($file3);

		$file4='.'.$order['contract_img4'];
		$type4=get_extension($file4);

		header("Content-type: octet/stream; charset=utf-8");


		//1、下载第一张
		if($contract_type==1){

			//判断是否是IE浏览器
			$userBrowser = $_SERVER['HTTP_USER_AGENT'];
			$filename=$order['name'].'1.'.$type1;

			if ( preg_match( '/MSIE/i', $userBrowser ) ){
			    $filename = urlencode($filename);
			}
			$filename = iconv('UTF-8', 'GBK//IGNORE', $filename);

			header("Content-disposition:attachment;filename=".$filename.";");
			header("Content-Length:".filesize($file1));
			readfile($file1);
		}

		//2、下载第二张
		if($contract_type==2){

			//判断是否是IE浏览器
			$userBrowser = $_SERVER['HTTP_USER_AGENT'];

			$filename=$order['name'].'2.'.$type2;//文件名

			//判断是否是IE浏览器
			if ( preg_match( '/MSIE/i', $userBrowser ) ){
			    $filename = urlencode($filename);
			}

			$filename = iconv('UTF-8', 'GBK//IGNORE', $filename);


			header("Content-disposition:attachment;filename=".$filename.";");
			header("Content-Length:".filesize($file2));
			readfile($file2);	
		}

		//3、下载第三张
		if($contract_type==3){

			//判断是否是IE浏览器
			$userBrowser = $_SERVER['HTTP_USER_AGENT'];

			$filename=$order['name'].'3.'.$type3;//文件名

			//判断是否是IE浏览器
			if ( preg_match( '/MSIE/i', $userBrowser ) ){
			    $filename = urlencode($filename);
			}

			$filename = iconv('UTF-8', 'GBK//IGNORE', $filename);


			header("Content-disposition:attachment;filename=".$filename.";");
			header("Content-Length:".filesize($file3));
			readfile($file3);	
		}

		//4、下载第四张
		if($contract_type==4){

			//判断是否是IE浏览器
			$userBrowser = $_SERVER['HTTP_USER_AGENT'];

			$filename=$order['name'].'4.'.$type4;//文件名

			//判断是否是IE浏览器
			if ( preg_match( '/MSIE/i', $userBrowser ) ){
			    $filename = urlencode($filename);
			}

			$filename = iconv('UTF-8', 'GBK//IGNORE', $filename);


			header("Content-disposition:attachment;filename=".$filename.";");
			header("Content-Length:".filesize($file4));
			readfile($file4);	
		}
		

		
		exit;
	}

	/**
	 * 根据订单金额，反扣推荐奖
	 * @author 黄俊
	 * date 2017-10-16
	 */
	public function fan_rec_reward(){
		// 奖励配置信息
		// $reward_config=D('Web')->reward_config();
		// $order=M()->query('SELECT * FROM `order` WHERE `status` IN (2,6) AND update_time BETWEEN "2017-10-09 00:00:00" AND "2017-10-15 23:59:59"');
		// $total=0;
		// foreach ($order as $key => $value) {
		// 	D('Finance')->fan_rec_reward($value['money'],$value['recommend_user'],$reward_config);
		// 	$total++;
		// }

		// echo $total."<br>";

		// echo "成功！";
	}

}
?>