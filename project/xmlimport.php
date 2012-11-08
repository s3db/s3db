<?php
	#xmlimport recreates a project form the XML
	#Includes links to project page
	#Helena F Deus (helenadeus@gmail.com)
	ini_set('display_errors',0);
	if($_REQUEST['su3d']) {
		ini_set('display_errors',1);
	}
	if($_SERVER['HTTP_X_FORWARDED_HOST']!='') {
		$def = $_SERVER['HTTP_X_FORWARDED_HOST'];
	} else {
		$def = $_SERVER['HTTP_HOST'];
	}
	if(file_exists('../config.inc.php')) {
		include('../config.inc.php');
	} else {
		Header('Location: http://'.$def.'/s3db/');
		exit;
	}
	
	$key = $_GET['key'];
	#Get the key, send it to check validity
	include_once('../core.header.php');
	
	if($key) { 
		$user_id = get_entry('access_keys', 'account_id', 'key_id', $key, $db); 
	} else { 
		$user_id = $_SESSION['user']['account_id'];
	}
	
	#$args = '?key='.$_REQUEST['key'];
	include (S3DB_SERVER_ROOT.'/webActions.php');
	$uni = compact('db', 'user_id','dbstruct', 'regexp');

	#load the xml in the uploaded file
	$xml = simplexml_load_string($xmlstr);
	
	$partialdirname = 'tmps3db/xmlschema.s3db';
	$totaldirname = $GLOBALS['s3db_info']['server']['db']['uploads_folder'].'/'.$GLOBALS['s3db_info']['server']['db']['uploads_file'].'/'.$partialdirname;
	
	$xmlfile = $totaldirname;
	
	
	#extract data from the file
	$handle = fopen ($xmlfile, 'r');
	
	$xmlstring = fread($handle, filesize($xmlfile));
	
	$xml = simplexml_load_string($xmlstring);  #this will read the xml and output the result on an array
	
	$xml = get_object_vars($xml);
	#if ($newproject!='') header ('Location:index_main_page.php');

	#MAIN
	if ($xml!='') {
		####################################################
		# Create the project
		$s3ql['db']=$db;
		$s3ql['user_id'] = $user_id;
		$s3ql['insert']='project';
		$s3ql['where']['project_name'] = urldecode($xml['NAME']);
		$s3ql['where']['project_description'] = urldecode($xml['DESCRIPTION']);
		
		$s3ql['format']='html';
		$createdProject = S3QLaction($s3ql);
		$msg=html2cell($createdProject);$msg = $msg[2];
		#$createdProject = '<project_id>0437078001183483970</project_id>';
		
		#get the resulting project_id, if the query worked
		#ereg('<project_id>([0-9]+)</project_id>', $createdProject, $s3qlout);
		
		$project_id = $msg['project_id'];
		if($project_id=='') {
			$report .='<tr><td><font color = "red">'.$createdProject.'</font></td></tr>';
		} else {		#project was created, create the classes
			$report .=  '<tr><td><input type="button" onclick="window.location=\''.$action['project'].'&project_id='.$project_id.'\'" value="Enter project"></td></tr>';
			$report .='<tr><td><font color = "blue">Project '.urldecode($xml['NAME']).' Created</font></td></tr>';
			#####################################################
			#Find the project_id in the output
			#####################################################
			#Get the classes from the xml
			#$xml = get_object_vars($xml);
			
			$xmlclasses = $xml['RESOURCE'];
			#echo '<pre>';print_r($xmlclasses);
			for($i=0;$i<count($xmlclasses);++$i) {
				#create the classes
				$class = get_object_vars($xmlclasses[$i]);
				
				$s3ql['where'] = '';
				$s3ql['insert'] = 'collection';
				$s3ql['where']['project_id'] = $project_id;
				$s3ql['where']['entity'] = urldecode($class['ENTITY']);
				$s3ql['where']['notes'] = urldecode($class['NOTES']);
				$s3ql['format']='html';
				$classInserted = S3QLAction($s3ql);
				$msg=html2cell($classInserted);$msg = $msg[2];
				#was class created? check
				#ereg('<error>([0-9]+)</error>(.*)<(message|collection_id)>(.*)</(message|collection_id)>', $classInserted, $s3qlout);
					
				if($msg['error_code']=='0') {
					$class_id = $msg['collection_id'];
					$classes[$class['ENTITY']]['class_id'] = $class_id;
					$classes[$class['ENTITY']]['rules']=(is_object($class['RULE']))?array(get_object_vars($class['RULE'])):$class['RULE'];
					$report .= '<tr><td><font color = "#FF9900">Class '. urldecode($class['ENTITY']).' created</font></td></tr>';
				}
				if($class_id=='') {
					$report .= '<tr><td><font color = "#FFCC00">'.$msg['message'].'</font></td></tr>';
				}
			}
			
			#classes created, create the rules
			foreach ($classes as $class_name=>$class_info) {
				$class_id = $class_info['class_id'];
				#rules that use class i as subject
				for($j=0;$j <count($class_info['rules']); $j++) {
					$rule = (is_object($class_info['rules'][$j]))?get_object_vars($class_info['rules'][$j]):$class_info['rules'][0];
					if(urldecode($rule['OBJECT'])!='UID') {
						$s3ql['insert'] = 'rule';
						$s3ql['where'] = '';
						$s3ql['where']['project_id'] = $project_id;
						$s3ql['where']['subject_id'] = $class_id;
						$s3ql['where']['verb'] = urldecode($rule['VERB']);
						if($classes[urldecode($rule['OBJECT'])]!='') {		#was there a class created with this object name?
							$s3ql['where']['object_id'] = $classes[$rule['OBJECT']]['class_id'];
						} else {
							$s3ql['where']['object'] = urldecode($rule['OBJECT']);
						}
						$s3ql['where']['notes'] = urldecode($rule['NOTES']);
						$s3ql['format']='html';
						$ruleInserted = S3QLaction($s3ql);
						$msg=html2cell($ruleInserted);$msg = $msg[2];
						#ereg('<error>([0-9]+)</error>(.*)<(rule_id|message)>(.*)</(rule_id|message)>', $ruleInserted, $s3qloutRule);
						
						if($msg['error_code']=='0') {
							$rule_id = $msg['rule_id'];
							$report .= '<tr><td><font color = "#00CC33">Rule '.$rule['SUBJECT'].' '.$rule['VERB'].' '.$rule['OBJECT'].' created</font></td></tr>';
						} else {
							echo urldecode($rule['OBJECT'])!='UID';
							$report .= '<tr><td><font color = "red">Rule '.$rule['SUBJECT'].' '.$rule['VERB'].' '.$rule['OBJECT'].' was NOT created. Reason: '.$msg['message'].'</font></td></tr>';
						}
					}
				}
			}
		} #end the class loop
	} else {
		$report .= '<tr><td><font color = "red">Nothing was found on the xml file.Please upload a valid xml file</font></td></tr>';
	}
	echo '<table border="1">';
	echo $report;
	echo '</table>';
?>