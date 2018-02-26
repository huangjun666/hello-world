<?php
/**
 * 生活馆模块
 * @author 黄俊
 * date 2017-7-31
 */
class ShAction extends BaseAction{

	/**
	 * 首页
	 * @author 黄俊
	 * date 2017-7-31
	 */
	public function index(){
		//权限判断---加上白名单
		if( !in_array($_SESSION['role'], array(2,3,4)) && !in_array($_SESSION['user'], SERVICE('System')->get_white_config('sh')) ){
			$this->error('权限不够！');
		}

		// var_dump(strtotime('0000-00-00 00:00:00'));die();
		if(IS_POST){

			$sh_tel=I('sh_tel','');
			$where='';
			if(!empty($sh_tel)){
				$where.=" sh_tel='".$sh_tel."'";
			}else{
				$this->redirect('index');
			}
			
			$member=SERVICE('Sh')->getMemberList($where);//列表

			//计算各个经销商的房租状态 1、正常 2、即将到期 3、过期
			foreach ($member as $key => $value) {

				$sh_room_endtime=intval(strtotime($value['sh_room_endtime']));//房租到期时间时间戳
				$current_time=time();//当前时间戳

				//1、正常状态，到期时间大于1个月60*60*24*30
				if( ($sh_room_endtime-$current_time) >= 60*60*24*30 ){
					$member[$key]['sh_status']=1;
				}

				//2、即将到期，到期时间小于1个月60*60*24*30，大于当前时间
				if( ($sh_room_endtime-$current_time) < 60*60*24*30 && $sh_room_endtime >= $current_time ){
					$member[$key]['sh_status']=2;
				}

				//3、过期，到期时间小于当前时间
				if( $sh_room_endtime < $current_time ){
					$member[$key]['sh_status']=3;
				}

			}

			$this->page=1;//获得页码
			$this->sort=0;//序号
			$this->assign('member',$member);
			$this->display();

		}else{
			import('ORG.Util.Page');//引入分页类

			$where="`user` NOT IN('admin','LSMT')";//条件

			//如果是通过白名单进来的经销商
			if( $_SESSION['role'] == 1 ){
				$this->display();
				die();
			}

			//如果是馆主
			if( $_SESSION['role'] == 2 ){
				$where.=" and handle='".$_SESSION['user']."'";
			}

			/*分页*/
			$count=SERVICE('Sh')->getMemberCount($where);//总数量
			$page=new Page($count,20);
			$limit=$page->firstRow.','.$page->listRows;

			$member=SERVICE('Sh')->getMemberList($where,$limit);//列表

			//计算各个经销商的房租状态 1、正常 2、即将到期 3、过期
			foreach ($member as $key => $value) {

				$sh_room_endtime=intval(strtotime($value['sh_room_endtime']));//房租到期时间时间戳
				$current_time=time();//当前时间戳

				//1、正常状态，到期时间大于1个月60*60*24*30
				if( ($sh_room_endtime-$current_time) >= 60*60*24*30 ){
					$member[$key]['sh_status']=1;
				}

				//2、即将到期，到期时间小于1个月60*60*24*30，大于当前时间
				if( ($sh_room_endtime-$current_time) < 60*60*24*30 && $sh_room_endtime >= $current_time ){
					$member[$key]['sh_status']=2;
				}

				//3、过期，到期时间小于当前时间
				if( $sh_room_endtime < $current_time ){
					$member[$key]['sh_status']=3;
				}

			}

			// P($member);die();

			$this->page=$page->show();//获得页码
			$this->sort=$page->firstRow;//序号
			$this->assign('member',$member);
			$this->display();
		}
		
	}

	/**
	 * 编辑会员生活馆信息
	 * @author 黄俊
	 * date 2017-8-3
	 */
	public function edit(){
		//权限判断
		if( !in_array($_SESSION['role'], array(2,3,4)) ){
			$this->error('权限不够！');
		}

		if(IS_POST){
			//获取参数
			$data['member_id']=I('member_id',0,'intval');

			$data['sh_ProvinceID']=I('province',0,'intval');
			$data['sh_CityID']=I('city',0,'intval');
			$data['sh_DistrictID']=I('district',0,'intval');
			//身份证所在地区必选
			if ( $data['sh_ProvinceID'] == 0 || $data['sh_CityID'] == 0 || $data['sh_DistrictID'] == 0 ) {
				$this->assign('error','身份证所在地区必选');
				$this->display('edit');
				exit();
			}

			$data['sh_adress']=I('adress','');
			//详细地址不可以为空
			if( empty($data['sh_adress']) ){
				$this->assign('error','详细地址不可以为空');
				$this->display('edit');
				exit();
			}

			$data['sh_tel']=I('sh_tel','');
			//经销商预留手机号
			if( !is_tel($data['sh_tel']) ){
				$this->assign('error','经销商预留手机号不对');
				$this->display('edit');
				exit();
			}
			
			$data['sh_room_endtime']=I('sh_room_endtime','');
			$data['update_time']=date('Y-m-d H:i:s');//更新时间

			//保存
			if( M('member')->save($data) ){
				$this->success('编辑成功！',U('index'));
			}else{
				$this->error('编辑失败，请重试！',U('index'));
			}
		}else{
			//获得会员ID
			$member_id=I('member_id',0,'intval');

			$member=SERVICE('Sh')->getMemberView(' member_id='.$member_id);
			// P($member);die();
			$this->assign('member',$member);
			$this->display('edit');
		}
		
	}


	/**
	 * 会员详情
	 * @author 黄俊
	 * date 2017-8-3
	 */
	public function view(){

		//权限判断---加上白名单
		if( !in_array($_SESSION['role'], array(2,3,4)) && !in_array($_SESSION['user'], SERVICE('System')->get_white_config('sh')) ){
			$this->error('权限不够！');
		}

		//获取参数 
		$member_id=I('member_id',0,'intval');

		$member=SERVICE('Sh')->getMemberView(' member_id='.$member_id);

		$this->member=$member;
		$this->display('view');
	}

	/**
	 * 生活馆
	 * @author 黄俊
	 * date 2017-8-6
	 */
	public function shop(){

		//权限判断---加上白名单
		if( !in_array($_SESSION['role'], array(2,3,4)) && !in_array($_SESSION['user'], SERVICE('System')->get_white_config('sh')) ){
			$this->error('权限不够！');
		}

		if(IS_POST){

			$sh_ProvinceID=I('province',0,'intval');
			$sh_CityID=I('city',0,'intval');
			$sh_DistrictID=I('district',0,'intval');
			$name=I('name','');
			$action=I('action',0,'intval');//使用的表单2提交的


			//查询条件
			$where='';
			$where.=' sh_ProvinceID='.$sh_ProvinceID.' and sh_CityID='.$sh_CityID.' and sh_DistrictID='.$sh_DistrictID;

			//使用的表单2提交的
			if($action){
				if($name){
					$where=' `name` LIKE "%'.$name.'%" ';
				}else{
					$this->redirect('shop');
				}
			}
			
			$member=SERVICE('Sh')->getMemberList($where);//列表

			$this->page=1;//获得页码
			$this->sort=0;//序号
			$this->assign('member',$member);
			$this->display();

		}else{
			import('ORG.Util.Page');//引入分页类

			$where=' role in(1,2)';//条件

			//如果是通过白名单进来的经销商
			if( in_array($_SESSION['role'], array(1,2)) ){
				$this->display();
				die();
			}

			// //如果是馆主
			// if( $_SESSION['role'] == 2 ){
			// 	$where.=" handle='".$_SESSION['user']."'";
			// }

			/*分页*/
			$count=SERVICE('Sh')->getMemberCount($where);//总数量
			$page=new Page($count,20);
			$limit=$page->firstRow.','.$page->listRows;

			$member=SERVICE('Sh')->getMemberList($where,$limit);//列表

			// P($member);die();

			$this->page=$page->show();//获得页码
			$this->sort=$page->firstRow;//序号
			$this->assign('member',$member);
			$this->display();
		}

	}

	/**
	 * 编辑生活馆信息
	 * @author 黄俊
	 * date 2017-8-6
	 */
	public function shopEdit(){
		//权限判断
		if( !in_array($_SESSION['role'], array(2,3,4)) ){
			$this->error('权限不够！');
		}

		if(IS_POST){
			//获取参数
			$data['member_id']=I('member_id',0,'intval');

			$data['sh_ProvinceID']=I('province',0,'intval');
			$data['sh_CityID']=I('city',0,'intval');
			$data['sh_DistrictID']=I('district',0,'intval');
			//身份证所在地区必选
			if ( $data['sh_ProvinceID'] == 0 || $data['sh_CityID'] == 0 || $data['sh_DistrictID'] == 0 ) {
				$this->assign('error','身份证所在地区必选');
				$this->display();
				exit();
			}

			$data['sh_adress']=I('adress','');
			//详细地址不可以为空
			if( empty($data['sh_adress']) ){
				$this->assign('error','详细地址不可以为空');
				$this->display();
				exit();
			}

			$data['sh_tel']=I('sh_tel','');
			//馆主预留手机号
			if( !is_tel($data['sh_tel']) ){
				$this->assign('error','馆主预留手机号不对');
				$this->display();
				exit();
			}
			
			$data['sh_status']=I('sh_status',5,'intval');
			$data['sh_update_time']=date('Y-m-d H:i:s');//更新时间

			//保存
			if( M('member')->save($data) ){
				$this->success('编辑成功！',U('shop'));
			}else{
				$this->error('编辑失败，请重试！',U('shop'));
			}
		}else{
			//获得会员ID
			$member_id=I('member_id',0,'intval');

			$member=SERVICE('Sh')->getMemberView(' member_id='.$member_id);
			// P($member);die();
			$this->assign('member',$member);
			$this->display();
		}
		
	}


	/**
	 * 会员详情
	 * @author 黄俊
	 * date 2017-8-3
	 */
	public function shopView(){

		//权限判断---加上白名单
		if( !in_array($_SESSION['role'], array(2,3,4)) && !in_array($_SESSION['user'], SERVICE('System')->get_white_config('sh')) ){
			$this->error('权限不够！');
		}

		//获取参数 
		$member_id=I('member_id',0,'intval');

		$member=SERVICE('Sh')->getMemberView(' member_id='.$member_id);

		$this->member=$member;
		$this->display();
	}

}
?>