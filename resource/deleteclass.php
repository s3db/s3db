<?php
	#deleterule.php is a form for changing rules, classes or not
	#Includes links to edit and delete resource, as well as edit rules
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
	#just to know where we are...
	$thisScript = end(explode('/', $_SERVER['SCRIPT_FILENAME'])).'?'.$_SERVER['argv'][0];

	$key = $_GET['key'];

	#Get the key, send it to check validity
	include_once('../core.header.php');

	if($key) {
		$user_id = get_entry('access_keys', 'account_id', 'key_id', $key, $db);
	} else {
		$user_id = $_SESSION['user']['account_id'];
	}
	#Universal variables
	$project_id = $_REQUEST['project_id'];
	$class_id = ($_REQUEST['collection_id']!='')?$_REQUEST['collection_id']:$_REQUEST['class_id'];
	$rule_id = $_REQUEST['rule_id'];

	#info about resource
	$resource_info = URIinfo('C'.$class_id, $user_id, $key, $db);
	$uni = compact('db', 'user_id');
	if($class_id=='') {
		echo "Please indicate the resource ou intend to delete (for example &collection_id=xxx)";
		exit;
	}

	if(!$resource_info['delete']) {		#check projectAcl
		echo "User cannot delete this resource!!";
		exit;
	} else {
		if($_POST['delete'] != '' && $class_id!='') {
			$s3ql=$uni;
			$s3ql['delete'] = 'collection';
			$s3ql['where']['collection_id'] = $class_id;
			if($s3ql['flag']=='unlink') {
				$s3ql['where']['project_id']=$project_id;
				$s3ql['flag']='unlink';
			} else {
				$s3ql['flag']='all';
			}
			$s3ql['format']='html';
			$done = S3QLaction($s3ql);
			#ereg('<error>([0-9]+)</error>(.*)<message>(.*)</message>', $done, $s3qlout);
			$msg = html2cell($done);
			if($msg[2]['error_code']==0) {
				Header('Location:'.$action['project']); #since resource has became extinct, redirect to the project's page
				exit;
			} else {
				echo $msg[2]['message'];
			}
		}
		#include all the javascript functions for the menus...
		include('../S3DBjavascript.php');
	
		#and the short menu for the resource script
		#$rule_info = URIinfo('R'.$resource_info['rule_id'], $user_id, $key, $db);
		$message = "Do you really want to delete this class? <br /><br /><b>All the resources of ".$resource_info['entity'].", rules and statements associated with them will be deleted!!!</b>";
?>
<table class="middle" width="100%"  align="center">
	<tr>
		<td>
			<table class="insidecontents" width="90%"  align="center" border="0">
				<tr>
					<td class="message" colspan="9"><br /></td>
				</tr>
				<tr bgcolor="#80BBFF">
					<td align="center" colspan="8">Delete Class<input type="hidden" name="rule_id" value="7"></td>
				</tr>
				<tr class="odd">
					<?php echo '<td class="message" colspan="8">'.$message.'</b><br /><br /></td>'; ?>
				</tr>
				<tr class="even">
					<td width="5%">Collection ID</td>
					<td  width="5%">Owner</td>
					<td  width="15%">Create Date</td>
					<td width="10%">Name</td>
					<!-- <td  width="10%">Verb</td>
					<td  width="10%">Object</sup></td> -->
					<td  width="20%">Notes</td>
				</tr>
				<tr class="odd">
<?php
	echo '
					<td width="5%"><b>'.$resource_info['collection_id'].'</b></td>
					<td  width="5%"><b>'.find_user_loginID(array('account_id'=>$resource_info['created_by'], 'db'=>$db)).'</b></td>
					<td  width="15%"><b>'.$resource_info['created_on'].'</b></td>
					<td width="10%"><b>'.$resource_info['entity'].'</b></td>
					<!-- <td  width="10%"><b>'.$rule_info['verb'].'</b></td>
					<td  width="10%"><b>'.$rule_info['object'].'</sup></td> -->
					<td  width="20%">'.$resource_info['notes'].'<b></b></td>';
?>
					<td>
						<select name="flag" size="5">
							<option value="all" selected>Delete this collection and all dependencies</option>
							<option value="unlink">Unlink this collection from project</option>
							<!-- <option value="all">Delete this class and all dependences</option> -->
						</select>
					</td>
				</tr>
			</table>
		</td>
	</tr>
</table>
<table class="bottom" width="100%"  align="center">
	<tr>
		<td>
			<table class="insidecontents" width="90%" align="center" border="0">
				<tr>
					<td align="left">
						<form name= "deleterule" action = "<?php echo $action['deleteclass']; ?>" method="POST">
							<input type="submit" name="delete" value="OK">&nbsp;&nbsp;&nbsp;&nbsp;
							<input type="button" name="cancel" value="Cancel" onClick="window.location='<?php echo $action['resource']; ?>'"><br /><br />
						</form>
					</td>
				</tr>
			</table>
		</td>
	</tr>
</table>
<?php
}
?>
