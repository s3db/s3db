<?php
	#editproject displays general information on the project;
	#Includes links to resource pages, xml and rdf export 
	#Helena F Deus (helenadeus@gmail.com)
	 ini_set('display_errors',0);
	if($_REQUEST['su3d'])
	ini_set('display_errors',1);
	
	if($_SERVER['HTTP_X_FORWARDED_HOST']!='')
			$def = $_SERVER['HTTP_X_FORWARDED_HOST'];
	else 
			$def = $_SERVER['HTTP_HOST'];
	
	if(file_exists('../config.inc.php'))
	{
		include('../config.inc.php');
	}
	else
	{
	
		Header('Location: http://'.$def.'/s3db/');
		exit;
	}

$key = $_GET['key'];


#Get the key, send it to check validity

include_once('../core.header.php');

if($key)
	$user_id = get_entry('access_keys', 'account_id', 'key_id', $key, $db);
	else
	$user_id = $_SESSION['user']['account_id'];

#Universal variables
$sortorder = $_REQUEST['orderBy'];
$direction = $_REQUEST['direction'];
$project_id = $_REQUEST['project_id'];
$project_info = URIinfo('P'.$project_id, $user_id, $key, $db);
#$acl = find_final_acl($user_id, $project_id, $db);
$uni = compact('db', 'acl','user_id','key', 'project_id', 'dbstruct');

#relevant extra arguments
if($_REQUEST['key'])
	$key_arg = '&key='.$_REQUEST['key'];

#$args = '?project_id='.$_REQUEST['project_id'].$key_arg;
#Define the page actions
include('../webActions.php');
#echo '<pre>';print_r($project_info);
if ($project_id=='')
	{
		echo "Please specify a project_id";
		exit;
	}
elseif(!$project_info['change'])
{		echo "User cannot change this project. Your permission level on this project is ".$project_info['acl'];
		exit;
}
else
{
	
	
	
	if ($_POST['add_users']!='') { #on pressing this button, inserrt the selected users in the project_acl table with permission level zero
		
		if (is_array($_POST['users'])) {
			foreach ($_POST['users'] as $key=>$account_id) {
				
				
				$s3ql=compact('user_id','db');
				#$s3ql['edit']='project';
				$s3ql['insert']='user';
				$s3ql['where']['project_id']=$project_id;
				$s3ql['where']['user_id']=$account_id;
				if($user_id!=$account_id)
				$s3ql['where']['permission_level'] = '---';
				else
				$s3ql['where']['permission_level'] = 'YYY';

				#echo '<pre>';print_r($_POST);
				
				$done = S3QLaction($s3ql);
				
			}
		}
			elseif ($_POST['other_user_id']!='') {
				#check user_id validity
				$user_info = get_info('account',$_POST['other_user_id'], $db); 
				
				if (!is_array($user_info)) {
				echo "This user is not valid";

				}
				else {
					$s3ql=compact('user_id','db');
					#$s3ql['edit']='project';
					$s3ql['delete']='user';
					$s3ql['where']['project_id']=$project_id;
					$s3ql['where']['user_id']=$_POST['other_user_id'];
					#$s3ql['set']['permission_level'] = '0';
					
					$done = S3QLaction($s3ql);
					
			}
		}
		
		
	}

	if ($_POST['remove_users']!='') { #on pressing this button, inserrt the selected users in the project_acl table with permission level zero
		
		
		if (is_array($_POST['users'])) {
			foreach ($_POST['users'] as $key=>$other_account_id) {
				
				$s3ql=compact('user_id','db');
				#$s3ql['edit']='project';
				$s3ql['delete']='user';
				$s3ql['where']['project_id']=$project_id;
				$s3ql['where']['user_id']=$other_account_id;
				#$s3ql['set']['permission_level'] = '-1';
				
				#echo '<pre>';print_r($s3ql);
				$done = S3QLaction($s3ql);
				#echo $done;exit;
			}
		}
		elseif ($_POST['other_user_id']!='') {
				#check user_id validity
				$user_info = get_info('user',$_POST['other_user_id'], $db); 
				
				if (!is_array($user_info)) {
				echo "<font color='red'>User_id is not valid</font>";

				}
				else {
					$s3ql=compact('user_id','db');
					$s3ql['edit']='project';
					$s3ql['where']['project_id']=$project_id;
					$s3ql['where']['user_id']=$_POST['other_user_id'];
					$s3ql['set']['permission_level'] = '---';
			}
		}
		
		
	}
	
	 
	if($_POST['back'])
	{
		Header('Location: '.$action['project']);
	}
	
	
	#$shared_users = list_shared_users($uni);
	$s3ql=compact('user_id','db');
	$s3ql['select']='*';
	$s3ql['from']='users';
	$s3ql['where']['project_id']=$project_id;
#echo '<pre>';print_r($s3ql);
	$shared_users = S3QLaction($s3ql);
	
#echo '<pre>';print_r($shared_users);	
#exit;
	if($_POST['submit'])
	{
		$s3ql=compact('user_id','db');
		$s3ql['insert'] = 'user';
		$s3ql['where']['project_id'] = $project_id;

		#edit one user at a time
		
		if (is_array($shared_users))
		{
		foreach($shared_users as $user)
		{
		
		
		#$permLevel = $_POST['view_'.base64_encode($user['account_id'])].$_POST['edit_'.base64_encode($user['account_id'])].$_POST['add_data_'.base64_encode($user['account_id'])];
		#if(strlen($permLevel)!='3')
		#	$message .= 'Please select 3 values for permission on user '.$user['account_id'].': view, change and add_data';
		#$account_id = $user['account_id'];
		
		$account_id = $user['account_id'];
		if(!is_numeric($account_id))
		$account_id = base64_encode($user['account_id']);
		
		$permLevel = $_POST['view_'.$account_id].$_POST['edit_'.$account_id].$_POST['add_data_'.$account_id];
		$permLevel = str_replace(array('0','1','2'), array('n','s','y'), $permLevel);
		if($permLevel!='')
			{
			if(!is_numeric($account_id)) $account_id = base64_decode($account_id); 
			$s3ql['where']['user_id'] = $account_id;
			$s3ql['where']['permission_level'] = $permLevel;
			$s3ql['format'] = 'php';
			$done = S3QLaction($s3ql);$msg=unserialize($done);$msg = $msg[0];
			
			#echo $done;
			#exit;
			#ereg('<error>([0-9]+)</error>.*<message>(.*)</message>', $done, $s3qlout);
			#$msg=html2cell($done);$msg = $msg[2];
			#if($msg['error_code']!='0')
			#		$message .= $msg['message'];
			
			#}
		
		}
		#echo $message;exit;
		
		
		
		}
		# now update project info
		$changeable = array('project_name','project_description');
		
		$s3ql=compact('user_id','db');
		$s3ql['edit']='project';
		$s3ql['where']['project_id']=$project_id;

		foreach($changeable as $toChange)
			if(in_array($toChange, array_keys($_POST)))
			$s3ql['where'][$toChange] = nl2br($_POST[$toChange]);
		
		#echo '<pre>';print_r($s3ql);
		$done = S3QLaction($s3ql);
		#echo $done;
		#exit;
		$msg=html2cell($done);$msg = $msg[2];
		#ereg('<error>([0-9]+)</error>.*<message>(.*)</message>', $done, $s3qlout);
		
		if($msg['error_code']!='0' && $msg['error_code']!='3')#if user did not update anything, just leave it as is
			{$message .= substr($s3qlout[2], 0, strpos($msg['message'], "."));	
			}
		
		if($message=='')
				{
				Header('Location:'.$action['project']);
				exit;
				}
				else {#if message is not empty, display it + a button to go back to projec
					
					$message .= '<BR><input type = "button" value = "Back to Project" onclick="window.location=\''.$action['project'].'\'">';
				}
	
	}
	
}		
include_once('../S3DBjavascript.php');

#echo '<pre>';print_r($shared_users);
	if(is_array($shared_users) && !empty($shared_users))
	{
		for ($i=0; $i < count($shared_users); $i++) {
			if(!is_numeric($shared_users[$i]['account_id']))
			$shared_users[$i]['account_id']=base64_encode($shared_users[$i]['account_id']);
				
		}		
		
		$datagrid = render_elements($shared_users, $acl, array('Login', 'User Name', 'Access Control List'), 'account_acl');
		
		
	}
	

	

}


?>
<form name='insertAcl' action= "<?php echo $action['editproject'] ?>" method="POST">
<table class="middle" width="100%" align="center">
	<tr><td>
		<table class="insidecontents" width="80%" align="center" border="0">
			<tr><td class="message" colspan="9"><?php echo $message ?></td></tr>
			<tr bgcolor="#80BBFF"><td colspan="4" align="center">Edit Project</td></tr>
			<tr class="odd">

				<td class="info" width="20%">Project Name</td>
				<td><input name="project_name" style="background: lightyellow"  value= "<?php echo $project_info['project_name'] ?>">&nbsp;
				</td>
				<td class="info" width="20%">Project Owner</td>
				<td class="info" width="25%"><b><?php echo $user_lid = find_user_loginID(array('account_id'=>$project_info['project_owner'], 'db'=>$db)); ?><b></td>
			</tr>
			<tr class="even">
				<td class="info" width="20%">Project Description<sup class="required"><?php echo $project_description_required ?></sup></td>
				<td class="info" colspan="3"><textarea name="project_description" style="background: lightyellow" rows="3" cols="60"><?php echo $project_info['project_description'] ?></textarea></td>
			</tr>
			<tr><td><br /></td></tr>
			<?php
			if($datagrid!='')
			echo '
			<tr bgcolor="#80BBFF"><td colspan="4" align="center">Users that share groups</td></tr>
				
			</tr>
			<tr>
				<td class="info" colspan="4">'.$datagrid.'&nbsp;</td>
				
			</tr>';
			?>
			<tr bgcolor="#80BBFF"><td colspan="4" align="center">Add other users to the list</td></tr>
			<tr class="even">
				<td class="info" colspan="4">
				<table border="1px" width="100%" align="center" cellspacing="0" cellpadding="4" class="datagrid">
				<th width="15%" align="left">User ID</th>
				<th width="20%" align="left">User Name</th>
				<th width="20%" align="left"></th>
			
			<tr class="odd">
				<td width="15%" align="left"><input type="text" name="other_user_id" id="user_id"></td>
				<td width="20%" align="left"><?php user_drop_down_list('users[]', $db, $user_id) ?></td>
				<td width="20%" align="left"><input type="submit" name="add_users" value="Add these users"><input type="submit" name="remove_users" value="Remove these users"><br /></td>
				</tr>
			
				</table>
			</td></tr>
			
			
		</table>
		
			
<tr>
			<td width="10%">
			<nobr><input type="submit" name="submit" value="Update Project">&nbsp;&nbsp;&nbsp;<input type="button" name="back" value="Back to Project" onClick="window.location='<?php echo $action['project']?>'">&nbsp;&nbsp;&nbsp;<input type="button" name="delete" value="Delete Project" onclick="window.location='<?php echo $action['deleteproject']?>'"></nobr>
			</td>
			</tr>			
</table>

</form>

<?php
include('../footer.php');
?>