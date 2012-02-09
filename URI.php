<?php

#URI is an API for delivering URI when given a UID and a key. reads query strings in XML and returns an output

#Syntax of the query: #URI.php?key=xxx&UID=C123
#UID is composed a letter, C, I, R, S, P or U, if it is, respectivelly Class, Instance, Rule, Statement, Project or User

#Helena F Deus, April 4, 2007
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

foreach($_GET as $name => $value)
        {
         $get[strtolower($name)] = $value;
		 
		 list($nest, $bird)=explode(':', $name);
         if($nest!='')
			 $get[$nest][$bird]=$value;
		}

$key = $get['key'];
$uid = $get['uid'];

#When the key goes in the header URL, no need to read the xml, go directly to the file
include_once('core.header.php'); #core header will take care of exiting the system in case key is invalid

$format =  $_REQUEST['format'];
$complete = ($_REQUEST['display']=='complete')?true:false;

if($format=='') $format = 'html';



#If no query is provided, expect a UID. 
#Reading the UID should return a letter, C, I, R, S, U or P and a number.

#queries will be only on exact ID

if($uid=='')
	{
	$letter = 'U';
	$t = $GLOBALS['s3codes'][$letter];
	$ID = $user_id;
	$element_info = $user_info;
	$data[0] = include_all(compact('letter', 'info','element_info', 'user_id', 'db','key'));
	$data[0]['uid']=$GLOBALS['Did'].(($letter!='U')?('|U'.$user_id):'').'|'.$letter.$ID;
	
	}
else{
	$letter = letter($uid);
	$t = $GLOBALS['s3codes'][$letter];
	$uid_info = uid($uid);
	$ID = substr($uid_info['uid'],1,strlen($uid_info['uid']));

	if($ID!='' && $letter!='')
	{
	$element_info = URIinfo($uid, $user_id, $key, $db);
	
	#Find the appropriate table information from each table where to look for the UID
	#User_id does not need to check if there is permissions to perform the query, all others need permission
	
	
		if(!is_array($element_info)){
			echo formatReturn($GLOBALS['error_codes']['something_does_not_exist'],'UID '.$uid.' does not exist', $format,'');
			exit;
		
		}
		if (!$element_info['view'])  {
			echo formatReturn($GLOBALS['error_codes']['no_permission_message'],'User does not have permission on uid '.$uid, $format,'');
			exit;
			#echo ($no_permission_message.'<message>user does not have permission on uid '.$uid.' </message>');
			#exit;
		}

	
	ereg('<error>([0-9]+)</error>.*<message>(.*)</message>', $element_info, $s3qlout);
	
		if ($s3qlout!='' && $s3qlout[1]!='0') {
			echo formatReturn($s3qlout[1], $s3qlout[2]);
			exit;
		}
		else{
		
		$data[0] = $element_info;
		}
	   if(!$data['remote_uri']){  #this applies only to local data
			$localDid = (substr($GLOBALS['Did'], 0,1)=='D')?$GLOBALS['Did']:'D'.$GLOBALS['Did'];
			$data[0]['uid']=$localDid.(($letter!='U')?('|U'.$user_id):'').'|'.$letter.$ID;
			$data[0]['uri']=S3DB_URI_BASE.'/'.$uid;
			$data[0] = array_filter(array_diff_key($data[0], array('project_folder'=>'', 'account_pwd'=>'','status'=>'', 'view'=>'','change'=>'','add_data'=>'','delete'=>'')));
			
			if($letter=='U' && ($user_id!=1 && $user_id!=$element_info['created_by'] && $user_id!=$element_info['account_id'])){
				$data[0]['email'] = "";	$data[0]['account_email'] = "";
			}
	   }
	}
	}
	
	if(!is_array($data[0]))
		{echo formatReturn($GLOBALS['error_codes'][$something_does_not_exist], "uid ".$uid." does not exist", $s3ql['format'],'');
		exit;
		}
	
	$cols = columnsToDisplay($letter);
	
	if($complete){
	if($data[0]['links']){
	foreach ($data[0]['links'] as $newCol=>$moreData) {
		 $data[0][$newCol] = $moreData;
		 array_push($cols, $newCol);

	}
	
	}
	}
	
	$z = compact('data','cols', 'format','letter');
	if($format=='rdf'){
	header('Content-type: application/rdf+xml');	
	}
	echo (outputFormat($z));
	
	exit;
	


?>