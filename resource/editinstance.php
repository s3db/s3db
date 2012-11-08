<?php
	#editinstance.php is a form for changing notes of instance
	#Helena F Deus (helenadeus@gmail.com)
	ini_set('display_errors',0);
	if($_REQUEST['su3d']) {
		ini_set('display_errors',1);
	}
	if($_SERVER['HTTP_X_FORWARDED_HOST']!='') {
		$def = $_SERVER['HTTP_X_FORWARDED_HOST'];
	} else {
		$def = $_SERVER['HTTP_HOST'];
	}
	if(file_exists('../config.inc.php')) {
		include('../config.inc.php');
	} else {
		Header('Location: http://'.$def.'/s3db/');
		exit;
	}
	$key = $_GET['key'];
	#Get the key, send it to check validity

	include_once('../core.header.php');
	include 'instance.vars.php';
	
	if(!$instance_info['view']) {
		echo 'User does not have permission on this item';
	}
	if(!$instance_info['change']) {
		echo 'User does not have permission to change this item';
	} else {
		if($_POST['edit_resource'] !='') {
			$s3ql['db'] = $db;
			$s3ql['user_id'] = $user_id;
			$s3ql['edit'] = 'item';
			$s3ql['where']['item_id'] = $instance_id;
			$s3ql['set']['notes'] = $_POST['notes'];
			$done = S3QLaction($s3ql);
			$done = html2cell($done);
			#ereg('<error>(.*)</error>(.*)<message>(.*)</message>', $done, $s3qlout);
			
			if($done[2]['error_code']=='0') {
				$js = sprintf("%s\n", '<script type="text/javascript">');
				$js .= sprintf("%s\n", 'function kill_me()');
				$js .= sprintf("%s\n", '{');
				$js .= sprintf("%s\n", 'opener.window.location.reload(); self.close(); return false;');
				$js .= sprintf("%s\n", '}');
				$js .= sprintf("%s\n", '</script>');
				echo $js;
			} else {
				$message=$done[2]['message'];
			}
		}
?>
<body onload="kill_me()">
	<form action="<?php echo $action['editinstance']; ?>" method="post" autocomplete="on">
		<table border="0">
			<tr>
				<td>Editing resource #<?php echo $instance_id; ?></td>
				<td align="right">&nbsp;<font color="red"><b><?php echo $instance_info['notes']; ?></b></font></td>
			</tr>
		</table>
		<table>
			<tr>
				<td class="message"><?php echo $message; ?></td>
			</tr>
			<tr>
				<td colspan="2"><hr color="navy" size="2"></hr></td>
			</tr>
			<tr>
				<td style="color: red" colspan="2"><br /></td>
			</tr>
<?php
		echo '
			<tr>
				<td width="25">Project: </td>
				<td>'.$project_info['project_name'].'</td>
			</tr><tr>
				<td>ID: </td>
				<td>'.$instance_info['resource_id'].'</td>
			</tr><tr>
				<td>Entity</td>
				<td>'.$instance_info['entity'].'</td>
			</tr><tr>
				<td>Notes: </td>
				<td><textarea  style="background: lightyellow" rows="2" cols="30" name="notes" >'.$instance_info['notes'].'</textarea></td>
			</tr><tr>
				<td>Created On: </td>
				<td>'.$instance_info['created_on'].'</td>
			</tr><tr>
				<td>Created By: </td>
				<td>'.find_user_loginID(array('account_id'=>$instance_info['created_by'], 'db'=>$db)).'</td>
			</tr><tr>
				<td>Modified On: </td>
				<td>'.$instance_info['modified_on'].'</td>
			</tr><tr>
				<td>Modified By: </td>
				<td>'.find_user_loginID(array('account_id'=>$instance_info['modified_by'], 'db'=>$db)).'</td>
			</tr>';
?>
			<tr>
				<td colspan="2"><br /> </td>
			</tr>
			<tr>
				<td><input type="submit" name="edit_resource" value="&nbsp;&nbsp;Update&nbsp;&nbsp;"></td>
				<td><input type="button" name="delete_resource" value="&nbsp;&nbsp;Delete&nbsp;&nbsp;" onClick="window.location='<?php echo $action['deleteinstance']; ?>'"></td>
			</tr>
		</table>
	</form>
<?php
}	
?>
	