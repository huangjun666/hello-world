<?php
/**
 * 通知栏
 * @author 黄俊
 * date 2017-5-7
 */
class NewsAction extends BaseAction{
	
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
	 * date 2017-5-7
	 */
	public function index(){
		//权限判断
		if( !in_array($_SESSION['role'], array(1,2,3,4)) ){
			$this->error('权限不够！');
		}

		$where=" FIND_IN_SET('".$_SESSION['role']."',role)";
		
		import('ORG.Util.Page');//引入分页类

		/*分页*/
		$count=SERVICE('News')->getNewsCount($where);//总数量
		$page=new Page($count,20);
		$limit=$page->firstRow.','.$page->listRows;
		
		$this->NewsList=SERVICE('News')->getNewsList($where,$limit);
		$this->page=$page->show();//获得页码
		$this->sort=$page->firstRow;//序号

		$this->display();
		
	}

	/**
	 * 添加公告
	 * @author 黄俊
	 * date 2017-5-9
	 */
	public function add(){
		//权限判断
		if( !in_array($_SESSION['role'], array(3,4)) ){
			$this->error('权限不够！');
		}
		if(IS_POST){

			/*验证表单提交字段*/
			// P($_POST);die();
			//公告标题不可以为空
			$data['title']=I('title','');
			if(empty($data['title'])){
				$this->assign('error','公告标题不可以为空');
				$this->display();
				exit();
			}

			//公告内容不可以为空
			$data['content']=I('news_content','');
			if(empty($data['content'])){
				$this->assign('error','公告内容不可以为空');
				$this->display();
				exit();
			}

			//角色权限设定
			$data['role']=I('role',array());
			$data['role']=implode($data['role'], ',');
			if(empty($data['role'])){
				$this->assign('error','权限设定不合法！');
				$this->display();
				exit();
			}

			$data['handle_user']=$_SESSION['user'];
			$data['handle_name']=$_SESSION['name'];
			$data['update_time']=date('Y-m-d H:i:s');

			//保存
			$news_id=M('news')->add($data);
			if($news_id){//保存成功之后，跳转至list页面
				$this->success('添加成功！',U('index'));
			}else{
				$this->assign('error','非法错误，请重试');
				$this->display();
			}

		}else{
			$this->display();
		}
		
		
	}

	/**
	 * 编辑公告
	 * @author 黄俊
	 * date 2017-5-9
	 */
	public function edit(){
		//权限判断
		if( !in_array($_SESSION['role'], array(3,4)) ){
			$this->error('权限不够！');
		}

		if(IS_POST){
			/*验证表单提交字段*/
			
			
			//news_id不可以为空
			$data['news_id']=I('news_id',0,'intval');
			if(empty($data['news_id'])){
				$this->assign('error','非法请求');
				$this->display();
				exit();
			}


			//公告标题不可以为空
			$data['title']=I('title','');
			if(empty($data['title'])){
				$this->assign('error','公告标题不可以为空');
				$this->display();
				exit();
			}

			//公告内容不可以为空
			$data['content']=I('news_content','');
			if(empty($data['content'])){
				$this->assign('error','公告内容不可以为空');
				$this->display();
				exit();
			}

			//角色权限设定
			$data['role']=I('role',array());
			$data['role']=implode($data['role'], ',');
			if(empty($data['role'])){
				$this->assign('error','权限设定不合法！');
				$this->display();
				exit();
			}

			$data['handle_user']=$_SESSION['user'];
			$data['handle_name']=$_SESSION['name'];
			$data['update_time']=date('Y-m-d H:i:s');

			//保存
			$news_id=M('news')->save($data);
			if($news_id){//保存成功之后，跳转至list页面
				$this->success('编辑成功！',U('index'));
			}else{
				$this->assign('error','非法错误，请重试');
				$this->display();
			}

		}else{
			// 接收字段
			$news_id=I('news_id',0,'intval');

			//查询出公告
			$news=M('news')->where(array("news_id"=>$news_id))->find();

			//是否存在
			if(empty($news)){
				$this->error('非法访问！');
			}

			//是否有查看权限
			$roleArr=explode(',',$news['role']);//角色

			if( !in_array($_SESSION['role'],$roleArr) ){
				$this->error('非法访问！');
			}
			
			$this->news=$news;
			$this->roleArr=$roleArr;
			$this->display();
		}
		
		
	}

	/**
	 * 删除公告
	 * @author 黄俊
	 * date 2017-5-13
	 */
	public function del(){
		//权限判断
		if( !in_array($_SESSION['role'], array(3,4)) ){
			$this->error('权限不够！');
		}
		// 接收字段
		$news_id=I('news_id',0,'intval');
		
		//删除公告
		$rows=M('news')->where(array("news_id"=>$news_id))->delete();

		if($rows){
			$this->success('删除成功！',U('index'));
		}else{
			$this->success('删除失败！',U('index'));
		}
		
	}

	/**
	 * 公告详情
	 * @author 黄俊
	 * date 2017-5-9
	 */
	public function view(){
		//权限判断
		if( !in_array($_SESSION['role'], array(1,2,3,4)) ){
			$this->error('权限不够！');
		}
		// 接收字段
		$news_id=I('news_id',0,'intval');

		//查询出公告
		$news=M('news')->where(array("news_id"=>$news_id))->find();

		//是否存在
		if(empty($news)){
			$this->error('非法访问！');
		}

		//是否有查看权限
		$roleArr=explode(',',$news['role']);

		if( !in_array($_SESSION['role'],$roleArr) ){
			$this->error('非法访问！');
		}
		
		$this->news=$news;
		$this->display();
		
	}

}
?>