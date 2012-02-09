<?php

function delete_statement($S)
	{
	
		extract($S);
		if(is_array($statement_info))
		extract($statement_info);
		
		$sql = "update s3db_statement set status = 'I', modified_on = now() where statement_id='".$statement_id."'";
		#echo $sql;exit;
		$db->query($sql, __LINE__, __FILE__);
		
		$dbdata = get_object_vars($db);
		
		if($dbdata['Errno']==0) 
			
		{
		$L = array('statement_id'=>$statement_id, 'old_rule_id'=>$rule_id, 'old_resource_id'=>$resource_id, 'old_project_id'=>$project_id, 'old_value'=>$value, 'old_notes'=>$notes, 'action'=>'delete', 'modified_by'=>$user_id, 'created_on'=>$created_on, 'created_by'=>$created_by, 'db'=>$db);

		$logged = insert_statement_log($L);

		if($statement_info['file_name']!='')
			{#move the file into a directory called deleted
			if(is_dir($GLOBALS['s3db_info']['server']['db']['uploads_folder']))
				$maindir = $GLOBALS['s3db_info']['server']['db']['uploads_folder'].$GLOBALS['s3db_info']['server']['db']['uploads_file'];
				else
				$maindir = S3DB_SERVER_ROOT.'/extras/'.$GLOBALS['s3db_info']['server']['db']['uploads_file'];

			$folder_code_name = $statement_info['value'];
			$file_name = $statement_info['file_name'];
			list ($realname, $extension) = explode('.', $file_name);

			$file_code_name =  $realname.'_'.$statement_info['project_id'].'_'.$statement_info['resource_id'].'_'.$statement_info['rule_id'].'_'.$statement_info['statement_id'].'.'.$extension;

			$dir = $maindir."/".$folder_code_name;
			$file_location = $dir."/".$file_code_name;

			$deleted_file = $dir."/deleted/".$file_code_name;
			
			#echo $file_location .'<br />'.$deleted_file;
			if(!is_dir($dir.'/deleted')) mkdir($dir.'/deleted');
			
			#rename($file_location, $deleted_file);
			copy($file_location, $deleted_file);
			unlink($file_location);
			
			}
		return TRUE;
		
		}
		else return False;
		
	}


function delete_class($R)
{
	extract($R);
		
		$account_lid = find_user_loginID(array('account_id'=>$user_id, 'db'=>$db));#this is for rule_log
		
		
		$resource_class_id = $class_info['resource_id'];
		if($resource_class_id=='') return ('Please provide class_id');	
			
						
			if($instances=='')
			{
			$s3ql = compact('user_id', 'db');
			$s3ql['from'] = 'instances';
			$s3ql['where']['class_id'] = $resource_class_id;
			#$s3ql['where']['project_id']=$resource_info['project_id'];
			

			$instances = S3QLaction($s3ql);
			
			}
			


			if($rules=='')
			{
			$s3ql=compact('user_id','db');
			$s3ql['from'] = 'rules';
			$s3ql['where']['subject_id'] = $resource_class_id;
			
			#echo '<pre>';print_r($s3ql);
			$rules = S3QLaction($s3ql);

			$s3ql=compact('user_id','db');
			$s3ql['from'] = 'rules';
			$s3ql['where']['object_id'] = $resource_class_id;
			
			#echo '<pre>';print_r($s3ql);
			$rules2 = S3QLaction($s3ql);

			array_push($rules2, $rules);
			}
			
			

			
			
			#Start deleting
			if(is_array($instances))
			foreach ($instances as $i => $this_resource_instances)
			{
			$s3ql=compact('user_id','db');
			$s3ql['delete']='instance';
			$s3ql['where']['instance_id']=$instances[$i]['resource_id'];
			$s3ql['where']['confirm']='yes';

			$done = S3QLaction($s3ql);
			
			
			}

			if(is_array($rules))
			foreach($rules as $i => $this_subject_rule)
			{
			$s3ql=compact('user_id','db');
			$s3ql['delete']='rule';
			$s3ql['where']['rule_id']=$rules[$i]['rule_id'];
			$s3ql['where']['confirm']='yes';

			$done = S3QLaction($s3ql);
			}

			#Delete the resource_class_id after the instances and rules
			#$sql = "delete from s3db_resource where resource_id = '".$resource_class_id."' and iid = '0'";
			$sql = "update s3db_resource set status = 'I', modified_on = now() where resource_id = '".$resource_class_id."' and iid = '0'";
			
			
			$db->query($sql, __LINE__, __FILE__);

			$sql = "update s3db_rule set status = 'I', modified_on = now() where rule_id = '".$class_info['rule_id']."' and object = 'UID'";
			
			$db->query($sql, __LINE__, __FILE__);
		
		$dbdata = get_object_vars($db);
		if($dbdata['Errno']=='0')
			return (True);
			
}


function delete_rule($R)
	{
		
		extract($R);
		
		$account_lid = find_user_loginID(array('account_id'=>$user_id, 'db'=>$db));#this is for rule_log
		
		
		#If this is a resource rule, update the resource_class to project_id = 0 and all the instances of resource, entity remains the same
		
		#find out all the statements inserted under this rule
			
			if(!is_array($statements))
			{
			$s3ql=compact('user_id','db');
			$s3ql['select']='*';
			$s3ql['from']='statements';
			$s3ql['where']['rule_id']=$rule_info['rule_id'];
			

			$statements = S3QLaction($s3ql);
			}

			#delete the statements (and log them... but that job i keep for delete statement)
			if (is_array($statements)) {
			
			for ($i=0; $i < count($statements); $i++) {
				$s3ql=compact('user_id','db');
				$s3ql['delete']='statement';
				$s3ql['where']['statement_id']=$statements[$i]['statement_id'];
				$s3ql['where']['confirm']='yes';

				$done = S3QLaction($s3ql);
				
			}
			}
			
			#now delete the rule
			
			if ($rule_id=='') return ('Rule ID is missing');
			
			$sql = "update s3db_rule set status = 'I', modified_on = now() where rule_id='".$rule_id."'";
						
			$db->query($sql, __LINE__, __FILE__);
			
			$dbdata = get_object_vars($db);
			if($dbdata['Errno']==0) 
			{
			#update the access_rules table, other users must know the rule was deleted
			$otherProjs = ereg_replace('(^|_)'.$rule_info['project_id'].'_', '', $rule_info['permission']);
			if($otherProjs!='') #is there anything shared?
			{
			$sql = "update s3db_access_rules set status = 'deleted' where rule_id = '".$rule_info['rule_id']."'";
				$db->query($sql, __LINE__, __FILE__);
			}	
			
			$inputs = array('newsubject'=>'', 'newverb'=>'', 'newobject'=>'', 'newnotes'=>'');
			$oldvalues = array('oldsubject'=>$rule_info['subject'], 'oldverb'=>$rule_info['verb'], 'oldobject'=>$rule_info['object'], 'oldnotes'=>$rule_info['notes']);
			$action = 'delete';
					
			$log = compact('rule_info', 'oldvalues', 'inputs', 'action', 'db', 'user_id');
					
			#log the deleted rule
			$logged = insert_rule_log($log);

			
			return True;
			}
			else
			return False;
				
	
				
}

function delete_resource_instance($R)
	{
		extract($R);
		$resource_id = $R['resource_id'];
		$modified_by = $R['user_id'];
		$db = $R['db'];
		$resource_info = s3info('instance', $resource_id, $db);
		$resource_class_id = $resource_info['resource_class_id'];
		$project_id = $resource_info['project_id'];
		$old_rule_id = get_rule_id_by_entity_id($resource_class_id, $project_id, $db);

		#Find all statements on this resource_id
		if (!is_array($statements)) {
			$statements = CORElist(array('child'=>'statement', 'parent_ids'=>array('instance_id'=>$resource_id), 'user_id'=>$user_id, 'db'=>$db));
		}
		
		#Find all statements where this resource_id is object. First we have to figure out which rules point to the object where this instance might have been inserted.
		#$class_id = $resource_info['resource_class_id'];
		

		$s3ql=compact('user_id','db');
		$s3ql['select']='*';
		$s3ql['from']='statements';
		$s3ql['where']['value']=$resource_id;
		$s3ql['where']['object_id'] = $resource_class_id;

		
		#$borrowedStats = S3QLaction($s3ql); #COMING SOON

		if (is_array($statements))
			foreach ($statements as $i => $statement_info)
			{
			$s3ql=compact('user_id','db');
			$s3ql['delete']='statement';
			$s3ql['where']['statement_id']=$statement_info['statement_id'];
			$s3ql['where']['confirm'] = 'yes';
			
			$done = S3QLaction($s3ql);
			
			#$sql = "delete from s3db_statement where statement_id = '".$statement_info['statement_id']."'";
			#$db->query($sql, __LINE__, __FILE__);
			
			$S = array('statement_id' =>$statement_info['statement_id'], 
					'old_rule_id'=>$statement_info['rule_id'], 
					'old_resource_id'=>$statement_info['resource_id'], 
					'old_project_id'=>$statement_info['project_id'], 
					'old_value'=>$statement_info['value'],
					'old_notes'=>$statement_info['notes'],
					'created_by'=>$statement_info['created_by'],
					'created_on'=>$statement_info['created_on'],
					'modified_by'=>$user_id, 
					'action'=>'delete',
					'db'=>$db);
			$logged = insert_statement_log($S);
			
			}

		#Change the resources table
		#$sql = "delete from s3db_resource where resource_id = '".$resource_id."' and iid='1'";
		$sql = "update s3db_resource set status = 'I', modified_on = now() where resource_id = '".$resource_id."' and iid='1'";
		
		#echo '<pre>';print_r($statements);
		#echo $sql;exit;
		$db->query($sql, __LINE__, __FILE__);

		$dbdata = get_object_vars($db);
		
		if($dbdata['Errno']==0) 
		{
		$S = array('statement_id'=>'0',
				'old_rule_id'=>$old_rule_id, 
				'old_resource_id'=>$resource_id, 
				'old_project_id'=>$resource_info['project_id'], 
				'old_value'=>$resource_info['entity'], 
				'old_notes'=>$resource_info['notes'],
				'created_by'=>$resource_info['created_by'],
				'created_on'=>$resource_info['created_on'],
				'modified_by'=>$modified_by, 
				'action'=>'delete',
				'db'=>$db);
		$logged = insert_statement_log($S);
		
			
		#Change in the statements table
		#$sql = "delete from s3db_statement where resource_id = '".$resource_id."'";

		#$db->query($sql, __LINE__, __FILE__);

		$dbdata = get_object_vars($db);
			

			
		##This piece of code is meant to enable the regeneration of the list of resource instances in query result
			$queryresult = $_SESSION['query_result'];
		#echo '<pre>'; print_r($queryresult);
			if(is_array($queryresult)) 
			{
				function resource_id_compare($a, $b)
				{
				   if ($a == $b) 
					{
					   return 0;
				   }
				  
				}
				foreach($queryresult as $i=>$value)
				{
					if($queryresult[$i]['resource_id'] == $resource_id)
					{
						unset($queryresult[$i]);
					
					}
				
				}
				#if (is_array($deleteme)) #Remove the small array from the big array
				#	$queryresult = array_diff_uassoc($queryresult,$deleteme, "resource_id_compare");
				
				#echo '<pre>'; print_r($queryresult);


			}
			$_SESSION['query_result'] = $queryresult;	
		
		return True;
		}
		else return False;
	}

function delete_resource_nolog($deleting_resource)
	{
		$db= $_SESSION['db'];
		$sql = "delete from s3db_statement where resource_id='".$deleting_resource['resource_id']."'";
		//echo $sql;
		$db->query($sql, __LINE__, __FILE__);
		$sql = "delete from s3db_resource where resource_id='".$deleting_resource['resource_id']."'";
		//echo $sql;
		$db->query($sql, __LINE__, __FILE__);
		return True;
	}
	
function delete_group($G)
	{#$G required db and group_id. Group info would be useful for future version where old users get logged
		extract($G);
		
		#delete in account 
		#$sql = "delete from s3db_account where account_id='".$group_id."'";
		if($group_id=='1') return (False);

		$sql = "update s3db_account set account_status='I', modified_on=now(), modified_by='".$user_id."' where account_id='".$group_id."'";
		$db->query($sql, __LINE__, __FILE__);
		#echo $sql;
		#delete in account_group
		$sql = "delete from s3db_account_group where group_id='".$group_id."'";
		$db->query($sql, __LINE__, __FILE__);
		#echo $sql;
		$dbvars = get_object_vars($db);
		if ($dbvars['Errno']==0) {
			return (true);
		}
		else {
		return false;	
		}
		
	}

		

	function delete_project($P)
	{
		extract($P);
		if($db=='')	$db = $_SESSION['db'];

		if($newowner == '')
		{
			#delete all the classes. These should fall into a cascade of deleting all the instances, which will delete all the statements
			if(!is_array($classes))
			{
			$s3ql=compact('user_id','db');
			$s3ql['select']='*';
			$s3ql['from']='classes';
			$s3ql['where']['project_id']=$project_id;

			$classes = s3list($s3ql);
			}

			if(!is_array($rules))
			{
			$s3ql=compact('user_id','db');
			$s3ql['select']='*';
			$s3ql['from']='rules';
			$s3ql['where']['project_id']=$project_id;

			$rules = s3list($s3ql);
			}
			
			
			
			
			
			
			if(is_array($classes))
			{
				foreach ($classes as $key=>$class_info) {
					
					if ($class_info['project_id']==$project_id) {
					
					$s3ql=compact('user_id','db');
					$s3ql['delete']='class';
					$s3ql['where']['class_id']=$class_info['class_id'];
					$s3ql['where']['confirm']='yes';
					
					
					$done = S3QLaction($s3ql);
					
					}
					else {
						$s3ql=compact('user_id','db');
						$s3ql['delete']='rule';
						$s3ql['where']['rule_id']=$class_info['rule_id'];
						$s3ql['where']['project_id']=$project_id;
					}
				}
			
			}

			$sql = "update s3db_project set project_status = 'I', status = 'I', modified_on = now() where project_id='".$project_id."'";
			
			$db->query($sql, __LINE__, __FILE__);

			$dbdata = get_object_vars($db);
			if ($dbdata['Errno']=='0') {
			return (True);
			}
		}
		else
		{
			$sql = "update s3db_project set project_owner='".$newowner."' where project_id='".$project_id."'";
			
			$db->query($sql, __LINE__, __FILE__);
			
			
			return True;
		}
		return False;
	}

	function switch_projects_owner($oldowner, $newowner, $db)
	{
		//echo $oldowner;
		//echo $newowner;
		#$db = $_SESSION['db'];
		if($newowner == '')
		{
			
			//$sql = "delete from s3db_account where account_id='".$oldowner."'";
			$sql = "update s3db_account set account_status='I' where account_id='".$oldowner."'";
			#echo $sql;	
			$db->query($sql, __LINE__, __FILE__);
			
			$sql = "delete from s3db_account_group where account_id='".$oldowner."'";
			$db->query($sql, __LINE__, __FILE__);
			
			$sql = "update s3db_project set project_status='I' and project_owner='".find_admin($db)."' where project_owner='".$oldowner."'";
			$db->query($sql, __LINE__, __FILE__);
			
			$sql = "delete from s3db_project_acl where acl_account='".$oldowner."'";
			$db->query($sql, __LINE__, __FILE__);
			
			$sql = "delete from s3db_project_acl s3db_project where acl_project_id in (select project_id from s3db_project where project_owner='".$oldowner."')";
			$db->query($sql, __LINE__, __FILE__);
			return True;
		}
		else
		{
			$sql = "delete from s3db_account where account_id='".$oldowner."'";
			$db->query($sql, __LINE__, __FILE__);
			
			$sql = "delete from s3db_account_group where account_id='".$oldowner."'";
			$db->query($sql, __LINE__, __FILE__);
			
			$sql = "update s3db_project set project_owner='".$newowner."' where project_owner='".$oldowner."'";
			$db->query($sql, __LINE__, __FILE__);
		
			$sql = "update s3db_project_acl set acl_account='".$newowner."' where acl_account='".$oldowner."'";
			$db->query($sql, __LINE__, __FILE__);
			return True;
		}
		return False;
	}

	function delete_user($U)
	{
		extract($U);
		//echo $newowner;
		#$db = $_SESSION['db'];
		#user_to_delete CANNOT BE EMPTY - don't take any chances!
		if (is_numeric($user_to_delete)) {
			
		
		$sql = "update s3db_account set account_status='I' where account_id='".$user_to_delete."'";
			#echo $sql;	
			$db->query($sql, __LINE__, __FILE__);
		
		
		if($projects_new_owner == '')
		{
			
			//$sql = "delete from s3db_account where account_id='".$oldowner."'";
			
			
			$sql = "delete from s3db_account_group where account_id='".$user_to_delete."'";
			$db->query($sql, __LINE__, __FILE__);
			
			$sql = "update s3db_project set project_status='I' and project_owner='".find_admin($db)."' where project_owner='".$user_to_delete."'";
			$db->query($sql, __LINE__, __FILE__);
			
			$sql = "delete from s3db_project_acl where acl_account='".$user_to_delete."'";
			$db->query($sql, __LINE__, __FILE__);
			
			$sql = "delete from s3db_project_acl s3db_project where acl_project_id in (select project_id from s3db_project where project_owner='".$user_to_delete."')";
			$db->query($sql, __LINE__, __FILE__);
			return True;
		}
		else
		{
			#$sql = "delete from s3db_account where account_id='".$user_to_delete."'";
			#$db->query($sql, __LINE__, __FILE__);
			
			$sql = "delete from s3db_account_group where account_id='".$user_to_delete."'";
			$db->query($sql, __LINE__, __FILE__);
			
			$sql = "update s3db_project set project_owner='".$projects_new_owner."' where project_owner='".$user_to_delete."'";
			$db->query($sql, __LINE__, __FILE__);
		
			$sql = "update s3db_project_acl set acl_account='".$projects_new_owner."' where acl_account='".$user_to_delete."'";
			$db->query($sql, __LINE__, __FILE__);
			return True;
		}
		return False;
		}
		return False;
	}
	
	function delete_user_from_group($U)
	{
		extract($U);
		
		$sql = "delete from s3db_account_group where account_id='".$user_to_delete."' and group_id = '".$group_id."'";
		
		$db->query($sql, __LINE__, __FILE__);

		if($group_id=='1')
		{$sql = "update s3db_account set account_type = 'u' where account_id = '".$user_to_delete."'";
		$db->query($sql, __LINE__, __FILE__);
		}
		
		$dbdata = get_object_vars($db);
		if ($dbdata['Errno']==0) {
			return (True);
		}
		else {
			return (False);
		}
	}
	
	function delete_element($D)

	{
		extract($D);

		
		$sql = "delete from s3db_".$table." where ".$element."_id = '".$element_id."'".$query_end;
		$db->query($sql, __LINE__, __FILE__);

		#echo $sql;
		$dbdata = get_object_vars($db);
		if($dbdata['Errno']==0) 
		return True;
		else return False;
		

	}

function delete_expired_keys($date, $db)

{
if($date=='')
	$date= date('Y-m-d H:i:s');

$sql = "delete from s3db_access_keys where expires <= '".$date."'";
#echo $sql;
		$db->query($sql, __LINE__, __FILE__);
		if($db->next_record())
		{
			#$UID = $db->f($return);
			return  True;

		}
		else return False;


}

function delete_permission($P)
{extract($P);
#if class updated, update rule has uid as well.
	$uid = $permission_info['uid'];
	$swap = array('C'=>'rule_id', 'I'=>'statement_id');
		if(in_array(substr($uid, 0,1), array_keys($swap)))
			{$letter = substr($uid, 0,1);
				$new_id = strtoupper(substr($swap[$letter], 0,1)).$info[$uid][$swap[$letter]];
			}

$sql = "delete from s3db_permission where uid='".$permission_info['uid']."' and shared_with = '".$permission_info['shared_with']."'";
#echo $sql;exit;
$db->query($sql, __LINE__, __FILE__);

if($new_id!='')
	{
	$sql = "delete from s3db_permission where uid='".$new_id."' and shared_with = '".$permission_info['shared_with']."'";
	
	$db->query($sql, __LINE__, __FILE__);
	}

##Now Change the queryMethod to b such that the permissions file is updated whenever a new permission is added
if(is_file($GLOBALS['uploads'].'/queryMethod'))
file_put_contents($GLOBALS['uploads'].'/queryMethod', 'a');

$dbdata = get_object_vars($db);
if($dbdata['Errno']==0) 
		return True;
		else return False;
}

function deleteCoreResource($uid, $user_id, $db)
{#function deleteCoreResource performs very simple deleteResource queries - given a uid, it retrieves a table and the correct numeric id to delete.

$s3codes = $GLOBALS['s3codes'];
$s3tables = $GLOBALS['s3tables'];
$s3ids = $GLOBALS['s3ids'];
$messages = $GLOBALS['message'];

	$uid_info = uid($uid);
	$letter = letter($uid);
	
	$table = $s3codes[$letter];
	
	if($table=='') 
		return (substr($uid_info['uid'], 0, strlen($uid_info['uid'])).' is not a valid resource identifyer');
	

	#map resource to the right table
	$table_id = $s3ids[$table];
	$table = $s3tables[$table];
	
	
	#numeric id
	
	$num_id = ltrim(str_replace($GLOBALS['Did'].'/', '', $uid), $letter);
	#$sql = "delete from s3db_".$table." where ".$table_id." = '".$num_id."'";
	switch ($letter) {
			case 'I':
			
			#also, for every item there is a "has UID" statement that needs to be deleted. But the rule is being deleted when the collection is deleted (from item). So items and statemnts need to be deleted first
		   	if($num_id!=''){
			$SQL = "select statement_id from s3db_statement where rule_id in (select rule_id from s3db_rule where verb = 'has UID' and object = 'UID') and resource_id = '".$num_id."'";
			
			$db->query($SQL);

			if($db->next_record()){
			$stat2delete = $db->f('statement_id');
			$sql1 = "delete from s3db_statement where statement_id = '".$stat2delete."'";
			$db->query($sql1);
			$sql2 = "delete from s3db_permission where uid = 'S".$stat2delete."' or shared_with = 'S".$stat2delete."'";
			#echo $sql2.'<BR>';
			$db->query($sql2);
			}
			}
			$instance_info = URI($uid, $user_id, $db);
			

			$s3ql=compact('user_id','db');
			$s3ql['select']='*';
			$s3ql['from']='statements';
			$s3ql['where']['value'] = $num_id;
			
						
			$stats_to_delete = S3QLaction($s3ql);
			if (is_array($stats_to_delete)) 
			foreach ($stats_to_delete as $sInd=>$stat_info) {
				
				if ($stat_info['object_id']==$instance_info['resource_class_id'] && $stat_info['delete']) {
					$s3ql=compact('user_id','db');
					$s3ql['delete']='statement';
					$s3ql['where']['statement_id']=$stat_info['statement_id'];
					
					S3QLaction($s3ql);

				}
			}
			
			break;
		case 'C':
			#remove also the rule "hasUID"
			if($num_id!='')
			{$rule_has_UID_sql = "delete from s3db_rule where subject_id = '".$num_id."' and object='UID' and verb='has UID'";
			$db->query($rule_has_UID_sql, __LINE__, __FILE__);
			}
			
		break;
		}
	
	
	
	#after dependencies are deleted, delete the resource
	
	$sql = "delete from s3db_".$table." where ".$table_id." = '".$num_id."'";
	#echo $sql.'<BR>';
	$db->query($sql);
	
	##Now delete this id in the permissions table
	#
	if($uid!=''){
	$deleteSQL = "delete from s3db_permission where uid='".$uid."' or shared_with = '".$uid."'";
	#echo $deleteSQL.'<BR>';
	$db->query($deleteSQL);
	}

	$dbdata = get_object_vars($db);
	if($dbdata['Errno']==0) 
	{	
		
		return True;
	}
		else return False;
	
}
	
?>