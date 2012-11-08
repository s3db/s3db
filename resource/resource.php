<?php
	#resource.php displays general information about resource
	#Includes links to edit and delete resource, as well as edit rules
	#Helena F Deus (helenadeus@gmail.com)
	ini_set('display_errors',0);
	if($_REQUEST['su3d']) {
		ini_set('display_errors',1);
	}
	include('classheader.php');

	if($resource_info['view']) {
		#include all the javascript functions for the menus...
		include('../S3DBjavascript.php');

		#and the short menu for the resource script
		include('../action.header.php');

		$s3ql=compact('user_id','db');
		$s3ql['from']='users';
		$s3ql['where']['collection_id']=$class_id;
		$users = S3QLaction($s3ql);
?>
<table  border=0 class="intro" width="100%"  align="center">
	<tr bgcolor="#CCFF99">
		<td colspan="3" align="center" ><br /><br />Resource Details</FONT></td>
	</tr>
	<tr class="">
		<td width="20%">Resource Name: </td>
<?php
		echo '<td><b>'.$resource_info['entity'].'</b>&nbsp;&nbsp;&nbsp;&nbsp;';
		if($resource_info['change']) {		##only level 3 in the project that created this resource cna change or delete it #also, cannot edit resources stored remotelly... for now :-)
			if($uid_info['Did']==$GLOBALS['Did']) {
				echo '<a href="'.$action['editclass'].'">Edit</a>&nbsp;&nbsp;&nbsp;&nbsp;';
			}
			echo '<a href="'.$action['deleteclass'].'">Delete</a></td>';
		}
?>
	</tr>
	<tr class="">
		<td>Resource Description: </td>
		<?php echo '<td><b>'.$resource_info['notes'].'</b></td>'; ?>
	</tr>
	<tr class="">
		<td>Created By: </td>
		<?php echo '<td><b>'.find_user_loginID(array('account_id'=>$resource_info['created_by'], 'db'=>$db)).'</b></td>'; ?>
	</tr>
	<tr cclass="">
		<td>Created On: </td>
		<?php echo '<td><b>'.$resource_info['created_on'].'</b></td>'; ?>
	</tr>
	<tr class="">
		<td>Collection ID: </td>
		<?php echo '<td><b>'.$class_id.'</b></td>'; ?>
	</tr>
	<tr class="">
		<td>
			<BR>
<?php
		if($project_info['change']) {
			echo '<input type="button" value="Edit Rules" size="20" onClick="window.location=\''.$action['editrules'].'\'">&nbsp;&nbsp;&nbsp;<BR><BR>';
		}
?>
		</td>
		<td><BR></td>
	</tr>
<?php
		#include the rules at the end of the page
		if(is_array($rules) && !empty($rules)) {
			echo '<tr bgcolor="#CCFF99"><td colspan="3" align="center">Rules</td></tr>';
			echo $rule_list = render_elements($rules, $acl, array('Rule_id', 'Subject', 'Verb', 'Object', 'Notes'), 'rule');
		}
		if(is_array($users) && !empty($users)) {
			$new = 0;
			$uid = 'C'.$class_id;
			echo '<BR><BR>';
			echo '<table  border=0 class="intro" width="100%"  align="center"><tr bgcolor="#CCFF99"><td colspan="3" align="center">Users</td></tr>';
			echo $user_list = render_elements($users, $acl,  array('User ID', 'Login', 'User Name', 'Permissions'), 'account_acl', 0, $uid);
		}
	}
?>
</table>