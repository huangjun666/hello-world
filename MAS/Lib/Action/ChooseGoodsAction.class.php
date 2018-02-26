<?php
/**
 * 选货模块
 * @author 黄俊
 * date 2016-6-28
 */
class ChooseGoodsAction extends BaseAction{

	private $order_id;//订单id
	private $orderGoodsInfo;//订货单信息

	/**
	 * 类似构造函数
	 * @author 黄俊
	 * date 2016-11-28
	 */
    public function _initialize(){
    	parent::_initialize();

    	//权限判断
		if( !in_array($_SESSION['role'], array(1,2,3,4,5)) ){
			$this->error('权限不够！');
		}

    	#检查order_id是否存在，以确保是从订货单跳转过来，防止直接访问
    	$order_id=I('order_id',0,'intval');//订单id
    	
    	//session和I是否一致，不一致，将session覆盖
    	if( $order_id !=0 && $order_id != $_SESSION['order_id'] ){
    		session('order_id',$order_id);
    	}
    	$this->order_id=empty($order_id)?$_SESSION['order_id']:$order_id;

    	//order_id不存在，则调整至订货管理页面
    	if(empty($this->order_id)){
    		$this->redirect('OrderGoods/index');
    	}

    	// 检查订货单当前状态是否可以选货，防止流程绕过
    	$orderGoodsInfo=SERVICE('OrderGoods')->getOrderGoods($this->order_id,true);

    	//馆主
    	if(in_array($_SESSION['role'], array(1,2)) && $orderGoodsInfo['status'] != 1){

    		if(IS_AJAX){
    			$rs['status']=2;
    			$rs['msg']='订货单已经提交，不可以这样操作！';
    			$this->ajaxReturn($rs);
    		}else{
    			$this->error('订货单已经提交，不可以这样操作！');
    		}
    		
    	}

    	// 财务和系统管理员
    	if(in_array($_SESSION['role'], array(3,4,5)) && in_array($orderGoodsInfo['status'], array(7)) ){
    		if(IS_AJAX){
    			$rs['status']=2;
    			$rs['msg']='订货单已经完结，不可以这样操作！';
    			$this->ajaxReturn($rs);
    		}else{
    			$this->error('订货单已经完结，不可以这样操作！');
    		}
    	}

    	// 仓库管理员
    	if(in_array($_SESSION['role'], array(5)) && in_array($orderGoodsInfo['status'], array(1,7)) ){
    		if(IS_AJAX){
    			$rs['status']=2;
    			$rs['msg']='当前订货单还没提交，仓库管理员不可以操作！';
    			$this->ajaxReturn($rs);
    		}else{
    			$this->error('当前订货单还没提交，仓库管理员不可以操作！');
    		}
    	}

    	// 如果是经销商，检查改订货单是否是改经销商的
    	if( in_array($_SESSION['role'], array(1)) && $_SESSION['id_card'] !=$orderGoodsInfo['id_card'] ){
    		$this->error('非法操作:不是你添加的订货单，不可以选货！');
    	}

    	// 如果是馆主，检查改订货单是否由该馆主添加
    	if( in_array($_SESSION['role'], array(2)) && $_SESSION['user'] !=$orderGoodsInfo['handle'] ){
    		$this->error('非法操作:不是你添加的订货单，不可以选货！');
    	}

    	$this->orderGoodsInfo=$orderGoodsInfo;
	}

	/**
	 * 首页
	 * @author 黄俊
	 * date 2016-11-21
	 */
	public function index(){

		if(IS_POST){

			//货物名称
			$name=I('name','');

			// 查询条件
			$where="name like '%".$name."%' and status=1";

			// 查询结果
			$goodsList=M('goods')->where($where)->order('sort DESC,update_time DESC,add_time DESC')->select();

			$this->page=1;//获得页码
			$this->sort=0;//序号
			$this->assign('goodsList',$goodsList);
			$this->display();
		}else{

			import('ORG.Util.Page');//引入分页类

			$where=array('status'=>1);
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
	 * 货物列表
	 * @author 黄俊
	 * date 2016-11-21
	 */
	public function goodsList(){

		if(IS_POST){

			//接收参数
			$name=I('name','');//货物名称
			$cate_id=I('cate_id',0,'intval');//货物分类

			// 查询条件
			$where="cate_id=".$cate_id." and name like '%".$name."%' and status=1 ";

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

			$where=array('cate_id'=>$cate_id,'status'=>1);
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
	 * 货物详情页
	 * @author 黄俊
	 * date 2016-11-21
	 */
	public function goodsView(){

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
	 * 货物加入订货单
	 * @author 黄俊
	 * date 2016-11-21
	 */
	public function chooseGoods(){

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
			if( !in_array($action, array('del','add')) ){//del:加入订货单 add:退货 【del、add是相对goods表而言】
				$this->error('非法操作！');
			}

			//查询货物，检查是否可操作
			$goods=M('goods')->where(array('goods_id'=>$goods_id,'status'=>1))->find();

			//检查是否为空
			if(empty($goods)){
				$this->error('非法访问！');
			}

			// 货品出库入库记录数据
			$num=$action=='add'?-$num:$num;//变动数量

			// 检查变动数量是否合法
			if( $action=='del' && $goods['num']-$num < 5 ){
				$rs['status']=2;
				$rs['msg']='添加失败，加入之后，货物数量小于5，不可以加入！！';
				$this->ajaxReturn($rs);
			}

			#1、计算添加之后，货品总价是否超过订单总额的100%

			//系统配置
			// $reward_config=D('Web')->reward_config();

			// 当前订货单总额
			$currentMoney=SERVICE('OrderGoods')->getCurrentGoodSMoney($this->orderGoodsInfo['order_goods_id']);
			
			// 将要变化的额度
			$changeMoney=$num*$goods['price']*$goods['discount']*0.01;
			
			// 总的额度
			$totalMoney=$currentMoney+$changeMoney;
			
			// 判断
			if( $totalMoney > $this->orderGoodsInfo['money'] ){
				$rs['status']=2;
				$rs['msg']='添加失败，订单额度不够！';
				$rs['msg'].='---订单额度：'.$this->orderGoodsInfo['money'];
				$rs['msg'].='---该货品添加之后，总额为：'.$totalMoney;
				$this->ajaxReturn($rs);
			}

			#2、是否有相同的货品，已经被加入到该订货单:
			$where=array(
				'order_goods_id'=>$this->orderGoodsInfo['order_goods_id'],
				'goods_id'=>$goods['goods_id']
				);
			$chooseGoods=M('order_goods_list')->where($where)->find();

			// 判断
			if(empty($chooseGoods)){//没有，执行保存操作

				// 如果是退回操作
				if($action=='add'){
					$rs['status']=2;
					$rs['msg']='警告：非法操作，该订货单内，没有该货物！';
					$this->ajaxReturn($rs);
				}

				$goodsListData['order_goods_id']=$this->orderGoodsInfo['order_goods_id'];
				$goodsListData['goods_id']=$goods['goods_id'];
				$goodsListData['num']=$num;
				$goodsListData['add_time']=date('Y-m-d H:i:s');
				$goodsListData['update_time']=date('Y-m-d H:i:s');

				if(!M('order_goods_list')->add($goodsListData)){
					$rs['status']=2;
					$rs['msg']='加入到订货单列表失败';
					$this->ajaxReturn($rs);
				}

			}else{//有，执行更新操作，

				$goodsListData['list_id']=$chooseGoods['list_id'];
				$goodsListData['order_goods_id']=$this->orderGoodsInfo['order_goods_id'];
				$goodsListData['goods_id']=$goods['goods_id'];
				$goodsListData['num']=$chooseGoods['num']+$num;
				$goodsListData['update_time']=date('Y-m-d H:i:s');

				#判断改变后的num:
				if( $goodsListData['num']<0 ){//操作不合法：没有那么多货物数量
					$rs['status']=2;
					$rs['msg']='没有那么多货物数量';
					$this->ajaxReturn($rs);
				}else if($goodsListData['num']==0 && $chooseGoods['deliver_num']==0 ){//数量和已发货数量都为0，执行删除操作
					if( !M('order_goods_list')->where(array('list_id'=>$goodsListData['list_id']))->delete() ){
						$rs['status']=2;
						$rs['msg']='网络不好，请重试!';
						$this->ajaxReturn($rs);
					}
				}else{//其余，执行更新操作
					if(!M('order_goods_list')->save($goodsListData)){
						$rs['status']=2;
						$rs['msg']='加入到订货单列表失败';
						$this->ajaxReturn($rs);
					}
				}

				
			}

			
			// 保存货品出库入库记录数据
			$goodsLogData['goods_id']=$goods['goods_id'];
			$goodsLogData['num']=-$num;
			$goodsLogData['type']=-$num>0?4:3;
			$goodsLogData['add_time']=date('Y-m-d H:i:s');
			$goodsLogData['update_time']=date('Y-m-d H:i:s');
			$goodsLogData['handle_user']=$_SESSION['user'];
			$goodsLogData['handle_name']=$_SESSION['name'];

			// 保存记录
			if(M('goods_log')->add($goodsLogData)){

				// 货品总数变动
				$data['goods_id']=$goods['goods_id'];
				$data['num']=$goods['num']-$num;
				$data['update_time']=date('Y-m-d H:i:s');
				if(M('goods')->save($data)){//总数变动
					$rs['status']=1;
					$rs['num']=-$num;
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
	 * 获取当前订货单的总额和已花费的金额
	 * @author 黄俊
	 * date 2016-11-21
	 */
	public function getOrderGoodsConsumption(){
		
		if(IS_AJAX){
			

			$orderGoodsInfo=$this->orderGoodsInfo;//当前订货单信息
			
			//获得已开销的金额总量
			$usedMoney=SERVICE('OrderGoods')->getCurrentGoodSMoney($orderGoodsInfo['order_goods_id']);

			$rs['totalMoney']=$orderGoodsInfo['money'];
			$rs['usedMoney']=$usedMoney;
			$rs['status']=1;
			$rs['msg']='成功！';

			$this->ajaxReturn($rs);

		}
	}

}
?>