<?php

/**
 * 财务模型
 * 说明：提供财务相关的服务
 * @author 黄俊
 * date 2016-6-28
 */

class FinanceModel extends BaseModel{

	/**
	 * 根据会员信息和订单信息计算上线的业绩
	 * @author 黄俊
	 * date 2016-7-1
	 */
	public function calculateAch($money,$member){

		//实例化模型
		$m=new Model();

		//业绩
		$addAch=$money;

		//上线
		$tree=empty($member['tree'])?$member['pid']:$member['pid'].','.$member['tree'];

		//sql语句
		$sql='UPDATE `member` SET total_ach=total_ach+'.$addAch.',month_ach=month_ach+'.$addAch.',week_ach=week_ach+'.$addAch.',update_time="'.date('Y-m-d H:i:s',time()).'" WHERE member_id IN ('.$tree.')';


		//修改团队上线的业绩
		return $m->execute($sql);

	}

	/**
	 * 根据会员信息和订单信息计算上线【将来】的业绩
	 * @author 黄俊
	 * date 2016-12-6
	 */
	public function calculateFutureAch($money,$member){

		//实例化模型
		$m=new Model();

		//业绩
		$addAch=$money;

		//上线
		$tree=empty($member['tree'])?$member['pid']:$member['pid'].','.$member['tree'];

		//sql语句
		$sql='UPDATE `member` SET future_total_ach=future_total_ach+'.$addAch.',future_month_ach=future_month_ach+'.$addAch.',future_week_ach=future_week_ach+'.$addAch.',update_time="'.date('Y-m-d H:i:s',time()).'" WHERE member_id IN ('.$tree.')';


		//修改团队上线的业绩
		return $m->execute($sql);

	}

	/**
	 * 根据会员信息和订单信息计算上线的推荐奖
	 * @author 黄俊
	 * date 2016-7-1
	 */
	public function rec_reward($money,$recommend_user,$reward_config){

		//实例化模型
		$m=new Model();

		//业绩
		$addAch=$money;

		//级别
		$level=array('fx_one','fx_two','fx_three');//三级分销奖励

		//推荐人
		$recommend_user=$recommend_user;

		foreach ($level as $key => $value) {

			# 当前级别的推荐奖励
			$reward=$addAch*$reward_config[$value]*0.01;

			# 增加奖励到推荐人账户上
			$sql=' update member set all_rec_reward=all_rec_reward+'.$reward.',rec_reward=rec_reward+'.$reward.' where user="'.$recommend_user.'"';
			$m->execute($sql);

			# 获取该推荐人的上一级推荐人
			$sql='select recommend_user from member where user="'.$recommend_user.'"';
			$rs=$m->query($sql);

			$recommend_user=$rs[0]['recommend_user'];

			if( empty($recommend_user) ){//如果没有上一级推荐人，退出循环
				break;
			}

		}

		return true;

	}

	/**
	 * 根据会员信息和订单信息扣除上线的推荐奖
	 * @author 黄俊
	 * date 2017-10-16
	 */
	public function fan_rec_reward($money,$recommend_user,$reward_config){

		//实例化模型
		$m=new Model();

		//业绩
		$addAch=$money;

		//级别
		$level=array('fx_one','fx_two','fx_three');//三级分销奖励

		//推荐人
		$recommend_user=$recommend_user;

		foreach ($level as $key => $value) {

			# 当前级别的推荐奖励
			$reward=$addAch*$reward_config[$value]*0.01;

			# 增加奖励到推荐人账户上
			$sql=' update member set all_rec_reward=all_rec_reward-'.$reward.',rec_reward=rec_reward-'.$reward.' where user="'.$recommend_user.'"';
			$m->execute($sql);

			# 获取该推荐人的上一级推荐人
			$sql='select recommend_user from member where user="'.$recommend_user.'"';
			$rs=$m->query($sql);

			$recommend_user=$rs[0]['recommend_user'];

			if( empty($recommend_user) ){//如果没有上一级推荐人，退出循环
				break;
			}

		}

		return true;

	}

	/**
	 * 根据会员信息和报单总额清除上线的推荐奖
	 * @author 黄俊
	 * date 2016-7-1
	 */
	public function clear_rec_reward($recommend_user,$money,$reward_config){

		//实例化模型
		$m=new Model();

		//级别
		$level=array('fx_one','fx_two','fx_three');//三级分销奖励

		//推荐人
		$recommend_user=$recommend_user;
		
		foreach ($level as $key => $value) {

			# 当前级别的推荐奖励
			$reward=$money*$reward_config[$value]*0.01;

			# 增加奖励到推荐人账户上
			$sql=' update member set rec_reward=rec_reward-'.$reward.' where user="'.$recommend_user.'"';
			$m->execute($sql);

			# 获取该推荐人的上一级推荐人
			$sql='select recommend_user from member where user="'.$recommend_user.'"';
			$rs=$m->query($sql);

			$recommend_user=$rs[0]['recommend_user'];

			if( empty($recommend_user) ){//如果没有上一级推荐人，退出循环
				break;
			}

		}

		return true;

	}

	/**
	 * 根据会员信息和订单信息计算上线的辅导奖树形节点
	 * @author 黄俊
	 * date 2016-7-1
	 */
	public function coach_reward($member,$reward_config){

		//实例化模型
		$m=new Model();

		//辅导奖辐射级别
		$level=$reward_config['fd_level'];

		//安置人
		$pid=$member['pid'];
		//推荐人
		$recommend_user=$member['recommend_user'];


		# 安置人辅导树---8代
		for ($i=0; $i < 8; $i++) {

			# 增加id到安置人辅导树上

			$sql=' update member set coach_tree=CONCAT(coach_tree,",",'.$member['member_id'].') where member_id='.$pid;
			$m->execute($sql);

			# 获取该安置人的上一级安置人
			$sql='select pid from member where member_id='.$pid;
			$rs=$m->query($sql);

			$pid=$rs[0]['pid'];//上一级安置人

			if( $pid == 0 ){//如果没有上一级安置人，退出循环
				break;
			}

		}

		# 推荐人辅导树----8层
		for ($i=0; $i < 6; $i++) {

			//1、查出该会员的上一级推荐人的辅导树
			$rerecommendMember=M('member')->field('member_id,coach_tree,recommend_user')->where(array('user'=>$recommend_user))->find();

			//2、检查该会员的id是否在该推荐人的辅导树中
			$tree=$rerecommendMember['coach_tree'].',';
			$member_id=','.$member['member_id'].',';

			//3、没有，则增加id到辅导树
			if( strpos($tree, $member_id) === false ){
				$sql=' update member set coach_tree=CONCAT(coach_tree,",",'.$member['member_id'].') where user="'.$recommend_user.'"';
				$m->execute($sql);
			}

			// 4、查出下下一级推荐人
			$recommend_user=$rerecommendMember['recommend_user'];//上一级推荐人

			if( empty($recommend_user) ){//如果没有上一级推荐人，退出循环
				break;
			}

		}

		return true;

	}

	/**
	 * 计算团队奖
	 * @author 黄俊
	 * date 2016-7-1
	 */
	public function team_reward($self,$team,$reward_config){

		/*团队奖返回的结果*/
		$team_reward['team_reward']=0;//自身获得的团队奖励
		$team_reward['team_lost_reward']=0;//该业务线已经被分配出去的团队奖励

		/*计算该业务线已经被分配出去的团队奖励*/
		foreach ($team as $key => $value) {
			$team_reward['team_lost_reward']+=$value['team_lost_reward'];
		}

		/*不满足获得奖励的情况：*/ 
		if( !$this->can_team_reward($self,$team,$reward_config['td_limit']) ){
			return $team_reward;
		}

		/*满足，计算团队奖*/
		$all_team_reward=$this->all_team_reward($self,$reward_config);

		// 自身获得 的奖励
		$team_reward['team_reward']=$all_team_reward-$team_reward['team_lost_reward'];

		// 该业务线已经分配出去的奖励
		$team_reward['team_lost_reward']=$all_team_reward;

		return $team_reward;

	}

	/**
	 * 根据会员业绩，获得满额团队奖
	 * @author 黄俊
	 * date 2016-7-1
	 * 返回值：团队奖
	 */
	public function all_team_reward($self,$reward_config){
		
		// $week_ach=$self['week_ach'];//业绩#黄俊：7月1日，此处调整
		$week_ach=$self['month_ach'];//业绩#黄俊：7月1日，此处调整
		$p=0;//奖励比例

		// 1级
		if( $week_ach >= $reward_config['td_one'] ){
			$p+=$reward_config['td_one_p'];
		}

		// 2级
		if( $week_ach >= $reward_config['td_two'] ){
			$p+=$reward_config['td_two_p'];
		}

		// 3级
		if( $week_ach >= $reward_config['td_three'] ){
			$p+=$reward_config['td_three_p'];
		}

		// 4级
		if( $week_ach >= $reward_config['td_four'] ){
			$p+=$reward_config['td_four_p'];
		}

		// 5级
		if( $week_ach >= $reward_config['td_five'] ){
			$p+=$reward_config['td_five_p'];
		}

		// 6级
		if( $week_ach >= $reward_config['td_six']){
			$p+=$reward_config['td_six_p'];
		}

		// 7级
		if( $week_ach >= $reward_config['td_seven']){
			$p+=$reward_config['td_seven_p'];
		}

		// 8级
		if( $week_ach >= $reward_config['td_eight']){
			$p+=$reward_config['td_eight_p'];
		}

		return $week_ach*$p*0.01;

	}


	/**
	 * 检查会员是否满足获得团队奖的条件
	 * @author 黄俊
	 * date 2016-7-1
	 * 返回值：不满足：false，满足：true
	 */
	public function can_team_reward($self,$team,$num){

		#1、下属不满足2个
		$total=count($team);

		if( $total < $num ){
			return false;
		}

		#2、至少有2个下属业绩不为0
		$haveAch=0;
		// $time=getWeekFirst();#黄俊：7月1日，此处调整
		$time=getMonthFirstNew();#黄俊：7月1日，此处调整
		// $time['now_start']='2016-08-29 02:00:00';
		foreach ($team as $key => $value) {
			// if( $value['week_ach'] != 0  ||  strtotime($value['add_time']) > strtotime($time['now_start'])  ){#黄俊：7月1日，此处调整
			// 	$haveAch++;
			// }
			if( $value['month_ach'] != 0  ){# ||  strtotime($value['add_time']) > strtotime($time[0]) 黄俊：7月1日，此处调整
				$haveAch++;
			}
		}

		if( $haveAch < $num ){
			return false;
		}

		#满足
		return true;

	}

	/**
	 * 计算团队星级
	 * @author 黄俊
	 * date 2017-7-3
	 */
	public function team_star($self,$team,$reward_config){

		/*团队星级返回的结果*/
		$team_star=0;

		/*不满足获得星级的情况：*/ 
		if( !$this->can_team_star($self,$team,$reward_config['td_limit']) ){
			return $team_star;
		}

		/*满足，计算团队星级*/
		$team_star=$this->compute_team_star($self,$team);

		return $team_star;

	}

	/**
	 * 根据会员业绩，计算团队星级
	 * @author 黄俊
	 * date 2017-7-3
	 * 返回值：团队星级
	 */
	public function compute_team_star($self,$team){
		
		// $week_ach=$self['week_ach'];//业绩#黄俊：7月1日，此处调整
		$month_ach=$self['month_ach'];//业绩#黄俊：7月1日，此处调整
		$star=0;//团队星级

		#团队业绩 起步从30万 算起，
		#是大部门业绩减去  小部门业绩相加达成30万的 则为一星

		//算出最大部门的业绩
		$max_team_ach=0;
		foreach ($team as $key => $value) {
			
			//如果当前业绩高于$max_team_ach，则更新$max_team_ach
			if($value['month_ach']>$max_team_ach){
				$max_team_ach=$value['month_ach'];
			}
		}

		//大部门业绩减去，剩余小部门业绩
		$month_ach=$month_ach-$max_team_ach;


		// 一星
		if( $month_ach >= 300000 ){
			$star=1;
		}

		// 二星
		if( $month_ach >= 800000 ){
			$star=2;
		}

		// 三星
		if( $month_ach >= 1200000 ){
			$star=3;
		}

		// 四星
		if( $month_ach >= 2000000 ){
			$star=4;
		}

		// 五星
		if( $month_ach >= 3360000 ){
			$star=5;
		}

		return $star;

	}


	/**
	 * 检查会员是否满足获得团队星级的条件
	 * @author 黄俊
	 * date 2017-7-3
	 * 返回值：不满足：false，满足：true
	 */
	public function can_team_star($self,$team,$num){

		#1、下属不满足2个
		$total=count($team);

		if( $total < $num ){
			return false;
		}

		#2、至少有2个下属业绩不为0
		$haveAch=0;
		// $time=getWeekFirst();#黄俊：7月1日，此处调整
		$time=getMonthFirstNew();#黄俊：7月1日，此处调整
		// $time['now_start']='2016-08-29 02:00:00';
		foreach ($team as $key => $value) {
			// if( $value['week_ach'] != 0  ||  strtotime($value['add_time']) > strtotime($time['now_start'])  ){#黄俊：7月1日，此处调整
			// 	$haveAch++;
			// }
			if( $value['month_ach'] != 0  ||  strtotime($value['add_time']) > strtotime($time[0])  ){#黄俊：7月1日，此处调整
				$haveAch++;
			}
		}

		if( $haveAch < $num ){
			return false;
		}

		#满足
		return true;

	}

	/**
	 * 团队星级数字转为字符串
	 * @author 黄俊
	 * date 2017-7-3
	 * 返回值：string
	 */
	public function team_star_str($team_star){

		$team_star_str='';//字符串

		switch ($team_star) {
			case 1:
				$team_star_str='一';
				break;
			case 2:
				$team_star_str='二';
				break;
			case 3:
				$team_star_str='三';
				break;
			case 4:
				$team_star_str='四';
				break;
			case 5:
				$team_star_str='五';
				break;
			
			default:
				# code...
				break;
		}

		#返回结果
		return $team_star_str;

	}

	/**
	 * 比较自身与下属的级别差异，同时获得下属团队奖
	 * @author 黄俊
	 * date 2016-7-1
	 */
	public function sub_team_reward($me,$subValue,$reward_config,$self=0){

		$percent=array('td_one_p','td_two_p','td_three_p','td_four_p','td_five_p','td_six_p','td_seven_p','td_eight_p');

		$reward=0;//最终奖励

		$levelDiff=0;// 计算中间级别差异
		$p=0;	//百分比

		//如果$self=1,
		if($self){

			//转化
			$subValueArr=array();
			$subValueArr[]=$subValue;

			//检查是否撞线
			if(!$this->self_team_reward($me,$subValueArr)){
				return $reward;
			}

			$p+=$me['p'];//奖励比例
		}
		
		$levelDiff=$me['level']-$subValue['level']-1;
		

		$subLevel=$subValue['level'];//下属级别

		for ($i=0; $i < $levelDiff; $i++) { 
		
			$key=$percent[$subLevel];
			$p=$p+$reward_config[$key];
			$subLevel++;
		}
		

		$reward=$subValue['ach']*$p*0.01;

		return $reward;

	}

	/**
	 * 比较自身是否可以获得自己的团队奖
	 * @author 黄俊
	 * date 2016-7-1
	 */
	public function self_team_reward($me,$sub){

		$ok=true;

		foreach ($sub as $key => $value) {
			
			if( $me['level'] <= $value['level'] ){
				$ok=false;
				break;
			}

		}

		return $ok;
	}

	/**
	 * 根据业绩返回所在团队奖的级别和奖励比例
	 * @author 黄俊
	 * date 2016-7-1
	 */
	public function team_level($week_ach,$reward_config){

		$rs=array();//结果
		$rs['ach']=$week_ach;

		if( $week_ach < $reward_config['td_one'] ){//0级

			$rs['level']=0;
			$rs['p']=0;

		}else if( $week_ach >= $reward_config['td_one'] && $week_ach < $reward_config['td_two'] ){
			
			$rs['level']=1;
			$rs['p']=$reward_config['td_one_p'];

		}else if( $week_ach >= $reward_config['td_two'] && $week_ach < $reward_config['td_three'] ){
						
			$rs['level']=2;
			$rs['p']=$reward_config['td_two_p'];

		}else if( $week_ach >= $reward_config['td_three'] && $week_ach < $reward_config['td_four'] ){
						
			$rs['level']=3;
			$rs['p']=$reward_config['td_three_p'];

		}else if( $week_ach >= $reward_config['td_four'] && $week_ach < $reward_config['td_five'] ){
						
			$rs['level']=4;
			$rs['p']=$reward_config['td_four_p'];

		}else if( $week_ach >= $reward_config['td_five'] && $week_ach < $reward_config['td_six'] ){
						
			$rs['level']=5;
			$rs['p']=$reward_config['td_five_p'];

		}else if( $week_ach >= $reward_config['td_six'] && $week_ach < $reward_config['td_seven'] ){
						
			$rs['level']=6;
			$rs['p']=$reward_config['td_six_p'];

		}else if( $week_ach >= $reward_config['td_seven'] && $week_ach < $reward_config['td_eight'] ){
						
			$rs['level']=7;
			$rs['p']=$reward_config['td_seven_p'];

		}else if( $week_ach >= $reward_config['td_eight']){
						
			$rs['level']=8;
			$rs['p']=$reward_config['td_eight_p'];

		}

		return $rs;
	}

	/**
	 * 计算分红奖次数
	 * @author 黄俊
	 * date 2016-7-1
	 * 返回：达到要求后，可以参与分红的次数
	 */
	public function fh_reward($member_id,$month_ach,$reward_config=array()){
		//实例化模型
		$member=M('member');
		$fh_reward=0;

		//奖励配置
		if( empty($reward_config) ){
			$reward_config=D('Web')->reward_config();
		}
		

		/*月业绩少于1000W,m没有奖励*/
		if( $month_ach < $reward_config['fh_limit'] ){
			return $fh_reward;
		}


		//查询该会员下属各个会员的月业绩
		$month_sub_ach=$member->field('member_id,month_ach')->where(array('pid'=>$member_id))->select();

		/*下属少于2个人的，没有分红奖励*/
		if( count($month_sub_ach) < 2 ){
			return $fh_reward;
		}

		/*去除最多的那个，其他人的收益之和*/
		$other_ach=$this->other_ach($month_sub_ach);

		/*大部门业绩--业绩最高的那个部门*/
		$max_ach=$this->max_ach($month_sub_ach);

		/*其他人的收益之和小于大部门业绩的20%,没有分红奖励*///200W
		if( $other_ach < ($max_ach*$reward_config['fh_limit_other']*0.01) ){
			return $fh_reward;
		}

		/*满足以上条件,则可以参与分红*/
		
		return $reward_config['fh_num'];//返回可以进行分红的次数


	}

	/**
	 * 为有资格参与上月进行分红的会员，分红
	 * @author 黄俊
	 * date 2016-7-1
	 */
	public function fh_action($reward_config=array()){

		//奖励配置
		if( empty($reward_config) ){
			$reward_config=D('Web')->reward_config();
		}

		//获得本月1号和下月1号、上个月1号
		$date= date('Y-m-d');
		$date=getMonthFirst($date);

		//条件
		$where='STATUS=2 AND update_time BETWEEN "'.$date[2].'" AND "'.$date[1].'"';

		/*统计集团上月总的订单业绩*/
		$order=M('order')->field('money')->where($where)->select();

		//业绩
		$ach=0;

		//计算业绩
		foreach ($order as $key => $value) {
			$ach+=$value['money'];
		}

		/*统计上月可以进行分红的人数*/
		$peopleWhere='bonus_type=3 AND start_time BETWEEN "'.$date[2].'" AND "'.$date[1].'"';
		$people=M('finance')->where($peopleWhere)->count();

		if(!$people){
			return 0;
		}

		/*分红金额*/
		$reward=($ach*$reward_config['fh_profit']*0.01)/$people;

		M('finance')->where($peopleWhere)->setField('bonus',$reward);

	}

	/**
	 * 去除最多的那个，其他人的业绩之和
	 * @author 黄俊
	 * date 2016-7-1
	 */
	public function other_ach($month_sub_ach){

		// 将业绩放入一维数组
		$ach=array();
		foreach ($month_sub_ach as $key => $value) {
			$ach[]=$value['month_ach'];
		}

		//获得最大值
		$max=max($ach);

		//返回其他人业绩之和
		return (array_sum($ach)-$max);

	}

	/**
	 * 取出团队大部门业绩--业绩最多的那个部门
	 * @author 黄俊
	 * date 2017-11-20
	 */
	public function max_ach($month_sub_ach){

		// 将业绩放入一维数组
		$ach=array();
		foreach ($month_sub_ach as $key => $value) {
			$ach[]=$value['month_ach'];
		}

		//获得最大值
		$max=max($ach);

		//返回大部门业绩
		return $max;

	}


	/**
	 * 计算生活馆收益
	 * @author 黄俊
	 * date 2016-7-1
	 */
	public function sh_reward($user,$ok=false,$reward_config=array()){

		//获得本月1号和下月1号、上个月1号
		$date= date('Y-m-d');
		$date=getMonthFirst($date);

		$where='';

		if($ok){//上个月馆主收益
			$where='handle="'.$user.'" AND STATUS=2 AND update_time BETWEEN "'.$date[2].'" AND "'.$date[1].'"';
		}else{//当月馆主收益
			$where='handle="'.$user.'" AND STATUS=2 AND update_time BETWEEN "'.$date[0].'" AND "'.$date[1].'"';
		}

		//取得本月所有通过该会员录入的订单的金额
		$order=M('order')->field('money')->where($where)->select();

		//业绩
		$ach=0;

		//计算业绩
		foreach ($order as $key => $value) {
			$ach+=$value['money'];
		}

		//奖励配置
		if( empty($reward_config) ){
			$reward_config=D('Web')->reward_config();
		}

		//返回收益
		return $ach*$reward_config['sh_profit']*0.01;
	}

}

?>