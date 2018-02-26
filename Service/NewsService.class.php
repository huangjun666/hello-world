<?php
/**
 * 通知栏服务service
 * @author 黄俊
 * @date 2017-5-13
 */
class NewsService extends BaseService{

	/**
	 * 获得公告列表
	 * @author 黄俊
	 * 2017-5-13
	 */
	public function getNewsList($where='',$limit=''){

		// 查询sql
		$sql='';
		$sql.='SELECT * FROM `news`';

		//where条件
		if(!empty($where)){
			$sql.=' where '.$where;
		}

		//order by 排序
		$sql.=' order by add_time DESC';

		//limit限制
		if(!empty($limit)){
			$sql.=' limit '.$limit;
		}

		//查询
		$rs=M()->query($sql);

		// 返回结果
		return $rs;
	}

	/**
	 * 获得公告总量
	 * @author 黄俊
	 * 2017-5-13
	 */
	public function getNewsCount($where=''){

		// 查询sql
		$sql='';
		$sql.='SELECT COUNT(*) total FROM `news`';

		//where条件
		if(!empty($where)){
			$sql.=' where '.$where;
		}

		//查询
		$rs=M()->query($sql);
		
		// 返回结果
		return $rs[0]['total'];
	}

	/**
	 * 检查会员是否有该公告的查看权限
	 * @author 黄俊
	 * 2017-5-13
	 * return bool
	 */
	public function isCanShow($news_id){

		// 查询sql
		$sql='';
		$sql.='SELECT * FROM `news`';
		$sql.='WHERE FIND_IN_SET("'.$_SESSION['role'].'",role) AND news_id = '.$news_id;

		//查询
		$rs=M()->query($sql);
		
		// 返回结果
		if(empty($rs)){
			return false;
		}else{
			return true;
		}
	}

}

?>