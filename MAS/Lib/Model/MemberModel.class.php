<?php

/**
 * 会员模型
 * 说明：处理会员模块数据，及提供会员模块相关的服务
 * @author 黄俊
 * date 2016-6-28
 */

class MemberModel extends BaseModel{

	/**
	 * 根据member_id获得会员信息
	 * @author 黄俊
	 * date 2016-7-1
	 */

	public function getUser($member_id){

		$m=new Model();//实例化模型

		$sql='';//sql语句
		$sql.='SELECT m.*,p.`areaname` province,c.`areaname` city,d.`areaname` district FROM `member` m';
		$sql.=' LEFT JOIN `shop_area` p ON p.`id`=m.`ProvinceID`';
		$sql.=' LEFT JOIN `shop_area` c ON c.`id`=m.`CityID`';
		$sql.=' LEFT JOIN `shop_area` d ON d.`id`=m.`DistrictID`';
		$sql.=' WHERE m.member_id='.$member_id;
		$rs=$m->query($sql);
		
		if( empty($rs) ){
			return false;
		}else{
			return $rs[0];
		}

	}

	/**
	 * 会员登录日志记录
	 * @author 黄俊
	 * date 2016-7-1
	 * 参数：$user:用户名，$success：登录状态：1成功，2失败
	 */
	public function login_log($user,$success=1){

		$data['user']=$user;
		$data['success']=$success;
		$data['login_ip']=get_client_ip();

		if( M('member_login_log')->add($data) ){//写入日志
			return true;
		}else{
			return false;
		}
		

	}

	/**
	 * 检查会员是否被限制登录
	 * @author 黄俊
	 * date 2016-7-1
	 * 参数：$user:用户名，$limit：登录次数限制，$time:时间范围
	 * 返回：可以登录的剩余次数
	 */
	public function login_limit($user,$limit=3,$time=300){

		//查询过去$time时间内，默认300秒  登录失败的次数
		$count=M('member_login_log')->where('user="'.$user.'" and success=2 and time BETWEEN "'.date('Y-m-d H:i:s',time()-$time).'" and "'.date('Y-m-d H:i:s',time()).'"')->count();

		//比对$limit,返回差值
		$num=$limit-$count;
		return $num;
	}

	/**
	 * 会员通过身份证找回密码日志记录
	 * @author 黄俊
	 * date 2016-7-1
	 * 参数：$user:用户名，$success：登录状态：1成功，2失败
	 */
	public function findPwd_log($user,$success=1){

		$data['user']=$user;
		$data['success']=$success;
		$data['type']=2;
		$data['login_ip']=get_client_ip();

		if( M('member_login_log')->add($data) ){//写入日志
			return true;
		}else{
			return false;
		}
		

	}

	/**
	 * 检查会员是否被限制通过验证身份证找回密码
	 * @author 黄俊
	 * date 2016-7-1
	 * 参数：$user:用户名，$limit：登录次数限制，$time:时间范围
	 * 返回：可以登录的剩余次数
	 */
	public function findPwd_limit($user,$limit=3,$time=300){

		//查询过去$time时间内，默认300秒  登录失败的次数
		$count=M('member_login_log')->where('user="'.$user.'" and type=2 and success=2 and time BETWEEN "'.date('Y-m-d H:i:s',time()-$time).'" and "'.date('Y-m-d H:i:s',time()).'"')->count();

		//比对$limit,返回差值
		$num=$limit-$count;
		return $num;
	}

	/**
	 * 检查姓名生成会员账号
	 * @author 黄俊
	 * date 2016-7-1
	 * 参数：$name:姓名
	 * 返回：会员账号
	 */
	public function createUser($name,$team_tag=''){

		import('ORG.Util.Pinyin');//引入拼音类
		$py = new PinYin();

		$userPy=$py->getFirstPY($name);//生成姓名的拼音
		$userPy=empty($team_tag)?$userPy:$team_tag.'_'.$userPy;//为会员加上团队标签前缀

		$user='';//会员账号
		$userArr=array('1','2');

		while ( !empty($userArr) ) {//生成唯一的会员账号

			$user=$userPy.randomString(2,4);//生成会员账号

			$userArr=M('member')->where(array('user'=>$user))->find();

		}

		//返回生成的会员账号
		return $user;

	}

	/**
	 * 根据订单自动创建会员
	 * @author 黄俊
	 * date 2016-7-1
	 * 参数：$orderInfo:完整的订单信息
	 * 返回：生成后的会员信息
	 */
	public function createMember($orderInfo){

		//查询安置人信息，安置人决定团度归属
		$member=M('member');
		$place=$member->where(array('user'=>$orderInfo['place_user']))->find();

		$memberdata['pid']=$place['member_id'];//安置人会员id

		$placePid=empty($place['pid'])?'':''.$place['pid'];
		$memberdata['tree']=empty($place['tree'])?$placePid:$placePid.','.$place['tree'];//团队树状节点

		$memberdata['team_tag']=$place['team_tag'];//安置人团度标签

		$memberdata['user']=$this->createUser($orderInfo['name'],$memberdata['team_tag']);//根据姓名生成账号
		$memberPassword='m';//随机密码
		$memberPassword.=randomString(2,6);//随机密码
		$memberdata['password']=encrypt($memberPassword);//密码加密
		
		$memberdata['name']=$orderInfo['name'];//姓名
		$memberdata['sex']=$orderInfo['sex'];//性别
		$memberdata['id_card']=$orderInfo['id_card'];//身份证号
		$memberdata['ProvinceID']=$orderInfo['ProvinceID'];//省
		$memberdata['CityID']=$orderInfo['CityID'];//市
		$memberdata['DistrictID']=$orderInfo['DistrictID'];//区
		$memberdata['adress']=$orderInfo['adress'];//地址
		$memberdata['tel']=$orderInfo['tel'];//电话
		$memberdata['bank_name']=$orderInfo['bank_name'];//银行名
		$memberdata['bank_user']=$orderInfo['bank_user'];//开户名
		$memberdata['bank_card']=$orderInfo['bank_card'];//银行账号
		$memberdata['email']=$orderInfo['email'];//邮箱
		$memberdata['update_time']=date('Y-m-d H:i:s');//更新时间
		$memberdata['shop']=$orderInfo['name'].'的店铺';//店铺名称
		$memberdata['point']=$this->money_point($orderInfo['money']);//点位信息
		$memberdata['role']=$orderInfo['type'];//订单类型决定角色信息
		$memberdata['recommend_user']=$orderInfo['recommend_user'];//推荐人
		$memberdata['handle']=$orderInfo['handle'];//推荐人
		
		/*创建会员*/		
		$member_id=$member->add($memberdata);

		if($member_id){

			$memberdata['member_id']=$member_id;
			$memberdata['password']=$memberPassword;

			//返回会员信息
			return $memberdata;
		}else{//失败返回false
			return false;
		}


	}

	/**
	 * 通过身份证号，判断是否存在该身份证申请的会员
	 * @author 黄俊
	 * date 2016-7-6
	 * 参数：$id_card
	 * 返回：存在true，不存在false
	 */
	public function existMember($id_card){

		$member=M('member')->where(array('id_card'=>$id_card))->find();

		if( empty($member) ){
			return false;
		}else{
			return $member;
		}

	}

	/**
	 * 根据订单金额，提升会员点位
	 * @author 黄俊
	 * date 2016-7-6
	 * 参数：订单金额$money,会员$id_card
	 * 返回：存在true，不存在false
	 */
	public function up_point($money,$id_card,$orderType){

		/*通过金额和订单类型检查该订单是否满足提升点位的条件*/
		if( !in_array($money, array(13200,20000,33200,40000,53200)) || !in_array($orderType, array(1,2)) ){
			return ture;//不满足，返回true，结束流程
		}

		/*通过时间，判断--30天*/
		$update_time=M('order')->where('id_card="'.$id_card.'" AND `status` IN(2,5,6)')->order('update_time ASC')->getField('update_time');
		if( time()-strtotime($update_time) > 60*60*24*30 ){
			return true;
		}

		$member=M('member');

		//订单的金额，省略单位W
		// $money=($money-1)*2;

		//会员信息
		// $memberInfo=$member->where(array('id_card'=>$id_card))->find();

		//会员当前点位金额，省略单位W
		// $point=($memberInfo['point']-1)*2;

		//获得当前会员之前的报单金额status:2、5、6
		$moneyTotal=M('order')->field('SUM(money) moneyTotal')->where('id_card="'.$id_card.'" and orderType in(1,2) AND `status` IN(2,5,6)')->find();

		//合并之后的金额
		$point=$this->money_point($moneyTotal['moneyTotal']);
		$point=$point>=6?6:$point;

		//点位类型
		$data['point']=$point;
		$data['update_time']=date('Y-m-d H:i:s');

		//保存点位
		if( $member->where(array('id_card'=>$id_card))->save($data) ){
			return true;
		}else{
			return false;
		}

	}

	/**
	 * 根据订单金额，降低会员点位
	 * @author 黄俊
	 * date 2016-7-6
	 * 参数：订单金额$money,会员$id_card
	 * 返回：存在true，不存在false
	 */
	public function down_point($money,$id_card,$orderType){

		/*通过金额和订单类型检查该订单是否满足降低点位的条件*/
		if( !in_array($money, array(13200,20000,33200,40000,53200)) || !in_array($orderType, array(1,2)) ){
			return ture;//不满足，返回true，结束流程
		}

		$member=M('member');

		//获得当前会员之前的报单金额status:2、5、6
		$moneyTotal=M('order')->field('SUM(money) moneyTotal')->where('id_card="'.$id_card.'" and orderType in(1,2) AND `status` IN(2,5,6)')->find();

		//合并之后的金额
		$point=$this->money_point($moneyTotal['moneyTotal']);
		$point=$point>=6?6:$point;

		//点位类型
		$data['point']=$point;
		$data['update_time']=date('Y-m-d H:i:s');

		//保存点位
		if( $member->where(array('id_card'=>$id_card))->save($data) ){
			return true;
		}else{
			return false;
		}

	}

	/**
	 * 根据订单金额，返回对应的点位
	 * @author 黄俊
	 * date 2016-7-6
	 * 参数：订单金额$money
	 */
	public function money_point($money){

		if( $money <= 0 ){
			return 0;
		}elseif ( $money > 0 && $money <= 10000 ) {
			return 1;
		}elseif ( $money > 10000 && $money <= 20000 ) {
			return 2;
		}elseif ( $money > 20000 && $money <= 40000 ) {
			return 4;
		}elseif ( $money > 40000 ) {
			return 6;
		}

	}

	/**
	 * 新增会员时，奖会员ID加入到公司账号的辅导树中
	 * @author 黄俊
	 * date 2016-7-6
	 * 参数：会员$member_id
	 */
	public function joinLSMT($member_id){
		//实例化模型
		$m=new Model();

		$sql=' update member set coach_tree=CONCAT(coach_tree,",",'.$member_id.') where member_id=2 ';
		$m->execute($sql);
	}


}

?>