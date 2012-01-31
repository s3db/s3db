<?php

function insertLogs($uid, $info, $user_id, $db)
{
$letter = strtoupper(substr($uid, 0,1));
$oldvalues = $info[$uid];

$action = 'delete';
switch ($letter) {
	case 'R':
		{
		$rule_info = $info[$uid];
		insert_rule_log(compact('oldvalues', 'action', 'rule_info', 'user_id', 'db'));
		}
		break;
	case 'S':
		{
		$statement_info = $info[$uid];
		#echo '<pre>';print_r($oldvalues);exit;
		insert_statement_log(compact('oldvalues', 'action', 'statement_info', 'user_id', 'db'));
		}
		break;
	case 'U':
		{
			$user_info = $info[$uid];
			insert_access_log(compact('user_id', 'db'));
		}
		break;


}
	


}

function insert_statement($S)
	{
		
	
	
	#Extract the simple vars
	extract($S);
	if(is_array($statement_info)) 
		extract($statement_info);
	
	$created_by = $user_id;

	if($created_on =='')
		$created_on = 'now()';
		else
		$created_on = "'".$created_on."'";

		if ($modified_on==''|| $modified_on==0) 
			$modified_on = 'null';
		else
			$modified_on = "'".$modified_on."'";

		if($statement_id=='') 
				$statement_id =  s3id();
		#$statement_id = find_latest_UID('statement', $db)+1;
		#$statement_id = str_replace (array('.', ' '),'', microtime());
		
		
	$sql = "insert into s3db_statement(statement_id, project_id, resource_id, rule_id, value, notes, file_name, mime_type, file_size, created_on, created_by, permission, status) values ('".$statement_id."', '".$project_id."', '".$resource_id."', '".$rule_id."', '".$value."', '".$notes."', '".$filename."', '".$mimetype."', '".$filesize."',".$created_on.", '".$created_by."', '".$project_id."_', 'A')";

	#echo $sql;exit;
	$db->query($sql, __LINE__, __FILE__);	
	
	$dbdata = get_object_vars($db);
	
	if($dbdata['Errno']==0) return array(TRUE, $GLOBALS['messages']['success'].'<statement_id>'.$statement_id.'</statement_id>');
	else 
		return array(False);
	}



function insert_statement_log($S)

	{
	
	extract($S);
	if(is_array($statement_info))
	extract($statement_info);

	$tableFields = array('statement_id', 'rule_id', 'resource_id', 'project_id', 'value', 'notes', 'created_on', 'created_by');

	foreach ($tableFields as $a_field) {
		if($inputs[$a_field]=='')
		$oldvalues[$a_field] = $statement_info[$a_field];
		
	}
	
	#echo '<pre>';print_r($inputs);
	$statement_log_id = s3id();
	$modified_by=$user_id;
	$sql = "insert into s3db_statement_log (statement_log_id, statement_id, old_rule_id, old_resource_id, old_project_id, old_value, old_notes, action, modified_by, modified_on, created_on, created_by) values ('".$statement_log_id."', '".$oldvalues['statement_id']."', '".$oldvalues['rule_id']."','".$oldvalues['resource_id']."','".$oldvalues['project_id']."','".$oldvalues['value']."','".$oldvalues['notes']."','".$action."','".$modified_by."', now(), '".$oldvalues['created_on']."', '".$oldvalues['created_by']."')";

	#echo $sql;exit;

	$db->query($sql, __LINE__, __FILE__);
		
		$dbdata = get_object_vars($db);
		if($dbdata['Errno']==0) return TRUE;
		else 
		{$sql = "insert into s3db_statement_log (statement_log_id, statement_id, old_rule_id, old_resource_id, old_project_id, old_value, old_notes, action, modified_by, modified_on, created_on, created_by) values ('', '".$oldvalues['statement_id']."', '".$oldvalues['rule_id']."','".$oldvalues['resource_id']."','".$oldvalues['project_id']."','".$oldvalues['value']."','".$oldvalues['notes']."','".$action."','".$modified_by."', now(), '".$oldvalues['created_on']."', '".$oldvalues['created_by']."')";
#echo $sql;
		$db->query($sql, __LINE__, __FILE__);

		}
	}


function insert_access_log($S)
{
	  extract($S);
	  if($_SESSION['db']) { $session_id= session_id();}
	  else { $session_id= 'key'; }
	  
	  $sql = "insert into s3db_access_log (session_id,login_timestamp,login_id,ip) values ('".$session_id."',now(),'','".$user_id."','".$_SERVER['REMOTE_ADDR']."')";
	  
	  $db->query($sql);
	  if($db->Errno==0){
		  return (true);
	  }
	  else {
			return (false);
	  }
}


function insert_rule($R)
	{
		extract($R);
		if($rule_id=='')
		$rule_id =  s3id();
		#$rule_id = str_replace (array('.', ' '),'', microtime());
		
		
		
		$sql = "insert into s3db_rule (rule_id, project_id, subject, verb, object, notes, created_on, created_by, permission, subject_id, verb_id, object_id, validation, status) values ('".$rule_id."', '".$rule_info['project_id']."', '".$rule_info['subject']."','".$rule_info['verb']."','".$rule_info['object']."','".$rule_info['notes']."', now(),'".$user_id."', '_".$rule_info['project_id']."_', '".$rule_info['subject_id']."', '".$rule_info['verb_id']."', '".$rule_info['object_id']."', '".$rule_info['validation']."', 'A')";

		#echo $sql;
		$db->query($sql, __LINE__, __FILE__);
		$dbdata = get_object_vars($db);
		$rule_info['rule_id'] = find_latest_UID('rule', $db);
		$action = 'create';
		$inputs = array('newsubject'=>$rule_info['subject'], 'newverb'=>$rule_info['verb'], 'newobject'=>$rule_info['object'], 'newnotes'=>$rule_info['notes']);
		$oldvalues = array('oldsubject'=>'', 'oldsverb'=>'', 'oldsobject'=>'');
		$log = compact('action', 'inputs','project_id', 'oldvalues', 'rule_info', 'user_id', 'db');
		
		#echo $sql;
		#exit;
		if($dbdata['Errno']==0) 
		{
		$logged = insert_rule_log($log);
		return TRUE;
		}
		else #try again, make compatible with older versions
		{
			$sql = "insert into s3db_rule (project_id, subject, verb, object, notes, created_on, created_by, permission, subject_id, object_id, status) values ('".$rule_info['project_id']."', '".$rule_info['subject']."','".$rule_info['verb']."','".$rule_info['object']."','".$rule_info['notes']."', now(),'".$user_id."', '".$rule_info['project_id']."_', '".$rule_info['subject_id']."', '".$rule_info['object_id']."', 'A')";

			$db->query($sql, __LINE__, __FILE__);
		
			$dbdata = get_object_vars($db);
			
			
			if($dbdata['Errno']==0) 
			{
			$logged = insert_rule_log($log);
			
			return TRUE;
			}
			else
			return FALSE;
			
		}

	}

	function insert_rule_log($R)
	{		
			extract($R);
#echo '<pre>';print_r($R);exit;
			$old_table_fields = array('subject', 'verb', 'object', 'subject_id', 'verb_id', 'object_id', 'notes');
			if($oldvalues!='')
			{
				foreach ($old_table_fields as $oldfield) {
				if($oldvalues[$oldfield]=='')
						$oldvalues[$oldfield] = $rule_info[$oldfield];
			}
			$rule_info['oldvalues']=$oldvalues;
			}
			$new_table_fields = array('subject', 'verb', 'object', 'subject_id', 'verb_id', 'object_id', 'notes');
			
			foreach ($new_table_fields as $newfield) {
				if($action!='delete'){
				if($inputs[$newfield]!='')
						$rule_info[$newfield] = $inputs[$newfield];
				}
				else {
					$rule_info[$newfield]='';
				}
			}
			
			if($rule_info['action_by']=='') $rule_info['action_by']=$user_id;
			
			#$sql ="insert into s3db_rule_change_log (project_id, rule_id, action, action_by, action_timestamp, new_subject, new_verb, new_object, new_notes, old_subject, old_verb, old_object, old_notes) values ('".$rule_info['project_id']."', '".$rule_info['rule_id']."', '".$action."', '".$rule_info['action_by']."', now(), '".$rule_info['subject']."', '".$rule_info['verb']."', '".$rule_info['object']."', '".$rule_info['notes']."', '".$rule_info['oldsubject']."',  '".$rule_info['oldverb']."',  '".$rule_info['oldobject']."', '".$rule_info['oldnotes']."')";
			$sql ="insert into s3db_rule_change_log (project_id, rule_id, action, action_by, action_timestamp, new_subject, new_verb, new_object, new_subject_id, new_verb_id, new_object_id, new_notes, old_subject, old_verb, old_object, old_subject_id, old_verb_id, old_object_id, old_notes) values ('".$rule_info['project_id']."', '".$rule_info['rule_id']."', '".$action."', '".$rule_info['action_by']."', now(), '".$rule_info['subject']."', '".$rule_info['verb']."', '".$rule_info['object']."', '".$rule_info['subject_id']."', '".$rule_info['verb_id']."', '".$rule_info['object_id']."', '".$rule_info['notes']."', '".$rule_info['oldvalues']['subject']."',  '".$rule_info['oldvalues']['verb']."',  '".$rule_info['oldvalues']['object']."', '".$rule_info['oldvalues']['subject_id']."',  '".$rule_info['oldvalues']['verb_id']."',  '".$rule_info['oldvalues']['object_id']."','".$rule_info['oldnotes']."')";
		#echo $sql;exit;
			
			$db->query($sql, __LINE__, __FILE__);
			
			
			$dbdata = get_object_vars($db);
			if($dbdata['Errno']==0) return TRUE;
			else return FALSE;
			
	
	}

	function insert_address($U)
		{
		extract($U);
		#if($inputs['addr1']!=''||$inputs['addr2']!=''||$inputs['city']!=''||$inputs['state']!=''||$inputs['postal_code']!=''||$inputs['country']!='')
		#{
		
			$sql = "insert into s3db_addr(addr_id, addr1, addr2, city, state, postal_code, country) values('".s3id()."','".$inputs['addr1']."','".$inputs['addr2']."','".$inputs['city']."','".$inputs['state']."','".$inputs['postal_code']."','".$inputs['country']."')";
			$db->query($sql, __LINE__, __FILE__);	
			
			#$addr_id = $db->get_last_insert_id('s3db_addr', 'addr_id');	

			#Find the latest adress UID
			$sql = "SELECT addr_id FROM s3db_addr WHERE addr_id = (SELECT max(addr_id) FROM s3db_addr)";
			
			$db-> query($sql, __LINE__, __FILE__);	
			if($db->next_record())
					{
						$addr_id = $db->f('addr_id');
					}
			
			
			if($addr_id != '')
			{
				return $addr_id;
			}
			else {
				return (-10);
			}
		#}
		
		#else {
				$sql = "insert into s3db_addr (addr_id) values('1')";
				$db->query($sql, __LINE__, __FILE__);	

				return (-10);
			#}
		}

		

	function insert_file_for_transfer($filedata, $user_id, $db)

	{
	
	$sql = "insert into s3db_file_transfer (file_id, filename, filesize, status, expires, filekey, created_by) values ('".$filedata['file_id']."', '".$filedata['filename']."', '".$filedata['filesize']."', '".$filedata['status']."', '".$filedata['expires']."', '".$filedata['filekey']."', '".$user_id."')";
	#echo $sql;
	
	$db->query($sql, __LINE__, __FILE__);
	
	$dbdata = get_object_vars($db);
	if($dbdata['Errno']==0) return TRUE;
	else return FALSE;

	}

	
	function insert_permission($Z)
	{#function insert +_pemission makes an entry on s3db_permissions, that specifies what permission a certain resource has on another resource
	#Syntax: insert_permission(compact('permission_info', 'db', 'user_id'))
	extract($Z);
	$uid = $permission_info['uid'];
	
	#some ids can be swapped, that is class is swapped with rule "hasUID" and instance is swapped with statement of rule "hasUID"
	if(!is_array($info[$uid])) 
		$info[$uid] = URI($uid, $user_id, $db);

	
	$permission_info['id']=substr($permission_info['uid'], 1, strlen($permission_info['uid']));
	
	
	if(strlen($permission_info['permission_level'])=='2')
		$permission_info['permission_level'] = $permission_info['permission_level'].substr($permission_info['permission_level'], -1);
	
	$sql = "insert into s3db_permission (uid, id, shared_with, permission_level, created_by, created_on, pl_view, pl_change, pl_use, id_num, id_code, shared_with_num, shared_with_code) values ('".$permission_info['uid']."','".$permission_info['id']."', '".$permission_info['shared_with']."', '".$permission_info['permission_level']."', '".$user_id."', now(), '".substr($permission_info['permission_level'],0,1)."','".substr($permission_info['permission_level'],1,1)."','".substr($permission_info['permission_level'],0,1)."', '".$permission_info['id']."', '".strtoupper(substr($permission_info['uid'],0,1))."', '".substr($permission_info['shared_with'],1,strlen($permission_info['shared_with']))."', '".strtoupper(substr($permission_info['shared_with'],0,1))."')";
	#echo $sql.chr(10);
	$db->query($sql, __LINE__, __FILE__);
	#echo '<pre>';print_r($db);
	#exit;
	
	if($new_id!='')
		{	$sql = "insert into s3db_permission (uid, id, shared_with, permission_level, created_by, created_on) values ('".$new_id."','".substr($new_id, 1, strlen($new_id))."', '".$permission_info['shared_with']."', '".$permission_info['permission_level']."', '".$user_id."', now())";
		$db->query($sql, __LINE__, __FILE__);
		}
	
	$dbdata = get_object_vars($db);
	##Now Change the queryMethod to b such that the permissions file is updated whenever a new permission is added
	if(is_file($GLOBALS['uploads'].'/queryMethod'))
	file_put_contents($GLOBALS['uploads'].'/queryMethod', 'a');
	#echo $sql;
	#echo '<pre>';print_r($dbdata);
		if($dbdata['Errno']==0) 
		return TRUE;
		else
		return False;	
		
	}

function insert_s3db($D)
	{#insert_s3db(compact('element', 'inputs', 'user_id', 'db'));
	#this is meant to be a general function for every insert, froum user to group. It create the entry, based on information on array $info and adds an entry on permissions
	#There will be 2 special cases: creating a class also creates the rule "has UID" and creating an instance also creates the statament where reosurce_id is instance_id and rule is "hasUID"
	extract($D);
	$table = $GLOBALS['s3tables'][$element];
	#echo '<pre>';print_r($D);
	$cols_for_entry = $GLOBALS['dbstruct'][$element];
	$letter = strtoupper(substr($element,0,1));
	
	

	#some special restrictions apply
	switch ($letter) {
		case 'U':
		{	$cols_for_entry = array_diff($cols_for_entry, array('addr1', 'addr2', 'city', 'state', 'postal_code', 'country'));
			array_push($cols_for_entry, 'account_pwd');

			$inputs['account_addr_id']=insert_address($D);
		}
		break;
		case 'G':
		{
		$cols_for_entry  = array_merge($cols_for_entry, array('account_pwd', 'account_group'));
		$inputs['account_type'] = 'g';
		$inputs['account_group'] = $inputs['account_type'];
		$inputs['account_uname'] = $inputs['account_lid'];
		break;
		}
		case 'C':
		{
		$inputs['iid'] = '0';
		break;
		}
		case 'I':
		{
		$inputs['iid'] = '1';
		$inputs['resource_class_id']=($inputs['resource_class_id']=='')?$inputs['class_id']:$inputs['resource_class_id'];
		$inputs['resource_id']=($inputs['resource_id']!='')?$inputs['resource_id']:$inputs['instance_id'];
		break;
		}
		case 'F':
		{
		$element='statement';
		$cols_for_entry = $GLOBALS['dbstruct']['statements'];
		$table = $GLOBALS['s3tables']['statements'];
		$inputs['statement_id']= s3id();
		
		#now need to move file from tmp folder into final folder
		
		
		$moved = tmpfile2folder(array('inputs'=>$inputs, 'db'=>$db, 'user_id'=>$user_id));
					
			if(!$moved[0])#something went wrong, delete the statement.
				{
				return ($moved[1]);				
				}
				else {
					$inputs=$moved[1];
				}
				
		}
		break;
		

		
	}
	
	
	#remove ''_id from cols for entry if that field is empty;
	
	if($inputs[$GLOBALS['s3ids'][$element]]=='')
		{
		#never levae the primary key input empty
		#$inputs[$GLOBALS['s3ids'][$element]] = find_latest_UID($table, $db)+1;
		$inputs[$GLOBALS['s3ids'][$element]] = s3id();
		
		}
	
	
		$sql = buildInsertString($cols_for_entry, $inputs, $table);
	
	
		#echo $sql;exit;
		$db->query($sql, __LINE__, __FILE__);
		if($db->Errno==1) #This is a duplicate key. No problem, let's try again 
		{
		$inputs[$GLOBALS['s3ids'][$element]] = s3id();
		$sql = buildInsertString($cols_for_entry, $inputs, $table);
		$db->query($sql, __LINE__, __FILE__);
		}
		
		$dbdata = get_object_vars($db);
		#$dbdata['Errno']='0';
		#echo '<pre>';print_r($dbdata);exit;
				
		if($dbdata['Errno']!='0') 
			{
			if($table=='account'){
				$sql = "update s3db_".$table." set account_status = 'A' where account_id = '".$inputs['account_id']."'";
				$db->query($sql, __LINE__, __FILE__);
				$dbdata = get_object_vars($db);
			}
			if($dbdata['Errno']!=0) {
			return array(False,$GLOBALS['error_codes']['something_went_wrong'].'<message>'.str_replace('key', $GLOBALS['COREids'][$element], $dbdata['Error']).'</message>', $GLOBALS['error_codes']['something_went_wrong'], $dbdata['Error']);
			}
			}
		else{
			#$element_id = $db->get_last_insert_id($table, $GLOBALS['s3ids'][$element]);
			#$element_id = find_latest_UID($table, $db);
			
			$element_id = $inputs[$GLOBALS['s3ids'][$element]];
			$info[$letter.$element_id]=$inputs;

			#special restrictions apply after create:
			switch ($letter) {
				case 'P':
				{
					$project_id = $element_id;
					#if project_id is remote, need to change it's name a bit because / and # are not allowed in project_name;
					#$project_id = urlencode($project_id);
					
					
					#create the folder on the extras for the files of this project
					$folder_code_name = random_string(15).'.project'.urlencode($project_id);
					$maindir = $GLOBALS['s3db_info']['server']['db']['uploads_folder'].$GLOBALS['s3db_info']['server']['db']['uploads_file'];

					$destinationfolder =  $maindir.'/'.$folder_code_name;
					
					
					  #create the folder for the project
					if(mkdir($destinationfolder, 0777))
					{
						$indexfile = $destinationfolder.'/index.php';
						if (file_exists($destinationfolder))
							{
							file_put_contents ($indexfile , 'This folder cannot be accessed');
							chmod($indexfile, 0777);
							}
					
					$sql = "update s3db_project set project_folder = '".$folder_code_name."' where project_id = '".$project_id."'";
					$db->query($sql, __LINE__, __FILE__);
					}
					else {
						echo "Could not create directory for this project. You might not be able to upload files to this project.";
					}
							
				}
				break;
				case 'I':
					{
						
						$class_id = $inputs['resource_class_id'];
						$statement_info = $inputs;
						
						$statement_info['rule_id'] = fastRuleID4class(compact('class_id', 'db', 'user_id'));
						$statement_info['value'] = $element_id;
						$statement_info['resource_id'] = $element_id;
						
						#$stat_inserted = insert_s3db(array('element'=>'statement', 'inputs'=>$statement_info, 'db'=>$db, 'user_id'=>$user_id));
						#echo '<pre>';print_r($statement_info);exit;
						$stat_inserted = insert_statement(compact('statement_info', 'db', 'user_id'));
						$action='create';
						insert_statement_log(compact('oldvalues', 'inputs', 'action', 'statement_info', 'user_id', 'db'));
						#echo '<pre>';print_r($stat_inserted);
						if($stat_inserted[0])
						{ereg('<statement_id>([0-9]+)</statement_id>', $stat_inserted[1], $s3qlout);
						$statement_info['statement_id'] =  $stat_inserted[1];}
						
						$info['S'.$statement_info['statement_id']]=$statement_info;
						
						
						

					}
				break;
				case 'C':
				{
					$rule_info = $inputs;
					$rule_info['subject']=$inputs['entity'];
					$rule_info['subject_id']=$element_id;
					$rule_info['verb_id']='0';
					$rule_info['verb']='has UID';
					$rule_info['object']='UID';
					#echo '<pre>';print_r($inputs);
					#echo '<pre>';print_r($rule_info);exit;
					$rule_inserted = insert_rule(compact('rule_info', 'db', 'user_id'));
					
				}
				break;
				case 'R':
				{
					$rule_info = $inputs;
					$rule_info['rule_id']=$element_id;
					#echo '<pre>';print_r($rule_info);exit;
					$action='create';
					$rule_inserted = insert_rule_log(compact('rule_info', 'action', 'db', 'user_id'));
					
				}
				break;
				case 'S':
				{
				$statement_info=$inputs;
				$action='create';
				insert_statement_log(compact('oldvalues', 'action', 'statement_info', 'user_id', 'db'));
				}
				case 'F':
				{
				$statement_info=$inputs;
				$action='create';
				insert_statement_log(compact('oldvalues', 'action', 'statement_info', 'user_id', 'db'));
				}
				
				
			}
			#now add an entry that specifies user "creator' with permission level on 222 this entry (because someone has to have it)
			#some resources need to be mirrored, or swapped:
			
			if(ereg('^(U|G)$', $letter))
				{

				#owner of groups is automatically created within it with PL 222
				if(ereg('^G$', $letter))
					{$permission_info = array('uid'=>'U'.$user_id, 'shared_with'=>strtoupper(substr($element, 0,1)).$element_id,
					'permission_level'=>'222');
					#echo '<pre>';print_r($permission_info);
					insert_permission(compact('permission_info', 'db', 'user_id', 'info'));
					
					}
				elseif(ereg('^U$', $letter)){
					
					##also, for each user insertions, create an item_id for this user in the userManagement project. This will only create it if it does not yet exist
					include_once(S3DB_SERVER_ROOT.'/s3dbcore/authentication.php');
					$user_proj=create_authentication_proj($db,$user_id);
				   
					#now, create an item in the userManagement project for this user
					$user2add = $element_id;
					
					$c=compact('user2add','user_proj', 'user_id','db');
					$user_proj=insert_authentication_tuple($c);
						if($inputs['permission_level']!=""){ ##creator has specified that his own permissions can propagate
							$permission_info = array('uid'=>'U'.$user_id, 'shared_with'=>'U'.$user2add, 'permission_level'=>$inputs['permission_level']);
							insert_permission(compact('permission_info', 'db', 'user_id', 'info'));
						}
				}

				#and then insert them i deployment
				$permission_info = array('uid'=>'D'.$GLOBALS['Did'], 'shared_with'=>strtoupper(substr($element, 0,1)).$element_id);
				$permission_info['permission_level']=($inputs['permission_level']!='')?$inputs['permission_level']:'200';
				
				
				
				
				
				}
			else {
				
			if(ereg('^P$', $letter)) {
			#project has a special treatment, creators of project get to have permission level 222 on it. 
			
			$permission_info['shared_with'] = 'U'.$user_id;
			$permission_info['shared_with'] = 'U'.$user_id;
			$permission_info['uid'] = $letter.$element_id;
			$permission_info['permission_level']='YYY';##This assures that it will migrate to child resources
			insert_permission(compact('permission_info', 'db', 'user_id', 'info'));
			
			}
			elseif(ereg('^R$', $letter)){
			#Rule require permission to be inserted also for subject_id, verb_id and, if exists, object-id
			##For SUBJECT
			$permission_info = array('uid'=>'R'.$rule_info['rule_id'], 'shared_with'=>'C'.$rule_info['subject_id'], 'permission_level'=>'222', 'info'=>$info);
			#insert_permission(compact('permission_info', 'db', 'user_id', 'info'));
			
			##For VERB
			$permission_info = array('uid'=>'R'.$rule_info['rule_id'], 'shared_with'=>'I'.$rule_info['verb_id'], 'permission_level'=>'222', 'info'=>$info);
			#insert_permission(compact('permission_info', 'db', 'user_id', 'info'));
			
			#FOR OBJECT
			if($rule_info['object_id']){
			$permission_info = array('uid'=>'R'.$rule_info['rule_id'], 'shared_with'=>'C'.$rule_info['object_id'], 'permission_level'=>'222', 'info'=>$info);
			#insert_permission(compact('permission_info', 'db', 'user_id', 'info'));
			}
			
			
			
			$permission_info['shared_with'] = 'P'.$inputs['project_id'];
			}
			
			elseif(ereg('^C$', $letter)){
			$permission_info['shared_with'] = 'P'.$inputs['project_id'];
					
			}
			elseif(ereg('^I$',  $letter)){
			#insert for statement too
			$permission_info = array('uid'=>'S'.$statement_info['statement_id'], 'shared_with'=>'R'.$statement_info['rule_id'], 'permission_level'=>'222', 'info'=>$info);
			
			#insert_permission(compact('permission_info', 'db', 'user_id', 'info'));

			#and then for instance
			$permission_info['shared_with'] = 'C'.$inputs['resource_class_id'];
			}
			elseif(ereg('^S|F$', $letter)){
			
			if($letter=='F')
			{$element_id = $statement_info['statement_id'];
			$element = 'file';
			$letter = 'S';
			
			}
			$permission_info = array('uid'=>$letter.$statement_info['statement_id'], 'shared_with'=>'I'.$statement_info['resource_id'], 'permission_level'=>'222', 'info'=>$info);
			
			
			#insert_permission(compact('permission_info', 'db', 'user_id', 'info'));
			
			##If there is an object_id, insert one for that too
			if($statement_info['object_id']){
			$permission_info = array('uid'=>$letter.$statement_info['statement_id'], 'shared_with'=>'I'.$statement_info['object_id'], 'permission_level'=>'222', 'info'=>$info);
			
			#insert_permission(compact('permission_info', 'db', 'user_id', 'info'));
			}
			
			#And add one for the rule
			$permission_info['shared_with'] = 'R'.$inputs['rule_id'];
			
			}
			
			

			
			#and not these are global
			$permission_info['permission_level']=($inputs['permission_level']!='')?$inputs['permission_level']:'222';
			$permission_info['uid'] = $letter.$element_id;
			
			$info[$permission_info['uid']] = URI($permission_info['uid'], $user_id, $db);
			
			
			}
			
			#echo '<pre>';print_r($permission_info);
			#insert_permission(compact('permission_info', 'db', 'user_id', 'info'));
		
			

			return array(TRUE, $GLOBALS['error_codes']['success']."; ".$element.'_id'.': <'.$element.'_id'.'>'.$element_id.'</'.$element.'_id'.'>'.'<a href =" '.$query['url'].'?key='.$D['key'].'&query=<S3QL><select>*</select><from>'.$GLOBALS['plurals'][$element].'</from><where><'.$element.'_id>'.$element_id.'</'.$element.'_id></where></S3QL>">View '.$element.'</a>', $element, $element.'_id'=>$element_id, $GLOBALS['messages']['success'], strtoupper($element).' inserted');
			}
		
		
}

	function buildInsertString($cols_for_entry, $inputs, $table)
	{
	foreach ($cols_for_entry as $col) {
	
		if($col=='account_pwd')
			$inputs[$col] = md5($inputs[$col]);

		$colnames .= $col;
		$values .= ($inputs[$col]!='')?"'".$inputs[$col]."'":(($col=='created_by')?"'".$user_id."'":(($col=='created_on')?"now()":((ereg("_status$", $col)?"'A'":"''"))));
		if($col!=end($cols_for_entry))
			{$colnames .= ", ";
			$values .= ", ";
			}
		
	}

	
		$sql = "insert into s3db_".$table." (".$colnames.") values (".$values.")";
		return ($sql);
	}
?>