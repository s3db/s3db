<?php
	#fileUpdate + move2s3db are specific functions for uploading sdb files to s3db mothership regularly (whenever tehre are changes)
	function fileUploadFromValue($s3ql, $db, $user_id) {
		//is there a filename?
		$filename = ($s3ql['where']['file_name']!='')?$s3ql['where']['file_name']:'file_'.random_string(10).'_'.$s3ql['where']['rule_id'].'_'.$s3ql['where']['item_id'].'.txt';
		 
		//when statement_id is provided, use it; if file exists, append, otherwise create
		if($s3ql['where']['statement_id']!='') {
			$statement_id = $s3ql['where']['statement_id'];
			$stat_info = URIinfo('S'.$statement_id, $user_id, $s3ql['key'],$db);
			if(!is_array($stat_info)) {
				$msg="Statement ".$statement_id." not found";
				return ($msg);
			} elseif(!$stat_info['change']) {
				$msg="User does not have permission to edut S".$statement_id.".";
				return ($msg);
			}
			if($s3ql['where']['file_name']!='' && $stat_info['file_name']!=$filename) {
				$msg="Statement_id ".$statement_id." does not have a file called ".$filename;
				return ($msg);
			}
			$filename = $stat_info['file_name'];
			$rule_id = $stat_info['rule_id'];
			$project_id =  $stat_info['project_id'];
			$folder =  $stat_info['project_folder'];
			$item_id = $stat_info['item_id'];
		}
		ereg('.*\.([a-zA-Z0-9]*)$', $filename,$tmp);
		$extension = $tmp[1];
		$name = ereg_replace('\.'.$extension.'$','',$filename);
		if($statement_id) {
			//if there is already 
			#find the file, open it, add the fragment and return 
			$maindir = $GLOBALS['s3db_info']['server']['db']['uploads_folder'].$GLOBALS['s3db_info']['server']['db']['uploads_file'];
			$fileLocation = $maindir.'/'.$folder;
			$file_in_folder =$fileLocation .'/'.$name.'_'.$project_id.'_'.$item_id.'_'.$rule_id.'_'.strval($statement_id).'.'.$extension;
			if(is_file($file_in_folder)) {
				$a=fopen($file_in_folder,'a');
				if(fwrite($a, $s3ql['where']['value'])) {
					$s3ql['statement_id']=$statement_id;
					$s3ql['file_name'] = $filename;
					return ($s3ql);
				}
			}
		}
		//when the file already exists, append the value to the end of the file
		if(!$s3ql['where']['statement_id'] && $s3ql['where']['file_name']!='') {
			$s3qlS=compact('db','user_id');
			$s3qlS['from']='statement';
			$where=array_filter(array_diff_key($s3ql['where'], array('value'=>'')));
			$s3qlS['where'] = $where;
			$statements = S3QLaction($s3qlS);
			if(count($statements)>1) {
				$msg="There is more than 1 file to be updated. Plase specify statement_id where the file should be edited.";
				return ($msg);
			} else {
				#find the file, open it, add the fragment and return 
				$folder = $statements[0]['project_folder'];
				$maindir = $GLOBALS['s3db_info']['server']['db']['uploads_folder'].$GLOBALS['s3db_info']['server']['db']['uploads_file'];
				$fileLocation = $maindir.'/'.$folder;
				$file_in_folder =$fileLocation .'/'.$name.'_'.$statements[0]['project_id'].'_'.$statements[0]['item_id'].'_'.$statements[0]['rule_id'].'_'.strval($statements[0]['statement_id']).'.'.$extension;
				if(is_file($file_in_folder)) {
					$a=fopen($file_in_folder,'a');
					if(fwrite($a, $s3ql['where']['value'])) {
						$s3ql['statement_id']=$statements[0]['statement_id'];
						$s3ql['file_name'] = $filename;
						return ($s3ql);
					}
				}
			}
		}
		//If we have not appended anything, create the file at this point		
		//create a filekey
		$filekey = generateAFilekey(array('filename'=>$filename, 'filesize'=>'', 'db'=>$db, 'user_id'=>$user_id));
		$filedata = get_filekey_data($filekey, $db);
	
		//crate a new file on the s3db side
		$maindir = $GLOBALS['s3db_info']['server']['db']['uploads_folder'].$GLOBALS['s3db_info']['server']['db']['uploads_file'];
		$fileFullName = $maindir.'/tmps3db/'.$filedata['file_id'].'.'.$extension;
		$fileCreated = fopen($fileFullName, 'w');
		if(!$fileCreated) {
			return (false);
		} else {
			fwrite($fileCreated, $s3ql['where']['value']);
			fclose($fileCreated);
			
			//$filedata = get_filekey_data($filekey, $db);
			//now remove the value and add the filekey to the query
			$s3ql['where'] = array_filter(array_diff_key($s3ql['where'], array('value'=>'','file_name'=>'')));
			$s3ql['where']['filekey'] =  $filekey;
			return ($s3ql);
		}
	}

	function fileUploadFromValue2($s3ql, $db, $user_id) {
		//is there a filename?
		$filename = ($s3ql['where']['file_name']!='')?$s3ql['where']['file_name']:'file_'.random_string(10).'_'.$s3ql['where']['rule_id'].'_'.$s3ql['where']['item_id'].'.txt';
		ereg('.*\.([a-zA-Z0-9]*)$', $filename,$tmp);
		$extension = $tmp[1];
	
		//create a filekey
		$filekey = generateAFilekey(array('filename'=>$filename, 'filesize'=>'', 'db'=>$db, 'user_id'=>$user_id));
		$filedata = get_filekey_data($filekey, $db);
	
		//crate a new file on the s3db side
		$maindir = $GLOBALS['s3db_info']['server']['db']['uploads_folder'].$GLOBALS['s3db_info']['server']['db']['uploads_file'];
		$fileFullName = $maindir.'/tmps3db/'.$filedata['file_id'].'.'.$extension;
		$fileCreated = fopen($fileFullName, 'w');
		if(!$fileCreated) {
			return (false);
		} else {
			fwrite($fileCreated, $s3ql['where']['value']);
			fclose($fileCreated);

			//$filedata = get_filekey_data($filekey, $db);
			//now remove the value and add the filekey to the query
			$s3ql['where'] = array_filter(array_diff_key($s3ql['where'], array('value'=>'','file_name'=>'')));
			$s3ql['where']['filekey'] =  $filekey;
			return ($s3ql);
		}
	}

	function fileUpload($s3ql, $db, $user_id) {
		//is there a filename?
		$filename = ($s3ql['where']['file_name']!='')?$s3ql['where']['file_name']:'file_'.random_string(10).'_'.$s3ql['where']['rule_id'].'_'.$s3ql['where']['item_id'].'.txt';
		ereg('.*\.([a-zA-Z0-9]*)$', $filename,$tmp);
		$extension = $tmp[1];
	
		//create a filekey
		$filekey = generateAFilekey(array('filename'=>$filename, 'filesize'=>'', 'db'=>$db, 'user_id'=>$user_id));
		$filedata = get_filekey_data($filekey, $db);
	
		//crate a new file on the s3db side
		$maindir = $GLOBALS['s3db_info']['server']['db']['uploads_folder'].$GLOBALS['s3db_info']['server']['db']['uploads_file'];
		$fileFullName = $maindir.'/tmps3db/'.$filedata['file_id'].'.'.$extension;
		$fileCreated = fopen($fileFullName, 'w');
		if(!$fileCreated) {
			return (false);
		} else {
			fwrite($fileCreated, $s3ql['where']['value']);
			fclose($fileCreated);

			//$filedata = get_filekey_data($filekey, $db);
			//now remove the value and add the filekey to the query
			$s3ql['where'] = array_filter(array_diff_key($s3ql['where'], array('value'=>'','file_name'=>'')));
			$s3ql['where']['filekey'] =  $filekey;
			return ($s3ql);
		}
	}

	function fileUpdate($file,$user_id,$db) {
		$item_id = findFileItemId($file,$user_id,$db);
	
		###
		#Physically insert the file	
		$rule_id = $GLOBALS['update_project']['file']['rule_id'];
		$project_id = $GLOBALS['update_project']['project_id'];
		$toMove=compact('file','item_id','rule_id', 'project_id');
		
		#$moved = move2s3db($toMove, $db, $user_id);
		$moved = file2statement($toMove, $db,$user_id);
		return ($moved);
	}

	function file2statement($toMove, $db,$user_id) {
		extract($toMove);
		$filekey = generateAFilekey(array('filename'=>$file, 'filesize'=>filesize($file), 'db'=>$db, 'user_id'=>$user_id));
		$filedata = get_filekey_data($filekey, $db);
		$maindir = $GLOBALS['s3db_info']['server']['db']['uploads_folder'].$GLOBALS['s3db_info']['server']['db']['uploads_file'];
		$folder = project_folder_name($project_id, $db);
		$tmp = fileNameAndExtension($file);
		extract($tmp);
		$newSpot = $maindir.'/tmps3db/'.$filedata['file_id'].'.'.$extension;
		#$newSpot = ereg_replace('/.$','',$newSpot);
		copy($file,$newSpot);
			
		$s3ql=compact('user_id','db');
		$s3ql['insert']='file';
		$s3ql['where']['filekey']=$filekey;
		$s3ql['where']['rule_id']=$rule_id;
		$s3ql['where']['item_id']=$item_id;
		$fileinserted = S3QLaction($s3ql);
		$fileinserted  = html2cell($fileinserted);
		
		if($fileinserted[2]['error_code']=='0') {
			return ($fileinserted[2]['file_id']);
		} else {
			return False;
		}
	}

	function move2s3db($toMove, $db, $user_id) {
		#toMove is an array with file, item_id and rule_id, for example $toMove=compact('file','item_id','rule_id');
		##
		#encode the file in base64. Break it in 1084 (1Mb) pieces
		$fid=fopen($toMove['file'], 'r');
		#$fSize = 10000;
		$fSize = filesize($toMove['file']);##=>to change once I find a way to palce the pointer at the end of the file
		$parts = ceil(filesize($toMove['file'])/$fSize);
		$filekey = generateAFilekey(array('filename'=>$toMove['file'], 'filesize'=>filesize($toMove['file']), 'db'=>$db, 'user_id'=>$user_id));
	
		for($i=1; $i <= $parts; $i++) {
			$fileStr = base64_encode(fread($fid, $fSize));
			$fragNr=$i.'/'.$parts;
			###
			#send this piece to the local deployment as a file
			if($fileStr!='') {
				$received = receiveFileFragments(compact('filekey', 'db', 'fileStr', 'fragNr'));
			}
		}
		$s3ql=compact('user_id','db');
		$s3ql['insert']='file';
		$s3ql['where']['filekey']=$filekey;
		$s3ql['where']['rule_id']=$toMove['rule_id'];
		$s3ql['where']['item_id']=$toMove['item_id'];
		$fileinserted = S3QLaction($s3ql);
		$fileinserted = html2cell($fileinserted);
		return ($fileinserted);	
	}

	function findFileItemId($file,$user_id,$db) {
		### => This part to uncomment once queries are made faster
		#Is there an item with this path value on path rule?
		
		/*
		$s3ql=compact('user_id','db');
		$s3ql['select']='*';
		$s3ql['from']='statements';
		$s3ql['where']['rule_id']=$GLOBALS['update_project']['path']['rule_id'];
		$s3ql['where']['file_name']=$path;
		#$s3ql['where']['value']=base64_encode($file);
		$s3ql['where']['local']=1;
		$s3ql['limit']='1';
		$s3ql['format']='html';
		$stat =S3QLaction($s3ql);
		
		if(!is_array($stat)) {
			$s3ql=compact('user_id','db');
			$s3ql['insert']='item';
			$s3ql['where']['collection_id']=$GLOBALS['update_project']['collection_id'];
			$s3ql['where']['notes']=base64_encode($file);
			$s3ql['format']='html';
			$inserted =S3QLaction($s3ql);
			ereg('<error>([0-9]+)</error>(.*)<(message|item_id)>(.*)</(message|item_id)>', $inserted, $s3qlout);
			$item_id = $s3qlout[4];
		
			###
			#Now fill up the stat - for information retrieval purposes only (this avoids having to create a long list to keep track of the item where the file is
			$s3ql=compact('user_id','db');
			$s3ql['insert']='statement';
			$s3ql['where']['item_id']=$item_id;
			$s3ql['where']['rule_id']=$GLOBALS['update_project']['path']['rule_id'];
			$s3ql['where']['value']=base64_encode($file);
			$s3ql['local']=1;
			$inserted =S3QLaction($s3ql);
		} else {
			$stat_info=$stat[0];
			$item_id = $stat_info['item_id'];
		}
		*/
	
		$sql = "select resource_id from s3db_statement where rule_id = '".$GLOBALS['update_project']['file']['rule_id']."' and file_name = '".$file."' order by created_on desc limit 1";
		$db->query($sql,__LINE__,__FILE__);
		if($db->next_record()) {
			$item_id = $db->f('resource_id');
		} else {
			$s3ql=compact('user_id','db');
			$s3ql['insert']='item';
			$s3ql['where']['collection_id']=$GLOBALS['update_project']['collection_id'];
			$s3ql['where']['notes']=urlencode($file);
			$s3ql['format']='html';
			$inserted =S3QLaction($s3ql);
			$msg = html2cell($inserted);
			$msg = $msg[2];
			#ereg('<error>([0-9]+)</error>(.*)<(message|item_id)>(.*)</(message|item_id)>', $inserted, $s3qlout);
		
			$item_id = $msg['item_id'];
			###
			#Now fill up the stat - for information retrieval purposes only (this avoids having to create a long list to keep track of the item where the file is
			$s3ql=compact('user_id','db');
			$s3ql['insert']='statement';
			$s3ql['where']['item_id']=$item_id;
			$s3ql['where']['rule_id']=$GLOBALS['update_project']['path']['rule_id'];
			$s3ql['where']['value']=urlencode($file);
			$s3ql['local']=1;
			$inserted =S3QLaction($s3ql);
		}
		return ($item_id);
	}

	function findUpdates($fileOld, $fileNew) {
		###
		#parse the local doc
		$rdfmodel = rdf2php($fileOld);
	
		###
		##find every doc and put it in an array with data
		$sparql = 'SELECT ?file ?date ?path WHERE {?file <http://purl.org/dc/elements/1.1/date> ?date . 
		?file <http://s3db.org/scripts> ?path .}';
		$subset=$rdfmodel->sparqlQuery($sparql);
	
		$files =  pushURI($subset, '?path','label');
		$dates =  pushURI($subset, '?date','label');
		$local = array_combine($files, $dates);
		
		###
		##find all the remote docs
		#$rssUrl= 'extras/updates.rdf'; =>testnig it with the same file to make sure the questions are returning valid responses
		$rssUrl= $fileNew;
		$newRSS = fopen($rssUrl,'r');
		$remote = stream_get_contents($newRSS);
		@file_put_contents(S3DB_SERVER_ROOT.'/remote.rdf',$remote);
		$remotemodel = rdf2php($rssUrl);
	
		$subset=$remotemodel->sparqlQuery($sparql);
		$files =  pushURI($subset, '?path','label');
		$dates =  pushURI($subset, '?date','label');
		$uris =  pushURI($subset, '?file','uri');
		$remote = array_combine($files, $dates);
		$onCall = array_combine($files, $uris);

		###
		#ask questions
		#question 1: remote - local  > 0?
		$tmpCmp = array_diff_key($remote, $local);
		#if(count(array_filter($remote))>count(array_filter($local)))
		if(count($tmpCmp)>0) {
			#then list all missing
			foreach ($tmpCmp as  $filepath=>$date) {
				$missing[$filepath] = $onCall[$filepath];
			}
		}
	
		#question 2: of the existing local files, is there any that is more ancient that the corresponding remote?
		foreach ($local as $filepath=>$date) {
			if(strtotime($remote[$filepath])>strtotime($local[$filepath])) {
				#$missing[$filepath]=$remote[$filepath];
				$missing[$filepath]=$onCall[$filepath];
			}
		}
		return ($missing);
	}

	function findUpdates1($fileOld, $fileNew) {
		###
		#parse the local doc
		$rdfmodel = rdf2php($fileOld);
			
		###
		##find every doc and put it in an array with data
		$sparql = 'SELECT ?file ?date ?path WHERE {?file <http://purl.org/dc/elements/1.1/date> ?date . 
		?file <http://s3db.org/scripts> ?path .}';
		$subset=$rdfmodel->sparqlQuery($sparql);
		
		$files =  pushURI($subset, '?path','label');
		$dates =  pushURI($subset, '?date','label');
		$local = array_combine($files, $dates);
		
		###
		##find all the remote docs
		$rssUrl= $fileNew;
		#$rssUrl= 'extras/updates.rdf'; =>testnig it with the same file to make sure the questions are returning valid responses
		$newRSS = @fopen($rssUrl, 'r');
		$remote = @stream_get_contents($newRSS);
		$remotemodel = rdf2php($rssUrl);
		$subset=$remotemodel->sparqlQuery($sparql);
		$files =  pushURI($subset, '?path','label');
		$dates =  pushURI($subset, '?date','label');
		$remote = array_combine($files, $dates);
	
		###
		#ask questions
		#question 1: nr remote > nr local?
		if(count(array_filter($remote))>count(array_filter($local))) {
			#then list all missing
			$missing = array_diff_key($remote,$local);
		}
	
		#question 2: of the existing local files, is there any that is more ancient that the corresponding remote?
		foreach ($local as $filepath=>$date) {
			if(strtotime($remote[$filepath])>strtotime($local[$filepath])) {
				$missing[$filepath]=$remote[$filepath];
			}
		}
		return ($missing);
	}
?>