<?php
	#headerFrame is the javascript header that will persist as the top frame of the page. Eventually user data will be stored here in javascript
 ini_set('display_errors',0);
	if($_REQUEST['su3d'])
	ini_set('display_errors',1);
	if(file_exists('../config.inc.php'))
	{
		include('../config.inc.php');
	}
	else
	{
		echo '<META HTTP-EQUIV="Refresh" Content= "0; target="_parent" URL="../login.php?error=2">';
		exit;
	}
	$section_num = ($_GET['section_num']=='')?'4':$_GET['section_num'];
		
	#$args = '?key='.$_REQUEST['key'];
	#include(S3DB_SERVER_ROOT.'/header_frames.inc.php');
	include '../core.header.php';
	include '../S3DBjavascript.php';
	include '../webActions.php';
	
	
	
	if($key)
	$user_id = get_entry('access_keys', 'account_id', 'key_id', $key, $db);
	else
	$user_id = $_SESSION['user']['account_id'];


	#$user_info = s3info('user', $user_id, $db);
	$user_info = $_SESSION['user'];
	include '../tabs.php';
?>