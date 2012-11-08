<?php
	#deleteinstance.php is the interface for deleting an instance
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
	if($key) {
		$user_id = get_entry('access_keys', 'account_id', 'key_id', $key, $db);
	} else {
		$user_id = $_SESSION['user']['account_id'];
	}

	#$instance_id = ($_REQUEST['item_id']!='')?$_REQUEST['item_id']:$_REQUEST['instance_id'];
	#$instance_info = URIinfo('I'.$instance_id, $user_id, $key, $db);#get_info('instance', $instance_id, $db);
	
	#$project_id = $_REQUEST['project_id'];
	#$project_info = get_info('project', $project_id, $db);
	
	$class_id = $resource_info['resource_class_id'];
	if($class_id =='') {
		$entity = $instance_info['entity'];
		$class_id = resourceClassID4Instance(compact('project_id', 'entity', 'db'));
	}

	if(!$instance_info) {
		echo 'Item does not exist';
		exit;
	}

	if(!$instance_info['delete']) {
		echo 'User does not have permission to change this instance';
		exit;
	} else {
		if($_POST['delete_resource'] !='') {
			$s3ql['user_id'] = $user_id;
			$s3ql['db'] = $db;
			$s3ql['delete'] = 'item';
			$s3ql['where']['item_id'] = $instance_id;
			$s3ql['flag']='all';
			#$s3ql['where']['confirm'] = 'yes';
			$done = S3QLaction($s3ql);
			$done = html2cell($done);
			#ereg('<error>(.*)</error>.*<message>(.*)</message>', $done, $s3qlout);
			
			if($done[2]['error_code']=='0') {
				$js = sprintf("%s\n", '<script type="text/javascript">');
				$js .= sprintf("%s\n", 'function kill_me()');
				$js .= sprintf("%s\n", '{');
				$js .= sprintf("%s\n", ' self.close(); return false;');
				#$js .= sprintf("%s\n", '        self.close(); return false;');
				$js .= sprintf("%s\n", '}');
				$js .= sprintf("%s\n", '</script>');
				echo $js; #send this bit of javascript to the browser
			} else {
				$message .= $s3qlout[3];
			}
		}
?>
<body onload="kill_me()">
	<form action="<?php echo $action['deleteinstance']; ?>" method="post" autocomplete="on">
		<table border="0">
			<tr>
				<td><b>Deleting</b> resource #<?php echo $instance_id; ?></td><td align="right">&nbsp;<font color="red"><b><?php echo $instance_info['notes']; ?></b></font></td>
			</tr>
		</table>
		<table>
			<tr>
				<td colspan="2"><hr color="navy" size="2"></hr></td>
			</tr>
			<tr>
				<td style="color: red" colspan="2"></td>
			</tr>
			<tr>
				<td style="color: red" colspan="2">Attention: All statements withing this instance will become inaccessible</td>
			</tr>
<?php
		echo '
			<tr>
				<td width="25" style="color: red" colspan="2"></td>
			</tr><tr>
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
				<td style="color: red" >'.$instance_info['notes'].'</td>
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
				<td>
					<input type="submit" name="delete_resource" value="&nbsp;&nbsp;Delete&nbsp;&nbsp;">
				</td>
			</tr>
		</table>
	</form>
<?php
	}	
?>