<?php
	##function scanModified.php scans the files under the directory S3DB and checks if any of the files has been modified between the current date and the date of the last s3dbupdates.rdf file.
	##This function generates a list of files that need to be uploaded into s3db
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
	$key=$GLOBALS['update_project']['key'];
	$date = ($_REQUEST['date']=='')?date("d-m-Y"):$_REQUEST['date'];
	include_once('core.header.php');

	#unlink('s3dbupdates.rdf');

	##
	#Read all the file from the current directory
	if($date!='') {
		$cwd=getcwd();
	
		$rootname=dirname($cwd).'/'.basename($cwd);
		$model = ModelFactory::getDefaultModel();
		$model = buildDirModel($cwd, $model, $rootname, $user_id,$date, $db);
		$filename = "tmp/s3dbupdates".date('d-m-Y').random_string(6).".rdf";
		$model->saveAs($filename, "rdf");
	
		//chmod($filename, 0777);
		Header("Location: ".$filename);# =>To uncomment when finished testing
		exit;
	}
	//	else {
	//		###
	//		#Read the old file (old is always moved to extras when there is a new one) and compare it with the most recent one
	//
	//		$updates = findUpdates('extras/s3dbupdates.bak.rdf', 's3dbupdates.rdf');
	//		#echo '<pre>';print_r($updates);exit;
	//
	//		###
	//		#Create an item for each file, except if it already exists.
	//		if(is_array($updates)){
	//			$model = ModelFactory::getDefaultModel();
	//			foreach ($updates as $file=>$date) {
	//				if(filesize($file)>0){
	//					#echo "updating ".$file.chr(10);
	//					$file_id= fileUpdate($file,$user_id,$db);
	//	
	//					#recreate the rdf
	//					$fstat=lstat($file);
	//					$lastModified = date('Y-m-d H:i:s', $fstat['mtime']);
	//
	//					$subjResources = new Resource($GLOBALS['s3db_info']['deployment']['URI'] .'s3dbfiles.php?file_id='.$file_id);
	//					$statement = new Statement($subjResources, new Resource('http://purl.org/dc/elements/1.1/date'), new Literal($lastModified));
	//	
	//					$path = new Statement($subjResources, new Resource('http://s3db.org/scripts'), new Literal($file));
	//	
	//					$model->add($statement);
	//					$model->add($path); 
	//				}		
	//			}
	//			copy('s3dbupdates.rdf', 's3dbupdates.rdf'.date('Ymd'));
	//			$model->saveAs("s3dbupdates.rdf", "rdf");
	//			chmod("s3dbupdates.rdf", 0777);
	//		}
	//	}

	###
	#Once the job is done, move updates to OldUpdates. Old updates will match what has been uploaded/
	function buildDirModel($dir, $model, $rootname, $user_id,$date, $db) {
		##Remove from $dir to output the part until s3db root;
		$dirFiles=scandir($dir);
	
		#foreach ($dirFiles as $ind) 
		for($i=0; $i < count($dirFiles) ; $i++) {
			$file_id=''; #file_id si a tmp var that changes on each loop;
			$ind = $dirFiles[$i];
			
			##if there is a starting date, upload only those files that were modified after that date
			if(is_file($dir.'/'.$ind) && !ereg('^(s3id|config.inc.php|treeitem.*.js|.*.tmp|zzz.*.xml$|.*s3db.xml$|.php[0-9]{8}|^updated_log|s3dbupdates|.*error_log|\.htaccess)', $ind) && strtotime(date("d-m-Y", filemtime($dir.'/'.$ind)))>=strtotime($date)) {
				$fstat=lstat($dir.'/'.$ind);
				$lastModified = date('Y-m-d H:i:s', $fstat['mtime']);
				$path = str_replace($rootname,'',$dir);
				$path = ($path=='')?$ind:substr($path,1,strlen($path)).'/'.$ind;
				$path = addslashes($path);
			
				###
				#Is there an item with this path value on path rule?
				#$item_id = findFileItemId($path,$user_id,$db);
			
				###
				#Find the statement_id of this file on the local s3db
				#$allFileIds = @file_get_contents('fileIds.tmp');
				#$allFileIds=@unserialize($allFileIds);
				#$file_id = @array_search($path, $allFileIds);
			
				if($file_id=='') {
					$sql = "select * from s3db_statement where rule_id = '".$GLOBALS['update_project']['file']['rule_id']."' and file_name = '".$path."' order by created_on desc limit 1";
			
					$db->query($sql, __LINE__,__FILE__);
					if($db->next_record()) {
						$created_on = $db->f('created_on');
						$file_id = $db->f('statement_id');
						#$allFileIds[$file_id]=$path;
					}
				}

				if($file_id=='' || (strtotime(date('Y-m-d G:i:s'))-strtotime($created_on))>(60*5)) {	#more than 5 min
					$updated= fileUpdate($path,$user_id,$db);
					$file_id = $updated;
				}
				if($file_id!='') {
					#file_put_contents('fileIds.tmp',serialize($allFileIds));
		
					#echo "writting item ".$path." ".$file_id.chr(10);
					$subjResources = new Resource(S3DB_URI_BASE.'/s3dbfiles.php?file_id='.$file_id);
					$statement = new Statement($subjResources, new Resource('http://purl.org/dc/elements/1.1/date'), new Literal($lastModified));
					$path = new Statement($subjResources, new Resource('http://s3db.org/scripts'), new Literal($path));
			
					$model->add($statement);
					$model->add($path); 
				} else {
					$mm .= "Could not find a file_id for ".$path.chr(10);
				}
			} elseif(is_dir($dir.'/'.$ind) && !ereg('^(\.|\.\.|extras|tmp)$', $ind)) {
				$newDir = $dir.'/'.$ind;
				$submodel = ModelFactory::getDefaultModel();
				$submodel = buildDirModel($newDir, $submodel, $rootname, $user_id, $date,$db);
				$model->addModel($submodel);
			}
		}
		@file_put_contents('update_error_log'.date('dmY').'.txt', $mm);
		return ($model);
	}
?>