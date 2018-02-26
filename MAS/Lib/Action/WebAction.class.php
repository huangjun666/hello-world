<?php
/**
 * 通用的服务
 * @author 黄俊
 * date 2016-6-28
 */
class WebAction extends BaseAction{

	/**
	 * 返回省和直辖市
	 * @author 黄俊
	 * date 2016-6-28
	 */
	public function province(){

		if(IS_AJAX){//ajax操作

			/*查询省和直辖市*/
			$web=D('Web');
			$rs=$web->province();

			//ajax返回
			if($rs){
				$data['rs']=$rs;
				$data['status']=1;
				$data['msg']='成功';
				$this->ajaxReturn($data);
			}else{
				$data['status']=2;
				$data['msg']='失败';
				$this->ajaxReturn($data);
			}
		
		}
		
	}

	/**
	 * 返回城市
	 * @author 黄俊
	 * date 2016-6-29
	 * 参数：ProvinceID 省ID号
	 */
	public function city(){

		if(IS_AJAX){//ajax操作

			//获取参数
			$provinceID=I('provinceID',0,'intval');

			/*查询省下一级的市*/
			$web=D('Web');
			$rs=$web->city($provinceID);

			//ajax返回
			if($rs){
				$data['rs']=$rs;
				$data['status']=1;
				$data['msg']='成功';
				$this->ajaxReturn($data);
			}else{
				$data['status']=2;
				$data['msg']='失败';
				$this->ajaxReturn($data);
			}
		
		}
	}

	/**
	 * 返回县/区
	 * @author 黄俊
	 * date 2016-6-29
	 * 参数：cityID 市ID号
	 */
	public function district(){

		if(IS_AJAX){//ajax操作

			//获取参数
			$cityID=I('cityID',0,'intval');

			/*查询省下一级的市*/
			$web=D('Web');
			$rs=$web->district($cityID);

			//ajax返回
			if($rs){
				$data['rs']=$rs;
				$data['status']=1;
				$data['msg']='成功';
				$this->ajaxReturn($data);
			}else{
				$data['status']=2;
				$data['msg']='失败';
				$this->ajaxReturn($data);
			}
		
		}
	}

}
?>