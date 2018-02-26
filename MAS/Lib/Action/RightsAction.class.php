<?php
/**
 * 权限模块
 * @author 黄俊
 * date 2016-6-28
 */
class RightsAction extends BaseAction{

	/**
	 * 首页
	 * @author 黄俊
	 * date 2016-6-28
	 */
	public function index(){
		//权限判断
		if( !in_array($_SESSION['role'], array(4)) ){
			$this->error('权限不够！');
		}

		$user=I('user','');//用户名或账号
		$role=I('role',0,'intval');//角色

		$where='';//条件

		// 角色选择
		if( $role==0 ){
			$where.=' role in(1,2,3,4,5)';
		}else{
			$where.=' role ='.$role;
		}

		// 用户名或账号
		if(!empty($user)){
			$where.=" and (`user` LIKE '%".$user."%' OR `name` LIKE '%".$user."%')";
		}
		$where.=" and `user` NOT IN('admin','LSMT')";

		import('ORG.Util.Page');//引入分页类

		/*分页*/
		$count=M('member')->where($where)->count();//总数量
		$page=new Page($count,20);
		$limit=$page->firstRow.','.$page->listRows;

		$member=M('member')->field('member_id,user,name,role,status')->where($where)->limit($limit)->order('status ASC,add_time DESC,update_time DESC')->select();

		$this->page=$page->show();//获得页码
		$this->sort=$page->firstRow;//序号
		$this->assign('member',$member);
		$this->display();
		
	}

	/**
	 * 权限修改
	 * @author 黄俊
	 * date 2016-6-28
	 */
	public function edit(){
		//权限判断
		if( !in_array($_SESSION['role'], array(4)) ){
			$this->error('权限不够！');
		}

		if(IS_POST){

			$member_id=I('member_id',0,'intval');
			$role=I('role',0,'intval');
			
			//检查角色是否合法
			if( !in_array($role, array(1,2,3,4,5)) ){
				$this->assign('error','角色不合法');
				$this->display('edit');
				exit();
			}

			//该会员的信息
			$member=M('member')->field('member_id,user,name,role')->where(array('member_id'=>$member_id))->find();

			//如果角色没有发生变化
			if($member['role']==$role){
				$this->success('角色没有发生变化！',U('index'));
				exit();
			}

			/*插入权限管理审核表的数据*/
			$data['user']=$member['user'];
			$data['name']=$member['name'];
			$data['role_before']=$member['role'];
			$data['role']=$role;
			$data['audit_user']=$_SESSION['user'];
			$data['audit_name']=$_SESSION['name'];
			$data['update_time']=date('Y-m-d H:i:s');//更新时间

			//保存
			if( M('rights_audit')->add($data) ){
				$this->success('提交成功，请等待超级管理员审核！',U('index'));
			}else{
				$this->error('编辑失败，请重试！',U('index'));
			}


		}else{
			$member_id=I('member_id',0,'intval');
			$member=M('member')->field('member_id,user,name,role')->where(array('member_id'=>$member_id))->find();

			$this->assign('member',$member);
			$this->display();
		}
		
	}

	

	/**
	 * 权限管理审核列表
	 * @author 黄俊
	 * date 2017-10-22
	 */
	public function audit(){
		//权限判断
		if( !in_array($_SESSION['role'], array(4)) ){
			$this->error('权限不够！');
		}

		import('ORG.Util.Page');//引入分页类

		/*分页*/
		$count=M('rights_audit')->count();//总数量
		$page=new Page($count,20);
		$limit=$page->firstRow.','.$page->listRows;

		$rights_audit=M('rights_audit')->limit($limit)->order('field(`status`,1,2,3),add_time DESC')->select();

		$this->page=$page->show();//获得页码
		$this->sort=$page->firstRow;//序号
		$this->assign('rights_audit',$rights_audit);
		$this->display();
	}

	/**
	 * 权限管理审核
	 * @author 黄俊
	 * date 2017-10-22
	 */
	public function pass(){
		//权限判断
		if( !in_array($_SESSION['role'], array(4)) ){
			$this->error('权限不够！');
		}

		//获取字段
		$rights_audit_id=I('id',0,'intval');
		$audit=I('audit',1,'intval');//1、不通过，2、通过

		$rights_audit=M('rights_audit')->where(array('id'=>$rights_audit_id))->find();

		//检查是否被审核过
		if($rights_audit['status']!=1){
			$this->error("已经审核过！");
			exit();
		}

		//审核单不存在
		if(empty($rights_audit)){
			$this->error("非法操作！");
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

		M('rights_audit')->save($auditData);

		/*会员角色变更*/
		if($audit==2){//审核通过时，会员角色需要变化

			//角色变更
			$where=array(array('user'=>$rights_audit['user']));
			$data['role']=$rights_audit['role'];
			$data['update_time']=date('Y-m-d H:i:s');//更新时间

			//保存
			if(!M('member')->where($where)->save($data)){

				//数据库操作失败，提示客户端
				$this->error("网络不好，操作失败！");
				exit();
			}
		}

		//根据不同的操作，给不同的提示信息
		if($audit==2){
			$this->success('审核通过，重新登陆后，权限生效！');
		}else{
			$this->success('审核不通过！');
		}
	}

}
?>