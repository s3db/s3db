<?php
	/*
	 * xlsfile = xlsproject.php?key=xxx&project_id=123
	 * xlsproject reads the several project resources and data into an excel spreadsheet and forces download on the client.
	 * Input: key and project_id
	 * 
	 * www.s3db.org/documentation.html
	 * Helena F Deus, November 8, 2006
	 * 
	 */

	ini_set('display_errors',0);
	if($_REQUEST['su3d']) {
		ini_set('display_errors',1);
	}

	if($_SERVER['HTTP_X_FORWARDED_HOST']!='') {
		$def = $_SERVER['HTTP_X_FORWARDED_HOST'];
	} else {
		$def = $_SERVER['HTTP_HOST'];
	}
	
	if(file_exists('config.inc.php')) {
		include('config.inc.php');
	} else {
		Header('Location: http://'.$def.'/s3db/');
		exit;
	}
	
	//just to know where we are...
	$thisScript = end(explode('/', $_SERVER['SCRIPT_FILENAME'])).'?'.$_SERVER['argv'][0];
	$time= date('s');
	$key = $_GET['key'];

	//echo '<pre>';print_r($_GET);
	//Get the key, send it to check validity

	include_once('core.header.php');
	$a = set_time_limit(0);
	if($key) {
		$user_id = get_entry('access_keys', 'account_id', 'key_id', $key, $db);
	} else {
		$user_id = $_SESSION['user']['account_id'];
	}

	//Universal variables
	$sortorder = $_REQUEST['orderBy'];
	$direction = $_REQUEST['direction'];
	$project_id = $_REQUEST['project_id'];

	//$acl = find_final_acl($user_id, $project_id, $db);
	$uni = compact('db', 'user_id','key', 'project_id');
	$class_id = $_REQUEST['class_id'];
	$project_id = $_REQUEST['project_id'];

	if($class_id=='' && $project_id=='' && $_SESSION['queryresult']=='') {
		echo $GLOBALS['messages']['something_missing']."<message>Please specify a class_id</message>";
		exit;
	}
	if($_SESSION['queryresult']!='') {
		$class_id = ($_REQUEST['collection_id']=='')?$_REQUEST['class_id']:$_REQUEST['collection_id'];
	}

	if($project_id!='') {
		$project_info=URIinfo('P'.$project_id, $user_id, $key, $db);
		if(!$project_info['view']) {
			echo ($GLOBALS['messages']['no_permission_message'].'<message>User does not have permission in project</message>');
			exit;
		}
	}
	if($class_id!='') {
		$class_info = URIinfo('C'.$class_id, $user_id, $key, $db);
		if(!$class_info['view']) {
			echo ($GLOBALS['messages']['no_permission_message'].'<message>User does not have permission in class</message>');
			exit;
		}
	}

	$s3ql = compact('db', 'user_id');
	require_once 'Spreadsheet/Excel/Writer.php';

	// Creating a workbook
	$workbook = new Spreadsheet_Excel_Writer();

	// sending HTTP headers
	// Creating a worksheet per resource

	if($class_id!='') { 
		$class_id = $_REQUEST['class_id'];
		$resources[0] = $class_info;
	} else {
		//Build the query structure
		$s3ql['from'] = 'collections';
		$s3ql['where']['project_id'] = $project_id;
		$resources = S3QLaction($s3ql);
	}
	$instSum = 0;
	$statSum = 0;
	
	if (is_array($resources)) {
		for($i=0;$i<count($resources);$i++) {
			$resource_info = $resources[$i];
			if(strlen($resource_info['entity'])>30) {
				$sheetName = substr($resource_info['entity'], 0, 30);
			} else {
				$sheetName = $resource_info['entity'];
			}
	
			$worksheet = $workbook->addWorksheet($sheetName);

			//echo '<pre>';print_r($worksheet);exit;
			//Grab the rules, change the data structure
			$s3ql=compact('user_id','db');
			$s3ql['from'] = 'rules';
			$s3ql['where']['subject_id'] = $resource_info['class_id'];
			if($_REQUEST['project_id']!='') {
				$s3ql['where']['project_id'] = $_REQUEST['project_id'];
			}
			$s3ql['where']['object']="!=UID";	
			$rules = S3QLaction($s3ql); //get all rules, remove the UID later

			if(is_array($rules) && !empty($rules)) {
				$subjects = array_map('grab_subject', $rules);
				$verbs = array_map('grab_verb', $rules);
				$objects = array_map('grab_object', $rules, $verbs);
				$subjects = array_combine(range(0,count($subjects)-1), $subjects);
				$verbs = array_combine(range(0,count($verbs)-1), $verbs);
				$objects = array_combine(range(0,count($objects)-1), $objects);
				
				$rules = array_combine(range(0,count($rules)-1), $rules);
			} else {
				$verbs = array();
				$objects = array();
			}
	
			//List the instances, the resource_id and notes will occupy the first 2 cols
			if($_SESSION['queryresult']!='' && $_REQUEST['data']!='no' && $class_id!='') {
				$instances = $_SESSION['queryresult'];
			} elseif($_REQUEST['data']!='no') {
				$s3ql=compact('user_id','db');
				$s3ql['from'] = 'items';
				$s3ql['where']['collection_id']=$resource_info['collection_id'];
				$instances = S3QLaction($s3ql);
				$_SESSION['queryresult'] = $instances;
			}
			$instSum = $instSum+count($instances);
	
			//echo '<pre>';print_r($instances);exit;
			// The actual data
			//2 cols for resource_id and  notes
	
			if($worksheet->message!='') {
				echo $worksheet->message;
				exit;
			}
			//echo '<pre>';print_r($worksheet);
			$worksheet->write(0, 0, 'Resource');
			$worksheet->write(0, 1, 'Resource');
			$worksheet->write(1, 0, $sheetName);
			$worksheet->write(1, 1, $sheetName);
			$worksheet->write(2, 0, 'UID');
			$worksheet->write(2, 1, 'notes');
	
	
			//3 lines for header
			for($h=0;$h<count($verbs);$h++) {
				$worksheet->write(0, $h+2, $subjects[$h]);
				$worksheet->write(1, $h+2, $verbs[$h]);
				$worksheet->write(2, $h+2, $objects[$h]);
			}

			$addtoNextRow = 0;
		
			//Spit out the instance resource id and notes
			//echo '<pre>';print_r($instances);
			//is there a query page limit?
			if($_REQUEST['num_per_page']!='' && $_REQUEST['current_page']!='') {
				$start = (($_REQUEST['current_page']-1)*$_REQUEST['num_per_page']);
				$end=($_REQUEST['num_per_page']*$_REQUEST['current_page']);
			} else {
				$start = 0;
				$end= count($instances);
			}
			$kk=0;
			$end=(count($instances)<$end)?count($instances):$end;
		
			if(is_array($instances)) {
				for($k=$start;$k<$end;$k++) { 
					$st = array_merge($uni, array('resource_id'=>$instances[$k]['resource_id'], 'object'=>'!=UID'));
					//$all_values = get_all_statements($st);
					if(!is_array($instances[$k]['stats'])) {
						$s3ql=compact('user_id','db');
						$s3ql['select']='*';
						$s3ql['from']='statements';
						$s3ql['where']['instance_id']=$instances[$k]['resource_id'];
						$all_values= S3QLaction($s3ql);
						$_SESSION['queryresult'][$k]['stats'] = $all_values; 
					} else {
						$all_values = $instances[$k]['stats'];
					}
					//echo '<pre>';print_r($s3ql);
					//echo '<pre>';print_r($all_values);exit;
					//reset rule keys
		
					$statSum = $statSum + count($all_values);
					//echo '<pre>';print_r($all_values);
		
					$row = $kk+3+$addtoNextRow;
					//resources with statements, put an lines for more than 1 stat per rule
					$n=(get_max_num_values($all_values, $rules)==0)?1:get_max_num_values($all_values, $rules);
					
					for($m=0; $m<$n; $m++) {
						//Resource_id and notes in the first and seecond col
						$subrow = $row+$m;
						$worksheet->write($subrow, 0, $instances[$k]['resource_id']);
						$worksheet->write($subrow, 1, $instances[$k]['notes']);
					
						if(is_array($rules)) {
							foreach($rules as $j=>$value) {
								$rule_id = $rules[$j]['rule_id'];
								$values = get_value_by_rule($all_values, $rule_id);
					
								//echo $rule_id;
								//echo '<pre>';print_r($values);
								//show only the filename and not the values
								if($values[$m]['file_name'] != '') {
									$worksheet->write($subrow, $j+2, $values[$m]['file_name']);
									//elseif(object_is_resource(array('project_id'=>$values[$m]['project_id'],'subject'=>$value['object'], 'db'=>$db)) && $values[$m]['value']!='')
									//elseif(resourceObject(array('project_id'=>$values[$m]['project_id'],'rule_id'=>$rule_id, 'db'=>$db)) && $values[$m]['value']!='')
								
									//$worksheet->write($subrow, $j+2, $values[$m]['value']);
								} elseif(ereg('<a href=(.*)>(.*)<\/a>',	$values[$m]['value'], $href)) {
									$worksheet->write($subrow, $j+2, $href[1]);
								} else {
									$worksheet->write($subrow, $j+2, $values[$m]['value']);
								}
							}
						}
						$addtoNextRow = $addtoNextRow+$m-1;
					}
					$kk++;
				}
			}
		}
		//exit;
		// Let's send the file

		$namefile =(count($resources)>1)?urlencode($project_info['project_name']):urlencode($resource_info['entity']);
		$workbook->send($namefile.'.xls');
		$workbook->close();
	}
?>

