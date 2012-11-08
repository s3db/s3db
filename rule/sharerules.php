<?php
	#Sharerules.php is an interface for enabling a connection to an internal or external resource. 
	#Helena F Deus (helenadeus@gmail.com)
	ini_set('display_errors',0);
	if($_REQUEST['su3d']) {
		ini_set('display_errors',1);
	}
	#This application is oriented towards versions of S3DB where the connections table exists - 0.9.5 and higher

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
	$xml = $_REQUEST['query'];

	#When the key goes in the header URL, no need to read the xml, go directly to the file
	include_once('../core.header.php');
	$tpl = CreateObject('s3dbapi.Template', $GLOBALS['s3db_info']['server']['template_dir']);

	#Universal variables
	$uni = array('db'=>$db, 'user_id'=>$user_id, 'key'=>$key);
	if($_REQUEST['orderBy']!='') {
		$SQLextra = array('order by '.$_REQUEST['orderBy'].' '.$_REQUEST['direction']);
	}

	$project_id = $_REQUEST['project_id'];
	#$class_id = $_REQUEST['class_id'].$_REQUEST['entity_id'].$_REQUEST['resource_id'];
	#$resource_info = get_info('class', $class_id, $db);
	#$rule_id = $_REQUEST['rule_id'];
	#$rule_info = get_info('rule', $rule_id, $db);
	#is project_id empty? get it form the class
	if($project_id=='') {
		echo "Please specify project_id";
		exit;
	} elseif($project_id!='') {
		$acl =  find_final_acl($user_id, $project_id, $db);
		#CHECK USER PERMISSION
		if($acl=='' || ($acl != 0 && $acl != 1 && $acl != 2 && $acl != 3)) {
			echo 'You are not allowed on this project.';
			exit;
		}
	} else {
		echo "Please specify a valid UID";
		exit;
	}
		
	#Still here? ok, we can continue the script
	if($acl == '3') {
		echo "
	<TABLE width=100% border='0'>
		<tr bgcolor='#80BBFF'>
			<td colspan='9' align='center'>Request a connection to a resource</td>
		</tr>
		<tr>
			<td><BR></td>
		</tr>
		<tr>
			<td bgcolor='#FFFFCC'>Please insert the intended rule ID </td>
		</tr>
		<tr>
			<td>
				<form name='connect_UID' action='sharerules.php?key=".$key."&project_id=".$_REQUEST['project_id']."' method='POST'>
					<input name=\"rule_id\" type=\"text\"><sup>*required</sup>
			</td>
		</tr>
		<tr>
			<td bgcolor='#FFFFCC'>Please enter a short description for the reason to connect</td>
		</tr>
		<tr>
			<td><textarea name=\"notes\" style=\"background: lightyellow\" rows=\"3\" cols=\"60\"></textarea><BR><BR></td>
		</tr>
		<tr>
			<td><input type=\"submit\" name=\"connect\" value=\"Submit\"></td>
		</tr>
	</table>";

		echo "<font color='red'>";		#all warning show up in red
		#every entry on the tables or every edition or deletion will be dependent on project_id and rule_id
		if($_REQUEST['action']=='delete' || $_REQUEST['action']=='deny') {
			##Delete entry on rules_access
			#Update entry on access_rules
			#Add entry on rule_log
			#This is the own project_id
					
			$rule_info = get_info('rule', $_REQUEST['delete_rule_id'], $db);
			#update rules table
					
			if(!$rule_info) {
				echo "Rule not found";
			} else {
				if($_REQUEST['action']=='delete') {
					$rmproject_id = $_REQUEST['project_id'];
				} else {
					$rmproject_id = $_REQUEST['ext_project_id'];
				}
				$rule_info['oldpermission'] =$rule_info['permission']; #keep the old for the log
				$rule_info['permission'] = ereg_replace('(^|_)'.$rmproject_id.'_', '_', $rule_info['permission']); #remove the deleting project_id from permissions
				$rule_info['project_id'] = $rmproject_id; #trick to make rule log recognize this is a rule adition
				$rule_info['action_by'] = $user_id;
				$action = 'disconnect';
				$editrule = compact('db', 'user_id', 'rule_info', 'inputs', 'action');
		
				if(update_rule($editrule)) {
					echo "Rule disconnected";
					$match_values = array ('project_id'=>$rmproject_id, 'rule_id'=>$_REQUEST['delete_rule_id']);
					if($_REQUEST['action']=='delete') {
						delete_entry('access_rules', $match_values, $db);
					} else {
						update_entry('access_rules', $match_values, 'status', 'disconnected', $db);
					}
				}
			}
		} elseif($_REQUEST['action']=='accept' && $acl='3') {
			#find the important rule
			#find existing permissions on rules table (permissions field)
			#update s3db_rule set permissions = oldvalue._newvalue
			#Delete from pending permission
				
			$rule_info = get_info('rule', $_REQUEST['accept_rule_id'], $db);
			if(!$rule_info) { 		#if rules is not empty
				echo "Rule not found";
			} else {
				#let's see... is the permission for this project already set up? string string :-)
				$alreadyAccepted = ereg('(^|_)'.$_REQUEST['ext_project_id'].'_', $rule_info['permission']);
				if($alreadyAccepted) {
					echo "Rule already accepted";
				} else {
					$rule_info['oldpermission'] =$rule_info['permission']; #keep the old for the log
					$rule_info['permission'] = $rule_info['permission'].$_REQUEST['ext_project_id'].'_';
					$rule_info['project_id'] = $_REQUEST['ext_project_id'];
					$rule_info['action_by'] = 
					$action = 'connect';
					$editrule = compact('db', 'user_id', 'rule_info', 'inputs', 'action');
					if(update_rule($editrule)) {
						#add entry on rules_accesss
						$match = array ('ext_project_id'=>$_REQUEST['ext_project_id'], 'rule_id'=>$_REQUEST['accept_rule_id'], 'project_id'=>$_REQUEST['project_id']);
						update_entry('access_rules', $match, 'status', 'connected', $db);
						echo "Rule accepted";
					}
				}
			}
			echo "</font>";
		}
	}

	##Action for the button of submitting a UID
	#	{
	if($_POST['connect']) {
		if($_POST['rule_id']=='') {
			echo "Please specify rule_id to connect";
		} else {
			#ok, so you want to share? are you asking to see a rule or a resource? you know, you won't be able to see anything before asking the share the resource!
			#Check if the rule exists internally	
			$rule_info = URI('R'.$_POST['rule_id'], $user_id, $db);
			echo "<tr><td><font color='red'>"; #swtich will repond with an output to any validation
			if(!is_array($rule_info)) {
				echo "Rule ".$_POST['rule_id']." does not exist";
			}
			#is the subject already shared?
			#find among all the rules on this project if the subject already is shared/exists. Every shared rule must go through the same process of validation as creating a rule
				
			$s3ql['insert']='rule';
			$s3ql['where']['rule_id']=$_POST['rule_id'];
			$s3ql['where']['project_id']=$project_id;
			$done = S3QLaction($s3ql);

			ereg('<error>([0-9]+)</error><message>(.*)</message>', $done, $s3qlout);
			if($s3qlout[1]!='0') {
				echo $s3qlout['2'];
			} else {
				echo "Permission on rule ".$rule_id." requested and pending.";
			}
			#$inputs = array('subject'=>$rule_info['subject'], 'verb'=>$rule_info['verb'],'object'=>$rule_info['object'], 'notes'=>$rule_info['notes']);
			#$action = 'create';
			#				
			#$resource['project_id'] = $_REQUEST['project_id'];
			#$rule_info['owner_project_id'] = $rule_info['project_id']; #keep the owner in place just in case...
			#$rule_info['project_id'] = $_REQUEST['project_id'];
			#$createrule = compact('resource', 'rule_info', 'project_id', 'action', 'db', 'inputs', 'user_id');	
			#$validity = validate_rule_inputs($createrule);
			#switch($validity) {
			#	case 0:
			#	{
			#		$entry = Array (
			#					'project_id' => $_REQUEST['project_id'],
			#					'account_id' => $user_id,
			#					'rule_id' => $_POST['rule_id'],
			#					'notes' => $_POST['notes']
			#				);
			#		$addEntry = add_entry('access_rules', $entry, $db);
			#		if($addEntry) {
			#			echo "Permission on rule ".$rule_id." requested and pending.";
			#		}
			#		break;
			#	}
			#	case 4:
			#	{
			#		echo "Rule ".$rule_info['subject']."|".$rule_info['verb']."|".$rule_info['object']." already exists in this project";
			#		break;
			#	}
			#	case 6:
			#	{
			#		echo "Subject of this rule does not exist in this project, a resource must be created/shared before a rule can be added to it";
			#		break;
			#	}
			#	case 7:
			#	{
			#		echo "Permission on rule ".$rule_info['subject']."|".$rule_info['verb']."|".$rule_info['object']." was already requested. The rule administrator must first give you permission on it.";
			#		break;
			#	}
			#}
			echo "</font></tr></td>";
		}
	}
	#project rules
	$project_rules = array();
	$s3ql=compact('user_id','db');
	$s3ql['from']='rules';
	$s3ql['where']['project_id']=$_REQUEST['project_id'];
	$rules = s3list($s3ql);
	if(is_array($rules))
	$project_rules = array_map('grab_rule_id', $rules);
	
	#Get all the requests
	$s3ql=compact('user_id','db');
	$s3ql['select']='*';
	$s3ql['from']='requests';
	$allRequests = S3QLaction($s3ql);

	$MyPending = $allRequests;
	if(is_array($MyPending)) {
		foreach ($MyPending as $key=>$user_request) {		#those requests taht have nothing to do with this project can go
			if($user_request['project_id']!=$_REQUEST['project_id']) {
				$MyPending = array_diff_key($MyPending, array($key=>''));
				if (in_array($user_request['rule_id'], $project_rules)) {
					$myRules[] = $user_request;
				}
			}
		}
	}
	
	##Print a list of pending requests
	$columns = array('Requested By', 'Project Name', 'Rule_id', 'Rule', 'Notes', 'Status', 'Requested_on', 'Actions'); #these are the ones taht go to "render elements"

	#1 table for pending and...
	if(is_array($MyPending) && count($MyPending)>0) {
		#transform the array to include project names and requested by
		$element='project';
		$idNames = all(compact('element', 'db'));
		$MyPending = array_map('replace_project_id_and_name', $MyPending);
		$element='rule';
		$idNames = all(compact('element', 'db'));
		$MyPending = array_map('replace_id_with_name', $MyPending);

		$element='account';
		$idNames = all(compact('element', 'db'));
		$MyPending = array_map('replace_id_with_name', $MyPending);
		
		echo "<table border='0' width='100%'><tr bgcolor='#80BBFF'><td colspan='9' align='center'>My Pending Requests for Sharing</td></b>";
		echo "</tr><td><BR></td>";	
		echo $table = render_elements($MyPending, $acl, $columns, 'access_rules');
	}

	#1 table for my Rules
	if (is_array($myRules) && count($myRules)>0) {
		if($acl!=3) {
			$columns = array_diff($columns, array('Actions'));
		}
		#transform the array to include project names and requested by
		$element = 'project';
		$idNames = all(compact('element', 'db'));
		$myRules = array_map('replace_project_id_and_name', $myRules);
		
		$element='rule';
		$idNames = all(compact('element', 'db'));
		$myRules = array_map('replace_id_with_name', $myRules);
		
		$element='account';
		$idNames = all(compact('element', 'db'));
		$myRules = array_map('replace_id_with_name', $myRules);
		
		echo "<table border='0' width='100%'><tr bgcolor='#80BBFF'><td colspan='9' align='center'>My Shared Rules</td></b></table>";
		echo "</tr><td><BR></td>";	
		echo $table = render_elements($myRules, $acl, $columns, 'access_rules');
	}
	echo '</form></td></tr></table>';
?>