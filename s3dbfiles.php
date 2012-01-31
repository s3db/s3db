<?php

#s3dbfiles.php returns the most recent version of the requested file as stored in the local s3db

###
#Detect which file is being requested. All files are public
ini_set('display_errors',0);
	if($_REQUEST['su3d'])
	ini_set('display_errors',1);
$fileID2get = ($_REQUEST['file_id']!='')?$_REQUEST['file_id']:$_REQUEST['statement_id'];

if(file_exists('config.inc.php'))
	{
		include('config.inc.php');
	}
	else
	{
		Header('Location: index.php');
		exit;
	}

#include('updates.s3db.php');
$key=$GLOBALS['update_project']['key'];
include_once('core.header.php');


###
#Find the corresponding file on s3db, sort by created_n and return the first one found
if($fileID2get=='')
echo "Please provide a file_id in the format: s3dbfiles.php?file_id=xx";
elseif(isLocal('S'.$fileID2get, $db))
{
	$statement_info = URI('S'.$fileID2get, $user_id, $db);
	$format=$_REQUEST['format'];
	pushDownload2Header(compact('statement_info', 'db', 'user_id', 'format'));
}
else 
echo "Echo file_id=".$fileID2get." is not a valid file_id";

exit;

?>