<?php
#Uploads accepts file fragments, identified by a file_id and inserts the file in an appropriate location where it can be retrieved by s3db and linked in a statement

#Helena F Deus, Dec 1, 2006

	if(file_exists('config.inc.php'))
	{
		include('config.inc.php');
	}
	else
	{
		Header('Location: index.php');
		exit;
	}

$xml = $_REQUEST['file'];
$format=$_REQUEST['format'];
#Determine if XML is a URL or a string
if (ereg('http://.*', $xml))
{

$handle = fopen ($xml, 'rb');
$xml = stream_get_contents($handle);
fclose($handle);
}
$xml = simplexml_load_string($xml);  #this will read the xml and output the result on an array

#Get the key, send it to check validity, if key is missing check for filekey

$key = $xml->key; 
if ($key == '') $key = $_REQUEST['key'];

if ($key != '') 
{
	#Ckeck for filename and generate the filekey
	#include_once('core.keyheader.php');
	include_once('core.header.php');
	#include_once('s3dbcore/transferFile.php');
	
	

	if (!$_FILES)
	{echo formatReturn('3','No file to upload.', $_REQUEST['format'],'');
	exit;
	
	}
	#If a file_id is not provided, create one
	elseif($file_id == '')
			{
			 
			 foreach ($_FILES as $inputName=>$fileData) {
				ereg('.*\.([a-zA-Z0-9]*)$', $_FILES[$inputName]['name'], $ext); 
				$moved = copy($_FILES[$inputName]['tmp_name'], $final);
				$filename = $_FILES[$inputName]['name'];
			}

		 
		 $ext = $ext[1];
		#if there is no data besides the key, ask for a filename and filesize
		
		if($filename == '') $filename = $xml -> filename;
		$filesize = $_REQUEST['filesize'];
		if($filesize == '') $filesize = $xml -> filesize;

		$filekey = generateAFilekey(compact('filename', 'filesize', 'db', 'user_id'));
		
		#Retrieve the file_id fro mthe filekey
		$tmp= get_filekey_data($filekey, $db); $file_id = $tmp['file_id'];

		
		##Now upload the file
		#Define the folder where these files will be stored
		$folder = $GLOBALS['s3db_info']['server']['db']['uploads_folder'].$GLOBALS['s3db_info']['server']['db']['uploads_file'].'/tmps3db/';
	
		$final = $folder.$file_id.'.'.$ext;
		
		##Copy file from Php tmp directory
		foreach ($_FILES as $inputName=>$fileData) {
			$moved = copy($_FILES[$inputName]['tmp_name'], $final);
		}
		
		

		if ($moved)
		{
		
		##Was a rule_id and item_id provided? If yes, insert it using s3ql, if not, provide the filekey
		if($_REQUEST['item_id'] && $_REQUEST['rule_id']){
		$s3ql=compact('user_id','db');
		$s3ql['insert']='file';
		$s3ql['where']['item_id']=$_REQUEST['item_id'];
		$s3ql['where']['rule_id']=$_REQUEST['rule_id'];
		$s3ql['where']['filekey']=$filekey;
		
		$s3ql['format']=$_REQUEST['format'];
		
		$done = S3QLaction($s3ql);
		echo $done;exit;
		}
		else{
		echo formatReturn('0',"This filekey is to be used instead of key for file transfer, it will expire in 24h. Break the file in base64 encoded fragments, replacing the character '+' with it's URL equivalent '%2b'",$format,array('filekey'=>$filekey));
		}
		}
		else { 
			echo formatReturn('2',"Failed to import file",$format,'');}
		
		}

	


}
else #if key is empty, check for filekey
{
$filekey = $xml->filekey;
	
	if ($filekey == '') $filekey = $_REQUEST['filekey'];
	
	if ($filekey!='')
	{
	include_once('core.filekeyheader.php');
	
	#add a form to the page such that it accepts both POST and GET
	#echo '<form name="file" method="POST">';
	#echo '<input type="hidden" name="query"> <!-- this form is for programming environments that support sending POST -->';
	#echo '</form>';
	
	#echo receiveFileFragments(compact('filekey', 'db'));
	echo formatReturn('0',receiveFileFragments(compact('filekey', 'db')), $format,'');
	
	exit;
	}
	else 
	{	include_once (S3DB_SERVER_ROOT.'/s3dbcore/callback.php');
	include_once (S3DB_SERVER_ROOT.'/s3dbcore/display.php');
		echo formatReturn('3',"Filekey is missing.",$format,''); exit;
	
	}
}
	

?>
