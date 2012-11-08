<?php
	#admin header: intended to keep non admin users away from these pages ;-)
	#creategroup.php is an interface for inserting a group. Include insert group and adding users to it
	#Helena F Deus (helenadeus@gmail.com)
	ini_set('display_errors',0);
	if($_REQUEST['su3d']) {
		ini_set('display_errors',1);
	}
	if($_SERVER['HTTP_X_FORWARDED_HOST']!='') {
		$def = $_SERVER['HTTP_X_FORWARDED_HOST'];
	} else {
		$def = $_SERVER['HTTP_HOST'];
	}	
	if(file_exists('../config.inc.php')) {
		include('../config.inc.php');
	} else {
		Header('Location: http://'.$def.'/s3db/');
		exit;
	}
	
	$key = $_GET['key'];
	#Get the key, send it to check validity
	include_once('../core.header.php');

	if($key!='') {
		$args = '?key='.$_REQUEST['key'];
	}
	include '../webActions.php';

	$id = $_REQUEST['id'];
	if($key) {
		$user_id = get_entry('access_keys', 'account_id', 'key_id', $key, $db);
	} else {
		$user_id = $_SESSION['user']['account_id'];
	}
    $user_info = s3info('user', $user_id, $db);
	ereg('(.*)/(edituser.php)$', $_SERVER['PHP_SELF'], $script);
	if(!user_is_admin($user_id, $db)) {
		if(($script[2]!='edituser.php'  || $user_id!=$_REQUEST['id'])|| user_is_public($user_id, $db)) {
			Header('Location: '.$action['main']);
			exit;
		}
	}
?>