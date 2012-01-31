<?php
#viewresource.php is a special join of rules, resource instances and statements, provides an excel-like struture for each resource class;
#Syntax: viewresource.php?key=xxx&collection_id=yyy

#Helena F Deus, April 20, 2007
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
	
$key = $_REQUEST['key'];

#echo '<pre>';print_r($_GET);
#Get the key, send it to check validity

include_once('core.header.php');


//if($key)
//	$user_id = get_entry('access_keys', 'account_id', 'key_id', $key, $db);
//	else
//	$user_id = $_SESSION['user']['account_id'];

#Universal variables
$class_id = ($_REQUEST['collection_id']=='')?$_REQUEST['class_id']:$_REQUEST['collection_id'];

$collection_info = URIinfo('C'.$class_id,$user_id, $key, $db);

include('webActions.php');
if($class_id=='')
{

echo "Please provide a collection_id";
exit;
}

elseif(!$collection_info['view'])
	{
		echo "User does not have access to this resource";
		exit;
	}
else
{

	

	##For viewing statments as a table will need to have a slighly different sintax for diaplay because one combination of row/col can have more than 1 value, in that case a complete new line will need to be repeated
			#will needs rules and items
			
			
			
			if ($_SESSION['queryresult']=='') {
				$s3ql=compact('user_id','db');
				$s3ql['select']='*';
				$s3ql['from']='items';
				$s3ql['where']['collection_id']=$class_id;
				$items = S3QLaction($s3ql);
			 }
			 else {
				$items = $_SESSION['queryresult'];
			 }
			
			if($_REQUEST['num_per_page']!='' && $_REQUEST['current_page']!='')
			{
			$start = (($_REQUEST['current_page']-1)*$_REQUEST['num_per_page']);
			$end=($_REQUEST['num_per_page']*$_REQUEST['current_page']);
			}
			else {
				$start = 0;
				$end= count($items);
			}

			$end=(count($items)<$end)?count($items):$end;
			if(is_array($items))
			{
				 for ($i=$start; $i < $end; $i++) {
					if(!is_array($items[$i]['stats'])){
								$s3ql=compact('user_id','db');
								$s3ql['from']='statements';
								$s3ql['where']['item_id']=$items[$i]['item_id'];
								$items[$i]['stats'] = S3QLaction($s3ql);
								$_SESSION['queryresult'][$i]['stats'] = $items[$i]['stats'];
						}
				 }
					
			}
			 #echo '<pre>';print_r($items);exit;
			
			$s3ql=compact('user_id','db');
			$s3ql['select']='*';
			$s3ql['from']='rules';
			$s3ql['where']['subject_id']=$class_id;
			$s3ql['where']['object'] = "!='UID'";

			$rules = S3QLaction($s3ql);
			
			
			##Find the values that are to be sent to datamatrix.php, both header and data
			
			if(!empty($rules)){
			$verbs = array_map('grab_verb', $rules);
			foreach($verbs as $i=>$value)
			{
			$objects = array_map('grab_object', $rules, $verbs);
			}
			}
			#echo '<pre>';print_r($objects);
			#A matrix will need to be built that has rules on top,  resource_id as rows and statements in the middle 
						
			#Create the header, which will not differ for listall or query
			$head = array('key'=>$key, 'user_id'=>$user_id,'db'=>$db, 'resource_class_id'=>$class_info['resource_id'],'class_info'=>$class_info, 'project_id'=>$project_id, 'rules'=>$rules, 'objects'=>$objects, 'verbs'=>$verbs, 'instances'=>$items,'format'=>strval($s3ql['format']), 'color'=>strval($s3ql['color']),'start'=>$start,'end'=>$end);

			
			
			$display .= create_datamatrix_header($head);
			if(is_array($items))
			{$display .= render_datamatrix_values($head);}
			else echo '<table><tr><td>Your query returned no results.</td></tr></table>';
						
			echo $display;
			exit;

}
			

?>