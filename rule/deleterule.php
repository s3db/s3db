<?php
#deleterule.php is a form for changing rules, classes or not
	#Includes links to edit and delete resource, as well as edit rules
	#Helena F Deus (helenadeus@gmail.com)
	
	ini_set('display_errors',0);
	if($_REQUEST['su3d'])
	ini_set('display_errors',1);

	if($_SERVER['HTTP_X_FORWARDED_HOST']!='') $def = $_SERVER['HTTP_X_FORWARDED_HOST'];
	else $def = $_SERVER['HTTP_HOST'];
	if(file_exists('../config.inc.php')) {
		include('../config.inc.php');}
	else{
	Header('Location: http://'.$def.'/s3db/');
	exit;}
#just to know where we are...
$thisScript = end(explode('/', $_SERVER['SCRIPT_FILENAME'])).'?'.$_SERVER['argv'][0];

$key = $_GET['key'];

#Get the key, send it to check validity
include_once('../core.header.php');

if($key)
	$user_id = get_entry('access_keys', 'account_id', 'key_id', $key, $db);
	else
	$user_id = $_SESSION['user']['account_id'];

#Universal variables
$project_id = $_REQUEST['project_id'];
$class_id = $_REQUEST['class_id'];
$rule_id = $_REQUEST['rule_id'];

if($rule_id=='' && $class_id=='')
	{
	echo "Rule_id must be provided";
	#$rule_id = get_rule_id_by_entity_id($class_id, $resource_info['project_id'], $db);
	
	}
elseif($class_id!='' && $rule_id=='') #this is VERY important, if a rule_id is provided, that that rule_id is to be deleted, not the class!!
	{
	$resource_info = URIinfo('C'.$class_id, $user_id, $key, $db);
	$rule_id = $resource_info['rule_id'];
	}


#info about resource
$rule_info = URIinfo('R'.$rule_id, $user_id, $key, $db);
#echo '<pre>';print_r($rule_info);exit;
#$resourceAcl = find_final_acl($user_id, $rule_info['project_id'], $db);
#$acl = find_final_acl($user_id, $_REQUEST['project_id'], $db);
$uni = compact('db', 'user_id');

#Define the outgoing links there are going to exist in this page
#relevant extra arguments
#$args = '?key='.$_REQUEST['key'].'&project_id='.$_REQUEST['project_id'];
#$action['project'] = S3DB_URI_BASE.'/project/project.php'.$args;

#$args = $args.'&class_id='.$_REQUEST['class_id'];

#include('../webActions.php');

$class_id = get_resource_id_from_rule(compact('rule_info', 'db'));
$resource_info = URIinfo('C'.$class_id, $user_id, $key, $db);


if(!$rule_info['delete'])
	{#check projectAcl
		{echo "User cannot delete this resource!!";
		exit;
		}
	}
elseif($rule_info['delete'])
{
	#echo '<pre>';print_r($_POST);
	if($_POST['delete'] != '')
	{
		$s3ql=compact('user_id','db');
		if($rule_info['object']=='UID' && $rule_info['verb']=='has UID')
		{
		$s3ql['delete'] = 'class';
		$s3ql['where']['class_id'] = $class_id;
		

		}
		else
		{
		
		$s3ql['delete'] = 'rule';
		$s3ql['where']['rule_id'] = $rule_id;
		if($_POST['flag']=='unlink')
			{$s3ql['where']['project_id']=$project_id;
			$s3ql['flag']='unlink';
			}
		else {
			$s3ql['flag']='all';	
		}

		
		
		}
		
		#echo '<pre>';print_r($s3ql);
		$s3ql['format']='html';
		$done = S3QLaction($s3ql);
		$msg=html2cell($done);
		#echo $done;
		#ereg('<error>(.*)</error>(.*)<message>(.*)</message>', $done, $s3qlout);
		if($msg[2]['error_code']=='0')
		{
			#echo str_replace(array('&rule_id='.$_REQUEST['rule_id'], '&action=delete'),array('',''),$action['editrules']);
			Header('Location:'.str_replace(array('&rule_id='.$_REQUEST['rule_id'], '&action=delete'),array('',''),$action['editrules']));
			#since resource has became extinct, redirect to the project's page
			exit;
		}
		else {
			$message .= '<br />'.$msg[2]['message'];
		}
		
	}

#include all the javascript functions for the menus...
include('../S3DBjavascript.php');

#and the short menu for the resource script

$rule_info = URIinfo('R'.$rule_id, $user_id, $key, $db);

$message .= "Do you really want to delete this rule? <br /> Select 'Delete Rule' to remove all the statements that depend on this rule; select 'Unlink' to remove the rule from this project but leave it available for other projects";

}

?>

<table class="middle" width="100%"  align="center">
	<tr><td>
		<table class="insidecontents" width="90%"  align="center" border="0">
			<tr><td class="message" colspan="9"><br /></td></tr>
			<tr bgcolor="#80BBFF"><td align="center" colspan="8">Delete Rule<input type="hidden" name="rule_id" value="7"></td></tr>
			<tr class="odd">
				<?php
				echo '<td class="message" colspan="8">'.$message.'</b><br /><br /></td>';
				?>
			</tr>
			<tr class="odd">
				
			</tr>
			<tr class="even">
				<td width="5%">Rule ID</td>

				<td  width="5%">Owner</td>
				<td  width="15%">Create Date</td>
				<td width="10%">Subject</td>
				<td  width="10%">Verb</td>
				<td  width="10%">Object</sup></td>
				<td  width="20%">Notes</td>
				

			</tr>
			<tr class="odd">
				<?php
				echo '<form name= "deleterule" action = "'.$action['deleterule'].'&rule_id='.$rule_id.'" method="POST">';
				echo '<td width="5%"><b>'.$rule_info['rule_id'].'</b></td>
				<td  width="5%"><b>'.find_user_loginID(array('account_id'=>$resource_info['created_by'], 'db'=>$db)).'</b></td>
				<td  width="15%"><b>'.$rule_info['created_on'].'</b></td>
				<td width="10%"><b>'.$rule_info['subject'].'</b></td>
				<td  width="10%"><b>'.$rule_info['verb'].'</b></td>

				<td  width="10%"><b>'.$rule_info['object'].'</sup></td>
				<td  width="20%">'.$rule_info['notes'].'<b></b></td>';
				if($project_id!=''){
				echo '<td><select name="flag" size="2">
						<option value="resource" selected>Delete this rule</option>
						<option value="unlink">Unlink this rule from project</option>
						<!-- <option value="all">Delete this class and all dependences</option> -->
				</select></td>';
			}
				
				?>
				
			</tr>
		</table>
	</td></tr>
</table>
<table class="bottom" width="100%"  align="center">
	<tr><td>
	<table class="insidecontents" width="90%" align="center" border="0">

	<tr><td align="left">
		<?php
		
		
		
		echo '<input type="submit" name="delete" value="OK">&nbsp;&nbsp;&nbsp;&nbsp;';
		echo '<input type="button" name="cancel" value="Cancel" onClick="window.location=\''.$action['editrules'].'\'"><br /><br /></td></tr>';
		echo '</form>';
		?>
	</table>
	</td></tr>
</form>
