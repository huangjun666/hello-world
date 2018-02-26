$(function(){
	//商品图片列表---鼠标经过，显示工具栏
	$('.shop_goods_list ul li').mouseenter(function(){
		$(this).find('.name').slideUp('fast');
	})
	$('.shop_goods_list ul li').mouseleave(function(){
		$(this).find('.name').slideDown();
	})
})