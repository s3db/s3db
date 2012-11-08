<?php
	#project_page displays general information on the project;
	#Includes links to resource pages, xml and rdf export 
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
	
	if($key) {
		$user_id = get_entry('access_keys', 'account_id', 'key_id', $key, $db);
	} else {
		$user_id = $_SESSION['user']['account_id'];
	}
	$args = '?key='.$_REQUEST['key'];

	include('../webActions.php');

	#CREATE THE HEADER AND SET THE TPL FILE
	$s3ql=compact('user_id','db');
	$s3ql['select']='*';
	$s3ql['from']='groups';
	$s3ql['where']['user_id']=$user_id;
	#$s3ql['format']='html';
	if ($_REQUEST['orderBy']!='') { 
		$s3ql['order_by'] = $_REQUEST['orderBy'].' '.$_REQUEST['direction'];
	}
	$groups = S3QLaction($s3ql);
	#$groups = $done;
	
	$s3ql=compact('user_id','db');
	$s3ql['select']='*';
	$s3ql['from']='users';
	#$s3ql['where']['group_id']=$group_info['account_id'];
	if ($_REQUEST['orderBy']!='') {
		$s3ql['order_by'] = $_REQUEST['orderBy'].' '.$_REQUEST['direction'];
	}
	$users = S3QLaction($s3ql);
	$shared_users = $users;
	if(is_array($shared_users) && is_array($groups)) {
		foreach ($groups as $group_info) {
			if($group_info['account_uname']!='Admin') {
				$group_info['account_type']='g';
				$group_info['account_uname'] .= ' (group)';
				array_push($shared_users,$group_info);
			}
		}
	}
	if(is_array($shared_users) && !empty($shared_users)) {
		$shared_users = array_combine(range(0,count($shared_users)-1), $shared_users);
		$datagrid = render_elements($shared_users, $acl, array('Login', 'User Name', 'Access Control List'),'account_acl');
	}
	
	#ACTION FOR PRESSING THE SUBMIT BUTTON
	if($_POST['create_project']!='') {
		$s3ql=compact('user_id','db');
		$s3ql['insert'] = 'project';
		if($_POST['project_name']!='')
		$s3ql['where']['project_name'] = htmlentities($_POST['project_name'], ENT_QUOTES);
		if($_POST['project_description']!='')
		$s3ql['where']['project_description'] = htmlentities($_POST['project_description'], ENT_QUOTES);
		$s3ql['format']='html';
		
		#run the query
		$done = S3QLaction($s3ql);
		$msg = html2cell($done);
		$msg=$msg[2];
		#get the resulting project_id, if the query worked
		
		#preg_match('/[0-9]+/', $done, $project_id);
		$project_id = $msg['project_id'];
		
		#project was created, check for user post
		#if(ereg('<error>([0-9]+)</error>.*<project_id>(.*)</project_id>', $done, $s3qlout))
		if($msg['error_code']==0) {
			$project_id = $msg['project_id'];
			if(is_array($shared_users)) {
				foreach($shared_users as $i=>$value) {
					$account_id = $shared_users[$i]['account_id'];
					$permission_level = $_POST['view_'.$account_id].$_POST['edit_'.$account_id].$_POST['add_data_'.$account_id];
					if($permission_level!='') {
						if(strlen($permission_level)!='3') {
							$message .="Please provide a valid permission level for view, change and add_data on user ".$shared_users[$i]['account_lid'];
						} elseif($permission_level!='---' && $permission_level!='mmm') {
							$s3ql=compact('user_id','db');
							#$s3ql['edit'] = 'project';
							$s3ql['insert']='user';
							$s3ql['where']['project_id'] = $project_id;
							$s3ql['where']['user_id'] = $account_id;
							$s3ql['where']['permission_level'] = $permission_level;
							$s3ql['format']='html';
							$done = S3QLaction($s3ql);
							$msg = html2cell($done);
							$msg = $msg[2];
						}
					}
					$permission_level='';
				}
			}
			Header('Location:'.$action['project'].'&project_id='.$project_id);
			exit;
		} else {
			#ereg('<error>([0-9]+)</error>.*<message>(.*)</message>', $done, $s3qlout);
			echo $msg['message'];
		}
	}
	include_once('../S3DBjavascript.php');	
?>
<form name='insertAcl' action= "<?php echo $action['createproject'] ?>" method="POST">
	<table class="middle" width="100%" align="center">
		<tr>
			<td>
				<table class="insidecontents" width="80%" align="center" border="0">
					<tr bgcolor="#80BBFF">
						<td colspan="4" align="center">Create New Project</td>
					</tr>
					<tr class="odd">
						<td class="info" width="20%">Project Name</td>
						<td><input name="project_name" style="background: lightyellow"  value= "<?php echo $project_name ?>">&nbsp;</td>
						<td class="info" width="20%">Project Owner</td>
						<td class="info" width="25%"><b><?php echo $user_lid = find_user_loginID(array('account_id'=>$user_id, 'db'=>$db)); ?><b></td>
					</tr>
					<tr class="even">
						<td class="info" width="20%">Project Description<sup class="required"><?php echo $project_description_required ?></sup></td>
						<td class="info" colspan="3"><textarea name="project_description" style="background: lightyellow" rows="3" cols="60"><?php echo $project_description ?></textarea></td>
					</tr>
					<tr>
						<td><br /></td>
					</tr>
<?php
	if($datagrid!='') {
		echo '
					<tr bgcolor="#80BBFF">
						<td colspan="4" align="center">Users that share groups</td>
					</tr>
					<tr>
						<td class="info" colspan="4">'.$datagrid.'&nbsp;</td>
					</tr>';
	}
?>
					<tr>
						<td><br /><input type="submit" name="create_project" value="Create Project"></td>
					</tr>
				</table>
			</td>
		</tr>
	</table>
</form>
<?php
	include('../footer.php');
?>