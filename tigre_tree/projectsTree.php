<?php
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
	#$deployment_info = URIinfo($GLOBALS['Did'], $user_id, $key, $db);
	#$pl = permissionOnResource(array('uid'=>'U'.$user_id, 'shared_with'=>'D'.$GLOBALS['Did'],'user_id'=>$user_id, 'db'=>$db));
	#$pl = permission_level($pl, 'U'.$user_id, $user_id, $db);

	if($key!='') {
		$user_id = get_entry('access_keys', 'account_id', 'key_id', $key, $db);
	} else {
		$user_id = $_SESSION['user']['account_id'];
	}
	#actions for the tree

	#relevant extra arguments
	$args = '?key='.$_REQUEST['key'];
	include('../webActions.php');

	if ($_REQUEST['refreshButton']!='') {
		echo '<meta http-equiv="refresh">';
		#header('Location:'.$thisScript.''); 
		exit;
	}	

	if(is_numeric($user_id)) {
		$tree_items_file = "treeitems".$user_id.".js"; #don't forget to change this in the final version
	} else {
		$tree_items_file = "treeitems".base64_encode($user_id).".js"; 	
	}
	include ('resources_tree.php');

	#Other vars includes all the info about user, db, etc, that are needed in order to conenct to the db, they are specified in the file important_vars.php
	$DBvars = compact('db', 'user_id', 'dbstruct', 'regexp', 'action');
	if(!$handle = fopen($tree_items_file, 'w+')) {
		echo "Cannot open file ($tree_items_file)";
		exit;
	}
	if(fwrite($handle, create_tree_items($tree_items_file, $DBvars)) === FALSE) {
		echo "Cannot write to file ($tree_items_file)";
		exit;
	}
	#chmod($tree_items_file, 0700); 

	fclose($handle);
	# This is the file that has all the CSS for the tigre tree
	#create the refresh button
	#echo '<form method="POST" action="'.$thisScript.'">';
	#echo '<INPUT TYPE="button" name="refreshButton" value="Refresh" onClick="window.location.reload()"></form>';

	function create_tree_items($tree_items_file, $othervars) {
		extract($othervars);
		$deployment_info = URIinfo('D'.$GLOBALS['Did'], $user_id, $key, $db);
		#Change the struct for project
		#wait for allprojects.php to retrieve all the projects first and put them on session :-)
		$s3ql['db'] = $db;
		$s3ql['user_id'] = $user_id;
		$s3ql['from'] = 'projects';
		$s3ql['order_by']='project_id asc';

		$projects = S3QLaction($s3ql);
		$treeitem .= sprintf("%s\n", "var TREE_ITEMS = [ ['Projects', '".$action['listprojects']."',");
		if(is_array($projects)) {
			foreach($projects as $project_info) {
				#$acl = find_final_acl($user_id, $project_info['project_id'], $db);
				$treeitem .= sprintf("%s\n", "['".addslashes(urldecode( $project_info['project_name']))."', '".$action['project']."&project_id=". $project_info['project_id']."',"); #open the project

				#Create the tree node for each shared resource
				if(is_array($_SESSION[$user_id]['resources'][$project_info['project_id']])) {
					$classes = $_SESSION[$user_id]['resources'][$project_info['project_id']];
				} else {
					$s3ql=compact('user_id','db');
					$s3ql['select']='*';
					$s3ql['from'] = 'collections';
					$s3ql['where']['project_id'] = $project_info['project_id'];
					$classes = S3QLaction($s3ql);
					#$classes = S3QLaction($s3ql);
				}
				if(is_array($classes)) {
					/*
					$s3ql=compact('user_id','db');
					$s3ql['select']='*';
					$s3ql['from'] = 'rules';
					$s3ql['where']['project_id'] = $project_info['project_id'];
					#$s3ql['where']['object']="!='UID'";
					$rules = S3QLaction($s3ql);
					#separate the rules per subject
					foreach ($rules as $rule_info) {
						if($rule_info['object']!='UID')
						$subject_rules['C'.$rule_info['subject_id']][] = $rule_info;
					}
					*/
					foreach($classes as $resource_info) {
						#$rule_id = get_rule_id_by_entity_id($resource_info['resource_id'],  $resource_info['project_id'], $db);
						$treeitem .= sprintf("%s\n", "	['".addslashes(urldecode( $resource_info['entity']))."', '".$action['resource']."&project_id=".$project_info['project_id']."&class_id=". $resource_info['resource_id']."&rule_id=".$rule_id."',");#open the resource
				
						#List the rules for each shared resource
						$rules = $subject_rules['C'.$resource_info['class_id']];
						#Make the node for each rule
						/*			
						if (is_array($rules)) {
							foreach ($rules as $rule_info) {
								$treeitem .= "		['".addslashes(urldecode($rule_info['verb']))."<B>|</B>".addslashes(urldecode($rule_info['object']))."', '".$action['querypage']."&project_id=".$project_info['project_id']."&class_id=".$resource_info['resource_id']."&rule_id=".$rule_info['rule_id']."'";#open the verb|object
								$treeitem .= sprintf("%s\n", "],"); #close the verb|object
							}
						}
						*/
						#Make a node for new rule
						if($resource_info['view']) {
							$treeitem .= "		['<I>[Query ".addslashes(urldecode($resource_info['entity']))."]</I>', '".$action['querypage']."&class_id=".$resource_info['resource_id']."&project_id=".$project_info['project_id']."'";#query page for class
							$treeitem .= sprintf("%s\n", "],");
							$treeitem .= "		['<I>[List all ".addslashes(urldecode($resource_info['entity']))."]</I>', '".$action['querypage']."&class_id=".$resource_info['resource_id']."&project_id=".$project_info['project_id']."&listall=yes'";#list instances
							$treeitem .= sprintf("%s\n", "],"); 
						}
						if($resource_info['add_data']) {
							$treeitem .= "		['<I>[Add ".addslashes(urldecode($resource_info['entity']))."]</I>', '".$action['insertinstance']."&class_id=".$resource_info['resource_id']."&project_id=".$project_info['project_id']."'";#add instance
							$treeitem .= sprintf("%s\n", "],");
							$treeitem .= "		['<I>[Add rule]</I>', '".$action['createrule']."&project_id=".$project_info['project_id']."&class_id=".$resource_info['resource_id']."&rule_id=".$rule_id."'";#open the verb|object
							$treeitem .= sprintf("%s\n", "],"); #close the verb|object
						}
						$treeitem .= sprintf("%s\n", "	],"); #close the shared resource

						#Create a node for new resource
					}
				}
				if($project_info['add_data']) {
					$treeitem .= sprintf("%s\n", "	['[<I>New Collection</I>]', '".$action['createclass']."&project_id=".$project_info['project_id']."',");#open the resource
					$treeitem .= sprintf("%s\n", "	],"); #close the shared resource
				}
				$treeitem .= sprintf("%s\n", "],"); #close the project
			}
		}

		#Create a node for new project
		if($deployment_info['propagate']) {
			$treeitem .= sprintf("%s\n", "['[<I>New project</I>]', '".$action['createproject']."',"); #open the project
			$treeitem .= sprintf("%s\n", "],"); #close the project
		}
		$treeitem .= "] ];"; #close the tree
		return $treeitem;
	}
?>