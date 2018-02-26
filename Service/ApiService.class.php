<?php
/**
 * 外部API服务service
 * @author 黄俊
 * @date 2016-11-30
 */
class ApiService extends BaseService{

	/**
	 * 物流公司查询--获得该API支持的所有物流公司
	 * @author 黄俊
	 * 接口来源：阿里云
	 * 备注：【要花钱的】
	 * date 2016-12-7
	 */
	public function getLogisticsCompany(){

		$host = "http://jisukdcx.market.alicloudapi.com";
	    $path = "/express/type";
	    $method = "GET";
	    $appcode = "8353d0ec26384704900911fcd399031b";//你自己的AppCode
	    $headers = array();
	    array_push($headers, "Authorization:APPCODE " . $appcode);
	    $querys = "";
	    $bodys = "";
	    $url = $host . $path;

	    $curl = curl_init();
	    curl_setopt($curl, CURLOPT_CUSTOMREQUEST, $method);
	    curl_setopt($curl, CURLOPT_URL, $url);
	    curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
	    curl_setopt($curl, CURLOPT_FAILONERROR, false);
	    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
	    curl_setopt($curl, CURLOPT_HEADER, false);
	    if (1 == strpos("$".$host, "https://"))
	    {
	        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
	        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
	    }
	    // var_dump(curl_exec($curl));
	    $rs=curl_exec($curl);
	    $rs=json_decode($rs);

	    return $rs;
	}

	/**
	 * 物流查询--获得详细物流信息
	 * @author 黄俊
	 * 接口来源：阿里云
	 * 备注：【要花钱的】
	 * 参数：$companyType：物流公司，$number：物流单号
	 * date 2016-12-7
	 */
	public function getLogisticsInfo($companyType,$number){
		set_time_limit(0);
		$host = "http://jisukdcx.market.alicloudapi.com";
	    $path = "/express/query";
	    $method = "GET";
	    $appcode = "8353d0ec26384704900911fcd399031b";//你自己的AppCode
	    $headers = array();
	    array_push($headers, "Authorization:APPCODE " . $appcode);
	    $querys = "number=".$number."&type=".$companyType;
	    $bodys = "";
	    $url = $host . $path . "?" . $querys;

	    $curl = curl_init();
	    curl_setopt($curl, CURLOPT_CUSTOMREQUEST, $method);
	    curl_setopt($curl, CURLOPT_URL, $url);
	    curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
	    curl_setopt($curl, CURLOPT_FAILONERROR, false);
	    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
	    curl_setopt($curl, CURLOPT_HEADER, false);
	    if (1 == strpos("$".$host, "https://"))
	    {
	        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
	        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
	    }
	    // var_dump(curl_exec($curl));
	    $rs=curl_exec($curl);
	    $rs=json_decode($rs);

	    return $rs;
	}

}

?>