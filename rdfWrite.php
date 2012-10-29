<?php
	#This script was designed to run a set of queries that will recreate a database based on an RDF formatted file. This is not meant to be run in the interface, but via a system call, since the file can be large and time for socket open would expire before import is completed.
	#Helena F Deus, March 12,2008
	function rdfWrite($F) {
		extract($F);
		$tmpModel = file_get_contents($file.'_model');

		$model = unserialize($tmpModel);
		unlink($file.'_model');

		$tmpS3DB = file_get_contents($file.'_s3db');
		$s3db = unserialize($tmpS3DB);
		unlink($file.'_s3db');

		#Import the S3DB model into an S3DB database
		if(isset($user_id) && isset($db)) {
			##USERS
			if(is_array($s3db['U'][0])) {
				foreach ($s3db['U'] as $uInd=>$user_info) {
					$s3ql=compact('user_id','db');
					$Uinserted = readWriteExtInput('user', $user_info, $s3ql);
					$message .= $Uinserted[1];
					if(!$Uinserted[0]) {
						#return ($message);
					}
				}
			}
			##GROUPS
			if(is_array($s3db['G'][0])) {
				foreach ($s3db['G'] as $gInd=>$group_info) {
					$s3ql=compact('user_id','db');
					$Ginserted = readWriteExtInput('group', $group_info, $s3ql);
					$message .= $Ginserted[1];
					if (!$Ginserted[0]) {
						#return ($message);
					}
					if(is_array($group_info['U'])) {
						foreach ($group_info['U'] as $user=>$user_info) {
							$s3ql=compact('user_id','db');
							$s3ql['insert']='user';
							$s3ql['where']['user_id']=$user_info['user_id'];
							$s3ql['where']['group_id']=$group_info['group_id'];
							$inserted = S3QLaction($s3ql);
						}
					}
				}
			}
			##PROJECTS
			if(is_array($s3db['P'][0])) {
				foreach ($s3db['P'] as $pInd=>$project_info) {
					$s3ql=compact('user_id','db');
					$Pinserted = readWriteExtInput('project', $project_info, $s3ql);
					$message .= $Pinserted[1];
					if (!$Pinserted[0]) {
						#return ($message);
					}
					if(is_array($project_info['C'])) {
						foreach ($project_info['C'] as $cInd=>$collection_info) {
							$collection_info['project_id'] = $project_info['project_id'];
							$s3ql=compact('user_id','db');
							$Cinserted = readWriteExtInput('collection', $collection_info, $s3ql);
							$message .= $Cinserted[1];
							if (!$Cinserted[0]) {
								#return ($message);
							}
							if(is_array($collection_info['I'])) {
								foreach ($collection_info['I'] as $iInd=>$item_info) {
									$item_info['collection_id']=$collection_info['collection_id'];
									$s3ql=compact('user_id','db');
									$Iinserted = readWriteExtInput('item', $item_info, $s3ql);
									$message .= $Iinserted[1];
									if (!$Iinserted[0]) {
										#return ($message);
									}
								}
							}
						}
					}
					#now for the rules & statements
					if(is_array($project_info['R'])) {
						foreach ($project_info['R'] as $rInd=>$rule_info) {
							#$rule_info['project_id'] = $project_info['project_id'];
							$s3ql=compact('user_id','db');
							$Rinserted = readWriteExtInput('rule', $rule_info, $s3ql);
							$message .= $Rinserted[1];
							if (!$Rinserted[0]) {
								#return ($message);
							}
							if (is_array($rule_info['S'])){
								foreach ($rule_info['S'] as $sInd=>$stat_info) {
									$stat_info['rule_id']=$rule_info['rule_id'];
									$s3ql=compact('user_id','db', 'key');
									$Sinserted = readWriteExtInput('statement', $stat_info, $s3ql);
									$message .= $Sinserted[1];
									if (!$Iinserted[0]) {
										#return ($message);
									}
								}
							}
						}
					}
				}
			}
			##COLLECTIONS
			if(is_array($s3db['C'][0])) {
				foreach ($s3db['C'] as $cInd=>$collection_info) {
					#$collection_info['project_id'] = $project_info['project_id'];
					$s3ql=compact('user_id','db');
					$Cinserted = readWriteExtInput('collection', $collection_info, $s3ql);
					$message .= $Cinserted[1];
					if (!$Cinserted[0]) {
						#return ($message);
					}
					if(is_array($collection_info['I'])) {
						foreach ($collection_info['I'] as $iInd=>$item_info) {
							#$item_info['collection_id']=$collection_info['collection_id'];
							$s3ql=compact('user_id','db');
							$Iinserted = readWriteExtInput('item', $item_info, $s3ql);
							$message .= $Iinserted[1];
							if (!$Iinserted[0]) {
								#return ($message);
							}
						}
					}
				}
			}
			##RULES
			if(is_array($s3db['R'][0])) {
				foreach ($s3db['R'] as $rInd=>$rule_info) {
					#$rule_info['project_id'] = $project_info['project_id'];
					$s3ql=compact('user_id','db');
					$Rinserted = readWriteExtInput('rule', $rule_info, $s3ql);
					$message .= $Rinserted[1];
					if (!$Rinserted[0]) {
						#return ($message);
					}
					if (is_array($rule_info['S'])) {
						foreach ($rule_info['S'] as $sInd=>$stat_info) {
							$stat_info['rule_id']=$rule_info['rule_id'];
							$s3ql=compact('user_id','db');
							$Sinserted = readWriteExtInput('statement', $stat_info, $s3ql);
							$message .= $Sinserted[1];
							if (!$Sinserted[0]) {
								#return ($message);
							}
						}
					}
				}
			}
			##PERMISSIONS
			if(is_array($s3db['permissions'][0])) {
				foreach ($s3db['permissions'] as $permission_info) {
					$s3ql=compact('user_id','db');
					$s3ql['insert']=$GLOBALS['s3codes'][substr($permission_info['shared_with'],0,1)];
					$s3ql['where'][$GLOBALS['COREids'][$GLOBALS['s3codes'][substr($permission_info['shared_with'],0,1)]]]=substr($permission_info['shared_with'],1,strlen($permission_info['shared_with']));
					$s3ql['where'][$GLOBALS['COREids'][$GLOBALS['s3codes'][substr($permission_info['uid'],0,1)]]]=substr($permission_info['uid'],1,strlen($permission_info['uid']));
					$s3ql['where']['permission_level']=$permission_info['permission_level'];
					$done=S3QLaction($s3ql);
				}
			}
		}
	}
	
	function readWriteExtInput($element, $info,$s3ql_in) {
		extract($s3ql_in);
		#$idReplacements=($GLOBALS['idReplacements']=='')?array():$GLOBALS['idReplacements'];
		#if($info[$element.'_id']=="") { $info[$element.'_id'] = s3id(); }
		if ($info[$element.'_id']!='' && $info['object']!='UID') {
			$s3ql=$s3ql_in;
			$s3ql['insert']=$element;
			if($element=='item' && $info['notes']=='') {
				##insert something for ntoes, otherwise, rdf import will not create a new item
				$info['notes']= " ";
			}
			foreach ($info as $info_key=>$info_value) {
				if(is_array($GLOBALS['idReplacements']) && in_array($info_value, array_keys($GLOBALS['idReplacements'])) && ereg('_id$|created_by', $info_key)) {		#this is bad news, it means the id was replaced
					$info_value = $GLOBALS['idReplacements'][$info_value];
				}
				if(in_array($info_key, $GLOBALS['s3input'][$element]) && $info_key!='password') {
					#test for file
					if($element=='statement' && $info_key=='value') {
						if(isS3DBfile($info_value) || isS3DBLink($info_value)) {		#write file to folder
							$filekey=moveS3DBfile($info_value, $db, $user_id);
							$s3ql['insert']='file';
							$info_key='filekey';
							$info_value=$filekey;
							$s3ql['where'][$info_key] = $info_value;
						}
					}
					$s3ql['where'][$info_key] = urldecode($info_value);
				}
			}
			$s3ql['format']='php';
			$inserted = S3QLaction($s3ql);
			$msg=unserialize($inserted);$inserted = $msg[0];
			#$inserted = html2cell($inserted);$inserted=$inserted[2];
		
			$newS3QL = $s3ql;
			$try=1;
			#while(ereg('^9|4$',$inserted['error_code']) && $try<10) #this means this resource already existed.
			while(!ereg('^0$',$inserted['error_code']) && $try<10) {
				$newS3QL['where'][$element.'_id']++;
				$newS3QL['format']='php';
				$inserted = S3QLaction($newS3QL);
				$msg=unserialize($inserted);$inserted = $msg[0];
				#$inserted = html2cell($inserted);$inserted=$inserted[2];
				$try++;
			}
			#if(ereg('^9|4$',$inserted['error_code']))
			if(!ereg('^0$',$inserted['error_code'])) {
				$error_log.="Could not create ".$newS3QL['insert'].". Gave up after 5 attempts. ".$inserted['message'].'<br />';
			}
			if($inserted['error_code']!='0' && $inputs['su3d']) {
				$eeee=@fopen('tmp/error_log_'.date('Ymd'), 'a+');
				@fwrite($eeee, serialize(array('q'=>$s3ql, 'new'=>$newS3QL, 'ms'=>$inserted['message'])));
				echo "Here are the queries that were not valid:";
				echo '<pre>';print_r($s3ql);
				echo '<pre>';print_r($newS3QL);
				echo '<pre>';print_r($inserted);
			}
			#if($s3ql['insert']=='file') {
			#	echo '<pre>';print_r($s3ql);
			#	echo '<pre>';print_r($inserted);
			#	exit;
			#}
	
			##User that is inserting must have permission to further insert stuff in this id
			if($inserted['error_code']=='0') {
				switch ($element) {
					case 'project':
						$permission_info = array('uid'=>'P'.$inserted[$element.'_id'],'shared_with'=>'U'.$user_id, 'permission_level'=>'222');		
					case 'collection':
						$permission_info = array('uid'=>'C'.$inserted[$element.'_id'],'shared_with'=>'U'.$user_id, 'permission_level'=>'222');		
						break;
					case 'rule':
						$permission_info = array('uid'=>'R'.$inserted[$element.'_id'],'shared_with'=>'U'.$user_id, 'permission_level'=>'222');		
						break;
					case 'item':
						$permission_info = array('uid'=>'I'.$inserted[$element.'_id'],'shared_with'=>'U'.$user_id, 'permission_level'=>'222');
					case 'statement':
						$permission_info = array('uid'=>'S'.$inserted[$element.'_id'],'shared_with'=>'U'.$user_id, 'permission_level'=>'222');
						break;
				}
				$p=insert_permission(array('permission_info'=>$permission_info, 'db'=>$db, 'user_id'=>"'-100'"));
			}
	
			if($inserted[$element.'_id']!='' && $inserted[$element.'_id']!=$s3ql['where'][$element.'_id']) {
				##need to re-issue all the ids that rely on this one fro this moment forward
				$GLOBALS['idReplacements'][$s3ql['where'][$element.'_id']]=$inserted[$element.'_id'];
				#array_push($idReplacements, array($s3ql['where'][$element.'_id']=>$inserted[$element.'_id']));
			}
		
			if($element=='user' && $info['password']!='' && $s3ql_in['user_id']=='1') { 		#password is already md5
				insertUserPassword($info['user_id'], $info['password'], $s3ql_in['db']);
			}
	
			if($inserted[2][$element.'_id']=='') {
				echo $error_log;
				return(array(False, $inserted[2]['message']));
			} else {
				return(array(True, $inserted));
			}
		}
	}

	function insertUserPassword($user_id, $password, $db) {
		$sql = "update s3db_account set account_pwd = '".$password."' where account_id = '".$user_id."'";
		$db->query($sql, __FILE__, __LINE__);
	}

	function isS3DBfile($stat_value) {
		#ereg('^s3dbFile_(.*)_(.*[^(_|")])$', $stat_value, $isFile);
		#if(!ereg('^s3dbFile(.*)#(.*[^(_|")])$', $stat_value, $isFile))
		#!ereg('^s3dbFile_(.*)#(.*[^(_|")])$', $stat_value, $isFile)
		if(substr($stat_value, 0, 9)!='s3dbFile_') {
			return (0);
		} else {
			return (1);
		}
	}

	function isS3DBlink($stat_value) {
		#ereg('^s3dbFile_(.*)_(.*[^(_|")])$', $stat_value, $isFile);
		#if(!ereg('^s3dbFile(.*)#(.*[^(_|")])$', $stat_value, $isFile))
		#!ereg('^s3dbFile_(.*)#(.*[^(_|")])$', $stat_value, $isFile)
		if(substr($stat_value, 0, 9)!='s3dbLink_') {
			return (0);
		} else {
			return (1);
		}
	}

	function moveS3DBfile($stat_value, $db, $user_id) {
		ereg('^s3dbFile_(.*)#(.*[^(_|")])$', $stat_value, $isFile);
		if(!$isFile) {
			ereg('^s3dbLink_(.*)#(.*[^(_|")])$', $stat_value, $isFile);
		}
		if(!$isFile) {
			ereg('^s3dbPath_(.*)#(.*[^(_|")])$', $stat_value, $isFile);
		}
		$filename = $isFile[1];
		$fileNewPath=$isFile[2];
		if(!is_file($fileNewPath)) {
			return 'File not found';
		}
		#chdir($GLOBALS['s3db_info']['server']['db']['uploads_folder'].$GLOBALS['s3db_info']['server']['db']['uploads_file'].'/tmps3db/');
		$filesize = filesize($fileNewPath);
		#write a filekey to send the file by the API
		$filekey = generateAFilekey(compact('filename', 'filesize', 'db','user_id'));
		#move the file like the API would do
		$dest = findDestFile(compact('filekey', 'db', 'user_id'));
	
		if(copy($fileNewPath, $dest)) {
			unlink($fileNewPath);
			#rename($fileNewPath, $fileNewPath.'_moved');
			return ($filekey);
		} else {
			return (False);
		}
	}

	function moveS3DBfileLink($stat_value, $db, $user_id) {
		ereg('^s3dbLink_(.*)#(.*[^(_|")])$', $stat_value, $isLink);

		#Reading the link should give us the file contents
		if($isLink) {
			$link= $isLink[2];
			$fid = stream_get_contents(fopen($link, 'r'));
			if($fid=='') {
				return ("Could not open the file.");
			}
		}
		
		$filename = $isFile[1];
		$fileNewPath=$isFile[2];
		if(!is_file($fileNewPath)) {
			return 'File not found';
		}
		#chdir($GLOBALS['s3db_info']['server']['db']['uploads_folder'].$GLOBALS['s3db_info']['server']['db']['uploads_file'].'/tmps3db/');
		
		$filesize = filesize($fileNewPath);
		#write a filekey to send the file by the API
		$filekey = generateAFilekey(compact('filename', 'filesize', 'db','user_id'));
		#move the file like the API would do
		$dest = findDestFile(compact('filekey', 'db', 'user_id'));
		if(copy($fileNewPath, $dest)) {
			#unlink($fileNewPath);
			#rename($fileNewPath, $fileNewPath.'_moved');
			return ($filekey);
		}
	}
?>