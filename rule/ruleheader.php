<?php
#classheader contains common elements to all class interfaces.
	#Helena F Deus (helenadeus@gmail.com)
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
$key = $_REQUEST['key'];
include_once('../core.header.php');
##echo '<pre>';print_r($user_info);
#if($key)
#	$user_id = get_entry('access_keys', 'account_id', 'key_id', $key, $db);
#	else
#	$user_id = $_SESSION['user']['account_id'];

#Universal variables
$project_id = $_REQUEST['project_id'];
$rule_id = $_REQUEST['rule_id'];
$uid = 'R'.$rule_id;
$element = 'rule';

$project_info = URIinfo('P'.$project_id, $user_id, $key, $db);
$rule_info = URIinfo($uid, $user_id, $key, $db);
$class_id = ($_REQUEST['class_id']!='')?$_REQUEST['class_id']:(($_REQUEST['entity_id']!='')?$_REQUEST['entity_id']:$_REQUEST['resource_id']);
$resource_info = URIinfo('C'.$class_id, $user_id, $key, $db);
$class_info = $resource_info;
if($_REQUEST['orderBy'])
$SQLextra['order_by'] = ' order by '.$_REQUEST['orderBy'].' '.$_REQUEST['direction'];

$uni = compact('db', 'user_id','key', 'SQLextra', 'resource_info', 'dbstruct');

#Define the outgoing links there are going to exist in this page
#relevant extra arguments
#include('../webActions.php');

$s3ql=compact('user_id','db');
$s3ql['from']='users';
$s3ql['where']['project_id']=$project_id;
$users=S3QLaction($s3ql);
$aclGrid = aclGrid(compact('user_id', 'db', 'users'));

?>