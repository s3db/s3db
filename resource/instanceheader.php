<?php
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
	
	#just to know where we are...
	$thisScript = end(explode('/', $_SERVER['SCRIPT_FILENAME'])).'?'.$_SERVER['argv'][0];
	$key = $_GET['key'];

	#Get the key, send it to check validity
	include_once('../core.header.php');
	if($key) {
		$user_id = get_entry('access_keys', 'account_id', 'key_id', $key, $db);
	} else {
		$user_id = $_SESSION['user']['account_id'];
	}
	
	#Universal variables
	$instance_id = $_REQUEST['instance_id'];
	if($instance_id) {
		$instance_info = URIinfo('I'.$instance_id, $user_id, $key, $db);
	}
	
	#$acl = find_final_acl($user_id, $project_id, $db);
	$uni = compact('db', 'user_id');
	
	$s3ql=compact('user_id','db');
	$s3ql['from']='users';
	if($_REQUEST['instance_id']!='') {
		$s3ql['where']['item_id']=$instance_id;
	} else {
		$s3ql['where']['collection_id']=$_REQUEST['class_id'];	
	}
	$users=S3QLaction($s3ql);
	#$aclGrid = aclGrid(compact('user_id', 'db', 'users'));
?>