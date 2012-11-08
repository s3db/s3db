<?php
	#Viewuser.php is a general interface to visualize user infomation. Can be accessed by anyoen with permission on the user (creator of the user, general admin or user himself)
	include('adminheader.php');
	
	$section_num = '2';
	$website_title = $GLOBALS['s3db_info']['server']['site_title'].'  - view user account';
	$site_intro = $GLOBALS['s3db_info']['server']['site_intro'];
	include(S3DB_SERVER_ROOT.'/s3style.php');
	include(S3DB_SERVER_ROOT.'/tabs.php');
	
	if(!empty($_REQUEST['id'])) {
		$account_id = $_GET['id'];	
		$userviewed = URIinfo('U'.$account_id, $user_id, $key, $db);
		$account_addr_id= $userviewed['account_addr_id'];	
	}
	
	$s3ql=compact('user_id','db');
	$s3ql['select']='*';
	$s3ql['from']='groups';
	$s3ql['where']['user_id']=$userviewed['account_id'];
	$groups = S3QLaction($s3ql);
	
	$account_groups= create_static_group_list($groups, $userviewed['account_id']);
	$view_message= 'View User Account';
	$content_width= '70%';
	$button= '<input type="button" name="back" value="Back to User Account List" onClick="window.location=\''.$action['listusers'].'\'">';
	$account_lid= $userviewed['account_lid'];		
	$account_status=$userviewed['account_status'];
	if($userviewed['account_status'] =='A') {
		$account_status= 'Active';
		//$checked= 'checked');
	} else {
		$account_status= 'Inactive';
	}
	$account_uname=$userviewed['account_uname'];
	if($userviewed['account_type']=='u') {
		$account_type='User';
	}
	if($userviewed['account_type']=='p') {
		$account_type='Public User';
	}
	
	$account_email=$userviewed['account_email'];
	$account_phone=$userviewed['account_phone'];
	$account_last_login_on=substr($userviewed['account_last_login_on'], 0, 19);
	$account_last_login_from=$userviewed['account_last_login_from'];
	$account_last_pwd_changed_on= substr($userviewed['account_last_pwd_changed_on'], 0, 19);
	$account_last_pwd_changed_by= find_user_loginID($userviewed['account_last_pwd_changed_by']);
	$created_on= substr($userviewed['created_on'], 0, 19);
	$created_by= find_user_loginID($userviewed['created_by']);
	$modified_on= substr($userviewed['modified_on'], 0, 19);
	$modified_by= find_user_loginID($userviewed['modified_by']);

	$addr1= $userviewed['addr1'];
	$addr2= $userviewed['addr2'];
	$city= $userviewed['city'];
	$state= $userviewed['state'];
	$postal_code= $userviewed['postal_code'];
	$country= $userviewed['country'];
?>
<!-- BEGIN top -->
<form method="POST" action="<?php $action['viewuser'] ?>">
	<table class="top" align="center">
		<tr>
			<td>
				<table class="insidecontents" align="center" width="<?php $content_width ?>">
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
<!-- END top -->
<!-- BEGIN user_info_view -->
	<table class="middle" width="100%"  align="center">
		<tr>
			<td>
				<table class="insidecontents" width="<?php echo $content_width ?>"  align="center" border="0">
					<tr bgcolor="#80BBFF">
						<td colspan="4" align="center"><?php echo $view_message ?></td>
					</tr>
					<tr class="odd">
						<td class="info">Login ID</td>
						<td class="account_view"><?php echo $account_lid ?><input type="hidden" name="account_id" value="<?php echo $account_id ?>"<input type="hidden" name="account_addr_id" value="<?php echo $account_addr_id ?>"</td>
						<td class="info">Account Status</td>
					<!-- <td class="info"><input type="checkbox" name="account_status" value="<?php echo $account_status ?>" <?php echo $checked ?>>&nbsp;</td> -->
						<td class="account_view"><?php echo $account_status ?></td>
					</tr>
					<tr class="even">
						<td class="info">Real Name</td>
						<td class="account_view"><?php echo $account_uname ?></td>
						<td class="info">Account Type</td>
						<td class="account_view"><?php echo $account_type ?></td>
					</tr>
					<tr class="odd">
						<td class="info">Created On</td>
						<td class="account_view"><?php echo $created_on ?></td>
						<td class="info">Created By</td>
						<td class="account_view"><?php echo $created_by ?></td>
					</tr>
					<tr class="even">
						<td class="info">Last Modified On</td>
						<td class="account_view"><?php echo $modified_on ?></td>
						<td class="info">Last Modified By</td>
						<td class="account_view"><?php echo $modified_by ?></td>
					</tr>
					<tr class="odd">
					<!--
						<td width="25%" align="left">Account Type</td>
						<td width="25%" align="left"><?php #echo $account_type ?></td>
					-->
						<td class="info">Account Groups</td>
						<td class="account_view" colspan="3" ><?php echo $account_groups ?></td>
					</tr>
					<tr class="even">
						<td class="info">Last Password Changed On</td>
						<td class="account_view"><?php echo $account_last_pwd_changed_on ?></td>
						<td class="info">Last Password Changed By</td>
						<td class="account_view" ><?php echo $account_last_pwd_changed_by ?></td>
					</tr>
					<tr class="odd">
						<td class="info">Last Login On</td>
						<td class="account_view"><?php echo $account_last_login_on ?></td>
						<td class="info">Last Login From</td>
						<td class="account_view"><?php echo $account_last_login_from ?></td>
					</tr>
					<tr class="even">
						<td class="info" colspan="4">&nbsp;</td>
					</tr>
					<tr class="odd">
						<td class="info">Address 1</td>
						<td class="account_view"><?php echo $addr1 ?></td>
						<td class="info">Address 2</td>
						<td class="account_view"><?php echo $addr2 ?></td>
					</tr>
					<tr class="even">
						<td class="info">City</td>
						<td class="account_view"><?php echo $city ?></td>
						<td class="info">State</td>
						<td class="account_view"><?php echo $state ?></td>
					</tr>
					<tr class="odd">
						<td class="info">Postal Code</td>
						<td class="account_view"><?php echo $postal_code ?></td>
						<td class="info">Country</td>
						<td class="account_view"><?php echo $country ?></td>
					</tr>
					<tr class="even">
						<td class="info">Email</td>
						<td class="account_view"><?php echo $account_email ?></td>
						<td class="info">Phone</td>
						<td class="account_view"><?php echo $account_phone ?></td>
					</tr>
				</table>
			</td>
		</tr>
	</table>
<!-- END user_info_view -->
<!-- BEGIN bottom -->
	<table class="bottom" width="100%"  align="center">
		<tr>
			<td>
				<table class="insidecontents" width="<?php echo $content_width ?>"  align="center">
					<tr>
						<td align="left"><?php echo $button ?><br /><br /></td>
					</tr>
				</table>
			</td>
		</tr>
	</table>
</form>
<!-- END bottom -->