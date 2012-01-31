<?php
#encrypt.php acepts a string and encripts it using the s3db public key;
ini_set('display_errors',0);
if($_REQUEST['su3d'])
ini_set('display_errors',1);

if(file_exists('config.inc.php')){
		include('config.inc.php');
		
}
else {	
	Header('Location: login.php');
		exit; 
}
@set_time_limit(0);
@ini_set('memory_limit', '256M');
@ini_set('upload_max_filesize', '128M');
@ini_set('post_max_size', '256M');
@ini_set('max_input_time', '-1');
@ini_set('max_execution_time', '-1');
@ini_set('expect.timeout', '-1');
@ini_set('default_socket_timeout', '-1');

 
include_once(S3DB_SERVER_ROOT."/s3dbcore/encryption.php");
$str=$_REQUEST['q'];
$pubKey = $GLOBALS['s3db_info']['deployment']['public_key'];
if($_REQUEST['public_key']) $pubKey =$_REQUEST['public_key'];
$encripted = encrypt($str, $pubKey);
echo $encripted;


?>