<?php


function s3id()
{
#finds a new id from a file that keeps growing as new ids are added
#this function will be included in several scripts, which means that path will change. Need to keep path fixed.
include('config.inc.php');

$idsFile = S3DB_SERVER_ROOT.'/s3id';

if(!is_file($idsFile))
	{
	file_put_contents($idsFile, '3');
	chmod($idsFile,0777);
	return ('3');
	}
else
	{#get the last number and replace it with a new one
	$lastId = file_get_contents($idsFile);
	$newid = $lastId+1;
	file_put_contents($idsFile, $newid);
	return ($newid);
	}

}
?>