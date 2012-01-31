<?php
#editstatement.php is the interface for editing statements.
		#Helena F Deus (helenadeus@gmail.com)
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
	#Get the key, send it to check validity

include_once('core.header.php');

if($user_info['account_id']) $user_id = $user_info['account_id'];
else $user_id = $_SESSION['user']['account_id'];


			
$statement_id = $_REQUEST['statement_id'];
//$statement_info = URI('S'.$statement_id, $user_id, $db);
$statement_info = URIinfo('S'.$statement_id, $user_id, $key, $db);
#echo '<pre>';print_r($statement_info);exit;

if(!$statement_info['view'])
{
	echo "User does not have permission on this file";
}
else
{
		pushDownload2Header(compact('statement_info', 'db', 'user_id', 'format'));
			
}

?>
