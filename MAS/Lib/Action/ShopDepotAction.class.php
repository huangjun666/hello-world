<?php
/**
 * 商城商品模块
 * @author 黄俊
 * date 2017-7-4
 */
class ShopDepotAction extends BaseAction{

	/**
	 * 类似构造函数
	 * @author 黄俊
	 * date 2016-11-28
	 */
    public function _initialize(){
    	parent::_initialize();
    	#记录当前应该调用哪个左侧主菜单
    	session('shop_menu','Shop_Depot');
    	// session['shop_member_id']，优先等于
    	
    }

	/**
	 * 首页
	 * @author 黄俊
	 * date 2017-7-4
	 */
	public function index(){
		//权限判断
		if( !in_array($_SESSION['role'], array(3,4,5)) ){
			$this->error('权限不够！');
		}
		if(IS_POST){

			//商品名称
			$name=I('name','');

			// 查询条件
			$where="name like '%".$name."%' and status in(1,3)";//

			// 查询结果
			$goodsList=M('shop_goods')->where($where)->order('sort DESC,update_time DESC,add_time DESC')->select();

			$this->page=1;//获得页码
			$this->sort=0;//序号
			$this->assign('goodsList',$goodsList);
			$this->display();
		}else{

			import('ORG.Util.Page');//引入分页类

			$where="status in(1,3)";
			/*分页*/
			$count=M('shop_goods')->where($where)->count();//总数量
			$page=new Page($count,20);
			$limit=$page->firstRow.','.$page->listRows;

			$this->goodsList=M('shop_goods')->where($where)->order('sort DESC')->limit($limit)->select();
			$this->page=$page->show();//获得页码
			$this->sort=$page->firstRow;//序号

			$this->display();
		}
		
		
	}

	/**
	 * 商品栏目分类
	 * @author 黄俊
	 * date 2016-11-21
	 */
	public function goodsCate(){
		//权限判断
		if( !in_array($_SESSION['role'], array(3,4,5)) ){
			$this->error('权限不够！');
		}

		$this->goodsCate=M('shop_goods_cate')->where('status=1')->order('add_time ASC')->select();
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
			$cate_id=M('shop_goods_cate')->add($data);
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
			if(M('shop_goods_cate')->save($data)){
				$this->success('添加成功！',U('goodsCate'));
			}else{
				$this->error('网络不好，请稍候再试！');
			}


		}else{
			// 参数
			$cate_id=I('cate_id',0,'intval');
			//查询栏目
			$this->cate=M('shop_goods_cate')->where(array('cate_id'=>$cate_id))->find();
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
		if(M('shop_goods_cate')->save($data)){

			//更改商品状态
			M('shop_goods')->where(array('cate_id'=>$data['cate_id']))->save($data);
			$this->success('删除成功！',U('goodsCate'));
			
		}else{
			$this->error('网络不好，请稍候再试！');
		}	
		
	}

	/**
	 * 商品列表
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
			$name=I('name','');//商品名称
			$cate_id=I('cate_id',0,'intval');//商品分类

			// 查询条件
			$where="cate_id=".$cate_id." and name like '%".$name."%' and status in(1,3)";

			// 查询结果
			$goodsList=M('shop_goods')->where($where)->order('sort DESC,update_time DESC,add_time DESC')->select();
			
			// 分类
			$this->cate=M('shop_goods_cate')->where(array('cate_id'=>$cate_id))->find();
			$this->page=1;//获得页码
			$this->sort=0;//序号
			$this->assign('goodsList',$goodsList);
			$this->display();
		}else{

			//接收参数
			$cate_id=I('cate_id',0,'intval');

			$this->cate=M('shop_goods_cate')->where(array('cate_id'=>$cate_id))->find();

			import('ORG.Util.Page');//引入分页类

			$where="cate_id=".$cate_id."  and status in(1,3)";
			/*分页*/
			$count=M('shop_goods')->where($where)->count();//总数量
			$page=new Page($count,20);
			$limit=$page->firstRow.','.$page->listRows;

			$this->goodsList=M('shop_goods')->where($where)->order('sort DESC')->limit($limit)->select();
			$this->page=$page->show();//获得页码
			$this->sort=$page->firstRow;//序号

			$this->display();

		}

		
		
	}

	/**
	 * 添加商品
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

			//验证商品名称
			$data['name']=I('name','');
			if( empty($data['name']) ){
				$this->assign('error','商品名称必填');
				$this->display();
				exit();
			}

			//验证商品封面图
			$data['cover']=I('cover','');
			if( empty($data['cover']) ){
				$this->assign('error','请上传商品封面图');
				$this->display();
				exit();
			}

			//验证商品编号
			$data['number']=I('number','');
			if( empty($data['number']) ){
				$this->assign('error','商品编号必填');
				$this->display();
				exit();
			}

			//验证商品尺寸
			$data['size']=I('size','');
			if( empty($data['size']) ){
				$this->assign('error','商品尺寸必填');
				$this->display();
				exit();
			}

			//验证商品颜色
			$data['color']=I('color','');
			if( empty($data['color']) ){
				$this->assign('error','商品颜色必填');
				$this->display();
				exit();
			}

			//验证商品价格
			$data['price']=I('price',0,'floatval');
			if( empty($data['price']) ){
				$this->assign('error','价格必填');
				$this->display();
				exit();
			}

			//验证商品真实价格
			$data['true_price']=I('true_price',0,'floatval');
			if( empty($data['true_price']) ){
				$this->assign('error','真实价格必填');
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
			$data['summary']=I('summary','');//商品简介

			$data['add_time']=date('Y-m-d H:i:s');
			$data['update_time']=date('Y-m-d H:i:s');
			$data['handle_user']=$_SESSION['user'];
			$data['handle_name']=$_SESSION['name'];

			// 添加新数据
			if(M('shop_goods')->add($data)){
				$this->success('添加成功！',U('goodsList',array('cate_id'=>$data['cate_id'])));
			}else{
				$this->error('网络不好，请稍候再试！');
			}


		}else{
			
			// 参数
			$cate_id=I('cate_id',0,'intval');

			//查询栏目
			$this->cate=M('shop_goods_cate')->where(array('cate_id'=>$cate_id,'status'=>1))->find();

			$this->display();
		}
		
	}

	/**
	 * 编辑商品
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

			//验证商品id
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

			//验证商品名称
			$data['name']=I('name','');
			if( empty($data['name']) ){
				$this->assign('error','商品名称必填');
				$this->display();
				exit();
			}

			//验证商品封面图
			$data['cover']=I('cover','');
			if( empty($data['cover']) ){
				$this->assign('error','请上传商品封面图');
				$this->display();
				exit();
			}

			//验证商品编号
			$data['number']=I('number','');
			if( empty($data['number']) ){
				$this->assign('error','商品编号必填');
				$this->display();
				exit();
			}

			//验证商品尺寸
			$data['size']=I('size','');
			if( empty($data['size']) ){
				$this->assign('error','商品尺寸必填');
				$this->display();
				exit();
			}

			//验证商品颜色
			$data['color']=I('color','');
			if( empty($data['color']) ){
				$this->assign('error','商品颜色必填');
				$this->display();
				exit();
			}

			//验证商品价格
			$data['price']=I('price',0,'floatval');
			if( empty($data['price']) ){
				$this->assign('error','价格必填');
				$this->display();
				exit();
			}

			//验证商品真实价格
			$data['true_price']=I('true_price',0,'floatval');
			if( empty($data['true_price']) ){
				$this->assign('error','真实价格必填');
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
			$data['summary']=I('summary','');//商品简介
			
			$data['add_time']=date('Y-m-d H:i:s');
			$data['update_time']=date('Y-m-d H:i:s');
			$data['handle_user']=$_SESSION['user'];
			$data['handle_name']=$_SESSION['name'];

			// 保存更新
			if(M('shop_goods')->save($data)){
				$this->success('编辑成功！',U('goodsList',array('cate_id'=>$data['cate_id'])));
			}else{
				$this->error('网络不好，请稍候再试！');
			}


		}else{
			
			// 参数
			$cate_id=I('cate_id',0,'intval');
			$goods_id=I('goods_id',0,'intval');

			//查询栏目
			$this->cate=M('shop_goods_cate')->where(array('cate_id'=>$cate_id))->find();

			//查询商品
			$this->goods=M('shop_goods')->where(array('goods_id'=>$goods_id))->find();

			$this->display();
		}
		
	}

	/**
	 * 删除商品
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
		if(M('shop_goods')->save($data)){
			$this->success('删除成功！',U('goodsList',array('cate_id'=>$cate_id)));
		}else{
			$this->error('网络不好，请稍候再试！');
		}

	}


	/**
	 * 商品详情页
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

		$cate=M('shop_goods_cate')->where(array('cate_id'=>$cate_id,'status'=>1))->find();
		$goods=M('shop_goods')->where(array('goods_id'=>$goods_id,'status'=>1))->find();

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
	 * 商品出库入库
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

			//查询商品，检查是否可操作
			$goods=M('shop_goods')->where(array('goods_id'=>$goods_id,'status'=>1))->find();

			//检查是否为空
			if(empty($goods)){
				$this->error('非法访问！');
			}

			// 商品出库入库记录数据
			$num=$action=='add'?$num:-$num;
			$goodsLogData['goods_id']=$goods['goods_id'];
			$goodsLogData['num']=$num;
			$goodsLogData['type']=$num>0?2:1;
			$goodsLogData['add_time']=date('Y-m-d H:i:s');
			$goodsLogData['update_time']=date('Y-m-d H:i:s');
			$goodsLogData['handle_user']=$_SESSION['user'];
			$goodsLogData['handle_name']=$_SESSION['name'];

			// 保存记录
			if(M('shop_goods_log')->add($goodsLogData)){

				// 商品总数变动
				$data['goods_id']=$goods['goods_id'];
				$data['num']=$goods['num']+$num;
				$data['update_time']=date('Y-m-d H:i:s');
				if(M('shop_goods')->save($data)){//总数变动
					$rs['status']=1;
					$rs['num']=$num;
					$rs['msg']='成功';
					$this->ajaxReturn($rs);
				}else{//失败
					$rs['status']=2;
					$rs['msg']='商品总数变动失败';
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
	 * 商品出库入库记录
	 * @author 黄俊
	 * date 2016-11-21
	 */
	public function goodsChangeLog(){
		//权限判断
		if( !in_array($_SESSION['role'], array(3,4,5)) ){
			$this->error('权限不够！');
		}

		//商品名称
		$name=I('name','');
		$where='';//查询条件

		// 查询条件
		if(!empty($name)){
			$where=" g.name like '%".$name."%'";
		}
		
		import('ORG.Util.Page');//引入分页类

		/*分页*/
		$count=SERVICE('ShopDepot')->getGoodsLogCount($where);//总数量
		$page=new Page($count,20);
		$limit=$page->firstRow.','.$page->listRows;

		
		$this->goodsLogList=SERVICE('ShopDepot')->getGoodsLogList($where,$limit);
		$this->page=$page->show();//获得页码
		$this->sort=$page->firstRow;//序号

		$this->display();
	}

	/**
	 * 商品停售或恢复
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

			// 取得商品信息
			$goods=M('shop_goods')->where(array('goods_id'=>$goods_id))->find();

			// 检查商品是否存在
			if(empty($goods)){
				$rs['status']=2;
				$rs['msg']='商品不存在！';
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

			if( M('shop_goods')->save($data) ){
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


	/**
	 * 商品封面上传
	 * @author 黄俊
	 * date 2017-7-5
	 */
	public function goodsCoverUpload(){
		//权限判断
		if( !in_array($_SESSION['role'], array(3,4,5)) ){
			$this->error('权限不够！');
		}

		//必须上传文件，避免直接访问
		if(!isset($_FILES['picfile'])){
			$rs['status']=2;
			$rs['msg']='请选择上传文件！';
			echo '{status:"'.$rs['status'].'",msg:"'.$rs['msg'].'"}';//输出json格式的数据
			exit();
		}

		//检查文件是否上传，或者上传出错
        if($_FILES['picfile']['error']!=0){//不等于0，表示文件没上传，或上传出错
            $rs['status']=2;
			$rs['msg']='检查文件是否上传，或者上传出错！';
			echo '{status:"'.$rs['status'].'",msg:"'.$rs['msg'].'"}';//输出json格式的数据
			exit();
			// $this->ajaxReturn($rs);
        }

        // 检查图片格式是否满足
        $imgType=array('.gif','.png','.jpg');//限制格式
        $curType=substr($_FILES['picfile']['name'], strrpos($_FILES['picfile']['name'], '.'));

        //检查图片类型
        if(!in_array($curType, $imgType)){
            $rs['status']=2;
			$rs['msg']='图片类型不对！只能上传gif、png、jpg';
			echo '{status:"'.$rs['status'].'",msg:"'.$rs['msg'].'"}';//输出json格式的数据
			exit();
			$this->ajaxReturn($rs);
        }

        /*检查图片大小--300kb*/
        if( intval($_FILES['picfile']['size']) > 300*1024 ){
            $rs['status']=2;
			$rs['msg']='图片这么大，会撑死的！不能超过300K！';
			echo '{status:"'.$rs['status'].'",msg:"'.$rs['msg'].'"}';//输出json格式的数据
			exit();
			$this->ajaxReturn($rs);
        }

        /*上传图片*/
		import('ORG.Net.UploadFile');

		$upload=new UploadFile();

		//设置参数
		$upload->maxSize=1000000;//1MB
		// $upload->autoSub=true;//开启子目录保存
		// $upload->subType='date';//子目录创建方式

		//开始上传
		if(!$upload->upload('./upload/goods_cover/')){
			$rs['status']=2;
			$rs['msg']='未知错误，文件上传失败';
			echo '{status:"'.$rs['status'].'",msg:"'.$rs['msg'].'"}';//输出json格式的数据
			exit();
			$this->ajaxReturn($rs);
		}
		
		//获得文件信息
		$info=$upload->getUploadFileInfo();
		$imgSrc=$info[0]['savepath'].$info[0]['savename'];
		$imgSrc=substr($imgSrc, 1);


		//成功，返回图片地址
		$rs['status']=1;
		$rs['imgSrc']=$imgSrc;
		$rs['msg']='成功！';
		echo '{status:"'.$rs['status'].'",msg:"'.$rs['msg'].'",imgSrc:"'.$rs['imgSrc'].'"}';//输出json格式的数据
		exit();
		$this->ajaxReturn($rs);
	}


	/**
	 * 商品订货统计
	 * @author 黄俊
	 * date 2017-7-30
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
			$count=SERVICE('ShopDepot')->getGoodsTransactionLogCount($start_time,$end_time);//总数量
		}else{//按类别
			$count=SERVICE('ShopDepot')->getGoodsCateTransactionLogCount($start_time,$end_time);//总数量
		}

		$page=new Page($count,20);
		$limit=$page->firstRow.','.$page->listRows;

		/*按类型分类*/
		if( $type==1 ){//按单品
			$this->goodsLogList=SERVICE('ShopDepot')->getGoodsTransactionLogList($start_time,$end_time,$limit);
		}else{//按类别
			$this->goodsLogList=SERVICE('ShopDepot')->getGoodsCateTransactionLogList($start_time,$end_time,$limit);
		}
		$this->type=$type;
		$this->page=$page->show();//获得页码
		$this->sort=$page->firstRow;//序号

		$this->display();
	}

	/**
	 * 商品订货统计报表下载
	 * @author 黄俊
	 * date 2017-7-30
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

			$goodsLogList=SERVICE('ShopDepot')->getGoodsTransactionLogList($start_time,$end_time);

			//设置宽度   
			$objActSheet->getColumnDimension('A')->setWidth(50);
			$objActSheet->getColumnDimension('B')->setWidth(20);
			$objActSheet->getColumnDimension('C')->setWidth(20);
			$objActSheet->getColumnDimension('D')->setWidth(20);
			$objActSheet->getColumnDimension('E')->setWidth(20);

			//Excel表格式
			$letter = array('A','B','C','D','E');

			//表头数组
			$tableheader = array('商品','货号','尺码','颜色','订货数量');
			
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

			$goodsLogList=SERVICE('ShopDepot')->getGoodsCateTransactionLogList($start_time,$end_time);

			//设置宽度   
			$objActSheet->getColumnDimension('A')->setWidth(50);
			$objActSheet->getColumnDimension('B')->setWidth(20);

			//Excel表格式
			$letter = array('A','B');

			//表头数组
			$tableheader = array('商品分类','订货数量');
			
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
		$filename='重销商品订货统计--'.$start_time.'至'.$end_time;
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
	 * 商品发货统计
	 * @author 黄俊
	 * date 2017-7-30
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
			$count=SERVICE('ShopDepot')->getGoodsDeliverGoodsLogCount($start_time,$end_time);//总数量
		}else{//按类别
			$count=SERVICE('ShopDepot')->getGoodsCateDeliverGoodsLogCount($start_time,$end_time);//总数量
		}

		$page=new Page($count,20);
		$limit=$page->firstRow.','.$page->listRows;

		/*按类型分类*/
		if( $type==1 ){//按单品
			$this->goodsLogList=SERVICE('ShopDepot')->getGoodsDeliverGoodsLogList($start_time,$end_time,$limit);
		}else{//按类别
			$this->goodsLogList=SERVICE('ShopDepot')->getGoodsCateDeliverGoodsLogList($start_time,$end_time,$limit);
		}
		$this->type=$type;
		$this->page=$page->show();//获得页码
		$this->sort=$page->firstRow;//序号

		$this->display();
	}

	/**
	 * 商品发货统计报表下载
	 * @author 黄俊
	 * date 2017-7-30
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

			$goodsLogList=SERVICE('ShopDepot')->getGoodsDeliverGoodsLogList($start_time,$end_time);

			//设置宽度   
			$objActSheet->getColumnDimension('A')->setWidth(50);
			$objActSheet->getColumnDimension('B')->setWidth(20);
			$objActSheet->getColumnDimension('C')->setWidth(20);
			$objActSheet->getColumnDimension('D')->setWidth(20);
			$objActSheet->getColumnDimension('E')->setWidth(20);

			//Excel表格式
			$letter = array('A','B','C','D','E');

			//表头数组
			$tableheader = array('商品','货号','尺码','颜色','发货数量');
			
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

			$goodsLogList=SERVICE('ShopDepot')->getGoodsCateDeliverGoodsLogList($start_time,$end_time);

			//设置宽度   
			$objActSheet->getColumnDimension('A')->setWidth(50);
			$objActSheet->getColumnDimension('B')->setWidth(20);

			//Excel表格式
			$letter = array('A','B');

			//表头数组
			$tableheader = array('商品分类','发货数量');
			
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
		$filename='重销商品发货统计--'.$start_time.'至'.$end_time;
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


}

?>