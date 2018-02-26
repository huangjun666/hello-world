<?php
/**
 * 会员模块
 * @author 黄俊
 * date 2016-6-28
 */
class MemberAction extends BaseAction{

	/**
	 * 首页
	 * @author 黄俊
	 * date 2016-6-28
	 */
	public function index(){

		//权限判断
		if( in_array($_SESSION['role'], array(1,2)) ){
			$this->redirect('view');
		}

		if(IS_POST){

			$user=I('user','');
			$where='';
			if(!empty($user)){
				$where.="`user` LIKE '%".$user."%' OR `name` LIKE '%".$user."%'";
			}else{
				$this->redirect('index');
			}
			
			$member=M('member')->where($where)->order('status ASC,add_time DESC,update_time DESC')->select();

			$this->page=1;//获得页码
			$this->sort=0;//序号
			$this->assign('member',$member);
			$this->display();

		}else{
			import('ORG.Util.Page');//引入分页类

			/*分页*/
			$count=M('member')->count();//总数量
			$page=new Page($count,20);
			$limit=$page->firstRow.','.$page->listRows;

			$member=M('member')->where("`user` NOT IN('admin','LSMT')")->limit($limit)->order('status ASC,add_time DESC,update_time DESC')->select();

			$this->page=$page->show();//获得页码
			$this->sort=$page->firstRow;//序号
			$this->assign('member',$member);
			$this->display();	
		}
		
	}

	/**
	 * 添加会员
	 * @author 黄俊
	 * date 2016-6-28
	 */
	public function add(){

		//权限判断
		if( !in_array($_SESSION['role'], array(4)) ){
			$this->error('权限不够！');
		}

		if(IS_POST){

			/*实例化模型*/
			$member=M('member');

			/*获取参数*/
			$data['user']=I('user','');

			//用户名
			if( !is_user($data['user']) ){
				$this->assign('error','用户名非法');
				$this->display('add');
				exit();
			}

			//用户名是否存在
			if( $member->where(array('user'=>$data['user']))->count() > 0 ){
				$this->assign('error','用户名已经存在');
				$this->display('add');
				exit();
			}

			//检查密码是否为空，并且是否一致
			$pswd1=I('password1','');
			$pswd2=I('password2','');
			if( empty($pswd1) ){
				$this->assign('error','密码不可以为空');
				$this->display('add');
				exit();
			}

			if( $pswd1 != $pswd2 ){
				$this->assign('error','密码不一致');
				$this->display('add');
				exit();
			}

			$data['password']=encrypt($pswd1);

			$data['shop']=I('shop','');
			//店铺不可以为空
			if( empty($data['shop']) ){
				$this->assign('error','店铺不可以为空');
				$this->display('add');
				exit();
			}

			$data['team_tag']=I('team_tag','');
			//团队标签不可以为空，并且格式要符合
			if( empty($data['team_tag']) ){
				$this->assign('error','团队标签不规范');
				$this->display('add');
				exit();
			}

			$data['name']=I('name','');
			//姓名不可以为空
			if( empty($data['name']) ){
				$this->assign('error','姓名不可以为空');
				$this->display('add');
				exit();
			}

			$data['sex']=I('sex',0,'intval');
			//检查性别是否合法
			if( $data['sex'] != 1 && $data['sex'] != 2 ){
				$this->assign('error','性别不合法');
				$this->display('add');
				exit();
			}

			$data['id_card']=I('id_card','');
			//身份证号
			if( !is_idCard($data['id_card']) ){
				$this->assign('error','身份证号非法');
				$this->display('add');
				exit();
			}

			//身份证号是否已经被注册过
			if( $member->where(array('id_card'=>$data['id_card']))->count() > 0 ){
				$this->assign('error','身份证号已经被注册过');
				$this->display('add');
				exit();
			}

			$data['ProvinceID']=I('province',0,'intval');
			$data['CityID']=I('city',0,'intval');
			$data['DistrictID']=I('district',0,'intval');
			//身份证所在地区必选
			if ( $data['ProvinceID'] == 0 || $data['CityID'] == 0 || $data['DistrictID'] == 0 ) {
				$this->assign('error','身份证所在地区必选');
				$this->display('add');
				exit();
			}

			$data['adress']=I('adress','');
			//详细地址不可以为空
			if( empty($data['adress']) ){
				$this->assign('error','详细地址不可以为空');
				$this->display('add');
				exit();
			}

			$data['tel']=I('tel','');
			//移动电话
			if( !is_tel($data['tel']) ){
				$this->assign('error','移动电话不对');
				$this->display('add');
				exit();
			}


			$data['bank_name']=I('bank_name','');
			$data['bank_user']=I('bank_user','');
			$data['bank_card']=I('bank_card',0);
			//银行信息必填
			if( empty($data['bank_name']) || empty($data['bank_user']) || empty($data['bank_card']) ){
				$this->assign('error','银行相关信息必填');
				$this->display('add');
				exit();
			}

			$data['email']=I('email','');
			//验证邮箱
			if( !is_email($data['email']) ){
				$this->assign('error','邮箱格式不对');
				$this->display('add');
				exit();
			}

			$data['fixed_tel']=I('fixed_tel','');
			$data['recommend_user']='LSMT';
			$data['pid']=2;
			$data['update_time']=date('Y-m-d H:i:s');
			//保存
			$member_id=$member->add($data);
			if($member_id){
				D('Member')->joinLSMT($member_id);
				$this->success('添加成功！',U('index'));
			}else{
				$this->assign('error','非法错误，请重试');
				$this->display('add');
			}
			
		}else{
			$this->display('add');
		}
		
	}

	/**
	 * 编辑会员
	 * @author 黄俊
	 * date 2016-6-28
	 */
	public function edit(){
		//权限判断
		if( !in_array($_SESSION['role'], array(4)) ){
			$this->error('权限不够！');
		}

		if(IS_POST){
			//获取参数
			$data['member_id']=I('member_id',0,'intval');

			$data['shop']=I('shop','');
			//店铺不可以为空
			if( empty($data['shop']) ){
				$this->assign('error','店铺不可以为空');
				$this->display('edit');
				exit();
			}

			$data['name']=I('name','');
			//姓名不可以为空
			if( empty($data['name']) ){
				$this->assign('error','姓名不可以为空');
				$this->display('edit');
				exit();
			}

			$data['password']=I('password','');
			//姓名不可以为空
			if( empty($data['password']) ){
				$this->assign('error','密码不可以为空');
				$this->display('edit');
				exit();
			}

			$data['password']=encrypt($data['password']);//加密

			$data['sex']=I('sex',0,'intval');
			//检查性别是否合法
			if( $data['sex'] != 1 && $data['sex'] != 2 ){
				$this->assign('error','性别不合法');
				$this->display('edit');
				exit();
			}

			$data['point']=I('point',0,'intval');
			//检查点位信息是否合法
			if( !in_array($data['point'], array(0,1,2,4,6)) ){
				$this->assign('error','点位信息不合法');
				$this->display('edit');
				exit();
			}

			$data['ProvinceID']=I('province',0,'intval');
			$data['CityID']=I('city',0,'intval');
			$data['DistrictID']=I('district',0,'intval');
			//身份证所在地区必选
			if ( $data['ProvinceID'] == 0 || $data['CityID'] == 0 || $data['DistrictID'] == 0 ) {
				$this->assign('error','身份证所在地区必选');
				$this->display('edit');
				exit();
			}

			$data['adress']=I('adress','');
			//详细地址不可以为空
			if( empty($data['adress']) ){
				$this->assign('error','详细地址不可以为空');
				$this->display('edit');
				exit();
			}

			$data['tel']=I('tel','');
			//移动电话
			if( !is_tel($data['tel']) ){
				$this->assign('error','移动电话不对');
				$this->display('edit');
				exit();
			}


			$data['bank_name']=I('bank_name','');
			$data['bank_user']=I('bank_user','');
			$data['bank_card']=I('bank_card',0);
			//银行信息必填
			if( empty($data['bank_name']) || empty($data['bank_user']) || empty($data['bank_card']) ){
				$this->assign('error','银行相关信息必填');
				$this->display('edit');
				exit();
			}

			$data['email']=I('email','');
			//验证邮箱
			if( !is_email($data['email']) ){
				$this->assign('error','邮箱格式不对');
				$this->display('edit');
				exit();
			}

			// 检查推荐人是否存在
			$data['recommend_user']=I('recommend_user','');
			$recommend_user=M('member')->where(array('user'=>$data['recommend_user']))->find();
			if( empty($recommend_user) ){
				$this->assign('error','检查推荐人不存在，请检查！');
				$this->display('edit');
				exit();
			}

			// 推荐人不可以是自己
			if( $data['recommend_user'] == I('user','') ){
				$this->assign('error','推荐人不可以是自己！');
				$this->display('edit');
				exit();
			}

			// 如果馆主不为空，检查馆主是否存在
			$data['handle']=I('handle','');

			if( !empty($data['handle']) ){

				$handle=M('member')->where(array('user'=>$data['handle']))->find();

				if( empty($handle) ){
					$this->assign('error',$data['handle'].',馆主不存在，请检查！');
					$this->display('edit');
					exit();
				}

				if( $handle['role'] != 2 ){
					$this->assign('error',$data['handle'].',该会员不是馆主！');
					$this->display('edit');
					exit();
				}
			}
			

			
			$data['fixed_tel']=I('fixed_tel','');
			$data['update_time']=date('Y-m-d H:i:s');//更新时间


			//会员信息
			$member=M('member')->where( array('member_id'=>$data['member_id']) )->find();
			if(empty($member)){
				$this->error('非法操作！');
			}

			/***以上是接收以及验证字段的代码***/
			// 会员编辑功能变化：
			// 凡是member_audit中存在的会员字段，在编辑时，都不会实时生效，需要另一个管理员进行审核之后，才会生效
			$auditField=array('bank_name','bank_user','bank_card','point','recommend_user','handle');
			$auditData=array();//审核表数据

			//检测会员信息里的审核字段，是否发生改变
			foreach ($auditField as $key => $value) {
				//只要有一个发生改变，则生成一个审核单
				if($member[$value] !=$data[$value] ){
					//审核单信息
					$auditData['user']=$member['user'];
					$auditData['name']=$member['name'];
					$auditData['bank_name_before']=$member['bank_name'];//开户行
					$auditData['bank_name']=$data['bank_name'];
					unset($data['bank_name']);
					$auditData['bank_user_before']=$member['bank_user'];//开户名
					$auditData['bank_user']=$data['bank_user'];
					unset($data['bank_user']);
					$auditData['bank_card_before']=$member['bank_card'];//银行账号
					$auditData['bank_card']=$data['bank_card'];
					unset($data['bank_card']);
					$auditData['point_before']=$member['point'];//点位信息
					$auditData['point']=$data['point'];
					unset($data['point']);
					$auditData['recommend_user_before']=$member['recommend_user'];//推荐人会员账号
					$auditData['recommend_user']=$data['recommend_user'];
					unset($data['recommend_user']);
					$auditData['handle_before']=$member['handle'];//所属馆主会员帐号
					$auditData['handle']=$data['handle'];
					unset($data['handle']);
					$auditData['member_status_before']=$member['status'];//会员状态
					$auditData['member_status']=$member['status'];
					$auditData['audit_user']=$_SESSION['user'];
					$auditData['audit_name']=$_SESSION['name'];
					$auditData['update_time']=date('Y-m-d H:i:s');//更新时间
					break;
				}
			}
			// P($auditData);
			// P($data);
			// die();
			// 财务表同时更新
			$financeData['name']=$data['name'];
			$financeData['tel']=$data['tel'];
			// $financeData['bank_user']=$data['bank_user'];
			// $financeData['bank_name']=$data['bank_name'];
			// $financeData['bank_card']=$data['bank_card'];
			$financeData['update_time']=date('Y-m-d H:i:s');//更新时间

			M('finance')->where(array('user'=>$member['user']))->save($financeData);

			//保存
			if( M('member')->save($data) ){
				if(empty($auditData)){
					$this->success('编辑成功！',U('index'));
				}else{
					if(M('member_audit')->add($auditData)){
						$this->success('成功：本次修改,涉及敏感信息，部分信息需要其他超级管理员审核，才能生效！',U('index'));
					}else{
						$this->error('编辑失败，请重试！',U('index'));
					}
				}
				
			}else{
				$this->error('编辑失败，请重试！',U('index'));
			}
		}else{
			//获得会员ID
			$member_id=I('member_id',0,'intval');

			$member=D('Member')->getUser($member_id);
			// P($member);die();
			$this->assign('member',$member);
			$this->display('edit');
		}
		
	}

	/**
	 * 冻结会员
	 * @author 黄俊
	 * date 2016-6-28
	 */
	public function stop(){
		//权限判断
		if( !in_array($_SESSION['role'], array(4)) ){
			$this->error('权限不够！');
		}
		//获取参数
		$data['member_id']=I('member_id',0,'intval');
		$data['status']=2;
		$data['update_time']=date('Y-m-d H:i:s');//更新时间

		//会员信息
		$member=M('member')->where( array('member_id'=>$data['member_id']) )->find();
		if(empty($member)){
			$this->error('非法操作！');
		}

		//审核单信息
		$auditData=array();
		$auditData['user']=$member['user'];
		$auditData['name']=$member['name'];

		$auditData['bank_name_before']=$member['bank_name'];//开户行
		$auditData['bank_name']=$member['bank_name'];

		$auditData['bank_user_before']=$member['bank_user'];//开户名
		$auditData['bank_user']=$member['bank_user'];

		$auditData['bank_card_before']=$member['bank_card'];//银行账号
		$auditData['bank_card']=$member['bank_card'];

		$auditData['point_before']=$member['point'];//点位信息
		$auditData['point']=$member['point'];

		$auditData['recommend_user_before']=$member['recommend_user'];//推荐人会员账号
		$auditData['recommend_user']=$member['recommend_user'];

		$auditData['handle_before']=$member['handle'];//所属馆主会员帐号
		$auditData['handle']=$member['handle'];

		$auditData['member_status_before']=$member['status'];//会员状态
		$auditData['member_status']=$data['status'];

		$auditData['audit_user']=$_SESSION['user'];
		$auditData['audit_name']=$_SESSION['name'];
		$auditData['update_time']=date('Y-m-d H:i:s');//更新时间

		//保存
		if( M('member_audit')->add($auditData) ){
			$this->success('操作成功，请等待超级管理员审核！',U('index'));
		}else{
			$this->error('操作失败，请重试！',U('index'));
		}
	}

	/**
	 * 删除会员
	 * @author 黄俊
	 * date 2016-6-28
	 */
	public function del(){
		//权限判断
		if( !in_array($_SESSION['role'], array(4)) ){
			$this->error('没有权限！');
		}


		$member_id=I('member_id',0,'intval');//会员ID
		if( !$member_id ){
			$this->error('会员ID 不能为空！');
		}
		#1、业绩
		#2、推荐奖
		#3、辅导树状八节点
		#4、订单
		#5、本身
		$member=M('member');//会员模型
		$m=new Model();//实例化模型
		$reward_config=D('Web')->reward_config();//奖励配置

		//会员信息
		$memberInfo=$member->where(array('member_id'=>$member_id))->find();

		// 会员是否存在
		if( empty($memberInfo) ){
			$this->error('会员不存在！');
		}

		$tree=$memberInfo['pid'].','.$memberInfo['tree'];//团队树
		$update_time=date('Y-m-d H:i:s');//更新时间

		//计算该会员的报单总额
		$orderSql='select sum(money) qian from `order` where id_card="'.$memberInfo['id_card'].'" and status in(2,4,5,6)';
		$orderRs=$m->query($orderSql);
		$money=$orderRs[0]['qian'];

		/*1、清除上级的业绩*/
		$sql='update member set future_total_ach=future_total_ach-'.$money.',future_month_ach=future_month_ach-'.$money.',future_week_ach=future_week_ach-'.$money.',update_time="'.$update_time.'" where member_id in ('.$tree.')';

		if( !$m->execute($sql) ){
			$this->error('清除上级的业绩失败！');
		}

		/*2、清楚推荐奖*/
		D('Finance')->clear_rec_reward($memberInfo['recommend_user'],$money,$reward_config);


		/*3、清楚八级辅导节点*/
		//辅导奖辐射级别
		$level=$reward_config['fd_level'];

		//安置人
		$pid=$memberInfo['pid'];

		for ($i=0; $i < $level; $i++) {

			# 获得安置的辅导八级节点树
			$coach_tree = $member->where(array('member_id'=>$pid))->getField('coach_tree');
			$coach_tree=str_replace(','.$memberInfo['member_id'], '', $coach_tree);


			$sql=' update member set coach_tree="'.$coach_tree.'" where member_id='.$pid;
			$m->execute($sql);

			# 获取该安置人的上一级安置人
			$sql='select pid from member where member_id='.$pid;
			$rs=$m->query($sql);

			$pid=$rs[0]['pid'];//上一级安置人

			if( empty($pid) ){//如果没有上一级安置人，退出循环
				break;
			}

		}

		/*4、删除订单*/
		$orderWhere=' id_card = "'.$memberInfo['id_card'].'" and status in (2,4,5,6) ';
		M('order')->where($orderWhere)->delete();

		/*5、删除本身*/
		if( $member->where('member_id='.$member_id)->delete() ){
			echo '成功！';
		}else{
			echo '失败！';
		}

	}

	/**
	 * 会员联系方式修改
	 * @author 黄俊
	 * date 2016-6-28
	 */
	public function editInfo(){

		if(IS_POST){

			//获取参数
			$data['member_id']=$_SESSION['uid'];

			$data['tel']=I('tel','');
			//移动电话
			if( !is_tel($data['tel']) ){
				$this->assign('error','移动电话不对');
				$this->display();
				exit();
			}

			$data['email']=I('email','');
			//验证邮箱
			if( !is_email($data['email']) ){
				$this->assign('error','邮箱格式不对');
				$this->display();
				exit();
			}
			
			$data['fixed_tel']=I('fixed_tel','');
			$data['update_time']=date('Y-m-d H:i:s');//更新时间

			//保存
			if( M('member')->save($data) ){
				$this->success('编辑成功！',U('index'));
			}else{
				$this->error('编辑失败，请重试！',U('index'));
			}
		}else{
			//获得会员ID
			$member_id=$_SESSION['uid'];

			$member=D('Member')->getUser($member_id);
			// P($member);die();
			$this->assign('member',$member);
			$this->display();
		}
	}

	/**
	 * 激活会员
	 * @author 黄俊
	 * date 2016-6-28
	 */
	public function start(){

		//权限判断
		if( !in_array($_SESSION['role'], array(4)) ){
			$this->error('权限不够！');
		}

		//获取参数
		$data['member_id']=I('member_id',0,'intval');
		$data['status']=1;
		$data['update_time']=date('Y-m-d H:i:s');//更新时间

		//会员信息
		$member=M('member')->where( array('member_id'=>$data['member_id']) )->find();
		if(empty($member)){
			$this->error('非法操作！');
		}

		//审核单信息
		$auditData=array();
		$auditData['user']=$member['user'];
		$auditData['name']=$member['name'];

		$auditData['bank_name_before']=$member['bank_name'];//开户行
		$auditData['bank_name']=$member['bank_name'];

		$auditData['bank_user_before']=$member['bank_user'];//开户名
		$auditData['bank_user']=$member['bank_user'];

		$auditData['bank_card_before']=$member['bank_card'];//银行账号
		$auditData['bank_card']=$member['bank_card'];

		$auditData['point_before']=$member['point'];//点位信息
		$auditData['point']=$member['point'];

		$auditData['recommend_user_before']=$member['recommend_user'];//推荐人会员账号
		$auditData['recommend_user']=$member['recommend_user'];

		$auditData['handle_before']=$member['handle'];//所属馆主会员帐号
		$auditData['handle']=$member['handle'];

		$auditData['member_status_before']=$member['status'];//会员状态
		$auditData['member_status']=$data['status'];

		$auditData['audit_user']=$_SESSION['user'];
		$auditData['audit_name']=$_SESSION['name'];
		$auditData['update_time']=date('Y-m-d H:i:s');//更新时间

		//保存
		if( M('member_audit')->add($auditData) ){
			$this->success('操作成功，请等待超级管理员审核！',U('index'));
		}else{
			$this->error('操作失败，请重试！',U('index'));
		}
	}

	/**
	 * 会员详情
	 * @author 黄俊
	 * date 2016-6-28
	 */
	public function view(){

		//获取参数 
		$member_id=I('member_id',0,'intval');

		if(empty($member_id)){
			$member_id=$_SESSION['uid'];
		}

		//如果不是系统管理员
		if( $_SESSION['role'] <= 2 && $_SESSION['uid'] != $member_id ){
			$this->error('非法操作！');
		}

		$member=D('Member')->getUser($member_id);
		$this->sh_member=SERVICE('Sh')->getMemberView(' member_id='.$member_id);

		$this->member=$member;
		$this->display('view');
	}

	/**
	 * 密码修改
	 * @author 黄俊
	 * date 2016-6-28
	 */
	public function pwd(){

		if(IS_POST){

			//验证两次输入的密码是否一致
			$newpwd1=I('newpwd1','');
			$newpwd2=I('newpwd2','');

			if( empty($newpwd1) ){

				$this->error='新密码不可以为空';
				$this->display();
				exit();

			}

			if( $newpwd1 != $newpwd2 ){

				$this->error='两次输入的密码不一致';
				$this->display();
				exit();

			}

			//实例化模型
			$member=M('member');

			//获取会员信息
			$user=$member->where(array('member_id'=>$_SESSION['uid']))->find();

			//验证密码
			if( $user['password'] != I('oldpwd','','encrypt') ){

				$this->error='密码错误！';
				$this->display();
				exit();

			}

			$data['member_id']=$_SESSION['uid'];
			$data['password']=encrypt($newpwd1);
			$data['update_time']=date('Y-m-d H:i:s');//更新时间

			//保存新密码
			if( $member->save($data) ){
				$this->success('密码修改成功！',U('index'));
			}else{
				$this->error('网络不好，请重新操作！');
			}




		}else{
			$this->display();
		}
	}

	/**
	 * 会员下载
	 * @author 黄俊
	 * date 2016-6-28
	 */
	public function download(){
		//权限判断
		if( !in_array($_SESSION['role'], array(4)) ){
			$this->error('权限不够！');
		} 

		/*脚本设置*/
		set_time_limit(0);
		ini_set('memory_limit', '512M');

		$memberInfo=M('member')->where("`user` NOT IN('admin','LSMT') and status=1")->select();


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
		$objActSheet->getColumnDimension('F')->setWidth(10);
		$objActSheet->getColumnDimension('G')->setWidth(20);
		$objActSheet->getColumnDimension('H')->setWidth(30);
		$objActSheet->getColumnDimension('I')->setWidth(15);
		$objActSheet->getColumnDimension('J')->setWidth(15);
		$objActSheet->getColumnDimension('K')->setWidth(15);
		$objActSheet->getColumnDimension('L')->setWidth(15);
		$objActSheet->getColumnDimension('M')->setWidth(15);
		$objActSheet->getColumnDimension('N')->setWidth(20);
		

		//Excel表格式
		$letter = array('A','B','C','D','E','F','G','H','I','J','K','L','M','N');

		//表头数组
		$tableheader = array('序号','会员账号','姓名','电话','身份证号','开户名','开户行','银行账号','地址','添加时间','点位信息','角色','推荐人会员账号','安置人id');
		
		//填充表头信息
		for($i = 0;$i < count($tableheader);$i++) {
			$objActSheet->setCellValue("$letter[$i]1","$tableheader[$i]");
		}

		$k=2;
		foreach ($memberInfo as $key => $value) {
			$objActSheet->setCellValue("A".$k,$value['member_id']);
			$objActSheet->setCellValue("B".$k,$value['user']);
			$objActSheet->setCellValue("C".$k,$value['name']);
			$objActSheet->setCellValue("D".$k,$value['tel'].' ');
			$objActSheet->setCellValue("E".$k,$value['id_card'].' ');
			$objActSheet->setCellValue("F".$k,$value['bank_user']);
			$objActSheet->setCellValue("G".$k,$value['bank_name']);
			$objActSheet->setCellValue("H".$k,$value['bank_card'].' ');
			$objActSheet->setCellValue("I".$k,$value['adress']);
			$objActSheet->setCellValue("J".$k,$value['add_time'].' ');
			$objActSheet->setCellValue("K".$k,$value['point'].'万');

			$role='';
			switch ($value['role']) {
				case 1:
					$role='普通经销商';
					break;
				case 2:
					$role='馆主';
					break;
				case 3:
					$role='财务';
					break;
				case 4:
					$role='超级管理员';
					break;
				case 5:
					$role='仓库管理员';
					break;
				
				default:
					$role='未知';
					break;
			}

			$objActSheet->setCellValue("L".$k,$role);
			$objActSheet->setCellValue("M".$k,$value['recommend_user']);
			$objActSheet->setCellValue("N".$k,$value['pid'].' ');
			$k++;
		}

		//创建Excel输入对象
		$filename='会员信息表'.date('Y-m-d');
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
	 * 本月星级董事
	 * @author 黄俊
	 * date 2017-8-4
	 */
	public function star(){
		//权限判断
		if( !in_array($_SESSION['role'], array(4)) ){
			$this->error('权限不够！');
		}

		$time=I('time',date('Y-m-d',time()),'addOneSecond');//时间节点

		import('ORG.Util.Page');//引入分页类

		/*判断是否查询member表*/
		if( SERVICE('Tree')->isMemberSelect(2,$time) ){
			/*分页*/
			$count=M('member')->where(" team_star IN(1,2,3,4,5) ")->count();//总数量
			$page=new Page($count,20);
			$limit=$page->firstRow.','.$page->listRows;

			$member=M('member')->where(" team_star IN(1,2,3,4,5) ")->limit($limit)->select();

			$this->page=$page->show();//获得页码
			$this->sort=$page->firstRow;//序号
			$this->assign('member',$member);
			$this->display();
		}else{
			//条件
			$where=' "'.$time.'" BETWEEN start_time AND end_time ';

			/*分页*/
			$count=M('team_star_log')->where($where)->count();//总数量
			$page=new Page($count,20);
			$limit=$page->firstRow.','.$page->listRows;

			$member=M('team_star_log')->where($where)->limit($limit)->select();

			$this->page=$page->show();//获得页码
			$this->sort=$page->firstRow;//序号
			$this->assign('member',$member);
			$this->display();
		}

		
		
	}

	/**
	 * 本月星级董事下载
	 * @author 黄俊
	 * date 2017-8-6
	 */
	public function starDownload(){
		//权限判断
		if( !in_array($_SESSION['role'], array(4)) ){
			$this->error('权限不够！');
		}

		$time=I('time',date('Y-m-d',time()),'addOneSecond');//时间节点

		/*判断是否查询member表*/
		if( SERVICE('Tree')->isMemberSelect(2,$time) ){
			$member=M('member')->where(" team_star IN(1,2,3,4,5) ")->select();
		}else{
			//条件
			$where=' "'.$time.'" BETWEEN start_time AND end_time ';
			$member=M('team_star_log')->where($where)->select();
		}

		//引入PHPExcel库文件
		import('Class.PHPExcel',APP_PATH);

		//创建对象
		$excel = new PHPExcel();
		$objActSheet = $excel->getActiveSheet();
		//设置宽度   
		$objActSheet->getColumnDimension('A')->setWidth(20);
		$objActSheet->getColumnDimension('B')->setWidth(20);
		$objActSheet->getColumnDimension('C')->setWidth(10);
		$objActSheet->getColumnDimension('D')->setWidth(20);
		$objActSheet->getColumnDimension('E')->setWidth(30);
		$objActSheet->getColumnDimension('F')->setWidth(10);
		

		//Excel表格式
		$letter = array('A','B','C','D','E','F');

		//表头数组
		$tableheader = array('会员账号','姓名','性别','移动电话','邮箱','星级');
		
		//填充表头信息
		for($i = 0;$i < count($tableheader);$i++) {
			$objActSheet->setCellValue("$letter[$i]1","$tableheader[$i]");
		}

		$k=2;
		foreach ($member as $key => $value) {
			$objActSheet->setCellValue("A".$k,$value['user']);
			$objActSheet->setCellValue("B".$k,$value['name']);
			//性别
			$sex=$value['sex']==1?"男":"女";
			$objActSheet->setCellValue("C".$k,$sex);
			$objActSheet->setCellValue("D".$k,$value['tel'].' ');
			$objActSheet->setCellValue("E".$k,$value['email'].' ');
			$objActSheet->setCellValue("F".$k,$value['team_star']."星");
			
			$k++;
		}

		//创建Excel输入对象
		$filename='本月星级董事'.date('Y-m-d');
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
	 * 会员信息审核列表
	 * @author 黄俊
	 * date 2017-11-4
	 */
	public function auditList(){
		//权限判断
		if( !in_array($_SESSION['role'], array(4)) ){
			$this->error('权限不够！');
		}

		import('ORG.Util.Page');//引入分页类

		/*分页*/
		$count=M('member_audit')->count();//总数量
		$page=new Page($count,20);
		$limit=$page->firstRow.','.$page->listRows;

		$member_audit=M('member_audit')->limit($limit)->order('field(`status`,1,2,3),add_time DESC')->select();

		$this->page=$page->show();//获得页码
		$this->sort=$page->firstRow;//序号
		$this->assign('member_audit',$member_audit);
		$this->display();
	}

	/**
	 * 会员信息审核详情
	 * @author 黄俊
	 * date 2017-11-4
	 */
	public function auditView(){
		//权限判断
		if( !in_array($_SESSION['role'], array(4)) ){
			$this->error('权限不够！');
		}

		// 接收字段
		$member_audit_id=I('id',0,"intval");
		//验证字段
		if($member_audit_id==0){
			$this->error('非法访问！');
		}

		$member_audit=M('member_audit')->where(array('id'=>$member_audit_id))->find();
		//验证合法
		if(empty($member_audit)){
			$this->error('非法访问！');
		}

		$this->assign('member_audit',$member_audit);
		$this->display();
	}

	/**
	 * 会员信息审核
	 * @author 黄俊
	 * date 2017-11-4
	 */
	public function pass(){
		//权限判断
		if( !in_array($_SESSION['role'], array(4)) ){
			$this->error('权限不够！');
		}

		//获取字段
		$rights_audit_id=I('id',0,'intval');
		$audit=I('audit',1,'intval');//1、不通过，2、通过

		$rights_audit=M('member_audit')->where(array('id'=>$rights_audit_id))->find();

		//审核单不存在
		if(empty($rights_audit)){
			$this->error("非法操作！");
			exit();
		}

		//检查是否被审核过
		if($rights_audit['status']!=1){
			$this->error("已经审核过！");
			exit();
		}

		//对比提交人和审核人，
		if($rights_audit['audit_user']==$_SESSION['user']){
			$this->error("请交给其他超级管理员审核！");
			exit();
		}

		/*审核-审核单*/
		$auditData['id']=$rights_audit_id;
		$auditData['handle_user']=$_SESSION['user'];//审核人员帐号
		$auditData['handle_name']=$_SESSION['name'];//审核人员姓名
		$auditData['status']=$audit==2?2:3;//只有$audit=2，才通过，其他都是不通过
		$auditData['update_time']=date('Y-m-d H:i:s');//更新时间

		M('member_audit')->save($auditData);

		/*会员信息变更*/
		if($audit==2){//审核通过时，会员信息变化

			
			// 财务报表信息变更
			$financeData['bank_user']=$rights_audit['bank_user'];
			$financeData['bank_name']=$rights_audit['bank_name'];
			$financeData['bank_card']=$rights_audit['bank_card'];
			$financeData['update_time']=date('Y-m-d H:i:s');//更新时间

			M('finance')->where(array('user'=>$rights_audit['user']))->save($financeData);

			//会员信息变更
			$where=array(array('user'=>$rights_audit['user']));

			$memberData['bank_name']=$rights_audit['bank_name'];
			$memberData['bank_user']=$rights_audit['bank_user'];
			$memberData['bank_card']=$rights_audit['bank_card'];
			$memberData['point']=$rights_audit['point'];
			$memberData['recommend_user']=$rights_audit['recommend_user'];
			$memberData['status']=$rights_audit['member_status'];
			$memberData['update_time']=date('Y-m-d H:i:s');//更新时间

			//保存
			if(!M('member')->where($where)->save($memberData)){

				//数据库操作失败，提示客户端
				$this->error("网络不好，操作失败！");
				exit();
			}
		}

		//根据不同的操作，给不同的提示信息
		if($audit==2){
			$this->success('审核通过，会员信息发生改变！');
		}else{
			$this->success('审核不通过！');
		}
	}

}
?>