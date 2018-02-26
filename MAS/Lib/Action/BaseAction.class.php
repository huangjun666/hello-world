<?php
/**
 * 基类
 * @author 黄俊
 * date 2016-6-28
 */
class BaseAction extends Action {

	/**
	 * 类似构造函数
	 * @author 黄俊
	 * date 2016-6-28
	 */
    public function _initialize(){

    	// 判断登录状态
		if(!isset($_SESSION['uid']) && MODULE_NAME !='Login' ){

			//如果进行自动财务结算操作，可以不用进行登录验证
			if( MODULE_NAME."/".ACTION_NAME !='Finance/settlement_week' && MODULE_NAME."/".ACTION_NAME !='Finance/settlement_month' &&  MODULE_NAME."/".ACTION_NAME !='Finance/compute_coach' &&  MODULE_NAME."/".ACTION_NAME !='Finance/compute_team' ){
				
				$this->redirect('Login/index');
			}
			
		}else if( USER_LIMIT && MODULE_NAME !='Login' ){

			//如果进行自动财务结算操作，可以不用进行登录验证
			if( MODULE_NAME."/".ACTION_NAME !='Finance/settlement_week' && MODULE_NAME."/".ACTION_NAME !='Finance/settlement_month' &&  MODULE_NAME."/".ACTION_NAME !='Finance/compute_coach' &&  MODULE_NAME."/".ACTION_NAME !='Finance/compute_team' ){
				
				//'admin','LSMT001','LSMT','CNB_cszy4007'
				if( !in_array($_SESSION['user'], array('admin')) ){
					session_unset();
					session_destroy();
					$this->error('系统正在维护中。。。。。');
				}
			}
			
		}

		//如果不是shopAction，清除shopAction里面的session['shop_order_id']
		// if(MODULE_NAME !='Shop'){
		// 	session('shop_order_id',0);
		// }

	}
}

?>