<?php
/**
 * 登录模块
 * @author 黄俊
 * date 2016-6-28
 */
class LoginAction extends BaseAction{

	/**
	 * 登录首页
	 * @author 黄俊
	 * date 2016-6-28
	 */
	public function index(){

		if(IS_POST){

			// //过滤部分用户
			// if( userFilter(I('uname')) ){
			// 	$this->error='温馨提示：系统正在维护中！';
			// 	$this->display();
			// 	exit();
			// }
			
			//实例化模型
			$member=D('Member');

			//验证用户名和密码是否正确
			$user=M('member')->where(array('user'=>I('uname')))->find();

			if( empty($user) ){
				$this->error='错误提示：用户名不存在！';
				$this->display();
				exit();
			}

			// 检查是否是超级管理员
			// if( $user['role'] == 4 ){
			// 	$this->error='错误提示：用户名不存在！';
			// 	$this->display();
			// 	exit();
			// }

			//检查账号是否被冻结
			if( $user['status'] == 2 ){
				$this->error='错误提示：用户被冻结！';
				$this->display();
				exit();
			}

			//检查限制
			$limit=$member->login_limit($user['user'],5);

			if( $limit <= 0 ){
				$this->error='错误提示：账号暂时被限制，请稍候再试！';
				$this->display();
				exit();
			}


			//检查密码
			if( $user['password'] != I('pwd','','encrypt') ){

				//记录登录日志
				$member->login_log($user['user'],2);

				$this->error='错误提示：密码错误！还可以进行'.($limit-1).'次登录';
				$this->display();
				exit();
			}

			//记录登录日志
			$member->login_log($user['user']);

			session('uid',$user['member_id']);
			session('user',$user['user']);
			session('name',$user['name']);
			session('role',$user['role']);
			session('id_card',$user['id_card']);

			if($user['role']==5){//仓库管理员跳转到仓库
				Redirect('/Depot/index');
			}else{//其余人跳转到财务中心
				Redirect('/');
			}
			
		}else{

			$this->display();
		}
		
	}

	/**
	 * 退出登录
	 * @author 黄俊
	 * date 2016-7-2
	 */
	public function loginout(){
		session_unset();
		session_destroy();
		$this->redirect('Login/index');
	}

	/**
	 * 找回密码界面
	 * @author 黄俊
	 * date 2016-7-2
	 */
	public function findPwd(){

		if(IS_POST){

			// 获取参数
			$user=I('uname','');//用户名
			$id_card=I('uCard','');//身份证号码

			// 账号不能为空
			if( empty($user) ){
				$this->error='错误提示：用户名不可以为空！';
				$this->display();
				exit();
			}

			// 验证身份证格式
			if( !is_idCard($id_card) ){
				$this->error='错误提示：身份证格式不正确！';
				$this->display();
				exit();
			}

			//实例化模型
			$member=D('Member');

			//验证用户名是否存在
			$memberInfo=M('member')->where(array('user'=>$user))->find();

			if( empty($memberInfo) ){
				$this->error='错误提示：用户名不存在！';
				$this->display();
				exit();
			}

			//检查账号是否被冻结
			if( $memberInfo['status'] == 2 ){
				$this->error='错误提示：用户被冻结！';
				$this->display();
				exit();
			}

			//检查验证次数限制
			$limit=$member->findPwd_limit($memberInfo['user'],5);//5次

			if( $limit <= 0 ){
				$this->error='错误提示：账号暂时被限制，请稍候再试！';
				$this->display();
				exit();
			}


			//验证身份证
			if( $memberInfo['id_card'] != $id_card ){

				//记录登录日志
				$member->findPwd_log($memberInfo['user'],2);//1、表示成功 2、表示失败

				$this->error='错误提示：身份证匹配错误！还可以进行'.($limit-1).'次验证';
				$this->display();
				exit();
			}

			//记录日志
			$member->findPwd_log($memberInfo['user']);

			// 记录session，并跳转至重置密码页面
			session('findPwd_user',$memberInfo['user']);

			$this->redirect('Login/setPwd');

		}else{
			$this->display();
		}
		
	}

	/**
	 * 重设密码界面
	 * @author 黄俊
	 * date 2016-7-2
	 */
	public function setPwd(){

		//防止直接访问
		if(!$_SESSION['findPwd_user']){
			$this->error('警告，非法操作！');
		}

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

			$data['password']=encrypt($newpwd1);
			$data['update_time']=date('Y-m-d H:i:s');//更新时间

			//保存新密码
			if( M('member')->where( array('user'=>$_SESSION['findPwd_user']) )->save($data) ){
				// 清楚session
				session('findPwd_user',null);
				// 页面跳转至登录界面
				$this->success('密码重置成功！',U('index'));
			}else{
				$this->error('网络不好，请重新操作！');
			}
		}else{
			$this->display();
		}
		
	}

}
?>