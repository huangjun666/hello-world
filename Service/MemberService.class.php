<?php
/**
 * 会员服务service
 * @author 黄俊
 * @date 2016-11-30
 */
class MemberService extends BaseService{

	/**
	 * 通过身份证获得会员信息
	 * @author 黄俊
	 * 2016-12-7
	 */
	public function getMemberByIdCard($id_card=''){
		// 获得会员信息
		$member=M('member')->where(array('id_card'=>$id_card))->find();
		// 返回结果
		return $member;
	}

}

?>