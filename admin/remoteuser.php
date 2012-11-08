<?php
	#createuser.php is the interface for admins for creating users. Includes tabs.php. 
	include('adminheader.php');
	if($_POST['submit']) {
		$prot=$_REQUEST['protocol'];
		$email=$_REQUEST['email'];
		$auth=$_REQUEST['authority'];
		
		if($auth=='' || $email=='') {
			$message .= "Please input an authority and a username or email";
		} else {
			$user_uri =  (($prot!='http')?$prot.':':'').$auth.':'.$email;
			$s3ql=compact('user_id','db');
			$s3ql['insert']='user';
			#$s3ql['where'] = array('user_id'=>$_POST['remote_user_id'],'permission_level'=>$permission_level);
			$s3ql['where'] = array('user_id'=>$user_uri);
			$s3ql['format']='php';
			$done = S3QLaction($s3ql);

			if($done) {
				$msg=unserialize($done);$msg=$msg[0];
			}
			if($msg['error_code']=='0') {
				#preg_match('[0-9]', $done, $inserted_user_id);
				$inserted_user_id = $s3qlout[4];
				#insert the user in the specified groups
			} else {
				$message .= $msg['message'];
			}
		
			if($message=='') {
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
	if($message=='') {
		$message = $default_message;
	}
?>
<body onload="load()">
	<script type="text/javascript" src="../js/login.js"></script>
	<script type="text/javascript" src="../js/wz_tooltip.js"></script>
	<!-- BEGIN top -->
	<form method="POST" action="<?php echo $action['remoteuser']; ?>">
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
						<tr>
							<td colspan="4" align="left">Remote users are users that are have account in places as diverse as Google, LDAP directory or even in another deployment of S3DB. User URI has the following syntax<BR><b><i>protocol</i>: <i>authority</i>: <i>username or email.</i></b></td>
						</tr>
						<tr class="odd">
							<td id="load" colspan="4"></td>
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
	</form>
	<!-- END bottom -->
<?php
	include '../footer.php';
?>