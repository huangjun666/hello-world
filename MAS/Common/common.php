<?php
/**
 * 打印数组函数
 * @author 黄俊
 * date 2016-6-28
 * $arr:数组
 */
function P($arr){
	echo '<pre>';
	print_r($arr);
}


/**
 * 检查是否是移动电话
 * @author 黄俊
 * date 2016-6-30
 */
function is_tel($tel){

    if(preg_match("/^1[34578]\d{9}$/", $tel)){
        return true;
    }else{
        return false;
    }
}


/**
 * 过滤部分用户
 * @author 黄俊
 * date 2017-9-10
 */
function userFilter($user){

    $filterArray=array("CNB_wtq5422","CNB_twh4545","CNB_twhA8112","CNB_twhB8473","CNB_xq5805","CNB_cl5514",
        "CNB_zq4268","CNB_rq2560","CNB_hch5253","CNB_ls1435","CNB_xll5840","CNB_ljq2320",
        "CNB_xgj7558","CNB_yl3673","CNB_gp8486","CNB_hzb9750","CNB_wb7301","CNB_lzp6399",
        "CNB_wyz6355","CNB_fy5251","CNB_jh9552","CNB_wj2984","CNB_f6086","CNB_dss5396",
        "CNB_hwq0792","CNB_lyp1252","CNB_s9983","CNB_zy9073","CNB_sff6943","CNB_wc6726",
        "CNB_lwh9637","CNB_hb5418","CNB_gfy9966","CNB_lz0572");

    // 是否在过滤名单里面
    if( in_array($user, $filterArray) ){
        return true;
    }

    return false;
}




/**
 * 检查是否是银行卡号
 * @author 黄俊
 * date 2017-5-6
 */
function is_bank_card($bank_card){

    if(preg_match("/^\d+$/", $bank_card)){
        return true;
    }else{
        return false;
    }
}

/**
 * 检查是否是固定电话
 * @author 黄俊
 * date 2016-6-30
 */
function is_fixedTel($tel){

    if(preg_match("/^0\d{2,3}-?\d{7,8}$/", $tel)){
        return true;
    }else{
        return false;
    }
}



/**
 * 检查账号是否合法
 * @author 黄俊
 * date 2016-6-30
 */
function is_user($user){

    if( $user == 'admin' || $user == 'root' ){
        return false;
    }

	if(preg_match("/^[a-zA-Z][a-zA-Z0-9_]{4,50}$/", $user)){
	 	return true;
	}else{
		return false;
	}
}

/**
 * 检查邮箱
 * @author 黄俊
 * date 2016-6-30
 */
function is_email($email){

	if(preg_match("/\w+([-+.]\w+)*@\w+([-.]\w+)*\.\w+([-.]\w+)*/", $email)){
	 	return true;
	}else{
		return false;
	}
}

/**
 * 检查团队标签
 * @author 黄俊
 * date 2016-6-30
 */
function is_teamTag($tag){

    if(preg_match("/^[0-9a-zA-Z]{2,5}$/",$tag)){  
      return true;
    }else{
      return false;
    }

}


/**
 * 检查身份证
 * @author 黄俊
 * date 2016-6-30
 */
function is_idCard($id){

	if(preg_match("/^[1-9]\d{5}[1-9]\d{3}((0\d)|(1[0-2]))(([0|1|2]\d)|3[0-1])\d{3}([0-9]|[x,X])$/", $id)  || preg_match("/^[1-9]\d{7}((0\d)|(1[0-2]))(([0|1|2]\d)|3[0-1])\d{3}$/", $id)){
	 	return true;
	}else{
		return false;
	}
}

/**
 * 字符串加密函数
 * @author 黄俊
 * @date 2016-6-30
 */
function encrypt($data, $key='mac')
{
 $key = md5($key);
    $x  = 0;
    $len = strlen($data);
    $l  = strlen($key);
    for ($i = 0; $i < $len; $i++)
    {
        if ($x == $l) 
        {
         $x = 0;
        }
        $char .= $key{$x};
        $x++;
    }
    for ($i = 0; $i < $len; $i++)
    {
        $str .= chr(ord($data{$i}) + (ord($char{$i})) % 256);
    }
    return base64_encode($str);
}

/**
 * 字符串解密函数
 * @author 黄俊
 * @date 2016-6-30
 */
function decrypt($data, $key='mac')
{
 $key = md5($key);
    $x = 0;
    $data = base64_decode($data);
    $len = strlen($data);
    $l = strlen($key);
    for ($i = 0; $i < $len; $i++)
    {
        if ($x == $l) 
        {
         $x = 0;
        }
        $char .= substr($key, $x, 1);
        $x++;
    }
    for ($i = 0; $i < $len; $i++)
    {
        if (ord(substr($data, $i, 1)) < ord(substr($char, $i, 1)))
        {
            $str .= chr((ord(substr($data, $i, 1)) + 256) - ord(substr($char, $i, 1)));
        }
        else
        {
            $str .= chr(ord(substr($data, $i, 1)) - ord(substr($char, $i, 1)));
        }
    }
    return $str;
}

/**
 * 自增加
 * @author 黄俊
 * @date 2016-6-30
 */
function addNum($num,$n=1){
    $num=$num+$n;
    return $num;
}

/**
 * 数组分页
 * @author 黄俊
 * @date 2017-6-19
 */
function arrPage($arr,$limit,$count){

    $page=explode(',', $limit);//分页

    $newArr=array();//新的结果

    $limitNum=$page[1]+$page[0];//限制到某个索引位置

    $limitNum=$limitNum>$count?$count:$limitNum;//最大值不能超过总量

    //取出区间内的数据
    for ($i=$page[0]; $i <$limitNum ; $i++) { 
        $newArr[]=$arr[$i];
    }

    return $newArr;
}

/**
 * @desc 获取随机字符串
 * @param int $type
 * @param int $length
 * @return string
 */
function randomString($type, $length) {
    switch ($type) {
        case 1 :
            $charactors = "abcdefghijklmnopqrstuvwxyz";
            break;
        case 2 :
            $charactors = "0123456789";
            break;
        case 3 :
            $charactors = "0123456789abcdefghijklmnopqrstuvwxyz";
            break;
        case 4 :
            $charactors = "3456789";
            break;
    }

    $randomString = "";
    while ( strlen ( $randomString ) < $length ) {
        $randomString .= substr ( $charactors, (mt_rand () % (strlen ( $charactors ))), 1 );
    }
    return ($randomString);
}

/**
 * 获得上月初、本月初和下月初时间
 * @author 黄俊
 * date 2016-7-7
 */
function getMonthFirst($date=''){

        $date=empty($date)?date('Y-m-d'):$date;
        $firstday = date("Y-m-01",strtotime($date));
        $lastday = date("Y-m-d",strtotime("$firstday +1 month"));
        $prevday = date("Y-m-d",strtotime("$firstday -1 month"));
        return array($firstday,$lastday,$prevday);
 }

 /**
 * 获得上月初、本月初和下月初时间
 * @author 黄俊
 * date 2017-6-18
 */
function getMonthFirstNew($date=''){

    $date=empty($date)?date('Y-m-d'):$date;
    $w=date('j',strtotime($date));  //获取当前月的第几天
    
    /*检查是否是第一天*/   
    if($w==1){
        $now_end=date("Y-m-01",strtotime($date));//下月初
        $now_start=date("Y-m-d",strtotime("$now_end -1 month"));//当月初
    }else{
        $now_start=date("Y-m-01",strtotime($date));//当月初
        $now_end=date("Y-m-d",strtotime("$now_start +1 month"));//下月初
    }
    

    return array($now_start,$now_end);
 }

/**
 * 时间减一秒
 * @author 黄俊
 * date 2016-7-7
 */
function reduceOneSecond($date){
    $time=strtotime($date)-1;
    return date("Y-m-d H:i:s",$time);
}

/**
 * 时间加一秒
 * @author 黄俊
 * date 2016-7-7
 */
function addOneSecond($date){
    $time=strtotime($date)+1;
    return date("Y-m-d H:i:s",$time);
}

/**
 * 获得指定时间的周开始和结束时间
 * @author 黄俊
 * date 2016-7-7
 */
function getWeekInfo($date){

    $date=empty($date)?date('Y-m-d'):$date;
    $first=1; //$first =1 表示每周星期一为开始日期 0表示每周日为开始日期
    $w=date('w',strtotime($date));  //获取当前周的第几天 周日是 0 周一到周六是 1 - 6
    $now_start=date('Y-m-d 00:00:00',strtotime("$date -".($w ? $w - $first : 6).' days')); //获取本周开始日期，如果$w是0，则表示周日，减去 6 天
    $now_end=date('Y-m-d 23:59:59',strtotime("$now_start +6 days"));  //本周结束日期
    $last_start=date('Y-m-d 00:00:00',strtotime("$now_start - 7 days"));  //上周开始日期
    $last_end=date('Y-m-d 23:59:59',strtotime("$now_start - 1 days"));  //上周结束日期

    $data['now_start']=$now_start;
    $data['now_end']=$now_end;
    $data['last_start']=$last_start;
    $data['last_end']=$last_end;

    return $data;
}

/**
 * 获得指定时间的本周一和下周一时间
 * @author 黄俊
 * date 2016-7-7
 */
function getWeekFirst($date){

    $date=empty($date)?date('Y-m-d'):$date;
    $first=1; //$first =1 表示每周星期一为开始日期 0表示每周日为开始日期
    $w=date('w',strtotime($date));  //获取当前周的第几天 周日是 0 周一到周六是 1 - 6
    $now_start=date('Y-m-d 02:00:00',strtotime("$date -".($w ? $w - $first : 6).' days')); //获取本周开始日期，如果$w是0，则表示周日，减去 6 天
    $now_end=date('Y-m-d 02:00:00',strtotime("$now_start +7 days"));  //本周结束日期

    $data['now_start']=$now_start;
    $data['now_end']=$now_end;

    return $data;
}

/**
 * 新的获得指定时间的本周一和下周一时间
 * @author 黄俊
 * date 2017-3-6
 * 备注：该函数的区别在于，每逢周一，则返回上周的时间范围，其他6天，返回当周时间范围
 */
function getWeekFirstNew($date){

    $date=empty($date)?date('Y-m-d'):$date;
    $first=1; //$first =1 表示每周星期一为开始日期 0表示每周日为开始日期
    $w=date('w',strtotime($date));  //获取当前周的第几天 周日是 0 周一到周六是 1 - 6
    // echo $w;die();
    $now_start=date('Y-m-d 02:00:00',strtotime("$date -".($w ? $w - $first : 6).' days')); //获取本周开始日期，如果$w是0，则表示周日，减去 6 天
    $now_end=date('Y-m-d 02:00:00',strtotime("$now_start +7 days"));  //本周结束日期

    if($w==1){
        $data['now_start']=date('Y-m-d 02:00:00',strtotime("$now_start -7 days"));
        $data['now_end']=$now_start; 
    }else{
        $data['now_start']=$now_start;
        $data['now_end']=$now_end; 
    }
    

    return $data;
}

/**
 * 返回当前时间是否在0点至N点之间
 * @author 黄俊
 * date 2017-3-2
 * return bool
 */
function timeLimit($hour=0){
    
    $time=time();//当前时间
    $startTime=strtotime(date('Y-m-d'));// 起始时间
    $endTime=$startTime+($hour*60*60);// 结束时间

    /*比较时间范围*/ 
    if( $time >= $startTime && $time <= $endTime ){
        return true;
    }else{
        return false;
    }
}

/**
 * 返回文件扩展名
 * @author 黄俊
 * date 2017-7-11
 * return str
 */
function get_extension($file){
    return pathinfo($file, PATHINFO_EXTENSION);
}

/**
 * 邮箱发送函数
 * @author 黄俊
 * date 2016-7-7
 * 参数说明(发送地址, 邮件主题, 邮件内容,附件绝对路径(可选))
 */
function mailQT($sendto_email, $subject, $body,$att=array()){
    import('Class.PHPMailer',APP_PATH);
    $mail=new PHPMailer();
    //echo var_dump(empty($att)) ;die();
    // P($mail);die();
    $mail->IsSMTP();

    $mail->Host = "smtp.163.com";//smtp.163.com
    $mail->Username = "m18668049687@163.com";   
    $mail->Password = "180236ss";
    $mail->Port =25;
    $mail->FromName =  "浪莎美体";   
    $mail->SMTPAuth = true;
    $mail->From = $mail->Username;
    $mail->CharSet = "utf-8";           
    $mail->Encoding = "base64"; 
    $mail->AddAddress($sendto_email); 
    if(!empty($att)){
        foreach($att as $key=>$val){
            if(!empty($val)){
                $mail->AddAttachment($val);  //注意要给绝对路径
            }
        }
    }
     
    $mail->IsHTML(true); 
    $mail->Subject = $subject;
    $mail->Body = $body;
    $mail->AltBody ="text/html"; 
    if(!$mail->Send()) { 
        // echo "邮件错误信息: " . $mail->ErrorInfo; 
        return false;
    }else{
        // echo "邮件发送成功!"; 
        return true;
    }
}

/**
 * 短信发送函数--旧的
 * 应用场景：
 *     1、订单审核成功后，系统自动生成的会员账号，发送给用户手机
 *     2、异常时，通知管理员【黄俊】
 * @author 黄俊
 * date 2016-7-7
 * 参数说明(手机号码, 用户名, 密码)
 */
function duanxin($tel,$user,$password){

    import('Class.duanxin.TopSdk',APP_PATH);

    $c = new TopClient;
    $c ->appkey = '23418708';
    $c ->secretKey = '8f98e7e40a9b6126b4502bcd3e8eef16' ;
    $req = new AlibabaAliqinFcSmsNumSendRequest;
    $req ->setExtend( "" );
    $req ->setSmsType( "normal" );
    $req ->setSmsFreeSignName( "浪莎" );
    $req ->setSmsParam( "{user:\"$user\",password:\"$password\"}" );
    $req ->setRecNum( $tel );
    $req ->setSmsTemplateCode( "SMS_12785237" );//SMS_12785237:发送账号初始密码,SMS_67555152:发送账号初始密码-5-21-03
    $resp = $c ->execute( $req );
    return $resp;
}

/**
 * 短信发送函数--新的
 * 应用场景：
 *      1、订单审核成功后，系统自动生成的会员账号，发送给用户手机
 * @author 黄俊
 * date 2017-5-21
 * 参数说明(姓名,手机号码, 用户名, 密码)
 */
function duanxin1($tel,$name,$user,$password){

    import('Class.duanxin.TopSdk',APP_PATH);

    $c = new TopClient;
    $c ->appkey = '23418708';
    $c ->secretKey = '8f98e7e40a9b6126b4502bcd3e8eef16' ;
    $req = new AlibabaAliqinFcSmsNumSendRequest;
    $req ->setExtend( "" );
    $req ->setSmsType( "normal" );
    $req ->setSmsFreeSignName( "浪莎" );
    $req ->setSmsParam( "{name:\"$name\",user:\"$user\",password:\"$password\"}" );
    $req ->setRecNum( $tel );
    $req ->setSmsTemplateCode( "SMS_67555152" );//SMS_12785237:发送账号初始密码,SMS_67555152:发送账号初始密码-5-21-03
    $resp = $c ->execute( $req );
    return $resp;
}

/**
 * 短信发送函数
 * 应用场景：
 *     1、重销商城下单是，发短信通知用户，真实的下单金额
 * @author 黄俊
 * date 2017-8-13
 * 参数说明(手机号码, 用户名, 下单金额)
 */
function duanxin2($tel,$name,$money){

    import('Class.duanxin.TopSdk',APP_PATH);

    $c = new TopClient;
    $c ->appkey = '23418708';
    $c ->secretKey = '8f98e7e40a9b6126b4502bcd3e8eef16' ;
    $req = new AlibabaAliqinFcSmsNumSendRequest;
    $req ->setExtend( "" );
    $req ->setSmsType( "normal" );
    $req ->setSmsFreeSignName( "浪莎" );
    $req ->setSmsParam( "{name:\"$name\",money:\"$money\"}" );
    $req ->setRecNum( $tel );
    $req ->setSmsTemplateCode( "SMS_85535030" );//短信模板ID
    $resp = $c ->execute( $req );
    return $resp;
}

/**
 * 短信发送函数
 * 应用场景：
 *      1、订单审核【二审】成功后，推荐人被推荐5次，在达到5次的门槛时，短信通知用户
 * @author 黄俊
 * date 2017-7-3
 * 参数说明(手机号码, 姓名,推荐达标人数)
 */
function rec_duanxin($tel,$name,$num){

    import('Class.duanxin.TopSdk',APP_PATH);

    $c = new TopClient;
    $c ->appkey = '23418708';
    $c ->secretKey = '8f98e7e40a9b6126b4502bcd3e8eef16';
    $req = new AlibabaAliqinFcSmsNumSendRequest;
    $req ->setExtend( "" );
    $req ->setSmsType( "normal" );
    $req ->setSmsFreeSignName( "浪莎" );
    $req ->setSmsParam( "{name:\"$name\",num:\"$num\"}" );
    $req ->setRecNum( $tel );
    $req ->setSmsTemplateCode( "SMS_75910081" );//短信模板ID
    $resp = $c ->execute( $req );
    return $resp;
}

/**
 * 短信发送函数
 * 应用场景：
 *      1、团队业绩达到相应的星级标准，短信通知用户
 * @author 黄俊
 * date 2017-7-3
 * 参数说明(手机号码, 姓名,星级title)
 */
function star_duanxin($tel,$name,$title){

    import('Class.duanxin.TopSdk',APP_PATH);

    $c = new TopClient;
    $c ->appkey = '23418708';
    $c ->secretKey = '8f98e7e40a9b6126b4502bcd3e8eef16';
    $req = new AlibabaAliqinFcSmsNumSendRequest;
    $req ->setExtend( "" );
    $req ->setSmsType( "normal" );
    $req ->setSmsFreeSignName( "浪莎" );
    $req ->setSmsParam( "{name:\"$name\",title:\"$title\"}" );
    $req ->setRecNum( $tel );
    $req ->setSmsTemplateCode( "SMS_75755098" );//短信模板ID
    $resp = $c ->execute( $req );
    return $resp;
}

/**
 * 服务调用
 * @author 黄俊
 * @date 2016-11-30
 */
function SERVICE($serviceName){

    //加载核心service
    require_once APP_SERVICE.'BaseService.class.php';

    //加载需要调用的服务
    require_once APP_SERVICE.$serviceName.'Service.class.php';

    //返回调用实例
    $service=$serviceName.'Service';
    return $service::service($service);
}














?>