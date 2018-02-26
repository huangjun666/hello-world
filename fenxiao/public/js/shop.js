
var shop=(function(){

	var ok=true;//流程控制开关

	/*左侧商品分类展开与收起效果*/
	var goodsCateShow=function(obj){

		var obj=$(obj);
		var shopGoodsCateShow=shop.getCookie("shopGoodsCateShow")?parseInt(shop.getCookie("shopGoodsCateShow")):0;
		if(!shopGoodsCateShow){
			obj.addClass('more');
			obj.siblings().slideUp();
			shop.setCookie("shopGoodsCateShow",1,0);
		}else{
			obj.removeClass('more');
			obj.siblings().slideDown();
			shop.setCookie("shopGoodsCateShow",0,0);
		}
		
	}

	/*商品入库出库弹窗*/
	var goodsDialog=function(goods_id,name,number,num_unit,action){

		// 弹窗对象
		var objH='';
			objH+='<div class="goodsDialog">';
			objH+='<div class="head">';

			//判断是出库还是入库
			if(action=='add'){
				objH+='<span>商品入库</span>';
			}else{
				objH+='<span>商品出库</span>';
			}
			
			objH+='</div>';
			objH+='<div class="name m">';
			objH+='<span>商品名称：</span>';
			objH+='<em>'+name+'</em>';
			objH+='</div>';
			objH+='<div class="name m1">';
			objH+='<span>商品编号：</span>';
			objH+='<em>'+number+'</em>';
			objH+='</div>';
			objH+='<div class="num m1">';
			//判断是出库还是入库
			if(action=='add'){
				objH+='<span>入库数量：</span>';
			}else{
				objH+='<span>出库数量：</span>';
			}
			objH+='<input type="text" id="goodsNum" value="" placeholder="数量">'+'&nbsp;'+num_unit;
			objH+='</div>';
			objH+='<div class="but">';
			objH+='<div class="submit" onclick="shop.goodsChange('+goods_id+',\''+action+'\')">确定</div>';
			objH+='<div class="submit close" onclick="shop.fancyboxClose()">取消</div>';
			objH+='</div>';
			objH+='</div>';

		//弹窗参数
		var opts={
        'centerOnScroll':true,
        'autoHeight':true,
        'minHeight':30,
        'topRatio':0.5,
        'leftRatio':0.6,
        'autoWidth':true
	    };

	    // 启动弹窗
	    $.fancybox(objH,opts);
	    $('#goodsNum').focus();
	    shop.forceNum('#goodsNum');
	}

	/*商品入库出库*/
	var goodsChange=function(goods_id,action){
		// alert($('#goodsNum').val());
		// alert($(obj).siblings('.num').text());
		var num=parseInt($('#goodsNum').val());

		// 检查num是否合法
		if( num<=0 ){
			alert('非法数据!');
			$('#goodsNum').focus();
			return false;
		}

		if(ok){
			ok=false;

			// ajax请求
			$.ajax({
				url:"/ShopDepot/goodsChange",
				data:{
					num:num,
					goods_id:goods_id,
					action:action
				},
				type:'post',
				dateType:'json',
				success:function(data,status){
					ok=true;
					//成功
					if(data.status==1){
						var num=data.num;
						var oldNum=parseInt($('#goodsNum_'+goods_id).text());
						var newNum=oldNum+num;
						$('#goodsNum_'+goods_id).text(newNum);
					}

					//失败
					if(data.status==2){
						alert(data.msg);
					}

					// 关闭弹窗
					shop.fancyboxClose();
				}
			})
		}else{
			alert('网络忙！')
		}
	}

	/*强制输入数字*/ 
	var forceNum=function(obj){
		//强制数字
	    $(obj).blur(function(){
	        var val=$(this).val();
	        if( isNaN(val) ){
	          $(this).val(0);
	        }
	    });
	}

	/*商品停售*/
	var goodsStop=function(obj,goods_id,goods_status){

		var obj=$(obj);

		// 如果是停售的话，提醒用户
		if( goods_status==1 ){
			if( !confirm("商品【停售】之后，将不可再交易，\n确认要【停售】吗？") ){
				return false;
			}
		}

		// 如果是恢复的话，提醒用户
		if( goods_status==3 ){
			if( !confirm("商品【恢复】之后，将变的可交易，\n确认要【恢复】吗？") ){
				return false;
			}
		}

		// 请求
		if(ok){
			ok=false;
			$.ajax({
				url:'/ShopDepot/goodsStop',
				data:{
					goods_id:goods_id
				},
				type:'post',
				dateType:'json',
				success:function(data,status){
					ok=true;
					//成功
					if(data.status==1){
						var goodsStatus=data.goodsStatus;
						if( goodsStatus==1 ){
							obj.html('停售').attr({'onclick':'shop.goodsStop(this,'+goods_id+','+goodsStatus+')',"class":"but stop"});
						}
						if( goodsStatus==3 ){
							obj.html('恢复').attr({'onclick':'shop.goodsStop(this,'+goods_id+','+goodsStatus+')',"class":"but start"});
						}
					}
					//失败
					if(data.status==2){
						alert(data.msg);
					}
				}
			})
		}else{
			alert('网络忙！')
		}
		
	}

	/*商品封面图上传*/
	var goodsCoverUpload=function(){
		// 请求
		if(ok){
			ok=false;
	        $.ajaxFileUpload({
	            url:'/ShopDepot/goodsCoverUpload',
	            secureuri:false,
	            fileElementId:'picfile',
	            dataType: 'json',
	            data:{name:'logan', id:'id'},
	            success: function (data)
	            {
	            	ok=true;
	            	//成功
	            	if(data.status==1){
	            		var img='<img src="'+data.imgSrc+'" width="240" height="186" />';
		                $('#shop_goods_cover').html(img);
		                $('#cover').val(data.imgSrc);
	            	}

	            	//失败
	            	if(data.status==2){
	            		alert(data.msg);
	            	}
	                
	            }
	        })
        }else{
			alert('网络忙！')
		}
    }

	/*设置cookie*/
	var setCookie=function(name,value,expiresHours){

		var cookieString=name+"="+escape(value)+";path=/";//新的cookie 

		//判断是否设置过期时间 
		if(expiresHours>0){ 
		var date=new Date(); 
		date.setTime(date.getTime+expiresHours*3600*1000); 
		cookieString=cookieString+"; expires="+date.toGMTString(); 
		} 

		//设置cookie
		document.cookie=cookieString;

	}

	/*获得cookie*/
	var getCookie=function(name){

		var strCookie=document.cookie; //所有cookie
		var arrCookie=strCookie.split("; "); //转换为数组

		//循环所有cookie，返回指定的值
		for(var i=0;i<arrCookie.length;i++){ 
			var arr=arrCookie[i].split("="); 
			if(arr[0]==name)return arr[1]; 
		} 
		return ""; 
	}

	/*关闭弹窗*/
	var fancyboxClose=function(){
		 $.fancybox.close();
	}

	/*加入购物车弹窗*/
	var chooseGoodsDialog=function(shop_order_id,goods_id,name,number,num_unit,price,discount,price_unit,action){

		// 弹窗对象
		var objH='';
			objH+='<div class="goodsDialog">';
			objH+='<div class="head">';

			//判断是加入订货单还是退回
			if(action=='add'){
				objH+='<span>退回商品</span>';
			}else{
				objH+='<span>加入购物车</span>';
			}
			
			objH+='</div>';
			objH+='<div class="name m">';
			objH+='<span>商品名称：</span>';
			objH+='<em>'+name+'</em>';
			objH+='</div>';
			objH+='<div class="name m1">';
			objH+='<span>商品编号：</span>';
			objH+='<em>'+number+'</em>';
			objH+='</div>';

			//商品总数
			var goodsNum=parseInt($('#goodsNum_'+goods_id).text());

			//检查商品总数 <5 ，不可以再进行选货
			if( action=='del' && goodsNum < 1 ){
				alert("商品数量不足！");
				return false;
			}

			objH+='<div class="name m1">';
			objH+='<span>商品数量：</span>';
			objH+='<em>'+goodsNum+num_unit+'</em>';
			objH+='</div>';


			objH+='<div class="num m1">';
			//判断是出库还是入库
			if(action=='add'){
				objH+='<span>退回数量：</span>';
			}else{
				objH+='<span>加入数量：</span>';
			}
			objH+='<input type="text" id="goodsNum" value="" placeholder="数量">'+'&nbsp;'+num_unit;
			objH+='</div>';
			objH+='<div class="name m1">';
			objH+='<span>总价：</span>';
			objH+='<em id="totalPrice">0</em>';
			objH+='</div>';
			objH+='<div class="but">';
			objH+='<div class="submit" onclick="shop.chooseGoods('+shop_order_id+','+goods_id+',\''+action+'\')">确定</div>';
			objH+='<div class="submit close" onclick="shop.fancyboxClose()">取消</div>';
			objH+='</div>';
			objH+='</div>';

		//弹窗参数
		var leftRatio=action=='add'?0.45:0.6;
		var opts={
        'centerOnScroll':true,
        'autoHeight':true,
        'minHeight':30,
        'topRatio':0.5,
        'leftRatio':leftRatio,
        'autoWidth':true
	    };

	    // 启动弹窗
	    $.fancybox(objH,opts);
	    $('#goodsNum').focus();
	    shop.forceNum('#goodsNum');

	    //根据数量，自动计算总价
	    $('#goodsNum').keyup(function(){
	    	var num=parseInt($(this).val());
	    	$(this).val(num);
	    	var totalPrice=parseFloat((num*price*discount*0.01).toFixed(3));
	    	$('#totalPrice').text(totalPrice+''+price_unit+'(折后价)');
	    })
	}

	/*加入购物车*/
	var chooseGoods=function(shop_order_id,goods_id,action){

		var num=parseInt($('#goodsNum').val());//要加入订货单的数量
		var goodsNum=parseInt($('#goodsNum_'+goods_id).text());//货物总数

		// 检查num是否合法
		if( num<=0 || isNaN(num) ){
			alert('非法数据!');
			$('#goodsNum').focus();
			return false;
		}

		// 检查剩余货物总数 <5 ，不可以再进行选货
		if( action=='del' && (goodsNum-num) < 0 ){
			alert('商品数量不足，加入购物车失败！');
			$('#goodsNum').focus();
			return false;
		}

		if(ok){
			ok=false;

			// ajax请求
			$.ajax({
				url:"/Shop/chooseGoods",
				data:{
					num:num,
					goods_id:goods_id,
					shop_order_id:shop_order_id,
					action:action
				},
				type:'post',
				dateType:'json',
				success:function(data,status){
					ok=true;
					//成功
					if(data.status==1){
						var num=data.num;
						if(action=='del'){//加入订货单操作

							var oldNum=parseInt($('#goodsNum_'+goods_id).text());
							var newNum=oldNum+num;
							$('#goodsNum_'+goods_id).text(newNum);

						}else if(action=='add'){//退回操作

							var oldNum=parseInt($('#goodsNum_'+goods_id).text());//旧数量
							var price=parseFloat($('#goodsPrice_'+goods_id).text());//价格
							var oldMoney=parseFloat($('#goodsMoney_'+goods_id).text());//旧总额
							var discount=parseFloat($('#goodsDiscount_'+goods_id).text())*0.01;//折扣
							// alert(discount)
							var newNum=oldNum-num;//新的数量
							var newMoney=(oldMoney-(num*price*discount)).toFixed(1);//新的总额

							//页面显示变更
							if( newNum<=0 ){//如果货品被退完，删除该行数据
								$('#goodsNum_'+goods_id).parent().remove();
							}else{
								$('#goodsNum_'+goods_id).text(newNum);
								$('#goodsMoney_'+goods_id).text(newMoney);
							}
							
							shop.autoComputeGoodsTotal();

						}
						
					}

					//失败
					if(data.status==2){
						alert(data.msg);
					}

					// 关闭弹窗
					shop.fancyboxClose();

					//计算当前订单的开销情况
					// shop.getOrderGoodsConsumption();
				}
			})
		}else{
			alert('网络忙！')
		}
	}

	/*自动计算货品总数和总价*/
	var autoComputeGoodsTotal=function(){
		var numObj=$('.orderGoodsNum');
		var deliverObj=$('.deliverGoodsNum');
		var moneyObj=$('.orderGoodsMoney');
		var goodsNum=0,goodsMoney=0,deliverMoney=0;

		//统计数量
		numObj.each(function(){//货品数量
			goodsNum+=parseInt($(this).text());
		})
		deliverObj.each(function(){//已发货数量和金额
			// 数量
			var num=parseInt($(this).text());
			goodsNum+=num;

			// 金额
			var price=parseFloat($(this).siblings('.goodsPrice').text());
			// 折扣
			var discount=parseFloat($(this).siblings('.goodsDiscount').text());

			deliverMoney+=(num*price*discount*0.01);

		})
		// 统计金额
		moneyObj.each(function(){
			goodsMoney+=parseFloat($(this).text());
		})

		//显示数量和总额
		$('#orderGoodsNum').text(goodsNum+'件');
		$('#orderGoodsMoney').text(goodsMoney.toFixed(0)+'元');
		$('#orderGoodsMoney1').text(goodsMoney.toFixed(0)+'元');
		$('#deliverGoodsMoney').text(deliverMoney.toFixed(0)+'元');
	}

	/*去商城选货*/
	var goChooseGoods=function(shop_order_id){
		location.href='/Shop/index/shop_order_id/'+shop_order_id;
	}

	/*订单--保存一下*/
	var orderGoodsSave=function(shop_order_id,save){

		// 如果是提交订单的话，提醒用户
		if(save==1){
			if(!confirm("订单提交之后，将不可再编辑，\n确认要提交吗？")){
				return false;
			}
		}

		if(save==2){
			if(!confirm("订单发货之后，将不可再编辑，\n确认要提交吗？")){
				return false;
			}
		}

		//物流公司
        var logistics_company=$('#logistics_company').val();
        if(save && logistics_company==0){
        	alert('请输入有效的物流公司！');
            $('#logistics_company').focus();
            return false;
        }

		//物流运单号
        var waybill_number=$('#waybill_number').val();
        if(save && waybill_number==''){
        	alert('请输入有效的物流运单号！');
            $('#waybill_number').focus();
            return false;
        }

		// 备注
		var remarks=$('#remarks').val();

		if(save){
			$('#saveAndDeliver').val('提交中').addClass('bg4');
		}
		if(ok){
			ok=false;
			$.ajax({
				url:'/ShopCart/save',
				data:{
					shop_order_id:shop_order_id,
					remarks:remarks,
					logistics_company:logistics_company,
					waybill_number:waybill_number,
					save:save
				},
				type:'post',
				dateType:'json',
				success:function(data,status){
					ok=true;
					//成功
					if(data.status==1){
						alert(data.msg);
						if(data.msg=='提交成功！'){
							location.reload();
						}
					}
					//失败
					if(data.status==2){
						alert(data.msg);

						if(save==1){
							$('#saveAndDeliver').val('保存并提交').removeClass('bg4');
						}

						if(save==2){
							$('#saveAndDeliver').val('保存并发货').removeClass('bg4');
						}
						
					}

				}
			})
		}else{
			alert('网络忙！')
		}
	}

	/* 结束订货单*/
	var endOrderGoods=function(shop_order_id){

		// 如果是完结订货单的话，提醒用户
		if(!confirm("订货单【完结】之后，将不可再编辑，\n确认要【完结】吗？")){
			return false;
		}

		// 请求
		if(ok){
			ok=false;
			$.ajax({
				url:'/ShopCart/end',
				data:{
					shop_order_id:shop_order_id
				},
				type:'post',
				dateType:'json',
				success:function(data,status){
					ok=true;
					//成功
					if(data.status==1){
						location.href="/ShopCart/view/shop_order_id/"+shop_order_id;
					}
					//失败
					if(data.status==2){
						alert(data.msg);
					}
				}
			})
		}else{
			alert('网络忙！')
		}
	}

	/*下载订单*/
	var downloadOrderGoods=function(shop_order_id){
		location.href="/ShopCart/download/shop_order_id/"+shop_order_id;
	}

	/*查看物流信息*/
	var showOK=true;
	var showLogistics=function(shop_order_id){

		if(showOK){
			showOK=false;
			$('#logisticsInfo').append('<tr class="load"><td>正在加载......<td></tr>');
			$.ajax({
				url:'/ShopCart/showLogistics',
				data:{
					shop_order_id:shop_order_id
				},
				type:'post',
				dateType:'json',
				success:function(data,status){
					//成功
					if(data.status==1){
						var list=data.list;
						var html='';
						$('#logisticsInfo').find('.info').remove();
						for (var i = 0; i < list.length; i++) {
							html+='<tr class="info">';
							html+='<td width=140 align="right"><b>'+list[i].ymd+'</b>&nbsp;&nbsp;&nbsp;&nbsp; '+list[i].time+'</td>';
							html+='<td align="left">'+list[i].status+'</td>';
							html+='</tr>';
						}
						// 加到物流信息表
						$('#logisticsInfo').append(html);
						$('#logisticsInfo').find('.load').remove();
					}
					//失败
					if(data.status==2){
						alert(data.msg);
					}
					showOK=true;
				}
			})
		}else{
			alert('网络忙！')
		}
	}

	/*确认收货*/
	var receipt=function(shop_order_id){

		if(ok){
			ok=false;
			$.ajax({
				url:'/ShopCart/receipt',
				data:{
					shop_order_id:shop_order_id
				},
				type:'post',
				dateType:'json',
				success:function(data,status){
					ok=true;
					//成功
					if(data.status==1){
						alert(data.msg);
						$('#receipt').removeClass('bg2').addClass('bg4').attr("disabled","disabled").val('已收货');
					}
					//失败
					if(data.status==2){
						alert(data.msg);
					}
				}
			})
		}else{
			alert('网络忙！')
		}
	}


	//返回
	return {
		goodsCateShow:goodsCateShow,
		setCookie:setCookie,
		getCookie:getCookie,
		fancyboxClose:fancyboxClose,
		goodsChange:goodsChange,
		goodsDialog:goodsDialog,
		forceNum:forceNum,
		goodsStop:goodsStop,
		goodsCoverUpload:goodsCoverUpload,
		chooseGoodsDialog:chooseGoodsDialog,
		chooseGoods:chooseGoods,
		autoComputeGoodsTotal:autoComputeGoodsTotal,
		goChooseGoods:goChooseGoods,
		orderGoodsSave:orderGoodsSave,
		endOrderGoods:endOrderGoods,
		downloadOrderGoods:downloadOrderGoods,
		showLogistics:showLogistics,
		receipt:receipt
	}

})()
