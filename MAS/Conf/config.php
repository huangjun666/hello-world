<?php
return array(

	//点语法默认解析
	'TMPL_VAR_IDENTIFY' =>'array',

	'LOAD_EXT_CONFIG' => 'user,db', // 加载扩展配置文件
	// 'SHOW_PAGE_TRACE'=>true,//页面报错

	//伪静态后缀名
	'URL_HTML_SUFFIX'=>'',

	//自定义标签配置
	'APP_AUTOLOAD_PATH'=>'@.TagLib',// @表示当前项目
	'TAGLIB_BUILD_IN'=>'Cx,Mac',
	
	//获取系统变量时，默认执行的函数
	'DEFAULT_FILTER'=>'htmlspecialchars,strip_tags,trim',

	//配置public路径
	'TMPL_PARSE_STRING'=>array(
		'__PUBLIC__'=>__ROOT__.'/public/'
		),

	//配置URL路由
	'URL_MODEL'=>2,//URL的方式，URL重写！
	// 'URL_ROUTER_ON'=>true,//开启路由
	// 'URL_ROUTE_RULES'=>array(//定义路由规则
	// 	'/^c_(\d+)$/'=>'Index/List',
	// 	),

	//周结密令
	'week_pwd'=>'ach@langsha888',
	//月结密令
	'month_pwd'=>'ach@langsha888',
	//辅导奖密令
	'coach_pwd'=>'ach@langsha888',
	//团队奖密令
	'team_pwd'=>'ach@langsha888',
);
?>