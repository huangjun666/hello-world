<?php
// echo '正在维护中。。。将在12点之前重启服务！';die();
	$rand=mt_rand ( 50*1000 ,  700*1000 );
	// usleep($rand);
// header('HTTP/1.1 404 Not Found');
//   header("status: 404 Not Found");
	define('APP_NAME', 'MAS');
	define("APP_PATH", '../MAS/');
	define("APP_SERVICE", '../Service/');
	define('APP_DEBUG', true);
	define('USER_LIMIT', false);//true,false,维护期间使用
	include '../ThinkPHP/ThinkPHP.php';
?>