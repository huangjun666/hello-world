<?php
/**
 * 财务服务service
 * @author 黄俊
 * @date 2016-11-30
 */
class FinanceService extends BaseService{

	/**
	 * 获得当周辅导奖总额
	 * @author 黄俊
	 * 2017-3-1
	 * return int
	 */
	public function getCoachRewardTotal(){
		//实例化模型
		$m=new Model();

		// 查询sql
		$sql='SELECT SUM(coach_reward) total FROM `member`';

		//查询
		$rs=$m->query($sql);

		// 返回结果
		return $rs[0]['total'];
	}

	/**
	 * 获得当月真实业绩总额
	 * @author 黄俊
	 * 2017-3-1
	 * return int
	 */
	public function getTrueAchTotal(){

		$month=getMonthFirstNew();//当月一到下月一的时间范围

		//实例化模型
		$m=new Model();

		// 查询sql
		$sql='SELECT SUM(money) total FROM `order` WHERE `status`IN(2,6) AND update_time BETWEEN "'.$month[0].'" AND "'.$month[1].'"';

		// echo $sql;die();
		//查询
		$rs=$m->query($sql);

		// 返回结果
		return intval($rs[0]['total']);//intval($rs[0]['total']);
	}

	/**
	 * 获得会员的馆主收益记录
	 * @author 黄俊
	 * 2017-5-21
	 * return array
	 */
	public function getGuanzhuLog($user){

		//历史，已经结算过的
		$alreadyLog=$this->getAlreadyGuanzhuLog($user);

		//当月，未结算过的
		$alreadyLog[]=$this->getCurrentGuanzhuLog($user);

		//返回结果
		return array_reverse($alreadyLog);
	}

	/**
	 * 获得会员已经结算过的馆主收益记录
	 * @author 黄俊
	 * 2017-5-21
	 * return array[二维]
	 */
	public function getAlreadyGuanzhuLog($user){

		//字段
		$field='id,user,bonus,status,start_time,remarks';

		//条件
		$where=array('user'=>$user,'bonus_type'=>5);

		//查询
		$rs=M('finance')->field($field)->where($where)->select();

		// 返回结果
		return $rs;

	}

	/**
	 * 获得会员当月的馆主收益
	 * @author 黄俊
	 * 2017-5-21
	 * return array
	 */
	public function getCurrentGuanzhuLog($user){

		//当月生活馆收益
		$rs['id']=0;
		$rs['user']=$user;
		$rs['bonus']=D('Finance')->sh_reward($user);
		$rs['status']=1;
		$rs['remarks']='';
		$rs['start_time']=date('Y-m-d 00:00:00');

		//返回结果
		return $rs;
	}

	/**
	 * 获取指定月份内，馆主的报单详情
	 * @author 黄俊
	 * 2017-5-21
	 * return array
	 */
	public function getGuanzhuOrderLog($user,$time){

		//获得本月1号和下月1号、上个月1号
		$date= date('Y-m-d',$time);
		$date=getMonthFirst($date);

		//本月
		$where='handle="'.$user.'" AND STATUS=2 AND update_time BETWEEN "'.$date[0].'" AND "'.$date[1].'"';

		//取得本月所有通过该会员录入的订单
		$order=M('order')->field('order_id,name,tel,update_time,money')->where($where)->select();

		// echo M()->getLastSql();die();

		return $order;
	}

	/**
	 * 获得税后工资
	 * @author 黄俊
	 * 2017-6-18
	 * return int
	 */
	public function tax($money,$reward_config){

		//检查是否满足扣税起征额度
		if( $money >= $reward_config['tax_money'] ){
			return ($money-($money*$reward_config['tax_rate']*0.01));
		}else{
			return $money;
		}

	}

	/**
	 * 点位工资换算
	 * @author 黄俊
	 * 2017-6-18
	 * return int
	 */
	public function pointToMoney($money,$point,$reward_config){

		$rsMoney=money;//返回结果

		//根据点位上限换算工资
		switch ($point) {
			case 1:#1万点位
				$rsMoney=$money>$reward_config['point_one']?$reward_config['point_one']:$money;
				break;
			case 2:#2万点位
				$rsMoney=$money>$reward_config['point_two']?$reward_config['point_two']:$money;
				break;
			case 4:#4万点位
				$rsMoney=$money>$reward_config['point_four']?$reward_config['point_four']:$money;
				break;
			case 6:#6万点位
				$rsMoney=$money>$reward_config['point_six']?$reward_config['point_six']:$money;
				break;
		}

		return $rsMoney;

	}

	/**
	 * 推荐人本月被推荐次数达5次，发生短信通知用户
	 * @author 黄俊
	 * 2017-7-3
	 * return bool
	 */
	public function rec_duanxin($recommend_user,$reward_config){

		$ok=false;//返回结果

		/*计算本月被推荐的数量*/
		//获得本月1号和下月1号、上个月1号
		$date=getMonthFirst();

		//本月
		$where='recommend_user="'.$recommend_user.'" AND STATUS in(2,6) AND update_time BETWEEN "'.$date[0].'" AND "'.$date[1].'"';

		//取得本月所有该会员推荐，并通过二审和三审的订单总数
		$count=M('order')->where($where)->count();
		// echo M()->getLastSql();
		// echo $count;die();
		//比较订单数量，是否达标，只在达到5次的时候，推荐一次
		if($count==$reward_config['rec_notice']){
			$ok=true;
		}

		return $ok;

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



}

?>