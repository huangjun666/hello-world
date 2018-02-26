
var web=(function(){

	var ok=true;//流程控制开关

	/*获得省级名*/
	var getProvince=function(obj){

		var obj=$(obj);

		$.ajax({
			url:'/Web/province',
			type:'post',
			dateType:'json',
			success:function(data,status){

				var objHTML='';

				//成功
				if( data.status == 1 ){

					var rs=data.rs;

					for( i=0; i<rs.length; i++ ){
						if( rs[i].id == provinceID ){
							objHTML+='<option value="'+rs[i].id+'" selected=true>'+rs[i].areaname+'</option>';
						}else{
							objHTML+='<option value="'+rs[i].id+'">'+rs[i].areaname+'</option>';
						}
						
					}
					obj.html(objHTML);
					var ProvinceID=obj.val();
					var selectIndex = document.getElementById("province").selectedIndex;
    				$('#province_name').val(document.getElementById("province").options[selectIndex].text);
					web.getCity('#city',ProvinceID);
				}

			}
		})
	}

	/*通过省级ID获得市级名*/
	var getCity=function(obj,ProvinceID){

		var obj=$(obj);

		$.ajax({
			url:'/Web/city',
			data:{
				provinceID:ProvinceID
			},
			type:'post',
			dateType:'json',
			success:function(data,status){

				var objHTML='';

				//成功
				if( data.status == 1 ){

					var rs=data.rs;

					for( i=0; i<rs.length; i++ ){
						if( cityID == rs[i].id ){
							objHTML+='<option value="'+rs[i].id+'" selected=true>'+rs[i].areaname+'</option>';
						}else{
							objHTML+='<option value="'+rs[i].id+'">'+rs[i].areaname+'</option>';
						}
					}

					obj.html(objHTML);
					var CityID=obj.val();
					var selectIndex = document.getElementById("city").selectedIndex;
      				$('#city_name').val(document.getElementById("city").options[selectIndex].text);
					web.getDistrict('#district',CityID);
				}

			}
		})
	}

	/*通过省级ID获得市级名*/
	var getDistrict=function(obj,CityID){

		var obj=$(obj);

		$.ajax({
			url:'/Web/district',
			data:{
				cityID:CityID
			},
			type:'post',
			dateType:'json',
			success:function(data,status){

				var objHTML='';

				//成功
				if( data.status == 1 ){

					var rs=data.rs;

					for( i=0; i<rs.length; i++ ){
						if( districtID == rs[i].id ){
							objHTML+='<option value="'+rs[i].id+'" selected=true>'+rs[i].areaname+'</option>';
						}else{
							objHTML+='<option value="'+rs[i].id+'">'+rs[i].areaname+'</option>';
						}
					}

					obj.html(objHTML);
					var selectIndex = document.getElementById("district").selectedIndex;
      				$('#district_name').val(document.getElementById("district").options[selectIndex].text);
				}

			}
		})
	}

	/**以下是订单地址加载**/
	/*获得省级名*/
	var getOrderProvince=function(obj){

		var obj=$(obj);

		$.ajax({
			url:'/Web/province',
			type:'post',
			dateType:'json',
			success:function(data,status){

				var objHTML='';

				//成功
				if( data.status == 1 ){

					var rs=data.rs;

					for( i=0; i<rs.length; i++ ){
						if( rs[i].id == order_provinceID ){
							objHTML+='<option value="'+rs[i].id+'" selected=true>'+rs[i].areaname+'</option>';
						}else{
							objHTML+='<option value="'+rs[i].id+'">'+rs[i].areaname+'</option>';
						}
						
					}
					obj.html(objHTML);
					var ProvinceID=obj.val();
					var selectIndex = document.getElementById("order_province").selectedIndex;
    				$('#order_province_name').val(document.getElementById("order_province").options[selectIndex].text);
					web.getOrderCity('#order_city',ProvinceID);
				}

			}
		})
	}

	/*通过省级ID获得市级名*/
	var getOrderCity=function(obj,ProvinceID){

		var obj=$(obj);

		$.ajax({
			url:'/Web/city',
			data:{
				provinceID:ProvinceID
			},
			type:'post',
			dateType:'json',
			success:function(data,status){

				var objHTML='';

				//成功
				if( data.status == 1 ){

					var rs=data.rs;

					for( i=0; i<rs.length; i++ ){
						if( order_cityID == rs[i].id ){
							objHTML+='<option value="'+rs[i].id+'" selected=true>'+rs[i].areaname+'</option>';
						}else{
							objHTML+='<option value="'+rs[i].id+'">'+rs[i].areaname+'</option>';
						}
					}

					obj.html(objHTML);
					var CityID=obj.val();
					var selectIndex = document.getElementById("order_city").selectedIndex;
      				$('#order_city_name').val(document.getElementById("order_city").options[selectIndex].text);
					web.getOrderDistrict('#order_district',CityID);
				}

			}
		})
	}

	/*通过省级ID获得市级名*/
	var getOrderDistrict=function(obj,CityID){

		var obj=$(obj);

		$.ajax({
			url:'/Web/district',
			data:{
				cityID:CityID
			},
			type:'post',
			dateType:'json',
			success:function(data,status){

				var objHTML='';

				//成功
				if( data.status == 1 ){

					var rs=data.rs;

					for( i=0; i<rs.length; i++ ){
						if( order_districtID == rs[i].id ){
							objHTML+='<option value="'+rs[i].id+'" selected=true>'+rs[i].areaname+'</option>';
						}else{
							objHTML+='<option value="'+rs[i].id+'">'+rs[i].areaname+'</option>';
						}
					}

					obj.html(objHTML);
					var selectIndex = document.getElementById("order_district").selectedIndex;
      				$('#order_district_name').val(document.getElementById("order_district").options[selectIndex].text);
				}

			}
		})
	}

	/*下啦菜单效果*/
	var menu=function(obj,box){
		var obj=$(obj);
		obj.mouseover(function(){
			$(this).find(box).show();
		})
		obj.mouseleave(function(){
			$(this).find(box).hide();
		})
	}

	/*左侧货品分类展开与收起效果*/
	var goodsCateShow=function(obj){

		var obj=$(obj);
		var goodsCateShow=web.getCookie("goodsCateShow")?parseInt(web.getCookie("goodsCateShow")):0;
		if(!goodsCateShow){
			obj.addClass('more');
			obj.siblings().slideUp();
			web.setCookie("goodsCateShow",1,0);
		}else{
			obj.removeClass('more');
			obj.siblings().slideDown();
			web.setCookie("goodsCateShow",0,0);
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

	/*货物入库出库弹窗*/
	var goodsDialog=function(goods_id,name,number,num_unit,action){

		// 弹窗对象
		var objH='';
			objH+='<div class="goodsDialog">';
			objH+='<div class="head">';

			//判断是出库还是入库
			if(action=='add'){
				objH+='<span>货品入库</span>';
			}else{
				objH+='<span>货品出库</span>';
			}
			
			objH+='</div>';
			objH+='<div class="name m">';
			objH+='<span>货物名称：</span>';
			objH+='<em>'+name+'</em>';
			objH+='</div>';
			objH+='<div class="name m1">';
			objH+='<span>货物编号：</span>';
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
			objH+='<div class="submit" onclick="web.goodsChange('+goods_id+',\''+action+'\')">确定</div>';
			objH+='<div class="submit close" onclick="web.fancyboxClose()">取消</div>';
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
	    web.forceNum('#goodsNum');
	}

	/*货物入库出库*/
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
				url:"/Depot/goodsChange",
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
					web.fancyboxClose();
				}
			})
		}else{
			alert('网络忙！')
		}
	}

	/*去仓库选货*/
	var goChooseGoods=function(order_id){
		location.href='/ChooseGoods/index/order_id/'+order_id;
	}

	/*关闭弹窗*/
	var fancyboxClose=function(){
		 $.fancybox.close();
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

	/*保存并提交*/
	var saveSubmit=function(){
		
		if(confirm("订单提交之后，将不可再编辑，\n确认要提交吗？")){
			$('#save').val(1);
			$('form').submit();
		}
	}

	/*订货单--保存一下*/
	var orderGoodsSave=function(order_goods_id,save){

		// 如果是提交订货单的话，提醒用户
		if(save){
			if(!confirm("订货单提交之后，将不可再编辑，\n确认要提交吗？")){
				return false;
			}
		}

		//收货地址
		var goods_address=$('#goods_address').val();
		if(goods_address==''){
			alert('收货地址，不可以为空！');
			$('#goods_address').focus();
			return false;
		}

		// 收货人姓名
		var goods_name=$('#goods_name').val();
		if(goods_name==''){
			alert('收货人姓名，不可以为空！');
			$('#goods_name').focus();
			return false;
		}

		// 收货人联系方式
		var goods_tel=$('#goods_tel').val();
        if( !/^1\d{10}$/.test(goods_tel) && !/^0\d{2,3}-?\d{7,8}$/.test(goods_tel) ){
            alert('请输入正确的电话号码！');
            $('#goods_tel').focus();
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
				url:'/OrderGoods/save',
				data:{
					order_goods_id:order_goods_id,
					goods_address:goods_address,
					goods_name:goods_name,
					goods_tel:goods_tel,
					remarks:remarks,
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
						$('#saveAndDeliver').val('保存并发货').removeClass('bg4');
					}


				}
			})
		}else{
			alert('网络忙！')
		}
	}

	/*加入订货单弹窗*/
	var chooseGoodsDialog=function(order_id,goods_id,name,number,num_unit,price,discount,price_unit,action){

		// 弹窗对象
		var objH='';
			objH+='<div class="goodsDialog">';
			objH+='<div class="head">';

			//判断是加入订货单还是退回
			if(action=='add'){
				objH+='<span>退回货品</span>';
			}else{
				objH+='<span>加入订货单</span>';
			}
			
			objH+='</div>';
			objH+='<div class="name m">';
			objH+='<span>货物名称：</span>';
			objH+='<em>'+name+'</em>';
			objH+='</div>';
			objH+='<div class="name m1">';
			objH+='<span>货物编号：</span>';
			objH+='<em>'+number+'</em>';
			objH+='</div>';

			//货物总数
			var goodsNum=parseInt($('#goodsNum_'+goods_id).text());

			//检查货物总数 <5 ，不可以再进行选货
			if( action=='del' && goodsNum < 5 ){
				// alert("货物数量小于5，不可以加入！");
				return false;
			}

			objH+='<div class="name m1">';
			objH+='<span>货物数量：</span>';
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
			objH+='<div class="submit" onclick="web.chooseGoods('+order_id+','+goods_id+',\''+action+'\')">确定</div>';
			objH+='<div class="submit close" onclick="web.fancyboxClose()">取消</div>';
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
	    web.forceNum('#goodsNum');

	    //根据数量，自动计算总价
	    $('#goodsNum').keyup(function(){
	    	var num=parseInt($(this).val());
	    	$(this).val(num);
	    	var totalPrice=parseFloat((num*price*discount*0.01).toFixed(3));
	    	$('#totalPrice').text(totalPrice+''+price_unit+'(折后价)');
	    })
	}

	/*加入订货单*/
	var chooseGoods=function(order_id,goods_id,action){

		var num=parseInt($('#goodsNum').val());//要加入订货单的数量
		var goodsNum=parseInt($('#goodsNum_'+goods_id).text());//货物总数

		// 检查num是否合法
		if( num<=0 || isNaN(num) ){
			alert('非法数据!');
			$('#goodsNum').focus();
			return false;
		}

		// 检查剩余货物总数 <5 ，不可以再进行选货
		if( action=='del' && (goodsNum-num) < 5 ){
			alert('加入之后，货物数量小于5，不可以加入！');
			$('#goodsNum').focus();
			return false;
		}

		if(ok){
			ok=false;

			// ajax请求
			$.ajax({
				url:"/ChooseGoods/chooseGoods",
				data:{
					num:num,
					goods_id:goods_id,
					order_id:order_id,
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
							var deliverNum=parseInt($('#deliverNum_'+goods_id).text());//已发货数量

							//页面显示变更
							if( newNum<=0 && deliverNum==0 ){//如果货品被退完，删除该行数据
								$('#goodsNum_'+goods_id).parent().remove();
							}else{
								$('#goodsNum_'+goods_id).text(newNum);
								$('#goodsMoney_'+goods_id).text(newMoney);
							}
							
							web.autoComputeGoodsTotal();

						}
						
					}

					//失败
					if(data.status==2){
						alert(data.msg);
					}

					// 关闭弹窗
					web.fancyboxClose();

					//计算当前订单的开销情况
					web.getOrderGoodsConsumption();
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
		$('#orderGoodsMoney').text(goodsMoney.toFixed(1)+'元');
		$('#deliverGoodsMoney').text(deliverMoney.toFixed(1)+'元');
	}

	/*查看发货单*/
	var goDeliverGoods=function(order_id){
		location.href='/DeliverGoods/edit/order_id/'+order_id;
	}

	/*查看订货单*/
	var goOrderGoods=function(order_id){
		location.href='/OrderGoods/edit/order_id/'+order_id;
	}

	/*订货单下发货单管理*/
	var goDeliverGoodsManage=function(order_id){
		location.href='/DeliverGoods/deliverGoodsManage/order_id/'+order_id;
	}

	/*加入发货单弹窗*/
	var deliverGoodsDialog=function(order_id,goods_id,name,number,num_unit,price,discount,price_unit,action){

		// 弹窗对象
		var objH='';
			objH+='<div class="goodsDialog">';
			objH+='<div class="head">';

			//判断是加入发货单还是退回
			if(action=='add'){
				objH+='<span>退回至订货单</span>';
			}else{
				objH+='<span>加入发货单</span>';
			}
			
			objH+='</div>';
			objH+='<div class="name m">';
			objH+='<span>货物名称：</span>';
			objH+='<em>'+name+'</em>';
			objH+='</div>';
			objH+='<div class="name m1">';
			objH+='<span>货物编号：</span>';
			objH+='<em>'+number+'</em>';
			objH+='</div>';
			objH+='<div class="num m1">';
			//判断是加入发货单还是退回
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
			objH+='<div class="submit" onclick="web.deliverGoods('+order_id+','+goods_id+',\''+action+'\')">确定</div>';
			objH+='<div class="submit close" onclick="web.fancyboxClose()">取消</div>';
			objH+='</div>';
			objH+='</div>';

		//弹窗参数
		var opts={
        'centerOnScroll':true,
        'autoHeight':true,
        'minHeight':30,
        'topRatio':0.5,
        'leftRatio':0.45,
        'autoWidth':true
	    };

	    // 启动弹窗
	    $.fancybox(objH,opts);
	    $('#goodsNum').focus();
	    web.forceNum('#goodsNum');

	    //根据数量，自动计算总价
	    $('#goodsNum').keyup(function(){
	    	var num=parseInt($(this).val());
	    	$(this).val(num);
	    	var totalPrice=parseFloat((num*price*discount*0.01).toFixed(3));
	    	$('#totalPrice').text(totalPrice+''+price_unit+"(折后价)");
	    })
	}

	/*加入发货单*/
	var deliverGoods=function(order_id,goods_id,action){


		var num=parseInt($('#goodsNum').val());

		// 检查num是否合法
		if( num<=0 || isNaN(num) ){
			alert('非法数据!');
			$('#goodsNum').focus();
			return false;
		}
		if(ok){
			ok=false;

			// ajax请求
			$.ajax({
				url:"/DeliverGoods/deliverGoods",
				data:{
					num:num,
					goods_id:goods_id,
					order_id:order_id,
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

							var goodsNum=parseInt($('#goodsNum_'+goods_id).text());
							var deliverNum=parseInt($('#deliverNum_'+goods_id).text());
							var goodsNum=goodsNum-num;
							var deliverNum=deliverNum+num;
							$('#goodsNum_'+goods_id).text(goodsNum);
							$('#deliverNum_'+goods_id).text(deliverNum);

						}else if(action=='add'){//退回操作

							var oldNum=parseInt($('#goodsNum_'+goods_id).text());//旧数量
							var price=parseFloat($('#goodsPrice_'+goods_id).text());//价格
							var discount=parseFloat($('#goodsDiscount_'+goods_id).text());//折扣
							var oldMoney=parseFloat($('#goodsMoney_'+goods_id).text());//旧总额
							var newNum=oldNum+num;//新的数量
							var newMoney=(oldMoney+(num*price*discount*0.01)).toFixed(1);//新的总额

							//页面显示变更
							if( newNum<=0 ){//如果货品被退完，删除该行数据
								$('#goodsNum_'+goods_id).parent().remove();
							}else{
								$('#goodsNum_'+goods_id).text(newNum);
								$('#goodsMoney_'+goods_id).text(newMoney);
							}
							

						}
						
					}

					//失败
					if(data.status==2){
						alert(data.msg);
					}

					//计算总量
					web.autoComputeGoodsTotal();

					// 关闭弹窗
					web.fancyboxClose();
				}
			})
		}else{
			alert('网络忙！')
		}
	}

	/*批量加入发货单*/
	var batchDeliverGoods=function(order_id){
		// num:num,
		// goods_id:goods_id,
		// order_id:order_id,
		// action:action
		var requestData=[];//用于批量请求的数据
		var orderGoodsList=$('.orderGoodsList');
		// alert(orderGoodsList.size());
		// alert(requestData.length);
		//循环处理每一行
		var i=0;
		orderGoodsList.each(function(n){

			var tds=$(this).children();
			var goodsNum=parseInt(tds.eq(6).text());//产品数量

			//如果产品数量为正，则加该条产品加入到请求列表
			if( goodsNum > 0 ){

				var goods_id=tds.eq(4).attr('id').substr(11);//产品id

				requestData[i]={
					num:goodsNum,
					goods_id:goods_id,
					order_id:order_id,
					action:'del'
				};
				i++;
			}
		})

		//开始批量请求
		if(ok){
			$('#batchDeliverGoods').text('处理中..');
			web.batchDeliverGoodsRequest(requestData,0);
		}else{
			alert('网络忙！');
		}


	}

	/*批量加入发货单*/
	var batchDeliverGoodsRequest=function(requestData,i){

		//如果数据请求完毕，则停止递归
		if( i >= requestData.length ){
			ok=true;
			alert("处理完毕！");
			$('#batchDeliverGoods').text('一键发货');
			web.autoComputeGoodsTotal();//自动计算货品总数和总价
			return false;
		}

		// ajax请求
		$.ajax({
			url:"/DeliverGoods/deliverGoods",
			data:requestData[i],
			type:'post',
			dateType:'json',
			success:function(data,status){

				//成功
				if(data.status==1){
					var num=data.num;

					var goodsNum=parseInt($('#goodsNum_'+requestData[i].goods_id).text());
					var deliverNum=parseInt($('#deliverNum_'+requestData[i].goods_id).text());
					var goodsNum=goodsNum-num;
					var deliverNum=deliverNum+num;
					$('#goodsNum_'+requestData[i].goods_id).text(goodsNum);
					$('#deliverNum_'+requestData[i].goods_id).text(deliverNum);

					//递归请求
					i++;
					web.batchDeliverGoodsRequest(requestData,i);
				}

				//失败
				if(data.status==2){
					alert(data.msg);
					ok=true;
					$('#batchDeliverGoods').text('一键发货');
					return false;
				}

			}
		})
	}



	/*发货单--保存一下*/
	var deliverGoodsSave=function(deliver_id,save){

		// 如果是提交订货单的话，提醒用户
		if(save){
			if(!confirm("发货单发货之后，将不可再编辑，\n确认要提交吗？")){
				return false;
			}
		}

		//收货地址
		var goods_address=$('#goods_address').val();
		if(goods_address==''){
			alert('收货地址，不可以为空！');
			$('#goods_address').focus();
			return false;
		}

		// 收货人姓名
		var goods_name=$('#goods_name').val();
		if(goods_name==''){
			alert('收货人姓名，不可以为空！');
			$('#goods_name').focus();
			return false;
		}

		// 收货人联系方式
		var goods_tel=$('#goods_tel').val();
        if( !/^1\d{10}$/.test(goods_tel) && !/^0\d{2,3}-?\d{7,8}$/.test(goods_tel) ){
            alert('请输入正确的电话号码！');
            $('#goods_tel').focus();
            return false;
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
			$('#saveAndDeliver').val('发货中').addClass('bg4');
		}
		if(ok){
			ok=false;
			$.ajax({
				url:'/DeliverGoods/save',
				data:{
					deliver_id:deliver_id,
					deliver_address:goods_address,
					deliver_name:goods_name,
					deliver_tel:goods_tel,
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
						if(data.msg=='发货成功！'){
							location.href='/DeliverGoods/view/deliver_id/'+deliver_id;
						}
					}
					//失败
					if(data.status==2){
						alert(data.msg);
						$('#saveAndDeliver').val('保存并发货').removeClass('bg4');
					}
				}
			})
		}else{
			alert('网络忙！')
		}
	}

	/*查看物流信息*/
	var showOK=true;
	var showLogistics=function(deliver_id){

		if(showOK){
			showOK=false;
			$('#logisticsInfo').append('<tr class="load"><td>正在加载......<td></tr>');
			$.ajax({
				url:'/DeliverGoods/showLogistics',
				data:{
					deliver_id:deliver_id
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
	var receipt=function(deliver_id){

		if(ok){
			ok=false;
			$.ajax({
				url:'/DeliverGoods/receipt',
				data:{
					deliver_id:deliver_id
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

	/*下载发货单*/
	var downloadDeliver=function(deliver_id){

		// 如果发货单处于未发货状态，验证货物是否缺少
		if(ok){
			ok=false;
			$.ajax({
				url:'/DeliverGoods/downloadCheck',
				data:{
					deliver_id:deliver_id
				},
				type:'post',
				dateType:'json',
				success:function(data,status){
					ok=true;
					//成功
					if(data.status==1){
						location.href="/DeliverGoods/download/deliver_id/"+deliver_id;
					}
					//失败
					if(data.status==2){
						var list=data.list;
						var msg=data.msg;
						for (var i = 0; i < list.length; i++) {
							msg+="\n"+list[i].name+",缺货："+list[i].num+"件";
						};
						alert(msg);
					}
				}
			})
		}else{
			alert('网络忙！')
		}

	}

	/*下载订货单*/
	var downloadOrderGoods=function(order_goods_id){
		location.href="/OrderGoods/download/order_goods_id/"+order_goods_id;
	}

	/* 结束订货单*/
	var endOrderGoods=function(order_id){

		// 如果是完结订货单的话，提醒用户
		if(!confirm("订货单【完结】之后，将不可再编辑，\n确认要【完结】吗？")){
			return false;
		}

		// 请求
		if(ok){
			ok=false;
			$.ajax({
				url:'/OrderGoods/end',
				data:{
					order_id:order_id
				},
				type:'post',
				dateType:'json',
				success:function(data,status){
					ok=true;
					//成功
					if(data.status==1){
						location.href="/OrderGoods/view/order_id/"+order_id;
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

	/*商品停售*/
	var goodsStop=function(obj,goods_id,goods_status){

		var obj=$(obj);

		// 如果是停售的话，提醒用户
		if( goods_status==1 ){
			if( !confirm("货品【停售】之后，将不可再交易，\n确认要【停售】吗？") ){
				return false;
			}
		}

		// 如果是恢复的话，提醒用户
		if( goods_status==3 ){
			if( !confirm("货品【恢复】之后，将变的可交易，\n确认要【恢复】吗？") ){
				return false;
			}
		}

		// 请求
		if(ok){
			ok=false;
			$.ajax({
				url:'/Depot/goodsStop',
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
							obj.html('停售').attr('onclick','web.goodsStop(this,'+goods_id+','+goodsStatus+')');
						}
						if( goodsStatus==3 ){
							obj.html('<span class="red">恢复</span>').attr('onclick','web.goodsStop(this,'+goods_id+','+goodsStatus+')');
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

	/*获取选货页面当前订货单的总额和已花费的金额*/ 
	var getOrderGoodsConsumption=function(){

		$.ajax({
			url:'/ChooseGoods/getOrderGoodsConsumption',
			type:'post',
			dateType:'json',
			success:function(data,status){

				$('#money_count_total').text(data.totalMoney);
				$('#money_count_used').text(data.usedMoney);
			}
		})

	}

	/*删除公告*/ 
	var delNews=function(news_id){

		//提醒用户
		if(!confirm("公告【删除】之后，将不可回复，\n确认要【删除】吗？")){
			return false;
		}

		location.href="/News/del/news_id/"+news_id;

	}

	/*合同照片上传*/
	var contractUpload=function(obj,cover,file){
		// 请求
		if(ok){
			ok=false;
	        $.ajaxFileUpload({
	            url:'/Order/contractUpload',
	            secureuri:false,
	            fileElementId:file,
	            dataType: 'json',
	            data:{name:'logan', id:'id'},
	            success: function (data)
	            {
	            	ok=true;
	            	//成功
	            	if(data.status==1){
	            		var img='<img src="'+data.imgSrc+'" />';
		                $(obj).html(img);
		                $(cover).val(data.imgSrc);
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

    /*订单打回弹窗*/
	var backOrderDialog=function(order_id){

		// 弹窗对象
		var objH='';
		objH+='<div class="back_msg">';
		objH+='<h1 class="h1">请输入打回理由：</h1>';
		objH+='<div class="msg">';
		objH+='<textarea name="" id="msg" placeholder="请输入打回理由"></textarea>';
		objH+='</div>';
		objH+='<div class="but">';
		objH+='<span class="bg" onclick="web.backOrder('+order_id+')">确定</span>';
		objH+='<span onclick="web.fancyboxClose()">取消</span>';
		objH+='</div>';
		objH+='</div>';

		//弹窗参数
		var opts={
        'centerOnScroll':true,
        'autoHeight':true,
        'minHeight':30,
        'topRatio':0.5,
        'leftRatio':0.45,
        'autoWidth':true
	    };

	    // 启动弹窗
	    $.fancybox(objH,opts);
	    $('#msg').focus();
	}

	/*订单打回*/
	var backOrder=function(order_id){

		// 检查打回理由是否为空
		var back_msg=$('#msg').val();
		if(back_msg==''){
			alert("必须要填写打回理由！");
			$('#msg').focus();
			return false;
		}

		// 请求
		if(ok){
			ok=false;
			$.ajax({
				url:'/Order/back',
				data:{
					order_id:order_id,
					back_msg:back_msg
				},
				type:'post',
				dateType:'json',
				success:function(data,status){
					ok=true;
					//成功
					if(data.status==1){
						location.href='/Order/edit/order_id/'+order_id;
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
		getProvince:getProvince,
		getCity:getCity,
		getDistrict:getDistrict,
		getOrderProvince:getOrderProvince,
		getOrderCity:getOrderCity,
		getOrderDistrict:getOrderDistrict,
		menu:menu,
		goodsCateShow:goodsCateShow,
		setCookie:setCookie,
		getCookie:getCookie,
		goodsDialog:goodsDialog,
		fancyboxClose:fancyboxClose,
		goodsChange:goodsChange,
		forceNum:forceNum,
		goChooseGoods:goChooseGoods,
		saveSubmit:saveSubmit,
		orderGoodsSave:orderGoodsSave,
		chooseGoodsDialog:chooseGoodsDialog,
		chooseGoods:chooseGoods,
		autoComputeGoodsTotal:autoComputeGoodsTotal,
		deliverGoodsDialog:deliverGoodsDialog,
		deliverGoods:deliverGoods,
		goDeliverGoods:goDeliverGoods,
		goOrderGoods:goOrderGoods,
		deliverGoodsSave:deliverGoodsSave,
		goDeliverGoodsManage:goDeliverGoodsManage,
		showLogistics:showLogistics,
		receipt:receipt,
		downloadDeliver:downloadDeliver,
		downloadOrderGoods:downloadOrderGoods,
		endOrderGoods:endOrderGoods,
		goodsStop:goodsStop,
		batchDeliverGoods:batchDeliverGoods,
		batchDeliverGoodsRequest:batchDeliverGoodsRequest,
		getOrderGoodsConsumption:getOrderGoodsConsumption,
		delNews:delNews,
		contractUpload:contractUpload,
		backOrderDialog:backOrderDialog,
		backOrder:backOrder
	}

})()
