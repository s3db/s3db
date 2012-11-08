<?php
	#group.php list all groups that the admin user can edit (those where he belongs and those that he created. Only general admin can see all groups at once.)
	#Helena F Deus (helenadeus@gmail.com)
	include('adminheader.php');
	$manager='Group Manager';
	
	$s3ql=compact('user_id','db');
	$s3ql['select']='*';
	$s3ql['from']='groups';
	if($user_id!='1') {
		$s3ql['where']['user_id']=$user_id;
	}
	if($_REQUEST['orderBy']!='') {
		$s3ql['order_by']=$_REQUEST['orderBy'].' '.$_REQUEST['direction'];
	}
	$groups = S3QLaction($s3ql);

	if(!empty($groups)) {
		$columns = array('Group ID', 'Group Name', 'Actions');
		$data_grid=render_elements($groups, $acl, $columns, 'groups');
	} else {
		$message='No group yet. Please create one.'; 
	}
	
	$section_num = '2';
	$website_title = $GLOBALS['s3db_info']['server']['site_title'].' - Admin';
	$site_intro = $GLOBALS['s3db_info']['server']['site_intro'];
	include(S3DB_SERVER_ROOT.'/s3style.php');
	include(S3DB_SERVER_ROOT.'/tabs.php');
?>
<!-- BEGIN top -->
<form method="POST" action="<?php echo $action['listgroups']; ?>">
	<table class="top" align="center">
		<tr>
			<td>
				<table class="insidecontents" align="center" width="80%">
					<tr>
						<td class="message"><br /><?php echo $message; ?></td>
					</tr>
					<tr align="center">
						<td colspan="2" class="current_stage"><?php echo $current_stage ?></td>
					</tr>
				</table>
			</td>
		</tr>
	</table>
<!-- END top -->
	<!-- BEGIN middle -->
	<!--
	<div id="contents">
	-->
	<table class="middle">
		<tr>
			<td>
				<table class="insidecontents" width="60%" align="center">
<?php 
	if(!empty($groups)) {
		echo '
					<tr bgcolor="#99CCFF">
						<td align="center">'.$manager.'</td>
					</tr>
					<tr align="center">
						<td>'.$data_grid.'</td>
					</tr>';
	}
?>
				</table>
			</td>
		</tr>
	</table>
	<!-- END middle -->
	<!-- BEGIN bottom -->
	<table class="bottom" width="60%"  align="center">
		<tr>
			<td>
				<table class="insidecontents" width="<?php echo $content_width ?>"  align="center">
					<tr>
						<td align="left"><input type="button" name="newgroup" value="New Group Account" onClick="window.location='<?php echo $action['creategroup'] ?>'"><br /><br /></td>
					</tr>
				</table>
			</td>
		</tr>
	</table>
</form>
<!-- END bottom -->
<?php
	include(S3DB_SERVER_ROOT.'/footer.php');
?>