<?php
/**
 * 直播模块
 * @author 黄俊
 * 2017-4-10
 */
class TestAction extends BaseAction {


    /**
     * 直播聊天--发送聊天消息
     * @author 黄俊
     * date 2017-4-10
     */
    public function sendChat(){
        
        //参数
        $member_id=I('member_id',0,'intval');
        $name=I('name','');

        $rs['status']=1;
        $rs['msg']="成功";
        P($rs);die();
        // $this->ajaxReturn($rs);
    }

    /**
     * 直播聊天--发送聊天消息
     * @author 黄俊
     * date 2017-4-10
     */
    public function sendTest(){

        //脚本可以一直执行
        set_time_limit(0);
        //ignore_user_abort(true);//客户端断开，脚本停止执行
        session_write_close();//关闭session读写锁，释放锁资源
        // session_commit();
        // session("[pause]");
        // echo session_save_path();
        // die();
        
        //参数
        $member_id=I('member_id',0,'intval');
        $name=I('name','');

        $rs['status']=1;
        $rs['msg']="失败";

        $i=0;
        while (true) {//测试ajax阻塞效果
            if(connection_aborted()){
                file_put_contents(time()."断开了.txt", "断开了！");
                break;
            }
            if($i>10){
                // file_put_contents(time().".txt", date('Y-m-d H:i:s'));
                break;
            }
            sleep(1);
            $i++;
            
        }

        $rs['msg']="成功";
        P($rs);die();
        $this->ajaxReturn($rs);
    }


}

?>