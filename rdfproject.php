<?php
	#rdfproject.php parses a project in s3db into n3.
	#reads database info from the session or from key
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
	$key = $_GET['key'];
	if($key=='') { $key = $s3ql['key']; }

	#When the key goes in the header URL, no need to read the xml, go directly to the file
	include_once('core.header.php');
	include('dbstruct.php');

	if($key!='') {
		$user_id = get_entry('access_keys', 'account_id', 'key_id', $key, $db);
	} else {
		$user_id = $_SESSION['user']['account_id'];
	}
	if($user_id!='') {
		$project_id = $_REQUEST['project_id'];
		$url = 'http://'.$def.S3DB_URI_BASE.'/';
		#start building the string, prefix will be the very fisrt thing shouwing up
		$n3 .= sprintf('@prefix dc:<http://purl.org/dc/elements/1.1/> .'.chr(10));
		$n3 .= sprintf('@prefix rdfs:<http://www.w3.org/2000/01/rdf-schema#> .'.chr(10));
		$n3 .= sprintf('@prefix rdf:<http://www.w3.org/1999/02/22-rdf-syntax-ns#> .'.chr(10));
		$n3 .= sprintf('@prefix owl: <http://www.w3.org/2002/07/owl#> .'.chr(10));
		$n3 .= sprintf('@prefix : <'.$url.'project'.$project_id.'.owl#>'.chr(10));
		$n3 .= sprintf('<'.$url.'rdfproject'.$project_id.'.owl#> a owl:Ontology .
		 .');
		$rdf .='<rdf:RDF xmlns:rdf="http://www.w3.org/1999/02/22-rdf-syntax-ns#"'.chr(10);
		$rdf .='xmlns:rdfs="http://www.w3.org/2000/01/rdf-schema#"'.chr(10);
		$rdf .='xmlns:dc="http://purl.org/dc/elements/1.1/">';
		$rdf .='xmlns:owl="http://www.w3.org/2002/07/owl#"'; 
	
		#$key = '?key='.$_REQUEST['key'];
		#$url = 'http://'.$def.S3DB_URI_BASE.'/URI.php'.$key.'&uid=';
		$manageElements = array('groups', 'users');
		
		#Map dc names to s3db_names
		$s3Elements = array(
						'projects'=>array(
										'dc:description'=>'project_description', 
										'dc:name'=>'project_name'
									),
						'classes'=>array('dc:name'=>'entity'),
						'instances'=>array(
										'dc:comment'=>'notes',
										'rdfs:class'=>'class_id'
									),
						'rules'=>array( 
										'dc:comment'=>'notes', 
										'rdf:subject'=>'class_id',
										'rdf:predicate'=>'verb',
										'rdf:object'=>'object',
									),
						'statements'=>array(
										'rdf:subject'=>'instance_id',
										'rdf:predicate'=>'rule_id',
										'rdf:object'=>'value'
									)
					);
	
		#the unique identifier for each table
		$s3idNames = array('projects'=>'project_id', 'classes'=>'resource_id', 'instances'=>'resource_id', 'rules'=>'rule_id', 'statements'=>'statement_id');
		$coreElements = array_keys($s3Elements);
		if($project_id!='') {
			$project_info = URIinfo('P'.$project_id, $user_id, $key, $db);
			if(!$project_info['view']) {
				echo "User does not have permission on this project";
				exit;
			}
		} else {
			echo "Please specify project_id'";
			exit;
			#$project_id = getAllowedProjects(array('db'=>$db, 'user_id'=>$user_id));
		}	
		
		#grab all the coreelements from the project, or if no project, grab all the coreelements where user has permissions
		#for ($i=0;$i<count($coreElements), $i++)
		$s3ql=compact('user_id', 'db');
		foreach($coreElements as $element) {
			$cols = $dbstruct[$element];
			if($element!='project') {
				$cols = array_diff($cols, array('project_id', 'permission'));
			} elseif($element=='class') { 
				$iid='0';
			} elseif($element=='instance') { 
				$iid='1';
			}
			$s3ql['from'] = $element;
			$s3ql['where']['project_id'] = $project_id;
			$D[$element] = S3QLaction($s3ql);
			#$D[$element]= listS3DB(array('user_id'=>$user_id,'db'=>$db,'table'=>$element, 'cols'=>$cols, 'project_id'=>"~ '^".$project_id."$'",'permission'=>"~ '(^|_)".$project_id."_'", 'iid'=>$iid));
		
			if(is_array($D[$element])) {
				if($element =='statements') {
					$D[$element] = array_map('grab_class_instance_id', $D[$element]);
					$D[$element] = array_filter(array_map('delete_empty_statements', $D[$element]));#must ignore statements with no value
				} elseif($element=='rules') {
					$D[$element] = include_class_id($D[$element], $db); #whenever the element is a rule, replace the subject with a class
					$D[$element] = include_object_class_id($D[$element], $project_id, $db);
				} elseif($element=='instances') {
					$D[$element] = include_instance_class_id($D[$element], $project_id, $db); #whenever the element is a rule, replace the subject with a class
				}
			}
			#$D[$element] = array_map('ValuesToFileURLs',$D[$element]);
	
			if(is_array($D[$element])) {
				foreach($D[$element] as $n3statement) {
					$prefixRef = array_keys($s3Elements[$element]);
			
					#first line, the resource we are making a statement on
					#$n3 .= '<'.$url.strtoupper(substr($element,0,1)).urlencode('#').$n3statement[$s3idNames[$element]].'>'.chr(10);
					$n3 .= $n3statement[$s3idNames[$element]].' a  rdfs:Class ;'.chr(10);
	
					#rest of the lines, must get class_id and instance_id from resource_id;
					if($element=='rules') {
						$n3statement['class_id'] = $n3statement['subject_class_id'];
						if($n3statement['object_class_id']!='') {
							$n3statement['object'] =$n3statement['object_class_id'];
						}
					} elseif($element=='statements') {
						$n3statement = ValuesToFileURLs($n3statement);
						$n3statement['instance_id'] = $n3statement['resource_id'];
					}
	
					for($i=0;$i<=count($prefixRef);$i++) {
						if($n3statement[$s3Elements[$element][$prefixRef[$i]]]!='') {
							#is the object a resource or a literal? Get the appropriate wrapper; is the value of statement a resource or a literal, is the object of rule a resource or a literal?
							###TODO: THERE IS A BUG HERE WHEN OBJECT OF RULE IS CLASS, IT IS NOT PASSING CLASS_ID
							if((ereg('(.*)_id',$s3Elements[$element][$prefixRef[$i]])) || $element =='statements' && resourceObject(array('db'=>$db, 'rule_id'=>$n3statement['rule_id'], 'project_id'=>$project_id ))) {
								$start = '<'.$url.strtoupper(substr($s3Elements[$element][$prefixRef[$i]], 0,1)).urlencode('#');
								$end = '>';
							} elseif($element =='rules' && $n3statement['object_class_id']!='' && $s3Elements[$element][$prefixRef[$i]]=='object') {
								$start = '<'.$url.'C'.urlencode('#');
								$end = '>';
							} elseif(ereg('<http(.*)>', $n3statement[$s3Elements[$element][$prefixRef[$i]]])) {
								$start = '';
								$end = '';
							} else {
								$start = '"';
								$end = '"';
							}
							if($i==count($s3Elements[$element])-1) {
								$endStatement = '.';
							} else {
								$endStatement = ';';
							}
							$n3 .= chr(9).$prefixRef[$i].' '.$start.$n3statement[$s3Elements[$element][$prefixRef[$i]]].$end.$endStat.$endStatement.chr(10);
						}
					}
				}
			}
		}
	
		$filename = $GLOBALS['s3db_info']['server']['db']['uploads_folder'].$GLOBALS['s3db_info']['server']['db']['uploads_file'].'/tmps3db/project'.$project_id.'_requested_'.$user_id.'_'.date('m.d.y').'.n3';
		file_put_contents($filename, $n3);
		if($_REQUEST['download']=='no') {
			if(strlen($n3)<1000000) {
				print_r($n3);
			} else {
				echo "This project is too big to display, a file with it's contents will be delivered to your email";
			}
		} else {
			if(strlen($n3)<1000000) {
				##download the file
				#header("Pragma: public");
		        #header("Expires: 0"); // set expiration time
		        #header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
		        // browser must download file from server instead of cache
		        // force download dialog
				#header("Content-Type: application/force-download");
				header("Content-Type: application/octet-stream");
				#header("Content-Type: application/download");
	
		        // use the Content-Disposition header to supply a recommended filename and
		        // force the browser to display the save dialog.
		        header("Content-Disposition: attachment; filename=rdfproject".$project_id.".rdf");
	    	    header("Content-Transfer-Encoding: binary");
				echo file_get_contents($filename);
				#fclose($filename);
			} else {
				echo "This project is too big to display, a file with it's contents will be delivered to your email";
			}
		}
	}
?>