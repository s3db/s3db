<?php
	#deleteeuser.php is the interface for deleting a user. Includes tabs, and link to userlist.
	#Helena F Deus (helenadeus@gmail.com)
	include('adminheader.php');

	$imp_user_id = $_REQUEST['id'];
	$deleteduser = s3info('user', $imp_user_id, $db);

	#find a list of user to whom ownership of projects can be given to
	$s3ql=compact('user_id','db');
	$s3ql['select']='*';
	$s3ql['from']='users';
	$users = S3QLaction($s3ql);

	if($_POST['back']) {
		Header('Location: '.$action['listusers']);
	}
	if($_POST['deleteuser']) {
		#and make the user inactive
		$s3ql=compact('user_id','db');
		if($_POST['deleteuser']=='Remove from Deployment') {	
			$s3ql['delete']='user';
			$s3ql['where']['user_id']=$imp_user_id;
			$s3ql['flag']='resource';
		} elseif($_POST['deleteuser']=='Deactivate Account') {
			$s3ql['update']='user';
			$s3ql['where']['user_id']=$imp_user_id;
			$s3ql['where']['account_status']='I';
			#$s3ql['where']['permission_level']='000';
		}
		$s3ql['format']='html';
		$deleted = S3QLaction($s3ql);
		$deleted= html2cell($deleted);
		
		if($deleted[2]['error_code']=='0') {
			Header('Location: '.$action['listusers']);
			exit;
		} else {
			ereg('<message>(.*)</message>', $deleted, $s3qlouput);
			$message = $s3qlouput[0];
		}
	}
	include '../S3DBjavascript.php';
	include '../tabs.php';

	#$user_list=create_user_list($users);
	$section_num='2';
	$action_url=$actions['deleteuser'];
	$delete_message='Delete User Account -- '.$deleteduser['account_uname'].' ('.$deleteduser['account_lid'].')';
	$prompt='Are you sure you want to delete this user?';
	$content_width='60%';
	
	#if user exists somewhere else, there is no problem in removing it from this deployment. 
	$uid_info = uid($deleteduser['account_id']);
	
	if($uid_info['Did']!=$GLOBALS['Did']) {
		$action_button .='<input type="submit" name="deleteuser" value="Remove from Deployment">&nbsp;&nbsp;&nbsp;&nbsp;';
	}
	$action_button .='<input type="submit" name="deleteuser" value="Deactivate Account">&nbsp;&nbsp;&nbsp;&nbsp;';
    $action_button .='<input type="submit" name="back" value="Back to User Account List">';  
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
				</table>
			</td>
		</tr>
	</table>
<!-- END top -->
<!-- BEGIN delete_user -->
	<table class="middle" width="100%"  align="center">
		<tr>
			<td>
				<table class="insidecontents" width="<?php echo $content_width ?>"  align="center" border="0">
					<tr bgcolor="#80BBFF">
						<td colspan="2" align="center"><?php echo $delete_message ?><input type="hidden" name="account_id" value="<?php echo $account_id ?>"></td>
					</tr>
					<tr class="odd">
						<td class="message"><?php echo $prompt ?></td>
						<td>
							<table class="insidecontents" width="<?php echo $content_width ?>"  align="center" border="0">
								<tr class="odd">
									<td class="info">Login ID</td>
									<td class="account_view"><?php echo $deleteduser['account_lid'] ?><input type="hidden" name="account_id" value="<?php echo $account_id ?>"<input type="hidden" name="account_addr_id" value="<?php echo $account_addr_id ?>"</td>
									<td class="info">Account Status</td>
									<td  class="account_view"><?php echo $deleteduser['account_status'] ?></td>
								</tr>
								<tr class="even">
									<td class="info">Real Name</td>
									<td  class="account_view"><?php echo $deleteduser['account_uname'] ?></td>
									<td class="info">Account Type</td>
									<td  class="account_view"><?php echo $deleteduser['account_type'] ?></td>
								</tr>
								<tr class="odd">
									<td class="info">Created On</td>
									<td class="account_view"><?php echo $deleteduser['created_on'] ?></td>
									<td class="info">Created By</td>
									<td class="account_view"><?php echo getUserName($deleteduser['created_by'], $db); ?></td>
								</tr>
							</table>
						</td>
					</tr>
				</table>
			</td>
		</tr>
	</table>
<!-- END delete_user -->
<!-- BEGIN bottom -->
	<table class="bottom" width="100%"  align="center">
		<tr>
			<td>
				<table class="insidecontents" width="<?php echo $content_width ?>"  align="center">
					<tr>
						<td align="left"><?php echo $action_button  ?><br /><br /></td>
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