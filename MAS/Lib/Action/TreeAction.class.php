<?php
/**
 * 经销商树状结构图
 * @author 黄俊
 * date 2016-7-31
 */
class TreeAction extends BaseAction{

	/**
	 * 首页
	 * @author 黄俊
	 * date 2016-7-31
	 */
	public function index(){
		//权限判断
		if( !in_array($_SESSION['user'], SERVICE('System')->get_white_config('tree')) ){
			$this->error('权限不够！');
		}
		// $week=getMonthFirst();
		// P($week);die();
		$this->display();
		
	}

	/**
	 * 请求节点数据
	 * @author 黄俊
	 * date 2016-7-31
	 */
	public function tree(){
		//权限判断
		if( !in_array($_SESSION['user'], SERVICE('System')->get_white_config('tree')) ){
			$this->error('权限不够！');
		}
		
		$pid=I('member_id',0,'intval');
		$unit=I('unit',0,'intval');//单位：周、月、总
		$time=I('time','','addOneSecond');//时间节点
		$isFutureAch=true;//是否显示的是将来业绩/false/true

		//pid为0，则请求数据非法
		if( $pid == 0 ){
			$rs['status']=1;
			$rs['list']=$treeArr;
			$rs['msg']="成功！";
			$this->ajaxReturn($rs);
		}

		/*判断是否查询member表*/
		if( SERVICE('Tree')->isMemberSelect($unit,$time) ){

			//是否显示的是将来业绩
			if($isFutureAch){
				$field='member_id,pid,name,future_week_ach,future_month_ach,future_total_ach,team_reward,team_star';
			}else{
				$field='member_id,pid,name,week_ach,month_ach,total_ach,team_reward,team_star';
			}

			$tree=M('member')->field($field)->where(array('pid'=>$pid))->select();
			$treeArr=array();

			foreach ($tree as $key => $value) {
				$isParent=false;
				if( M('member')->where(array('pid'=>$value['member_id']))->count() > 0 ){
					$isParent=true;
				}
				//选择显示的业绩方式：周、月、总
				$ach=0;
				switch ($unit) {
					case 1:
						$ach=$isFutureAch?intval($value['future_week_ach']):intval($value['week_ach']);
						break;
					case 2:
						$ach=$isFutureAch?intval($value['future_month_ach']):intval($value['month_ach']);
						break;
					case 3:
						$ach=$isFutureAch?intval($value['future_total_ach']):intval($value['total_ach']);
						break;
					
					default:
						$ach=0;
						break;
				}
				// echo $ach;die();
				$member=M('member')->where(array('member_id'=>$value['member_id']))->find();

				//团队星级
				$team_star_str='';

				if($value['team_star']!=0){
					$team_star_str='【'.SERVICE('Finance')->team_star_str($value['team_star'])."星董事】";
				}
				
				$treeArr[]=array(
					'member_id'=>$value['member_id'],
					'pid'=>$value['pid'],
					// 'name'=>$member['status']==1?$value['name'].$ach.'_'.$value['team_reward']:'【已冻结】'.$value['name'].$ach.'_'.$value['team_reward'],
					'name'=>$member['status']==1?$value['name'].$ach.$team_star_str:'【已冻结】'.$value['name'].$ach.$team_star_str,
					'isParent'=>$isParent
					);
			}

			$this->ajaxReturn($treeArr);
		}else{
			// 查询条件
			$where=' pid='.$pid.' AND `type`='.$unit.' AND "'.$time.'" BETWEEN start_time AND end_time';
			
			// 开始查询
			$tree=M('ach_log')->field('member_id,pid,name,future_ach,ach,type')->where($where)->select();

			$treeArr=array();

			foreach ($tree as $key => $value) {
				$isParent=false;
				if( M('ach_log')->where(array('pid'=>$value['member_id']))->count() > 0 ){
					$isParent=true;
				}


				$member=M('member')->where(array('member_id'=>$value['member_id']))->find();

				//显示的业绩
				$ach=$isFutureAch?intval($value['future_ach']):intval($value['ach']);

				$treeArr[]=array(
					'member_id'=>$value['member_id'],
					'pid'=>$value['pid'],
					'name'=>$member['status']==1?$value['name'].$ach:'【已冻结】'.$value['name'].$ach,
					'isParent'=>$isParent
					);
			}

			// 返回结果
			$this->ajaxReturn($treeArr);
		}

		

	}

	/**
	 * 首次请求节点数据
	 * @author 黄俊
	 * date 2016-7-31
	 */
	public function first(){

		//权限判断
		if( !in_array($_SESSION['user'], SERVICE('System')->get_white_config('tree')) ){
			$this->error('权限不够！');
		}

		$unit=I('unit',1,'intval');//单位：周、月、总
		$time=I('time','','addOneSecond');//时间节点
		$isFutureAch=true;//是否显示的是将来业绩/false/true


		/*判断是否查询member表*/
		if( SERVICE('Tree')->isMemberSelect($unit,$time) ){

			//是否显示的是将来业绩
			if($isFutureAch){
				$field='member_id,pid,name,future_week_ach,future_month_ach,future_total_ach,team_reward,team_star';
			}else{
				$field='member_id,pid,name,week_ach,month_ach,total_ach,team_reward,team_star';
			}

			$tree=M('member')->field($field)->where(array('member_id'=>$_SESSION['uid']))->select();

			$treeArr=array();

			foreach ($tree as $key => $value) {
				$isParent=false;
				if( M('member')->where(array('pid'=>$value['member_id']))->count() > 0 ){
					$isParent=true;
				}

				//选择显示的业绩方式：周、月、总
				$ach=0;
				switch ($unit) {
					case 1:
						$ach=$isFutureAch?intval($value['future_week_ach']):intval($value['week_ach']);
						break;
					case 2:
						$ach=$isFutureAch?intval($value['future_month_ach']):intval($value['month_ach']);
						break;
					case 3:
						$ach=$isFutureAch?intval($value['future_total_ach']):intval($value['total_ach']);
						break;
					
					default:
						$ach=0;
						break;
				}

				$member=M('member')->where(array('member_id'=>$value['member_id']))->find();

				//团队星级
				$team_star_str='';

				if($value['team_star']!=0){
					$team_star_str='【'.SERVICE('Finance')->team_star_str($value['team_star'])."星董事】";
				}

				$treeArr[]=array(
					'member_id'=>$value['member_id'],
					'pid'=>$value['pid'],
					// 'name'=>$member['status']==1?$value['name'].$ach.'_'.$value['team_reward']:'【已冻结】'.$value['name'].$ach.'_'.$value['team_reward'],
					'name'=>$member['status']==1?$value['name'].$ach.$team_star_str:'【已冻结】'.$value['name'].$ach.$team_star_str,
					'isParent'=>$isParent
					);
			}
			// P($treeArr);die();
			$rs['status']=1;
			$rs['list']=$treeArr;
			$rs['msg']="成功！";
			$this->ajaxReturn($rs);

		}else{//查询ach_log表

			// 查询条件
			$where=' member_id='.$_SESSION['uid'].' AND `type`='.$unit.' AND "'.$time.'" BETWEEN start_time AND end_time';
			
			// 开始查询
			$tree=M('ach_log')->field('member_id,pid,name,future_ach,ach,type')->where($where)->select();

			$treeArr=array();

			foreach ($tree as $key => $value) {
				$isParent=false;
				if( M('ach_log')->where(array('pid'=>$value['member_id']))->count() > 0 ){
					$isParent=true;
				}

				$member=M('member')->where(array('member_id'=>$value['member_id']))->find();

				//显示的业绩
				$ach=$isFutureAch?intval($value['future_ach']):intval($value['ach']);

				$treeArr[]=array(
					'member_id'=>$value['member_id'],
					'pid'=>$value['pid'],
					'name'=>$member['status']==1?$value['name'].$ach:'【已冻结】'.$value['name'].$ach,
					'isParent'=>$isParent
					);
			}
			// P($treeArr);die();
			$rs['status']=1;
			$rs['list']=$treeArr;
			$rs['msg']="成功！";
			$this->ajaxReturn($rs);
		}

	}

}
?>