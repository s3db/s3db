<?php
#editproject displays general information on the project;
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
	$project_id = $_GET['project_id'];
	$project_info = URIinfo('P'.$project_id, $user_id, $key, $db);
	#$acl = find_final_acl($user_id, $project_id, $db);
	$uni = compact('db', 'acl','user_id','key', 'project_id', 'dbstruct');

	#relevant extra arguments
	#$args = '?key='.$_REQUEST['key'].'&project_id='.$_REQUEST['project_id'];
	
	#Define the page actions
	include('../webActions.php');

	if($project_id=='') {
		echo "Please specify a project_id";
		exit;
	} elseif(!$project_info['delete']) {		
		echo "User cannot change this project.";
		exit;
	} else {
		#CREATE BOTTLENECKS TO PREVENT UNAUTHORIZED USERS FROM ENTERING A PROJECT PAGE
		if($_POST['back']) {
			Header('Location: '.$action['project']);
		}
		if($_POST['deleteproject']!='') {
			$s3ql = compact('db','user_id');
			$s3ql['delete'] = 'project';
			$s3ql['where']['project_id'] = $project_id;
			#$s3ql['where']['confirm'] = 'yes';
			$s3ql['flag']=$_POST['flag'];
			$s3ql['format']='html';
			$done = S3QLaction($s3ql);
			$done=html2cell($done);
			
			#ereg('<error>([0-9]+)</error>(.*)<message>(.*)</message>', $done, $s3qlout);
			#if(delete_projects($deletedproject['project_id'], ''))
			
			if($done[2]['error_code']=='0') {
				Header('Location: '.$action['listprojects']);
				exit;
			} else {
				$tpl->set_var('message', $done[2]['message']);
			}
		}
		include '../S3DBjavascript.php';
	}
?>
<form name="deleteproject" method="POST" action="<?php echo $action['deleteproject']?>">
	<table class="middle" width="100%"  align="center">
		<tr>
			<td>
				<table class="insidecontents" width="100%"  align="center" border="0">
					<tr bgcolor="#80BBFF">
						<td colspan="2" align="center">Delete Project</td>
					</tr>
					<tr class="odd">
						<td class="info">Removing the project will affect all rules, classes, instance and statements therein. <b>Select "unlink" to remove yourself from project but leaving it for other users; Select "delete" to remove the project but leave dependencies for other users. Select "Delete with all dependencies" if your intention is to remove project, rules, classes and all dependencies.</b></td>
						<td>
							<select name="flag" size="5">
								<option value="resource" selected>Delete this project</option>
								<option value="unlink">Unlink this project</option>
								<option value="all">Delete this project and all dependencies</option>
							</select>
						</td>
					</tr>
					<tr>
						<td>
							<input type="button" name="back" value="Cancel" onClick="window.location='<?php echo $action['project']?>'">&nbsp;&nbsp;&nbsp;&nbsp;
							<input type="submit" name="deleteproject" value="Delete">&nbsp;&nbsp;&nbsp;&nbsp;
						</td>
					</tr>
				</table>
			</td>
		</tr>
	</table>
</form>
