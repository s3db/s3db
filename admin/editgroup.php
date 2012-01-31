<?php
	#editgroup.php is the interface for changing a pre-existing group (group name or existing users). Is contains the tabs.php file with links to project, etc

include('adminheader.php');

#edit group info
$group_id = $_REQUEST['group_id'];
$group_info = s3info('group', $group_id, $db);

if ($group_id=='' || !is_array($group_info)) {
	echo "Please provide a valid group_id";
	exit;
}
#group users
$s3ql=compact('user_id','db');
$s3ql['select']='*';
$s3ql['from']='users';
$s3ql['where']['group_id']=$group_id;

$group_users = S3QLaction($s3ql);


#from these, I only need their account_ids
if (is_array($group_users)) {
$group_users = account_id_as_key($group_users);
$group_users_ids = array_keys($group_users);
}
else {
	$group_users_ids=array();
}

#html variables
$group_name_required='*';
$message='* required';
	
#and finally all possible users, concatenate the list with myself
$s3ql=compact('user_id','db');
$s3ql['select']='*';
$s3ql['from']='users';
#$s3ql['where']['created_by']=$user_id;
$users = S3QLaction($s3ql);


#listing users
	
	if($_POST['updategroup'])
	{
		$new_group_name = trim($_POST['account_lid']);

		if ($new_group_name!=$group_info['account_lid']) {
			
		
		$s3ql=compact('user_id','db');
		$s3ql['edit']='group';
		$s3ql['where']['group_id']=$group_id;
		
		if ($new_group_name!='') {
			$s3ql['set']['groupname']=$new_group_name;
		}
		
		$done = S3QLaction($s3ql);
		ereg('<error>([0-9]+)</error><message>(.*)</message>', $done, $s3qlout);
		
		if ($s3qlout[1]=='0') {
			$group_info['account_lid'] = $new_group_name;
			$message = 'Group name changed.';
		}
		else {
			
			$message = $s3lqout[2];
		}
		}
		
		#selected users should be add users that exist in the post, remove users that exist, so run a foreach on the users and check if each one was posted or not.
		$selected_users = $_POST['account_users'];
		
		if (!is_array($selected_users)) {
			$selected_users = array();
		}
		
		$deleted_users = array_diff($group_users_ids, $selected_users);
		if(!empty($group_users_ids))
		$added_users = array_diff($selected_users, $group_users_ids);
		else {
		$added_users =	$selected_users;
		}
		
		
		foreach ($added_users as $new_user_id) {
			
			#this user exists in selected but it didn't exist in the group, so insert him
				$s3ql=compact('user_id','db');
				$s3ql['insert']='user';
				$s3ql['where']['user_id']=$new_user_id;
				$s3ql['where']['group_id']=$group_id;

			#	echo '<pre>';print_r($s3ql);
				$inserted = S3QLaction($s3ql);
				ereg('<error>([0-9]+)</error><message>(.*)</message>', $inserted, $s3qlout);
				if($s3qlout[1]!='0') $message = $s3qlout[2];
				#echo $inserted;
		
		}
		foreach ($deleted_users as $delete_user_id) {
				#these users exist in GROUP but it does NOT exist in SELECTED, so remove him
			
				$s3ql=compact('user_id','db');
				$s3ql['delete']='user';
				$s3ql['where']['user_id']=$delete_user_id;
				$s3ql['where']['group_id']=$group_id;

				$deleted = S3QLaction($s3ql);
				#echo $deleted;
				#exit;
				
				
			}
		Header('Location: '.$action['listgroups']);

		
	} #end post

	#redo the query because of changes
	#group users
	$s3ql=compact('user_id','db');
	$s3ql['select']='*';
	$s3ql['from']='users';
	$s3ql['where']['group_id']=$group_id;

	$group_users = S3QLaction($s3ql);
	
	if (is_array($group_users)) {
	$group_users = account_id_as_key($group_users);
	$group_users_ids = array_keys($group_users);
	}

	
	if (is_array($users)) {
	
	foreach ($users as $list_user_info) {
		#check if the user was there
		
		if (in_array($list_user_info['account_id'], $group_users_ids)) {
		$selected = 'selected';
		}
		else {
			$selected = '';
		}
		
		$user_list .= '<option value='.$list_user_info['account_id'].' '.$selected.'>'.$list_user_info['account_uname'].' ('.$list_user_info['account_lid'].')</option>';
	}
	}
include '../S3DBjavascript.php';
include '../tabs.php';
$disabled=($group_info['account_id']=='1')?' disabled':'';
#echo '<pre>';print_r($group_info);
#echo $action['editgroup'];exit;
?>
<form method="POST" action="<?php echo $action['editgroup']; ?>">
<table class="top" align="center">
	<tr><td>
		<table class="insidecontents" align="center" width="70%">
			<tr><td class="message"><br /><?php echo $message ?></td></tr>
			
		</table>
	</td></tr>
</table>
<table class="middle">
	<tr><td>
	<table class="insidecontents" width="60%" align="center">
		<tr bgcolor="#99CCFF"><td align="center">Edit Group Account<input type="hidden" name="account_id" value="<?php echo $group_info['account_id'] ?>"><input type="hidden" name="account_lid" value="<?php echo $group_info['account_lid'] ?>"></td></tr>
		
	</table>
	</td></tr>
</table>
<!-- BEGIN group_info_edit -->
<table class="middle" width="70%"  align="center">
	
		<table class="insidecontents" width="60%"  align="center" border="0">
			
			<tr class="odd">
				<td class="info">Group Name<sup class="required"><?php echo $group_name_required ?></sup></td>
				<td class="info">
				<?php echo '<input name="account_lid" value="'.$group_info['account_lid'].'"'.$disabled.'>&nbsp;</td>';?>
	
				<td class="info">Users to be added to this group</td>
				<td colspan="3"><select name="account_users[]" multiple><?php echo $user_list ?><option value="-100"></option></select></td>
			</tr>
		</table>
	
</table>
<!-- END group_info_edit -->
<table class="bottom" width="100%"  align="center">
	<tr><td>
	<table class="insidecontents" width="60%"  align="center">
	<tr><td align="left">
	
	<input type="submit" name="updategroup" value="Update Group Account">&nbsp;&nbsp;&nbsp;&nbsp;<input type="button" name="back" value="Back to Group Account List" onClick="window.location='<?php echo $action['listgroups'] ?>'"><br /><br /></td></tr>
	</table>
	</td></tr>
</form>
</table>

<?php
include '../footer.php';
?>
