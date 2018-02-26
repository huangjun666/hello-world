<?php
/**
 * 块内容设置
 * @author 黄俊
 * date 2016-6-28
 */
class BlockAction extends BaseAction{

	/**
	 * 首页
	 * @author 黄俊
	 * date 2016-6-28
	 */
	public function index(){

		if(IS_POST){
			//权限判断
			if( !in_array($_SESSION['role'], array(3,4)) ){
				$this->error('权限不够！');
			}
			
			//获取提交的表单值
			$data['orderGoods_msg']=I('orderGoods_msg','');

			$block=M('block');

			//循环更新配置库
			foreach ($data as $key => $value) {

				$d['update_time']=date('Y-m-d H:i:s');
				$d['value']=$value;

				if( !$block->where(array('key'=>$key))->save($d) ){
					$this->error('配置失败，请重试！',U('index'));
				}

			}

			//配置完成，调整页面
			$this->success('配置成功！',U('index'));


		}else{
			//权限判断
			if( !in_array($_SESSION['role'], array(3,4)) ){
				$this->error('权限不够！');
			}

			// 奖励配置
			$this->block=SERVICE('Block')->getAllBlock();

			$this->display();
		}
		
		
	}

}
?>