<?php

/*自定义标签库*/
class TagLibMac extends TagLib{

	protected $tags=array(
			'goodsCate'=>array('attr'=>'limit,order','close'=>1),
			'shopGoodsCate'=>array('attr'=>'limit,order','close'=>1),
		);

	//自定义nav标签
	public function _goodsCate($attr,$content){//$attr为上面自定义的属性，$content为标签之间的内容
		$attr=$this->parseXmlAttr($attr);

		$str=<<<str
<?php
	\$_goods_cate=M('goods_cate')->where(array('status'=>1))->order("{$attr['order']}")->limit("{$attr['limit']}")->select();
	foreach (\$_goods_cate as \$_goods_cate_k => \$_goods_cate_v):
		extract(\$_goods_cate_v);
		\$url=U('Depot/goodsList',array('cate_id'=>\$cate_id));
?>
str;

		$str.=$content;
		$str.='<?php endforeach; ?>';
		return $str;

	}

	//自定义nav标签
	public function _shopGoodsCate($attr,$content){//$attr为上面自定义的属性，$content为标签之间的内容
		$attr=$this->parseXmlAttr($attr);

		$str=<<<str
<?php
	\$_goods_cate=M('shop_goods_cate')->where(array('status'=>1))->order("{$attr['order']}")->limit("{$attr['limit']}")->select();
	foreach (\$_goods_cate as \$_goods_cate_k => \$_goods_cate_v):
		extract(\$_goods_cate_v);
		\$url=U('ShopDepot/goodsList',array('cate_id'=>\$cate_id));
?>
str;

		$str.=$content;
		$str.='<?php endforeach; ?>';
		return $str;

	}
}
?>