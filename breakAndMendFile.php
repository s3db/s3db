<?php
#break and mend file takes the input rdf/n3 file, breaks it into pieces, removes the included files into a directory, and rebuild the file to be parsed with rdf importer

#break file fits the purpose of breaking large rdf files into smaller ones such that they can be imported without the process being killed by php

#$file= ($_REQUEST['file']!='')?$_REQUEST['file']:$argv[2];
function breakAndMendFile($file){
$intime = time();


if($file=='' || !is_file($file)) {

	return "Please specify the rdf/n3 file to restore";
	exit;
	}
	
breakFile($file);
removeFiles($file);
rebuildFile($file);
}
function breakFile($file)
{
	
	$fid = fopen($file, 'r+');
	$fracsize=500000;
	$parts = ceil(filesize($file)/$fracsize);
	file_put_contents($file.'_parts', $parts);
	$i=1;	
	while ($i<=$parts) {
		
	###
	#find the position of the last carriage return before 200000
	
	$fraction = $nextFractionFile.fread($fid,$fracsize);
	$lastLineEnd = strrpos($fraction, chr(10));
	
	###
	#write to file only till lastLineEdn
	$fraction_file = fopen($file.'_'.$i,'w+');
	fwrite($fraction_file, substr($fraction, 0, $lastLineEnd));
	
	###
	#start building the nextFractionFile with whatever is left;
	$nextFractionFile = substr($fraction, $lastLineEnd, strlen($fraction)-$lastLineEnd);
	
	#echo $file.'_'.$i.chr(10);
	
	$i++;
	
	
	}
	

}

function removeFiles($file)
{
	##
	##find how many fragments of this file there are
	$parts = file_get_contents($file.'_parts');
	$i=1;
	###
	#read each of those parts individually while removing the files and building a cleaned file version
	
	while ($i<=$parts) {
		#echo 'cleaning '.$file.'_'.$i.chr(10).chr(13).'<br />';
		
		$fraction_file_clean = fopen($file.'_'.$i.'clean','w+');
		$lines=file($file.'_'.$i);
		
		$j=1;
		
		if(!empty($lines)){
		foreach ($lines as $line) {
		
		#echo "using regular expressions to find the files".chr(10);
		#ereg('"s3dbFile_(.*[^(")])_(.*[^(")])"', $line, $filedata); => ereg simply takes too long
		
		#echo "finding file data in file".chr(10);
		$s3dbFilePos = strpos($line, 's3dbFile_');
		$s3dbLinkPos = strpos($line, 's3dbLink_');
		$s3dbPathPos = strpos($line, 's3dbPath_');
		
		if($s3dbFilePos!='')
			{
			
			###
			#find the last underscore before the file starts.
			$filenameAndData = substr($line, $s3dbFilePos+strlen('s3dbFile_'),strlen($line));
			#echo $filenameAndData;exit;
			$lastMark = strpos($filenameAndData, '_');
			$filename = substr($filenameAndData, 0, $lastMark); 
			$filedata = substr($filenameAndData, $lastMark+1, (strpos($filenameAndData,'"')-($lastMark+1)));
			
				#	$filename = $filedata[1];
				$newFilename = $GLOBALS['s3db_info']['server']['db']['uploads_folder'].$GLOBALS['s3db_info']['server']['db']['uploads_file'].'/tmps3db/'.$filename.str_replace(array('.', ' '),array('', '_'),microtime(true));
				
				#echo 'opening file. Time: '.strftime('%S', (time()-$intime)).chr(10);
				$fid=fopen($newFilename,'w+');
				
				###
				#if the file is large, write it to the file in small fragments
				##
				#decode the file contents. Must be decoded at once because it was encoded at once
				#echo 'decoding file. Time:  '.strftime('%S', (time()-$intime)).chr(10);
				#$data2write = base64_decode($filedata);
				#$fileFragSize = 1084;
				#$fileFragNr = ceil(strlen($data2write)/$fileFragSize);
				#for ($k=0; $k<=$fileFragNr-1; $k++) {
					#echo 'writting frag '.($k+1).'/'.$fileFragNr.' to file '.strftime('%S', (time()-$intime)).chr(10);
				#	fwrite($fid, substr($data2write, $k*$fileFragSize, $fileFragSize));
				#								
				#}
				
				
				#echo 'done writting '.$filename.''.strftime('%S', (time()-$intime)).chr(10);
				fwrite($fid, base64_decode($filedata));
				fclose($fid);
				
				
				chmod($newFilename, 0777);
				$line=str_replace('_'.$filedata,'#'.$newFilename, $line);
				
				
				
				
			}
		
		if($s3dbLinkPos!=''){
		    ###
			#find the last underscore before the file starts.
			$filenameAndData = substr($line, $s3dbLinkPos+strlen('s3dbLink_'),strlen($line));
			ereg('(.*)_(http.*)"',$filenameAndData, $fileLinkData);
			
			$filename = $fileLinkData[1];
			$fileLink = $fileLinkData[2];
			
			$fileDataID = fopen($fileLink,"r");
			$fileData = stream_get_contents($fileDataID);
			
			#$newFilename = $GLOBALS['s3db_info']['server']['db']['uploads_folder'].$GLOBALS['s3db_info']['server']['db']['uploads_file'].'/tmps3db/'.$filename.str_replace(array('.', ' '),array('', '_'),microtime(true));
			#$newFilename = S3DB_SERVER_ROOT.'/tmp/'.$filename.str_replace(array('.', ' '),array('', '_'),microtime(true));
			$newFilename = S3DB_SERVER_ROOT.'/tmp/'.$filename;
			
			#echo 'opening file. Time: '.strftime('%S', (time()-$intime)).chr(10);
			
			$fid=fopen($newFilename,'w+');
			chmod($newFilename, 0777);
			if($fileData){
			fwrite($fid, $fileData);
			fclose($fid);
		    chmod($newFilename, 0777);
			$line=str_replace('_'.$fileLink,'#'.$newFilename, $line);
			
			}
			else {
				$line = substr($line, 0,$s3dbLinkPos).$fileLink;
			}
			
			
		}
		#echo 'writting line '.$j.' to '.$file.'_'.$i.'. Time: '.strftime('%S', (time()-$intime)).chr(10);
		fwrite($fraction_file_clean, $line);
		$j++;
		}
		}
		$i++;
	}
	
	#return ($data);
	
	
	
	
}

function rebuildFile($file)
{
	###
	#find out in how many fractions this file was broken
	$parts = file_get_contents($file.'_parts');

	for ($i=1; $i <= $parts; $i++) {
		$data .= file_get_contents($file.'_'.$i.'clean');
		$file2unlink = $file.'_'.$i.'clean';
		unlink($file2unlink);
		unlink($file.'_'.$i);
	}
	
	file_put_contents($file.'_clean', $data);
	
	

}


?>