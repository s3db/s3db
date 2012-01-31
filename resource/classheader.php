<?php
#classheader contains common elements to all class interfaces.
	ini_set('display_errors',0);
	if($_REQUEST['su3d'])
	ini_set('display_errors',1);
	
	if($_SERVER['HTTP_X_FORWARDED_HOST']!='') $def = $_SERVER['HTTP_X_FORWARDED_HOST'];
	else $def = $_SERVER['HTTP_HOST'];
	if(file_exists('../config.inc.php'))
	{include('../config.inc.php');}
	else{
		Header('Location: http://'.$def.'/s3db/');
		exit;
		}
	


#just to know where we are...
$thisScript = end(explode('/', $_SERVER['SCRIPT_FILENAME'])).'?'.$_SERVER['argv'][0];


#Get the key, send it to check validity
$key = $_GET['key'];
include_once('../core.header.php');

#if($key)
#	$user_id = get_entry('access_keys', 'account_id', 'key_id', $key, $db);
#	else
#	$user_id = $_SESSION['user']['account_id'];

$project_id = $_REQUEST['project_id'];
$project_info = URIinfo('P'.$project_id, $user_id,$key, $db);
$class_id = ($_REQUEST['collection_id']!='')?$_REQUEST['collection_id']:$_REQUEST['class_id'];
$collection_id = $class_id;
$uid = 'C'.$class_id;
$element = 'collection';
#Universal variables
$uid_info=uid($class_id);
$resource_info = URIinfo($uid, $user_id, $key, $db);
$class_info = $resource_info;
#echo '<pre>';print_r($class_info);exit;
$rule_id = $resource_info['rule_id'];
if($_REQUEST['orderBy'])
$SQLextra['order_by'] = ' order by '.$_REQUEST['orderBy'].' '.$_REQUEST['direction'];

$uni = compact('db', 'user_id','key', 'SQLextra', 'resource_info', 'dbstruct');

#Define the outgoing links there are going to exist in this page
#relevant extra arguments
#include('../webActions.php');

$s3ql=compact('user_id','db');
$s3ql['from']='users';
$s3ql['where']['collection_id']=$class_id;
$users=S3QLaction($s3ql);
#$aclGrid = aclGrid(compact('user_id', 'db', 'users'));
#echo '<pre>';print_r($users);
$s3ql = compact('db', 'user_id');
$s3ql['from'] = 'rules';
$s3ql['where']['subject_id'] = $class_id;
$s3ql['where']['object'] = "!=UID";
if($_REQUEST['project_id'])
	$s3ql['where']['project_id']=$_REQUEST['project_id'];
if($_REQUEST['orderBy'])
$s3ql['order_by'] = $_REQUEST['orderBy'].' '.$_REQUEST['direction'];
#echo '<pre>';print_r($s3ql);

$rules = S3QLaction($s3ql);
#echo '<pre>';print_r($rules);

?>