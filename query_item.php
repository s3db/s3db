<?php
ini_set('display_errors',0);
	if($_REQUEST['su3d'])
	ini_set('display_errors',1);
	
	if($_SERVER['HTTP_X_FORWARDED_HOST']!='')
			$def = $_SERVER['HTTP_X_FORWARDED_HOST'];
	else 
			$def = $_SERVER['HTTP_HOST'];
	
	if(file_exists('config.inc.php'))
	{
		include('config.inc.php');
	}
	else
	{
		Header('Location: http://'.$def.'/s3db/');
		exit;
	}
	

$key = $_GET['key'];

#echo '<pre>';print_r($_GET);
#Get the key, send it to check validity

include_once('core.header.php');

#Universal variables
$class_id = ($_REQUEST['collection_id']!='')?$_REQUEST['collection_id']:$_REQUEST['class_id'];
$uid = 'C'.$class_id;
if($class_id)
{
$pl = permission4Resource(array('uid'=>'C'.$class_id, 'shared_with'=>'U'.$user_id, 'db'=>$db, 'user_id'=>$user_id));
#$info['C'.$class_id] = URIinfo('C'.$class_id, $user_id, $key, $db);

$pl = permission_level($pl,'C'.$class_id, $user_id, $db);

if(!$pl['view'] && !$pl['propagate'])
	{echo "User does not have access to view or query this collection";
		exit;
	}
}

#What are the rules tah use this collection as subject?
if($_SESSION[$uid]['rules']==''){
	$s3ql=compact('user_id','db');
	$s3ql['from'] = 'rules';
	if($class_id!='')
	$s3ql['where']['subject_id'] = $class_id;
	#if($_REQUEST['project_id']!='')
	#$s3ql['where']['project_id'] = $_REQUEST['project_id'];
	$s3ql['where']['object']='!="UID"';
	
	if($_REQUEST['orderBy'])
	$s3ql['order_by'] = $_REQUEST['orderBy'].' '.$_REQUEST['direction'];
	
	$rules = S3QLaction($s3ql);
	#echo '<pre>';print_r($rules);exit;
	}
	else {
		$rules = $_SESSION[$uid]['rules'];
	}

#is there a query?
foreach ($_REQUEST as $key=>$value) {
	
	if (ereg('R(.*)', $key, $rule_id)) {
		$rule_value_pairs['rule_1_'.$rule_id[1]] = $value;
		#$rules[] = $rule_id[1];
		#$where['rule_id'] = $rule_id[1];
		#$where['value']=$value;
	}
}
$orderBy = $_REQUEST['orderBy'].' '.$_REQUEST['direction'];	
$format = ($_REQUEST['format']!='')?$_REQUEST['format']:'html.pretty';
if(!is_array($rule_value_pairs))
{
#query everything
$s3ql=compact('user_id','db');
		$s3ql['from'] = 'items';
		$s3ql['where']['collection_id'] = $class_id;
		if($_REQUEST['orderBy']!='')
		$s3ql['order_by'] = $orderBy;
		
		$items =S3QLaction($s3ql);
}
else {
	
	$data = search_resource(compact('rules', 'db', 'orderBy', 'rule_value_pairs'));
	$letter = 'I';
	$include_all=0;
	$data = fillSlotACL(compact('letter', 'user_id','db', 'data', 'include_all'));
	$instances = $data;
	$cols = array('resource_id','notes');
	$D = compact('data','format', 'select','returnFields', 'letter','cols', 'db');
	echo array2str($D);
	#
	#$omit_button_notes=1;
	#echo '<pre>';print_r($rules);
	#$data = include_statements(compact('rules', 'instances', 'user_id', 'db', 'project_id', 'omit_button_notes'));
	#echo '<pre>';print_r($data);exit;
	#$D = compact('data','format', 'select','returnFields', 'letter','cols', 'db')
	#echo array2str($D);

}


?>