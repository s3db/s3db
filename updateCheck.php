<?php
/*selfUpdate.php is the opposite function of supdates.php. Its purpose is to retrieve the RSS feed of updated files from the mothership and download the files into the appropriate locations.

Created By Helena F Deus (helenadeus@gmail.com) as part of the S3DB package.
15-Apr-2008
*/
 ini_set('display_errors',0);
	if($_REQUEST['su3d'])
	ini_set('display_errors',1);
if(file_exists('config.inc.php'))
	{
		include('config.inc.php');
	}
	else
	{
		Header('Location: index.php');
		exit;
	}

include('rdfheader.inc.php');
#include_once('core.header.php');
include('s3dbcore/move2s3db.php');
error_reporting(E_COMPILE_ERROR);
ini_set('display_errors', '1');
ini_set('allow_url_fopen','1');
ini_set('allow_call_time_pass_reference','1');
###
#Retrieve the new updates.rdf from the mothership
$url2call = $GLOBALS['s3db_info']['deployment']['mothership'].'/s3dbupdates.rdf';

###
#Read the old file (old is always moved to extras when there is a new one) and compare it with the most recent one
$updates = findUpdates(S3DB_SERVER_ROOT.'/s3dbupdates.rdf', $url2call);
if(is_array($updates)){

file_put_contents('tmpUpdates'.date('Ymd'),serialize($updates));
Header('Location:'.$GLOBALS['action']['home']);
exit;
}
?>