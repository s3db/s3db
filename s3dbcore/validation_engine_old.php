<?php
#Validation engine for inserting statements on S3DB


function rule_exists($R)
	
	{
		extract($R);

		$regexp=$GLOBALS['regexp'];
		

		#$sql = "select rule_id from s3db_rule where subject='".$rule_info['subject']."' and verb='".$rule_info['verb']."' and object='".$rule_info['object']."' and project_id='".$project_id."'";
		$sql = "select rule_id from s3db_rule where subject='".$info['subject']."' and verb='".$info['verb']."' and object='".$info['object']."' and rule_id!='".$info['rule_id']."' and project_id='".$info['project_id']."'" ;
		#echo '<BR>'.$sql;
		$db->query($sql, __LINE__, __FILE__);
		if($db->next_record())
			return True;
		else	
			return False;
	}



function validate_rule_inputs($R)
	{
		extract($R);
		
		
		if($resource['entity']=='')
		$resource['entity']= $rule_info['subject'];
		if($resource['project_id']=='')
		$resource['project_id']= $rule_info['project_id'];
		
	
		if($rule_info['subject'] == '')
			return 1;
		else if($rule_info['verb'] == '')
			return 2;
		else if($rule_info['object'] == '')
			return 3;
		#else if($action =='create' && rule_exists($R))
		else if(rule_exists($R))
			return 4;
		else if ($action =='edit' && $rule_info['oldsubject']==$rule_info['newsubject'] && $rule_info['oldverb']==$rule_info['newverb'] && $rule_info['oldobject']==$rule_info['newobject'] && $rule_info['oldnotes']==$rule_info['newnotes'] && $rule_info['old_object_id']==$rule_info['object_id'] && $rule_info['old_subject_id']==$rule_info['subject_id'])
			return 5;
		#if($action =='edit' && !is_resource_exists($resource, $db))
		if(!is_resource_exists($resource, $db) && $rule_info['object']!='UID')
			return 6;
		if(request_already_pending($rule_info, $db))
			return 7;
		else 
			return 0;
				
	}
		


	
	##FUNCTION TO CHECK WHETHER THIS PROJECT HAS ACCESS TO RESOURCES FROM ANOTHER PROJECT. INPUT IS THE NAME OF THE OBJECT, OUTPUT IS THE INFORMATION TO ACCESS IT E.G. PROJECT_ID

	

	
	function is_entity_exists($edited_resource)
	{
		$db = $_SESSION['db'];
		$sql = "select resource_id from s3db_resource where entity = '".$entity."' and project_id='".$_REQUEST['project_id']."'";
		$db->query($sql, __LINE__, __FILE__);
		if($db->next_record())
			return True;
		else
			return False;

	}

	function rule_object_is_resource($entity, $project_id)
	{
                $db = $_SESSION['db'];
                $sql = "select rule_id from s3db_rule where object='".$entity."' and project_id='".$project_id."'";
                $db->query($sql, __LINE__, __FILE__);
		while($db->next_record())
		{
			$rules[] = Array('rule_id'=>$db->f('rule_id'));
		}
		return $rules;		
	}
	

		
	function validate_project_inputs($P)
	{
		extract($P);
		
		
		if($project_info['project_name']=='')
		{
			return 1;
		}
		#else if($project_info['project_description'] =='')
		#{
		#	return 0;
		#}
		#elseif(is_project_exist($P, $db) && $action=="create")
		#elseif(is_project_exist($P, $db))
		#{
		#	return 2;	
		#}
		elseif($project_info['project_owner']=='')
		{
			return 3;	
		}
		elseif($permission_info['permission_level']!='' && (!ereg('^[0-3]', $permission_info['permission_level']) || !ereg('[0-3]$', $permission_info['permission_level']) || !ereg('^[0-3][0-3]$', $permission_info['permission_level'])))
		{
			return 4;	
		}

		 if (count($shared_users)>=1)
		 foreach($shared_users as $i => $value)
                {
                        if(($shared_users[$i]['rule_modify'] == '2' || $shared_users[$i]['rule_delete'] == '1') && $shared_users[$i]['rule_read'] !='4')
                        {
                                $_SESSION['perms_error'] = $shared_users[$i]['account_lid'].' needs to have read permission on rule if you give modify/delete permission on rules of this project';
                                return 5;
                        }
                        if(($shared_users[$i]['resource_modify'] == '2' || $shared_users[$i]['resource_delete'] == '1') && $shared_users[$i]['resource_read'] !='4')
                        {
                                $_SESSION['perms_error'] = $shared_users[$i]['account_lid'].' needs to have read permission on resource if you give modify/delete permission on resources of this project';
                                return 5;
                        }
                        if(($shared_users[$i]['statement_modify'] == '2' || $shared_users[$i]['statement_delete'] == '1') && $shared_users[$i]['statement_read'] !='4')
                        {
                                $_SESSION['perms_error'] = $shared_users[$i]['account_lid'].' needs to have read permission on statement if you give modify/delete permission on statements of this project';
                                return 5;
                        }
                }
	
		return 0;
	}

	function validate_resource_inputs($R)
	{
		extract($R);
		
		
		
		#echo '<pre>';print_r($ruleValid);
		if($resource_info['entity'] == '')
			return 1;
		else if(is_resource_exists($resource_info, $db))
			return 2;
		else
			return 0;
				
	}



	
	function validate_user_inputs($U)
	{
		
		extract($U);


		if($inputs['account_lid']=='')
		{
			return 1;
		}
		if($inputs['account_type']=='')
		{
			return 2;
		}
		if($inputs['account_status']=='')
		{
			return 4;
		}
		
		
		else if($inputs['account_uname'] =='')
		{
			return 3;
		}
		elseif($inputs['account_pwd'] =='')
			{
				return 5;
			}
		elseif(user_already_exist($imp_user_id, $inputs, $db))
			{
				return 8;	
			}
			
		
		return 0;
	}

	function validate_group_inputs($G)
	{
		
		extract($G);
		
		if(user_already_exist($group_id, $inputs, $db))
		{
			return 1;	
		}
		return 0;
	}


	function validate_access_key_inputs($I)
	{
	 if(is_array($I)) 
		 extract($I);
	
	if ($inputs['UID']!='') {
	$element_info = URI($inputs['UID'], $user_id, $db);
	}

	
	if ($inputs['key_id']=='' || $inputs['expires']=='' )
	return 0;
	elseif (strlen($inputs['key_id'])<10)
	return 1;
	elseif (!ereg ("([2-5][0-9][0-9][0-9])-([0-1][0-9])-([0-3][0-9])", $inputs['expires']))
	return 2;
	elseif (access_key_exists($inputs['key_id'], $db))
	return 3;
	elseif ($inputs['expires'] < date('Y-m-d'))
	return 4;
	elseif (htmlentities($inputs['key_id'])!=$inputs['key_id']) {
	return 8;
	}
	elseif ($inputs['UID']!='' && !is_array($element_info)) {
	return 6;
	}
	elseif ($inputs['UID']!='' && $element_info['created_by']!=$user_id) {
	return (7);
	}
	else
	return 5;
	}

	function entered_before($rule_id)
        {
                $rule_value_pairs = $_SESSION['rule_value_pairs'];
                if(count($rule_value_pairs) > 0)
                {
                        $rule_value_pair_rule_id = Array();
                        foreach($rule_value_pairs as $i => $value)
                        {
                                array_push($rule_value_pair_rule_id, $rule_value_pairs[$i]['rule_id']);
                        }
                        if(in_array($rule_id, $rule_value_pair_rule_id))
                                return $rule_value_pairs[$i]['value'];
                        else if(in_array($rule_id, $rule_value_pair_rule_id))
                                return $rule_value_pairs[$i]['value'];
                        else 
                                return '';
                }
                else
                        return '';
        }

	




	function request_already_pending($rule_info, $db)
	{$regexp = $GLOBALS['regexp'];
	$sql = "select * from s3db_access_rules where rule_id = '".$rule_info['rule_id']."' and project_id = '".$rule_info['project_id']."' and status ".$regexp." '^(connected|pending)$';";
	#echo $sql;
	$db->query($sql, __LINE__, __FILE__);
	
	if($db->next_record())
		return True;
	else		
		return False;
	}



	function validate_remote_user($account_info, $user_url, $key)

{ #user_url may have information on login, remove that before checking
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

		#echo $sql;
		$db->query($sql, __LINE__, __FILE__);
		if($db->next_record())
		{
			
			$account_id = $db->f('account_id');
			$account_lid = $db->f('account_lid');
			
			$sql ="insert into s3db_access_log (login_timestamp, session_id, login_id, ip) values(now(), 'remote login:".$user_url."','".$account_info['account_id']."','".$_SERVER['REMOTE_ADDR']."')";
			$db->query($sql, __LINE__, __FILE__);

			$sql = "update s3db_account set account_lid = '".$account_id.'#'.$account_info['account_lid']."', account_uname = '".$account_info['account_uname']."', account_email = '".$account_info['account_email']."' where account_id = '".$account_id."'";

			#echo $sql;exit;

			$db->query($sql, __LINE__, __FILE__);

			#$sql = "insert into s3db_access_keys (key_id, account_id, expires, notes) values ('".$key."', '".$account_id."', '".date('Y-m-d',time() + (1 * 24 * 60 * 60))."', 'Remote key, will expire in 24h')";
			$sql = "insert into s3db_access_keys (key_id, account_id, expires, notes) values ('".$key."', '".$account_id."', '".date('Y-m-d H:i:s',time() + (1 * 1 * 60 * 60))."', 'Remote key, will expire in 1h')";

			$db->query($sql, __LINE__, __FILE__);


			return  True;
		
		}

}

function validate_permission($Z)
{#Syntax: validate_permission(compact('permission_info', 'user_id', 'db', 'info'));
	extract($Z);
	$s3codes=$GLOBALS['s3codes'];

#if(ereg('(C|I)', substr($permission_info['uid'], 0,1)))
#		return (7);
if(ereg('^D', $permission_info['uid'])){
return (0);
}



if($permission_info['shared_with']!='')
	{$shared_with_info = URI($permission_info['shared_with'], $user_id, $db);
#echo '<pre>';print_r($shared_with_info);
}
#($s3codes[substr($permission_info['shared_with'], 0,1)], substr($permission_info['shared_with'], 1, strlen($permission_info['shared_with'])+1), $db);
else 
	return (8);


if($permission_info['uid']!='')
	{
	
	$shared_id_info = URI($permission_info['uid'], $user_id, $db);
	
	}
else {
	return (8);
}

	
	#if(!is_array($shared_with_info))
	#	return (4);
	#elseif(!is_array($shared_id_info))
	#	return (5);
	#user cannot grant permission on a resource greater than he himself has
	#elseif(!$shared_id_info['add_data'])
	
	if(!ereg('(^[0-2][0-2]$|^[0-2][0-2][0-2]$)',  $permission_info['permission_level']))
		return (1);
	
	elseif(substr($shared_id_info['permission_level'],0,1)<substr($permission_info['permission_level'],0,1) || substr($shared_id_info['permission_level'],1,1)<substr($permission_info['permission_level'],1,1) || substr($shared_id_info['permission_level'],2,1)<substr($permission_info['permission_level'],2,1))
		return (6);
	#elseif(!is_array())
	#elseif (substr($permission_info['uid'],1,strlen($permission_info['uid'])+1)!=$permission_info['id']) {
		#return (3);
	#}
	elseif(has_permission($permission_info, $db))
		return (2); 
	else {
		return (0);
	}
	
}

function validate_statement_value($rule_id,$value, $db)
{
$sql = "select validation from s3db_rule where rule_id = '".$rule_id."'";

$db->query($sql, __LINE__, __FILE__);

if($db->next_record())
		{
			
			$validation = $db->f('validation');
		}

		$types = array('number'=>'([0-9]+)',
						'string'=>'([a-zA-Z]+)',
						'boolean'=> '(yes|no)',
						'date'=>'([1-2][0-9][0-9][0-9])-([1-2][0-9])-([1-3][0-9])',
						'yyyy-mm-dd'=>'([1-2][0-9][0-9][0-9])-([1-2][0-9])-([1-3][0-9])',
						'mm-dd-yyyy'=>'([1-2][0-9])-([1-3][0-9])-([1-2][0-9][0-9][0-9])',
						'dd-mm-yyyy'=>'([1-3][0-9])-([1-2][0-9])-([1-2][0-9][0-9][0-9])', 
						);

		if(in_array($validation, array_keys($types)))
			$validation = $types[$validation];
		elseif(ereg('^xs:', $validation)) {
			$validation = XMLvalidation($validation);
		}
			
if($validation =='')
	return (True);
elseif(ereg($validation, $value))
	return (True);
else {
	return (False);
}
}

function  XMLvalidation($validation)
{#this is an XML namespace
$urlWTyes = "http://www.w3.org/2001/XMLSchema-datatypes.xsd";
	
}
	

function validateInputs($Z)
{#function validateInputs is meant to be a general validation function that is based on rules for validation
#The rules come in the format of triples
#$validity = validateInputs(compact('element', 'inputs', 'oldvalues', 'info', 'db'))
$messages = $GLOBALS['messages'];
#echo '<pre>';print_r($messages);
extract($Z);

if(ereg('edit|update', $action))
	$inputs=$info;
$code = $GLOBALS['s3codesInv'][$element];

switch ($code) {
	case 'U':
		$ruleValid[$element]=array($messages['something_missing'].'<message>Login cannot be empty</message>'=>!empty($inputs['account_lid']),
						$messages['wrong_input'].'<message>Account type must be a,u,g or p</message>'=>ereg('^(a|u|g|p|r)$', $inputs['account_type']),
									$messages['something_missing'].'<message>Account status cannot be empty</message>'=>!empty($inputs['account_status']), 
									$messages['something_missing'].'<message>username cannot be empty</message>'=>!empty($inputs['account_uname']), 
									$messages['something_missing'].'<message>email cannot be empty</message>'=>!empty($inputs['account_email']), 
									$messages['something_missing'].'<message>password cannot be empty</message>'=>!empty($inputs['account_pwd']), 
									$messages['repeating_action'].'<message>User '.$inputs['account_lid'].' already exists</message>'=>!user_already_exist($info['user_id'], $inputs, $db),
									$messages['repeating_action'].'<message>Email '.$inputs['account_email'].' already exists</message>'=>(!email_already_exist($Z) || $inputs['account_email']=='s3db@s3db.org'));
	break;
	case 'G':
		$ruleValid[$element]=array(
									$messages['something_missing'].'<message>Group name cannot be empty</message>'=>!empty($inputs['account_lid']),
									$messages['repeating_action'].'<message>Group '.$inputs['account_lid'].' already exists</message>'=>!user_already_exist($info['group_id'], $inputs, $db));
	break;
	case 'P':
		$ruleValid[$element]=array(
						$messages['something_missing'].'<message>Project Name cannot be empty</message>'=>!empty($inputs['project_name'])
						);
	break;
	case 'C':
		$ruleValid[$element]=array(
								$messages['something_missing'].'<message>Entity Cannot Be Empty'.$messages['syntax_message'].'</message>'=>!empty($inputs['entity']), $messages['repeating_action'].'<message>Resource Already Exists in Project</message>'=>!is_resource_exists($inputs, $db),
								#$messages['repeating_action'].'<message>'.$inputs['entity'].' Already Exists as an Object in Project</message>'=>!is_already_class($inputs, $db),
								$messages['nothing_to_change'].'<message>Nothing Was Changed</message>'=>!nothing_changed($inputs, $oldvalues));
	break;
	case 'I':
		$ruleValid[$element]=array($messages['nothing_to_change'].'<message>Nothing Was Changed</message>'=>!nothing_changed($inputs, $oldvalues),
								$messages['something_missing'].'<message>Class_id Cannot Be Empty</message>'=>!empty($inputs['resource_class_id']));
	break;
	case 'R':
		$ruleValid[$element]=array(
								$messages['something_missing'].'<message>Subject Cannot Be Empty</message>'=>!empty($inputs['subject']), 
								$messages['something_missing'].'<message>Class_id was not found for this Subject</message>'=>!empty($inputs['subject_id']), 
								$messages['something_missing'].'<message>Verb Cannot Be Empty</message>'=>!empty($inputs['verb']),
								$messages['something_missing'].'<message>Object Cannot Be Empty</message>'=>!empty($inputs['object']),
								$messages['repeating_action'].'<message>Rule Already Exists</message>'=>!rule_exists(compact('info', 'db')),
								$messages['nothing_to_change'].'<message>Nothing Was Changed</message>'=>!nothing_changed($inputs, $oldvalues),
								$messages['wrong_input'].'<message>Subject_id Must Exist as a Class Before Creating this Rule</message>'=>(subject_in_project($inputs['subject_id'], $inputs['project_id'], $db) && $info['object']!='UID'),
								$messages['repeating_action'].'<message>Request Is Already Pending For This Rule</message>'=>!($info['project_id']!=$inputs['project_id'] &&	request_already_pending($info, $db)));
	break;
	case 'S':
		$ruleValid[$element]= array(
								$messages['something_missing'].'<message>Value Cannot Be Empty</message>'=>!empty($inputs['value']), 
								$messages['something_missing'].'<message>Instance_id Cannot Be Empty</message>'=>!(empty($inputs['instance_id']) && empty($inputs['resource_id'])),
								$messages['something_missing'].'<message>Rule_id Cannot Be Empty</message>'=>!empty($inputs['rule_id']),
								$messages['repeating_action'].'<message>Value Already Exists in Rule</message>'=>!statement_exists(compact('info', 'db')),
								$messages['wrong_input'].'<message>Value Must Be a Valid Instance_id of Object of Rule</message>'=>(objectResourcANDvalueInstance(array('rule_id'=>$inputs['rule_id'], 'value'=>$inputs['value'], 'user_id'=>$user_id, 'key'=>$key, 'db'=>$db))),
								$messages['not_valid'].'<message>This Rule Requires the Format: '.getRuleValidation($inputs['rule_id'], $key, $user_id, $db).'</message>'=>validate_statement_value($inputs['rule_id'],$inputs['value'], $db),
								$messages['nothing_to_change'].'<message>Nothing Was Changed</message>'=>!nothing_changed($inputs, $oldvalues));
	break;
	case 'F';
	$ruleValid[$element]= array(
								$messages['something_missing'].'<message>Filekey cannot be empty</message>'=>!empty($inputs['filekey']), 
								$messages['something_missing'].'<message>Instance_id Cannot Be Empty</message>'=>!(empty($inputs['instance_id']) && empty($inputs['resource_id'])),
								$messages['something_missing'].'<message>Rule_id Cannot Be Empty</message>'=>!empty($inputs['rule_id']),
								$messages['something_missing'].'<message>File was not found</message>'=>fileFound(array('filekey'=>$inputs['filekey'], 'db'=>$db, 'user_id'=>$user_id, 'rule_id'=>$inputs['rule_id'])),
								$messages['nothing_to_change'].'<message>Nothing Was Changed</message>'=>!nothing_changed($inputs, $oldvalues));
	break;
}
#echo '<pre>';print_r($info);exit;
if(!min($ruleValid[$element])) #if min is a false, then one of them is a false
	{
	return $message= array(false, array_search('0', $ruleValid[$element]));
	}
else {
	#$id_name =$GLOBALS['s3ids'][$element];
	#$id_name.' '.$info[$id_name].' ';
	return $message = array(true, $messages['success']);
}
							
}

function remoteURLretrieval($uid_info, $db)
{	
	if(is_array($uid_info))
	extract($uid_info);
	else {
		$uid_info=uid($uid_info);
		extract($uid_info);
	}
	
	if(!http_test_existance($Did))
			#find $Did url
				#is it registered in my internal table?
				{
					$did_url = findDidUrl($Did, $db);
					
					$dateDiff_min= (strtotime(date('Y-m-d H:i:s'))-strtotime($did_url['checked_valid']))/60;
					
					#did_url empty? Mothership working?#checked no longer than an hour?
					if(empty($did_url) || $dateDiff_min>60)
					{
					#$mothership = $GLOBALS['s3db_info']['deployment']['mothership'];
					$mothership = $uid_info['MS'];
						if(http_test_existance($mothership))
						{
						#call mothership, find true url
						$true_url = fread(fopen($mothership.'/s3rl.php?Did='.$Did,'r'), '100000');
						if(!empty($true_url))
							{$data = html2cell($true_url);
							
							}
						
						$data[2]['deployment_id']=substr($Did, 1,strlen($Did));
						
						if(http_test_existance(trim($data[2]['url'])))
							$data[2]['checked_valid']=date('Y-m-d H:i:s');
						else {
							$data[2]['checked_valid']='';
						}
						
						#now update true url in local
						if(empty($did_url))
						insertDidUrl($data[2], $db);
						else 
						updateDidUrl($data[2], $db);
						#and define the variable
						$url = $data['url'];
						
						}
					}
					else {
						$url = trim($did_url['url']);
					}
					
					#echo '<pre>';print_r($did_url);exit;
				}
				else {
					$url = $Did;
				}

				return ($url);
	
}
?>