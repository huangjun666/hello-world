<?php
/**
 * 系统设置模块
 * @author 黄俊
 * date 2016-6-28
 */
class SystemAction extends BaseAction{

	/**
	 * 首页
	 * @author 黄俊
	 * date 2016-6-28
	 */
	public function index(){

		if(IS_POST){
			//权限判断
			if( !in_array($_SESSION['role'], array(4)) ){
				$this->error('权限不够！');
			}
			
			//获取提交的表单值

			#分销奖
			$data['fx_one']=I('fx_one',0,'intval');
			$data['fx_two']=I('fx_two',0,'intval');
			$data['fx_three']=I('fx_three',0,'intval');

			#辅导奖
			// $data['fd_level']=I('fd_level',0,'intval');
			$data['fd_percent']=I('fd_percent',0,'intval');

			#团队奖
			// $data['td_limit']=I('td_limit',0,'intval');
			$data['td_one']=I('td_one',0,'intval');
			$data['td_one_p']=I('td_one_p',0,'intval');
			$data['td_two']=I('td_two',0,'intval');
			$data['td_two_p']=I('td_two_p',0,'intval');
			$data['td_three']=I('td_three',0,'intval');
			$data['td_three_p']=I('td_three_p',0,'intval');
			$data['td_four']=I('td_four',0,'intval');
			$data['td_four_p']=I('td_four_p',0,'intval');
			$data['td_five']=I('td_five',0,'intval');
			$data['td_five_p']=I('td_five_p',0,'intval');
			$data['td_six']=I('td_six',0,'intval');
			$data['td_six_p']=I('td_six_p',0,'intval');
			$data['td_seven']=I('td_seven',0,'intval');
			$data['td_seven_p']=I('td_seven_p',0,'intval');
			$data['td_eight']=I('td_eight',0,'intval');
			$data['td_eight_p']=I('td_eight_p',0,'intval');

			#生活馆
			$data['sh_profit']=I('sh_profit',0,'intval');

			#分红奖
			$data['fh_dep_num']=I('fh_dep_num',0,'intval');
			$data['fh_profit']=I('fh_profit',0,'intval');
			$data['fh_limit']=I('fh_limit',0,'intval');
			$data['fh_limit_other']=I('fh_limit_other',0,'intval');
			$data['fh_num']=I('fh_num',0,'intval');

			#订货单
			$data['order_discount']=I('order_discount',0,'intval');

			#点位对应工资上限
			$data['point_one']=I('point_one',0,'intval');
			$data['point_two']=I('point_two',0,'intval');
			$data['point_four']=I('point_four',0,'intval');
			$data['point_six']=I('point_six',0,'intval');

			#工资扣税
			$data['tax_money']=I('tax_money',0,'intval');
			$data['tax_rate']=I('tax_rate',0,'intval');

			#推荐人通知
			$data['rec_notice']=I('rec_notice',0,'intval');

			/*判断团队奖的阶梯性*/
			if( !($data['td_one'] < $data['td_two'] && $data['td_two'] < $data['td_three'] && $data['td_three'] < $data['td_four'] && $data['td_four'] < $data['td_five'] && $data['td_five'] < $data['td_six'] && $data['td_six'] < $data['td_seven'] && $data['td_seven'] < $data['td_eight'] ) ){
				$this->assign('error','团队奖设置不合理，请检查！');
				$this->display();
				exit();
			}

			//当前奖励配置
			$reward_config=M('reward_config')->select();;

			/*取出发生变更的奖励配置*/
			$auditData=array();
			$auditKey=0;

			foreach ($reward_config as $key => $value) {
				
				//判断修改队列中，是否存在该选项
				if( !isset($data[$value['key']]) ){
					continue;
				}

				//对比,保留不一样的配置
				if($data[$value['key']] != $value['value']){
					$auditData[$auditKey]['remarks']=$value['remarks'];//键名备注
					$auditData[$auditKey]['key']=$value['key'];//键名
					$auditData[$auditKey]['value_before']=$value['value'];//审核之前的键值
					$auditData[$auditKey]['value']=$data[$value['key']];//审核之后的键值
					$auditData[$auditKey]['audit_user']=$_SESSION['user'];
					$auditData[$auditKey]['audit_name']=$_SESSION['name'];
					$auditData[$auditKey]['update_time']=date('Y-m-d H:i:s');//更新时间

					$auditKey++;
				}
			}

			// P($auditData);die();
			//如果奖励配置没有发生变化
			if(empty($auditData)){
				$this->success('奖励配置没有发生变化！',U('index'));
				exit();
			}

			//保存
			if( M('reward_config_audit')->addAll($auditData) ){
				$this->success('提交成功，请等待超级管理员审核！',U('index'));
			}else{
				$this->error('操作失败，请重试！',U('index'));
			}

		}else{
			//权限判断
			if( !in_array($_SESSION['role'], array(4)) ){
				$this->error('权限不够！');
			}

			// 奖励配置
			$this->reward_config=D('Web')->reward_config();

			$this->display();
		}
		
		
	}
	/**
	 * 首页
	 * @author 黄俊
	 * date 2016-6-28
	 */
	public function white(){

		if(IS_POST){
			//权限判断
			if( !in_array($_SESSION['role'], array(4)) ){
				$this->error('权限不够！');
			}
			
			$data['order']=I('order','');//订单管理白名单
			$data['tree']=I('tree','');//经销商树型图白名单
			$data['sh']=I('sh','');//生活馆信息查询白名单

			// P($data);die();
			$white_list=M('white_list');

			//循环更新配置库
			foreach ($data as $key => $value) {

				$d['update_time']=date('Y-m-d H:i:s');
				$d['value']=$value;

				if( !$white_list->where(array('key'=>$key))->save($d) ){
					$this->error('配置失败，请重试！',U('white'));
				}

			}

			//配置完成，调整页面
			$this->success('配置成功！',U('white'));


		}else{
			//权限判断
			if( !in_array($_SESSION['role'], array(4)) ){
				$this->error('权限不够！');
			}

			// 奖励配置
			// $white_config=SERVICE('System')->get_white_config('order');
			// P($white_config);die();
			$this->white_config=SERVICE('System')->get_white_config();

			$this->display();
		}
		
		
	}

	/**
	 * 奖励设置审核列表
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
		$count=M('reward_config_audit')->count();//总数量
		$page=new Page($count,20);
		$limit=$page->firstRow.','.$page->listRows;

		$reward_config_audit=M('reward_config_audit')->limit($limit)->order('field(`status`,1,2,3),add_time DESC')->select();

		$this->page=$page->show();//获得页码
		$this->sort=$page->firstRow;//序号
		$this->assign('reward_config_audit',$reward_config_audit);
		$this->display();
	}

	/**
	 * 奖励设置审核
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

		$rights_audit=M('reward_config_audit')->where(array('id'=>$rights_audit_id))->find();

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

		M('reward_config_audit')->save($auditData);

		/*配置变更*/
		if($audit==2){//审核通过时，配置变化

			//配置内容变更
			$where=array(array('key'=>$rights_audit['key']));
			$data['value']=$rights_audit['value'];
			$data['update_time']=date('Y-m-d H:i:s');//更新时间

			//保存
			if(!M('reward_config')->where($where)->save($data)){

				//数据库操作失败，提示客户端
				$this->error("网络不好，操作失败！");
				exit();
			}
		}

		//根据不同的操作，给不同的提示信息
		if($audit==2){
			$this->success('审核通过，配置生效！');
		}else{
			$this->success('审核不通过！');
		}
	}

}
?>