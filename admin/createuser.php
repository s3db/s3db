<?php
	#createuser.php is the interface for admins for creating users. Includes tabs.php. 
	include('adminheader.php');
	if($_POST['submit']) {
		#check if login and email inserted
		if ($_POST['account_lid']=='') {
			$message='Please indicate a loginID';
		} elseif($_POST['account_email']=='') {
			$message = 'Email is required for every account';
		} elseif($_POST['account_pwd_2'] =='') {		#check if pass1 = pass2
			$password2_required='*';
			$message='Please re-type your password to confirm';
		} elseif($_POST['account_pwd'] != $_POST['account_pwd_2']) {
			$password_required='*';
			$password2_required='*';
			$message='Re-typed password does not match';
		} else {
			$s3ql=compact('user_id','db');
			$s3ql['insert']='user';
		
			if ($_POST['public']) {
				$account_type = 'p';
			} elseif($_POST['account_groups']!='' && in_array('1', $_POST['account_groups'])) {
				$account_type = 'a';
			} else {
				$account_type = 'u';
			}
			$s3ql['where'] = array(
								'account_lid'=>$_POST['account_lid'],
								'account_uname'=>$_POST['account_uname'],
								'account_pwd'=>$_POST['account_pwd'],
								'account_pwd_2'=>$_POST['account_pwd_2'],
								'addr1'=>$_POST['addr1'],
								'addr2'=>$_POST['addr2'],
								'city'=>$_POST['city'],
								'state'=>$_POST['state'],
								'postal_code'=>$_POST['postal_code'],
								'country'=>$_POST['country'],
								'account_email'=>$_POST['account_email'],
								'account_type'=>$account_type,
								'account_phone'=>$_POST['account_phone']
							);
			if($_REQUEST['give_access']=='on' && $_REQUEST['permission_level']) {
				$s3ql['where']['permission_level']=$_REQUEST['permission_level'];
			}
			$s3ql['format']='php';
		
			$done = S3QLaction($s3ql);$msg=unserialize($done);$msg = $msg[0];
		
			#if(in_array('user_id', array_keys($done)))
			if ($msg['error_code']==0) {
				#preg_match('[0-9]', $done, $inserted_user_id);
				$inserted_user_id = $msg['user_id'];
				#insert the user in the specified groups
				$selected_groups = $_POST['account_groups'];
				if (is_array($selected_groups)) {
					foreach ($selected_groups as $group_id) {
						$s3ql=compact('user_id','db');
						$s3ql['insert']='user';
						$s3ql['where']['user_id']=$inserted_user_id;
						$s3ql['where']['group_id']=$group_id;
						$s3ql['format']='html';
						$done = S3QLaction($s3ql);
						$msg = html2cell($done);
						$msg = $msg[2];
					}
				}
				header('Location:'.$action['listusers']);
				exit;
			} else {
				$message = $msg['message'];
			}
		}
		#pass the variables to the form
		$account_lid= $_POST['account_lid'];
		$account_uname=$_POST['account_uname'];
		$addr1=$_POST['addr1'];
		$addr2=$_POST['addr2'];
		$city=$_POST['city'];
		$state=$_POST['state'];
		$postal_code=$_POST['postal_code'];
		$country=$_POST['country'];
		$account_email=$_POST['account_email'];
		$account_phone= $_POST['account_phone'];
	}
	
	include '../S3DBjavascript.php';
	include '../tabs.php';

	$edit_message='Create New User Account';
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
	
	#list all the groups where this user can make changes
	$s3ql=compact('user_id','db');
	$s3ql['select']='*';
	$s3ql['from']='groups';
	#$s3ql['where']['user_id']=$user_id;
	$groups = S3QLaction($s3ql);
	
	#make a select list 
	#if ($user_id=='1') {
	#	$group_select_list .= '<option value="1">Admin</option>';
	#}
	if(is_array($groups)) {
		foreach ($groups as $group_info) {
			if($group_info['account_id']=='3')	{ 
				$selected = " selected";
			} else {
				$selected = "";
			}
			$group_select_list .= '<option value="'.$group_info['account_id'].'" '.$selected.'>'.$group_info['account_lid'].'</option>';
		}
	}
	if($message=='') {
		$message = $default_message;
	}
?>
<!-- BEGIN top -->
<form method="POST" action="<?php echo $action['createuser']; ?>">
<!-- END top -->
	<table class="top" align="center">
		<tr>
			<td>
				<table class="insidecontents" align="center" width="60%">
					<tr>
						<td class="message"><br /><?php echo $message; ?></td>
					</tr>
				</table>
			</td>
		</tr>
	</table>
	<!-- BEGIN user_info_edit -->
	<table class="middle" width="100%"  align="center">
		<tr>
			<td>
				<table class="insidecontents" width="<?php echo $content_width ?>"  align="center" border="0">
					<tr bgcolor="#80BBFF">
						<td colspan="4" align="center"><?php echo $edit_message ?></td>
					</tr>
					<tr class="odd">
						<td class="info">Login ID<sup class="required"><?php echo $loginid_required ?></sup></td>
						<td class="info"><input name="account_lid" value="<?php echo $account_lid ?>">&nbsp;<input type="hidden" name="account_id" value="<?php echo $account_id ?>"<input type="hidden" name="account_addr_id" value="<?php echo $account_addr_id ?>"</td>
						<td class="info">Email<sup class="required"><?php echo $email_warn ?></sup></td>
						<td class="info"><input name="account_email" value="<?php echo $account_email ?>">&nbsp;</td>
					</tr>
					<tr class="even">
						<td class="info">Real Name<sup class="required"><?php echo $uname_required ?></sup></td>
						<td class="info"><input name="account_uname" value="<?php echo $account_uname ?>">&nbsp;</td>
						<td class="info">Account Type</td>
						<td ass="info"><?php echo $account_type ?></td>
					</tr>
					<tr class="odd">
						<td class="info">Password<sup class="required"><?php echo $password_required ?></sup></td>
						<td class="info"><input type="password" name="account_pwd" value="">&nbsp;</td>
						<td class="info">Re-type Password<sup class="required"><?php echo $password2_required ?></sup></td>
						<td class="info"><input type="password" name="account_pwd_2" value="">&nbsp;</td>
					</tr>
					<tr class="even">
						<td class="info">Groups</td>
						<td class="info"><select name="account_groups[]" multiple><?php echo $group_select_list ?><option value="-100"></option></select></td>
						<td class="info">Public</td>
						<td class="info"><input type="checkbox" name="public" value="public"> User cannot change password</td>
					</tr>
					<tr class="even">
					</tr>
				</table>
			</td>
		</tr>
		<tr>
			<td>
				<table class="insidecontents" width="<?php echo $content_width ?>"  align="center" border="0" width="100%"  align="center">
					<tr bgcolor="#80BBFF">
						<td colspan="4" align="center">Inherit permisions</td>
					</tr>
					<tr class="even">
						<td colspan="4" align="center">
							Users that you create can inherit permissions from you. You may specify the filter that you would like to apply to only allow certain access level to the data.
						</td>
					</tr>
					<tr class="even">
						<td><input type="checkbox" name="give_access">Give access</td>
						<td align="center">Filter: <input type="text" name="permission_level" value="NNN"></td>
						<td></td>
						<td></td>
					</tr>
				</table>
			</td>
		</tr>
	</table>
	<!-- END user_info_edit -->
	<!-- BEGIN bottom -->
	<table class="bottom" width="100%"  align="center">
		<tr>
			<td>
				<table class="insidecontents" width="<?php echo $content_width ?>"  align="center">
					<tr>
						<td align="left"><input type="submit" name="submit" value="Create User Account"><br /><br /></td>
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
