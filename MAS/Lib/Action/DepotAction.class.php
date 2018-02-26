<?php
/**
 * 仓库模块
 * @author 黄俊
 * date 2016-6-28
 */
class DepotAction extends BaseAction{

	/**
	 * 类似构造函数
	 * @author 黄俊
	 * date 2016-11-28
	 */
    public function _initialize(){
    	parent::_initialize();
    	#记录当前应该调用哪个左侧主菜单
    	session('menu','Depot');
    }

	/**
	 * 首页
	 * @author 黄俊
	 * date 2016-11-21
	 */
	public function index(){
		//权限判断
		if( !in_array($_SESSION['role'], array(3,4,5)) ){
			$this->error('权限不够！');
		}
		if(IS_POST){

			//货物名称
			$name=I('name','');

			// 查询条件
			$where="name like '%".$name."%' and status in(1,3)";//

			// 查询结果
			$goodsList=M('goods')->where($where)->order('sort DESC,update_time DESC,add_time DESC')->select();

			$this->page=1;//获得页码
			$this->sort=0;//序号
			$this->assign('goodsList',$goodsList);
			$this->display();
		}else{

			import('ORG.Util.Page');//引入分页类

			$where="status in(1,3)";
			/*分页*/
			$count=M('goods')->where($where)->count();//总数量
			$page=new Page($count,20);
			$limit=$page->firstRow.','.$page->listRows;

			$this->goodsList=M('goods')->where($where)->order('sort DESC')->limit($limit)->select();
			$this->page=$page->show();//获得页码
			$this->sort=$page->firstRow;//序号

			$this->display();
		}
		
		
	}

	/**
	 * 货物栏目分类
	 * @author 黄俊
	 * date 2016-11-21
	 */
	public function goodsCate(){
		//权限判断
		if( !in_array($_SESSION['role'], array(3,4,5)) ){
			$this->error('权限不够！');
		}

		$this->goodsCate=M('goods_cate')->where('status=1')->order('add_time ASC')->select();
		$this->display();
		
	}

	/**
	 * 添加栏目分类
	 * @author 黄俊
	 * date 2016-11-21
	 */
	public function addGoodsCate(){
		//权限判断
		if( !in_array($_SESSION['role'], array(3,4,5)) ){
			$this->error('权限不够！');
		}

		if(IS_POST){

			//接收数据
			$data['name']=I('name','');

			//验证栏目名称
			if( empty($data['name']) ){
				$this->assign('error','栏目名称不能为空');
				$this->display();
				exit();
			}

			$data['add_time']=date('Y-m-d H:i:s');
			$data['update_time']=date('Y-m-d H:i:s');
			$data['handle_user']=$_SESSION['user'];
			$data['handle_name']=$_SESSION['name'];

			// 保存
			$cate_id=M('goods_cate')->add($data);
			if($cate_id){
				$this->success('添加成功！',U('goodsCate'));
			}else{
				$this->error('网络不好，请稍候再试！');
			}


		}else{
			$this->display();
		}

		
		
	}

	/**
	 * 编辑栏目分类
	 * @author 黄俊
	 * date 2016-11-21
	 */
	public function editGoodsCate(){
		//权限判断
		if( !in_array($_SESSION['role'], array(3,4,5)) ){
			$this->error('权限不够！');
		}

		if(IS_POST){

			//接收数据
			$data['cate_id']=I('cate_id',0,'intval');
			$data['name']=I('name','');

			//验证栏目名称
			if( $data['cate_id']==0 ){
				$this->assign('error','非法操作');
				$this->display();
				exit();
			}

			//验证栏目名称
			if( empty($data['name']) ){
				$this->assign('error','栏目名称不能为空');
				$this->display();
				exit();
			}

			$data['update_time']=date('Y-m-d H:i:s');
			$data['handle_user']=$_SESSION['user'];
			$data['handle_name']=$_SESSION['name'];

			// 保存更新
			if(M('goods_cate')->save($data)){
				$this->success('添加成功！',U('goodsCate'));
			}else{
				$this->error('网络不好，请稍候再试！');
			}


		}else{
			// 参数
			$cate_id=I('cate_id',0,'intval');
			//查询栏目
			$this->cate=M('goods_cate')->where(array('cate_id'=>$cate_id))->find();
			$this->display();
		}

		
		
	}

	/**
	 * 删除栏目分类
	 * @author 黄俊
	 * date 2016-11-21
	 */
	public function delGoodsCate(){
		//权限判断
		if( !in_array($_SESSION['role'], array(3,4,5)) ){
			$this->error('权限不够！');
		}

		//接收数据
		$data['cate_id']=I('cate_id',0,'intval');

		//验证栏目名称
		if( $data['cate_id']==0 ){
			$this->assign('error','非法操作');
			$this->display();
			exit();
		}

		$data['status']=2;//状态：1、正常 2、删除
		$data['update_time']=date('Y-m-d H:i:s');
		$data['handle_user']=$_SESSION['user'];
		$data['handle_name']=$_SESSION['name'];

		// 更改栏目状态
		if(M('goods_cate')->save($data)){

			//更改货物状态
			M('goods')->where(array('cate_id'=>$data['cate_id']))->save($data);
			$this->success('删除成功！',U('goodsCate'));
			
		}else{
			$this->error('网络不好，请稍候再试！');
		}	
		
	}

	/**
	 * 货物列表
	 * @author 黄俊
	 * date 2016-11-21
	 */
	public function goodsList(){
		//权限判断
		if( !in_array($_SESSION['role'], array(3,4,5)) ){
			$this->error('权限不够！');
		}

		if(IS_POST){

			//接收参数
			$name=I('name','');//货物名称
			$cate_id=I('cate_id',0,'intval');//货物分类

			// 查询条件
			$where="cate_id=".$cate_id." and name like '%".$name."%' and status in(1,3)";

			// 查询结果
			$goodsList=M('goods')->where($where)->order('sort DESC,update_time DESC,add_time DESC')->select();
			
			// 分类
			$this->cate=M('goods_cate')->where(array('cate_id'=>$cate_id))->find();
			$this->page=1;//获得页码
			$this->sort=0;//序号
			$this->assign('goodsList',$goodsList);
			$this->display();
		}else{

			//接收参数
			$cate_id=I('cate_id',0,'intval');

			$this->cate=M('goods_cate')->where(array('cate_id'=>$cate_id))->find();

			import('ORG.Util.Page');//引入分页类

			$where="cate_id=".$cate_id."  and status in(1,3)";
			/*分页*/
			$count=M('goods')->where($where)->count();//总数量
			$page=new Page($count,20);
			$limit=$page->firstRow.','.$page->listRows;

			$this->goodsList=M('goods')->where($where)->order('sort DESC')->limit($limit)->select();
			$this->page=$page->show();//获得页码
			$this->sort=$page->firstRow;//序号

			$this->display();

		}

		
		
	}

	/**
	 * 添加货物
	 * @author 黄俊
	 * date 2016-11-21
	 */
	public function addGoods(){
		//权限判断
		if( !in_array($_SESSION['role'], array(3,4,5)) ){
			$this->error('权限不够！');
		}

		if(IS_POST){

			//接收数据
			$data['cate_id']=I('cate_id',0,'intval');

			//验证栏目id
			if( $data['cate_id']==0 ){
				$this->assign('error','非法操作');
				$this->display();
				exit();
			}

			//验证货物名称
			$data['name']=I('name','');
			if( empty($data['name']) ){
				$this->assign('error','货物名称必填');
				$this->display();
				exit();
			}

			//验证货物编号
			$data['number']=I('number','');
			if( empty($data['number']) ){
				$this->assign('error','货物编号必填');
				$this->display();
				exit();
			}

			//验证货物尺寸
			$data['size']=I('size','');
			if( empty($data['size']) ){
				$this->assign('error','货物尺寸必填');
				$this->display();
				exit();
			}

			//验证货物颜色
			$data['color']=I('color','');
			if( empty($data['color']) ){
				$this->assign('error','货物颜色必填');
				$this->display();
				exit();
			}

			//验证货物价格
			$data['price']=I('price',0,'floatval');
			if( empty($data['price']) ){
				$this->assign('error','价格必填');
				$this->display();
				exit();
			}

			//验证价格单位
			$data['price_unit']=I('price_unit','');
			if( empty($data['price_unit']) ){
				$this->assign('error','价格单位必填');
				$this->display();
				exit();
			}

			//验证折扣范围
			$data['discount']=I('discount',0,'intval');
			if( $data['discount']==0 || $data['discount'] > 100 ){
				$this->assign('error','折扣不在合法范围');
				$this->display();
				exit();
			}

			//验证数量单位
			$data['num_unit']=I('num_unit','');
			if( empty($data['num_unit']) ){
				$this->assign('error','数量单位必填');
				$this->display();
				exit();
			}

			
			$data['sort']=I('sort',1,'intval');//排序权重

			$data['add_time']=date('Y-m-d H:i:s');
			$data['update_time']=date('Y-m-d H:i:s');
			$data['handle_user']=$_SESSION['user'];
			$data['handle_name']=$_SESSION['name'];

			// 添加新数据
			if(M('goods')->add($data)){
				$this->success('添加成功！',U('goodsList',array('cate_id'=>$data['cate_id'])));
			}else{
				$this->error('网络不好，请稍候再试！');
			}


		}else{
			
			// 参数
			$cate_id=I('cate_id',0,'intval');

			//查询栏目
			$this->cate=M('goods_cate')->where(array('cate_id'=>$cate_id,'status'=>1))->find();

			$this->display();
		}
		
	}

	/**
	 * 编辑货物
	 * @author 黄俊
	 * date 2016-11-21
	 */
	public function editGoods(){
		//权限判断
		if( !in_array($_SESSION['role'], array(3,4,5)) ){
			$this->error('权限不够！');
		}

		if(IS_POST){

			//接收数据
			$data['goods_id']=I('goods_id',0,'intval');
			$data['cate_id']=I('cate_id',0,'intval');

			//验证货物id
			if( $data['goods_id']==0 ){
				$this->assign('error','非法操作');
				$this->display();
				exit();
			}

			//验证栏目id
			if( $data['cate_id']==0 ){
				$this->assign('error','非法操作');
				$this->display();
				exit();
			}

			//验证货物名称
			$data['name']=I('name','');
			if( empty($data['name']) ){
				$this->assign('error','货物名称必填');
				$this->display();
				exit();
			}

			//验证货物编号
			$data['number']=I('number','');
			if( empty($data['number']) ){
				$this->assign('error','货物编号必填');
				$this->display();
				exit();
			}

			//验证货物尺寸
			$data['size']=I('size','');
			if( empty($data['size']) ){
				$this->assign('error','货物尺寸必填');
				$this->display();
				exit();
			}

			//验证货物颜色
			$data['color']=I('color','');
			if( empty($data['color']) ){
				$this->assign('error','货物颜色必填');
				$this->display();
				exit();
			}

			//验证货物价格
			$data['price']=I('price',0,'floatval');
			if( empty($data['price']) ){
				$this->assign('error','价格必填');
				$this->display();
				exit();
			}

			//验证价格单位
			$data['price_unit']=I('price_unit','');
			if( empty($data['price_unit']) ){
				$this->assign('error','价格单位必填');
				$this->display();
				exit();
			}

			//验证折扣范围
			$data['discount']=I('discount',0,'intval');
			if( $data['discount']==0 || $data['discount'] > 100 ){
				$this->assign('error','折扣不在合法范围');
				$this->display();
				exit();
			}

			//验证数量单位
			$data['num_unit']=I('num_unit','');
			if( empty($data['num_unit']) ){
				$this->assign('error','数量单位必填');
				$this->display();
				exit();
			}

			$data['sort']=I('sort',1,'intval');//排序权重

			$data['add_time']=date('Y-m-d H:i:s');
			$data['update_time']=date('Y-m-d H:i:s');
			$data['handle_user']=$_SESSION['user'];
			$data['handle_name']=$_SESSION['name'];

			// 保存更新
			if(M('goods')->save($data)){
				$this->success('编辑成功！',U('goodsList',array('cate_id'=>$data['cate_id'])));
			}else{
				$this->error('网络不好，请稍候再试！');
			}


		}else{
			
			// 参数
			$cate_id=I('cate_id',0,'intval');
			$goods_id=I('goods_id',0,'intval');

			//查询栏目
			$this->cate=M('goods_cate')->where(array('cate_id'=>$cate_id,'status'=>1))->find();

			//查询货物
			$this->goods=M('goods')->where(array('goods_id'=>$goods_id,'status'=>1))->find();

			$this->display();
		}
		
	}

	/**
	 * 删除货物
	 * @author 黄俊
	 * date 2016-11-21
	 */
	public function delGoods(){
		//权限判断
		if( !in_array($_SESSION['role'], array(3,4,5)) ){
			$this->error('权限不够！');
		}

		//接收数据
		$cate_id=I('cate_id',0,'intval');
		$data['goods_id']=I('goods_id',0,'intval');
		$data['update_time']=date('Y-m-d H:i:s');
		$data['status']=2;

		// 保存更新
		if(M('goods')->save($data)){
			$this->success('删除成功！',U('goodsList',array('cate_id'=>$cate_id)));
		}else{
			$this->error('网络不好，请稍候再试！');
		}

	}


	/**
	 * 货物详情页
	 * @author 黄俊
	 * date 2016-11-21
	 */
	public function goodsView(){
		//权限判断
		if( !in_array($_SESSION['role'], array(3,4,5)) ){
			$this->error('权限不够！');
		}

		//接收数据
		$goods_id=I('goods_id',0,'intval');
		$cate_id=I('cate_id',0,'intval');

		$cate=M('goods_cate')->where(array('cate_id'=>$cate_id,'status'=>1))->find();
		$goods=M('goods')->where(array('goods_id'=>$goods_id,'status'=>1))->find();

		//检查是否为空
		if(empty($cate)){
			$this->error('非法访问！');
		}

		if(empty($goods)){
			$this->error('非法访问！');
		}

		$this->cate=$cate;
		$this->goods=$goods;

		$this->display();
		

	}

	/**
	 * 货物出库入库
	 * @author 黄俊
	 * date 2016-11-21
	 */
	public function goodsChange(){
		//权限判断
		if( !in_array($_SESSION['role'], array(3,4,5)) ){
			$this->error('权限不够！');
		}

		// 是否是ajax请求
		if(IS_AJAX){

			//接收数据
			$goods_id=I('goods_id',0,'intval');
			$num=I('num',0,'intval');
			$action=I('action','');

			// 检查num是否合法
			if( $num<=0 ){
				$this->error('非法操作！');
			}

			// 检查action是否合法
			if( !in_array($action, array('del','add')) ){
				$this->error('非法操作！');
			}

			//查询货物，检查是否可操作
			$goods=M('goods')->where(array('goods_id'=>$goods_id,'status'=>1))->find();

			//检查是否为空
			if(empty($goods)){
				$this->error('非法访问！');
			}

			// 货品出库入库记录数据
			$num=$action=='add'?$num:-$num;
			$goodsLogData['goods_id']=$goods['goods_id'];
			$goodsLogData['num']=$num;
			$goodsLogData['type']=$num>0?2:1;
			$goodsLogData['add_time']=date('Y-m-d H:i:s');
			$goodsLogData['update_time']=date('Y-m-d H:i:s');
			$goodsLogData['handle_user']=$_SESSION['user'];
			$goodsLogData['handle_name']=$_SESSION['name'];

			// 保存记录
			if(M('goods_log')->add($goodsLogData)){

				// 货品总数变动
				$data['goods_id']=$goods['goods_id'];
				$data['num']=$goods['num']+$num;
				$data['update_time']=date('Y-m-d H:i:s');
				if(M('goods')->save($data)){//总数变动
					$rs['status']=1;
					$rs['num']=$num;
					$rs['msg']='成功';
					$this->ajaxReturn($rs);
				}else{//失败
					$rs['status']=2;
					$rs['msg']='货品总数变动失败';
					$this->ajaxReturn($rs);
				}
			}else{//失败
				$rs['status']=2;
				$rs['msg']='保存记录失败';
				$this->ajaxReturn($rs);
			}
		}
			
	}

	/**
	 * 货物出库入库记录
	 * @author 黄俊
	 * date 2016-11-21
	 */
	public function goodsChangeLog(){
		//权限判断
		if( !in_array($_SESSION['role'], array(3,4,5)) ){
			$this->error('权限不够！');
		}

		// $week=getWeekInfo();
		// $month=getMonthFirst();
		// P($week);
		// P($month);
		// die();
		//货物名称
		$name=I('name','');
		$where='';//查询条件

		// 查询条件
		if(!empty($name)){
			$where=" g.name like '%".$name."%'";
		}
		
		import('ORG.Util.Page');//引入分页类

		/*分页*/
		$count=SERVICE('Depot')->getGoodsLogCount($where);//总数量
		$page=new Page($count,20);
		$limit=$page->firstRow.','.$page->listRows;

		
		$this->goodsLogList=SERVICE('Depot')->getGoodsLogList($where,$limit);
		$this->page=$page->show();//获得页码
		$this->sort=$page->firstRow;//序号

		$this->display();
	}

	/**
	 * 仓库订货统计
	 * @author 黄俊
	 * date 2017-2-24
	 */
	public function goodsTransactionLog(){
		//权限判断
		if( !in_array($_SESSION['role'], array(3,4,5)) ){
			$this->error('权限不够！');
		}

		//时间
		$start_time=I('start_time','');
		$end_time=I('end_time','');
		//按类型
		$type=I('type',1,'intval');

		//默认开始时间
		if(empty($start_time)){
			$start_time=date('Y-m-d',time());
		}

		//默认结束时间
		if(empty($end_time)){
			$end_time=date('Y-m-d',time()+(24*60*60));
		}

		
		import('ORG.Util.Page');//引入分页类

		/*分页*/
		$count=0;//总数量

		/*按类型分类*/
		if( $type==1 ){//按单品
			$count=SERVICE('Depot')->getGoodsTransactionLogCount($start_time,$end_time);//总数量
		}else{//按类别
			$count=SERVICE('Depot')->getGoodsCateTransactionLogCount($start_time,$end_time);//总数量
		}

		$page=new Page($count,20);
		$limit=$page->firstRow.','.$page->listRows;

		/*按类型分类*/
		if( $type==1 ){//按单品
			$this->goodsLogList=SERVICE('Depot')->getGoodsTransactionLogList($start_time,$end_time,$limit);
		}else{//按类别
			$this->goodsLogList=SERVICE('Depot')->getGoodsCateTransactionLogList($start_time,$end_time,$limit);
		}
		$this->type=$type;
		$this->page=$page->show();//获得页码
		$this->sort=$page->firstRow;//序号

		$this->display();
	}

	/**
	 * 仓库订货统计报表下载
	 * @author 黄俊
	 * date 2017-2-24
	 */
	public function goodsTransactionLogDownload(){
		//权限判断
		if( !in_array($_SESSION['role'], array(3,4,5)) ){
			$this->error('权限不够！');
		}

		//时间
		$start_time=I('start_time','');
		$end_time=I('end_time','');
		//按类型
		$type=I('type',1,'intval');

		//默认开始时间
		if(empty($start_time)){
			$start_time=date('Y-m-d',time());
		}

		//默认结束时间
		if(empty($end_time)){
			$end_time=date('Y-m-d',time()+(24*60*60));
		}


		//引入PHPExcel库文件
		import('Class.PHPExcel',APP_PATH);

		//创建对象
		$excel = new PHPExcel();
		$objActSheet = $excel->getActiveSheet();

		//日志列表
		$goodsLogList=array();

		/*按类型分类*/
		if( $type==1 ){//按单品

			$goodsLogList=SERVICE('Depot')->getGoodsTransactionLogList($start_time,$end_time);

			//设置宽度   
			$objActSheet->getColumnDimension('A')->setWidth(50);
			$objActSheet->getColumnDimension('B')->setWidth(20);
			$objActSheet->getColumnDimension('C')->setWidth(20);
			$objActSheet->getColumnDimension('D')->setWidth(20);
			$objActSheet->getColumnDimension('E')->setWidth(20);

			//Excel表格式
			$letter = array('A','B','C','D','E');

			//表头数组
			$tableheader = array('货品','货号','尺码','颜色','出货数量');
			
			//填充表头信息
			for($i = 0;$i < count($tableheader);$i++) {
				$objActSheet->setCellValue("$letter[$i]1","$tableheader[$i]");
			}

			$k=2;
			foreach ($goodsLogList as $key => $value) {
				$objActSheet->setCellValue("A".$k,$value['name'].' ');
				$objActSheet->setCellValue("B".$k,$value['number'].' ');
				$objActSheet->setCellValue("C".$k,$value['size'].' ');
				$objActSheet->setCellValue("D".$k,$value['color'].' ');
				$objActSheet->setCellValue("E".$k,abs($value['changeNum']).' ');
				$k++;
			}

		}else{//按类别

			$goodsLogList=SERVICE('Depot')->getGoodsCateTransactionLogList($start_time,$end_time);

			//设置宽度   
			$objActSheet->getColumnDimension('A')->setWidth(50);
			$objActSheet->getColumnDimension('B')->setWidth(20);

			//Excel表格式
			$letter = array('A','B');

			//表头数组
			$tableheader = array('货品分类','出货数量');
			
			//填充表头信息
			for($i = 0;$i < count($tableheader);$i++) {
				$objActSheet->setCellValue("$letter[$i]1","$tableheader[$i]");
			}

			$k=2;
			foreach ($goodsLogList as $key => $value) {
				$objActSheet->setCellValue("A".$k,$value['name'].' ');
				$objActSheet->setCellValue("B".$k,abs($value['changeNum']).' ');
				$k++;
			}

		}


		//创建Excel输入对象
		$filename='订货统计--'.$start_time.'至'.$end_time;
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
	 * 仓库发货统计
	 * @author 黄俊
	 * date 2017-3-3
	 */
	public function deliverGoodsLog(){
		//权限判断
		if( !in_array($_SESSION['role'], array(3,4,5)) ){
			$this->error('权限不够！');
		}

		//时间
		$start_time=I('start_time','');
		$end_time=I('end_time','');
		//按类型
		$type=I('type',1,'intval');

		//默认开始时间
		if(empty($start_time)){
			$start_time=date('Y-m-d',time());
		}

		//默认结束时间
		if(empty($end_time)){
			$end_time=date('Y-m-d',time()+(24*60*60));
		}

		
		import('ORG.Util.Page');//引入分页类

		/*分页*/
		$count=0;//总数量

		/*按类型分类*/
		if( $type==1 ){//按单品
			$count=SERVICE('Depot')->getGoodsDeliverGoodsLogCount($start_time,$end_time);//总数量
		}else{//按类别
			$count=SERVICE('Depot')->getGoodsCateDeliverGoodsLogCount($start_time,$end_time);//总数量
		}

		$page=new Page($count,20);
		$limit=$page->firstRow.','.$page->listRows;

		/*按类型分类*/
		if( $type==1 ){//按单品
			$this->goodsLogList=SERVICE('Depot')->getGoodsDeliverGoodsLogList($start_time,$end_time,$limit);
		}else{//按类别
			$this->goodsLogList=SERVICE('Depot')->getGoodsCateDeliverGoodsLogList($start_time,$end_time,$limit);
		}
		$this->type=$type;
		$this->page=$page->show();//获得页码
		$this->sort=$page->firstRow;//序号

		$this->display();
	}

	/**
	 * 仓库发货统计报表下载
	 * @author 黄俊
	 * date 2017-2-24
	 */
	public function deliverGoodsLogDownload(){
		//权限判断
		if( !in_array($_SESSION['role'], array(3,4,5)) ){
			$this->error('权限不够！');
		}

		//时间
		$start_time=I('start_time','');
		$end_time=I('end_time','');
		//按类型
		$type=I('type',1,'intval');

		//默认开始时间
		if(empty($start_time)){
			$start_time=date('Y-m-d',time());
		}

		//默认结束时间
		if(empty($end_time)){
			$end_time=date('Y-m-d',time()+(24*60*60));
		}


		//引入PHPExcel库文件
		import('Class.PHPExcel',APP_PATH);

		//创建对象
		$excel = new PHPExcel();
		$objActSheet = $excel->getActiveSheet();

		//日志列表
		$goodsLogList=array();

		/*按类型分类*/
		if( $type==1 ){//按单品

			$goodsLogList=SERVICE('Depot')->getGoodsDeliverGoodsLogList($start_time,$end_time);

			//设置宽度   
			$objActSheet->getColumnDimension('A')->setWidth(50);
			$objActSheet->getColumnDimension('B')->setWidth(20);
			$objActSheet->getColumnDimension('C')->setWidth(20);
			$objActSheet->getColumnDimension('D')->setWidth(20);
			$objActSheet->getColumnDimension('E')->setWidth(20);

			//Excel表格式
			$letter = array('A','B','C','D','E');

			//表头数组
			$tableheader = array('货品','货号','尺码','颜色','发货数量');
			
			//填充表头信息
			for($i = 0;$i < count($tableheader);$i++) {
				$objActSheet->setCellValue("$letter[$i]1","$tableheader[$i]");
			}

			$k=2;
			foreach ($goodsLogList as $key => $value) {
				$objActSheet->setCellValue("A".$k,$value['name'].' ');
				$objActSheet->setCellValue("B".$k,$value['number'].' ');
				$objActSheet->setCellValue("C".$k,$value['size'].' ');
				$objActSheet->setCellValue("D".$k,$value['color'].' ');
				$objActSheet->setCellValue("E".$k,abs($value['changeNum']).' ');
				$k++;
			}

		}else{//按类别

			$goodsLogList=SERVICE('Depot')->getGoodsCateDeliverGoodsLogList($start_time,$end_time);

			//设置宽度   
			$objActSheet->getColumnDimension('A')->setWidth(50);
			$objActSheet->getColumnDimension('B')->setWidth(20);

			//Excel表格式
			$letter = array('A','B');

			//表头数组
			$tableheader = array('货品分类','发货数量');
			
			//填充表头信息
			for($i = 0;$i < count($tableheader);$i++) {
				$objActSheet->setCellValue("$letter[$i]1","$tableheader[$i]");
			}

			$k=2;
			foreach ($goodsLogList as $key => $value) {
				$objActSheet->setCellValue("A".$k,$value['name'].' ');
				$objActSheet->setCellValue("B".$k,abs($value['changeNum']).' ');
				$k++;
			}

		}


		//创建Excel输入对象
		$filename='发货统计--'.$start_time.'至'.$end_time;
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
	 * 货物停售或恢复
	 * @author 黄俊
	 * date 2016-11-21
	 */
	public function goodsStop(){
		//权限判断
		if( !in_array($_SESSION['role'], array(3,4,5)) ){
			$this->error('权限不够！');
		}

		if(IS_AJAX){

			// 获取参数
			$goods_id=I('goods_id',0,'intval');

			// 取得货品信息
			$goods=M('goods')->where(array('goods_id'=>$goods_id))->find();

			// 检查货物是否存在
			if(empty($goods)){
				$rs['status']=2;
				$rs['msg']='货品不存在！';
				$this->ajaxReturn($rs);
			}

			# 状态变更
			// 检查状态是否合法
			if( !in_array($goods['status'], array(1,3)) ){
				$rs['status']=2;
				$rs['msg']='非法操作！';
				$this->ajaxReturn($rs);
			}

			// 更改数据
			$data['goods_id']=$goods['goods_id'];
			$data['status']=$goods['status']==1?3:1;
			$data['update_time']=date('Y-m-d H:i:s');

			if( M('goods')->save($data) ){
				$rs['status']=1;
				$rs['goodsStatus']=$data['status'];
				$rs['msg']='成功！';
				$this->ajaxReturn($rs);
			}else{
				$rs['status']=2;
				$rs['msg']='网络不好，请稍候再试！';
				$this->ajaxReturn($rs);
			}
		}
	}
}

?>