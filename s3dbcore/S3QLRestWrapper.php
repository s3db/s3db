<?php

function S3QLRestWrapper($W) {
	#Function S3QLRestWrapper builds the S3QL query to access a given S3DB resource
	#INPUT: W is an array with at least key, element and UID
	#element is any of the S3COre elements: class, instance, resource, statement; or S3DB permission elements: project, user; UID is the UID of the element specified in element; key is the key to enter s3db from any location
	#OUTPUT: a string, containing the URI with the information on the input element UID 

	if ($W['element'] == 'instance' || $W['element'] == 'class') $W['UIDType'] = 'resource';
	else $W['UIDType'] = $W['element'];

	if ($_SERVER['HTTP_X_FORWARDED_HOST'] != '') $hostURL = $_SERVER['HTTP_X_FORWARDED_HOST'];
	else $hostURL = $_SERVER['HTTP_HOST'];

	$wrap['url'] .= 'http://'.$hostURL.S3DB_URI_BASE.'/S3QL.php?query=';
	$wrap['s3ql'] .= '<S3QL>';
	$wrap['s3ql'] .= '<key>'.$W['key'].'</key>';
	$wrap['uri'] .= '<select>*</select>';
	$wrap['uri'] .= '<from>';
	$wrap['uri'] .= $W['element'].'s';
	$wrap['uri'] .= '</from>';
	if($W['UIDType']!='')
		{$wrap['uri'] .= '<where>';
		$wrap['uri'] .= '<'.$W['UIDType'].'_id>';
		$wrap['uri'] .= $W['UID'];
		$wrap['uri'] .= '</'.$W['UIDType'].'_id>';
		$wrap['uri'] .= '</where>';
		}
	$wrap['uri'] .= '</S3QL>';

	#$wrap = $wrap['url'].$wrap['s3ql'].$wrap['uri'];

	return $wrap;
}

function S3QLquery($s3ql)
	{
	#Function S3QLSyntax builds the S3QL query for any remote uri
	#INPUT: $s3ql is an array with at least key
	#OUTPUT: a string, containing the URI with the information on the input element UID 

	if($s3ql['url']=='')
	if ($_SERVER['HTTP_X_FORWARDED_HOST'] != '') 
		$s3ql['url'] = $_SERVER['HTTP_X_FORWARDED_HOST'];
	else $s3ql['url'] = $_SERVER['HTTP_HOST'];

	#when a key is not provided, then assume the user has a remote key that corresponds to the local key

	if($s3ql['key']=='')
		$s3ql['key'] = get_entry('access_keys', 'key_id', 'account_id', $s3ql['user_id'], $s3ql['db']);
	

	$wrap .= $s3ql['url'].'/S3QL.php?';
	
	if ($s3ql['user_id']!='') {
		$wrap .= '&user_id='.$s3ql['user_id'];
	}
	if ($s3ql['format']!='') {
		$wrap .= '&format='.$s3ql['format'];
	}
	
	$wrap .= '&query=<S3QL>';
	$wrap .= '<key>'.$s3ql['key'].'</key>';

	#remove the elements already used to build the query, keep the rest
	$s3ql = array_diff_key($s3ql, array('url'=>'', 'key'=>'', 'db'=>'','user_id'=>'','format'=>''));

	foreach($s3ql as $field=>$value)
		{
		if(!is_array($s3ql[$field])) #if is not an array, just build the simple xml
		$wrap .= '<'.$field.'>'.$s3ql[$field].'</'.$field.'>';
		else #for arrays, build the nested xml
		{$wrap .= '<'.$field.'>';
		foreach($value as $subfield=>$subvalue)
			{
			$wrap .= '<'.$subfield.'>'.$subvalue.'</'.$subfield.'>';
			}
		$wrap .= '</'.$field.'>';
		}
		}
	$wrap .= '</S3QL>';
	
	


	return $wrap;
	}

function S3QLquery2($s3ql)
	{
	#Function S3QLSyntax builds the S3QL query for any remote uri
	#INPUT: $s3ql is an array with at least key
	#OUTPUT: a string, containing the URI with the information on the input element UID 

	if($s3ql['url']=='')
	if ($_SERVER['HTTP_X_FORWARDED_HOST'] != '') 
		$s3ql['url'] = $_SERVER['HTTP_X_FORWARDED_HOST'];
	else $s3ql['url'] = $_SERVER['HTTP_HOST'];

	#when a key is not provided, then assume the user has a remote key that corresponds to the local key

	if($s3ql['key']=='')
		$s3ql['key'] = get_entry('access_keys', 'key_id', 'account_id', $s3ql['user_id'], $s3ql['db']);

	$wrap .= $s3ql['url'].'/S3QL.php?query=';
	$wrap .= '<S3QL>';
	$wrap .= '<key>'.$s3ql['key'].'</key>';

	#remove the elements already used to build the query, keep the rest
	$s3ql = array_diff_key($s3ql, array('url'=>'', 'key'=>'', 'user_id'=>'', 'db'=>''));

	foreach($s3ql as $field=>$value)
		{
		if(!is_array($s3ql[$field])) #if is not an array, just build the simple xml
		$wrap .= '<'.$field.'>'.$s3ql[$field].'</'.$field.'>';
		else #for arrays, build the nested xml
		{$wrap .= '<'.$field.'>';
		foreach($value as $subfield=>$subvalue)
			{
			$wrap .= '<'.$subfield.'>'.$subvalue.'</'.$subfield.'>';
			}
		$wrap .= '</'.$field.'>';
		}
		}
	$wrap .= '</S3QL>';



	return $wrap;
	}

function S3QLbuilder($D)
			{
			global $regexp, $dbstruct;
			extract($D);
			#need permission
			$elements = array('class', 'rule', 'instance', 'statement');
			$actions = array('from', 'insert', 'edit', 'delete');
				$D['table'] = $D['from'];
				$D['cols'] = $dbstruct[$D['table']];
			
				if(in_array($D['table'], $elements))
				{
				$D['project_id']=$regexp." '^".$D['where']['project_id']."$'";
				$D['permission']=$regexp." '(^|_)".$D['where']['project_id']."_'";
				$D['cols'] = array_diff($D['cols'], array('project_id', 'permission'));
				}
			if(is_array($D['where']))
				foreach($D['cols'] as $col)
				{	if ($D['where'][$col]!='')
					$D[$col] =$D['where'][$col];
				}
				
			return $D;
			}
	
	function S3QLinfo($element, $element_id, $user_id, $db)
	{
	$s3idNames = array('projects'=>'project_id', 'classes'=>'class_id', 'instances'=>'instance_id', 'rules'=>'rule_id', 'statements'=>'statement_id', 'users'=>'account_id', 'groups'=>'group_id');

	if (!in_array($element, array_keys($s3idNames))) {
		$delement = $element.'s';
		if (!in_array($delement, array_keys($s3idNames))) #try again
		{
		$delement = $element.'es'; #i know, it's a class... damn this hacking :-D
		}
	}
	
	$s3ql=compact('user_id','db');
	$s3ql['select']='*';
	$s3ql['from']=$delement;
	$s3ql['where'][$s3idNames[$delement]]=$element_id;

	$done = S3QLaction($s3ql);
	#echo '<pre>';print_r($s3ql);
	#echo '<pre>';print_r($done);
	
	

	if (is_array($done)) {
		
	return ($done[0]);
	}
	else {
		return False;
	}
	}


?>