<?php
##Keycheck checks if a given key existst and is assigned to the user_id provided
#Part of S3DB (http://s3db.org)
#Helena Deus (helenadeus@gmail.com), 2009-08-06

ini_set('display_errors',0);
if($_REQUEST['su3d'])
ini_set('display_errors',1);

if(file_exists('config.inc.php'))
	{
	include('config.inc.php');
	}
else {
	echo "Not a valid S3DB call";
	exit;
}

ini_set("include_path", S3DB_SERVER_ROOT.'/pearlib'. PATH_SEPARATOR. ini_get("include_path"));
include_once(S3DB_SERVER_ROOT.'/s3dbcore/class.db.inc.php');
include_once(S3DB_SERVER_ROOT.'/s3dbcore/common_functions.inc.php');
include_once(S3DB_SERVER_ROOT.'/s3dbcore/callback.php');
include_once(S3DB_SERVER_ROOT.'/s3dbcore/SQL.php');
include_once(S3DB_SERVER_ROOT.'/s3dbcore/display.php');

$inputs = scriptInputs($_REQUEST, $argv);

$db = CreateObject('s3dbapi.db');
$db->Halt_On_Error = 'no';
$db->Host     = $GLOBALS['s3db_info']['server']['db']['db_host'];
$db->Type     = $GLOBALS['s3db_info']['server']['db']['db_type'];
$db->Database = $GLOBALS['s3db_info']['server']['db']['db_name'];
$db->User     = $GLOBALS['s3db_info']['server']['db']['db_user'];
$db->Password = $GLOBALS['s3db_info']['server']['db']['db_pass'];
$db->connect();

$user_id = get_entry('access_keys', 'account_id', 'key_id', $inputs['key'], $db);
if($user_id==""){
	$data[0] = array('error_code'=>5,'message'=>'Key NOT validated', 'user_id'=>'NA');
}
elseif ($inputs['user_id']=="") {
	 $data[0] = array('error_code'=>0,'message'=>'Key successfully validated', 'user_id'=>$user_id);
}
elseif($user_id!="" && $user_id==ereg_replace('^U','',$inputs['user_id'])){
	$data[0] = array('error_code'=>0,'message'=>'Key successfully validated', 'user_id'=>$user_id);
}
else {
	$data[0] = array('error_code'=>5,'message'=>'Key NOT validated', 'user_id'=>'NA');
}


$cols = array('error_code','message','user_id');
$format = $inputs['format'];
echo outputFormat(compact('data','cols', 'format'));

?>