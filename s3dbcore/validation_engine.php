<?php
	#Validation engine for inserting statements on S3DB
	function validateInputs($Z) {		#function validateInputs is meant to be a general validation function that is based on rules for validation
		#The rules come in the format of triples
		#$validity = validateInputs(compact('element', 'inputs', 'oldvalues', 'info', 'db'))
		$messages = $GLOBALS['messages'];
		$error_codes = $GLOBALS['error_codes'];
		if(!$model) { $model = 'nsy'; }
		extract($Z);

		if(ereg('edit|update', $action)) {
			$action = 'edit';
			if(empty($inputs)) {
				$inputs=$info;
			} else {
				foreach($info as $inK=>$inV) {
					$inputs[$inK] = $inV;
				}
			}
		} else {
			$action = 'create';
		}
	
		$code = $GLOBALS['s3codesInv'][$element];
		switch ($code) {
			case 'D':
				$ruleValid[$element]=array(
						'<error>'.$error_codes['something_missing'].'</error><message>URL cannot be empty</message>'=>(!empty($inputs['url']) || !in_array('url', array_keys($inputs))),
						'<error>'.$error_codes['something_missing'].'</error><message>publickey cannot be empty</message>'=>(!empty($inputs['publickey']) || !in_array('publickey', array_keys($inputs)))
					);
				break;
			case 'U':
				$ruleValid[$element]=array(
						'<error>'.$error_codes['something_missing'].'</error><message>Login cannot be empty</message>'=>!empty($inputs['account_lid']),
						'<error>'.$error_codes['wrong_input'].'</error><message>Account type must be a,u,g or p</message>'=>ereg('^(a|u|g|p|r)$', $inputs['account_type']),
						'<error>'.$error_codes['something_missing'].'</error><message>Account status cannot be empty</message>'=>!empty($inputs['account_status']), 
						'<error>'.$error_codes['something_missing'].'</error><message>username cannot be empty</message>'=>!empty($inputs['account_uname']), 
						'<error>'.$error_codes['something_missing'].'</error><message>email cannot be empty</message>'=>!empty($inputs['account_email']), 
						'<error>'.$error_codes['something_missing'].'</error><message>password cannot be empty</message>'=>!empty($inputs['account_pwd']), 
						'<error>'.$error_codes['repeating_action'].'</error><message>User '.$inputs['account_lid'].' already exists</message>'=>!user_already_exist($info['user_id'], $inputs, $db),
						'<error>'.$error_codes['repeating_action'].'</error><message>Email '.$inputs['account_email'].' already exists</message>'=>((!email_already_exist($Z) || $inputs['account_email']=='s3db@s3db.org') || ($action=='edit' && $info['account_email']==$inputs['account_email'])),
						'<error>'.$error_codes['wrong_input'].'</error><message>Permission level '.$inputs['permission_level'].' is invalid</message>'=>($inputs['permission_level']=="" || eregi('['.$model.']+',$inputs['permission_level'])),
						'<error>'.$error_codes['no_permission_message'].'</error><message>User is not allowed to update permission on user'.$info['user_id'].'</message>'=>($info['created_by']==$user_id || $info['account_id']==$user_id || $user_id==1)
					);
				break;
			case 'G':
				$ruleValid[$element]=array(
						'<error>'.$error_codes['something_missing'].'</error><message>Group name cannot be empty</message>'=>!empty($inputs['account_lid']),
						'<error>'.$error_codes['repeating_action'].'</error><message>Group '.$inputs['account_lid'].' already exists</message>'=>!user_already_exist($info['group_id'], $inputs, $db)
					);
				break;
			case 'P':
				$ruleValid[$element]=array('<error>'.$error_codes['something_missing'].'</error><message>Project Name cannot be empty</message>'=>!empty($inputs['project_name']));
				break;
			case 'C':
				$ruleValid[$element]=array(
						'<error>'.$error_codes['something_missing'].'</error><message>Name Cannot Be Empty'.$messages['syntax_message'].'</message>'=>!empty($inputs['entity']), '<error>'.$error_codes['repeating_action'].'</error><message>Collection Already Exists in Project</message>'=>!is_resource_exists($inputs, $db),
						#'<error>'.$error_codes['repeating_action'].'</error><message>'.$inputs['entity'].' Already Exists as an Object in Project</message>'=>!is_already_class($inputs, $db),
						'<error>'.$error_codes['nothing_to_change'].'</error><message>Nothing Was Changed</message>'=>!nothing_changed($inputs, $oldvalues)
					);
				break;
			case 'I':
				$ruleValid[$element]=array(
						'<error>'.$error_codes['nothing_to_change'].'</error><message>Nothing Was Changed</message>'=>!nothing_changed($inputs, $oldvalues),
						'<error>'.$error_codes['something_missing'].'</error><message>Class_id Cannot Be Empty</message>'=>($action!='create' || !empty($inputs['resource_class_id']))
					);
				break;
			case 'R':
				$ruleValid[$element]=array(
						'<error>'.$error_codes['something_missing'].'</error><message>Subject Cannot Be Empty</message>'=>(!empty($inputs['subject']) || !empty($inputs['subject_id'])), 
						'<error>'.$error_codes['something_missing'].'</error><message>Class_id was not found for this Subject</message>'=>!empty($inputs['subject_id']), 
						'<error>'.$error_codes['something_missing'].'</error><message>Verb Cannot Be Empty</message>'=>(!empty($inputs['verb']) || !empty($inputs['verb_id'])),
						'<error>'.$error_codes['something_missing'].'</error><message>Object Cannot Be Empty</message>'=>(!empty($inputs['object']) || !empty($inputs['object_id'])),
						'<error>'.$error_codes['repeating_action'].'</error><message>Rule Already Exists</message>'=>!rule_exists(compact('info', 'db')),
						'<error>'.$error_codes['nothing_to_change'].'</error><message>Nothing Was Changed</message>'=>!nothing_changed($inputs, $oldvalues),
						'<error>'.$error_codes['wrong_input'].'</error><message>Subject_id Must Exist as a Class Before Creating this Rule</message>'=>(subject_in_project($inputs['subject_id'], $inputs['project_id'], $db) && $info['object']!='UID'),
						'<error>'.$error_codes['repeating_action'].'</error><message>Request Is Already Pending For This Rule</message>'=>!($info['project_id']!=$inputs['project_id'] &&	request_already_pending($info, $db))
					);
				break;
			case 'S':
				$ruleValid[$element]= array(
						'<error>'.$error_codes['something_missing'].'</error><message>Value Cannot Be Empty</message>'=>($inputs['value']!=""), 
						'<error>'.$error_codes['something_missing'].'</error><message>Instance_id Cannot Be Empty</message>'=>!(empty($inputs['instance_id']) && empty($inputs['resource_id'])),
						'<error>'.$error_codes['something_missing'].'</error><message>Rule_id Cannot Be Empty</message>'=>!empty($inputs['rule_id']),
						#'<error>'.$error_codes['repeating_action'].'</error><message>Value Already Exists in Rule</message>'=>!statement_exists(compact('info', 'db')),
						'<error>'.$error_codes['wrong_input'].'</error><message>Value Must Be a Valid Instance_id of Object of Rule</message>'=>(objectResourcANDvalueInstance(array('rule_id'=>$inputs['rule_id'], 'value'=>$inputs['value'], 'user_id'=>$user_id, 'key'=>$key, 'db'=>$db))),
						'<error>'.$error_codes['not_valid'].'</error><message>This Rule Requires the Format: '.getRuleValidation($inputs['rule_id'], $key, $user_id, $db).'</message>'=>validate_statement_value($inputs['rule_id'],$inputs['value'], $db),
						'<error>'.$error_codes['nothing_to_change'].'</error><message>Nothing Was Changed</message>'=>!nothing_changed($inputs, $oldvalues)
					);
				break;
			case 'F';
				$ruleValid[$element]= array(
						'<error>'.$error_codes['something_missing'].'</error><message>Filekey cannot be empty</message>'=>!empty($inputs['filekey']), 
						'<error>'.$error_codes['something_missing'].'</error><message>Instance_id Cannot Be Empty</message>'=>!(empty($inputs['instance_id']) && empty($inputs['resource_id'])),
						'<error>'.$error_codes['something_missing'].'</error><message>Rule_id Cannot Be Empty</message>'=>!empty($inputs['rule_id']),
						'<error>'.$error_codes['something_missing'].'</error><message>File was not found</message>'=>fileFound(array('filekey'=>$inputs['filekey'], 'db'=>$db, 'user_id'=>$user_id, 'rule_id'=>$inputs['rule_id'])),
						'<error>'.$error_codes['nothing_to_change'].'</error><message>Nothing Was Changed</message>'=>!nothing_changed($inputs, $oldvalues)
					);
				break;
		}
		if(!min($ruleValid[$element])) {		#if min is a false, then one of them is a false
			ereg('<error>([0-9]+)</error>(.*)<message>(.*)</message>', array_search('0', $ruleValid[$element]), $s3qlout);
			$error_code = $s3qlout[1];
			$message = $s3qlout[3];
			return array(false,'error_code'=>$error_code,'message'=>$message);
			#return $message= array(false, $error_code, $message);
		} else {
			#$id_name =$GLOBALS['s3ids'][$element];
			#$id_name.' '.$info[$id_name].' ';
			$error_code = '0';
			$message = "Validation Successfull";
			return array(true,'error_code'=>$error_code,'message'=>$message);
		}
	}

	function rule_exists($R) {
		extract($R);
		$regexp=$GLOBALS['regexp'];
		#$sql = "select rule_id from s3db_rule where subject='".$rule_info['subject']."' and verb='".$rule_info['verb']."' and object='".$rule_info['object']."' and project_id='".$project_id."'";
		$sql = "select rule_id from s3db_rule where subject='".$info['subject']."' and verb='".$info['verb']."' and object='".$info['object']."' and rule_id!='".$info['rule_id']."' and project_id='".$info['project_id']."'" ;
		$db->query($sql, __LINE__, __FILE__);
		if($db->next_record()) {
			return True;
		} else {	
			return False;
		}
	}

	function is_entity_exists($edited_resource) {
		$db = $_SESSION['db'];
		$sql = "select resource_id from s3db_resource where entity = '".$entity."' and project_id='".$_REQUEST['project_id']."'";
		$db->query($sql, __LINE__, __FILE__);
		if($db->next_record()) {
			return True;
		} else {
			return False;
		}
	}

	function rule_object_is_resource($entity, $project_id) {
		$db = $_SESSION['db'];
		$sql = "select rule_id from s3db_rule where object='".$entity."' and project_id='".$project_id."'";
		$db->query($sql, __LINE__, __FILE__);
		while($db->next_record()) {
			$rules[] = Array('rule_id'=>$db->f('rule_id'));
		}
		return $rules;		
	}
	
	function validate_access_key_inputs($I) {
		if(is_array($I)) { extract($I); }
		if ($inputs['UID']!='') {
			$element_info = URI($inputs['UID'], $user_id, $db);
		}
		if ($inputs['user_id']!='') {
			$user_info = URIinfo('U'.$inputs['user_id'], $user_id, $key, $db);
		}

		if($inputs['key_id']=='' || $inputs['expires']=='' ) {
			return 0;
		} elseif(strlen($inputs['key_id'])<10) {
			return 1;
		} elseif(!ereg ("([2-5][0-9][0-9][0-9])-([0-1][0-9])-([0-3][0-9])", $inputs['expires'])) {
			return 2;
		} elseif(access_key_exists($inputs['key_id'], $db)) {
			return 3;
		} elseif($inputs['expires'] < date('Y-m-d')) {
			return 4;
		} elseif(htmlentities($inputs['key_id'])!=$inputs['key_id']) {
			return 8;
		} elseif($inputs['UID']!='' && !is_array($element_info)) {
			return 6;
		} elseif($inputs['UID']!='' && $element_info['created_by']!=$user_id) {
			return (7);
		} elseif($inputs['user_id']!='' && $user_info['created_by']!=$user_id && $user_id!=1) {
			return (9);
		} else {
			return 5;
		}
	}

	function entered_before($rule_id) {
		$rule_value_pairs = $_SESSION['rule_value_pairs'];
		if(count($rule_value_pairs) > 0) {
			$rule_value_pair_rule_id = Array();
			foreach($rule_value_pairs as $i => $value) {
				array_push($rule_value_pair_rule_id, $rule_value_pairs[$i]['rule_id']);
			}
			if(in_array($rule_id, $rule_value_pair_rule_id)) {
				return $rule_value_pairs[$i]['value'];
			} elseif(in_array($rule_id, $rule_value_pair_rule_id)) {
				return $rule_value_pairs[$i]['value'];
			} else {
				return '';
			}
		} else {
			return '';
		}
	}

	function request_already_pending($rule_info, $db) {
		$regexp = $GLOBALS['regexp'];
		$sql = "select * from s3db_access_rules where rule_id = '".$rule_info['rule_id']."' and project_id = '".$rule_info['project_id']."' and status ".$regexp." '^(connected|pending)$';";
		$db->query($sql, __LINE__, __FILE__);
	
		if($db->next_record()) {
			return True;
		} else {
			return False;
		}
	}

	function validate_remote_user($account_info, $user_url, $key) { 		#user_url may have information on login, remove that before checking
		#if key is not valid in remote url, check if there is an indication on where the user has been created. If there is no indication, game over!
		$db = CreateObject('s3dbapi.db');
	 	$db->Halt_On_Error = 'no';
        $db->Host     = $GLOBALS['s3db_info']['server']['db']['db_host'];
        $db->Type     = $GLOBALS['s3db_info']['server']['db']['db_type'];
        $db->Database = $GLOBALS['s3db_info']['server']['db']['db_name'];
        $db->User     = $GLOBALS['s3db_info']['server']['db']['db_user'];
        $db->Password = $GLOBALS['s3db_info']['server']['db']['db_pass'];
        $db->connect();

		#$sql = "select * from s3db_account where account_status = 'A' and (account_lid = '".$user_url."' or account_lid = '".$user_url.'#'.$account_info['account_lid']."')";
		$sql = "select * from s3db_account where account_status = 'A' and account_id = '".$user_url."'";
		$db->query($sql, __LINE__, __FILE__);
		if($db->next_record()) {
			$account_id = $db->f('account_id');
			$account_lid = $db->f('account_lid');
			$sql ="insert into s3db_access_log (login_timestamp, session_id, login_id, ip) values(now(), 'remote login:".$user_url."','".$account_info['account_id']."','".$_SERVER['REMOTE_ADDR']."')";
			$db->query($sql, __LINE__, __FILE__);
			$sql = "update s3db_account set account_lid = '".$account_id.'#'.$account_info['account_lid']."', account_uname = '".$account_info['account_uname']."', account_email = '".$account_info['account_email']."' where account_id = '".$account_id."'";
			$db->query($sql, __LINE__, __FILE__);

			#$sql = "insert into s3db_access_keys (key_id, account_id, expires, notes) values ('".$key."', '".$account_id."', '".date('Y-m-d',time() + (1 * 24 * 60 * 60))."', 'Remote key, will expire in 24h')";
			$sql = "insert into s3db_access_keys (key_id, account_id, expires, notes) values ('".$key."', '".$account_id."', '".date('Y-m-d H:i:s',time() + (1 * 1 * 60 * 60))."', 'Remote key, will expire in 1h')";
			$db->query($sql, __LINE__, __FILE__);
			return  True;
		}

	}
	
	function validate_permission($Z) {		
		#Syntax: validate_permission(compact('permission_info', 'user_id', 'db', 'info'));
		extract($Z);
		$s3codes=$GLOBALS['s3codes'];

		#if(ereg('(C|I)', substr($permission_info['uid'], 0,1)))
		#		return (7);
		if(ereg('^D', $permission_info['uid'])) {
			return (0);
		}
		if(!$state) { $state=3; }
		if(!$model) { $model = 'nsy'; }

		if($permission_info['shared_with']!='') {
			$shared_with_info = URI($permission_info['shared_with'], $user_id, $db);
			#($s3codes[substr($permission_info['shared_with'], 0,1)], substr($permission_info['shared_with'], 1, strlen($permission_info['shared_with'])+1), $db);
		} else {
			return (8);
		}

		if($permission_info['uid']!='') {
			$shared_id_info = URI($permission_info['uid'], $user_id, $db);
		} else {
			return (8);
		}
	
		#Does the permission on user comply with the model? Convert if not
		if(!eregi('['.$model.']', $shared_id_info['permission_level'])) {
			$userPermision = str_replace(array('0','1','2'), str_split($model), $shared_id_info['permission_level']);
		} else {
			$userPermision =  $shared_id_info['permission_level'];
		}
	
		#Same with the user to assign
		if(!eregi('['.$model.']', $permission_info['permission_level'])) {
			$permision2assign = str_replace(array('0','1','2'), str_split($model), $permission_info['permission_level']);
		} else {
			$permision2assign= $permission_info['permission_level'];
		}

		#if(!is_array($shared_with_info))
		#	return (4);
		#elseif(!is_array($shared_id_info))
		#	return (5);
		#user cannot grant permission on a resource greater than he himself has
		#elseif(!$shared_id_info['add_data'])
	
		##Permission can be a combination of 3 or + letter state  or a combination of 3 digits, but not both
		if(!ereg('(^[0-2][0-2]$|^[0-2][0-2][0-2]$)',  $permision2assign)) {
			if(!ereg('(^[a-zA-Z-]+$)',  $permision2assign, $pL)) {
				return (1);
			}
		}	
	
		#Now check if user actually has permission to add other users to this resource:
		#In order to add other users to the resource, user must have permission to edit it.
		$isOwner = $shared_id_info['created_by']==$user_id;
		if($isOwner) {	#can only give permission that he himself effecitvelly has. replace all numbers with nsy
			$literal = str_split($model);
			$numeric = range(0,2);
			for($i=0; $i < $state; $i++) {
				$digit2assign = substr($permision2assign,$i,1);
				$digitOnUser = 	substr($userPermision,$i,1);
				if($digit2assign!='-' && $numeric[array_search($digit2assign, $literal)]>$numeric[array_search(strtolower($digitOnUser), $literal)]) {
					if($user_id!='1') {
						return (6);
					}
				}
			}
		}
		if(has_permission($permission_info, $db)) {
			return (2); 
		} else {
			return (0);
		}
	}

	function validate_permission1($Z) {
		#Syntax: validate_permission(compact('permission_info', 'user_id', 'db', 'info'));
		extract($Z);
		$s3codes=$GLOBALS['s3codes'];

		#if(ereg('(C|I)', substr($permission_info['uid'], 0,1)))
		#		return (7);
		if(ereg('^D', $permission_info['uid'])) {
			return (0);
		}
		if($permission_info['shared_with']!='') {
			$shared_with_info = URI($permission_info['shared_with'], $user_id, $db);
			#($s3codes[substr($permission_info['shared_with'], 0,1)], substr($permission_info['shared_with'], 1, strlen($permission_info['shared_with'])+1), $db);
		} else {
			return (8);
		}

		if($permission_info['uid']!='') {
			$shared_id_info = URI($permission_info['uid'], $user_id, $db);
		} else {
			return (8);
		}

		#if(!is_array($shared_with_info))
		#	return (4);
		#elseif(!is_array($shared_id_info))
		#	return (5);
		#user cannot grant permission on a resource greater than he himself has
		#elseif(!$shared_id_info['add_data'])
		#elseif(!is_array())
		#elseif (substr($permission_info['uid'],1,strlen($permission_info['uid'])+1)!=$permission_info['id']) {
		#	return (3);
		#}
	
		if(!ereg('(^[0-2][0-2]$|^[0-2][0-2][0-2]$)',  $permission_info['permission_level'])) {
			return (1);
		} elseif(substr($shared_id_info['permission_level'],0,1)<substr($permission_info['permission_level'],0,1) || substr($shared_id_info['permission_level'],1,1)<substr($permission_info['permission_level'],1,1) || substr($shared_id_info['permission_level'],2,1)<substr($permission_info['permission_level'],2,1)) {
			return (6);
		} elseif(has_permission($permission_info, $db)) {
			return (2); 
		} else {
			return (0);
		}
	}

	function remoteURLretrieval($uid_info, $db) {
		if(is_array($uid_info)) {
			extract($uid_info);
		} else {
			$uid_info=uid($uid_info);
			extract($uid_info);
		}
		if(!http_test_existance($Did)) { 		#find $Did url , #is it registered in my internal table?
			$did_url = findDidUrl($Did, $db);
			$dateDiff_min= (strtotime(date('Y-m-d H:i:s'))-strtotime($did_url['checked_valid']))/60;

			#did_url empty? Mothership working?#checked no longer than an hour?
			if(empty($did_url) || $dateDiff_min>60) {
				#$mothership = $GLOBALS['s3db_info']['deployment']['mothership'];
				$mothership = $uid_info['MS'];
				if(http_test_existance($mothership)) {
					#call mothership, find true url
					$true_url = fread(fopen($mothership.'/s3rl.php?Did='.$Did,'r'), '100000');
					if(!empty($true_url)) {
						$data = html2cell($true_url);
					}
					$data[2]['deployment_id']=substr($Did, 1,strlen($Did));
						
					if(http_test_existance(trim($data[2]['url']))) {
						$data[2]['checked_valid']=date('Y-m-d H:i:s');
					} else {
						$data[2]['checked_valid']='';
					}
					#now update true url in local
					if(empty($did_url)) {
						insertDidUrl($data[2], $db);
					} else {
						updateDidUrl($data[2], $db);
					}
					#and define the variable
					$url = $data['url'];
					
				}
			} else {
				$url = trim($did_url['url']);
			}
		} else {
			$url = $Did;
		}
		return ($url);
	}

	function validate_statement_value($rule_id,$value, $db) {
		$sql = "select validation from s3db_rule where rule_id = '".$rule_id."'";
		$db->query($sql, __LINE__, __FILE__);
		if($db->next_record()) {
			$validation = $db->f('validation');
		}
		$types = array(
					'number'=>'([0-9]+)',
					'string'=>'([a-zA-Z]+)',
					'boolean'=> '(yes|no)',
					'date'=>'([1-2][0-9][0-9][0-9])-([1-2][0-9])-([1-3][0-9])',
					'yyyy-mm-dd'=>'([1-2][0-9][0-9][0-9])-([1-2][0-9])-([1-3][0-9])',
					'mm-dd-yyyy'=>'([1-2][0-9])-([1-3][0-9])-([1-2][0-9][0-9][0-9])',
					'dd-mm-yyyy'=>'([1-3][0-9])-([1-2][0-9])-([1-2][0-9][0-9][0-9])', 
				);
		if(in_array($validation, array_keys($types))) {
			$validation = $types[$validation];
		} elseif(ereg('^xs:', $validation)) {
			$validation = XMLvalidation($validation);
		}
		if($validation =='') {
			return (True);
		} elseif(ereg($validation, $value)) {
			return (True);
		} else {
			return (False);
		}
	}
?>