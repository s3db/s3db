<?php
	#user.php is a list of users for the administrators. Includes links for edit, delete, view and proxy users created by a specific admin.
	#Helena F Deus (helenadeus@gmail.com)


	#include(S3DB_SERVER_ROOT.'/header.inc.php');
	include('adminheader.php');
	$section_num = '2';
	$website_title = $GLOBALS['s3db_info']['server']['site_title'].' - Admin';
	$site_intro = $GLOBALS['s3db_info']['server']['site_intro'];

	if(in_array('activate', array_keys($_REQUEST)))
	{
	$s3ql=compact('user_id','db');
	$s3ql['edit']='user';
	$s3ql['where']['user_id']=$_REQUEST['activate'];
	$s3ql['where']['account_status']='A';
	
	$done = S3QLaction($s3ql);
	
	}
	
	
	if($_POST['newuser'])
	{
		Header('Location: '.$action['createuser']);
	}		
	
	#cols where query will be performed
	$s3ql=compact('user_id','db');
	$s3ql['select']='*';
	$s3ql['from']='users';
	if ($user_id!='1') {
	$s3ql['where']['created_by']=$user_id;
	}

	if(in_array('show_inactive', array_keys($_REQUEST)))
			$s3ql['where']['account_status'] = $GLOBALS['regexp']."A|I";
	
	 #these are the users this admin can edit
	if ($_REQUEST['orderBy']!='') {
		$s3ql['order_by']=$_REQUEST['orderBy'].' '.$_REQUEST['direction'];
	}
	else {
		$s3ql['order_by']='account_uname asc';
	}
	
	
	$users = S3QLaction($s3ql);
	$me = $user_info;
	
	
	if(is_array($users))
	array_push($users, $me);
	else {
		$users = array($me);
	}
	
	
	
	include(S3DB_SERVER_ROOT.'/s3style.php');
	include(S3DB_SERVER_ROOT.'/tabs.php');

	
	
	
	

if (is_array($users)) {
$managerHead = '<tr bgcolor="#99CCFF"><td align="center">User Manager</td></tr>';

if(user_is_admin($user_id, $db))
	if(!in_array('show_inactive', array_keys($_REQUEST)))
	$inactive_user_link = '<a href="'.$action['listusers'].'&show_inactive">Show Inactive Users</a>';
	else
	$inactive_user_link = '<a href="'.str_replace('&show_inactive', '', $action['listusers']).'">Hide Inactive Users</a>';
	

$cols2show = array('User ID', 'User Name', 'Login', 'Created Date', 'Actions');

if(in_array('show_inactive', array_keys($_REQUEST)))
$cols2show = array_merge($cols2show, array('Account Status'));

$datagrid = render_elements($users, $acl, $cols2show, 'users');
}
else {
	$message = 'No user yet. Please create one.';
}
#echo '<font color="red">'.$message.'</font>';
?>
<table class="top" align="center">
	<tr><td>
		<table class="insidecontents" align="center" width="80%">
			<tr><td class="message"><br /><br /><?php echo $message ?></td></tr>
			<tr align="center"><td colspan="2" class="current_stage"></td></tr>
		</table>
	</td></tr>
</table>

<table class="middle">
	<tr><td>
	<table class="insidecontents" width="80%" align="center">
		<tr align="left"><td>
			<?php echo $inactive_user_link ?>
		</td></tr>
		<?php echo $managerHead; ?>
		
		
		<tr align="center"><td>
			
			<?php echo $datagrid ?>
		</td></tr>

	</table>
	</td></tr>
</table>
<table class="bottom" width="100%"  align="center">
	<tr><td>
	<table class="insidecontents" width="80%"  align="center">
	<tr><td align="left"><input type="button" name="newuser" value="New User Account" onClick="window.location='<?php echo $action['createuser']; ?>'">
	<input type="button" name="remoteuser" value="Insert Remote User" onClick="window.location='<?php echo $action['remoteuser']; ?>'">
<br /><br />
	
	</td></tr>

	</table>
	</td></tr>
</form>
</table>
<?php
include(S3DB_SERVER_ROOT.'/footer.php');
?>