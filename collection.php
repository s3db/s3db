<?php
#resource.php displays general information about resource
	#Includes links to edit and delete resource, as well as edit rules
	#Helena F Deus (helenadeus@gmail.com)
#classheader contains common elements to all class interfaces.
	ini_set('display_errors',0);
	if($_REQUEST['su3d'])
	ini_set('display_errors',1);
	if($_SERVER['HTTP_X_FORWARDED_HOST']!='') $def = $_SERVER['HTTP_X_FORWARDED_HOST'];
	else $def = $_SERVER['HTTP_HOST'];
	if(file_exists('config.inc.php'))
	{include('config.inc.php');}
	else{
		Header('Location: http://'.$def.'/s3db/');
		exit;
		}
	
#Get the key, send it to check validity
$key = $_GET['key'];
include_once('core.header.php');

if($key)
	$user_id = get_entry('access_keys', 'account_id', 'key_id', $key, $db);
	else
	$user_id = $_SESSION['user']['account_id'];

$class_id = ($_REQUEST['class_id']!='')?$_REQUEST['class_id']:(($_REQUEST['collection_id']!='')?$_REQUEST['collection_id']:$_REQUEST['entity_id']);
$uid = 'C'.$class_id;
$element = 'class';

#Universal variables
$uid_info=uid($class_id);
$resource_info = URIinfo($uid, $user_id, $key, $db);
$class_info = $resource_info;
$rule_id = $resource_info['rule_id'];

if($_REQUEST['orderBy'])
$SQLextra['order_by'] = ' order by '.$_REQUEST['orderBy'].' '.$_REQUEST['direction'];

#Define the outgoing links there are going to exist in this page

$s3ql=compact('user_id','db');
$s3ql['from']='users';
$s3ql['where']['class_id']=$class_id;
$users=S3QLaction($s3ql);
$aclGrid = aclGrid(compact('user_id', 'db', 'users'));

$s3ql = compact('db', 'user_id');
$s3ql['from'] = 'rules';
$s3ql['where']['subject_id'] = $class_id;
$s3ql['where']['object'] = "!='UID'";
if($_REQUEST['project_id'])
	$s3ql['where']['project_id']=$_REQUEST['project_id'];
if($_REQUEST['orderBy'])
$s3ql['order_by'] = $_REQUEST['orderBy'].' '.$_REQUEST['direction'];
#echo '<pre>';print_r($s3ql);

$rules = S3QLaction($s3ql);

if($resource_info['view'])
{
#include all the javascript functions for the menus...
include('S3DBjavascript.php');

#and the short menu for the resource script
include('action.header.php');


$s3ql=compact('user_id','db');
$s3ql['from']='users';
$s3ql['where']['class_id']=$class_id;
$users = S3QLaction($s3ql);
?>

<table  border=0 class="intro" width="100%"  align="center">
	<br /><br />
	<tr bgcolor="#CCFF99"><td colspan="3" align="center" >Collection Details</FONT></td></tr>
	<tr class="">
		<td width="20%">Resource Name: </td>
	<?php
	echo '<td><b>'.$resource_info['entity'].'</b>&nbsp;&nbsp;&nbsp;&nbsp;';
	if($resource_info['change']) ##only level 3 in the project that created this resource cna change or delete it
	#also, cannot edit resources stored remotelly... for now :-)
	{
	if($uid_info['Did']==$GLOBALS['Did'])
	echo '<a href="'.$action['editclass'].'">Edit</a>&nbsp;&nbsp;&nbsp;&nbsp;';
	echo '<a href="'.$action['deleteclass'].'">Delete</a></td>';
	}
	?>
	</tr>
	<tr class="">
		<td>Resource Description: </td>
		<?php echo '<td><b>'.$resource_info['notes'].'</b></td>';
		?>
	</tr>
	<tr class="">
		<td>Created By: </td>
		<?php echo '<td><b>'.find_user_loginID(array('account_id'=>$resource_info['created_by'], 'db'=>$db)).'</b></td>';
		?>
	</tr>
		<tr cclass="">
		<td>Created On: </td>
		<?php
		echo '<td><b>'.$resource_info['created_on'].'</b></td>';
		?>
	</tr>
	<tr class="">
		<td>Class ID: </td>
		<?php
		echo '<td><b>'.$class_id.'</b></td>';
		?>
	</tr>
	<tr class="">
	<td>
	<BR>
	<?php
	if($project_info['add_data'])
	echo '<input type="button" value="Edit Rules" size="20" onClick="window.location=\''.$action['editrules'].'\'">&nbsp;&nbsp;&nbsp;<BR><BR> </td>';
	?>
	<td><BR></td>
	</tr>


<?php
#include the rules at the end of the page
#echo '<pre>';print_r($rules);
if(is_array($rules) && !empty($rules))
	{	echo '<tr bgcolor="#CCFF99"><td colspan="3" align="center">Rules</td></tr>';
		echo $rule_list = render_elements($rules, $acl, array('Rule_id', 'Subject', 'Verb', 'Object', 'Notes'), 'rule');
	}
if(is_array($users) && !empty($users))
	{
		echo '<BR><BR>';
		echo '<table  border=0 class="intro" width="100%"  align="center"><tr bgcolor="#CCFF99"><td colspan="3" align="center">Users</td></tr>';
		echo $user_list = render_elements($users, $acl,  array('Login', 'User Name', 'Permissions'), 'account_acl');

	}
}
?>
</table>