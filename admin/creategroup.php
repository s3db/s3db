<?php
	#creategroup.php is an interface for inserting a group. Include insert group and adding users to it
	#Helena F Deus (helenadeus@gmail.com)
	include('adminheader.php');

	$action_url = $action['creategroup'];
	$website_title=$GLOBALS['s3db_info']['server']['site_title'].' - create new group account';
	$edit_message='Create New Group Account';
	$content_width='70%';
	$group_name_required='*';
	$message='* required';
	
	#find all the users
	$s3ql=compact('user_id','db');
	$s3ql['select']='*';
	$s3ql['from']='users';
	$s3ql['format']='html';
	$users = S3QLaction($s3ql);
	$user_list=create_group_select_list($users);
	$button='<input type="submit" name="submit" value="Create Group Account">';
	
	if($_POST['submit']) {
		$s3ql=compact('user_id','db');
		$s3ql['insert']='group';
		$s3ql['where']['name']=$_POST['account_lid'];
		$s3ql['format']='html';
		$created = S3QLaction($s3ql);
		$msg = html2cell($created);
		$msg=$msg[2];
		#ereg('<group_id>([0-9]+)</group_id>', $created, $new_group_id);
		
		if($msg['error_code']=='0') {
			$group_id = $msg['group_id'];
		}	
		#now put the users in the group
		$selected_users = $_POST['account_users'];
		if(!is_array($selected_users)) {
			$selected_users = array();
		}
		
		if($group_id!='') {
			foreach ($selected_users as $imp_user_id) {
				$s3ql=compact('user_id','db');
				$s3ql['insert']='user';
				$s3ql['where']['user_id']=$imp_user_id;
				$s3ql['where']['group_id']=$group_id;
				$done = S3QLaction($s3ql);
				$msg = html2cell($done);
				$msg=$msg[2];
			}
			#and now go back to list groups
			header('Location:'.$action['listgroups']);
			exit;
		} else {
			#ereg('<message>(.*)</message>', $created, $message);
			$message = $msg['message'];
		}
	}
     
	include '../s3style.php';
	include '../tabs.php';
?>
<!-- BEGIN top -->
<form method="POST" action="<?php echo $action_url ?>">
	<table class="top" align="center">
		<tr>
			<td>
				<table class="insidecontents" align="center" width="<?php echo $content_width ?>">
					<tr>
						<td class="message"><br /><?php echo $message ?></td>
					</tr>
					<tr align="center">
						<td colspan="2" class="current_stage"><?php echo $current_stage ?></td>
					</tr>
				</table>
			</td>
		</tr>
	</table>
	<!-- BEGIN group_info_edit -->
	<table class="middle" width="100%"  align="center">
		<tr>
			<td>
				<table class="insidecontents" width="<?php echo $content_width ?>"  align="center" border="0">
					<tr bgcolor="#80BBFF">
						<td colspan="7" align="center"><?php echo $edit_message ?><input type="hidden" name="account_id" value="<?php echo $account_id ?>"><input type="hidden" name="account_lid" value="<?php echo $account_lid ?>"></td>
					</tr>
					<tr class="odd">
						<td class="info">Group Name<sup class="required"><?php echo $group_name_required ?></sup></td>
						<td class="info"><input name="account_lid" value="<?php echo $account_lid ?>">&nbsp;</td>
						<td class="info">Users to be added to this group</td>
						<td colspan="3"><select name="account_users[]" multiple><?php echo $user_list ?><option value="-100"></option></select></td>
					</tr>
				</table>
			</td>
		</tr>
	</table>
	<!-- END group_info_edit -->
	<!-- BEGIN bottom -->
	<table class="bottom" width="100%"  align="center">
		<tr>
			<td>
				<table class="insidecontents" width="<?php echo $content_width ?>"  align="center">
					<tr>
						<td align="left"><?php echo $button ?> <input type="button" value="Group List" onClick="window.location='<?php echo $action['listgroups']; ?>'"><br /><br /></td>
					</tr>
				</table>
			</td>
		</tr>
	</table>
	<!-- END bottom -->
</form>
<?php
	include '../footer.php';
?>
