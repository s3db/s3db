<?php
#instance.vars.php is a script to be included whenever for any webS3DB interface where the acces on instance must be verified. This script is not to be executed stand alone.
ini_set('display_errors',0);
if($_REQUEST['su3d'])
ini_set('display_errors',1);

if(is_object($db)) {
if($key) $user_id = get_entry('access_keys', 'account_id', 'key_id', $key, $db);
	else $user_id = $_SESSION['user']['account_id'];

$instance_id = ($_REQUEST['item_id']!='')?$_REQUEST['item_id']:$_REQUEST['instance_id'];
$instance_info = S3QLinfo('instance', $instance_id, $user_id, $db);

$project_id = $_REQUEST['project_id'];
$project_info = S3QLinfo('project', $project_id, $user_id, $db);
#arguments for the actions
$args = '?key='.$_REQUEST['key'].'&project_id='.$_REQUEST['project_id'].'&class_id='.$_REQUEST['class_id'].'&item_id='.$instance_id;

#actions for this page
include '../webActions.php';

#$instanceAcl = dataAcl(compact('instance_info', 'user_id', 'db'));
$instanceAcl = $instance_info['acl'];
#instanceAcl will be the level of permission that a specific user has on this instance
}
else {
	exit;
}
?>