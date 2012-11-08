<?php
	#admin/index.php is the admin browser for users and groups. Should a normal user try to access it, he will be redirected to logout.
	#Helena F Deus (helenadeus@gmail.com)
	include('adminheader.php');
		
	$section_num = '2';
	$website_title = $GLOBALS['s3db_info']['server']['site_title'].' - Admin';
	$site_intro = $GLOBALS['s3db_info']['server']['site_intro'];
	
	include(S3DB_SERVER_ROOT.'/s3style.php');
	include(S3DB_SERVER_ROOT.'/tabs.php');

	if($user_info['account_lid'] == 'Admin') {
         $message='You are logging in as generic admin account. You are recommended to create a <a href="'.$action['createuser'].'">new</a> user account with admin privilege and use that account.';
	}
?>
<table class="contents">
	<tr>
		<td class="message"><br /><br /><?php echo $message ?><br /><br /></td>
	</tr>
	<tr>
		<td><h2>Admin</h2></td>
	</tr>
	<tr>
		<td>As the site administrator, you can manage the user and group account. You can also manage other's projects and work on your own projects.</td>
	</tr>
	<tr>
		<td><br /><br /><br /></td>
	</tr>
</table>
<?php
	include(S3DB_SERVER_ROOT.'/footer.php');
?>