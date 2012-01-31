<?php

#Callbacks.php is for internal use only and fits the purpose of gathering all funcctions that are called as callback for an array
function array_delete($ary,$key_to_be_deleted) 
{ 
        $new = array(); 
        if(is_string($key_to_be_deleted)) { 
            if(!array_key_exists($key_to_be_deleted,$ary)) { 
                return; 
            } 
            foreach($ary as $key => $value) { 
                if($key != $key_to_be_deleted) { 
                    $new[$key] = $value; 
                } 
            } 
            $ary = $new; 
        } 
        if(is_array($key_to_be_deleted)) { 
            foreach($key_to_be_deleted as $del) { 
                $ary=array_delete($ary,$del); 
            } 
        } 
    return ($ary);
}

function remove_numeric_id($uid)
{
		if(is_numeric($uid))
		   return ('');
}

function uid_str($array, $field) #To be used with array_walk
		{
			
			#return $array;
			return $array[$field];
		}

function grab_uid($array)
		{
			return $array['uid'];
		}
function comma_split($v, $w)
		{
			$v .= $w.', ';
			return $v;
		}
function bar_split($v, $w)
		{
			$v .= $w.'|';
			return $v;
		}

function grab_delete($element)

		{
		
		return $element['delete'];
		}

function grab_permission($user)
{	

	return ($user['permissionOnResource']);
	
}

function grab_verb($rule)

		{
		return $rule['verb'];
		}

function grab_subject($rule)

		{
		
		return $rule['subject'];
		}

function grab_object($rule, $verb)

		{
		if($rule['verb']==$verb)
		return $rule['object'];
		}

function grab_value($statement)

		{
		if($statement['file_name']=='')
		return $statement['value'];
		else
		return $statement['file_name'];
		}

function notes($instance_id, $db)
{
	$db->query("select notes from s3db_resource where resource_id = '".$instance_id."'");
	if($db->next_record())
		return ($db->f('notes'));
	else {
		return ($instance_id);
	}
}
function letter($from)
{
	return (strtoupper(substr($from,0,1)));
}

function permissionModelComp($permLevel, $permModel='nsy')
	{
	
	#Is permission defined using the new model?
	if(!ereg('^['.$permModel.'-]+$',$permLevel))
	{
	
	$permision2user = str_replace(array('0','1','2'), str_split($permModel), $permLevel);
	
	}
	else {
		$permision2user=  $permLevel;
	}
	return ($permision2user);
	}

function allowed($pL, $digit2check,$isOwner,$state=3,$model='nsy')
{
	$literal = str_split($model);
	$numeric = range(0,2);
	
	$permissionOnSlot =	substr($pL,$digit2check,1);
	
	#If digit corrsponds to highest, or digit is middle and user is owner, return true. Otherwise is false
	if(strtolower($permissionOnSlot)==substr($model,$state-1,1)) #if it is the same as the highest inthe model)
	{
			return (true);
	}
	elseif (strtolower($permissionOnSlot)==substr($model,$state-2,1) && $isOwner) {
		return (true);
	}
	else {
		return (False);
	}
		
	
	return (false);
}

function grab_resource_id($statement)

		{#call it statement but its the same for classes and instances
		if (is_array($statement)) {
			return $statement['resource_id'];
		}
		else {
			return ($statement);
		}
		
		}


function grab_just_object($rule)

		{
		return $rule['object'];
		}

function grab_nonUID_objects($R)
{
extract($R);

foreach($rules as $rule_info)
	{
	#echo $rule_info['object'].'<br />';
	if(!resourceObject(array('regexp'=>$regexp, 'db'=>$db, 'rule_info'=>$rule_info, 'project_id'=>$project_id)))
	$objects[] = $rule_info['object'];
	}
#echo '<pre>';print_r($objects);
return $objects;

}

function grab_UID_objects($R)
{
extract($R);

foreach($rules as $rule_info)
	{
	
	if(resourceObject(array('rule_id'=>$rule_info['rule_id'], 'db'=>$db, 'project_id'=>$project_id)))
	$rule_info['object_rule_id'] = ruleId4object(array('regexp'=>$regexp, 'db'=>$db, 'object'=>$rule_info['object'], 'project_id'=>$project_id));
	$rule_info['object_class_id'] = classID4object(array('db'=>$db, 'object'=>$rule_info['object'], 'project_id'=>$project_id));
	$data[] = $rule_info;
	}
#echo '<pre>';print_r($data);
return $data;

}
function grab_id($element, $list_of_elements)
{
	
	if (!is_array($list_of_elements)) {
		$list_of_elements = array('sim'=>$list_of_elements);
	}
	foreach ($list_of_elements as $i=>$e_info) {
		
		$grabbed[$i] = $e_info[$element.'_id'];
	}
	
	if (!is_array($list_of_elements)) {
		$list_of_elements = $grabbed['sim'];
	}
	return ($grabbed);
	
}

function include_acl($elements, $user_id, $db)
{

if(is_array($elements))
foreach ($elements as $project)
	{
	#$project['acl'] = find_final_acl($user_id, $project['project_id'], $db);
	$data[] = $project;
	}
return $data;

}

function include_data_acl($D)
{
extract($D);

if(!is_array($instances))
	$instances[0] = $instance_info;

foreach ($instances as $instance_info)
	{
	
	$instance_info['dataAcl'] = dataAcl(compact('user_id', 'db', 'instance_info'));
	$data[] = $instance_info;
	}

	
return $data;

}

function include_statement_acl($elements, $user_id, $db)
{
if(is_array($elements))
foreach ($elements as $statement)
	{
	$statement_info = $statement;
	$statement['dataAcl'] = statementAcl(compact('user_id', 'db', 'statement_info'));
	$data[] = $statement;
	}
return $data;

}

function include_class_id($rules, $db)
{
	#echo '<pre>class';print_r($rules);
if(is_array($rules))
foreach ($rules as $rule_info)
	{
	$rule_id = $rule_info['rule_id'];
	$rule_info['subject_class_id'] = get_resource_id_from_rule(compact('rule_id', 'db'));
	$data[] = $rule_info;
	}
#echo '<pre>class';print_r($data);
return $data;

}

function include_subject_class_id($S)
{
extract($S);

if(is_array($rules))
foreach ($rules as $rule_info)
	{
	$rule_id = $rule_info['rule_id'];
	$entity = $rule_info['subject'];
	
	$rule_info['subject_class_id'] = classID4entity(compact('db', 'entity', 'project_id'));
	$data[] = $rule_info;
	}
#echo '<pre>class';print_r($data);
return $data;

}

function include_rule_info($statements, $project_id, $db)
{
foreach ($statements as $statement_info)
	{
	$rule_id = $statement_info['rule_id'];
	$rule_info = s3info('rule', $rule_id, $db);
	$statement_info['subject'] = $rule_info['subject'];
	$statement_info['verb'] = $rule_info['verb'];
	$statement_info['object'] = $rule_info['object'];
	$entity = $rule_info['subject'];#for the subject
	$statement_info['subject_class_id'] = fastClassID(compact('entity', 'project_id', 'db'));
	$entity = $rule_info['object']; #...and for the object
	$statement_info['object_class_id'] = fastClassID(compact('entity', 'project_id', 'db'));
	$data[] = $statement_info;
	}
return $data;

}

function include_object_class_id($rules, $project_id, $db)
{
if (is_array($rules))
foreach ($rules as $rule_info)
	{
	$rule_id = $rule_info['rule_id'];
	$entity = $rule_info['object'];
	$rule_info['object_class_id'] = fastClassID(compact('db', 'entity', 'project_id'));
	$data[] = $rule_info;
	}
return $data;

}

function include_all_class_id($S)
{
extract($S);

if(is_array($rules))
foreach ($rules as $rule_info)
	{
	$rule_id = $rule_info['rule_id'];
	$entity = $rule_info['subject'];
	$rule_info['subject_class_id'] = fastClassID(compact('db', 'entity', 'project_id'));

	$entity = $rule_info['object'];
	$rule_info['object_class_id'] = fastClassID(compact('entity', 'project_id', 'db'));
	$data[] = $rule_info;
	}
#echo '<pre>class';print_r($data);
return $data;

}

function get_rule_drop_down_menu($D)
{
extract($D);

if($rule_info=='') 
	{
	$rule_info = s3info('rule', $rule_id, $db);
	#$rule_info = include_all_class_id(compact('rule_info', 'db', 'project_id', 'user_id'));
	}

	#if(is_array($_SESSION[$user_id]['instances'][$rule_info['object_class_id']])) {
	#	$instances = $_SESSION[$user_id]['instances'][$class_id];
	#}
	#else {
	$s3ql = compact('db', 'user_id');
	$s3ql['select'] = '*';
	$s3ql['from'] = 'instances';
	$s3ql['where']['class_id'] = $rule_info['object_id'];
	
	#echo '<pre>';print_r($s3ql);exit;
	#$s3ql['where']['project_id'] = $project_id;

	
	#$instances = s3list($s3ql);
	$instances = S3QLAction($s3ql);
#echo '<pre>';print_r($instances);
		#if(!is_array($instances))
		#{
		#$s3ql['where']='';
		#$s3ql['where']['project_id']=$project_id;
		#$s3ql['where']['entity']=$rule_info['object'];
		
		#$instances = S3QLaction($s3ql);
		#$_SESSION[$user_id]['instances'][$rule_info['object_class_id']] = $instances;
		#}
	#}
	
		
		$inputBox .= '<select name="'.$select_name.'" size="1" style="background-color: lightyellow; font-size: 8pt" size="1">';
				$inputBox .=  '<option value="" selected></option>';
				if(is_array($instances))
				foreach($instances as $object_instance_info)
					{$inputBox .= '<option value ="'.$object_instance_info['resource_id'].'" >'.$object_instance_info['notes'].' <sub>(ID#'.$object_instance_info['resource_id'].')</sub></option>';
					}
		$inputBox .= '</select>';
	

return $inputBox;
	

}
function include_object_drop_down_menu($S)
{
extract($S);


	if($rule_info['object_class_id']!='')
		{
		$s3ql = compact('db', 'user_id');
		$s3ql['select'] = 'resource_id,notes';
		$s3ql['from'] = 'instances';
		$s3ql['where']['resource_class_id'] = $rule_info['object_class_id'];
		$s3ql['where']['project_id'] = $project_id;

		$instances = S3QLaction($s3ql);
		
		$inputBox .= '<select name="input[]" size="1" style="background-color: lightyellow; font-size: 8pt" size="1">';
				$inputBox .=  '<option value="" selected></option>';
				if(is_array($instances))
				foreach($instances as $object_instance_info)
					{$inputBox .= '<option value ="'.$object_instance_info['resource_id'].'" >'.$object_instance_info['notes'].' <sub>(ID#'.$object_instance_info['resource_id'].')</sub></option>';
					}
				$inputBox .= '</select>';
		$rule_info['object_drop_down_menu'] = $inputBox;
		}	
return ($rule_info);
	
}

function include_instance_class_id($instances, $project_id, $db)
	{
	foreach($instances as $instance_info)
		{if($instance_info['resource_class_id']=='')
		{
			$entity = $instance_info['entity'];
			if($project_id=='') $project_id = $instance_info['project_id'];
			$instance_info['resource_class_id'] = resourceClassID4Instance(compact('entity', 'project_id', 'db'));
			$instance_info['class_id'] = resourceClassID4Instance(compact('entity', 'project_id', 'db'));
		}
		else
		$instance_info['class_id'] = $instance_info['resource_class_id'];
		
		$data[] = $instance_info;
		}


	return $data;
	}
function include_button_notes($statements, $project_id, $db)
	{$action = $GLOBALS['webaction'];
	
	foreach($statements as $statement_info)
		{$rule_id = $statement_info['rule_id'];
		if($statement_info['object_id']!='')
			{
			$instance_id = $statement_info['value'];

			$sql = "select notes from s3db_resource where resource_id = '".$instance_id."'";

			$db->query($sql, __LINE__, __FILE__);
			if($db->next_record())
				{
				$notes = $db->f('notes');

				if($notes!='')
				$statement_info['button_notes'] = $notes;
				else
				$statement_info['button_notes'] = $instance_id;
				
				$statement_info['instance_button'] = '<input type="button" value="'.$statement_info['button_notes'].'" onClick="window.open(\''.$action['instance'].'&instance_id='.$instance_id.'\')">';
				}
			}
			else 
			$statement_info['instance_button'] = $statement_info['value'];
	
		$data[] = $statement_info;
		}


	return $data;
	}

function button_notes($statement_info, $user_id, $db)
	{$action = $GLOBALS['webaction'];
	
	$rule_id = $statement_info['rule_id'];
		if($statement_info['object_id']!='')
			{
			$instance_id = $statement_info['value'];

			$sql = "select notes from s3db_resource where resource_id = '".$instance_id."'";

			$db->query($sql, __LINE__, __FILE__);
			if($db->next_record())
				{
				$notes = $db->f('notes');

				if($notes!='')
				$button_notes = $notes;
				else
				$button_notes = $instance_id;
				
				}
			}
			else 
			$button_notes = $statement_info['value'];
	
		return $button_notes;
		
	}


	
function value($statement_info, $user_id, $db)
{$action = $GLOBALS['action'];
	$rule_id = $statement_info['rule_id'];
	$rule_info = s3info('rule', $rule_id, $db);
	if ($rule_info['object_id']!='') {
		$instance_id = $statement_info['value'];
		$intance_info = URI('I'.$instance_id, $user_id, $db);
		$notes = $intance_info['notes'];

			if($notes!='')
			$statement_info['button_notes'] = $notes;
			else
			$statement_info['button_notes'] = $instance_id;
			
			$value = '<input type="button" value="'.$statement_info['button_notes'].'" onClick="window.open(\''.$action['instance'].'&instance_id='.$instance_id.'\')">';
			}
			else {
				$value = include_fileLinks($statement_info, $db);
			}
			
	return ($value);
	
}

function include_button($statements, $user_id, $db)
{
$action = $GLOBALS['webaction'];
foreach ($statements as $key=>$statement_info) {
	$statement_info['button'] =  value($statement_info, $user_id, $db);
	$data[] = $statement_info;
}
	return ($data);
}

function include_statements($S)
{
extract($S);

if(is_array($instances)) {
$class_instances = array();
$statements = array();
foreach ($instances as $instance_info)
	{
	
	$s3ql = compact('db', 'user_id');
	$s3ql['select']='*';
	$s3ql['from'] = 'statements';
	$s3ql['where']['item_id'] = $instance_info['resource_id'];
	#echo '<pre>';print_r($s3ql);exit;
	$statements = S3QLaction($s3ql);
	

	
	if(is_array($statements))
		{
				
		#figure out the classes of the subject and object of the rule_id
		if (!is_array($rules)) {
		$class_id = $instance_info['class_id'];
		$s3ql=compact('user_id','db');
		$s3ql['select']='*';
		$s3ql['from']='rules';
		$s3ql['where']['subject_id']=$class_id;
		#$s3ql['where']['object']='!="UID"';
		$rules = S3QLaction($s3ql);

			}

			
		#now add rule_info to the statements
		if (is_array($statements) && is_array($rules)) {
			
			#echo '<pre>';print_r($rule_ids);
			$rule_ids = array_map('grab_rule_id', $rules);
			$rules = array_combine($rule_ids, $rules);
			#echo '<pre>';print_r($rules);
			$instance_statements = array();
			
			
			foreach ($statements as $statement_info) {
						
						$statement_info['subject'] = $rules[$statement_info['rule_id']]['subject'];
						$statement_info['verb'] = $rules[$statement_info['rule_id']]['verb'];
						$statement_info['object'] = $rules[$statement_info['rule_id']]['object'];
						$statement_info['subject_id'] = $rules[$statement_info['rule_id']]['subject_id'];
						$statement_info['object_id'] = $rules[$statement_info['rule_id']]['object_id'];
						if(!$omit_button_notes)
						$statement_info['button_notes']=button_notes($statement_info, $user_id, $db);
						if($statement_info['subject']!='' && $statement_info['verb']!='' && $statement_info['object']!='')
							$instance_statements[] = $statement_info;
						else {
							$instance_statements[]=array();
						}
					
					#echo '<pre>';print_r($statement_info);
					}
					
					$statements = array_filter($instance_statements);
		#$statements = include_rule_info($statements, $project_id, $db);
		#$statements = include_button_notes($statements, $project_id, $db);
		
		$instance_info['stats'] = $statements;
		}
	
	
	}
	else {
		$instance_info['stats']='';
	}

	$class_instances[] = $instance_info;
	} #closes the foreach
	}
	
return $class_instances;
}


function include_statements1($S)
{
extract($S);

if(is_array($instances))
foreach ($instances as $instance_info)
	{
	
	$s3ql = compact('db', 'user_id');
	$s3ql['select']='*';
	$s3ql['from'] = 'statements';
	$s3ql['where']['instance_id'] = $instance_info['resource_id'];
	#$s3ql['where']['project_id'] = $project_id;
	
	#$done = S3QLaction($s3ql);
	$done = s3list($s3ql);
	
	
	
	if(is_array($done))
		{
		$statements = $done;
		#figure out the classes of the subject and object of the rule_id
		#$statements = include_rule_info($statements, $project_id, $db);
		#$statements = include_button_notes($statements, $project_id, $db);
		$instance_info['stats'] = $done;
		
		}
	

	$data[] = $instance_info;
	}
	#echo '<pre>';print_r($data);
	
return $data;
}
function replace_owner_acl($data, $db)
{
foreach($data as $D)
	{
	if($D['acl_project_id']!='')
		{
		#$acl = find_final_acl($D['account_id'], $D['acl_project_id'], $db);
		$D['acl_rights'] = $acl;
		}
	
	$Corrected_data[] = $D;
	}
	return $Corrected_data;


}

function account_id_as_key($users)
{
if(!is_array($users))
	$user_info = array('key_me'=>$users);
else {
	$user_info = $users;
}

foreach($user_info as $user)
if($user['account_id']!='')
	$data[$user['account_id']] = $user;

if(!is_array($users))
	$data = $data['key_me'];

return $data;

}



function number_as_key($users)
{
if(is_array($users))
foreach($users as $account_id=>$user_info)
	$data[] = $user_info;

return $data;

}
function grab_project_id($project)

		{
		if (is_array($project)) {
		return $project['project_id'];
		}
		else {
			return ($project);
		}
		}
function grab_rule_id($rule)

		{
		if (is_array($rule)) {
			return $rule['rule_id'];
		}
		else {
			return ($rule);
		}
	}


function grab_class_instance_id($resource)
	{
	global $element;
		if($element=='instance' || $element=='statement')
		{
		$resource['instance_id'] = $resource['resource_id'];
		if($resource['resource_class_id']!='')
		$resource['class_id'] = $resource['resource_class_id'];
		
		}
		elseif($element=='class')
		$resource['class_id'] = $resource['resource_id'];
		

	return $resource;
	}

function delete_empty_statements($statement)
{
	if($statement['value']!='')
		return $statement;

}


function replace_created_by($data, $db)
	{
		
	if(is_array($data))
	foreach($data as $x)
		{$x['account_name'] = find_user_loginID(array('account_id'=>$x['account_id'], 'db'=>$db));
		$x['created_byID'] = find_user_loginID(array('account_id'=>$x['created_by'], 'db'=>$db));
		$x['project_owner'] = find_user_loginID(array('account_id'=>$x['created_by'], 'db'=>$db));
		$z[] = $x;
		}
	return $z;
	}

function replace_project_id($data)
	{
	global $projects;

	$data['project_name'] = $projects[$data['project_id']];
	return $data;
	}

function replace_project_id_and_name($data)
	{
	global $idNames;

	$data['project_name'] = $idNames[$data['project_id']].' (ID:'.$data['project_id'].')';
	return $data;
	}
function replace_id_with_name($data)
	{
	global $element, $idNames;

	$data[$element.'_name'] = $idNames[$data[$element.'_id']];
	return $data;
	}


function random_string($length)
	{
	  $acceptedChars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYS0123456789';
  $max = strlen($acceptedChars)-1;
  $random = null;
  for($i=0; $i < $length; $i++) {
   $random .= $acceptedChars{mt_rand(0, $max)};
  }
  return $random;
	
	}
function trimmit($something)
{
return trim($something);
}
	
function ValuesToFileLinks($statement)
{global $key;

if($statement['file_name']!='')
	{$filelink = '<a href="'.S3DB_URI_BASE.'/download.php?key='.$key.'&project_id='.$statement['project_id'].'&resource_id='.$statement['resource_id'].'&rule_id='.$statement['rule_id'].'&statement_id='.$statement['statement_id'].'">';
	
	$statement['value'] = $filelink.$statement['file_name'].'</a>';
	}
return $statement;
}
function Values2Links($statement)
{global $action;
if(!is_array($statement)) $statement = array(0=>$statement);
foreach($statement as $stat)
	{
if($stat['file_name']!='')
	{$filelink = '<a href="'.$action['download'].'">';
	
	$stat['value'] = $filelink.$stat['file_name'].'</a>';
	}
	$data[] = $stat;
	}
return $data;
}



function include_fileLinks($statement, $db)
{
$action = $GLOBALS['webaction'];
#echo '<pre>';print_r($action);
if($statement['file_name']!='')
	{
	#$filelink = '<a href="'.S3DB_URI_BASE.'/download.php?key='.$key.'&project_id='.$statement['project_id'].'&resource_id='.$statement['resource_id'].'&rule_id='.$statement['rule_id'].'&statement_id='.$statement['statement_id'].'">';
	#Find the file location, read the filesize, return that size to the user
	ereg('(.*)\.([a-zA-Z0-9]+)$', $statement['file_name'], $ext);
	if($ext){
	$name = $ext[1];
	$ext = $ext[2];
	}
	$fileLocated = 	$GLOBALS['uploads'].'/'.$statement['project_folder'].'/'.$name.'_'.urlencode($statement['project_id'].'_'.$statement['resource_id'].'_'.$statement['rule_id'].'_'.$statement['statement_id']).'.'.$ext;
	if(is_file($fileLocated)){
	$statement['file_size'] =filesize($fileLocated);
	}
	
	$filelink  = '<a href = "'.$action['download'].'&statement_id='.$statement['statement_id'].'">';
	
	$statement['value'] = $filelink.$statement['file_name'].'</a>';
	}
return $statement;
}


function ValuesToFileURLs($statement)
{global $key;

if($statement['file_name']!='')
	{$filelink = '<http://localhost/s3db/download.php?key='.$key.'&project_id='.$statement['project_id'].'&resource_id='.$statement['resource_id'].'&rule_id='.$statement['rule_id'].'&statement_id='.$statement['statement_id'].'">';
	
	$statement['value'] = $filelink;
	}
return $statement;
}

function sqlcharacters($value)
{$regexp = $GLOBALS['regexp'];


#does the value have regular expressions?
$cleanValue = preg_quote($value, '/');
#echo '<BR>'.$cleanValue.'<BR>';
if (strlen($cleanValue) > strlen($value))
	{
	
	$valueToQuery = stripslashes($value);
	#is this a "not"? if it's not, add the regexp part and the quotes
	#if(substr($valueToQuery,0,1)!='!')
	#	$valueToQuery = $regexp." '".$valueToQuery."'";
	#echo $valueToQuery;
	}
elseif(strlen($value) == strlen($cleanValue))
$valueToQuery = "= '".$value."'";

return $valueToQuery;
}

function parse_regexp($value)
{
 $regexp = $GLOBALS['regexp'];

$matchRegexp = substr($value, 0, strlen($regexp));
$matchNeg = (substr($value,0,2)=='!=');
$matchNeg = strstr($value, '!=');
#does the value have regular expressions?
$cleanValue = preg_quote($value, '/');
#echo '<BR>'.$cleanValue.'<BR>';
if($matchRegexp==$regexp)
$valueToQuery = stripslashes($value);
elseif(!$matchNeg)
$valueToQuery = "= '".$value."'";
else 
$valueToQuery = $value;


return $valueToQuery;
}


function parse_regexp1($value)
{$regexp = $GLOBALS['regexp'];


#does the value have regular expressions?
$cleanValue = preg_quote($value, '/');


if (strlen($cleanValue) > strlen($value))
	{
	
	#echo substr($value, 0, strlen($regexp));
	
	if((substr($value,0, 2)!='!=') && (substr($value, 0, strlen($regexp))!=$regexp) && (substr($value, 0,1)!='='))
	$valueToQuery = $regexp." '".stripslashes($value)."'";
	else
	$valueToQuery = stripslashes($value);

	}
elseif(strlen($value) == strlen($cleanValue) && (substr($value, 0, strlen($regexp))!=$regexp) && (substr($value, 0,1)!='='))
$valueToQuery = "= '".$value."'";
else {
	$valueToQuery = $value;
}

return $valueToQuery;
}

function S3QLSyntax($s3qlKeys)
{


if(!in_array($s3qlKeys, $syntax))
	return $s3qlKeys;


}
function grab_acl($x)
{
extract($x);
#echo '<pre>';print_r($elements);
foreach ($elements as $proj)
	{
	
	$proj['acl'] = $data['acl'];
	$data[] = $proj;
	}
return $data;
}

function trim_quotes ($cell)
{ return $cell = trim ($cell, "\"'");
}

function remove_empty_lines($line)
{
	if(!trim($line)=='')
	return ($line);
}


function clean_inputs($data)

{
if (!is_array($data)) { #this is for things that don't come in arrays, for example for using with array_map
	$data2clean = array('cleanme'=>$data);
	
}
else {
	$data2clean = $data;
}
#echo '<pre>';print_r($data);
foreach($data2clean as $key=>$input) {
	if (ereg('(subject|verb|object|entity|cleanme)',$key)) {
	$clean_data[$key] = str_replace('(', '\\\(', $input);	
	$clean_data[$key] = str_replace(')', '\\\)', $clean_data[$key]);	
	
	}
	
	
}
if (!is_array($data)) {
	$clean_data = $clean_data['cleanme'];
}
return ($clean_data);
}

function  create_element_list($element_ids)
{
#string create_something_list(array)
#function requires an array with elements that have _id in one of the keys. Output is a string
#Build a query for permissions


reset($element_ids);

$eles .= ''.current($element_ids).'$';
while(each($element_ids))
$eles .= '|'.current($element_ids).'$';

#remove two extra chars that each throuw in for some reason. All queries should be performed specifically on these projects
$eles = substr($eles, 0, strlen($eles)-2);

return ($eles);
}

function  create_list($element_ids)
{
#string create_something_list(array)
#function requires an array with elements that have _id in one of the keys. Output is a string
#Build a query for permissions

if(!is_array($element_ids)) return '';#on exception return empty string

reset($element_ids);

$eles .= '^'.current($element_ids).'$';
while(each($element_ids))
$eles .= '|^'.current($element_ids).'$';

#remove two extra chars that each throuw in for some reason. All queries should be performed specifically on these projects
$eles = substr($eles, 0, strlen($eles)-3);

return ($eles);
}

function createCharList($elements, $char)
{
	for ($i=0; $i < count($elements); $i++) {
		if($str!='') $str .= $char;
		$str .= $elements[$i];
	}
	return ($str);
}


function  create_project_id_list($projects)
{
#string  create_project_id_list($array)
#function reaquires an array with many projects. Output is a string
#Build a query for permissions

$projects = array_map('grab_project_id',$projects);


reset($projects);

$projs .= '^'.current($projects).'$';
while(each($projects))
$projs .= '|^'.current($projects).'$';

#remove two extra chars that each throuw in for some reason. All queries should be performed specifically on these projects
$projs = substr($projs, 0, strlen($projs)-3);

return ($projs);
}

function  create_class_id_list($classes)
{
#string  create_project_id_list($array)
#function reaquires an array with many projects. Output is a string
#Build a query for permissions

$classes = array_map('grab_resource_id',$classes);

reset($classes);

$clas .= '^'.current($classes).'$';
while(each($classes))
$clas .= '|^'.current($classes).'$';

#remove two extra chars that each throuw in for some reason. All queries should be performed specifically on these projects
$clas = substr($clas, 0, strlen($clas)-3);

return ($clas);
}

function create_rule_id_list($rules) #i know, it's the previos function with a diff name, to abstract later
{
	$rules = array_map('grab_rule_id',$rules);


reset($rules);

$rls .= '^'.current($rules).'$';
while(each($rules))
$rls .= '|^'.current($rules).'$';

#remove two extra chars that each throuw in for some reason. All queries should be performed specifically on these projects
$rls = substr($rls, 0, strlen($rls)-3);

return ($rls);
	
}
function  create_permission_list($projects)
{
#string  create_project_id_list($array)
#function reaquires an array with many projects. Output is a string
#Build a query for permissions
$projects = array_map('grab_project_id',$projects);
							
reset($projects);

$projs .= '(^|_)'.current($projects).'_';
while(each($projects))
$projs .= '|(^|_)'.current($projects).'_';

#remove two extra chars that each throuw in for some reason. All queries should be performed specifically on these projects
$projs = substr($projs, 0, strlen($projs)-7);

return ($projs);
}

function get_show_me($resource_info, $rules, $db)
	{
		
		if($rules==''){
		$sql = "select rule_id, subject, verb, object from s3db_rule where project_id='".$_REQUEST['project_id']."' and subject='".$resource_info['entity']."' order by verb, object";
		

		$db->query($sql, __LINE__, __FILE__);
		while($db->next_record())
		{
			$rules[] = Array('rule_id'=>$db->f('rule_id'),
					'subject'=>$db->f('subject'),
					'verb'=>$db->f('verb'),
					'object'=>$db->f('object'));
		}
		}
		$posted_show_me = $_POST['show_me'];
		#echo '<pre>';print_r($posted_show_me);exit;
		if(count($posted_show_me) > 0)
		{
		$show_me = Array();
		foreach($rules as $i => $value)
		{
			$show_me_id = 'show_me_'.$rules[$i]['rule_id'];
			$show_me_val = 'show_me_val_'.$rules[$i]['rule_id'];
			
			if(count($posted_show_me) > 0)
			{
				foreach($posted_show_me as $j =>$value)
				{
				if($posted_show_me[$j] == $show_me_val)
				{
					
				array_push($show_me, $rules[$i]);
				}
				}
			}
		}
			#echo '<pre>';print_r($show_me);exit;
			return $show_me;		
		}
		
	}

function get_rule_value_pairs($resource_info, $rules, $db)
{
	
	$rule_value_pairs = array();
	foreach ($rules as $key=>$value) {
		if($_POST['rule_1_'.$value['rule_id']]!='')
		$rule_value_pairs['rule_1_'.$value['rule_id']]=$_POST['rule_1_'.$value['rule_id']];
		if($_POST['rule_2_'.$value['rule_id']]!='')
		$rule_value_pairs['rule_2_'.$value['rule_id']]=$_POST['rule_2_'.$value['rule_id']];
		
	}
	
	return ($rule_value_pairs);
}

function gatherInputs($g)
{extract($g);
	
	$required = array(
						'key'=>array(),
						'project'=>array('project_name'),
						'collection'=>array('project_id', 'entity'),
						'rule'=>array('project_id', 'subject', 'subject_id', 'verb', 'object'),
						'item'=>array('collection_id'),
						'statement'=>array('item_id', 'rule_id', 'value'),
						'file' => array('item_id', 'rule_id', 'filekey'),
						'user' => array('account_lid', 'account_email'),
						'group'=>array('account_lid'));
	#$map = $GLOBALS['s3map'];
	

	$optional = array(
						'key'=>array('key_id', 'expires'),
						'project'=>array('project_id', 'project_description', 'project_owner','status', 'created_on', 'created_by'),
						'collection'=>array('collection_id', 'rule_id', 'notes','status', 'created_on', 'created_by'),
						'rule'=>array('rule_id', 'verb_id', 'object_id','notes', 'validation','status', 'created_on', 'created_by'),
						'item'=>array('item_id', 'notes', 'entity', 'project_id','status', 'created_on', 'created_by'),
						'statement'=>array('statement_id', 'project_id', 'notes', 'status', 'created_on', 'created_by'),
						'file' => array('statement_id',  'project_id', 'notes','status', 'created_on', 'created_by'),
						'user' => array('user_id', 'account_pwd', 'account_uname', 'account_email', 'account_phone', 'addr1', 'addr2', 'account_type','account_status', 'city', 'postal_code', 'state', 'country', 'created_on', 'created_by','permission_level'),
						'group'=>array('group_id', 'account_pwd', 'created_on', 'created_by'));
	
	$possible = array_merge_recursive($required,$optional);
	
	#echo '<pre>';print_r($info);exit;
	#echo '<pre>';print_r($to_create);exit;
	#echo '<pre>';print_r($possible[$element]);
	#echo '<pre>';print_r($to_create);
	$map[$element] = $GLOBALS['s3map'][$GLOBALS['plurals'][$element]];
	#echo '<pre>';print_r($map[$element]);
	foreach ($possible[$element] as $pot_input) {
		
		if ($to_create[$pot_input]!='') {
			if(ereg('owner|created_by',$pot_input)){
			//check if user exists; otherwise use user_id
			$sql = "select * from s3db_account where account_id = '".$to_create[$pot_input]."'";
			
			$db->query($sql);
			if(!$db->next_record()) {
			$to_create[$pot_input] = $user_id;	
			}
			
			}
			$value = $to_create[$pot_input];
			$fieldName=(in_array($pot_input, array_keys($map[$element])))?$map[$element][$pot_input]:$pot_input;
			$inputs[$fieldName]=addslashes($value);
			#echo '<pre>';print_r($inputs);
		}
		elseif($to_create[$pot_input]=='') #if it is empty, look for something that might replace it
			{
			#infer from empty required
			if(in_array($pot_input, $required[$element]))
				{$inputs = formatReturn($GLOBALS['error_codes']['something_missing'], $pot_input.' cannot be empty. '.$GLOBALS['messages']['syntax_message'], $format,'');
				return ($inputs);
				}
			#$inputs= ($GLOBALS['messages']['something_missing'].'<message>'.$pot_input.' cannot be empty. '.$GLOBALS['messages']['syntax_message'].'</message>');
			elseif(in_array($pot_input, $optional[$element]))
				{
			
				if (ereg('owner|created_by|status', $pot_input))
				$inputs[$pot_input]=(ereg('owner|created_by', $pot_input)?$user_id:(ereg('status', $pot_input)?'A':''));
				if(ereg('account_type', $pot_input))
					{
					$inputs[$pot_input] = substr($element,0,1);
					}
				if(ereg('account_pwd', $pot_input))
					{
					$inputs[$pot_input] = random_string(12);
					}
				if(ereg('account_uname', $pot_input))
					{
					$inputs[$pot_input] = $inputs['account_lid'];
					}
				
				if(ereg('entity', $pot_input))
					{	
					$inputs[$pot_input] = $info['C'.$to_create['collection_id']]['entity'];
					
					}
				if(ereg('project_id', $pot_input))
					{
					
					if(ereg('item', $element))
						{$inputs[$pot_input] = ($info['C'.$to_create['collection_id']]['project_id']!='')?$info['C'.$to_create['collection_id']]['project_id']:$info['R'.$to_create['rule_id']]['project_id'];
						}
					if(ereg('statement|file', $element)) #PROJECT_ID WILL BE THAT OF THE RULE
						{
						$inputs[$pot_input] = ($info['R'.$to_create['rule_id']]['project_id']!='')?$info['R'.$to_create['rule_id']]['project_id']:$info['I'.$to_create['rule_id']]['project_id'];
						}
					
					}
				
				}
			}
	#echo '<pre>';print_r($inputs);exit;
	
	}
	
	return ($inputs);
	
}

#$uid=uid('somthingsomethig#http://123.456.6.3:80/s3db/Cxxx');
#echo '1<pre>';print_r($uid);

function uid($complete_uid)
{#some uid might come with the identifier as the very first letter. For uid to work, uid must come WITHOUT that first identifier even if they are remote or include mothership.
	$complete_uid=trim($complete_uid);
	$qnames = array('s3db'=>$GLOBALS['s3db_info']['deployment']['mothership']);
	$did = str_replace(array('s3db:'),array($qnames['s3db']),$complete_uid);


	
	
	
	if(!ereg('(.*)/(.*)', $complete_uid, $out))
		{
		
		$uid = $complete_uid;
		#$Did=(($_SERVER['HTTPS']!='')?'https://':'http://'.$_SERVER['SERVER_NAME'].'/'.strtok($_SERVER['PHP_SELF'], '/'));
		$Did = $GLOBALS['Did'];

		$MS='';
		
		}
	else {
		$uid = end($out);
				
		#echo $complete_uid;exit;
		#not as for deployment, grab everything between start of string and last backslash. Truncate the 'uid' part of the string
		if(!ereg('(.*)#(.*)$', $complete_uid, $out1))
		{$Did  = substr($complete_uid, 0, strlen($complete_uid)-(strlen($uid)+1));
		
		$MS = 'http://s3db.org';
		
		#Added 27Mar08
		$Did = ereg_replace('^'.substr($uid, 0,1), '', $Did);
		$uid = $Did.'/'.$uid;
		}
		#...except when MS is identified. In this case, Did is everything between cardinal and Uid :-)
		else {
			$MS = $out1[1];
			$Did = substr($complete_uid, strlen($MS)+1, strlen($complete_uid)-strlen($uid)-strlen($MS)-2);
		}
		
	}

	return (compact('uid', 'Did', 'MS'));
}

function fileFound($F)
{extract($F);
	#$F must contain filekey, instance_id/instance_info, rule_id/rule_info, db, user_id
	#Find out if the file already exists in the tmp directory
	$filedata = get_filekey_data($filekey, $db);
	
	#echo '<pre>';print_r($filedata);exit;
	if(!$filedata || !is_array($filedata))
		return False;# '<message>Filekey is not valid.</message>');
	else
	extract($filedata);
	
	#$file_id = get_entry('file_transfer', 'file_id', 'filekey', $filekey, $db);
	#list($name, $extension) = explode('.', $filedata['filename']);
	$a=fileNameAndExtension($filedata['filename']);
	extract($a);
	

	
	#ereg('.([A-Za-z0-9]+)$',$filedata['filename'], $tokens);
	#$extension= $tokens[1];
	#$name = ereg_replace('.([A-Za-z0-9]+)$', '', $filedata['filename']);

	$maindir = $GLOBALS['s3db_info']['server']['db']['uploads_folder'].$GLOBALS['s3db_info']['server']['db']['uploads_file'].'/tmps3db';
				
	$old_file = $maindir.'/'.$file_id.'.'.$extension;
	
		if(!is_file($old_file)) 
		return False; # $something_does_not_exist."<message>File not found, please upload file first.</message>");
		else
			return (True);
}

function fileFoundInfo($F)
{extract($F);
	#$F must contain filekey, instance_id/instance_info, rule_id/rule_info, db, user_id
	#Find out if the file already exists in the tmp directory
	$filedata = get_filekey_data($filekey, $db);
	
	#echo '<pre>';print_r($filedata);exit;
	if(!$filedata || !is_array($filedata))
		return array(False, '<message>Filekey is not valid.</message>');
	else
	extract($filedata);
	
	#$file_id = get_entry('file_transfer', 'file_id', 'filekey', $filekey, $db);
	#list($name, $extension) = explode('.', $filedata['filename']);
	#separate file name from extensions
	$a=fileNameAndExtension($filedata['filename']);
	extract($a);

	#$tmp=explode(".", $filedata['filename']);	
	#ereg('.([A-Za-z0-9]+)$',$filedata['filename'], $tokens);
	#echo '<pre>';print_r($tokens);exit;
	#$extension= $tokens[1];
	
					
	
	$maindir = $GLOBALS['s3db_info']['server']['db']['uploads_folder'].$GLOBALS['s3db_info']['server']['db']['uploads_file'].'/tmps3db';
				
	$old_file = $maindir.'/'.$file_id.'.'.$extension;
	$old_file = ereg_replace('/.$','',$old_file);
		if(!is_file($old_file)) 
		return array(False, $something_does_not_exist."<message>File not found, please upload file first.</message>");
		else
			return array(True, 'old_file'=>$old_file, 'file_name'=>$filedata['filename'], 'file_size'=>filesize($old_file), 'mime_type'=>$extension);
}

//function captureIp()
//{#finds the IP address on the system call ipconfig
//	exec('ipconfig', $output);
//
//$ip = trim($ip);
//
//$prot=($_SERVER['HTTPS']!='')?"https://":"http://";
//$myLocal = $prot.$myIp."/".strtok($_SERVER['PHP_SELF'], '/');
//if(ereg('localhost|127.0.0.1', $_SERVER['SERVER_ADDR']) && $_SERVER['SERVER_ADDR']!=$myIp && $myIp!='')#is ip is valid and we are not there yet and this is a windows server..
//{
//header('Location: '.$myLocal, 1);exit;
//}
//
//	
//	if(is_array($output))
//	foreach ($output as $value) {
//		$value = trim($value);
//		if(ereg('(IP Address)(.+[0-9])', $value, $ipdata))
//		{
//		$rightip =ltrim($ipdata[2], ". . . . . . . . . . . : ");
//		$size[] = strlen($rightip);
//		$ip[] = $rightip;
//		}
//	}
//	#there may be + 1 IP. Return the longest...Lucky guess...
//	if(is_array($size))
//	$shortIp =  $ip[array_search(max($size), $size)];
//	else {
//		$shortIp = $ip[0];
//	}
//	
//	return ($shortIp);
//}

function removePermission($P)
	{
	extract($P);
	$permission_info=array('uid'=>$uid, 'shared_with'=>$shared_with, 'permission_level'=>'000', 'info'=>$info);

	#echo '<pre>';print_r($permission_info);
	$has_permission = has_permission($permission_info, $db);
	#echo $has_permission;
	
	if($has_permission!='' && $has_permission!='000')
		{
		$done=delete_permission(compact('permission_info', 'db', 'user_id', 'info'));
	
		}
	elseif($has_permission=='')
		{
		$done=insert_permission(compact('permission_info', 'db', 'user_id', 'info'));
		}
	
	if($done|| $has_permission=='000')
		$output .= formatReturn($GLOBALS['error_codes']['success'], $uid.' removed from '.$shared_with.'.', $format,'');
	else {
		$output .= formatReturn($GLOBALS['error_codes']['something_went_wrong'], $uid.' was NOT removed from resource '.$shared_with, $format,'');
	}
	return ($output);
	}

function n3UID($uid)
	{
	$uid_info=uid($uid);
	if($uid_info['Did']==$GLOBALS['Did']){$pre=':';$suf='';}
	else{ $pre='<';$suf='>';}
	$n3UID = $pre.$uid_info['uid'].$suf;
	return ($n3UID);
	}

function pushDownload2Header($Z)
	{extract($Z);
			
		
		$maindir = $GLOBALS['s3db_info']['server']['db']['uploads_folder'].$GLOBALS['s3db_info']['server']['db']['uploads_file'];
		
		#retrieve the project information
		#$project_id =  $statement_info['project_id'];
		#$project_info = URI('P'.$project_id, $user_id, $db);

		$folder_code_name = $statement_info['project_folder'];
		$file_name = urlencode($statement_info['file_name']);
		#list ($realname, $extension) = explode('.', $file_name);
		$tmp = fileNameAndExtension($file_name);
		#echo '<pre>';print_r($tmp);exit;
		extract($tmp);
		//echo $realname;exit;
		$file_new_name =  $name.'_'.urlencode($statement_info['project_id'].'_'.$statement_info['resource_id'].'_'.$statement_info['rule_id'].'_'.$statement_info['statement_id']).'.'.$extension;
		
		$file_location = $maindir."/".$folder_code_name."/".$file_new_name;
		#echo $file_location.is_file($file_location);exit;
		
		#echo $file_location;exit;
		if(is_file($file_location))
		$file_handle = fopen($file_location, "r");
		else
			{
			echo formatReturn($GLOBALS['error_codes']['something_went_wrong'], 'This file does not exist', $format, '');
			exit;
			}
		
		$file_name = urlencode($file_name);
		#echo 'download in progress';
		$size=(filesize($file_location))?filesize($file_location):'1';
		$file_contents = fread($file_handle, $size);
         if($_REQUEST['download']!='no' && $_REQUEST['download']!='0' && $_REQUEST['download']!='false'){
		header("Pragma: public");
        header("Expires: 0"); // set expiration time
        header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
        // browser must download file from server instead of cache

        // force download dialog
        header("Content-Type: application/force-download");
        header("Content-Type: application/octet-stream");
        header("Content-Type: application/download");
		#header("Content-Type: ".$ext."");

        // use the Content-Disposition header to supply a recommended filename and
        // force the browser to display the save dialog.
        header("Content-Disposition: attachment; filename=".$file_name."");
        header("Content-Transfer-Encoding: binary");
		
		
		echo $file_contents;
		}
		else {
			//encode the string when the format output is json
			
			 	if($_REQUEST['callback'] || $_REQUEST['format']=='json'){
				
				$callback = ($_REQUEST['jsonp']=='')?(($_REQUEST['callback']=='')?'s3db_json':$_REQUEST['callback']):$_REQUEST['jsonp'];
				$onLoad = ($_REQUEST['onload']=='')?'':'; '.stripslashes($_REQUEST['onload']).((ereg('\(.*\)',$_REQUEST['onload'])?'':'()'));
				$jsonpp = ($_REQUEST['jsonpp']=='')?'':', "'.$_REQUEST['jsonpp'].'"';
				
				$datatype = ($_REQUEST['datatype']!="")?$_REQUEST['datatype']:'string';
				if($datatype=='string'){
				$st = '"';
				$en = '"';
				}
				elseif($datatype=='numeric'){
				$st = '[';
				$file_contents = str_replace(" ",",",$file_contents);
				$en = ']';
				}
				if($datatype!='numeric' && $datatype!='var')
					{$file_contents =  urlencode($file_contents);}
				
				$string2out = $callback.'('.$st.$file_contents.$en.$jsonpp.')'.$onLoad;
				#$string2out = $callback.'('.'"'.urlencode($file_contents).'"'.$jsonpp.')'.$onLoad;

				}
				else {
				$string2out =  $file_contents;
				}
			
				
			
			
			echo $string2out;
		}
		fclose($file_handle);
	}

	function fileNameAndExtension($filename)
	{	
		
		ereg('(.*)\.([A-Za-z0-9]*)$',$filename, $tokens);
		
		$name = $tokens[1];
		$extension= $tokens[2];
		$a=compact('name','extension');
		
		return $a;
	}	


	

function array2xml($array, $letter, $root=false)
{
   static $indent = ''; $xml = '';
   $res = $GLOBALS['s3codes'][$letter];
	if($res=='')
		if($root==false)
			$res='error';
		else
			$res = $root;
	if(is_array($array))
	foreach($array as $key => $value)
   {
	
	if(!is_array($value))
	$value = htmlentities($value);

	if(!is_numeric($key))  
	$xml .= "$indent<$key>";
	else
	$xml .= "$indent<$res>";
    
	if(is_array($value))
      {
         $indent .= "\t";
         $value = "\n" . array2xml($value,  $letter) . ($indent = substr($indent, 1));
      }
      if(!is_numeric($key))  
		$xml .= "$value</$key>\n";
	  else
		$xml .= "$value</$res>\n";
		
   }
   return($xml);
} 
			
function formatReturn($error_code,$message, $format, $id, $root=false)
{

if($_REQUEST['out']=='header' || ($format=='json' && $_SERVER['HTTPS']))
	{
	header("Pragma: public");
	header("Expires: 0"); // set expiration time
	header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
	header("Content-Type: application/force-download");
	header("Content-Type: application/octet-stream");
	header("Content-Type: application/download");
	#header("Content-Type: ".$ext."");

	// use the Content-Disposition header to supply a recommended filename and
	// force the browser to display the save dialog.
	header("Content-Disposition: attachment; filename=".$format."");
	header("Content-Transfer-Encoding: binary");
	}

if($id=='')
	{
	
	$data[0] = array('error_code'=>$error_code, 'message'=>$message);
	
		
	}
else {
	
	
	if(count($id)==1){
	$id_name = array_keys($id);
	$id_name = $id_name[0];
	$data[0] = array('error_code'=>$error_code, 'message'=>$message, $id_name=>$id[$id_name]);
	}
	else {
	
	$data[0] = array('error_code'=>$error_code, 'message'=>$message);	
	foreach ($id as $k=>$v) {
		$data[0][$k]=$v;	
	}
	
	}

	
	#echo '<pre>';print_r($data);
	}
	$letter = 'E';
	$pack= compact('data','format', 'letter', 'root');
	
	return(completeDisplay($pack));
	
	
	
	
}
function xml_encode($data, $letter, $root=false,$namespaces=false)
	{
	#echo '<pre>';print_r($data);
	if($root==false)
		$root='ans';
	header("Content-type: application/xml"); 
	$xml.= "<?xml version='1.0'?>\n";  
	$xml.= "<s3db_xml";
	#include the namespaces
	if(is_array($namespaces) && !empty($namespaces)){
	$usedNS = array();
	foreach ($namespaces as $nInfo) {
		if($nInfo['qname'] && $nInfo['url']){
			if(!in_array($nInfo['qname'], $usedNS)){
			$nS .= ' xmlns:'.$nInfo['qname'].'="'.$nInfo['url'].'"';
			array_push($usedNS,$nInfo['qname']);
			}
		}
	}
	}
		
	$xml .= $nS.">\n".array2xml($data, $letter, $root)."</s3db_xml>";
	return ($xml);
	}


function SIF_encode($data, $letter)
{
	foreach ($data as $key=>$res_data) {
		$ID = $res_data[$GLOBALS['COREletterInv'][$letter]];
		foreach ($res_data as $key=>$value) {
			$sif .= $letter.$ID."\t".$key."\t".str_replace(' ','+', $value)."\n";
		}
		
	}
	return ($sif);
}

function tab_encode($data, $cols)
{	
	if(empty($cols))
	$cols = array_keys($data[0]);
	
	foreach ($data as $key=>$data_info) {
		
		$tabline='';
		
		foreach ($cols as $returnCol) {
			if($tabline!='') $tabline .= "\t";
			$tabline .= $data_info[$returnCol];
		}
		$tab .= $tabline."\n";
	}
	return ($tab);
}
	function get_parser_characters($format)

{
	
	if ($format=='tab') 
	{
	$tab = chr(9);
	$newline = chr(13);
	$middle = chr(9);
	$end_td = chr(9);
	$end_tr = chr(13);
	$end_th =chr(13);

	}
	elseif($format=='html' || $format=='') 
	{
	if($_REQUEST['table.class'])
	$begin_table = '<TABLE class = "'.$_REQUEST['table.class'].'">';
	else
	$begin_table = '<TABLE>';

	$end_table = '</TABLE>';
	$th = '<TH>';
	$end_th ='</TH>';
	$tr = '<TR>';
	$end_tr = '</TR>';
	$td = '<TD>';
	$end_td = '</TD>';

	$middle = $end_td.$td;
	
	
	}
	elseif(ereg('html.', $format)) 
	{
	
	list($lixo, $style) = explode('.', $format);
	echo $begin_table = '<TABLE class = "'.$style.'">';
	$end_table = '</TABLE>';
	$th = '<TH>';
	$end_th ='</TH>';
	$tr = '<TR>';
	$end_tr = '</TR>';
	$td = '<TD>';
	$end_td = '</TD>';

	$middle = $end_td.$td;
	
	
	}
	$parser_char = compact ('middle','begin_table','tr','end_tr','td','end_td','th','end_th','middle','end_table','tab','newline');
	
	#$parser_char = array ('middle'=> $middle, 'begin_table'=>$begin_table, 'tr'=>$tr, 'end_tr'=>$end_tr,'td'=>$td, 'end_td'=>$end_td, 'middle'=>$middle, 'end_table'=>$end_table, 'tab'=>$tab, 'newline'=>$newline, 'th'=>$th,'end_th'=>$end_th);
	return ($parser_char);
}


function rdf_encode_api($data, $letter, $format, $db)
		{
				
				
				
				$m = ModelFactory::getMemModel();
				define('s3db', 'http://www.s3db.org/core#');
				define('rdf', 'http://www.w3.org/1999/02/22-rdf-syntax-ns#');
				define('rdfs', 'http://www.w3.org/2000/01/rdf-schema#');
				
				foreach ($data as $ind=>$res_data) {
					
					
					$ID = $res_data[$GLOBALS['COREletterInv'][$letter]];
					#echo $letter;exit;
					#echo $GLOBALS['COREletterInv'][$letter];exit;
					$D = S3DB_URI_BASE;
					$URI = $D .'/'.$letter.$ID;
					$mResource = new Resource($URI);

					foreach ($res_data as $property=>$value) {
						
						if(!empty($value)){
						if(@in_array($property, @array_keys($GLOBALS['propertyURI'][$letter])))
						{
							$predicate = new Resource($GLOBALS['propertyURI'][$letter][$property]);
						
						#else
						#{	
						#	$predicate = new Resource (s3db.$property);
						#}
						
						if(in_array($property, array_keys($GLOBALS['pointer'])))
						{
						$s = new Statement ($mResource, $predicate, new Resource ($D.'/'.letter($GLOBALS['pointer'][$property]).$value));
						
						}
						elseif(in_array($property, $GLOBALS['COREids']))
						{
						$s = new Statement ($mResource, $predicate, new Resource ($D.'/'.letter($property).$value));
						
						}
						elseif($property=='value' && RuleHasObjectId($ID, $db))

						{
						$s = new Statement ($mResource, $predicate, new Resource ($D.'/I'.$value));
						}
						else
						{
						
						$s = new Statement ($mResource, $predicate, new Literal ($value));
						}
						
						$m->add($s);
						}
						}
					}
				
				if($letter=='S'){
				##Outputalso a statment for the unserialized statement where subject is item_id, pred is rule, object is value
				
				if(RuleHasObjectId($ID, $db))
				$obj = new Resource ($D.'/I'.$data[$ind]['value']);
				else 
				$obj = new Literal ($data[$ind]['value']);	
				
				
				$newS = new Statement (new Resource ($D.'/I'.$data[$ind]['item_id']), new Resource ($D.'/R'.$data[$ind]['rule_id']), $obj);
				$m->add($newS);
				}
				
				if($letter=='R'){
				##Outputalso a statment for the unserialized statement where subject is item_id, pred is rule, object is value
				
				if($data[$ind]['object_id']!='')
				$obj = new Resource ($D.'/C'.$data[$ind]['object_id']);
				else 
				$obj = new Literal ($data[$ind]['object']);	
				
				
				$newS = new Statement (new Resource ($D.'/C'.$data[$ind]['subject_id']), new Resource ($D.'/I'.$data[$ind]['verb_id']), $obj);
				$m->add($newS);
				}

				#And for every element that is part of the core, output a statement that mentions where in the ontology they belong
				$ontoS = new Statement ($mResource, new Resource (rdf.'type'), new Resource(s3db.$GLOBALS['N3Names'][$GLOBALS['s3codes'][$letter]]));
				$m->add($ontoS);
				
				}
				$m->parsedNamespaces = array(rdf=>'rdf', rdfs=>'rdfs', s3db=>'s3db', 'http://purl.org/dc/terms/'=>'dc', 'http://xmlns.com/foaf/0.1/'=>'foaf',$D.'/'=>'D');
				#echo '<pre>';print_r($m);
				$filename = $GLOBALS['uploads'].'tmps3db/'.random_string('10').".rdf";
				$m->saveAs($filename, $format); 
				return file_get_contents($filename);
				
}


function rdf_encode($data,$letter, $format, $db,$namespaces=false,$collected_data=array(), $dont_skip_serialized=true, $dont_skip_core_names=true)
	{	global $timer;
		define('s3db', 'http://www.s3db.org/core');
		define('rdf', 'http://www.w3.org/1999/02/22-rdf-syntax-ns');
		define('rdfs', 'http://www.w3.org/2000/01/rdf-schema');
		
		

		#any more namespaces?
		$uri_deployment = (substr(S3DB_URI_BASE, strlen($uri_deployment), 1)=="/")?S3DB_URI_BASE:S3DB_URI_BASE."/";
		$usedNS = array(''=>$uri_deployment,'dc'=>'http://purl.org/dc/terms/','s3db'=>'http://www.s3db.org/core#','rdf'=>'http://www.w3.org/1999/02/22-rdf-syntax-ns#','rdfs'=>'http://www.w3.org/2000/01/rdf-schema#');
		if($namespaces){
		foreach ($namespaces as $nInfo) {
			 if($nInfo['qname'] && $nInfo['url']){
				if(!in_array($nInfo['qname'], array_keys($usedNS))){
				#define($nInfo['qname'], $nInfo['url']);
				if(!ereg('(\#|\/)$', $nInfo['url'])) $nInfo['url'] = $nInfo['url'].'#';
				$usedNS[$nInfo['qname']] = $nInfo['url'];
				}
			}
		}
		}
		
		##without a letter, we need to know what type of data is this - dictionary data?
		$D = $uri_deployment;
		if(!$letter || !ereg('D|P|S|I|C|R|U|G', $letter)){
			
			if(is_array($data) && !empty($data))
			foreach ($data as $d=>$tuple) {
			   if($tuple['link_id']){
				#good, this is actual links - do not reify them
				#what will be the URI of the uid?
				$D = $uri_deployment;
				$property = $tuple['relation'];$value = $tuple['value'];
				$nsV="";$termV="";$nsP="";$termP="";
				list($nsP,$termP) = explode(":", $tuple['relation']);
				list($nsV,$termV) = explode(":",$tuple['value']);
				$s = $D .$tuple['uid'];
				$triple['s'] = $s;
				
				if($nsP && $termP)
					if($usedNS[$nsP]!=""){
					$triple['p']=str_replace($nsP.":", (eregi("#$",$usedNS[$nsP])?$usedNS[$nsP]:$usedNS[$nsP]."#"), $property);
					$triple['p_type']='uri';
					}
				if($nsV && $termV){
					if($usedNS[$nsV]!=""){
					$triple['o']=str_replace($nsV.':', (eregi("#$",$usedNS[$nsV])?$usedNS[$nsV]:$usedNS[$nsV]."#"), $value);
					$triple['o_type']='uri';
					}						
				}
				else {
						$triple['o'] = $value;
						if(ereg('^http', $value))
						$triple['o_type']='uri';
						else  $triple['o_type']='literal';
					}
				
				

			   }
			   else {#this is the generic purpose rdf encoder
				
				$triple['s'] = $usedNS['s3db'].'n'.$d;
				if(is_array($tuple)){
				foreach ($tuple as $vname=>$vvalue) {
					if(ereg('^http', $vname))
					{$triple['p'] = $vname;
					$triple['p_type']='uri';}
					else {
					$triple['p'] = $usedNS['s3db'].$vname;
					$triple['p_type']='literal';
					}

					$triple['o'] = $vvalue;
					if(ereg('^http', $vvalue))
					{$triple['o_type']='uri';}
					else {$triple['o_type']='literal';}
					$triples[] = $triple; 
				}
				$triple=array();//all triples were already added to the structure
				}
			   }
			if($triple)
			$triples[] = $triple;
			}
		   
		}
		else {
			
			#put data in the triple structure required by the parser
			foreach ($data as $ind=>$res_data) {
			#which element are we trying to retrieve? 
			$ID = $res_data[$GLOBALS['COREletterInv'][$letter]];
			$D = $uri_deployment;
			$s = $D.$letter.$ID;

						##if element is S and user selected to skip serializing the statements, don't add the serialized tripeldont_skip_serialized
						if(ereg("D|P|C|I|R", $letter) || $dont_skip_serialized){
						foreach ($res_data as $property=>$value) {
							
							##for the rules, either accept object or object_id
							if($letter=='R' && $property=='object'){
								 if($res_data['object_id']!=""){
								 $value = $res_data['object_id'];
								  $property='object_id';
								 }
							}

							$nsV="";$termV="";$nsP="";$termP="";
							list($nsP,$termP) = explode(":",$property);
							list($nsV,$termV) = explode(":",$value);
							if($value!=""){
							if(@in_array($property, @array_keys($GLOBALS['propertyURI'][$letter])))
							{
								$p = $GLOBALS['propertyURI'][$letter][$property];
							
								
								if(in_array($property, array_keys($GLOBALS['pointer'])))
								{
								$o = $D.letter($GLOBALS['pointer'][$property]).$value;
								$otype = 'uri';
								}
								elseif(in_array($property, $GLOBALS['COREids']))
								{
								$o = $D.letter($property).$value;
								$otype = 'uri';
								}
								elseif($property=='value')  
								{
									if($collected_data['R'.$res_data['rule_id']])
										{
										if($collected_data['R'.$res_data['rule_id']][0]['object_id']!="")
										{
										 $o = $D.'I'.$value;
										 $otype = 'uri';
										}
										else {
											$o = $value;
											$otype = 'literal';
										}
										}
									elseif(!empty($db) && RuleHasObjectId($ID, $db)){
										$o = $D.'I'.$value;
										$otype = 'uri';
									}
									else {
										$o = $value;
										$otype = 'literal';
									}
									
									
								}	
								
								else
								{
								
								$o = $value;
								$otype = 'literal';
								}
								$triple['s']=$s;
								$triple['p']=$p;
								$triple['o']=$o;
								$triple['s_type']='uri';
								$triple['o_type']=$otype;
							$triples[] = $triple;					
							}
							elseif($nsP && $termP) {
								#replace nsP by the corresponding namespace and build a triple
								
								if($usedNS[$nsP]!=""){
								$triple['s']=$s;
								$triple['s_type']='uri';
								$triple['p']=str_replace($nsP.":", (eregi("#$",$usedNS[$nsP])?$usedNS[$nsP]:$usedNS[$nsP]."#"), $property);
								}
								
								if($nsV && $termV){
									if($usedNS[$nsV]!=""){
										$triple['o']=str_replace($nsV.':', (eregi("#$",$usedNS[$nsV])?$usedNS[$nsV]:$usedNS[$nsV]."#"), $value);
										$triple['o_type']='uri';
									}
									
								}
								else {
									$triple['o'] = $value;
									if(ereg('^http', $value))
									$triple['o_type']='uri';
									else  $triple['o_type']='literal';
								}
								if($triple)
								$triples[] = $triple;
							}
						
						}

					}
					}

			if($letter=='S'){
			##Outputalso a statment for the unserialized statement where subject is item_id, pred is rule, object is value
			
			
			
			if(!empty($collected_data) &&  !empty($collected_data['R'.$data[$ind]['rule_id']]))
			{
				
				if($collected_data['R'.$data[$ind]['rule_id']][0]['object_id']){
					$obj = $D.'I'.$data[$ind]['value'];
					$objType = 'uri';
				}
				else {
					$obj = $data[$ind]['value'];	
					$objType = 'literal';
				}
			
			}
			elseif(!empty($db) && RuleHasObjectId($ID, $db))
			{$obj = $D.'I'.$data[$ind]['value'];
			$objType = 'uri';
			}
			else 
			{$obj = $data[$ind]['value'];	
			$objType = 'literal';
			}

			$triple['s']=$D.'I'.$data[$ind]['item_id'];
			$triple['p']=$D.'R'.$data[$ind]['rule_id'];
			$triple['o']=$obj;
			$triple['s_type']='uri';
			$triple['o_type']=$objType;
			$triples[] = $triple;
			
			}
			#And for every element that is part of the core, output a statement that mentions where in the ontology they belong
			if($dont_skip_core_names){
			$triple['s']=$D .$letter.$ID;
			$triple['p']=$usedNS['rdf']."type";
			$triple['o']=$usedNS['s3db'].$GLOBALS['N3Names'][$GLOBALS['s3codes'][$letter]];
			$triple['s_type']='uri';
			$triple['o_type']='uri';
			$triples[] = $triple;
			}
		
		
		}
		}
	if($format=='array'){
	$rdf_doc=$triples;
	}

	if($format=='rdf'){

	$a['ns']=$usedNS;
	$parser = ARC2::getComponent('RDFXMLParser', $a);
	$index = ARC2::getSimpleIndex($triples, false) ; /* false -> non-flat version */
	$rdf_doc = $parser->toRDFXML($index,$usedNS);
	
	}
	elseif($format=='turtle'){
	$a['ns'] = $usedNS;
	$parser = ARC2::getComponent('TurtleParser', $a);
	$index = ARC2::getSimpleIndex($triples, false) ; /* false -> non-flat version */
	$rdf_doc = $parser->toTurtle($index,$usedNS);
	}
	elseif($format=='n3'){
	$a['ns'] = $usedNS;
	$parser = ARC2::getComponent('NTriplesSerializer', $a);
	$index = ARC2::getSimpleIndex($triples, false) ; /* false -> non-flat version */
	$rdf_doc = $parser->toNTriples($index, $usedNS);
	}
	elseif($format=='rdf-json'){
	$a['ns'] = $usedNS;
	$parser = ARC2::getComponent('RDFJSONSerializer', $a);
	$index = ARC2::getSimpleIndex($triples, false) ; /* false -> non-flat version */
	$rdf_doc = $parser->toRDFJSON($index, $usedNS);
	
	$callback = ($_REQUEST['jsonp']=='')?(($_REQUEST['callback']=='')?'s3db_json':$_REQUEST['callback']):$_REQUEST['jsonp'];
	$onLoad = ($_REQUEST['onload']=='')?'':'; '.stripslashes($_REQUEST['onload']).((ereg('\(.*\)',$_REQUEST['onload'])?'':'()'));
	$jsonpp = ($_REQUEST['jsonpp']=='')?'':', "'.$_REQUEST['jsonpp'].'"';
	
	$rdf_doc = $callback.'('.$rdf_doc.$jsonpp.')'.$onLoad;

	
	}



	return ($rdf_doc);

}


function rdf_encode_old($data,$letter, $format, $db,$namespaces=false)
{	global $timer;
	define('s3db', 'http://www.s3db.org/core');
	define('rdf', 'http://www.w3.org/1999/02/22-rdf-syntax-ns');
	define('rdfs', 'http://www.w3.org/2000/01/rdf-schema');
	
	#any more namespaces?
	$usedNS = array(''=>$GLOBALS['URI'],'dc'=>'http://purl.org/dc/terms/','s3db'=>'http://www.s3db.org/core#','rdf'=>'http://www.w3.org/1999/02/22-rdf-syntax-ns#','rdfs'=>'http://www.w3.org/2000/01/rdf-schema#');
	if($namespaces){
	foreach ($namespaces as $nInfo) {
		 if($nInfo['qname'] && $nInfo['url']){
			if(!in_array($nInfo['qname'], array_keys($usedNS))){
			#define($nInfo['qname'], $nInfo['url']);
			if(!ereg('(\#|\/)$', $nInfo['url'])) $nInfo['url'] = $nInfo['url'].'#';
			$usedNS[$nInfo['qname']] = $nInfo['url'];
			}
		}
	}
	}
	##without a letter, we need to know what type of data is this - dictionary data?
		if(!$letter || !ereg('D|P|S|I|C|R|U|G', $letter)){
			foreach ($data as $d=>$tuple) {
			   if($tuple['link_id']){
				#good, this is actual links - do not reify them
				#what will be the URI of the uid?
				$D = S3DB_URI_BASE;
				$property = $tuple['relation'];$value = $tuple['value'];
				$nsV="";$termV="";$nsP="";$termP="";
				list($nsP,$termP) = explode(":", $tuple['relation']);
				list($nsV,$termV) = explode(":",$tuple['value']);
				$s = $D .'/'.$tuple['uid'];
				$triple['s'] = $s;
				
				if($nsP && $termP)
					if($usedNS[$nsP]!=""){
					$triple['p']=str_replace($nsP.":", (eregi("#$",$usedNS[$nsP])?$usedNS[$nsP]:$usedNS[$nsP]."#"), $property);
					$triple['p_type']='uri';
					}
				if($nsV && $termV){
					if($usedNS[$nsV]!=""){
					$triple['o']=str_replace($nsV.':', (eregi("#$",$usedNS[$nsV])?$usedNS[$nsV]:$usedNS[$nsV]."#"), $value);
					$triple['o_type']='uri';
					}						
				}
				else {
						$triple['o'] = $value;
						if(ereg('^http', $value))
						$triple['o_type']='uri';
						else  $triple['o_type']='literal';
					}
				
				

			   }
			if($triple)
			$triples[] = $triple;
			}
		
		}
		else {
		
			#put data in the triple structure required by the parser
			foreach ($data as $ind=>$res_data) {
				#which element are we trying to retrieve? 
				$ID = $res_data[$GLOBALS['COREletterInv'][$letter]];
				$D = S3DB_URI_BASE;
				$s = $D .'/'.$letter.$ID;
				
							
							foreach ($res_data as $property=>$value) {
								$nsV="";$termV="";$nsP="";$termP="";
								list($nsP,$termP) = explode(":",$property);
								list($nsV,$termV) = explode(":",$value);
								if(!empty($value)){
								if(@in_array($property, @array_keys($GLOBALS['propertyURI'][$letter])))
								{
									$p = $GLOBALS['propertyURI'][$letter][$property];
								
														
									if(in_array($property, array_keys($GLOBALS['pointer'])))
									{
									$o = $D.'/'.letter($GLOBALS['pointer'][$property]).$value;
									$otype = 'uri';
									}
									elseif(in_array($property, $GLOBALS['COREids']))
									{
									$o = $D.'/'.letter($property).$value;
									$otype = 'uri';
									}
									elseif($property=='value' && RuleHasObjectId($ID, $db))

									{
									$o = $D.'/I'.$value;
									$otype = 'uri';
									}
									else
									{
									
									$o = $value;
									$otype = 'literal';
									}
									$triple['s']=$s;
									$triple['p']=$p;
									$triple['o']=$o;
									$triple['s_type']='uri';
									$triple['o_type']=$otype;
								$triples[] = $triple;					
								}
								elseif($nsP && $termP) {
									#replace nsP by the corresponding namespace and build a triple
									
									if($usedNS[$nsP]!=""){
									$triple['s']=$s;
									$triple['s_type']='uri';
									$triple['p']=str_replace($nsP.":", (eregi("#$",$usedNS[$nsP])?$usedNS[$nsP]:$usedNS[$nsP]."#"), $property);
									}
									
									if($nsV && $termV){
										if($usedNS[$nsV]!=""){
											$triple['o']=str_replace($nsV.':', (eregi("#$",$usedNS[$nsV])?$usedNS[$nsV]:$usedNS[$nsV]."#"), $value);
											$triple['o_type']='uri';
										}
										
									}
									else {
										$triple['o'] = $value;
										if(ereg('^http', $value))
										$triple['o_type']='uri';
										else  $triple['o_type']='literal';
									}
									if($triple)
									$triples[] = $triple;
								}
							
							}

						}
			
			if($letter=='S'){
				##Outputalso a statment for the unserialized statement where subject is item_id, pred is rule, object is value
				
				if(RuleHasObjectId($ID, $db))
				{$obj = $D.'/I'.$data[$ind]['value'];
				$objType = 'uri';
				}
				else 
				{$obj = $data[$ind]['value'];	
				$objType = 'literal';
				}
				
				$triple['s']=$D.'/I'.$data[$ind]['item_id'];
				$triple['p']=$D.'/R'.$data[$ind]['rule_id'];
				$triple['o']=$obj;
				$triple['s_type']='uri';
				$triple['o_type']=$objType;
				$triples[] = $triple;
				
				}
			
			#And for every element that is part of the core, output a statement that mentions where in the ontology they belong
			$triple['s']=$D .'/'.$letter.$ID;
			$triple['p']=$usedNS['rdf']."type";
			$triple['o']=$usedNS['s3db'].$GLOBALS['N3Names'][$GLOBALS['s3codes'][$letter]];
			$triple['s_type']='uri';
			$triple['o_type']='uri';
			$triples[] = $triple;
			
			
			}
	}
if($format=='rdf'){

$a['ns']=$usedNS;
$parser = ARC2::getComponent('RDFXMLParser', $a);
$index = ARC2::getSimpleIndex($triples, false) ; /* false -> non-flat version */
$rdf_doc = $parser->toRDFXML($index,$usedNS);

}
elseif($format=='turtle'){
$a['ns'] = $usedNS;
$parser = ARC2::getComponent('TurtleParser', $a);
$index = ARC2::getSimpleIndex($triples, false) ; /* false -> non-flat version */
$rdf_doc = $parser->toTurtle($index,$usedNS);
}
elseif($format=='n3'){
$a['ns'] = $usedNS;
$parser = ARC2::getComponent('NTriplesSerializer', $a);
$index = ARC2::getSimpleIndex($triples, false) ; /* false -> non-flat version */
$rdf_doc = $parser->toNTriples($index, $usedNS);
}



return ($rdf_doc);

}




function parse_xml_query($q)
{	extract($q);
 	$xml = stripslashes($query);
   
	##When value brings tags, they will be parsed along with the rest of the xml. Avoid that by encoding it first.

	ereg('<authentication_id>(.*)</authentication_id>', $xml, $val);
	if($val[1]!='')
	$xml = str_replace('<authentication_id>'.$val[1].'</authentication_id>', '<authentication_id>'.base64_encode($val[1]).'</authentication_id>', $xml);

	if (ereg('^http.*', $xml))
	{
	$xmlFile= @file_get_contents($xml);
	
	if(empty($xmlFile))
		{
		echo (formatReturn($GLOBALS['error_codes']['something_missing'],'Query file is not valid', $_REQUEST['format'],''));
		exit;
		}
	else {
		$xml = $xmlFile;
		ereg('<authentication_id>(.*)</authentication_id>', $xml, $val);
		
		if($val[1]!='')
		$xml = str_replace('<authentication_id>'.$val[1].'</authentication_id>', '<authentication_id>'.base64_encode($val[1]).'</authentication_id>', $xml);

	}
	}
    if($xml!=''){
	
	try {
    $tmp = @simplexml_load_string($xml);
	if(!$tmp){
	$tmp = @simplexml_load_string(urldecode($xml));
	}
	 	
	if(!$tmp) {
        throw new Exception(formatReturn($GLOBALS['error_codes']['something_went_wrong'],'XML query is badly formatted. Please check your start/end tags', $_REQUEST['format'],''));
    }
	
	}
	catch(Exception $e) {
		
		echo formatReturn($GLOBALS['error_codes']['something_went_wrong'],$e->getMessage(), $format,'');
		exit;
	}
	$xml = $tmp;
	
	$s3ql = $xml;
	$s3ql = get_object_vars($s3ql);
    $s3ql['key'] = ($s3ql['key']!='')?$s3ql['key']:$key;
	
	#read data in the "where" tag
	if(get_object_vars($s3ql['where'])!='')
	$s3ql['where'] = get_object_vars($s3ql['where']);
	elseif($_REQUEST['where']!='')
	$s3ql['where'] = $_REQUEST['where'];
	
	if($s3ql['where']['authentication_id']!='')
		$s3ql['where']['authentication_id'] = base64_decode($s3ql['where']['authentication_id']);
	
	
	}
	return ($s3ql);
}

function rdfRestore($F)
	{extract($F);
		include(S3DB_SERVER_ROOT.'/breakAndMendFile.php');
		include(S3DB_SERVER_ROOT.'/rdfRead.php');
		include(S3DB_SERVER_ROOT.'/rdfWrite.php');

		$fileImportName = md5($inputs['file']);
		##create a copy of the original file
		#just in case...
		if(is_writable($GLOBALS['uploads'])){
		if(substr($GLOBALS['uploads'], strlen($GLOBALS['uploads'])-1,1)!='/')
		$newName = $GLOBALS['uploads'].'/'.$fileImportName;
		else {
			$newName = $GLOBALS['uploads'].$fileImportName;
		}
		}
		else {
			$newName = S3DB_SERVER_ROOT."/tmp/".$fileImportName;
		}

		
		$t=copy($file, $newName);
		
		
		if(!$t){
		if(!is_readable($file)){
		echo "Could not move file. User does not have permission to read ".$file." ";
		exit;
		}
		if(!is_writeable(S3DB_SERVER_ROOT."/tmp/")){
			echo "User does not have permission to write to ".S3DB_SERVER_ROOT."/tmp/";
			exit;
		}
		}
		$file= $newName;
		breakAndMendFile($file);
		
		$F = compact('file','db','user_id', 'inputs');

		## Read the file and write the serialized s3db output into a text file
		$s3db = rdfRead($F);
		

		##Read that text file and write it to s3db
		rdfWrite($F);

		#use this structure to insert a user in the right collection
		$newIds = $s3db;
		if(is_array($GLOBALS['idReplacements'])){
		foreach ($s3db as $letter=>$data) {
				foreach ($data as $ind=>$data_info) {
					foreach ($data_info as $attr=>$value) {
						
						if(in_array($value, array_keys($GLOBALS['idReplacements']))){
						$newIds[$letter][$ind][$attr] = $GLOBALS['idReplacements'][$value];
					}
					}
					
				}
		}


		}
		
		return ($newIds);
	}

function discover_url($dep_id,$db='',$user_id='',$key='')
{	
	$dep_idQ = ereg_replace('^D','',$dep_id);
	$sql="select * from s3db_deployment where deployment_id = '".$dep_idQ."'";
	$db->query($sql);
	
	if($db->next_record()){
		$url = $db->f('url');
	}
	else {
		#retrieve url from mothership
		$ms = $GLOBALS['s3db_info']['deployment']['mothership'];
		$s3ql['url']=$ms;
		$s3ql['key']=$key;
		$s3ql['from']='deployment';
		$s3ql['where']['deployment_id']=$dep_idQ;
		$s3ql['format']='php';
		$Q=S3QLquery($s3ql);
		while (!$a && $try<5) {
			$a=fopen($Q,"r");	
			$try++;
		}
		if(!$a){
		$msg = 'Mothership Not available';
		}
		else {
			$b=stream_get_contents($a);
			$urlInfo = unserialize($b);
			if(is_array($urlInfo) && $urlInfo[0]['url']!='')
				$url = $urlInfo[0]['url'];
		}
		
		
	}
	if(!$url){
	return (array(False, $msg));
	}
	else {
		return (array(true, $url));
	}
}

##Added 04/05/09 to support api.php
function translate_id_to_tables($ids, $element)
{
	foreach ($ids as $id) {
	
	if(!empty($GLOBALS['s3map'][$GLOBALS['plurals'][$element]][$id])){
		
		$mother_ids[] = $GLOBALS['s3map'][$GLOBALS['plurals'][$element]][$id];	
	}
	else {
		
		$mother_ids[] = $id;
	}
	}
return ($mother_ids);
}

function is_uid($str)
	{
	#not fully tested yet
	if(is_numeric($str)){ return (true);	}
	if(ereg('^D[0-9]+(P|C|R|I|S)[0-9]+', $str)) {return (true);}
	if(ereg('^http(.*)/(P|C|R|I|S)[0-9]+',$str)) {return (true);}
	return (false);
	}

function codes2tables($codes)
	{
	if(!is_array($codes)) $codes=array();
	
	foreach ($codes as $code) {
		$element = $GLOBALS['s3codes'][$code];
		$table  =$GLOBALS['s3tables'][$element];
		$tables[] =  $table;
	}		
	return ($tables);
	}

function get_open_gates($gate,$pregates = array())
	{
	$inherits = $GLOBALS['inherit_code'][$gate];
	
	if (!empty($inherits)) {
		foreach ($inherits as $tmpgate) {
			if(!in_array($tmpgate, $pregates))
			{array_push($pregates, $tmpgate);}
			
			$pregates=get_open_gates($tmpgate,$pregates);
			
		}
		
	
	}
	
	
	return ($pregates);
	}

function triplize_data($data, $letter,$namespaces, $db=array(), $user_id, $collected_data=array())
{
	#any more namespaces?
		$usedNS = array(''=>$GLOBALS['URI'],'dc'=>'http://purl.org/dc/terms/','s3db'=>'http://www.s3db.org/core#','rdf'=>'http://www.w3.org/1999/02/22-rdf-syntax-ns#','rdfs'=>'http://www.w3.org/2000/01/rdf-schema#');
		if($namespaces){
		foreach ($namespaces as $nInfo) {
			 if($nInfo['qname'] && $nInfo['url']){
				if(!in_array($nInfo['qname'], array_keys($usedNS))){
				#define($nInfo['qname'], $nInfo['url']);
				if(!ereg('(\#|\/)$', $nInfo['url'])) $nInfo['url'] = $nInfo['url'].'#';
				$usedNS[$nInfo['qname']] = $nInfo['url'];
				}
			}
		}
		}
		
		##without a letter, we need to know what type of data is this - dictionary data?
		if(!$letter || !ereg('D|P|S|I|C|R|U|G', $letter)){
			
			
			foreach ($data as $d=>$tuple) {
			   if($tuple['link_id']){
				#good, this is actual links - do not reify them
				#what will be the URI of the uid?
				$D = S3DB_URI_BASE;
				$property = $tuple['relation'];$value = $tuple['value'];
				$nsV="";$termV="";$nsP="";$termP="";
				list($nsP,$termP) = explode(":", $tuple['relation']);
				list($nsV,$termV) = explode(":",$tuple['value']);
				$s = $D .'/'.$tuple['uid'];
				$triple['s'] = $s;
				
				if($nsP && $termP)
					if($usedNS[$nsP]!=""){
					$triple['p']=str_replace($nsP.":", (eregi("#$",$usedNS[$nsP])?$usedNS[$nsP]:$usedNS[$nsP]."#"), $property);
					$triple['p_type']='uri';
					}
				if($nsV && $termV){
					if($usedNS[$nsV]!=""){
					$triple['o']=str_replace($nsV.':', (eregi("#$",$usedNS[$nsV])?$usedNS[$nsV]:$usedNS[$nsV]."#"), $value);
					$triple['o_type']='uri';
					}						
				}
				else {
						$triple['o'] = $value;
						if(ereg('^http', $value))
						$triple['o_type']='uri';
						else  $triple['o_type']='literal';
					}
				
				

			   }
			if($triple)
			$triples[] = $triple;
			}
		   
		}
		else {
			
			#put data in the triple structure required by the parser
			foreach ($data as $ind=>$res_data) {
			#which element are we trying to retrieve? 
			$ID = $res_data[$GLOBALS['COREletterInv'][$letter]];
			$D = S3DB_URI_BASE;
			$s = $D .'/'.$letter.$ID;

						
						foreach ($res_data as $property=>$value) {
							$nsV="";$termV="";$nsP="";$termP="";
							list($nsP,$termP) = explode(":",$property);
							list($nsV,$termV) = explode(":",$value);
							if(!empty($value)){
							if(@in_array($property, @array_keys($GLOBALS['propertyURI'][$letter])))
							{
								$p = $GLOBALS['propertyURI'][$letter][$property];
							
													
								if(in_array($property, array_keys($GLOBALS['pointer'])))
								{
								$o = $D.'/'.letter($GLOBALS['pointer'][$property]).$value;
								$otype = 'uri';
								}
								elseif(in_array($property, $GLOBALS['COREids']))
								{
								$o = $D.'/'.letter($property).$value;
								$otype = 'uri';
								}
								elseif($property=='value' && !empty($collected_data) && $collected_data[$res_data['rule_id']]['object_id']!=""){
								$o = $D.'/I'.$value;
								$otype = 'uri';
								}
								
								elseif($property=='value' && !empty($db) && RuleHasObjectId($ID, $db))

								{
								$o = $D.'/I'.$value;
								$otype = 'uri';
								}
								else
								{
								
								$o = $value;
								$otype = 'literal';
								}
								$triple['s']=$s;
								$triple['p']=$p;
								$triple['o']=$o;
								$triple['s_type']='uri';
								$triple['o_type']=$otype;
							$triples[] = $triple;					
							}
							elseif($nsP && $termP) {
								#replace nsP by the corresponding namespace and build a triple
								
								if($usedNS[$nsP]!=""){
								$triple['s']=$s;
								$triple['s_type']='uri';
								$triple['p']=str_replace($nsP.":", (eregi("#$",$usedNS[$nsP])?$usedNS[$nsP]:$usedNS[$nsP]."#"), $property);
								}
								
								if($nsV && $termV){
									if($usedNS[$nsV]!=""){
										$triple['o']=str_replace($nsV.':', (eregi("#$",$usedNS[$nsV])?$usedNS[$nsV]:$usedNS[$nsV]."#"), $value);
										$triple['o_type']='uri';
									}
									
								}
								else {
									$triple['o'] = $value;
									if(ereg('^http', $value))
									$triple['o_type']='uri';
									else  $triple['o_type']='literal';
								}
								if($triple)
								$triples[] = $triple;
							}
						
						}

					}

			if($letter=='S'){
			##Outputalso a statment for the unserialized statement where subject is item_id, pred is rule, object is value
			if(!empty($collected_data) &&  !empty($collected_data[$data[$ind]['rule_id']]) && $collected_data[$data[$ind]['rule_id']]['object_id'])
			{
			$obj = $D.'/I'.$data[$ind]['value'];
			$objType = 'uri';
			}
			elseif(!empty($db) && RuleHasObjectId($ID, $db))
			{$obj = $D.'/I'.$data[$ind]['value'];
			$objType = 'uri';
			}
			else 
			{$obj = $data[$ind]['value'];	
			$objType = 'literal';
			}

			$triple['s']=$D.'/I'.$data[$ind]['item_id'];
			$triple['p']=$D.'/R'.$data[$ind]['rule_id'];
			$triple['o']=$obj;
			$triple['s_type']='uri';
			$triple['o_type']=$objType;
			$triples[] = $triple;

			}
			#And for every element that is part of the core, output a statement that mentions where in the ontology they belong
			$triple['s']=$D .'/'.$letter.$ID;
			$triple['p']=$usedNS['rdf']."type";
			$triple['o']=$usedNS['s3db'].$GLOBALS['N3Names'][$GLOBALS['s3codes'][$letter]];
			$triple['s_type']='uri';
			$triple['o_type']='uri';
			$triples[] = $triple;
		
		
		}
		}
	return ($triples);	
}

function calculatePropagation($permission_level, $uid, $shared_with_user, $user_id, $db)
{	
	ereg('^([0-2])([0-2])([0-2])$', $permission_level, $tmpPLd);	
	
	
	if($tmpPLd[3]==2){
		$propagates = 1;
		$propagation_level = $permission_level;
		}
	elseif($tmpPLd[3]==1)
	{	
		$propagates = (createdBy($uid, $db)==$shared_with_user)?1:0;
		$propagation_level = (($tmpPLd[1]==0)?'0':($tmpPLd[1]/$tmpPLd[1])).($tmpPLd[2]/$tmpPLd[2]).'1';
	}
	elseif($tmpPLd[3]==0)
	{
		$propagates = 0;
		$propagation_level = '000';
	}
	$return =compact('propagates','propagation_level');
	
	
	return ($return);
}

function scriptInputs($_REQUEST, $argv)
{
	if(!empty($_REQUEST)){
		$inputs = $_REQUEST;
	}
	else {
		$tmp = $argv;
		
		foreach ($tmp as $key_arg) {
			ereg('(.*)=(.*)',$key_arg,$d);
			if($d){
			$inputs[$d[1]] = $d[2];
			}
		}
	}
	return ($inputs);
}

function bindRemoteUser($I)
{	 global $timer;
	##$bound = bindRemoteUser(compact('remote_user_id', 'user_id', 'db','user_info'))
	###Create a binding account for remote users; although this is not mandatory for authentication, it maintains consistency within the deployment, and giving parent user (user_id) power to change the default permission level
	extract($I);
   
	$element = 'user';
	
	##Does this remote user exist already locally? This may have been checked before; remove this part if that is true
	
	$local_info = s3info($element, $remote_user_id, $db);
	
	if($timer) $timer->setMarker('Binding remote user');
	
	if(!$local_info){
		
		$password = random_string(15);
		foreach ($GLOBALS['dbstruct']['users'] as $inp_name) {
			
			$to_create[$inp_name] = $remote_user_info[$inp_name];	
		}
		
		##Replace here to create with data that need to be local for this deployment
		$to_create['account_lid'] = $to_create['account_lid'].'@D'.ereg_replace('^D','',$GLOBALS['Did']); ##Account login must be different
		$to_create['account_type']='r';
		$to_create['account_group']='r';
		$to_create['created_by']=$user_id;
		$to_create['password'] = $password;
		
		##Temporarily create an account email for this user; to email will be sent but user email does need to be updated upon first login
		$to_create['account_email'] = $remote_user_id.'@D'.ereg_replace('^D','',$GLOBALS['Did']);
		$inputs = gatherInputs(compact('element', 'user_id','db', 'format','to_create'));
		$inputs['account_id']=$remote_user_id;
		
		list($valid, $message, $id) = insert_s3db(compact('element', 'inputs', 'user_id', 'db'));
	
	##Send the user's owner an email with the password for the new user
	include_once(S3DB_SERVER_ROOT.'/s3dbcore/s3email.php');
	
	##Small fix for google emails, as authority = google email does get the '.com' portion
	$user_email = ereg_replace('@gmail$','@gmail.com', $user_email);
	$message .= sprintf("%s\n\n", 'Dear '.$username);
	$message .= sprintf("%s\n", "You have created an account for remote user ".$remote_user_id." in S3DB deployment ".S3DB_URI_BASE);
	$message .= sprintf("%s\n", $inputs['account_uname']." can now login at ".S3DB_URI_BASE.'login.php');
	$message .= sprintf("%s\n", "Login: ".$to_create['account_lid']);
	$message .= sprintf("%\n\n", 'Password: '.$password);
	$message .= sprintf("%s\n",'The S3DB team.(http://www.s3db.org)');
	$message .= sprintf("%s\n\n",'Note: Please do not reply to this email, this is an automated message');
	
	
	#send_email(array('email'=>array($user_email), 'subject'=>'Account for remote user created', 'message'=>$message));
	if($valid) {return (true);}
	else {
		return (false);
	}
	}
	 else {
		return (true);
	 }
	
}
?>