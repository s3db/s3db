<?php
	#createclass.php creates a new resoruce class
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
	$project_id = $_REQUEST['project_id'];
	$project_info = URIinfo('P'.$project_id, $user_id, $key, $db);
	#$acl = find_final_acl($user_id, $project_id, $db);
	#relevant extra arguments
	#$args = '?key='.$_REQUEST['key'].'&project_id='.$_REQUEST['project_id'];
	
	include('../webActions.php');
	
	if($_REQUEST['project_id'] == '') {		#CHECK PERMISSION
		echo 'You are not working with any project yet.';
		exit;
	} elseif(!$project_info['add_data']) {		#CHECK USER PERMISSION
		echo 'You cannot create collections on this project.';
		exit;
	} else {		#RUN THE REST IN CASE USER HAS PERMISSION
		#FIGURE OUT THE USRES OF PROJECT AND INHERITED PERMISSIONS. SINCE THIS IS A NEW CLAS, PERMISION ARE INHERITED FROM PROJECT
		$s3ql=compact('user_id','db');
		$s3ql['from']='users';
		#$s3ql['where']['project_id']=$project_id;
		$s3ql['format']='html';
		$users=S3QLaction($s3ql);
		$new = 1;
		$uid = 'C'.$class_id;
		$how_many = count($users);
		$aclGrid = aclGrid(compact('user_id', 'db', 'users', 'new','uid','how_many'));

		if($_POST['new_resource']) {
			$s3ql=compact('db', 'user_id');
			$s3ql['insert'] = 'collection';
			$s3ql['where']['project_id'] = $_REQUEST['project_id'];
			if($_POST['entity']!='')
			$s3ql['where']['entity'] = htmlentities($_POST['entity'], ENT_QUOTES);
			if($_POST['notes']!='')
			$s3ql['where']['notes'] = htmlentities($_POST['notes'], ENT_QUOTES);
			$s3ql['format']='php';
			$done = S3QLaction($s3ql);
			$msg = unserialize($done);
			#$msg = html2cell($done);
			#ereg('<error>(.*)</error>(.*)<(message|collection_id)>(.*)</(message|collection_id)>', $done, $s3qlout);
			#if the class was created
			if($msg[0]['error_code']!='0') {
				$message .=$msg[0]['message'];
			} else {
				$class_id = $msg[0]['collection_id'];
				//now check if an ontology term was used;
				if($_REQUEST['entity_bioportal_full_id']!='') {
					createBioportalLink('entity', 'C', $class_id, $db, $user_id);
				}
				if(is_array($users)) {
					$s3ql=compact('user_id','db');
					$s3ql['insert']='user';
					$s3ql['where']['collection_id']=$class_id;
				
					#NOW ADD THE SELECTED USERS TO THE CLASS:
					#Are there users in project? They will inherit permissions directly,unless otherwise specified
				
					foreach($users as $ind=>$user) {
						$v = ($_POST['view_'.$user['account_id']]=='m')?'':$_POST['view_'.$user['account_id']];
						$e = ($_POST['edit_'.$user['account_id']]=='m')?'':$_POST['edit_'.$user['account_id']];
						$p = ($_POST['add_data_'.$user['account_id']]=='m')?'':$_POST['add_data_'.$user['account_id']];
						$permLevel = $v.$e.$p;
						if(strlen($permLevel)=='3')	{
							#$message .= 'Please select 3 values for permission on user '.$user['account_id'].': view, change and add_data';
							$s3ql['where']['user_id']=$user['account_id'];
							$s3ql['where']['permission_level']=$permLevel;
							$s3ql['format']='html';
							$done=S3QLaction($s3ql);
							$msg = html2cell($done);
						}
						#ereg('<error>([0-9]+)</error>.*<message>(.*)</message>', $done, $s3qlout1);
						#if($msg[2]['error_code']!='0') {
						#	$message.=$msg[2]['message'];
						#}
					}
				}
				if($message=='') {
					Header('Location:'.$action['resource'].'&class_id='.$class_id);
					exit;
				}
			}
		}
		include '../S3DBjavascript.php';
?>
<form name="insertAcl" method="POST" action="<?php echo $action['createresource']; ?>">
	<table class="create_resource" width="70%">
		<tr>
			<td class="message" colspan="9"><?php echo $message; ?></td>
		</tr>';
		<script type="text/javascript">
			// Grab the specific onto widget scripts we need and fires at start event
			jQuery.getScript("http://bioportal.bioontology.org/javascripts/JqueryPlugins/autocomplete/crossdomain_autocomplete.js", function(){
				formComplete_setup_functions();
			});
		</script>
		<tr bgcolor="#80BBFF">
			<td colspan="9" align="center">Create New Class</td>
		</tr>
		<tr>
			<td colspan="9" align="center">Class names must be unique on the project.</td>
		</tr>
		<tr class="odd" align="center">
			<td  width="10%">Owner</td>
			<td width="10%">Entity<sup class="required">*</sup></td>
			<td  width="20%">Notes</td>
			<td  width="10%">Action</td>
		</tr>
		<tr valign="top" align="center">
<?php
			echo '<td  width="10%">'.find_user_loginID(array('account_id'=>$user_id, 'db'=>$db)).'</td>';
			echo '<td width="10%"><input name="entity" class="bp_form_complete-all-name"></td>';
			echo '<td  width="20%"><textarea name="notes"  style="background: lightyellow" rows="2" cols="30" ></textarea></td>';
			echo '<td width="10%" align="center"><input type="submit" name="new_resource" value="Create"></td>';
?>
		</tr>
		<tr>
			<td colspan="9" align="center"><BR><BR></td>
		</tr>
		<tr bgcolor="#80BBFF">
			<td colspan="9" align="center">Manage permissions inherited from class</td>
		</tr>
		<tr>
			<td colspan="9" align="center">Use this section to manage permissions that individual users will have on the class and all dependencies. These will by default be inherited by every instance of the class.</td>
		</tr>
		<tr>
			<td colspan="9" align="center">
<?php 
		echo $aclGrid;
	}
?>
			</td>
		</tr>
	</table>
</form>
