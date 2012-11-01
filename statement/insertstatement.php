<?php
	#insertstatement.php is the interface for inserting a statement (instance, value, notes and file)
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

	$instance_id = ($_REQUEST['item_id']=='')?$_REQUEST['instance_id']:$_REQUEST['item_id'];
	#$instance_info = get_info('instance', $instance_id, $db);
	#$instanceAcl = dataAcl(compact('instance_info', 'user_id', 'db', 'project_id'));
	$instance_info = URIinfo('I'.$instance_id, $user_id, $key, $db);
	$project_id = $_REQUEST['project_id'];
	#$acl = find_final_acl($user_id, $project_id, $db);
	
	$rule_id = $_REQUEST['rule_id'];
	$rule_info = URIinfo('R'.$rule_id, $user_id, $key, $db);
	
	$ruleOnProject = ereg('(^|_)'.$project_id.'_', $rule_info['permission']);
	#$ruleAcl = find_final_acl($user_id, $rule_info['project_id'], $db);#is user allowed on this rule

	#relevant extra arguments
	#$args = '?key='.$_REQUEST['key'].'&project_id='.$_REQUEST['project_id'].'&instance_id='.$_REQUEST['instance_id'].'&rule_id='.$rule_id;
	#include('../webActions.php');

	if(!$rule_info['add_data']) {
		echo "User cannot insert statements in this rule";
		exit;
	} else {
		#add the header of the instance
		include('../resource/instance.header.php');
		$insertbutton =$_REQUEST["insert_".str_replace('.','_',$instance_id)."_".str_replace('.','_',$rule_id)];
		$rules[0] = $rule_info;
		#$rules =  include_object_class_id($rules, $project_id, $db);
		$rule_info = $rules[0];
		if($insertbutton!='') {
			$value = $_REQUEST['input_'.$instance_id.'_'.$rule_id];
			if($value=='') {
				$value = $_REQUEST['input_'.str_replace('.','_',$instance_id).'_'.str_replace('.','_',$rule_id)];
			}
			$notes =  $_REQUEST['text_'.$instance_id.'_'.$rule_id];
			$linkref = $_REQUEST['Hyperlink_ref_'.$instance_id.'_'.$rule_id];
			$linkname = $_REQUEST['Hyperlink_name_'.$instance_id.'_'.$rule_id];
			if($linkname=='') { $linkname = $linkref; }
			$filename = $_FILES['upload_input_'.$instance_id.'_'.$rule_id]['name'];
			$mimetype = $_FILES['upload_input_'.$instance_id.'_'.$rule_id]['type'];
			$filesize = filesize($_FILES['upload_input_'.$instance_id.'_'.$rule_id]['tmp_name']);
			$uploadedfile = $_FILES['upload_input_'.$instance_id.'_'.$rule_id]['tmp_name'];
			#$statement_id = str_replace (array('.', ' '),'', microtime());
			$valid_entry = FALSE;

			#Minimal code inside inserts to avoid the temptation of displaying what's inside
			if($insertbutton=='Insert') {
				if($_FILES['upload_input_'.$instance_id.'_'.$rule_id]['name'] =='') {
					if ($value!='') {
						$s3ql['db'] = $db;
						$s3ql['user_id'] = $user_id;
						$s3ql['insert'] = 'statement';
						#$s3ql['where']['project_id'] = $project_id;
						$s3ql['where']['instance_id'] = $instance_id;
						$s3ql['where']['rule_id'] = $rule_id;
						$s3ql['where']['value'] = $value;
						$s3ql['where']['notes'] = trim($notes);
						$s3ql['format']='html';
						$done = S3QLaction($s3ql);
						$msg=html2cell($done);$msg = $msg[2];
						#ereg('<error>([0-9]+)</error>.*<(message|statement_id)>(.*)</(message|statement_id)>', $done, $s3qlout);
						#preg_match('/[0-9]+/', $done, $statement_id);
						
						if($msg['error_code']=='0') {
							$statement_id = $msg['statement_id'];
							$S = compact('user_id', 'rule_info', 'instance_id', 'statement_id', 'value', 'notes', 'db');
							$report_msg = render_inserted($s3ql, $statement_id);
							$report_msg .= sprintf("%s\n", '		<br /><input type="button" value="Insert Another" onClick="window.location=\''.$action['insertstatement'].'\'">');
							$report_msg .= sprintf("%s\n", '		&nbsp;&nbsp;<input type="button" value="Close Window" onClick="opener.window.location.reload(); self.close();return false;">');
							#exit;
						} else {
							$S = compact('user_id', 'rule_info', 'instance_id', 'statement_id', 'value', 'notes', 'db', 's3qlout');	
							$report_msg = $msg['message'];
							$report_msg .= couldnot_insert_statement($S);
							#exit;
						}
					} else {
						$report_msg = render_value_cannot_be_null($s3ql);
						#$message = "Value cannot be empty";
					}
				} else {
					$value = project_folder_name ($project_id, $db);
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
						$filekey = generateAFilekey(compact('filename', 'filesize', 'db','user_id'));

						#move the file like the API would do
						$file =  $uploadedfile;
						$fileMoved = MoveFile(compact('filekey','db', 'file'));

						#generate a statement_id
						if($fileMoved) {
							$s3ql = compact('db', 'user_id');
							$s3ql['insert'] = 'file';
							$s3ql['where']['filekey'] = $filekey;
							$s3ql['where']['notes'] = trim($notes);
							$s3ql['where']['rule_id'] = $rule_id;
							$s3ql['where']['item_id'] = $instance_id;
							$s3ql['format']='html';
							$done = S3QLaction($s3ql);
							$msg=html2cell($done);$msg = $msg[2];
							#ereg('<file_id>([0-9]+)</file_id>', $done, $s3qlout);
							$statement_id = $msg['file_id'];

							if($msg['error_code']==0) {
								$s3ql['file_name'] = $filename;
								$insert='file';
								$S = compact('rule_id', 'instance_id','db','insert','filename');
								$report_msg = render_inserted($S, $statement_id);
								$report_msg .= sprintf("%s\n", '		<br /><input type="button" value="Insert Another" onClick="window.location=\''.$action['insertstatement'].'\'">');
								$report_msg .= sprintf("%s\n", '		&nbsp;&nbsp;<input type="button" value="Close Window" onClick="opener.window.location.reload(); self.close();return false;">');
							} else {
								$report_msg = "<font color='red'>".$msg['message']."</font>";
							}
						} else {
							$report_msg = "<font color='red'>Could not move the file</font>";
						}
					}
				}
			}
			echo $report_msg;
			exit;
		}
	}
	$index='1';
	echo '<form enctype="multipart/form-data" name="insertstatement" action="'.$action['insertstatement'].'" method="post" autocomplete="on">';
	echo render_empty_form(compact('index', 'rule_info', 'project_id', 'instance_id', 'db', 'user_id'));
	echo '<td valign="top"><input name="insert_'.$instance_id.'_'.$rule_id.'" value="Insert" type="submit"></td></tr>';
?>
