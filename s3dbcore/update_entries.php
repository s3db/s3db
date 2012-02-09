<?php

function update_statement($S)
	{
		extract($S);
		if(is_array($editing_statement))
		extract($editing_statement);
		elseif(is_array($statement_info))
		extract($statement_info);

		if($user_id!='')
		$modified_by = $user_id;

		

	if($statement_info['file_name']=='')
	$sql ="update s3db_statement set value='".addslashes($value)."', notes='".addslashes($notes)."', modified_by='".$modified_by."', modified_on=now() where statement_id='".$statement_id."'";
	else
	$sql ="update s3db_statement set notes='".addslashes($notes)."', modified_by='".$modified_by."', modified_on=now() where statement_id='".$statement_id."'";	
	
	
		
		#echo $sql;exit;
		$db->query($sql, __LINE__, __FILE__);
#echo $sql;
		
		$dbdata = get_object_vars($db);
		if($dbdata['Errno']==0) 
			
		{
		
		$L = array('statement_info'=>array('statement_id'=>$statement_id, 'rule_id'=>$rule_id, 'resource_id'=>$resource_id, 'project_id'=>$project_id, 'value'=>$value, 'notes'=>$notes, 'action'=>'edit', 'modified_by'=>$modified_by, 'created_on'=>$created_on, 'created_by'=>$created_by), 'inputs'=>$inputs, 'oldvalues'=>$oldvalues, 'db'=>$db, 'user_id'=>$user_id);

		#echo '<pre>';print_r($L);
		$logged = insert_statement_log($L);
		return TRUE;
		
		}
		else
			return FALSE;
	}


function update_resource_instance($R)
	{
		
		extract($R);
		#echo '<pre>';print_r($R);exit;
		if(is_array($editing_resource))
		extract($editing_resource);
		$modified_by = $user_id;
		
		#$sql ="update s3db_resource set notes='".$notes."', entity='".$entity."', modified_by='".$modified_by."', modified_on=now(), resource_class_id='".$resource_class_id."' where resource_id='".$resource_id."'";
		$sql ="update s3db_resource set notes='".addslashes($inputs['notes'])."', modified_by='".$modified_by."', modified_on=now() where resource_id='".$info['resource_id']."'";
		
		#$echo $sql;exit;
		$db->query($sql, __LINE__, __FILE__);
		$dbdata = get_object_vars($db);
		$old_rule_id = get_rule_id_by_entity_id($info['class_id'], $project_id, $db);
		
		$instance_info = $info;
		$Z= compact('instance_info','user_id', 'db');
			
		#$L = array('statement_id'=>fastStatementId($Z), 'old_rule_id'=>$old_rule_id, 'old_resource_id'=>$info['resource_id'], 'old_project_id'=>$info['project_id'], 'old_value'=>$info['entity'], 'old_notes'=>$oldvalues['notes'], 'action'=>'modify', 'modified_by'=>$modified_by, 'created_on'=>$info['created_on'], 'created_by'=>$info['created_by'], 'db'=>$db);

		#echo '<pre>';print_r($L);exit;
		if($dbdata['Errno']==0) 
			{
			#echo '<pre>';print_r($olvalues);exit;
			
			$statement_info = $info;
			$statement_info['statement_id']=fastStatementId($Z);
			$statement_info['rule_id'] = get_rule_id_by_entity_id($info['class_id'], $project_id, $db);
			$statement_info['value']=$info['resource_id'];
			if($input['notes']!='')
				$statement_info['notes']=$info['notes'];
			
			
			$action = 'edit';
			if($inputs['notes']!=$oldvalues['notes'])
			$logged = insert_statement_log(compact('oldvalues', 'inputs', 'action', 'statement_info', 'user_id', 'db'));

			#nor for the rules. Which rule use this item as verb_id
			$sql = "select * from s3db_rule where verb_id = '".$info['resource_id']."'";
			#echo $sql;
			$db->query($sql, __LINE__, __FILE__);

			while ($db->next_record()) {
				

				$rules[] = Array('rule_id'=>$db->f('rule_id'),
					'project_id'=>$db->f('project_id'),
					'subject'=>$db->f('subject'),	
					'verb'=>$db->f('verb'),	
					'object'=>$db->f('object'),
					'subject_id'=>$db->f('subject_id'),	
					'verb_id'=>$db->f('verb_id'),	
					'object_id'=>$db->f('object_id'),	
					'notes'=>$db->f('notes'),	
					'created_on'=>substr($db->f('created_on'), 0, 19),	
					'created_by'=>$db->f('created_by'),	
					'modified_on'=>$db->f('modified_on'),	
					'modified_by'=>$db->f('modified_by'),
					'permission'=>$db->f('permission'));
				
			}
				
				$inputs = array('verb'=>$inputs['notes']);
					
				if(is_array($rules))
				foreach ($rules as $rule_info) {
					$oldvalues['verb'] = $rule_info['verb'];
				
					$sql = "update s3db_rule set verb = '".$inputs['verb']."' where rule_id = '".$rule_info['rule_id']."'";
				
				#echo $sql.'<BR>';
					$db->query($sql, __LINE__, __FILE__);
				

				$action = 'edit';
				insert_rule_log(compact('oldvalues', 'inputs', 'rule_info', 'user_id', 'db', 'action'));
				}
				#echo $sql.'<BR>';
				

			

			##This piece of code is meant to enable the regeneration of the list of resource instances in query result
			
			$queryresult = $_SESSION['query_result'];
		
			if(is_array($queryresult)) 
				{
					foreach($queryresult as $i=>$value)
					{
						if($queryresult[$i]['resource_id'] == $editing_resource['resource_id'])
							$queryresult[$i]['notes'] = $editing_resource['notes'];
					}


				}
				$_SESSION['query_result'] = $queryresult;	
			return True;
				}
			else  #try again, this time without resource_class_id relation
				{
						return False;
				}
	}



function update_resource($R)
{
		extract($R);
		if(is_array($resource_info)) extract($resource_info);
		if(is_array($inputs)) extract($inputs);
		if(is_array($oldvalues)) extract($oldvalues);
		
		#echo '<pre>';print_r($resource_info);exit;
		if($modified_by=='') $modified_by = $user_id;

		#Before modifying anything, find the old rules where entity is involved
		$newentity = ($inputs['entity']!='')?$inputs['entity']:$resource_info['entity'];
		$newnotes = $inputs['notes']?$inputs['notes']:$resource_info['notes'];
		

		$rules_subject_involved = listS3db(array('table'=>'rules', 'user_id'=>$user_id, 'db'=>$db, 'cols'=>array('rule_id','subject', 'verb', 'object', 'notes','project_id'),'project_id'=>$rule_info['project_id'], 'subject'=>$oldvalues['oldentity']));

		$rules_object_involved = listS3db(array('table'=>'rules', 'user_id'=>$user_id, 'db'=>$db, 'cols'=>array('rule_id','subject', 'verb', 'object', 'notes','project_id'),'project_id'=>$rule_info['project_id'], 'object'=>$resource_info['entity']));
		
		

		if(!is_array($rules_subject_involved)) $rules_subject_involved = array();
		if(!is_array($rules_object_involved)) $rules_object_involved = array();
		$affected_rules = array_merge($rules_subject_involved, $rules_object_involved);


		#Update the class of resource entry
		$sql = "update s3db_resource set entity='".$newentity."', notes='".$newnotes."', modified_on = 'now()', modified_by = '".$user_id."' where iid='0' and resource_id='".$resource_info['resource_id']."' and project_id='".$resource_info['project_id']."'";
		
		#echo $sql;exit;

		$db->query($sql, __LINE__, __FILE__);
		
		$dbdata = get_object_vars($db);
		if($dbdata['Errno']==0)
			{
			#Update the instances, project_id that owns the declaration of resource should not be involved in the query
			$sql = "update s3db_resource set entity='".$newentity."' where iid='1' and resource_class_id='".$resource_info['resource_id']."'";

			#echo $sql;exit;
			$db->query($sql, __LINE__, __FILE__);
			
			$dbdata = get_object_vars($db);
			if($dbdata['Errno']!=0)
			{
			$sql = "update s3db_resource set entity='".$newentity."' where iid='1' and project_id = '".$resource_info['project_id']."'";
			$db->query($sql, __LINE__, __FILE__);
			#echo $sql;
			}
			$dbdata = get_object_vars($db);
			if($dbdata['Errno']==0)
			{
			#Update rules table, object field, this only updates those rules that were created by the owner of the resource.
			$sql = "update s3db_rule set object='".$newentity."' where object_id='".$resource_info['resource_id']."'";
			#$sql = "update s3db_rule set object='".$newentity."' where object='".$rule_info['subject']."' and project_id='".$resource_info['project_id']."'";
			
			#echo $sql;
			$db->query($sql, __LINE__, __FILE__);

			$sql = "update s3db_rule set subject='".$newentity."' where subject_id='".$resource_info['resource_id']."'";
			#$sql = "update s3db_rule set subject='".$newentity."' where subject='".$rule_info['subject']."' and project_id='".$resource_info['project_id']."'";
			#echo $sql;
			$db->query($sql, __LINE__, __FILE__);

			$sql = "update s3db_rule set notes='".$newnotes."' where subject_id='".$resource_info['resource_id']."' and verb='has UID' and object='UID'";
			#echo $sql;
			
			$db->query($sql, __LINE__, __FILE__);
			
			#Update rule_log
			$dbdata = get_object_vars($db);
			if($dbdata['Errno']==0)
			{
			#Put an entry for every changed rule on the rule log
			#Create a new rule_info, with the old info, for inserting the rule log
				

				for($i=0;$i<count($affected_rules);$i++)
				{
				
				$rule_info = $affected_rules[$i];
				
				#echo 'ola<pre>';print_r($rule_info);
				
				$R['rule_info'] = $affected_rules[$i];
				$R['action'] = 'edit';
				$R['inputs'] = array('newsubject'=>$newentity, 'newverb'=>$rule_info['verb'],'newobject'=>$rule_info['object'], 'newnotes'=>$rule_info['notes']);
				$R['oldvalues'] = array('oldsubject'=>$rule_info['subject'], 'oldverb'=>$rule_info['verb'],'oldobject'=>$rule_info['object']);
				$loginserted = insert_rule_log($R);
				}
			
			
			return TRUE;
			}
			}
			else return FALSE;

			}
			else return FALSE;
}

function update_rule($R)
	{
		extract($R);
		$rule_info = (is_array($rule_info))?$rule_info:$info;
		#$sql = "update s3db_rule set subject='".$rule_info['subject']."', verb='".$rule_info['verb']."', object='".$rule_info['object']."', notes='".$rule_info['notes']."', modified_on=now(), modified_by='".$user_id."' where rule_id ='".$rule_info['rule_id']."'";

		$sql = "update s3db_rule set subject='".$rule_info['subject']."', verb='".$rule_info['verb']."', object='".$rule_info['object']."', notes='".$rule_info['notes']."',  validation='".$rule_info['validation']."', permission='".$rule_info['permission']."', modified_on=now(), modified_by='".$user_id."', subject_id ='".$rule_info['subject_id']."' , verb_id = '".$rule_info['verb_id']."', object_id ='".$rule_info['object_id']."' where rule_id ='".$rule_info['rule_id']."'";
		#echo $sql;exit;

		$db->query($sql, __LINE__, __FILE__);
		
		$dbdata = get_object_vars($db);
		
			if($dbdata['Errno']==0)
			{
			if($action=='') $action = 'edit';
			$log = compact('rule_info', 'oldvalues', 'user_id', 'inputs', 'db', 'action');
			
			$logged = insert_rule_log($log);
			
			return True;
			}
			else return False;
		
	}	


function update_user($U)
	{#update_user changes the entry on the table. 
	#INPUTS: $U is an array with fields user_info, containing the information to be updated and db
		extract($U);
		
		if($user_info['addr1']!=''||$user_info['addr2']!=''||$user_info['city']!=''||$user_info['state']!=''||$user_info['postal_code']!=''||$user_info['country']!='')
		{
			if($user_info['account_addr_id'] =='' || $user_info['account_addr_id'] =='-10')
			{
				$inputs = $user_info;
				$user_info['account_addr_id'] = insert_address(compact('inputs', 'db'));
			}
			else
			{
				$sql = "update s3db_addr set addr1='".$user_info['addr1']."', addr2='".$user_info['addr2']."', city='".$user_info['city']."', state='".$user_info['state']."', postal_code='".$user_info['postal_code']."', country='".$user_info['country']."' where addr_id='".$user_info['account_addr_id']."'";
				#echo $sql;exit;
				$db->query($sql, __LINE__, __FILE__);	
			}
		}
				
					
				$sql = "update s3db_account set account_lid='".$user_info['account_lid']."', account_pwd='".$user_info['account_pwd']."', account_uname='".$user_info['account_uname']."', account_email='".$user_info['account_email']."', account_phone='".$user_info['account_phone']."', account_addr_id='".$user_info['account_addr_id']."', account_group='".$user_info['account_group']."', account_status='".$user_info['account_status']."', account_type='".$user_info['account_type']."', modified_on=now(), modified_by='".$user_id."' where account_id='".$user_info['account_id']."'";
				
				
		
		#echo '<pre>';print_r($user_info);
		#$echo $sql;
		#exit;
		
		
		$db->query($sql, __LINE__, __FILE__);
		$dbvars = get_object_vars($db);
		
		if ($dbvars['Errno']=='0') {
			
			return True;
		}
		else {
			return $dbvars['Errno'];
		}
		
	}




	function update_project($P)
	{
		extract($P);
		
		$sql = "update s3db_project set project_name='".$project_info['project_name']."', project_description='".$project_info['project_description']."', project_owner = '".$project_info['project_owner']."', modified_on=now(), modified_by='".$user_id."' where project_id='".$project_info['project_id']."'";
		#echo $sql;exit;
		$db->query($sql, __LINE__, __FILE__);	
		
		$dbdata = get_object_vars($db);
		

		if($dbdata['Errno']==0) 
		return TRUE;
		else
		return False;	
	}

	
	function update_group($G)
	{#updates account table where group_id specified. $G needs inputs, group_id, user_id and $db
		extract($G);
	#change the line in account table
	
	$sql = "update s3db_account set account_lid='".$inputs['account_lid']."', account_uname = '".$inputs['account_lid']."', modified_on = now(), modified_by='".$user_id."' where account_id = '".$group_id."'";
	
	$db->query($sql, __LINE__, __FILE__);
		
	$dbvars = get_object_vars($db);
	if ($dbvars['Errno']==0) {
		return (True);
	}
	else {
		return (False);
	}
	}
	
	
	
	
	
	
	
function update_permission($P)
{extract($P);
	if(strlen($permission_info['permission_level'])=='2')
		$permission_info['permission_level'] = $permission_info['permission_level'].substr($permission_info['permission_level'], -1);
	$uid = $permission_info['uid'];
	#if class updated, update rule has uid as well.
	$swap = array('C'=>'rule_id', 'I'=>'statement_id');
		if(in_array(substr($uid, 0,1), array_keys($swap)))
			{$letter = substr($uid, 0,1);
				$new_id = strtoupper(substr($swap[$letter], 0,1)).$info[$uid][$swap[$letter]];
			}

	$sql = "update s3db_permission set permission_level = '".$permission_info['permission_level']."' where uid='".$permission_info['uid']."' and shared_with = '".$permission_info['shared_with']."'";
	$db->query($sql, __LINE__, __FILE__);
	
	
	#echo $sql;
	
	if($new_id!='')
	{
	$sql = "update s3db_permission set permission_level = '".$permission_info['permission_level']."' where uid='".$new_id."' and shared_with = '".$permission_info['shared_with']."'";
	#echo $sql;exit;
	$db->query($sql, __LINE__, __FILE__);
	}
	$dbvars = get_object_vars($db);
	
	##Now Change the queryMethod to b such that the permissions file is updated whenever a new permission is added
	if(is_file($GLOBALS['uploads'].'/queryMethod'))
	file_put_contents($GLOBALS['uploads'].'/queryMethod', 'a');


	if ($dbvars['Errno']==0) {
		return (True);
	}
	else {
		return (False);
	}
	
}

	function update_requests($match, $field, $new_value, $db)
{#match = compact('rule_id', 'project_id');
	$table_name= 'access_rules';
	$match_fields = array('project_id', 'rule_id');
	$query_end = " where rule_id = '".$match['rule_id']."' and project_id = '".$match['project_id']."'";

	$sql = "update s3db_".$table_name." set ".$field."='".$new_value."'".$query_end;
#echo $sql;

$db->query($sql, __LINE__, __FILE__);
				if($db->next_record())
				return True;
				else 
				return False;
	

}

function updateIt($U)
{#works with any resource, provided the table, id and fields to update were provided in the input
#some updates like classes require that other resources be updates, like rules and instances, and some require that a log be written to keep track of changes.
extract($U);
	
}
function update_deployment($D)
{
	extract($D);
	
	$tableDescription = $GLOBALS['dbstruct'][$table];
	$f=0;
	foreach ($inputs as $field=>$value) {
		$f++;
		if(in_array($field,$tableDescription, 1)){
			$set .= $field." = '".$value."'";
			if($f<count($inputs)){
				$set .= ',';
			}
		}
	}
	$sql = "update s3db_".$table." set ".$set." where ".$identifier." = '".$element_id."'";
	$db->query($sql, __LINE__, __FILE__);
	if($db->Errno==0){
		return (true);
	}
	else {
		return (false);
	}

}
?>