<?php
	function create_resource($R) {
		extract($R);
		if ($resource_info['iid']=='') { $resource_info['iid'] = '1';}
		$R['resource_info']['resource_id'] = find_latest_UID('resource', $db)+1;
		$resource_id_created = insert_resource($R);
		if($resource_id_created) {
			$resource_id = find_latest_UID('resource', $db);
			$resource_info['resource_id'] = $resource_id;
			if($resource_info['iid'] == '0') {
				#INSERT LINE ON RULES TABLE
				$rule_info = Array(
								'project_id'=>$resource_info['project_id'],
								'owner'=>$user_id,
								'subject'=>$resource_info['entity'],
								'subject_id'=>$resource_info['resource_id'],
								'verb'=>'has UID',
								'object'=>'UID',
								'notes'=>nl2br($resource_info['notes'])
							);
				$db->query($sql, __LINE__, __FILE__);
				$R['rule_info']=$rule_info;
				$rule_inserted = insert_rule($R);
				
				if($rule_inserted) {
					$rule_info['rule_id'] = find_latest_UID('rule', $db);
					$inputs = array('newsubject'=>$resource_info['entity'], 'newverb'=>'has UID', 'newobject'=>'UID', 'newnotes'=>nl2br($resource_info['notes']));
					$action = 'create';
					$log = compact('action', 'rule_info', 'user_id', 'project_id', 'db', 'inputs');
					insert_rule_log($log);
				}
				
				#now check if there is any rule in the same project that already has this class as object. 
				$s3ql=compact('user_id','db');
				$s3ql['from']='rules';
				$s3ql['where']['object']=$resource_info['entity'];
				$s3ql['where']['project_id']=$resource_info['project_id'];
				$object_rules = S3QLaction($s3ql);

				if(is_array($object_rules)) {
					foreach ($object_rules as $key=>$rule_to_change) {
						$s3ql=compact('user_id','db');
						$s3ql['edit']='rule';
						$s3ql['where']['rule_id']=$rule_to_change['rule_id'];
						$s3ql['set']['object_id']=$resource_id;
						$done = S3QLaction($s3ql);
					}
				}
			} else {		#INSERT LINE ON STATEMENTS TABLE - deprecated, will keep instances in resource
				$statement_info = array(
									'project_id'=>$resource_info['project_id'],
									'resource_id'=>$resource_id,
									'rule_id'=>get_rule_id_by_entity_id($resource_info['resource_class_id'], $resource_info['project_id'], $db),
									'value'=>$resource_id, 
									'notes'=>$resource_info['notes'],
									'created_by'=>$resource_info['owner'],
									'db'=>$db
								);
				$R['statement_info']=$statement_info;
				insert_statement($R);
			}
			return $resource_id;	
		}
	}

	function create_project($P) {
		extract($P);
		$P['project_info']['project_id'] = find_latest_UID('project', $db)+1;
		$inputs = $project_info;
		$element = 'project';
		$insert_project = insert_s3db(compact('element', 'inputs', 'db', 'user_id'));
		#if (insert_project($P))
		if ($insert_project[0]) {
			#$project_id = find_latest_UID('project', $db);
			$project_id = $insert_project[2];
			$project_info['project_id']=$project_id;
		
			##Now create the folder on the extras for the files of this project
			$folder_code_name = random_string(15).'.project'.$project_id;
			$maindir = $GLOBALS['s3db_info']['server']['db']['uploads_folder'].$GLOBALS['s3db_info']['server']['db']['uploads_file'];
			$destinationfolder =  $maindir.'/'.$folder_code_name;
			#create the folder for the project
			if(mkdir($destinationfolder, 0777)) {
				$indexfile = $destinationfolder.'/index.php';
				if(file_exists($destinationfolder)) {
					file_put_contents ($indexfile , 'This folder cannot be accessed');
					chmod($indexfile, 0777);
				}
				$sql = "update s3db_project set project_folder = '".$folder_code_name."' where project_id = '".$project_id."'";
				$db->query($sql, __LINE__, __FILE__);
			} else {
				echo "Could not create directory for this project. You might not be able to upload files to this project.";
			}
			//$_SESSION['working_project'] = $project_id;	
			return True;		
		}
		return False;
	}

	function create_group($G) {
		#create group is a fucntion to take in group information and creaate a group
		extract($G);
		$newgroup['account_type'] = 'g';
		$owned_project_acl = owned_project_acl();
		$owned_project = owned_project();
		$inserted_user =  insert_user($newgroup);
		$account_id = $_SESSION['new_account_id'];
		
		if($inserted_user) {
			$db = $_SESSION['db'];
			$sql = "insert into s3db_account_group (account_id, group_id) values('".$account_id."', '".$account_id."')";
			$db->query($sql, __LINE__, __FILE__);
			if(!empty($newgroup['selected_users'])) {
				foreach($newgroup['selected_users'] as $i) {
					$sql = "insert into s3db_account_group (account_id, group_id) values('".$i."', '".$account_id."')";
					$db->query($sql, __LINE__, __FILE__);
					#if(count($owned_project) > 0) {
					#	foreach($owned_project as $j) {
					#		#if(!in_project_acl($owned_project_acl, $owned_project[$j]['project_id'], $i))
					#		#$db->query("insert into s3db_project_acl (acl_project_id, acl_account, acl_rights) values('".$owned_project[$j]['project_id']."', '".$i."', '0'");
					#	}
					#}
				}	
				return True;
			}
			return True;
		} else {
			return False;
		}
	}
	
	function create_user($U) {
		extract($U);
		$U['inputs']['account_addr_id']=insert_address($U);
		if($_SERVER['HTTP_X_FORWARDED_HOST']!='') {
			$server = $_SERVER['HTTP_X_FORWARDED_HOST'];
		} else { 
			$server = $_SERVER['HTTP_HOST'];
		}
		$s3dburl = 'http://'.$server.S3DB_URI_BASE;
		if(insert_user($U)) {
			#send and email to the user telling him about his new password!
			$message .= sprintf("%s\n\n",'Dear '.$inputs['account_uname'].',');
			$message .= sprintf("%s\n",'An account on s3db has been created on your behalf.');
			$message .= sprintf("%s\n",'Your login ID is: '.$inputs['account_lid']);
			$message .= sprintf("%s\n",'Your password is: '.$inputs['account_pwd']);
			$message .= sprintf("%s\n\n",'You can login at '.$s3dburl);
			$message .= sprintf("%s\n",'The S3DB team.(http://www.s3db.org)');
			$message .= sprintf("%s\n\n",'Note: Please do not reply, this is an automated message');

			$E = array('email'=>array($inputs['account_email']), 'message'=>$message, 'subject'=>'Your s3db account');
			if($GLOBALS['s3db_info']['server']['email_host']!='' && $GLOBALS['s3db_info']['server']['email_host']!='mail') {		 #if user did no input data on host of deleted it, don't send email
				send_email($E);
			}
			return True;
		} else {
			return False;
		}
	}

	function create_rule($R) {
		extract($R);
		$R['rule_info']['rule_id'] = find_latest_UID('rule', $db)+1;
		if (insert_rule($R)) {
			$R['rule_info']['rule_id'] = find_latest_UID('rule', $db);
			$R['action']='create';
			#insert_rule_log($R);
			return True;
		}
	}
?>