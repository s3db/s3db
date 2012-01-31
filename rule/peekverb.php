<?php
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
	include_once('../core.header.php');
	
	
	$rule_id = $_REQUEST['rule_id'];
	$rule_info =URIinfo('R'.$rule_id, $user_id, $key, $db);
	$ruleAcl = find_final_acl($user_id, $rule_info['project_id'], $db);

	$class_id = $_REQUEST['class_id'];
	$class_info = get_info('class',$class_id,$db);
	$classAcl = find_final_acl($user_id, $class_info['project_id'], $db);
	#echo '<pre>';print_r($class_info);
	
	$project_id = $_REQUEST['project_id'];
	$project_info = get_info('project', $project_id, $db);
	$acl = find_final_acl($user_id, $project_id, $db);
	
	if($ruleAcl=='' && $classAcl=='')
	{#check acl of the provided project
		#find permission of this project on this resource
		if($class_id!='')
			{
			$rule_id = get_rule_id_by_entity_id($class_id, $project_id, $db);
			$rule_info = get_info('rule', $rule_id, $db);
			}
		
		if($acl=='1' || $acl=='2' || $acl=='3')
			{ #this is a class, permission can only be found on rules
			#echo $rule_info['permission'];
			$ruleOnProject = ereg('(^|_)'.$project_id.'_', $rule_info['permission']);
			if(!$ruleOnProject)
				{echo "User does not have access to this resource";
				exit;
				}
			}
			else 
			{echo "User does not have access to this project";
			exit;
			}
	}

if($ruleAcl=='1' || $ruleAcl=='2' || $ruleAcl=='3' || $ruleOnProject || $classAcl=='1' || $classAcl=='2' || $classAcl=='3')
{

		$s3ql = compact('db', 'user_id');
		if($rule_info!='' && $class_id=='')
		{
		$s3ql['select'] = '*';
		$s3ql['from'] = 'statements';
		$s3ql['where']['rule_id'] = $rule_info['rule_id'];
		$s3ql['where']['project_id'] = $project_id;
		
		$data = S3QLaction($s3ql);

		
		if(is_array($data))
			{
			$data = include_rule_info($data, $project_id, $db);
			$data = Values2Links($data);
			$data = array_unique(array_map('grab_value', $data));
			
			}
		#echo '<pre>';print_r($data);
		$formName = 'queryresource';
		
		}
		else
		{
		$s3ql['select'] = 'resource_id,notes';
		$s3ql['from'] = 'instances';
		$s3ql['where']['class_id'] = $class_info['resource_id'];
		
		$formName = 'queryresource';
		$data = S3QLaction($s3ql);
		
		}
		
	
		$out = sprintf("%s\n","<html>");
		$out .= sprintf("%s\n", "<head>");
		$out .= sprintf("%s\n", "<title>Existing values</title>");
		$out .= sprintf("%s\n", "<script type=\"text/javascript\">");
		$out .= sprintf("%s\n", "function SendInfo(txtVal){");
		$action = 'window.opener.document.'.$formName.'.'.$_GET['name'].'.value = txtVal;';
		$out .= sprintf("%s\n", $action);
		$out .= sprintf("%s\n", "window.close();");
		$out .= sprintf("%s\n", "}");
		$out .= sprintf("%s\n", "</script>");
		$out .= sprintf("%s\n", "</head>");
		$out .= sprintf("%s\n", "<body>");
 
		$out .= sprintf("%s\n", "<table><tr><td>");
		if($rule_id!='' && $class_id=='')
		$out .= sprintf("%s\n", printStatementsForPeek($data, $rule_info));
		else
		$out .= sprintf("%s\n", printInstancesForPeek($data, $class_info));
		$out .= sprintf("%s\n", "</td></tr></table>");
		$out .= sprintf("%s\n", "</body>");
		$out .= sprintf("%s\n", "</html>");
		echo $out;
}	
	
	
	

	
	

?>
