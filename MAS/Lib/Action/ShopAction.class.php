<?php
/**
 * 商城模块
 * @author 黄俊
 * date 2017-7-4
 */
class ShopAction extends BaseAction{

	/**
	 * 类似构造函数
	 * @author 黄俊
	 * date 2016-11-28
	 */
    public function _initialize(){
    	parent::_initialize();
    	#记录当前应该调用哪个左侧主菜单
    	session('shop_menu','Shop');
    	// session['shop_member_id']，优先等于
    	
    }

	/**
	 * 首页
	 * @author 黄俊
	 * date 2017-7-4
	 */
	public function index(){
		//权限判断
		if( !in_array($_SESSION['role'], array(2,3,4,5)) ){
			$this->error('权限不够！');
		}
		if(IS_POST){

			//商品名称
			$name=I('name','');

			// 查询条件
			$where="name like '%".$name."%' and status=1";//

			// 查询结果
			$goodsList=M('shop_goods')->where($where)->order('sort DESC,update_time DESC,add_time DESC')->select();

			$this->page=1;//获得页码
			$this->sort=0;//序号
			$this->assign('goodsList',$goodsList);
			$this->display();
		}else{

			import('ORG.Util.Page');//引入分页类

			$where="status=1";
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
	 * 商品列表
	 * @author 黄俊
	 * date 2017-7-4
	 */
	public function goodsList(){
		//权限判断
		if( !in_array($_SESSION['role'], array(2,3,4,5)) ){
			$this->error('权限不够！');
		}

		if(IS_POST){

			//接收参数
			$name=I('name','');//商品名称
			$cate_id=I('cate_id',0,'intval');//商品分类

			// 查询条件
			$where="cate_id=".$cate_id." and name like '%".$name."%' and status=1";

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

			$where="cate_id=".$cate_id."  and status=1";
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
	 * 商品详情页
	 * @author 黄俊
	 * date 2017-7-4
	 */
	public function goodsView(){
		//权限判断
		if( !in_array($_SESSION['role'], array(2,3,4,5)) ){
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
	 * 商品购买或退回
	 * @author 黄俊
	 * date 2017-7-25
	 */
	public function chooseGoods(){

		//权限判断
		if( !in_array($_SESSION['role'], array(2,3,4,5)) ){
			$this->error('权限不够！');
		}

		// 是否是ajax请求
		if(IS_AJAX){

			//接收数据
			$goods_id=I('goods_id',0,'intval');
			$num=I('num',0,'intval');
			$action=I('action','');
			$shop_order_id=I('shop_order_id',0,'intval');

			// 检查num是否合法
			if( $num<=0 ){
				$rs['status']=2;
				$rs['msg']='非法操作！';
				$this->ajaxReturn($rs);
			}



			// 检查action是否合法
			if( !in_array($action, array('del','add')) ){//del:加入订货单 add:退货 【del、add是相对goods表而言】
				$rs['status']=2;
				$rs['msg']='非法操作！';
				$this->ajaxReturn($rs);
			}

			//查询商品，检查是否可操作
			$goods=M('shop_goods')->where(array('goods_id'=>$goods_id,'status'=>1))->find();

			//检查是否为空
			if(empty($goods)){
				$rs['status']=2;
				$rs['msg']='非法访问！';
				$this->ajaxReturn($rs);
			}

			// 商品出库入库记录数据
			$num=$action=='add'?-$num:$num;//变动数量

			// 检查变动数量是否合法
			if( $action=='del' && $goods['num']-$num < 0 ){
				$rs['status']=2;
				$rs['msg']='数量不足，购买失败！';
				$this->ajaxReturn($rs);
			}

			/*查询或创建订单*/
			$shop_order=array();//订单信息

			//其他人的订单
			if($shop_order_id !=0){
				$shop_order=M('shop_order')->where(array('shop_order_id'=>$shop_order_id))->find();
			}

			//自己的订单
			if($shop_order_id ==0){

				#1、查询该用户处于下单中的重销订单
				$shop_order=M('shop_order')->where(array('member_id'=>$_SESSION['uid'],'status'=>1))->find();

				//如果没有
				if( empty($shop_order) ){
					#2、如果没有，且是add操作，返回错误
					if($action=='add'){
						$rs['status']=2;
						$rs['msg']='非法操作！';
						$this->ajaxReturn($rs);
					}
					#3、是del操作则自动创建，并返回完整的重销订单信息
					if($action=='del'){
						$shop_order=SERVICE('Shop')->createShopOrder();
					}
				}
			}

			// P($shop_order);die();

			//如果是馆主，只可以操作自己的订单
	    	if( $_SESSION['role'] ==2 ){
	    		if( $_SESSION['uid'] != $shop_order['member_id'] ){
	    			$rs['status']=2;
					$rs['msg']='非法操作！';
					$this->ajaxReturn($rs);
	    		}
	    	}
			
			/*将要变化的额度*/ 
			$changeMoney=$num*$goods['price']*$goods['discount']*0.01;//客户看到的变化额度
			$trueChangeMoney=$num*$goods['true_price']*$goods['discount']*0.01;//真实的变化额度


			#2、是否有相同的商品，已经被加入到该订货单:
			$where=array(
				'shop_order_id'=>$shop_order['shop_order_id'],
				'goods_id'=>$goods['goods_id']
				);
			$chooseGoods=M('shop_order_goods_list')->where($where)->find();

			// 判断
			if(empty($chooseGoods)){//没有，执行保存操作

				// 如果是退回操作
				if($action=='add'){
					$rs['status']=2;
					$rs['msg']='警告：非法操作，该订单内，没有该商品！';
					$this->ajaxReturn($rs);
				}

				$goodsListData['shop_order_id']=$shop_order['shop_order_id'];
				$goodsListData['goods_id']=$goods['goods_id'];
				$goodsListData['num']=$num;
				$goodsListData['add_time']=date('Y-m-d H:i:s');
				$goodsListData['update_time']=date('Y-m-d H:i:s');

				if(!M('shop_order_goods_list')->add($goodsListData)){
					$rs['status']=2;
					$rs['msg']='加入到订货单列表失败';
					$this->ajaxReturn($rs);
				}

			}else{//有，执行更新操作，

				$goodsListData['list_id']=$chooseGoods['list_id'];
				$goodsListData['shop_order_id']=$shop_order['shop_order_id'];
				$goodsListData['goods_id']=$goods['goods_id'];
				$goodsListData['num']=$chooseGoods['num']+$num;
				$goodsListData['update_time']=date('Y-m-d H:i:s');

				#判断改变后的num:
				if( $goodsListData['num']<0 ){//操作不合法：没有那么多商品数量
					$rs['status']=2;
					$rs['msg']='没有那么多商品数量';
					$this->ajaxReturn($rs);
				}else if($goodsListData['num']==0 ){//数量为0，执行删除操作
					if( !M('shop_order_goods_list')->where(array('list_id'=>$goodsListData['list_id']))->delete() ){
						$rs['status']=2;
						$rs['msg']='网络不好，请重试!';
						$this->ajaxReturn($rs);
					}
				}else{//其余，执行更新操作
					if(!M('shop_order_goods_list')->save($goodsListData)){
						$rs['status']=2;
						$rs['msg']='加入到订单列表失败';
						$this->ajaxReturn($rs);
					}
				}
				
			}
			
			// 保存商品出库入库记录数据
			$goodsLogData['goods_id']=$goods['goods_id'];
			$goodsLogData['num']=-$num;
			$goodsLogData['type']=-$num>0?4:3;
			$goodsLogData['add_time']=date('Y-m-d H:i:s');
			$goodsLogData['update_time']=date('Y-m-d H:i:s');
			$goodsLogData['handle_user']=$_SESSION['user'];
			$goodsLogData['handle_name']=$_SESSION['name'];

			// 保存记录
			if(M('shop_goods_log')->add($goodsLogData)){

				// 商品总数变动
				$data['goods_id']=$goods['goods_id'];
				$data['num']=$goods['num']-$num;
				$data['update_time']=date('Y-m-d H:i:s');
				if(M('shop_goods')->save($data)){//总数变动

					//更新订单总额
					$shopOrderData['shop_order_id']=$shop_order['shop_order_id'];
					$shopOrderData['money']=$shop_order['money']+$changeMoney;
					$shopOrderData['true_money']=$shop_order['true_money']+$trueChangeMoney;
					$shopOrderData['update_time']=date('Y-m-d H:i:s');

					if(M('shop_order')->save($shopOrderData)){
						$rs['status']=1;
						$rs['num']=-$num;
						$rs['msg']='成功';
						$this->ajaxReturn($rs);
					}else{
						$rs['status']=2;
						$rs['msg']='订单总额变动失败';
						$this->ajaxReturn($rs);
					}
					
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


}

?>