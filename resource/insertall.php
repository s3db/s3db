<?php
	#insertall.php is a form for inserting the values of rules in a single instance at once
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
	
	#just to know where we are...
	$thisScript = end(explode('/', $_SERVER['SCRIPT_FILENAME'])).'?'.$_SERVER['argv'][0];

	$key = $_GET['key'];

	#Get the key, send it to check validity
	include_once('../core.header.php');

	if($key) {
		$user_id = get_entry('access_keys', 'account_id', 'key_id', $key, $db);
	} else {
		$user_id = $_SESSION['user']['account_id'];
	}

	#Universal variables
	$instance_id = ($_REQUEST['item_id']!='')?$_REQUEST['item_id']:$_REQUEST['instance_id'];
	if($instance_id) {
		$instance_info = URIinfo('I'.$instance_id, $user_id, $key, $db);
	}
	if($instance_id=='') {
		echo "Please specify a valid item_id";
		exit;
	} else {
		if(!$instance_info['add_data']) {
			echo "User cannot add statements in this instance";
			exit;
		} else {
			#include all the javascript functions for the menus...
			include('../S3DBjavascript.php');
		
			#and the short menu for the resource script
			if($class_id=='') {
				$class_id= $instance_info['resource_class_id'];
			}
			if($class_id == '') {
				$class_id = $instance_info['class_id'];
			}
			include('../action.header.php');
	
			#include the header for the instance
			include('instance.header.php'); #this is a header but an html one, not an HTTP one.
			echo '<form name="insertstatement" enctype="multipart/form-data"  action="'.$action['instanceform'].'" method="post" autocomplete="on">';
	
			$s3ql = compact('db', 'user_id');
			$s3ql['select'] = '*';
			$s3ql['from'] = 'rules';
			$s3ql['where']['subject_id'] = $instance_info['class_id'];
			if($_REQUEST['project_id']) {
				$s3ql['where']['project_id']=$_REQUEST['project_id'];
			}
			$s3ql['where']['object'] = "!=UID";
			$s3ql['format']='html';
			$rules = S3QLaction($s3ql);
	
			#need to remove the non-add data rules
			$js = sprintf("%s\n", '<script type="text/javascript">');
			$js .= sprintf("%s\n", 'function go_to_right_position()');
			$js .= sprintf("%s\n", '{');
				
			if($_POST['insert_all']) {
				$rule_ids = find_out_inserted_statement(compact('instance_info', 'rules','project_id', 'db')); #figure out how many/which rules were inserted
				$js .= sprintf("%s\n", ' 	window.location="#'.$rule_ids[0].'"');
				if(is_array($rule_ids)) {
					echo render_inserted_statement_all(compact('instance_info', 'rules','user_id', 'db','rule_ids', 'project_id'));
				}
			} else {
				$_SESSION['current_color']='0';
				$_SESSION['previous_verb']='';
				$resource_id=$instance_info['resource_id'];
				if(is_array($rules)) {
					$stats ='';
					$index = 1;
					foreach($rules as $rule_info) {
						if($rule_info['add_data']) {
							$form .= render_empty_form(compact('index', 'rule_info', 'project_id', 'instance_id', 'db', 'user_id'));
						}	
					}
				}
				echo $form;
			}
			$js .= sprintf("%s\n", '}');
			$js .= sprintf("%s\n", '</script>');
			echo $js;
				
			if(is_array($rules)) {
				echo '<input type="submit" name="insert_all" value="Insert">';
				echo '&nbsp;&nbsp;&nbsp;<input type="button" name="clean" value="Clear Form" onClick="window.location=\''.$action['instanceform'].'\'">';
				echo '&nbsp;&nbsp;&nbsp;<input type="button" name="clean" value="View Data" onClick="window.location=\''.$action['item'].'\'">';
			} else {
				echo "Create some rules for this class before inserting data.";
			}
			echo '</form>';
		}
	}

	function find_out_inserted_statement($S) {
		extract($S);
		$resource_id = $instance_info['resource_id'];
		$rule_ids = Array();
		if(is_array($rules)) {
			foreach($rules as $rule_info) {
				$rule_id = $rule_info['rule_id'];
				if($_POST['insert_all']) {
					if($_POST['input_'.$resource_id.'_'.$rule_id] !='' || $_POST['text_'.$resource_id.'_'.$rule_id] !='' || $_FILES['upload_input_'.$resource_id.'_'.$rule_id]['name'] !='' || $_POST['input_'.str_replace('.','_', $resource_id).'_'.str_replace('.','_',$rule_id)]!='') {
						array_push($rule_ids, $rule_id);
					}
				} else {
					if($_POST['insert_'.$resource_id.'_'.$rule_id] && ($_POST['input_'.$resource_id.'_'.$rule_id] !='' || $_POST['text_'.$resource_id.'_'.$rule_id] !='')) {
						array_push($rule_ids, $rule_id);
					}
				}
			}
		}
		return array_unique($rule_ids);
	}	

	# Function to parse a submitted statement
	function render_inserted_statement_all($I) {
		extract($I);
		$_SESSION['current_color']='0';
		$_SESSION['previous_verb']='';
		$instance_id = $instance_info['resource_id'];
	
		$stats ='';
		if(is_array($rules)) {
			foreach($rules as $rule_info) {
				$report_msg ='';
				$subject = $rule_info['subject'];
				$verb = $rule_info['verb'];
				$object = $rule_info['object'];
				$rule_id = $rule_info['rule_id'];
				$rule_notes = $rule_info['notes'];
				$notes = $_POST['text_'.$instance_id.'_'.$rule_id];
				$index = $index+1;
		
				if(in_array($rule_id, $rule_ids)) {
					#gather data from post
					if($_FILES['upload_input_'.$instance_id.'_'.$rule_id]['name']=='') {
						$value = $_POST['input_'.$instance_id.'_'.$rule_id];
						if($value=='') {
							$value = $_POST['input_'.str_replace('.', '_',$instance_id).'_'.str_replace('.', '_',$rule_id)];
						}
						#insert the statement, run S3QL
						$s3ql = compact('db', 'user_id');
						$s3ql['insert'] = 'statement';
						#$s3ql['where']['project_id'] = $project_id;
						$s3ql['where']['item_id'] = $instance_id;
						$s3ql['where']['rule_id'] = $rule_id;
						$s3ql['where']['value'] = $value;
						$s3ql['where']['notes'] = $notes;
						#$s3ql['format']='html';
						$done = S3QLaction($s3ql);
						$done = html2cell($done);
			
						#ereg('<error>([0-9]+)</error>.*<(message|statement_id)>(.*)</(message|statement_id)>', $done, $s3qlout);
						$statement_id = $done[2]['statement_id'];
						$S = compact('user_id', 'rule_info', 'instance_id', 'statement_id', 'value', 'notes', 'db', 'done');		
						if($done[2]['error_code']=='0') {
							$report_msg = render_inserted($s3ql, $statement_id);
							#$report_msg .= sprintf("%s\n", '		<br /><input type="button" value="Insert Another" onClick="window.location=\''.$action['instanceform'].'\'">');
							#$report_msg .= sprintf("%s\n", '		<br /><input type="button" value="Close Window" onClick="window.location=\''.$action['instanceform'].'\'">');
						} else {
							$report_msg = couldnot_insert_statement($S);
							#render_statement_already_exists($s3ql);
						}
						#elseif($s3qlout[1]=='7') {
						#	$report_msg = render_resource_doesnot_exist($s3ql);
						#} elseif($s3qlout[1]=='3') {	
						#	$report_msg = render_value_cannot_be_null($s3ql);
						#}
					} else { 		#a file was uploaded
						#project is the same that will go to instance
						$project_id = $rule_info['project_id'];
						$value = project_folder_name($project_id, $db);
			
						$notes =  $_REQUEST['text_'.$instance_id.'_'.$rule_id];
						$filename = $_FILES['upload_input_'.$instance_id.'_'.$rule_id]['name'];
						$mimetype = $_FILES['upload_input_'.$instance_id.'_'.$rule_id]['type'];
						$filesize = filesize($_FILES['upload_input_'.$instance_id.'_'.$rule_id]['tmp_name']);
						$uploadedfile = $_FILES['upload_input_'.$instance_id.'_'.$rule_id]['tmp_name'];
						if($filesize <= 0) {
							$report_msg = 'Filesize cannot be null';
						} elseif($filename == '') {
							$report_msg = 'Filename cannot be empty';
						} elseif($value=='' || $uploadedfile=='') {
							$report_msg = 'Could not move file, please check with you administrator if file uploads are allowed.';
						} else {
							$tmp = fileNameAndExtension($filename);
							extract($tmp);
								
							#write a filekey to send the file by the API
							$filekey = generateAFilekey(compact('filename', 'extension', 'filesize', 'user_id', 'db'));
								
							#move the file like the API would do
							$file =  $uploadedfile;
							$fileMoved = MoveFile(compact('filekey','db', 'file'));
								
							#generate a statement_id
							if($fileMoved) {
								$s3ql = compact('db', 'user_id');
								$s3ql['insert'] = 'file';
								$s3ql['where']['filekey'] = $filekey;
								$s3ql['where']['notes'] = $notes;
								$s3ql['where']['project_id'] = $project_id;
								$s3ql['where']['rule_id'] = $rule_id;
								$s3ql['where']['item_id'] = $instance_id;
								$s3ql['format']='html';
								$done = S3QLaction($s3ql);
								$done=html2cell($done);

								#ereg('<statement_id>([0-9]+)</statement_id>', $done, $s3qlout);
								$statement_id = $done[2]['file_id'];
								if($statement_id!='') {
									$insert='file';
									$S = compact('rule_id', 'instance_id','db','insert','filename');
									$report_msg = render_inserted($S, $statement_id);
								}
							} else {
								$report_msg = "<font color='red'>Could not move the file</font>";
								exit;
							}
						}
					}
				} else {
					$report_msg = render_empty_form(compact('index', 'rule_info', 'project_id', 'instance_id', 'db'));
				}
				$finalOutput .= $report_msg;
			}              
		}
		return $finalOutput;
	}
?>
