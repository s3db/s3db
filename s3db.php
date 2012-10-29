<?php
	#S3QL reads query strings in XML and returns an output in html or tab
	#Syntax of the XML query: #S3QL.php?query=<select>classes</select><where><project_id>xxx</project_id><where>
	#S3QL.php?query=<select>rules</select><where><project_id>xxx</project_id><where>
	#S3QL.php?query=<select>instances</select><where><project_id>xxx</project_id><lass_id>yyy</class_id><where>
	#www.s3db.org/documentation.html
	#Helena F Deus, November 8, 2006

	#Endpoint validation.
	ini_set('display_errors',0);
	if($_REQUEST['su3d']) {
		ini_set('display_errors',1);
	}
	if(file_exists('config.inc.php')) {
		include('config.inc.php');
		$s3ql['connection'] = 'successfull';
	} else {
		$s3ql['connection'] = 'unsuccessfull';
		#Header('Location: http://'.$def.'/s3db/');
		exit;
	}
	#ini_set("error_reporting", E_ERROR | E_WARNING | E_PARSE);
	#ini_set("display_errors", '1');

	#Get the key, send it to check validity
	#When the key goes in the header URL, no need to read the xml, go directly to the file
	include_once(S3DB_SERVER_ROOT.'/dbstruct.php');
	include_once(S3DB_SERVER_ROOT.'/s3dbcore/display.php');
	include_once(S3DB_SERVER_ROOT.'/s3dbcore/callback.php');

	ereg('query=(.*)(&amp;)*', $_SERVER['argv'][0], $tmp);
	$query = ($_REQUEST['query']!='')?$_REQUEST['query']:html_entity_decode($tmp[1]);
	$s3ql = readInputMessage($query); #read the message from the URL input;
	$key=$s3ql['key'];

	include_once('core.header.php');

	if(in_array($s3ql['from'], array_keys($GLOBALS['plurals']))) {
		$user_id = get_entry('access_keys', 'account_id', 'key_id', $key, $db);
	}
	$format = ($s3ql['format']!='')?$s3ql['format']:$_REQUEST['format'];
	if($format=='') { $format='html'; }
	$s3ql['format'] = $format;
	#these represent all the queries that can be performed on the tables
	$table = $GLOBALS['s3tables'][$s3ql['from']];
	
	if($s3ql['insert']=='user' && $s3ql['where']['password']!='') {
		echo formatReturn('6','Password cannot be inserted via the API. Please leave this field empty and a random password will be sent to the user\'s email',$format,'');
	}
	if(($s3ql['edit']=='user'|| $s3ql['update']=='user') && ($s3ql['set']['password']!='' || $s3ql['set']['email']!='')) {
		echo formatReturn('6','Password and email cannot be changed via the API.',$format,'');
	}
	
	#input the struct into the S3QLaction function
	$s3ql['db'] = $db;
	$s3ql['user_id'] = $user_id;
	
	$s3qlOut = S3QLaction($s3ql);
	if(!is_array($s3qlOut)) {	
		echo ($s3qlOut);
		exit;
	}
	
	#When the result is an array, rules or any other, run display to give values as tab delimited
	$data = $s3qlOut;
	$letter = letter($s3ql['from']);
	$t=$GLOBALS['s3codes'][$letter];
	$t=$GLOBALS['plurals'][$t];
	#if($s3ql['select']!='*') {
	#	$toreplace = array_keys($GLOBALS['s3map'][$t]);
	#	$replacements = array_values($GLOBALS['s3map'][$t]);
	#	$s3ql['select'] = str_replace($toreplace, $replacements, $s3ql['select']);
	#	$s3ql_out=ereg_replace(' ', '', $s3ql['select']);#take out all the spaces
	#	$returnFields = explode(',', $s3ql_out);
	#}
	$letter = letter($s3ql['from']);
		
	$pack= compact('s3qlOut','data','s3ql','letter', 'returnFields','t','format', 'db');
	echo completeDisplay($pack);
	exit;

	#finish key valid
	function readInputMessage($query) {
		$xml = $query;
	
		##When value brings tags, they will be parsed along with the rest of the xml. Avoid that by encoding it first.
		ereg('<value>(.*)</value>', $xml, $val);
		if($val[1]!='') {
			$xml = ereg_replace($val[1], base64_encode($val[1]), $xml);
		}
		ereg('<notes>(.*)</notes>', $xml, $notes);
	
		if($notes[0]!='') {
			$xml = str_replace($notes[1], base64_encode($notes[1]), $xml);
		}
		if($action=='') { $action='select'; }
		#if($s3ql['from']=='') { $s3ql['from']='projects'; }
	
		#Determine if XML is a URL or a string
		if (!ereg('^<S3QL>.*', $xml)) {
			$xmlFile= @file_get_contents($xml);

			//$xml = ereg_replace("\\\\","\\", $xml);
			//$xml = "C:\Documents and Settings\mhdeus\My Documents\\2008\TCGA\query.xml";
			//$newXMLFile = $GLOBALS['uploads'].'/tmps3db/query.xml';
			//copy($xml, $newXMLFile);
			//exit;
			if(empty($xmlFile)) {
				echo (formatReturn($GLOBALS['error_codes']['something_missing'],'Query file is not valid', $_REQUEST['format'],''));
				exit;
			} else {
				$xml = $xmlFile;
			}
			//$handle = fopen ($xml, 'r');
			//$xml = fread($handle, '1000000');
			//fclose($handle);
		} elseif(ereg('^(select|insert|edit|update|grant)', $query, $action)) {
			#it is text, read it frmo text	
			$Q = explode(' ', $query);

			#if(ereg('^(projects|rules|classes|instances|statements|users|groups|keys)', $Q[1]))
			if(in_array($Q[1], array_keys($GLOBALS['s3input']))) {
				$s3ql['from']=$Q[1];
				if(array_search("in", $Q)) {
					$where_ind = array_search("in", $Q);
					$s3ql['where'][$Q[$where_ind+1]] = $Q[$where_ind+2];
				}
			} else {
				$s3ql[$action[1]] = $Q[1];
			}
			if(array_search("in", $Q)) {
				$where_ind = array_search("in", $Q);
				$s3ql['where'][$Q[$where_ind+1]] = $Q[$where_ind+2];
			}
			if(array_search("where", $Q)) {
				$where_ind = array_search("where", $Q);
				if($Q[$where_ind+2]=='=') {
					$s3ql['where'][$Q[$where_ind+1]] = $Q[$where_ind+3];
				} else {
					$s3ql['where'][$Q[$where_ind+1]] = $Q[$where_ind+2].$Q[$where_ind+3];
				}
			}

			#find a "from". If there is one, then the from and the next for a key=>value pair
			if(array_search('from', $Q)) {
				$s3ql['from'] = $Q[array_search('from', $Q)];
			}
			if(array_search('where', $Q)) { 	#if there is a 'where' in the array, them capture the following field=>value pairs
				$pairsA = range(array_search('where', $Q)+1, count($Q)+1, 4);
				$pairsB = range(array_search('where', $Q)+2, count($Q)+2, 4);
				$equality = range(array_search('where', $Q)+3, count($Q)+3, 4);
				$intersect = range(array_search('where', $Q)+2, count($Q)+4, 4);
			}
	
			//	echo '<pre>';print_r($pairsA);
			//	echo '<pre>';print_r($pairsB);
			//	echo '<pre>';print_r($equality);
			//	echo '<pre>';print_r($Q);
			//	echo '<pre>';print_r($s3ql);
			//	exit;
		}

		if($xml!='') {
			#echo $xml;exit;
			try {
				if(!@simplexml_load_string($xml)) {
					throw new Exception(formatReturn($GLOBALS['error_codes']['something_went_wrong'],'XML query is badly formatted. Please check your start/end tags', $_REQUEST['format'],''));
				}
			} catch(Exception $e) {
				print $e->getMessage();
				exit;
			}
	
			$xml = simplexml_load_string($xml);
			#When there is no XML, rely on GET
			$s3ql = $xml;
			$s3ql = get_object_vars($s3ql);
			
			$s3ql['key'] = ($s3ql['key']!='')?$s3ql['key']:$_REQUEST['key'];
		
			if(get_object_vars($s3ql['where'])!='') {
				$s3ql['where'] = get_object_vars($s3ql['where']);
			} elseif($_REQUEST['where']!='') {
				$s3ql['where'] = $_REQUEST['where'];
			}
			if($s3ql['where']['value']!='') {
				$s3ql['where']['value'] = base64_decode($s3ql['where']['value']);
			}
			if($s3ql['where']['notes']!='') {
				$s3ql['where']['notes'] = base64_decode($s3ql['where']['notes']);
			}

			#if(get_object_vars($s3ql['where']['or'])!='') {
			#	$s3ql['where']['or'] = get_object_vars($s3ql['where']['or']);
			#} elseif($_REQUEST['where']['or']!='') {
			#	$s3ql['where']['or'] = $_REQUEST['where']['or'];
			#}
		
			if(get_object_vars($s3ql['set'])!='') {
				$s3ql['set'] = get_object_vars($s3ql['set']);
				if($s3ql['set']['value']!='') {
					$s3ql['set']['value'] = base64_decode($s3ql['set']['value']);
				}
				if($s3ql['set']['notes']!='') {
					$s3ql['set']['notes'] = base64_decode($s3ql['set']['notes']);
				}
			} elseif($_REQUEST['set']!='') {
				$s3ql['set']=$_REQUEST['set'];
			}
	
			$s3ql['select']=($s3ql['select']!='')?$s3ql['select']:(($_REQUEST['select']!='')?$_REQUEST['select']:(((is_array($s3ql) && in_array('from', array_keys($s3ql))))?'*':''));
			$s3ql['from']=($s3ql['from']!='')?$s3ql['from']:(($_REQUEST['from']!='')?$_REQUEST['from']:'projects');
		
			if($s3ql=='') { $s3ql = $_GET; }
			if($s3ql['format']=='' && $_REQUEST['format']!='') {
				$s3ql['format'] = $_REQUEST['format'];
			}
			#interpred the "or" and  "and"
		} else {
			$s3ql['select']='*';
			$s3ql['from']='projects';
		}
		return $s3ql;
	}
?>