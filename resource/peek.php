<?php
	#peek is a generic script that lists all the items in a collection and interacts with the opener document in order to fill a field in a form (both of which must be provided as request inputs for peek)
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
	include_once('../core.header.php');
	
	$rule_id = $_REQUEST['rule_id'];
	if($rule_id) {
		$rule_info = URIinfo('R'.$rule_id, $user_id,$key, $db);
	}
	#$ruleAcl = find_final_acl($user_id, $rule_info['project_id'], $db);
	$class_id = $_REQUEST['class_id'];
	if($class_id) {
		$class_info = URIinfo('C'.$class_id, $user_id,$key, $db);
	}
	$project_id = $_REQUEST['project_id'];
	if($class_id=='' && $rule_id=='') {
		echo "Please provide a rule_id or a class_id";
		exit;
	}
	if($class_id!='' && !$class_info['view']) {
		echo "User does not have access to this resource";
		exit;
	}
	if($rule_id!='' && !$rule_info['view']) {
		echo "User does not have access to this project";
		exit;
	}

	$s3ql = compact('db', 'user_id');
	if($rule_info!='') {
		$s3ql['select'] = '*';
		$s3ql['from'] = 'statements';
		$s3ql['where']['rule_id'] = $rule_info['rule_id'];
		#$s3ql['where']['project_id'] = $project_id;
		$data = S3QLaction($s3ql);
		
		if(is_array($data)) {
			#$data = include_rule_info($data, $project_id, $db);
			$data = Values2Links($data);
			$data = array_unique(array_map('grab_value', $data));
			if($rule_info['object_id']!='') {

				##add the notes in the values
				foreach($data as $Item_id) {
					$sql = "select notes from s3db_resource where resource_id = '".$Item_id."'";
					$db->query($sql, __LINE__, __FILE__);
					if($db->next_record()) {
						$notes = $db->f('notes');
					} else {
						$notes ='';
					}
					$itemData[] = array('resource_id'=>$Item_id, 'notes'=>$notes);
				}
				$data = $itemData;
			}
		}
		$formName = 'queryresource';
	} else {
		$s3ql['select'] = 'item_id,notes';
		$s3ql['from'] = 'item';
		$s3ql['where']['collection_id'] = $class_info['resource_id'];
		$formName = 'insertstatement';
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
	if($rule_info!='') {
		$out .= sprintf("%s\n", printStatementsForPeek($data, $rule_info));
	} else {
		$out .= sprintf("%s\n", printInstancesForPeek($data, $class_info));
	}
	$out .= sprintf("%s\n", "</td></tr></table>");
	$out .= sprintf("%s\n", "</body>");
	$out .= sprintf("%s\n", "</html>");
	echo $out;
?>
