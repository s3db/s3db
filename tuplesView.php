<?php
	#viewresource.php is a special join of rules, resource instances and statements, provides an excel-like struture for each resource class;
	#Syntax: viewresource.php?key=xxx&collection_id=yyy
	#Helena F Deus, April 20, 2007
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
	
	$key = $_REQUEST['key'];

	#Get the key, send it to check validity
	include_once('core.header.php');
	#Universal variables
	$class_id = ($_REQUEST['collection_id']=='')?$_REQUEST['class_id']:$_REQUEST['collection_id'];
	$class_info = URIinfo('C'.$class_id,$user_id, $key, $db);

	include('webActions.php');
	
	if($class_id=='') {
		echo "Plase specify the collection_id from the tuples you are trying to retrieve";
		exit;
	}
	if(!$class_info['view']) {
		echo "User does not have access to this resource";
		exit;
	} else {
		#Parse the inputs: are there attr/value pair patterns to be queried?
		if($argv!='') {
			for ($i=1; $i <count($argv) ; $i++) {
				list($keyWord, $val) = explode('=',$argv[$i]);
				if(!ereg('collection_id|class_id|key|su3d',$keyWord));
				$inputs[$keyWord] = $val;
			}
		} else {
			$inputs = array_filter(array_diff_key($_GET, array('collection_id'=>'','class_id'=>'','key'=>'','su3d'=>'')));
		}

		##For viewing statments as a table will need to have a slighly different sintax for diaplay because one combination of row/col can have more than 1 value, in that case a complete new line will need to be repeated
		$cols = array('resource_id', 'resource_class_id','entity', 'notes', 'project_id','created_on', 'created_by' ,'iid');
		if(empty($inputs)) {
			if (is_array($_SESSION[$user_id]['instances'][$class_id])) {
				$instances = $_SESSION[$user_id]['instances'][$class_id];
			} else {
				$s3ql = compact('db', 'user_id');
				$s3ql['select'] = '*';
				$s3ql['from'] = 'items';
				$s3ql['where']['collection_id'] = $class_id;
				#$s3ql['where']['project_id'] = $project_id;
				$instances = S3QLaction($s3ql);
			}
			#For finding all the rules that this resource involves, change S a little bit
			$s3ql = compact('db', 'user_id');
			$s3ql['select'] = '*';
			$s3ql['from'] = 'rules';
			$s3ql['where']['subject_id'] = $class_id;
			$s3ql['where']['object'] = "!='UID'";
			$rules = S3QLaction($s3ql);
			#$rules = include_all_class_id(compact('rules', 'project_id', 'db'));
		} else {
			#Building the query
			$ruleScope=array();
			foreach ($inputs as $key=>$value) {
				$ruleScope[ereg_replace('^R', '',$key)]=stripslashes($value);
			}
			$q = array('rules'=>array_keys($ruleScope), 'logical'=>array_fill(0, count($inputs), 'and'), 'RuleValuePair'=>$ruleScope, 'db'=>$db);
			$instances = query_statements($q);
		}
		##Find the values that are to be sent to datamatrix.php, both header and data
		if(is_array($rules) && !empty($rules)) {
			$verbs = array_map('grab_verb', $rules);
			foreach($verbs as $i=>$value) {
				$objects = array_map('grab_object', $rules, $verbs);
			}
		}
		#A matrix will need to be built that has rules on top,  resource_id as rows and statements in the middle 
		#Create the header, which will not differ for listall or query
		$head = array('key'=>$key, 'user_id'=>$user_id,'db'=>$db, 'resource_class_id'=>$class_info['resource_id'],'class_info'=>$class_info, 'project_id'=>$project_id, 'rules'=>$rules, 'objects'=>$objects, 'verbs'=>$verbs, 'instances'=>$instances,'format'=>strval($s3ql['format']), 'color'=>strval($s3ql['color']));
		
		$display .= create_datamatrix_header($head);
		if(is_array($instances)) {
			$display .= render_datamatrix_values($head);
		} else {
			echo '<table><tr><td>Your query returned no results.</td></tr></table>';
		}
		echo $display;
	}
?>