<?php
	if($_SERVER['HTTP_X_FORWARDED_HOST']!='') {
		$def = $_SERVER['HTTP_X_FORWARDED_HOST'];
	} else {
		$def = $_SERVER['HTTP_HOST'];
	}
			
	if(file_exists('config.inc.php')) {
		include('config.inc.php');
	} else {
		Header('Location: http://'.$def.'/s3db/');
		exit;
	}
	$key = $_GET['key'];
	if($key=='') { $key = $s3ql['key']; }
	if($key=='') { $key=$argv[1]; }
	$file=$_REQUEST['file'];
	if($file=='') { $file=$argv[3]; }
	$inputs = ($argv!='')?$argv:$_REQUEST;
	#When the key goes in the header URL, no need to read the xml, go directly to the file
	include_once('core.header.php');
	include('dbstruct.php');

	if($user_id!='1') {
		echo "User cannot change table configuration";
		exit;
	} else {
		$sql = "update s3db_permission set id_code=substr(uid,1,1)";
		$db->query($sql,__LINE__,__FILE__);
		$sql = "update s3db_permission set id_num=substr(uid,2,length(uid))";
		$db->query($sql,__LINE__,__FILE__);
		$sql = "update s3db_permission set shared_with_code=substr(shared_with,1,1)";
		$db->query($sql,__LINE__,__FILE__);
		$sql = "update s3db_permission set shared_with_num=substr(shared_with,2,length(shared_with))";
		$db->query($sql,__LINE__,__FILE__);
	}
?>