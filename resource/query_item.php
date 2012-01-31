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

#echo '<pre>';print_r($_GET);
#Get the key, send it to check validity

include_once('../core.header.php');

#Universal variables
$class_id = ($_REQUEST['collection_id']!='')?$_REQUEST['collection_id']:$_REQUEST['class_id'];
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

?>