<?php
	#Uploads accepts file fragments, identified by a file_id and inserts the file in an appropriate location where it can be retrieved by s3db and linked in a statement
	#Helena F Deus, Dec 1, 2006
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

	$xml = $_REQUEST['file'];
	$format=$_REQUEST['format'];
	#Determine if XML is a URL or a string
	if (ereg('http://.*', $xml)) {
		$handle = fopen ($xml, 'rb');
		$xml = stream_get_contents($handle);
		fclose($handle);
	}
	$xml = simplexml_load_string($xml);  #this will read the xml and output the result on an array

	#Get the key, send it to check validity, if key is missing check for filekey
	$key = $xml->key; 
	if ($key == '') { $key = $_REQUEST['key']; }
	
	if ($key != '') {
		#Ckeck for filename and generate the filekey
		#include_once('core.keyheader.php');
		include_once('core.header.php');
		include_once('s3dbcore/acceptFile.php');
	
		#if there is no data besides the key, ask for a filename and filesize
		$filename = $_REQUEST['filename'];
		if($filename == '') { $filename = $xml->filename; }
		$filesize = $_REQUEST['filesize'];
		if($filesize == '') { $filesize = $xml->filesize; }
	
		if ($filename=='') {
			echo formatReturn('3','Failed to create filekey.	Please provide filename and filesize. Please refer to http://s3db.org for documentation', $_REQUEST['format'],'');
			exit;
		} elseif($file_id == '') {	#If a file_id is not provided, create one
			$filekey = generateAFilekey(compact('filename', 'filesize', 'db', 'user_id'));
			if($filekey) {
				$display .= sprintf ('%s', "<?xml version=\"1.0\"?>");
				$display .= sprintf ('%s',  "<S3QL>");
				$display .= sprintf ('%s', "<filekey>".$filekey."</filekey><BR>");
				$display .= sprintf('%s', "<message>This filekey is to be used instead of key for file transfer, it will expire in 24h</message><BR>");
				$display .= sprintf('%s', "<message>Break the file in base64 encoded fragments, replacing the character '+' with it's URL equivalent '%2b'</message><BR>");
				$display .= sprintf ('%s', "</S3QL>");
				echo formatReturn('0',"This filekey is to be used instead of key for file transfer, it will expire in 24h. Break the file in base64 encoded fragments, replacing the character '+' with it's URL equivalent '%2b'",$format,array('filekey'=>$filekey));
			#else echo "<report>Failed to create filekey</report>";
			} else { 
				echo formatReturn('2',"Failed to create filekey",$format,'');
			}
		}
	} else { #if key is empty, check for filekey
		$filekey = $xml->filekey;
		if($filekey == '') { $filekey = $_REQUEST['filekey']; }
		if($filekey!='') {
			include_once('core.filekeyheader.php');
	
			#add a form to the page such that it accepts both POST and GET
			#echo '<form name="file" method="POST">';
			#echo '<input type="hidden" name="query"> <!-- this form is for programming environments that support sending POST -->';
			#echo '</form>';
			
			echo formatReturn('0',receiveFileFragments(compact('filekey', 'db')), $format,'');
			exit;
		} else {
			include_once (S3DB_SERVER_ROOT.'/s3dbcore/callback.php');
			include_once (S3DB_SERVER_ROOT.'/s3dbcore/display.php');
			echo formatReturn('3',"Filekey is missing.",$format,''); exit;
		}
	}
?>
