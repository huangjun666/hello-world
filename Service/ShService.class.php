<?php
/**
 * 生活馆服务service
 * @author 黄俊
 * @date 2017-8-1
 */
class ShService extends BaseService{

	/**
	 * 获得生活馆信息列表统计
	 * @author 黄俊
	 * 2017-8-1
	 */
	public function getMemberCount($where=''){

		// 统计总数
		$count=M('member')->where($where)->count();

		// 返回结果
		return $count;
	}

	/**
	 * 获得生活馆信息列表
	 * @author 黄俊
	 * 2017-8-1
	 */
	public function getMemberList($where='',$limit=""){

		// 查询sql
		$sql='';

		$sql.='SELECT';
		$sql.=' m.member_id,';
		$sql.=' m.user,';
		$sql.=' m.name,';
		$sql.=' m.sh_adress,';
		$sql.=' m.sh_tel,';
		$sql.=' m.sh_room_endtime,';
		$sql.=' m.sh_status,';
		$sql.=' m.sh_update_time,';
		$sql.=' m.handle,';
		$sql.=' p.`areaname` sh_province,';
		$sql.=' c.`areaname` sh_city,';
		$sql.=' d.`areaname` sh_district';

		if($where){//where不为空
			$sql.=' FROM (SELECT * FROM `member` WHERE '.$where.') m';
		}else{//where为空
			$sql.=' FROM `member` m';
		}

		$sql.=' LEFT JOIN `shop_area` p ON p.`id`=m.`sh_ProvinceID`';
		$sql.=' LEFT JOIN `shop_area` c ON c.`id`=m.`sh_CityID`';
		$sql.=' LEFT JOIN `shop_area` d ON d.`id`=m.`sh_DistrictID`';

		//order by 排序
		$sql.=' order by m.sh_room_endtime DESC';

		//limit限制
		if(!empty($limit)){
			$sql.=' limit '.$limit;
		}

		// echo $sql;die();

		//查询
		$rs=M()->query($sql);

		// 返回结果
		return $rs;
	}

	/**
	 * 获得生活馆信息详情
	 * @author 黄俊
	 * 2017-8-1
	 */
	public function getMemberView($where=''){

		// 查询sql
		$sql='';

		$sql.='SELECT';
		$sql.=' m.member_id,';
		$sql.=' m.user,';
		$sql.=' m.name,';
		$sql.=' m.sh_ProvinceID,';
		$sql.=' m.sh_CityID,';
		$sql.=' m.sh_DistrictID,';
		$sql.=' m.sh_adress,';
		$sql.=' m.sh_tel,';
		$sql.=' m.sh_room_endtime,';
		$sql.=' m.sh_status,';
		$sql.=' m.sh_update_time,';
		$sql.=' m.handle,';
		$sql.=' mh.name handle_name,';
		$sql.=' mh.tel handle_tel,';
		$sql.=' p.`areaname` sh_province,';
		$sql.=' c.`areaname` sh_city,';
		$sql.=' d.`areaname` sh_district';

		$sql.=' FROM (SELECT * FROM `member` WHERE '.$where.') m';
		$sql.=' LEFT JOIN `member` mh ON mh.`user`=m.`handle`';

		$sql.=' LEFT JOIN `shop_area` p ON p.`id`=m.`sh_ProvinceID`';
		$sql.=' LEFT JOIN `shop_area` c ON c.`id`=m.`sh_CityID`';
		$sql.=' LEFT JOIN `shop_area` d ON d.`id`=m.`sh_DistrictID`';

		// echo $sql;die();

		//查询
		$rs=M()->query($sql);

		// 返回结果
		return $rs[0];
	}

}

?>