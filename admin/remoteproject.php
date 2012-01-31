<?php
	#createuser.php is the interface for admins for creating users. Includes tabs.php. 
	include('adminheader.php');
	
	
	if($_POST['submit'])
	{
		
		
		$permission_level = $_POST['view'].$_POST['change'].$_POST['add'];
		#check if login and email inserted
		if (strlen($permission_level)<3) {
			$message .= "Please select a permission level for view, change and add";
		}
		else{	
		
		$s3ql=compact('user_id','db');
		$s3ql['insert']='user';
				
		$s3ql['where'] = array('user_id'=>$_POST['remote_user_id'],
										'permission_level'=>$permission_level);

		
		
		$done = S3QLaction($s3ql);
		
		ereg('<error>([0-9]+)</error>.*<(user_id|message)>(.*)</(user_id|message)>', $done, $s3qlout);
		
		
		if ($s3qlout[1]=='0') {
			#preg_match('[0-9]', $done, $inserted_user_id);
			
			$inserted_user_id = $s3qlout[4];
			#insert the user in the specified groups
		}
		else {
			$message .= $s3qlout[3];
		}
		
		if($message==''){
		header('Location:'.$action['listusers']);
		exit;
		}

		}	
		
					
		#pass the variables to the form
	
		$remote_user_id= $_POST['remote_user_id'];
		$view=$remote_user_info['view'];
		$change=$remote_user_info['change'];
		$add=$remote_user_info['add'];

	
	}
            
		
	
	include '../S3DBjavascript.php';
	include '../tabs.php';

	$edit_message='Insert Remote User';
	$content_width='70%';
	$account_status='Active';
	$account_type='User';
	$checked='checked';
	$loginid_required='*';
	$uname_required='*';
	$password_required='*';
	$password2_required='*';
	$default_message='* required';
	$email_warn = '*';
	
	
	if ($message=='') {
		$message = $default_message;
		}
	

?>
<!-- BEGIN top -->
<form method="POST" action="<?php echo $action['remoteuser']; ?>">
<!-- END top -->

<table class="top" align="center">
	<tr><td>
		<table class="insidecontents" align="center" width="60%">
			<tr><td class="message"><br /><?php echo $message; ?></td></tr>
			
			
		</table>
	</td></tr>
</table>
<!-- BEGIN user_info_edit -->

<table class="middle" width="100%"  align="center">
	<tr><td>
		<table class="insidecontents" width="<?php echo $content_width ?>"  align="center" border="0">
			<tr bgcolor="#80BBFF"><td colspan="4" align="center"><?php echo $edit_message ?></td></tr>
			<tr><td colspan="4" align="left">Remote users are users that exist in other deployments of S3DB and need to access data on this deployment. Specify a remote user either by concatenating deployment_id and user_id (for example, D45/U33) or by concatenating URL with user_id (http://s3db.org/U4)</td></tr>
			<tr class="odd">
				<td class="info">User ID<sup class="required"><?php echo $loginid_required ?></sup></td>
				<td class="info"><input name="remote_user_id" value="http://" size="60">&nbsp;</td>	
				<td class="info">Permission Level<sup class="required"></sup></td>
				<td class="info">
				<table class="insidecontents" >
				<tr class="odd">
				<td></td>
				<td class="info">View</td>
				<td class="info">Change</td>
				<td class="info">Add</td>
				</tr>
				<tr class="even">
				<td class="info">0</td>
				<td><input type="radio" name="view" value="0"></td>
				<td><input type="radio" name="change" value="0" selected></td>
				<td><input type="radio" name="add" value="0" selected></td>
				</tr>
				<tr class="odd">
				<td class="info">1</td>
				<td><input type="radio" name="view" value="1"></td>
				<td><input type="radio" name="change" value="1"></td>
				<td><input type="radio" name="add" value="1"></td>
				</tr>
				<tr class="even">
				<td class="info">2</td>
				<td><input type="radio" name="view" value="2" selected></td>
				<td><input type="radio" name="change" value="2"></td>
				<td><input type="radio" name="add" value="2"></td>
				</tr>
				</table>
				
				</td>	
				
			</tr>
			
		</table>
	</td></tr>
</table>
<!-- END user_info_edit -->
<!-- BEGIN bottom -->
<table class="bottom" width="100%"  align="center">
	<tr><td>
	<table class="insidecontents" width="<?php echo $content_width ?>"  align="center">
	<tr><td align="left"><input type="submit" name="submit" value="Create User Account"><br /><br /></td></tr>
	</table>
	</td></tr>
</form>
</table>
<!-- END bottom -->
<?php
include '../footer.php';
?>