<?php
	/* selfUpdate.php is the opposite function of scanModified.php. Its purpose is to retrieve the RSS feed of updated files 
	 * from the mothership and download the files into the appropriate locations.
	 * 
	 * Created By Helena F Deus (helenadeus@gmail.com) as part of the S3DB package.
	 * 15-Apr-2008
	 */
	ini_set('display_errors',0);
	if($_REQUEST['su3d']) {
		ini_set('display_errors',1);
	}
	if(file_exists('config.inc.php')) {
		include('config.inc.php');
	} else {
		Header('Location: index.php');
		exit;
	}

	include('rdfheader.inc.php');
	include_once('core.header.php');
	#include('s3dbcore/move2s3db.php');
	
	if($user_id!='1') {
		echo "Only Admin can run the selfupdate.";
		exit;
	}
	#ini_set('display_errors', '1');
	ini_set('allow_url_fopen','1');
	ini_set('allow_call_time_pass_reference','1');

	###
	#Retrieve the new updates.rdf from the mothership
	#$url2call = $GLOBALS['s3db_info']['deployment']['mothership'].'/s3dbupdates.rdf';
	$codeSource = ($GLOBALS['s3db_info']['deployment']['code_source']!='')?$GLOBALS['s3db_info']['deployment']['code_source']:$GLOBALS['s3db_info']['deployment']['mothership'];
	
	$url2call = $codeSource.'scanModified.php?&date='.$_REQUEST['date']; #This is the mothership's dynamic url.
	$fid = fopen($url2call, 'r');

	###
	#Read the url2call into a local file
	$mothershipData = stream_get_contents($fid);

	if($mothershipData!='') { 
		$remoteFile = $GLOBALS['uploads'].'tmps3db/remote'.date('Ymd_Gis').'.rdf';
		file_put_contents($remoteFile, $mothershipData);

		###
		#Read the old file (old is always moved to extras when there is a new one) and compare it with the most recent one
		if(!is_file(S3DB_SERVER_ROOT.'/s3dbupdates.rdf')) {
			$emptyRDF = '<?xml version="1.0" encoding="UTF-8" ?>
<rdf:RDF
   xmlns:rdf="http://www.w3.org/1999/02/22-rdf-syntax-ns#"
   xmlns:rdfs="http://www.w3.org/2000/01/rdf-schema#"
   xmlns:xsd="http://www.w3.org/2001/XMLSchema#"
   xmlns:owl="http://www.w3.org/2002/07/owl#"
   xmlns:dc="http://purl.org/dc/elements/1.1/"
   xmlns:dcterms="http://purl.org/dc/terms/"
   xmlns:vcard="http://www.w3.org/2001/vcard-rdf/3.0#"
   xmlns:ns1="http://s3db.org/">

</rdf:RDF>';
			file_put_contents(S3DB_SERVER_ROOT.'/s3dbupdates.rdf',$emptyRDF);
		}
		if(!is_file(S3DB_SERVER_ROOT.'/s3dbupdates.rdf')) {
			return formatReturn($GLOBALS['error_codes']['something_does_not_exist'], "Apache was unable to write to S3DB directory. Please make sure all files under ".S3DB_SERVER_ROOT." have write permission.", '');
			exit; 
		}
		$updates = findUpdates(S3DB_SERVER_ROOT.'/s3dbupdates.rdf', $remoteFile);
	} else {
		return formatReturn($GLOBALS['error_codes']['something_does_not_exist'], "Mothership update file could not be read. Please try again later.", $_REQUEST['format'], '');
		exit;
	}

	###
	#Follow the link provided by the mothership, stored as value and place it under the correct path, stored as key
	if(is_array($updates)) {
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
			$updated .= "Updated ".S3DB_SERVER_ROOT.'/'.$paths[$i].chr(10);
		
			###
			#Replace the old file with the new one
			$fid1 = fopen($oldFile, 'w+');
		
			file_put_contents($oldFile,$filedata);
			file_put_contents(S3DB_SERVER_ROOT.'/s3dbupdates.rdf', file_get_contents($remoteFile));
			@chmod($oldFile, 0774);
		
			#copy('s3dbupdates.rdf', 'extras/s3dbupdates.bak.rdf');
			#fwrite($fid1, $filedata);
			#file_put_contents(S3DB_SERVER_ROOT.'/'.$paths[$i].'_updated', $filedata);
		}
		@file_put_contents('updated_log'.date('Ymd_His').'.txt', $updated);
	}
	if($_SESSION['db']!='') {
		Header('Location:'.$GLOBALS['action']['home']);
		exit;
	}
?>

