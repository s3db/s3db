<?php
/*selfUpdate.php is the opposite function of supdates.php. Its purpose is to retrieve the RSS feed of updated files from the mothership and download the files into the appropriate locations.

Created By Helena F Deus (helenadeus@gmail.com) as part of the S3DB package.
15-Apr-2008
*/

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
include_once('core.header.php');
#include('s3dbcore/move2s3db.php');

if($user_id!='1')
{
echo "Only Admin can run the selfupdate.";
exit;

}
ini_set('display_errors', '1');
ini_set('allow_url_fopen','1');
ini_set('allow_call_time_pass_reference','1');
###
#Retrieve the new updates.rdf from the mothership
$url2call = $GLOBALS['s3db_info']['deployment']['mothership'].'/s3dbupdates.rdf';

###
#Read the old file (old is always moved to extras when there is a new one) and compare it with the most recent one
$updates = findUpdates(S3DB_SERVER_ROOT.'/s3dbupdates.rdf', $url2call);
#echo '<pre>';print_r($updates);exit;
###
#Follow the link provided by the mothership, stored as value and place it under the correct path, stored as key
if(is_array($updates)){
$paths = array_keys($updates);
$uris = array_values($updates);
for ($i=0; $i < count($updates); $i++) {
	$fid = fopen($uris[$i],"r");
	$filedata=stream_get_contents($fid);
	
	$oldFile = S3DB_SERVER_ROOT.'/'.$paths[$i];
	$bak = S3DB_SERVER_ROOT.'/'.$paths[$i].date('dmY', time());
	
	###
	#Make a copy of the old file, if exists
	$c = copy($oldFile, $bak);
	
	echo "Updated ".S3DB_SERVER_ROOT.'/'.$paths[$i].chr(10);
	
	###
	#Replace the old file with the new one
	$fid1 = fopen($oldFile, 'w+');
	file_put_contents($oldFile,$filedata);
	file_put_contents(S3DB_SERVER_ROOT.'/s3dbupdates.rdf', file_get_contents(S3DB_SERVER_ROOT.'/remote.rdf'));

	#copy('s3dbupdates.rdf', 'extras/s3dbupdates.bak.rdf');
	#fwrite($fid1, $filedata);
	#echo $filedata;exit;

	#file_put_contents(S3DB_SERVER_ROOT.'/'.$paths[$i].'_updated', $filedata);
	
}
}
if($_SESSION['db']!='')
{
Header('Location:'.$GLOBALS['action']['home']);
exit;
}
?>

