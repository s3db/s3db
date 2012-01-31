<?php
#deletegroup.php is the interface for deleteing a group
#can only be performed by admins
#includes link to group list and navigation tabs
#	Helena F Deus (hdeus@s3db.org)
	
	include('adminheader.php');

	 $group_id = $_REQUEST['group_id'];
	 $deletedgroup = s3info('group', $group_id, $db);
	
	 if($_POST['back'])
        {
               Header('Location: '.$action['listgroups']);
        }

	if($_POST['deletegroup'])
	{

		$s3ql=compact('user_id','db');
		$s3ql['delete']='group';
		$s3ql['where']['group_id']=$group_id;
		$s3ql['where']['confirm']='yes';
		$s3ql['format']='html';
		$done = S3QLaction($s3ql);

		
		ereg('<error>(.*)</error><message>(.*)</message>', $done, $s3qlout);
		
		if ($s3qlout[1]=='0') {
	
			Header('Location: '.$action['listgroups']);
			exit;
		}
		else
		{
			$message=$s3qlout[2];
		}
	}
	
	$section_num= '2';
	$action_url= $actions['deletegroup'];
	
	$delete_message= 'Delete Group Account -- '.$deletedgroup['account_uname'].' ('.$deletedgroup['account_lid'].')';
	
	$content_width='60%';
	
	$button='<input type="submit" name="deletegroup" value="Delete">&nbsp;&nbsp;&nbsp;&nbsp;<input type="submit" name="back" value="Back to Group Account List">';
	
	
?>
<!-- BEGIN top -->
<form method="POST" action="<?php echo $action_url ?>">
<table class="top" align="center">
	<tr><td>
		<table class="insidecontents" align="center" width="<?php echo $content_width ?>">
			<tr><td class="message"><br /><?php echo $message ?></td></tr>
			<tr align="center"><td colspan="2" class="current_stage"><?php echo $current_stage ?></td></tr>
		</table>
	</td></tr>
</table>
<!-- END top -->
<!-- BEGIN delete_group -->
<table class="middle" width="100%"  align="center">
	<tr><td>
		<table style="background: #E8FDFF;" width="<?php echo $content_width ?>"  align="center" border="0">
			<tr bgcolor="#80BBFF"><td align="center"><?php echo $delete_message ?></td></tr>
			<tr class="odd">
				<td class="info" align="center"></b><br />Do you really want to delete this group?<br /><br /></td>
			</tr>
		</table>
	</td></tr>
</table>
<!-- END delete_group -->
<!-- BEGIN bottom -->
<table class="bottom" width="100%"  align="center">
	<tr><td>
	<table class="insidecontents" width="<?php echo $content_width ?>"  align="center">
	<tr><td align="left"><?php echo $button ?><br /><br /></td></tr>
	</table>
	</td></tr>
</form>
</table>
<!-- END bottom -->
