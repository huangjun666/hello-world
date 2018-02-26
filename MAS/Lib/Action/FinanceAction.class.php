<?php
/**
 * 财务模块
 * @author 黄俊
 * date 2016-6-28
 */
class FinanceAction extends BaseAction{


	/**
	 * 类似构造函数
	 * @author 黄俊
	 * date 2016-11-28
	 */
    public function _initialize(){
    	#记录当前应该调用哪个左侧主菜单
    	parent::_initialize();
    	session('menu','Finance');
    }

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
		/*
			1、根据会员排序
			2、每个会员独立计算出生活馆收益，团队将，加权分红奖
		*/
		if(IS_POST){

			//权限判断
			if( !in_array($_SESSION['role'], array(3,4)) ){
				$this->error('权限不够！');
			}

			$user=I('user','');

			$member=M('member')->field('member_id,user,name,tel,role,rec_reward,coach_reward,week_ach,month_ach,team_reward,fh_reward')->where(array('user'=>$user))->order('update_time DESC,add_time DESC')->select();

			$this->page=1;//获得页码
			$this->sort=0;//序号
			$this->assign('member',$member);
			$this->finance=D('Finance');
			$this->display();
		}else{
			import('ORG.Util.Page');//引入分页类

			$where=array();
			//权限判断
			if( !in_array($_SESSION['role'], array(3,4)) ){
				// $this->error('权限不够！');
				$where=array('user'=>$_SESSION['user']);
			}

			/*分页*/
			$count=M('member')->where($where)->count();//总数量
			$page=new Page($count,20);
			$limit=$page->firstRow.','.$page->listRows;

			$member=M('member')->field('member_id,user,name,tel,role,rec_reward,coach_reward,week_ach,month_ach,team_reward,fh_reward')->where($where)->limit($limit)->order('update_time DESC,add_time DESC')->select();

			$this->page=$page->show();//获得页码
			$this->sort=$page->firstRow;//序号
			$this->assign('member',$member);
			$this->finance=D('Finance');
			$this->display();
		}
		
	}

	/**
	 * 财务结算记录
	 * @author 黄俊
	 * date 2016-6-28
	 */
	public function log(){
		/**
		 * 0、财务结算在周一、或每月1日，系统自动结算后，才会产生
		 * 1、财务记录在财务表，
		 * 2、通过时间节点展示相应的记录
		 * 3、一键结算，会根据时间节点生成Excel下载，同时改变财务记录状态：结算
		 * 4、查询功能
		 */
		#1、查询类型分为：周结、月结，默认显示周结
			//1、周结显示分销奖、税率、税后工资、点位，其他参考3
			//2、月结显示辅导、团队、分红、馆主收益、和四项总额、税率、税后工资、点位、其他参考3
			//3、姓名(帐号)、开始时间、结束时间、状态、
		#2、时间节点：
			//1、如果时间节点为当天时间，则根据查询类型，自动切换为上周，或上月的时间
		//访问权限判断
		if( !in_array($_SESSION['role'], array(1,2,3,4)) ){
			$this->error('权限不够！');
		}

		$action_type=I('action_type',1,'intval');//结算方式
		$time=I('time',date('Y-m-d'));//时间节点
		$user=I('user','');

		//如果时间节点为当天时间，则根据查询类型，自动切换为上周，或上月的时间
		if($time == date('Y-m-d')){
			if( $action_type == 1){ 
				$time=date('Y-m-d',strtotime("$time - 7 days"));
			}else{
				$time=date('Y-m-d',strtotime("$time - 1 month"));
			}
		}

		// 根据结算方式，获得对应的奖励类型
		if($action_type==1){
			$bonus_type='1';
		}else{
			$bonus_type='2,3,4,5';
		}

		// echo $time;die();

		//根据时间节点，查询出未结算的财务报表
		$finance=M('finance');

		//条件
		$where=' "'.addOneSecond($time).'" BETWEEN start_time AND end_time and bonus_type in('.$bonus_type.')';

		//不是财务或管理员，只能看到自己的工资情况
		if(!in_array($_SESSION['role'], array(3,4))){
			$user=$_SESSION['user'];
		}

		//筛选出某个用户的工资情况
		if(!empty($user)){
			$where.=' and (user="'.$user.'" or name="'.$user.'")';
		}

		$report=$finance->where($where)->order(' user DESC,id ASC')->select();
		// echo M()->getLastSql();die();
		// if( empty($report) ){//如果没有可结算的数据
		// 	$this->error('该时间节点，没有财务数据！');
		// }
		// P($report);die();
		// 生成新的报表
		$newReport=array();
		$user='';//用user进行分组
		$ke=-1;

		foreach ($report as $key => $value) {

			if( $user != $value['user'] ){
				$ke++;
				$user=$value['user'];
				$newReport[$ke]['id']=$value['id'];
				$newReport[$ke]['user']=$value['user'];
				$newReport[$ke]['name']=$value['name'];
				$newReport[$ke]['tel']=$value['tel'];
				$newReport[$ke]['id_card']=$value['id_card'];
				$newReport[$ke]['bank_user']=$value['bank_user'];
				$newReport[$ke]['bank_name']=$value['bank_name'];
				$newReport[$ke]['bank_card']=$value['bank_card'];
				$newReport[$ke]['rec_reward']=0;
				$newReport[$ke]['team_reward']=0;
				$newReport[$ke]['fh_reward']=0;
				$newReport[$ke]['coach_reward']=0;
				$newReport[$ke]['sh_reward']=0;
				$newReport[$ke]['total_reward']=0;
				$newReport[$ke]['point']=$value['point'];
				$newReport[$ke]['action_user']=$value['action_user'];
				$newReport[$ke]['action_name']=$value['action_name'];
				$newReport[$ke]['status']=$value['status'];
				$newReport[$ke]['remarks']=$value['remarks'];
				$newReport[$ke]['start_time']=$value['start_time'];
				$newReport[$ke]['end_time']=$value['end_time'];
			}

			switch ($value['bonus_type']) {
				case 1:
					// 分销奖
					$newReport[$ke]['rec_reward']=round($value['bonus']);
					$newReport[$ke]['total_reward']+=round($value['bonus']);
					break;
				case 2:
					// 团队业绩奖
					$newReport[$ke]['team_reward']=round($value['bonus']);
					$newReport[$ke]['total_reward']+=round($value['bonus']);
					break;
				case 3:
					// 加权分红奖
					$newReport[$ke]['fh_reward']=round($value['bonus']);
					$newReport[$ke]['total_reward']+=round($value['bonus']);
					break;
				case 4:
					// 辅导奖
					$newReport[$ke]['coach_reward']=round($value['bonus']);
					$newReport[$ke]['total_reward']+=round($value['bonus']);
					break;
				case 5:
					// 馆主收益
					$newReport[$ke]['sh_reward']=round($value['bonus']);
					$newReport[$ke]['total_reward']+=round($value['bonus']);
					break;
			}

		}

		import('ORG.Util.Page');//引入分页类

		/*分页*/
		$count=count($newReport);//总数量
		$page=new Page($count,20);

		$limit=$page->firstRow.','.$page->listRows;
		// echo $limit;die();
		$finance=arrPage($newReport,$limit,$count);

		$this->page=$page->show();//获得页码
		$this->sort=$page->firstRow;//序号
		$this->action_type=$action_type;//结算方式
		$this->reward_config=D('Web')->reward_config();//奖励配置
		$this->assign('finance',$finance);

		// P($finance);die();
		$this->display();
	}

	/**
	 * 财务结算记录
	 * @author 黄俊
	 * date 2016-6-28
	 */
	public function editLog(){
		if( !in_array($_SESSION['role'], array(3,4)) ){
			$this->error('权限不够！');
		}
		// P($_GET);die();
		if(IS_POST){

			//接受字段
			$user=I('user','');
			$action_type=I('action_type',0,'intval');
			$start_time=I('start_time','');

			//验证状态值
			$data['status']=I('status',0,'intval');
			if($data['status']==0){
				$this->assign('error','非法操作');
				$this->display();
				exit();
			}

			//验证备注长度
			$data['remarks']=I('remarks','');
			if( strlen($data['remarks'])>30 ){
				$this->assign('error','备注过长');
				$this->display();
				exit();
			}

			//条件
			$where=array(
				'user'=>$user,
				'action_type'=>$action_type,
				'start_time'=>$start_time
				);

			if( M('finance')->where($where)->save($data) ){
				$this->success('编辑成功！');
			}else{
				$this->error('编辑失败，请重试！');
			}

		}else{

			$user=I('user','');
			$action_type=I('action_type',0,'intval');
			$start_time=I('start_time','');

			//条件
			$where=array(
				'user'=>$user,
				'action_type'=>$action_type,
				'start_time'=>$start_time
				);

			//取得要编辑的财务报表数据
			$this->financeLog=M('finance')->where($where)->find();

			$this->display();
		}
		
	}

	/**
	 * 财务结算记录方法备份
	 * @author 黄俊
	 * date 2016-6-28
	 * 备注：方法备份
	 */
	public function log_bak(){
		/**
		 * 0、财务结算在周一、或每月1日，系统自动结算后，才会产生
		 * 1、财务记录在财务表，
		 * 2、通过时间节点展示相应的记录
		 * 3、一键结算，会根据时间节点生成Excel下载，同时改变财务记录状态：结算
		 * 4、查询功能
		 */
		#1、查询类型分为：周结、月结，默认显示周结
			//1、周结显示分销奖、税率、税后工资、点位，其他参考3
			//2、月结显示辅导、团队、分红、馆主收益、和四项总额、税率、税后工资、点位、其他参考3
			//3、姓名(帐号)、开始时间、结束时间、状态、
		#2、时间节点：
			//1、如果时间节点为当天时间，则根据查询类型，自动切换为上周，或上月的时间
		//访问权限判断
		if( !in_array($_SESSION['role'], array(1,2,3,4)) ){
			$this->error('权限不够！');
		}

		$bonus_type=I('bonus_type',0,'intval');
		$time=I('time','');
		$user=I('user','');

		$where='';

		//权限判断
		if(  !in_array($_SESSION['role'], array(3,4))  ){
			// $this->error('权限不够！');
			$where.=' user="'.$_SESSION['user'].'"';
		}else{

			if($time){
				$where.=' "'.$time.'" BETWEEN start_time AND end_time';
			}

			if($bonus_type){
				$where.=' and bonus_type='.$bonus_type;
			}

			if($user){
				$where.=' and user="'.$user.'"';
			}
		}

		import('ORG.Util.Page');//引入分页类

		/*分页*/
		$count=M('finance')->where($where)->count();//总数量
		$page=new Page($count,20);

		$limit=$page->firstRow.','.$page->listRows;

		$finance=M('finance')->where($where)->limit($limit)->order('add_time DESC')->select();

		$this->page=$page->show();//获得页码
		$this->sort=$page->firstRow;//序号
		$this->assign('finance',$finance);
		$this->display();
	}

	/**
	 * 馆主收益记录
	 * @author 黄俊
	 * date 2017-5-21
	 */
	public function guanzhuLog(){

		//访问权限判断
		if( !in_array($_SESSION['role'], array(2,3,4)) ){
			$this->error('页面不存在！');
		}

		//获得馆主收益记录
		$this->log=SERVICE('Finance')->getGuanzhuLog($_SESSION['user']);//'CNB_zlj1892'

		$this->display();
	}

	/**
	 * 馆主收益记录详情
	 * @author 黄俊
	 * date 2017-5-21
	 */
	public function guanzhuLogView(){

		//访问权限判断
		if( !in_array($_SESSION['role'], array(2,3,4)) ){
			$this->error('页面不存在！');
		}

		//获得时间
		$time=I('start_time',0,'intval');

		//避免绕过流程，非法访问
		if(empty($time)){
			$this->error('非法访问！');
		}

		//根据会员和月份时间，取得对应月份的报单详情
		$this->orderLog=SERVICE('Finance')->getGuanzhuOrderLog($_SESSION['user'],$time);//'CNB_zlj1892'
		$this->reward_config=D('Web')->reward_config();

		$this->display();
	}

	/**
	 * 辅导奖比例日志
	 * @author 黄俊
	 * date 2017-3-2
	 */
	public function coachPercentLog(){
		///访问权限判断
		if( !in_array($_SESSION['role'], array(4)) ){
			$this->error('权限不够！');
		}

		import('ORG.Util.Page');//引入分页类

		/*分页*/
		$count=M('coach_percent_log')->count();//总数量
		$page=new Page($count,20);

		$limit=$page->firstRow.','.$page->listRows;

		$coachPercentLog=M('coach_percent_log')->limit($limit)->order('add_time DESC')->select();

		$this->page=$page->show();//获得页码
		$this->sort=$page->firstRow;//序号
		$this->assign('coachPercentLog',$coachPercentLog);
		$this->display();
	}

	/**
	 * 财务自动结算--周结--每个星期一的凌晨2点
	 * 结算类型：分销奖
	 * @author 黄俊
	 * date 2016-7-16
	 */
	public function settlement_week(){

		/*验证密令*/
		$week_pwd=I('week_pwd','');
		if( $week_pwd != C('week_pwd') ){
			exit();
		}

		/*脚本设置*/
		set_time_limit(0);
		session_write_close();//关闭session读写锁，释放锁资源--Linux下生效，window没用
		ini_set('memory_limit', '512M');

		/*结算脚本日志记录*/
		$total=0;//结算总数，结算了多少会员
		$start_time=time();//脚本开始时间
		$end_time=0;//脚本结束时间
		$diff_time=0;//时间差：脚本运行时间
		$type=1;//周结

		/*会员信息取出*/
		$member=M('member');
		$field='member_id,user,pid,name,id_card,tel,bank_user,bank_name,bank_card,week_ach,future_week_ach,rec_reward,coach_reward,team_reward,point,status';
		$memberInfo=$member->field($field)->select();
		$memberCount=count($memberInfo);//统计总数

		/*当前周的开始时间与结束时间*/
		$week=getWeekInfo();

		/*财务表模型*/
		$finance=M('finance');
		$financeD=D('Finance');

		//奖励结完后，记录下周业绩的日志
		$achLog=array();

		//奖励配置
		$reward_config=D('Web')->reward_config();

		/*循环结算每个会员的奖励---周结*/
		foreach ($memberInfo as $key => $value) {

			# 1、判断有无奖励，如有，计算奖励，并写入财务表，清零会员表周结相关的数据
			$financeData=array();//财务表数据
			$memberData=array();//奖励结完后，要修改的会员表数据
			$k=0;

			# 1、分销奖
			if( $value['rec_reward'] != 0 ){//判断有无奖励

				$financeData[$k]['user']=$value['user'];
				$financeData[$k]['name']=$value['name'];
				$financeData[$k]['tel']=$value['tel'];
				$financeData[$k]['id_card']=$value['id_card'];
				$financeData[$k]['bank_user']=$value['bank_user'];
				$financeData[$k]['bank_name']=$value['bank_name'];
				$financeData[$k]['bank_card']=$value['bank_card'];
				$financeData[$k]['point']=$value['point'];
				$financeData[$k]['bonus_type']=1;//分销奖
				$financeData[$k]['bonus']=$value['rec_reward'];//奖金

				//进行点位限制、扣税处理
				// $tmpMoney=SERVICE('Finance')->pointToMoney($value['rec_reward'],$value['point'],$reward_config);
				// $tmpMoney=SERVICE('Finance')->tax($tmpMoney,$reward_config);

				// $financeData[$k]['tax_rate']=$reward_config['tax_rate'];//税率
				// $financeData[$k]['tax_money']=$tmpMoney;//税后奖金
				$financeData[$k]['action_type']=1;//结算方式：周结
				$financeData[$k]['start_time']=$week['last_start'];//时间跨度last_start
				$financeData[$k]['end_time']=$week['last_end'];//时间跨度last_end

				//会员被冻结的情况
				if( $value['status'] == 2 ){
					$financeData[$k]['status']=3;//不可结
				}

				$k++;

			}
			

			# 要修改的会员表数据
			$memberData['member_id']=$value['member_id'];
			$memberData['week_ach']=0;
			$memberData['future_week_ach']=0;
			$memberData['rec_reward']=0;
			$memberData['week_ach_time']=date('Y-m-d H:i:s');
			$memberData['update_time']=date('Y-m-d H:i:s');

			#要记录的周业绩的日志记录
			$achLog[$key]['member_id']=$value['member_id'];
			$achLog[$key]['user']=$value['user'];
			$achLog[$key]['name']=$value['name'];
			$achLog[$key]['pid']=$value['pid'];
			$achLog[$key]['future_ach']=$value['future_week_ach'];
			$achLog[$key]['ach']=$value['week_ach'];
			$achLog[$key]['type']=1;//业绩类型：周业绩
			$achLog[$key]['start_time']=$week['last_start'];//时间跨度last_start
			$achLog[$key]['end_time']=$week['last_end'];//时间跨度last_end


			//如果数据不为空，将数据写入财务表
			if( !empty($financeData) ){

				if(  $finance->addAll($financeData) ){//成功，清空会员相应的业绩和奖金记录

					if( !$member->save($memberData) ){//失败，删除插入的财务表数据，同时发送邮件提醒，退出循环
						
						//条件
						$where=array(
							'user'=>$value['user'],
							'start_time'=>$week['last_start'],//last_start
							'end_time'=>$week['last_end']//last_end
							);
						$finance->where($where)->delete();//删除

						/*浪莎奖金结算异常*/
						$tel='18668049687';
						$user='异常1_'.$value['member_id'].'_'.$memberCount;
						$password=$total;
						duanxin($tel,$user,$password);
						break;
					}

				}else{//如果失败，提醒管理员，退出循环

					/*浪莎奖金结算异常---财务写入异常*/
					$tel='18668049687';
					$user='异常2_'.$value['member_id'].'_'.$memberCount;
					$password=$total;
					duanxin($tel,$user,$password);
					break;
				}
			}else{//如果没有任何奖金，也要清零周业绩
				
				if( !$member->save($memberData) ){
					/*浪莎奖金结算异常---会员业绩清零异常*/
					$tel='18668049687';
					$user='异常3_'.$value['member_id'].'_'.$memberCount;
					$password=$total;
					duanxin($tel,$user,$password);
					break;
				}
			}
			

			//统计
			$total++;

		}

		/*写入业绩日志*/
		if( !M('ach_log')->addAll($achLog) ){//写入失败，发送短信
			/*浪莎奖金结算异常---业绩日志写入失败*/
			$tel='18668049687';
			$user='异常5_';
			$password='业绩日志写入失败';
			duanxin($tel,$user,$password);
		}

		/*写入结算日志*/
		$log['total']=$total;
		$log['start_time']=date('Y-m-d H:i:s',$start_time);
		$log['end_time']=date('Y-m-d H:i:s');
		$log['diff_time']=time()-$start_time;
		$log['type']=$type;

		if( !M('finance_log')->add($log) ){//写入失败，发送邮件
			/*浪莎奖金结算异常---结算日志写入失败*/
			$tel='18668049687';
			$user='异常4_';
			$password='结算日志写入失败';
			duanxin($tel,$user,$password);
		}else{
			echo '成功！';
		}

	}

	/**
	 * 财务自动结算--月结--每个月一号的凌晨4点
	 * 结算类型：分红奖、馆主收益、团队奖、辅导奖
	 * @author 黄俊
	 * date 2016-7-16
	 */
	public function settlement_month(){

		/*验证密令*/
		$month_pwd=I('month_pwd','');
		if( $month_pwd != C('month_pwd') ){
			exit();
		}

		/*脚本设置*/
		set_time_limit(0);
		session_write_close();//关闭session读写锁，释放锁资源--Linux下生效，window没用
		ini_set('memory_limit', '512M');

		/*结算脚本日志记录*/
		$total=0;//结算总数，结算了多少会员
		$start_time=time();//脚本开始时间
		$end_time=0;//脚本结束时间
		$diff_time=0;//时间差：脚本运行时间
		$type=2;//月结

		/*会员信息取出*/
		$member=M('member');
		$field='member_id,user,name,pid,sex,id_card,tel,bank_user,bank_name,bank_card,month_ach,future_month_ach,fh_reward,role,point,status,coach_reward,team_reward,team_star,email';
		$memberInfo=$member->field($field)->select();
		$memberCount=count($memberInfo);//统计总数

		/*当前月的开始时间与结束时间*/
		$month=getMonthFirst();

		/*财务表模型*/
		$finance=M('finance');
		$financeD=D('Finance');

		//奖励结完后，记录下月业绩的日志
		$achLog=array();

		//奖励结完后，记录下星级董事日志
		$team_star_log=array();
		$team_star_key=0;

		//奖励配置
		$reward_config=D('Web')->reward_config();
		
		/*循环结算每个会员的奖励---月结*/
		foreach ($memberInfo as $key => $value) {

			# 1、判断有无奖励，如有，计算奖励，并写入财务表，清零会员表周结相关的数据
			$financeData=array();//财务表数据
			$memberData=array();//奖励结完后，要修改的会员表数据
			
			$k=0;

			# 1、辅导奖
			if( $value['coach_reward'] != 0 ){//判断有无奖励

				$financeData[$k]['user']=$value['user'];
				$financeData[$k]['name']=$value['name'];
				$financeData[$k]['tel']=$value['tel'];
				$financeData[$k]['id_card']=$value['id_card'];
				$financeData[$k]['bank_user']=$value['bank_user'];
				$financeData[$k]['bank_name']=$value['bank_name'];
				$financeData[$k]['bank_card']=$value['bank_card'];
				$financeData[$k]['point']=$value['point'];
				$financeData[$k]['bonus_type']=4;//辅导奖
				$financeData[$k]['bonus']=$value['coach_reward'];//奖金
				$financeData[$k]['action_type']=2;//结算方式：周结
				$financeData[$k]['start_time']=$month[2];//时间跨度
				$financeData[$k]['end_time']=$month[0];//时间跨度

				//会员被冻结的情况
				if( $value['status'] == 2 ){
					$financeData[$k]['status']=3;//不可结
				}
				$k++;

			}
			

			# 2、团队奖
			if( $value['team_reward'] != 0 ){//判断有无奖金
				$financeData[$k]['user']=$value['user'];
				$financeData[$k]['name']=$value['name'];
				$financeData[$k]['tel']=$value['tel'];
				$financeData[$k]['id_card']=$value['id_card'];
				$financeData[$k]['bank_user']=$value['bank_user'];
				$financeData[$k]['bank_name']=$value['bank_name'];
				$financeData[$k]['bank_card']=$value['bank_card'];
				$financeData[$k]['point']=$value['point'];
				$financeData[$k]['bonus_type']=2;//团队奖
				$financeData[$k]['bonus']=$value['team_reward'];//奖金
				$financeData[$k]['action_type']=2;//结算方式：周结
				$financeData[$k]['start_time']=$month[2];//时间跨度
				$financeData[$k]['end_time']=$month[0];//时间跨度

				//会员被冻结的情况
				if( $value['status'] == 2 ){
					$financeData[$k]['status']=3;//不可结
				}
				$k++;
			}

			# 3、生活馆收益
			if( $value['role'] != 1 ){//普通经销商没有收益

				$sh_reward=$financeD->sh_reward($value['user'],true,$reward_config);

				if( $sh_reward != 0 ){//如果存在收益，写入财务表
					$financeData[$k]['user']=$value['user'];
					$financeData[$k]['name']=$value['name'];
					$financeData[$k]['tel']=$value['tel'];
					$financeData[$k]['id_card']=$value['id_card'];
					$financeData[$k]['bank_user']=$value['bank_user'];
					$financeData[$k]['bank_name']=$value['bank_name'];
					$financeData[$k]['bank_card']=$value['bank_card'];
					$financeData[$k]['point']=$value['point'];
					$financeData[$k]['bonus_type']=5;//馆主收益
					$financeData[$k]['bonus']=$sh_reward;//奖金
					$financeData[$k]['action_type']=2;//结算方式：月结
					$financeData[$k]['start_time']=$month[2];//时间跨度
					$financeData[$k]['end_time']=$month[0];//时间跨度

					//会员被冻结的情况
					if( $value['status'] == 2 ){
						$financeData[$k]['status']=3;//不可结
					}
					$k++;
				}	

			}
			

			# 4、分红奖

			//检查上月是否达到要求
			$fh_reward=$financeD->fh_reward($value['member_id'],$value['month_ach'],$reward_config);

			//最终可以分红的此次
			$fh_reward=empty($fh_reward)?$value['fh_reward']:$fh_reward;

			if( $fh_reward != 0 ){//判断是否可以参与分红

				$financeData[$k]['user']=$value['user'];
				$financeData[$k]['name']=$value['name'];
				$financeData[$k]['tel']=$value['tel'];
				$financeData[$k]['id_card']=$value['id_card'];
				$financeData[$k]['bank_user']=$value['bank_user'];
				$financeData[$k]['bank_name']=$value['bank_name'];
				$financeData[$k]['bank_card']=$value['bank_card'];
				$financeData[$k]['point']=$value['point'];
				$financeData[$k]['bonus_type']=3;//分红奖
				$financeData[$k]['bonus']=intval($fh_reward);//奖金,次数
				$financeData[$k]['action_type']=2;//结算方式：月结
				$financeData[$k]['start_time']=$month[2];//时间跨度
				$financeData[$k]['end_time']=$month[0];//时间跨度

				//会员被冻结的情况
				if( $value['status'] == 2 ){
					$financeData[$k]['status']=3;//不可结
				}
				
				$k++;

				//会员剩余分红次数
				$memberData['fh_reward']=$fh_reward-1;

			}
			

			# 要修改的会员表数据
			$memberData['member_id']=$value['member_id'];
			$memberData['all_rec_reward']=0;
			$memberData['coach_reward']=0;
			$memberData['team_reward']=0;
			$memberData['team_lost_reward']=0;
			$memberData['team_star']=0;
			$memberData['month_ach']=0;
			$memberData['future_month_ach']=0;

			//是否是6月、12月:清除总业绩
			$clear_month=date('m');
			if($clear_month%6==0){
				$memberData['total_ach']=0;
				$memberData['future_total_ach']=0;
			}

			$memberData['month_ach_time']=date('Y-m-d H:i:s');
			$memberData['update_time']=date('Y-m-d H:i:s');


			#要记录的月业绩的日志记录
			$achLog[$key]['member_id']=$value['member_id'];
			$achLog[$key]['user']=$value['user'];
			$achLog[$key]['name']=$value['name'];
			$achLog[$key]['pid']=$value['pid'];
			$achLog[$key]['future_ach']=$value['future_month_ach'];
			$achLog[$key]['ach']=$value['month_ach'];
			$achLog[$key]['type']=2;//业绩类型：月业绩
			$achLog[$key]['start_time']=$month[2];//时间跨度
			$achLog[$key]['end_time']=$month[0];//时间跨度

			#要记录的星级董事的日志记录
			//如果本月是星级董事
			if($value['team_star']!=0){
				$team_star_log[$team_star_key]['user']=$value['user'];
				$team_star_log[$team_star_key]['name']=$value['name'];
				$team_star_log[$team_star_key]['sex']=$value['sex'];
				$team_star_log[$team_star_key]['tel']=$value['tel'];
				$team_star_log[$team_star_key]['email']=$value['email'];
				$team_star_log[$team_star_key]['team_star']=$value['team_star'];
				$team_star_log[$team_star_key]['start_time']=$month[2];//时间跨度
				$team_star_log[$team_star_key]['end_time']=$month[0];//时间跨度
				$team_star_key++;
			}
			


			//如果数据不为空，将数据写入财务表
			if( !empty($financeData) ){

				if(  $finance->addAll($financeData) ){//成功，清空会员相应的业绩和奖金记录

					if( !$member->save($memberData) ){//失败，删除插入的财务表数据，同时发送邮件提醒，退出循环
						
						//条件
						$where=array(
							'user'=>$value['user'],
							'start_time'=>$month[2],
							'end_time'=>$month[0]
							);
						$finance->where($where)->delete();//删除

						/*浪莎奖金结算异常*/
						$tel='18668049687';
						$user='异常5_'.$value['member_id'].'_'.$memberCount;
						$password=$total;
						duanxin($tel,$user,$password);
						break;
					}

				}else{//如果失败，发送邮件提醒管理员，退出循环
					/*浪莎奖金结算异常---财务写入异常*/
					$tel='18668049687';
					$user='异常6_'.$value['member_id'].'_'.$memberCount;
					$password=$total;
					duanxin($tel,$user,$password);
					break;
				}
			}else{//如果没有任何奖金，也要清零周业绩
				
				if( !$member->save($memberData) ){
					/*浪莎奖金结算异常---会员业绩清零异常*/
					$tel='18668049687';
					$user='异常7_'.$value['member_id'].'_'.$memberCount;
					$password=$total;
					duanxin($tel,$user,$password);
					break;
				}
			}
			

			//统计
			$total++;

		}

		/*为有资格参与上月进行分红的会员，分红*/
		$financeD->fh_action($reward_config);

		/*写入业绩日志*/
		if( !M('ach_log')->addAll($achLog) ){//写入失败，发送短信
			/*浪莎奖金结算异常---业绩日志写入失败*/
			$tel='18668049687';
			$user='异常9_';
			$password='业绩日志写入失败';
			duanxin($tel,$user,$password);
		}

		/*写入星级懂事日志*/
		if(!empty($team_star_log)){
			if( !M('team_star_log')->addAll($team_star_log) ){
				/*浪莎奖金结算异常---星级懂事日志写入失败*/
				$tel='18668049687';
				$user='异常10_';
				$password='星级懂事日志写入失败';
				duanxin($tel,$user,$password);
			}
		}

		/*写入结算日志*/
		$log['total']=$total;
		$log['start_time']=date('Y-m-d H:i:s',$start_time);
		$log['end_time']=date('Y-m-d H:i:s');
		$log['diff_time']=time()-$start_time;
		$log['type']=$type;

		if( !M('finance_log')->add($log) ){//写入失败，发送邮件
			/*浪莎奖金结算异常---结算日志写入失败*/
			$tel='18668049687';
			$user='异常8_';
			$password='结算日志写入失败';
			duanxin($tel,$user,$password);
		}else{
			echo '成功！';
		}
	}

	/**
	 * 每天例行辅导奖计算--周结--凌晨0点
	 * @author 黄俊
	 * date 2016-7-16
	 */
	public function compute_coach(){

		/*验证密令*/
		$coach_pwd=I('coach_pwd','');
		if( $coach_pwd != C('coach_pwd') ){
			exit();
		}

		/*脚本设置*/
		set_time_limit(0);
		session_write_close();//关闭session读写锁，释放锁资源--Linux下生效，window没用
		ini_set('memory_limit', '512M');

		/*结算脚本日志记录*/
		$total=0;//结算总数，结算了多少会员
		$start_time=time();//脚本开始时间
		$end_time=0;//脚本结束时间
		$diff_time=0;//时间差：脚本运行时间
		$type=3;//辅导奖计算


		/*会员信息取出*/
		$member=M('member');
		$m=new Model();
		$field='member_id,coach_tree';
		$memberInfo=$member->field($field)->order('member_id DESC')->select();
		$memberCount=count($memberInfo);//统计总数

		//奖励配置
		$reward_config=D('Web')->reward_config();

		foreach ($memberInfo as $key => $value) {
			
			
			
			if( !empty($value['coach_tree']) ){# coach_tree为空，不进行计算

				# 1、计算下级总收入
				$ids=trim($value['coach_tree'],',');

				$sql='SELECT SUM(all_rec_reward+coach_reward) AS income FROM `member` WHERE member_id IN ('.$ids.')';

				$rs=$m->query($sql);

				$income=$rs[0]['income'];//可以拿到辅导奖的下级的总收入

				# 2、算出辅导奖后，更新当前会员的辅导奖
				$data=array();
				$data['member_id']=$value['member_id'];
				$data['coach_reward']=$income*$reward_config['fd_percent']*0.01;
				$data['update_time']=date('Y-m-d H:i:s');

				if( !$member->save($data) ){//保存失败发送短信提醒
					/*保存辅导奖失败*/
					$tel='18668049687';
					$user='异常9_';
					$password='保存辅导奖失败';
					duanxin($tel,$user,$password);
					break;
				}

			}
			//统计
			$total++;
		}

		/*财务辅导奖比例日志----辅导奖/总业绩*/
		$coach_percent_log=array();
		$coach_percent_log['coach_reward']=SERVICE('Finance')->getCoachRewardTotal();
		$coach_percent_log['week_ach']=SERVICE('Finance')->getTrueAchTotal();

		//计算比例
		if( $coach_percent_log['week_ach'] ){
			$coach_percent_log['percent']=intval(($coach_percent_log['coach_reward']/$coach_percent_log['week_ach'])*10000);
		}else{
			$coach_percent_log['percent']=0;
		}

		if(!M('coach_percent_log')->add($coach_percent_log)){
			/*财务辅导奖比例日志写入失败*/
			$tel='18668049687';
			$user='异常11_';
			$password='辅导奖写入失败';
			duanxin($tel,$user,$password);
		}
		
		/*写入结算日志*/
		$log['total']=$total;
		$log['start_time']=date('Y-m-d H:i:s',$start_time);
		$log['end_time']=date('Y-m-d H:i:s');
		$log['diff_time']=time()-$start_time;
		$log['type']=$type;

		if( !M('finance_log')->add($log) ){//写入失败，发送短信
			/*日志写入失败*/
			$tel='18668049687';
			$user='异常10_';
			$password='日志写入失败';
			duanxin($tel,$user,$password);
		}else{
			echo '成功！';
		}

	}

	/**
	 * 每天例行团队奖计算--周结--凌晨1点
	 * @author 黄俊
	 * date 2016-7-16
	 */
	public function compute_team(){

		/*验证密令*/
		$team_pwd=I('team_pwd','');
		if( $team_pwd != C('team_pwd') ){
			exit();
		}

		/*脚本设置*/
		set_time_limit(0);
		session_write_close();//关闭session读写锁，释放锁资源--Linux下生效，window没用
		ini_set('memory_limit', '512M');

		/*结算脚本日志记录*/
		$total=0;//结算总数，结算了多少会员
		$start_time=time();//脚本开始时间
		$end_time=0;//脚本结束时间
		$diff_time=0;//时间差：脚本运行时间
		$type=4;//团队奖计算


		/*会员信息取出*/
		$finance=D('Finance');
		$member=M('member');
		$m=new Model();
		$field='member_id,name,tel,month_ach,team_star';
		$memberInfo=$member->field($field)->order('member_id DESC')->select();
		$memberCount=count($memberInfo);//统计总数

		//奖励配置
		$reward_config=D('Web')->reward_config();

		foreach ($memberInfo as $key => $value) {
			#1、获得下级的数据
			#2、处理自身和下级的数据，返回相应的结果：1、自身获得的奖励 2、当前节点已经分配的金额奖励
			#3、保存以上2条数据到会员身上
			
			

			#1、获得下级的数据
			$field='member_id,month_ach,team_lost_reward,add_time';
			$team=$member->field($field)->where(array('pid'=>$value['member_id']))->select();

			#2、处理自身和下级的数据，
			#返回相应的结果：1、自身获得的奖励 2、当前节点已经分配的金额奖励
			$team_reward=$finance->team_reward($value,$team,$reward_config);

			# 3、处理自身和下级的数据，
			#返回相应的结果：当前团队所属星级【30W开始，一星到五星】
			//code....
			$new_team_star=$finance->team_star($value,$team,$reward_config);

			//如果团队星级刷新，则发短信通知用户
			$team_star=$value['team_star'];
			if( $new_team_star>$team_star ){

				$team_star=$new_team_star;//团队星级刷新
				$team_star_str=$finance->team_star_str($team_star);//数字转为字符串
				//发送短信
				star_duanxin($value['tel'],$value['name'],$team_star_str);
			}

			# 4、算出团队奖后，更新当前会员
			$data=array();
			$data['member_id']=$value['member_id'];
			$data['team_reward']=$team_reward['team_reward'];
			$data['team_lost_reward']=$team_reward['team_lost_reward'];
			$data['team_star']=$team_star;
			$data['update_time']=date('Y-m-d H:i:s');

			if( !$member->save($data) ){//保存失败发送短信提醒
				/*保存辅导奖失败*/
				$tel='18668049687';
				$user='异常11_';
				$password='保存团队奖失败';
				duanxin($tel,$user,$password);
				break;
			}
			//统计
			$total++;
		}

		/*写入结算日志*/
		$log['total']=$total;
		$log['start_time']=date('Y-m-d H:i:s',$start_time);
		$log['end_time']=date('Y-m-d H:i:s');
		$log['diff_time']=time()-$start_time;
		$log['type']=$type;

		if( !M('finance_log')->add($log) ){//写入失败，发送短信
			/*日志写入失败*/
			$tel='18668049687';
			$user='异常12_';
			$password='日志写入失败';
			duanxin($tel,$user,$password);
		}else{
			echo '成功！';
		}

	}

	/**
	 * 一键结算所有
	 * 结算类型：所有
	 * @author 黄俊
	 * date 2016-7-16
	 * 返回：下载Excel
	 */
	public function settlement_all(){


		//权限判断
		if(  !in_array($_SESSION['role'], array(3,4))  ){
			$this->error('权限不够！');
		}

		$action_type=I('action_type',1,'intval');//结算方式
		$time=I('time',date('Y-m-d'));//时间节点

		//如果时间节点为当天时间，则根据查询类型，自动切换为上周，或上月的时间
		if($time == date('Y-m-d')){
			if( $action_type == 1){ 
				$time=date('Y-m-d',strtotime("$time - 7 days"));
			}else{
				$time=date('Y-m-d',strtotime("$time - 1 month"));
			}
		}

		// 根据结算方式，获得对应的奖励类型
		if($action_type==1){
			$bonus_type='1';
		}else{
			$bonus_type='2,3,4,5';
		}

		// echo $time;die();

		//根据时间节点，查询出未结算的财务报表
		$finance=M('finance');

		//条件
		$where=' "'.addOneSecond($time).'" BETWEEN start_time AND end_time and status=1 and bonus_type in('.$bonus_type.')';

		$report=$finance->where($where)->order(' user DESC')->select();
		
		if( empty($report) ){//如果没有可结算的数据
			$this->error('该时间节点，没有财务需要结算！');
		}

		// P($report);die();
		//奖励配置
		$reward_config=D('Web')->reward_config();
		// 生成新的报表
		$newReport=array();
		$user='';//用user进行分组
		$ke=-1;

		foreach ($report as $key => $value) {

			if( $user != $value['user'] ){
				$ke++;
				$user=$value['user'];
				$newReport[$ke]['id']=$value['id'];
				$newReport[$ke]['user']=$value['user'];
				$newReport[$ke]['name']=$value['name'];
				$newReport[$ke]['tel']=$value['tel'];
				$newReport[$ke]['id_card']=$value['id_card'];
				$newReport[$ke]['bank_user']=$value['bank_user'];
				$newReport[$ke]['bank_name']=$value['bank_name'];
				$newReport[$ke]['bank_card']=$value['bank_card'];
				$newReport[$ke]['rec_reward']=0;
				$newReport[$ke]['team_reward']=0;
				$newReport[$ke]['fh_reward']=0;
				$newReport[$ke]['coach_reward']=0;
				$newReport[$ke]['sh_reward']=0;
				$newReport[$ke]['total_reward']=0;
				$newReport[$ke]['point']=$value['point'];
				$newReport[$ke]['action_user']=$value['action_user'];
				$newReport[$ke]['action_name']=$value['action_name'];
			}

			switch ($value['bonus_type']) {
				case 1:
					// 分销奖
					$newReport[$ke]['rec_reward']=round($value['bonus']);
					$newReport[$ke]['total_reward']+=round($value['bonus']);
					break;
				case 2:
					// 团队业绩奖
					$newReport[$ke]['team_reward']=round($value['bonus']);
					$newReport[$ke]['total_reward']+=round($value['bonus']);
					break;
				case 3:
					// 加权分红奖
					$newReport[$ke]['fh_reward']=round($value['bonus']);
					$newReport[$ke]['total_reward']+=round($value['bonus']);
					break;
				case 4:
					// 辅导奖
					$newReport[$ke]['coach_reward']=round($value['bonus']);
					$newReport[$ke]['total_reward']+=round($value['bonus']);
					break;
				case 5:
					// 馆主收益
					$newReport[$ke]['sh_reward']=round($value['bonus']);
					$newReport[$ke]['total_reward']+=round($value['bonus']);
					break;
			}


			
		}

		//引入PHPExcel库文件
		import('Class.PHPExcel',APP_PATH);

		//创建对象
		$excel = new PHPExcel();
		$objActSheet = $excel->getActiveSheet();

		/*周结*/
		if($action_type==1){
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
			$tableheader = array('序号','会员账号','姓名','电话','身份证号','开户名','开户行','银行账号','分销奖','总金额','点位信息','税率','最终奖励','操作人');
			
			//填充表头信息
			for($i = 0;$i < count($tableheader);$i++) {
				$objActSheet->setCellValue("$letter[$i]1","$tableheader[$i]");
			}

			$k=2;
			foreach ($newReport as $key => $value) {
				$objActSheet->setCellValue("A".$k,$value['id']);
				$objActSheet->setCellValue("B".$k,$value['user']);
				$objActSheet->setCellValue("C".$k,$value['name']);
				$objActSheet->setCellValue("D".$k,$value['tel'].' ');
				$objActSheet->setCellValue("E".$k,$value['id_card'].' ');
				$objActSheet->setCellValue("F".$k,$value['bank_user']);
				$objActSheet->setCellValue("G".$k,$value['bank_name']);
				$objActSheet->setCellValue("H".$k,$value['bank_card'].' ');
				$objActSheet->setCellValue("I".$k,$value['rec_reward']);
				$objActSheet->setCellValue("J".$k,$value['total_reward']);
				$objActSheet->setCellValue("K".$k,$value['point'].'万');
				$objActSheet->setCellValue("L".$k,$reward_config['tax_rate'].'% ');

				//进行点位限制、扣税处理
	            $tmpMoney=SERVICE('Finance')->pointToMoney($value['total_reward'],$value['point'],$reward_config);
	            $tmpMoney=SERVICE('Finance')->tax($tmpMoney,$reward_config);
				$objActSheet->setCellValue("M".$k,$tmpMoney.' ');

				$action_user=$_SESSION['user'].'('.$_SESSION['name'].')';
				$objActSheet->setCellValue("N".$k,$action_user);
				$k++;
			}
		}
		


		/*月结*/
		if($action_type==2){
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
			$objActSheet->getColumnDimension('O')->setWidth(20);
			$objActSheet->getColumnDimension('P')->setWidth(20);
			$objActSheet->getColumnDimension('Q')->setWidth(20);

			//Excel表格式
			$letter = array('A','B','C','D','E','F','G','H','I','J','K','L','M','N','O','P','Q');

			//表头数组
			$tableheader = array('序号','会员账号','姓名','电话','身份证号','开户名','开户行','银行账号','辅导奖','团队奖','生活馆收益','分红奖','总金额','点位信息','税率','最终奖励','操作人');
			
			//填充表头信息
			for($i = 0;$i < count($tableheader);$i++) {
				$objActSheet->setCellValue("$letter[$i]1","$tableheader[$i]");
			}

			$k=2;
			foreach ($newReport as $key => $value) {
				$objActSheet->setCellValue("A".$k,$value['id']);
				$objActSheet->setCellValue("B".$k,$value['user']);
				$objActSheet->setCellValue("C".$k,$value['name']);
				$objActSheet->setCellValue("D".$k,$value['tel'].' ');
				$objActSheet->setCellValue("E".$k,$value['id_card'].' ');
				$objActSheet->setCellValue("F".$k,$value['bank_user']);
				$objActSheet->setCellValue("G".$k,$value['bank_name']);
				$objActSheet->setCellValue("H".$k,$value['bank_card'].' ');
				$objActSheet->setCellValue("I".$k,$value['coach_reward']);
				$objActSheet->setCellValue("J".$k,$value['team_reward']);
				$objActSheet->setCellValue("K".$k,$value['sh_reward']);
				$objActSheet->setCellValue("L".$k,$value['fh_reward']);
				$objActSheet->setCellValue("M".$k,$value['total_reward']);
				$objActSheet->setCellValue("N".$k,$value['point'].'万');
				$objActSheet->setCellValue("O".$k,$reward_config['tax_rate'].'% ');

				//进行点位限制、扣税处理
	            $tmpMoney=SERVICE('Finance')->pointToMoney($value['total_reward'],$value['point'],$reward_config);
	            $tmpMoney=SERVICE('Finance')->tax($tmpMoney,$reward_config);
				$objActSheet->setCellValue("P".$k,$tmpMoney.' ');

				$action_user=$_SESSION['user'].'('.$_SESSION['name'].')';
				$objActSheet->setCellValue("Q".$k,$action_user);
				$k++;
			}
		}

		//创建Excel输入对象
		$filename='财务报表'.date('Y-m-d');
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

		//财务报表更新
		$data['status']=2;
		$data['action_user']=$_SESSION['user'];
		$data['action_name']=$_SESSION['name'];

		$finance->where($where)->save($data);

	}

	/**
	 * 根据时间节点，导出报表
	 * 结算类型：所有
	 * @author 黄俊
	 * date 2016-7-16
	 * 返回：下载Excel
	 */
	public function settlement_report(){
		//访问权限判断
		if( !in_array($_SESSION['role'], array(3,4)) ){
			$this->error('权限不够！');
		}

		$action_type=I('action_type',1,'intval');//结算方式
		$time=I('time',date('Y-m-d'));//时间节点

		//如果时间节点为当天时间，则根据查询类型，自动切换为上周，或上月的时间
		if($time == date('Y-m-d')){
			if( $action_type == 1){ 
				$time=date('Y-m-d',strtotime("$time - 7 days"));
			}else{
				$time=date('Y-m-d',strtotime("$time - 1 month"));
			}
		}

		// 根据结算方式，获得对应的奖励类型
		if($action_type==1){
			$bonus_type='1';
		}else{
			$bonus_type='2,3,4,5';
		}

		// echo $time;die();

		//根据时间节点，查询出未结算的财务报表
		$finance=M('finance');

		//条件
		$where=' "'.addOneSecond($time).'" BETWEEN start_time AND end_time and status in(1,2,4) and bonus_type in('.$bonus_type.')';

		$report=$finance->where($where)->order(' user DESC')->select();
		
		if( empty($report) ){//如果没有可结算的数据
			$this->error('该时间节点，没有财务数据！');
		}
		// P($report);die();
		//奖励配置
		$reward_config=D('Web')->reward_config();
		// 生成新的报表
		$newReport=array();
		$user='';//用user进行分组
		$ke=-1;

		foreach ($report as $key => $value) {

			if( $user != $value['user'] ){
				$ke++;
				$user=$value['user'];
				$newReport[$ke]['id']=$value['id'];
				$newReport[$ke]['user']=$value['user'];
				$newReport[$ke]['name']=$value['name'];
				$newReport[$ke]['tel']=$value['tel'];
				$newReport[$ke]['id_card']=$value['id_card'];
				$newReport[$ke]['bank_user']=$value['bank_user'];
				$newReport[$ke]['bank_name']=$value['bank_name'];
				$newReport[$ke]['bank_card']=$value['bank_card'];
				$newReport[$ke]['rec_reward']=0;
				$newReport[$ke]['team_reward']=0;
				$newReport[$ke]['fh_reward']=0;
				$newReport[$ke]['coach_reward']=0;
				$newReport[$ke]['sh_reward']=0;
				$newReport[$ke]['total_reward']=0;
				$newReport[$ke]['point']=$value['point'];
				$newReport[$ke]['action_user']=$value['action_user'];
				$newReport[$ke]['action_name']=$value['action_name'];
			}

			switch ($value['bonus_type']) {
				case 1:
					// 分销奖
					$newReport[$ke]['rec_reward']=round($value['bonus']);
					$newReport[$ke]['total_reward']+=round($value['bonus']);
					break;
				case 2:
					// 团队业绩奖
					$newReport[$ke]['team_reward']=round($value['bonus']);
					$newReport[$ke]['total_reward']+=round($value['bonus']);
					break;
				case 3:
					// 加权分红奖
					$newReport[$ke]['fh_reward']=round($value['bonus']);
					$newReport[$ke]['total_reward']+=round($value['bonus']);
					break;
				case 4:
					// 辅导奖
					$newReport[$ke]['coach_reward']=round($value['bonus']);
					$newReport[$ke]['total_reward']+=round($value['bonus']);
					break;
				case 5:
					// 馆主收益
					$newReport[$ke]['sh_reward']=round($value['bonus']);
					$newReport[$ke]['total_reward']+=round($value['bonus']);
					break;
			}

		}

		// P($newReport);die();
		//引入PHPExcel库文件
		import('Class.PHPExcel',APP_PATH);

		//创建对象
		$excel = new PHPExcel();
		$objActSheet = $excel->getActiveSheet();

		/*周结*/
		if($action_type==1){
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
			$tableheader = array('序号','会员账号','姓名','电话','身份证号','开户名','开户行','银行账号','分销奖','总金额','点位信息','税率','最终奖励','操作人');
			
			//填充表头信息
			for($i = 0;$i < count($tableheader);$i++) {
				$objActSheet->setCellValue("$letter[$i]1","$tableheader[$i]");
			}

			$k=2;
			foreach ($newReport as $key => $value) {
				$objActSheet->setCellValue("A".$k,$value['id']);
				$objActSheet->setCellValue("B".$k,$value['user']);
				$objActSheet->setCellValue("C".$k,$value['name']);
				$objActSheet->setCellValue("D".$k,$value['tel'].' ');
				$objActSheet->setCellValue("E".$k,$value['id_card'].' ');
				$objActSheet->setCellValue("F".$k,$value['bank_user']);
				$objActSheet->setCellValue("G".$k,$value['bank_name']);
				$objActSheet->setCellValue("H".$k,$value['bank_card'].' ');
				$objActSheet->setCellValue("I".$k,$value['rec_reward']);
				$objActSheet->setCellValue("J".$k,$value['total_reward']);
				$objActSheet->setCellValue("K".$k,$value['point'].'万');
				$objActSheet->setCellValue("L".$k,$reward_config['tax_rate'].'% ');

				//进行点位限制、扣税处理
	            $tmpMoney=SERVICE('Finance')->pointToMoney($value['total_reward'],$value['point'],$reward_config);
	            $tmpMoney=SERVICE('Finance')->tax($tmpMoney,$reward_config);
				$objActSheet->setCellValue("M".$k,$tmpMoney.' ');

				$action_user=$_SESSION['user'].'('.$_SESSION['name'].')';
				$objActSheet->setCellValue("N".$k,$action_user);
				$k++;
			}
		}
		


		/*月结*/
		if($action_type==2){
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
			$objActSheet->getColumnDimension('O')->setWidth(20);
			$objActSheet->getColumnDimension('P')->setWidth(20);
			$objActSheet->getColumnDimension('Q')->setWidth(20);

			//Excel表格式
			$letter = array('A','B','C','D','E','F','G','H','I','J','K','L','M','N','O','P','Q');

			//表头数组
			$tableheader = array('序号','会员账号','姓名','电话','身份证号','开户名','开户行','银行账号','辅导奖','团队奖','生活馆收益','分红奖','总金额','点位信息','税率','最终奖励','操作人');
			
			//填充表头信息
			for($i = 0;$i < count($tableheader);$i++) {
				$objActSheet->setCellValue("$letter[$i]1","$tableheader[$i]");
			}

			$k=2;
			foreach ($newReport as $key => $value) {
				$objActSheet->setCellValue("A".$k,$value['id']);
				$objActSheet->setCellValue("B".$k,$value['user']);
				$objActSheet->setCellValue("C".$k,$value['name']);
				$objActSheet->setCellValue("D".$k,$value['tel'].' ');
				$objActSheet->setCellValue("E".$k,$value['id_card'].' ');
				$objActSheet->setCellValue("F".$k,$value['bank_user']);
				$objActSheet->setCellValue("G".$k,$value['bank_name']);
				$objActSheet->setCellValue("H".$k,$value['bank_card'].' ');
				$objActSheet->setCellValue("I".$k,$value['coach_reward']);
				$objActSheet->setCellValue("J".$k,$value['team_reward']);
				$objActSheet->setCellValue("K".$k,$value['sh_reward']);
				$objActSheet->setCellValue("L".$k,$value['fh_reward']);
				$objActSheet->setCellValue("M".$k,$value['total_reward']);
				$objActSheet->setCellValue("N".$k,$value['point'].'万');
				$objActSheet->setCellValue("O".$k,$reward_config['tax_rate'].'% ');

				//进行点位限制、扣税处理
	            $tmpMoney=SERVICE('Finance')->pointToMoney($value['total_reward'],$value['point'],$reward_config);
	            $tmpMoney=SERVICE('Finance')->tax($tmpMoney,$reward_config);
				$objActSheet->setCellValue("P".$k,$tmpMoney.' ');

				$action_user=$_SESSION['user'].'('.$_SESSION['name'].')';
				$objActSheet->setCellValue("Q".$k,$action_user);
				$k++;
			}
		}
		

		//创建Excel输入对象
		$filename='财务报表'.date('Y-m-d');
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
	 * 根据时间节点，导出报表
	 * 结算类型：对已经结算过的报表，进行【已发放】标注
	 * @author 黄俊
	 * date 2017-6-19
	 */
	public function pay_off(){
		//权限判断
		if( !in_array($_SESSION['role'], array(3,4))  ){
			$this->error('权限不够！');
		}

		$action_type=I('action_type',1,'intval');//结算方式
		$time=I('time',date('Y-m-d'));//时间节点

		//如果时间节点为当天时间，则根据查询类型，自动切换为上周，或上月的时间
		if($time == date('Y-m-d')){
			if( $action_type == 1){ 
				$time=date('Y-m-d',strtotime("$time - 7 days"));
			}else{
				$time=date('Y-m-d',strtotime("$time - 1 month"));
			}
		}

		// 根据结算方式，获得对应的奖励类型
		if($action_type==1){
			$bonus_type='1';
		}else{
			$bonus_type='2,3,4,5';
		}

		// echo $time;die();

		//根据时间节点，查询出未结算的财务报表
		$finance=M('finance');

		//条件
		$where=' "'.$time.'" BETWEEN start_time AND end_time and status=2 and bonus_type in('.$bonus_type.')';

		$report=$finance->where($where)->order(' user DESC')->select();
		
		if( empty($report) ){//如果没有可结算的数据
			$this->error('该时间节点，没有财务可以进行【已发放】操作！');
		}

		//财务报表更新
		$data['status']=4;
		$data['action_user']=$_SESSION['user'];
		$data['action_name']=$_SESSION['name'];

		//更新
		if($finance->where($where)->save($data)){
			$this->success('操作成功！',U('log',array('action_type'=>$action_type,'time'=>$time)));
		}else{
			$this->error('系统出错，操作失败，请重试！');
		}
	}

	/**
	 * 重建辅导树
	 * @author 黄俊
	 * date 2016-7-16
	 * 返回：无
	 */
	public function create_coach_tree(){
		//权限判断
		if( !in_array($_SESSION['role'], array(4))  ){
			$this->error('权限不够！');
		}

		// 角色判断
		if( !in_array($_SESSION['user'], array('admin')) ){
			$this->error('权限不够！');
		}

		/*脚本设置*/
		set_time_limit(0);
		session_write_close();//关闭session读写锁，释放锁资源--Linux下生效，window没用
		ini_set('memory_limit', '512M');

		/*结算脚本日志记录*/
		$total=0;//结算总数，结算了多少会员
		$start_time=time();//脚本开始时间
		$end_time=0;//脚本结束时间
		$diff_time=0;//时间差：脚本运行时间
		$type=5;//重建辅导树

		/*清除旧的辅导树*/
		M()->execute("UPDATE `member` SET coach_tree=''");

		/*会员信息取出*/
		$finance=D('Finance');
		$member=M('member');
		$m=new Model();
		$field='member_id,pid,recommend_user';
		$memberInfo=$member->field($field)->order('member_id ASC')->select();
		$memberCount=count($memberInfo);//统计总数

		//奖励配置
		$reward_config=D('Web')->reward_config();

		foreach ($memberInfo as $key => $value) {
			if( $value['pid'] != 0 ){
				$finance->coach_reward($value,$reward_config);
			}
			$total++;
		}

		/*写入结算日志*/
		$log['total']=$total;
		$log['start_time']=date('Y-m-d H:i:s',$start_time);
		$log['end_time']=date('Y-m-d H:i:s');
		$log['diff_time']=time()-$start_time;
		$log['type']=$type;

		if( !M('finance_log')->add($log) ){//写入失败，发送短信
			/*日志写入失败*/
			$tel='18668049687';
			$user='异常13_';
			$password='日志写入失败';
			duanxin($tel,$user,$password);
		}else{
			echo '成功！';
		}

	}

	/**
	 * 手动执行月度分红
	 * @author 黄俊
	 * date 2016-7-16
	 * 返回：无
	 */
	public function fh_action(){
		//权限判断
		if( !in_array($_SESSION['role'], array(4))  ){
			$this->error('权限不够！');
		}
		/*为有资格参与上月进行分红的会员，分红*/
		D('Finance')->fh_action();

		echo "成功！";
	}

}
?>