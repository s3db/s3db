<?php

function get_filekey_data($filekey, $db)
{
$sql = "select * from s3db_file_transfer where filekey='".$filekey."' and expires>='".date('Y-m-d H:i:s')."'";
$db->query($sql, __LINE__, __FILE__);
if($db->next_record())
	{
	$file_info = array('file_id'=>$db->f('file_id'), 
							'filename'=>$db->f('filename'),
							'filesize'=>$db->f('filesize'),
							'created_by'=>$db->f('created_by'));
	return ($file_info);
	}
	else {
		return (False);
	}
	
}
function get_entry($table, $what, $where, $equals, $db)

{
	if ($GLOBALS['s3db_info']['server']['db']['db_type'] == 'mysql') $regexp = 'regexp';
	else $regexp = '~';
	
	if($db=='') $db = $_SESSION['db'];
	
	#remove commas from what
	$what = str_replace(' ', '', $what);
	$return = explode(',', $what);
	


	
	$sql = "select ".$what." from s3db_".$table." where ".$where." ".$regexp." '^".$equals."$'";
		#echo $sql;
		$db->query($sql, __LINE__, __FILE__);
		
		$data = array();
		while($db->next_record())
		{
			
			foreach ($return as $cols) {
				$data[$cols] = $db->f($cols);
			}
			 
			
		}

		
		if($data!='' && count($return)>1)
		return $data;
		elseif($data!='' && count($return)==1){
			return ($data[$what]);
		}
		else return False;

}

function getUserName($user_id, $db)
{
	$sql = "select * from s3db_account where account_id = '".$user_id."'";
	$db->query($sql, __LINE__, __FILE__);
	
	if($db->next_record())
		{
			return $db->f('account_lid');
		}
}

function get_rule_id_by_entity_id($resource_class_id, $project_id, $db)
	{global $regexp;
                
				#$db = $_SESSION['db'];
              
				if ($resource_class_id!='')
				##Find the entity
				{$sql ="select entity from s3db_resource where resource_id='".$resource_class_id."' and iid='0'";
				#echo $sql;
				$db->query($sql, __LINE__, __FILE__);
				while($db->next_record())
				{
				$entity = $db->f('entity');
				}
				}

				
				if ($entity!='')
				$sql = "select rule_id from s3db_rule where subject='".$entity."' and verb='has UID' and object='UID' and (project_id ".$regexp." '^".$project_id."$' or permission ".$regexp." '(^|_)".$project_id."_')";
				#echo $sql;

                $db->query($sql, __LINE__, __FILE__);
		while($db->next_record())
		{
			$rule_id = $db->f('rule_id');
		}
		
		return $rule_id;		
	}

	function get_resource_id_from_rule($rule)
	{
	extract($rule);

	if ($rule_info=='') $rule_info=get_info('rule', $rule_id, $db);

	$sql = "select resource_id from s3db_resource where entity='".$rule_info['subject']."' and iid='0' and project_id = '".$rule_info['project_id']."'";

	
	$db->query($sql, __LINE__, __FILE__);
		while($db->next_record())
		{
			$resource_id = $db->f('resource_id');
		}
		
		return $resource_id;		
	}

	function get_class_id_from_instance($I)
        {	extract($I);
				

               $sql ="select resource_id from s3db_resource where entity='".$instance_info['entity']."' and project_id='".$project_id."' and iid = 0";
		#echo $sql;
                $db->query($sql, __LINE__, __FILE__);
                if($db->next_record())
                        return $db->f('resource_id');
                return '';
        }


function find_entity_from_rule($rule_id)
	{
		$db = $_SESSION['db'];
		$sql = "select object from s3db_rule where rule_id = '".$rule_id."' and project_id='".$_REQUEST['project_id']."'";
		$db->query($sql, __LINE__, __FILE__);
		if($db->next_record())
			return $db->f('object');
		
	}

	


function add_entry ($table_name, $values_array, $db)

{

if ($table_name == 'access_rules') 
	{
$fields_array = array('project_id', 'rule_id', 'account_id', 'notes', 'URI', 'requested_on', 'status');

$values = "'".$values_array['project_id']."', '".$values_array['rule_id']."', '".$values_array['account_id']."', '".$values_array['notes']."', 'local', now(), 'pending'";

}

if ($table_name == 'access_keys') 
	{
$fields_array = array('key_id', 'account_id', 'expires', 'notes', 'uid');

$values = "'".$values_array['key_id']."', '".$values_array['account_id']."', '".$values_array['expires']."', '".$values_array['notes']."', '".$values_array['UID']."'";

}

if ($table_name == 'rule') 
	{
$fields_array = array('rule_id', 'project_id', 'subject', 'verb', 'object', 'notes', 'created_on', 'created_by', 'modified_on', 'modified_by', 'permission', 'validation');
	
$values = "'".$values_array['project_id']."', '".$values_array['rule_id']."', '".$values_array['subject']."', '".$values_array['verb']."','".$values_array['object']."','".$values_array['notes']."','".$values_array['created_on']."','".$values_array['created_by']."','".$values_array['modified_on']."','".$values_array['modified_by']."','".$values_array['permission']."','".$values_array['validation']."'";

	}

#Build the comma separated string for insert into a table
	
	for ($i=0; $i<count($fields_array); $i++)
		{if ($i!=count($fields_array)-1)
		$fields .= $fields_array[$i].', ';
		else $fields .= $fields_array[$i].'';
		}

	
	
if($db=='') $db = $_SESSION['db'];

$sql = 'insert into s3db_'.$table_name.' ('.$fields.') values ('.$values.')';
#echo $sql;
$db->query($sql, __LINE__, __FILE__);

$dbdata = get_object_vars($db);

if($dbdata['Errno']==0)
	return True;
else
	return False;


}

function find_entry($table_name, $return_fields, $where, $various)

{##Find all entries on a table
	##usage: bool = find_entry('specify if looking for several values a unique entry', 'table name', 'array with values or var with values')
	
#Various is an array to carry any number of data, the only requirement is that is is formed using "compact"

	if (is_array($various))
		extract($various);
	else
		$where = $various;

	
	if(!is_object($db)) $db = $_SESSION['db'];


	if ($where!='')
	{
	$query_fields = $where."='".$entry."'";
	
	}



	

		if($query_fields!='') $where = ' where ';
			else $where = '';
			if ($sortorder!='') $sort = ' order by '.$sortorder.' '.$direction.'';

			

			$sql = 'select * from s3db_'.$table_name.$where.$query_fields.$sort.'';

			$db->query($sql, __LINE__, __FILE__);
			while($db->next_record())
					{
					#foreach ($return_fields as $field)	
					$entries[] = Array('project_id'=>$db->f('project_id'),
						'rule_id'=>$db->f('rule_id'),
						'statement_id'=>$db->f('statement_id'),
						'account_id'=>$db->f('account_id'),
						'key_id'=>$db->f('key_id'),
						'expires'=>$db->f('expires'),
						'notes'=>$db->f('notes'),
						'URI'=>$db->f('URI'),
						'status'=>$db->f('status'),
						'requested_on'=>$db->f('requested_on'));
					}


			/*if ($number_of_values == 'single')
				{while($db->next_record())
					{
						
						$entries[] = Array($return_fields=>$db->f($return_fields));
					}
				}
			*/
				
		#echo $sql;
	

#echo '<pre>';print_r($entries);
return $entries;


}


function find_admin($db)
	{
		
		$sql = "select account_id from s3db_account where account_lid='Admin'";
		$db->query($sql, __LINE__, __FILE__);
		$db->next_record();
		return $db->f('account_id');
	}
	

function find_unique_entry($table_name, $return_value, $match_array, $db)
{
#Special case - instead of a direct extract of value, where trying to find a regexp
if(is_object($_SESSION['db']))
	$db = $_SESSION['db'];


if ($table_name == 'access_rules')
	{
	$query_subtring = "project_id='".$match_array['project_id']."' and rule_id='".$match_array['rule_id']."'";
	
	}


$sql = 'select '.$return_value.' from s3db_'.$table_name.' where '.$query_subtring.'';
$db->query($sql, __LINE__, __FILE__);
				if($db->next_record())
				return True;
				else 
				return False;
	

}

function delete_entry($table_name, $match_array, $db)
{
if ($table_name == 'access_rules')
	{
	$match_fields = array('project_id', 'rule_id');

	$query_string = " where ".$match_fields[0]."='".$match_array['project_id']."' and ".$match_fields[1]."='".$match_array['rule_id']."'";
	
	}

if ($table_name == 'access_keys')
	{
	$match_fields = array('key_id', 'expires');

	$query_string = " where ".$match_fields[0]."='".$match_array['key_id']."' and ".$match_fields[1]."='".$match_array['expires']."'";
	
	}

	



if($db=='') $db = $_SESSION['db'];
 $sql = "delete from s3db_".$table_name.$query_string;
$db->query($sql, __LINE__, __FILE__);
				if($db->next_record())
				return True;
				else 
				return False;
	

}


function find_latest_UID($table, $db)
	{
	if(ereg('statement_log', $table))
		{$sql = "select max(statement_log_id) as max from s3db_statement_log";
		$db->query($sql, __LINE__, __FILE__);

		if($db->next_record())
		return $statement_log_id = $db->f('max');
		}
	else	
		{
		if(ereg('(instance|class|resource class|resource instance)',$table))
			{$table='resource';}
		if (ereg('(user|group)', $table)) {
			$table = 'account';
		}

		 $sql = "select ".$table."_id from s3db_".$table." order by created_on desc limit 1";


		 $db->query($sql, __LINE__, __FILE__);
		
		if($db->next_record())
			{
				return  $db->f($table."_id");

			}
		}
	}



		
function get_resource_permissions($project_id)
	{
		$db= $_SESSION['db'];
		$sql = "select acl_rights from s3db_project_acl where acl_project_id ='".$project_id."' and acl_account='".$_SESSION['user']['account_id']."'";
		$db->query($sql, __LINE__, __FILE__);
		if($db->next_record())
		{
			//echo substr($db->f('acl_rights'), 1, 1). '\n';
			return substr($db->f('acl_rights'), 1, 1);
		}	
		return '';
	}
	
		function next_resource_id()

	{
		$db = $_SESSION['db'];
		$sql = 'select max(resource_id) from s3db_resource';
		$db->query($sql, __LINE__, __FILE__);
		if($db->next_record())
		{
			$last_rule = Array('resource_id'=>$db->f('max(resource_id)'));
		}

	   $next_rule = $last_rule['resource_id']+1;
		return $next_rule;
	
	
	
	}

	function next_rule_id()

	{
		$db = $_SESSION['db'];
		
		$sql = 'select max(rule_id) from s3db_rule';
		
		$db->query($sql, __LINE__, __FILE__);
		if($db->next_record())
		{
			if ($GLOBALS['s3db_info']['server']['db']['db_type']=='mysql')
			$last_rule = Array('rule_id'=>$db->f('max(rule_id)'));
			else
			$last_rule = Array('rule_id'=>$db->f('max'));
		}

	   $next_rule = $last_rule['rule_id']+1;
		return $next_rule;
	
	
	
	}



		function next_statement_id()

	{
		$db = $_SESSION['db'];
		$sql = 'select max(statement_id) from s3db_statement';
		$db->query($sql, __LINE__, __FILE__);
		if($db->next_record())
		{
			$last_rule = Array('statement_id'=>$db->f('max(statement_id)'));
		}

	   $next_rule = $last_rule['statement_id']+1;
		return $next_rule;
	
	
	
	}

			function get_resource_ids($entity)
	{
                $db = $_SESSION['db'];
		$project_id = $_REQUEST['project_id'];
                $sql = "select resource_id from s3db_resource where entity='".$entity."' and project_id='".$project_id."'";
                $db->query($sql, __LINE__, __FILE__);
		//echo $sql;
		while($db->next_record())
		{
			$resources[] = Array('resource_id'=>$db->f('resource_id'));
		//	print_r($resources);
		}
		return $resources;		
	}	

		

	
	 function get_resource_entity($resource_id, $db)
        {
				if($db=='') $db = $_SESSION['db'];

                $sql = "select entity from s3db_resource where resource_id='".$resource_id."'";
                //echo $sql;
                $db->query($sql, __LINE__, __FILE__);
                if($db->next_record())
                        return $db->f('entity');
                else
                        return '';
        }

		function find_project_owner($id, $db)
        {
                if(!is_object($db)) $db = $_SESSION['db'];
                $sql = "select account_lid, account_uname from s3db_account where account_id='".$id."'";
                $db->query($sql, __LINE__, __FILE__);
                if($db->next_record())
                        return $db->f('account_uname').' ( '.$db->f('account_lid') .' )';
                else 
                        return '';
        }    
	

		function find_new_uid($entity)
	{
		$db = $_SESSION['db'];
		$sql = "select max(uid) from s3db_resource where entity = '".$entity."' and project_id='".$_REQUEST['project_id']."'";
		$db->query($sql, __LINE__, __FILE__);
		if($db->next_record())
			return $db->f('max');
		else
			return '-1';
	}
	
	function get_resource_notes($statement_id) 
	{
		$db= $_SESSION['db'];
		$sql = "select re.notes from s3db_resource as re, s3db_statement as s where s.statement_id='".$statement_id."' and re.resource_id = s.resource_id";
		//echo $sql;
		$db->query($sql, __LINE__, __FILE__);
		if($db->next_record())
		{
			return $db->f('notes');
		}
		else
			return '';
	}	
		
	function find_user_loginID($a)
        {
              if(!is_array($a)) 
			{
				$db = $_SESSION['db'];
				$account_id = $a;
			}
			  else
				  extract($a);

                $sql = "select account_lid from s3db_account where account_id ='".$account_id."'";
                $db->query($sql, __LINE__, __FILE__);
                $db->next_record();
                return $db->f('account_lid');
        }	

	function find_user_name($userid)
	{
		$db = $_SESSION['db'];
		$sql = "select account_lid, account_uname from s3db_account where account_id='".$userid."'";
		//echo $sql;
		$db->query($sql, __LINE__, __FILE__);
		$user = Array();
		if($db->next_record())
		{
			$user = Array('account_lid'=>$db->f('account_lid'),
				      'account_uname'=>$db->f('account_uname'));
		}
		return $user;
		
	}

				
	function find_active_members($deletedgroup)
	{
		$active_members="";
		$db = $_SESSION['db'];
		$sql = "select account_id from s3db_account_group where group_id='".$deletedgroup['account_id']."'";
		$db->query($sql, __LINE__, __FILE__);
		while($db->next_record())
		{
			$user = get_user_info($db->f('account_id'));
			if($user['account_lid']!='Admin' && $user['account_type']!='g' && $user['account_status']=='A')
			{
				$active_members.=$user['account_uname'].", ";
			}	
		}
		
		return substr($active_members, 0, strrpos($active_members, ','));		
	}

	function user_groups($group_id, $account_id)
	{
		//echo "g: ".$group_id;
		//echo "a: ".$account_id;
		$db = $_SESSION['db'];
		$sql = "select * from s3db_account_group where account_id='".$account_id."' and group_id ='".$group_id."'";
		#echo $sql;	
		$db->query($sql, __LINE__, __FILE__);
		if($db->next_record())
			return True;	
		else
			return False;	
			
	}
	
		
	
	

//function get_permissions()
//	{global $resource_info, $project_info;
//		$db= $_SESSION['db'];
//		$sql = "select acl_rights from s3db_project_acl where acl_project_id ='".$project_info['id']."' and acl_account='".$_SESSION['user']['account_id']."'";
//		$db->query($sql, __LINE__, __FILE__);
//		//echo $sql;
//		if($db->next_record())
//			return $db->f('acl_rights');
//		return '';
//	}

function get_old_group_memebers($group)
	{
		//echo $group;
		$db = $_SESSION['db'];
		$sql = "select account_id from s3db_account_group where group_id='".$group."' and account_id !=group_id";
		//echo $sql;
		$db->query($sql, __LINE__, __FILE__);
		$old_group = array();
		while($db->next_record())
		{
			$old_group[] = $db->f('account_id');
		}
		//print_r($old_group);
		return $old_group;
	}

 function user_not_in_new_group($old_group, $new_group)
	 {
		//print_r($new_group);
		//print_r($old_group);
		$result = array();
		if (is_array($old_group)) {
		foreach($old_group as $i=>$value)
		{
			//echo $i;
			if (is_array($new_group))
			if(!in_array($old_group[$i], $new_group, true))
			 	$result[]= $old_group[$i];	
		}
		}
		return $result;		
		//return array_diff($old_group, $new_group);
	 }


##FORMER ELEMENT_INFO, these functions are to be distributed among smaller scripts containing only the functions necessary for a particular purpose. 

function get_resource_id($resource)
        {
                if(is_array($resource))
					extract($resource);
				else 
					{
						$db =$_SESSION['db'];
						$entity = $resource;
					}

               $sql ="select resource_id from s3db_resource where entity='".$entity."' and project_id='".$project_id."' and iid = 0";
		#echo $sql;
                $db->query($sql, __LINE__, __FILE__);
                if($db->next_record())
                        return $db->f('resource_id');
                return '';
        }


##This function is the same as the previous, only it demands for the project_id and db, which is cannot get from the session or the url
#scripts that use the above function should be updated to allow merging with this one
function get_resource_id_S3QL($entity, $project_id, $db)
        {
               if ($GLOBALS['s3db_info']['server']['db']['db_type'] == 'mysql') $regexp = 'regexp';
				else $regexp = '~';
		 #$sql ="select resource_id from s3db_resource where entity='".$entity."' and project_id='".$project_id."' and iid = '0'";
			   $sql ="select resource_id from s3db_resource where entity ".$regexp." '".$entity."' and project_id='".$project_id."' and iid = '0'";
				#echo $sql;
                $db->query($sql, __LINE__, __FILE__);
                if($db->next_record())
                        return $db->f('resource_id');
                return '';
        }

function getResourceID_newresource($resource)
        {		$project_info = get_info('project', $_REQUEST['project_id'], $db);
		
                $acl = find_user_acl($_SESSION['user']['account_id'], $_REQUEST['project_id'], $_SESSION['db']);
                $result =  str_pad($resource['resource_id'], 6, '0', STR_PAD_LEFT).'<br />';
		
                if($_SESSION['user']['account_id'] == $project_info['owner'] || $acl =='3' ||$acl =='2' ||$resource['created_by']==$_SESSION['user']['account_id'])
                        $result .= getEditNewResourceLink($resource['resource_id']). '&nbsp;&nbsp;'. getDeleteNewResourceLink($resource['resource_id']);
                return $result;
        }


function get_resource_class_id($resource_id)
        {
                if(is_array($resource_id)) extract($resource_id);
					else $db =$_SESSION['db'];

               $sql ="select resource_class_id from s3db_resource where resource_id='".$resource_id."'";

                $db->query($sql, __LINE__, __FILE__);
                if($db->next_record())
                        return $db->f('resource_class_id');
                return '';
        }

function get_rule($rule_id)
	{
		$db = $_SESSION['db'];
		$sql ="select subject, verb, object, notes from s3db_rule where rule_id='".$rule_id."'";
		$db->query($sql, __LINE__, __FILE__);
		if($db->next_record())
		{
			$rule = Array('subject'=>$db->f('subject'),
					 'verb'=>$db->f('verb'),
					 'object'=>$db->f('object'),
					 'notes'=>$db->f('notes'));
		}
		return $rule;
	}
function get_rule_insertall($subject, $verb, $object)
        {
                $db = $_SESSION['db'];
                $sql ="select rule_id, notes from s3db_rule where subject='".$subject."' and verb='".$verb."' and object='".$object."'";
                //echo $sql;
                $db->query($sql, __LINE__, __FILE__);
                if($db->next_record())
                {
                        $rule = Array('rule_id'=>$db->f('rule_id'), 'notes'=>$db->f('notes'));
                }
                //print_r($rule);
                return $rule;
        }

	function search_all($project_id, $entity, $entity_id)
	{
		
		$final_query = "select resource_id, entity, notes, created_on, created_by  from s3db_resource where resource_class_id = '".$entity_id."' and entity='".$entity."' and iid !=0 order by created_on desc";

		#$final_query = "select resource_id, entity, notes, created_on, created_by  from s3db_resource where entity='".$entity."' and project_id='".$project_id."' and iid !=0 order by created_on desc";
		
		#$final_query = "select resource_id, entity, notes, created_on, created_by  from s3db_resource where entity='".$resource_info['entity']."' and iid !=0 order by created_on desc";
		
		$display_query = $final_query;	
		$db = $_SESSION['db'];
		$db->query($final_query, __LINE__, __FILE__);
		while($db->next_record())
		{
			$found_resources[] = Array('resource_id'=>$db->f('resource_id'),
						'entity'=>$db->f('entity'),
						'created_by'=>$db->f('created_by'),
						'created_on'=>$db->f('created_on'),
						'notes'=>$db->f('notes'));
		}
		//$found_instances = get_found_resources($found_resources);
		$found_instances = $found_resources;
		$found_instances['sqlquery'] = $display_query;
		//$_SESSION['used_rule'] = $used_rule;
		return $found_instances;									
	}

	 function get_entity($resource_id, $db)
        {
                #$db = $_SESSION['db'];
               $sql = "select entity from s3db_resource where resource_id='".$resource_id."'";
                $db->query($sql, __LINE__, __FILE__);
                if($db->next_record())
                        return $db->f('entity');
                else
                        return '';
        }

	 function get_project_owner($project_id)
        {
                $db = $_SESSION['db'];
                $sql = "select account_id from s3db_project, s3db_account where s3db_project.project_owner =s3db_account.account_id and project_id='".$project_id."'";
		//echo $sql;
                $db->query($sql, __LINE__, __FILE__);
		$res ='';
                if($db->next_record())
                        $res = $db->f('account_id');
		//echo $res;
                return $res;
        }

		function get_verb ($rule_id, $db)
	{
				#$db = $_SESSION['db'];
                $sql = "select verb from s3db_rule where rule_id='".$rule_id."'";
                $db->query($sql, __LINE__, __FILE__);
                if($db->next_record())
                        return $db->f('verb');
                else
                        return '';
	
	
	}

	function get_object ($rule_id, $db)
	{
				#$db = $_SESSION['db'];
                $sql = "select object from s3db_rule where rule_id='".$rule_id."'";
                $db->query($sql, __LINE__, __FILE__);
                if($db->next_record())
                        return $db->f('object');
                else
                        return '';
	
	
	}



	

		function get_entity_id($entity, $project_id)
        {
                $db = $_SESSION['db'];
                $sql = "select resource_id from s3db_resource where entity='".$entity."' and iid=0 and project_id='".$project_id."'";
                $db->query($sql, __LINE__, __FILE__);
                if($db->next_record())
                        return $db->f('resource_id');
                else
                        return '';
        }

		function get_classrule_id($subject, $project_id)

			{
                $db = $_SESSION['db'];
                $sql = "select rule_id from s3db_rule where subject='".$subject."' and verb='has UID' and object='UID' and project_id='".$project_id."'";
                $db->query($sql, __LINE__, __FILE__);
                if($db->next_record())
                        return $db->f('rule_id');
                else
                        return '';
        }

		function get_classresource_id($subject, $project_id, $db)

			{
               if($db=='') $db = $_SESSION['db'];
                $sql = "select resource_id from s3db_resource where entity='".$subject."' and iid=0 and project_id='".$project_id."'";
                $db->query($sql, __LINE__, __FILE__);
                if($db->next_record())
                        return $db->f('resource_id');
                else
                        return '';
        }

		function get_value($resource_id, $rule_id, $project_id)
	{
        $db = $_SESSION['db'];
		$sql ="select project_id, resource_id, statement_id, rule_id, file_name, value from s3db_statement where resource_id='".$resource_id."' and rule_id='".$rule_id."' and value !='' and project_id='".$project_id."'";
                $db->query($sql, __LINE__, __FILE__);
		//echo $sql;
                while($db->next_record())
		{
			$values[] = Array('value'=>$db->f('value'), 
				'project_id'=>$db->f('project_id'),
				'resource_id'=>$db->f('resource_id'),
				'statement_id'=>$db->f('statement_id'),
				'rule_id'=>$db->f('rule_id'),
				'file_name'=>$db->f('file_name'));
			/*$file_name = $db->f('file_name');
			if($file_name == '')
				$values[]= $db->f('value');
			else
				$values[] =  "File: ".$file_name;
			*/
		}
		//print_r($values);
		return $values;
	}

function subject_in_project($subject_id, $project_id, $db)
{	
	$uid = 'C'.$subject_id;
	$shared_with = 'P'.$project_id;
	#$sql = select(compact('uid', 'shared_with', 'db'));
	$sql = "select resource_id from s3db_resource where iid='0' and resource_id = '".$subject_id."'";
	$db->query($sql, __FILE__, __LINE__);

	 if($db->next_record())
		{return (True);
	 }
	 else {
		return (False);
	 }
}


function is_resource_exists($resource_info, $db)
	{
		
		if ($GLOBALS['s3db_info']['server']['db']['db_type'] == 'mysql') $regexp = 'regexp';
		else $regexp = '~';
		
		
		$subject = ($resource_info['subject']!='')?$resource_info['subject']:$resource_info['entity'];
		$rule_id = $resource_info['rule_id'];
		if($rule_id=='')
			$rule_id = ruleId4class(array('element_info'=>$resource_info, 'db'=>$db));
		#$sql = "select resource_id from s3db_resource where entity='".$resource['entity']."' and project_id='".$resource['project_id']."'";
		if($rule_id!='')
		$sql = "select rule_id from s3db_rule where subject='".$subject."' and object='UID' and (project_id='".$resource_info['project_id']."' or permission ".$regexp." '(^|_)".$resource_info['project_id']."_') and rule_id!='".$rule_id."'";	
		else {
			$sql = "select rule_id from s3db_rule where subject='".$subject."' and object='UID' and (project_id='".$resource_info['project_id']."' or permission ".$regexp." '(^|_)".$resource_info['project_id']."_')";	
		}
		#echo $sql;exit;
		$db->query($sql, __LINE__, __FILE__);
	
		if($db->next_record())
			return True;
		else
			return False;
	}

function nothing_changed($inputs, $oldvalues)
{

$inputs = @array_values($inputs);
$oldvalues = @array_values($oldvalues);
if ($inputs == $oldvalues) return True;
else return False;

}

function all_users($U)
{
#function all users will generate a list of user_ids and login_ids such that a user_id can be replaced by its user_id
extract($U);

$sql = "select account_id, account_lid from s3db_account";
$db->query($sql, __LINE__, __FILE__);
while($db->next_record())
	{$ids[] = $db->f('account_id');
	$logins[] = $db->f('account_lid');
	}
$users = array_combine($ids, $logins);

return  $users;

}

function all_projects($P)
{
#function all users will generate a list of user_ids and login_ids such that a user_id can be replaced by its user_id
extract($P);

$sql = "select project_id, project_name, project_description from s3db_project";
$db->query($sql, __LINE__, __FILE__);
while($db->next_record())
	{$ids[] = $db->f('project_id');
	$names[] = $db->f('project_name');
	}
$projs = array_combine($ids, $names);

return  $projs;

}
		
function all($x)
{#function all gets a list of all available IDs and Names from a particular table
	extract($x);
	
	$name=array();
	if($element=='project') $name[0]='project_name';
	if($element=='resource') $name[0]='entity';
	if($element=='account') $name[0]='account_lid';
	if($element=='rule') {$name[0]='subject';$name[1]='verb';$name[2]='object';}
	
	$sql = "select * from s3db_".$element."";
	$db->query($sql, __LINE__, __FILE__);
	
	while($db->next_record())
		{$ids[] = $db->f($element.'_id');
		if($name[1]=='')
		$names[] = $db->f($name[0]);
		else
		$names[] = $db->f($name[0]).'|'.$db->f($name[1]).'|'.$db->f($name[2]);
		}
		#echo '<pre>';print_r($names);
		$all = array_combine($ids, $names);
		return $all;
}

function my_connected_rules($y)
{	extract($y);
	
	$sql = "select * from s3db_access_rules where rule_id in (select rule_id from s3db_rule where project_id = '".$project_id."')";
	#echo $sql;
	$db->query($sql, __LINE__, __FILE__);
	 while($db->next_record())
          {	
			$resultStr .= "\$data[] = Array(";
				
				foreach ($cols as $col)
						
					{
						if($db->f($col)!='')
						{
						$resultStr .= "'".$col."'=>'".addslashes($db->f($col))."'";
						if($col != end($cols))
						$resultStr .= ",";
						}
					}
					$resultStr .= ");";
					
					
				
			}
			#echo $resultStr;
				#evaluate the long string
				eval($resultStr);
		
			#echo '<pre>';print_r($data);
			

			return $data;
}

function resourceObject($D)
{ global $regexp;
	#Returns true if the object of the rule is a resource (in which case the value of the statement is a resource)
extract($D);
	if(!$rule_info)
	$rule_info = get_info('rule', $rule_id, $db);

	if(!$regexp)
		if ($GLOBALS['s3db_info']['server']['db']['db_type'] == 'mysql') $regexp = 'regexp';
		else $regexp = '~';

	$sql = "select * from s3db_rule where subject='".$rule_info['object']."' and verb = 'has UID' and object = 'UID' and (project_id ".$regexp." '^".$project_id."$' or permission ".$regexp." '(^|_)".$project_id."_')";

	#echo $sql;
	$db->query($sql, __LINE__, __FILE__);

	if($db->next_record())
		return True;
	else 
		return False;
}

function resource_found($A)
	{
		extract($A);

		if($object=='')
			$object = $rule_info['object'];
		if($object=='')
			{
			$rule_info = get_info('rule', $rule_id, $db);
			$object = $rule_info['object'];
			}
		#$db = $_SESSION['db'];
		#Seek value in all resources where object is entity
		$s3ql['db'] = $db;
		$s3ql['user_id'] = $user_id;
		$s3ql['select'] = '*';
		$s3ql['from'] = 'instances';
		$s3ql['where']['project_id'] = $project_id;
		$s3ql['where']['resource_id'] = $value;
		$s3ql['where']['entity'] = $object;
		
		$done = S3QLaction($s3ql);

		#echo '<pre>';print_r($s3ql);
		#echo $done;
		if(is_array($done))
			return True;
		else
			return False;
		#$sql = "select resource_id from s3db_resource where entity='".$object."' and resource_id='".$value."'"; 

		

		
		
//		$db->query($sql, __LINE__, __FILE__);
//		if($db->next_record())
//			return True;
//		else	
//			return False;

	}
		
function resourceClassID4Instance($x)
{
extract($x);

$table = 'class';
$permission = "~ '(^|_)".$project_id."_'";
$project_id = "~ '^".$project_id."$'";
$cols = array('entity', 'resource_id', 'iid');
$iid='0';

$class_entry = listS3DB(compact('db', 'table', 'cols', 'project_id', 'permission','iid', 'entity'));
$class_id = $class_entry[0]['resource_id'];

 if($class_id!='')
 return $class_id;
 else
 return False;
}

function ruleId4object($R)
{$regexp=$GLOBALS['regexp'];
extract($R);


$sql = "select * from s3db_rule where subject='".$object."' and verb='has UID' and object='UID' and (project_id ".$regexp." '^".$project_id."$' or permission ".$regexp." '(^|_)".$project_id."_')";

#echo $sql;
$db->query($sql, __LINE__, __FILE__);

	if($db->next_record())
		return $db->f('rule_id');

		
}

function subject4subjectID($S)
{extract($S);
#grabbing a label; might not be in local implementation
#$S must contain subject_id and db


$sql = "select subject from s3db_rule where subject_id = '".$subject_id."' and verb ='has UID' and object='UID'";
$db->query($sql, __LINE__, __FILE__);

	if($db->next_record())
		return $db->f('subject');
	else {
		
		#$data=remoteURI($subject_id, $key, $user_id, $db);
		$data=URIinfo('C'.$subject_id, $user_id, $key, $db);
		if($data['entity'])
			return ($data['entity']);
		else {
			return (False);
		}
	}
	

	
}

function object4objectID($S)
{extract($S);
#$S must contain subject_id and db


$sql = "select subject from s3db_rule where subject_id = '".$object_id."' and verb ='has UID' and object='UID'";
$db->query($sql, __LINE__, __FILE__);

	if($db->next_record())
		return $db->f('subject');
	else {
		$data=remoteURI($object_id, $key, $user_id, $db);
		if($data['entity'])
		return ($data['entity']);
		else {
			return (False);
		}
	}

}

function verb4instanceID($V)
{extract($V);
#$V must contain at least instance_id and db
	#if(ereg('/', $instance_id))
	$data=URIinfo('I'.$instance_id, $user_id,$key, $db);
	#echo 'ola<pre>';print_r($data);exit;
	#$data=remoteURI($instance_id, $key, $user_id, $db);
if(is_array($data))
return ($data['notes']);
#return ($data['entity'].' '.$data['notes'].' (I'.$instance_id.')');
else {
	return (False);
}
}
#$sql = "select entity, notes from s3db_resource where instance_id = '".$instance_id."'";
#	$db->query($sql, __LINE__, __FILE__);
#echo $sql;exit;
	#if($db->next_record())
	#{$ent = $db->f('entity');
	#$notes = $db->f('notes');
	#$notes = ($notes!='')?$notes:$instance_id;
		
	#	return ($ent.$notes);
	#}
	#else 
		
#}

function projectVerbClass($V)
{extract($V);
# projectVerbClass($V) find the class_id of the class of arbitrary verbs. 
	#$sql = "select * from s3db_resource where iid = '0' and entity = 's3dbVerb' and resource_id in (select substr(uid,2,length(uid)) from s3db_permission where uid ".$GLOBALS['regexp']." '^C' and shared_with = 'P".$project_id."')";
	$sql = "select * from s3db_resource where iid = '0' and entity = 's3dbVerb' and project_id = '".$project_id."'";

	
	$db->query($sql, __LINE__, __FILE__);

	if($db->next_record())
		{
		$s3dbVerb = array('entity'=>$db->f('entity'), 'resource_id'=>$db->f('resource_id'), 'project_id'=>$db->f('project_id'));
		}
	
	if($s3dbVerb!='')
		return ($s3dbVerb);
	else {
		return (False);
	}
}

function ruleId4entity($R)
{$regexp=$GLOBALS['regexp'];
extract($R);
$sql = "select * from s3db_rule where subject='".$entity."' and verb='has UID' and object='UID' and (project_id ".$regexp." '^".$project_id."$' or permission ".$regexp." '(^|_)".$project_id."_')";

#echo $sql;
$db->query($sql, __LINE__, __FILE__);

	if($db->next_record())
		return $db->f('rule_id');

		
}

function ruleId4instance($I)
{$regexp=$GLOBALS['regexp'];
extract($I);
#this one is a bit tricky, as it implied finding the project-id of the class
$sql = "select project_id from s3db_resource where resource_id='".$class_id."'";
$db->query($sql, __LINE__, __FILE__);

	if($db->next_record())
		$project_id = $db->f('project_id');

$sql = "select * from s3db_rule where subject='".$entity."' and verb='has UID' and object='UID' and (project_id ".$regexp." '^".$project_id."$' or permission ".$regexp." '(^|_)".$project_id."_')";

$db->query($sql, __LINE__, __FILE__);

	if($db->next_record())
		return $db->f('rule_id');
	
}

function ruleId4class($I)
{$regexp=$GLOBALS['regexp'];
extract($I);

#need to find the entity, if not provided
if ($element_info['entity']!='' && $element_info['rule_project_id']!='') {
	$entity = $element_info['entity'];
	$project_id = $element_info['rule_project_id'];
}
else {
	$sql ="select entity,project_id from s3db_resource where resource_id='".$element_info['class_id']."' and iid='0'";
	
	$db->query($sql, __LINE__, __FILE__);

	while($db->next_record())
	{	
	$entity =  $db->f('entity');
		$project_id = $db->f('project_id');
	}
	}

$sql = "select * from s3db_rule where subject='".$entity."' and verb='has UID' and object='UID' and (project_id ".$regexp." '^".$project_id."$' or permission ".$regexp." '(^|_)".$project_id."_')";
#echo $sql;
$db->query($sql, __LINE__, __FILE__);

	if($db->next_record())
		return $db->f('rule_id');
	
}

function classID4entity($P)
{ $regexp=$GLOBALS['regexp'];
#P needs $object, $project_id, $db
extract($P);
$sql = "select resource_id from s3db_resource as c, s3db_rule as r where
(r.project_id ".$regexp." '^".$project_id."$' or r.permission ~ '(^|_)".$project_id."_')
and r.subject=c.entity and r.project_id=c.project_id and r.object='UID' and iid='0' and entity = '".$entity."'";
#echo $sql.'<br />';exit;
$db->query($sql, __LINE__, __FILE__);

	if($db->next_record())
		return $db->f('resource_id');
}

function fastClassID($P)
{$regexp=$GLOBALS['regexp'];
#P needs $entity, $project_id, $db
#fastClassID(compact($entity, $project_id, $db))
#This function is assuming there can only be 1 class with the same name on a project
extract($P);
#$sql = "select distinct(resource_id) as resource_id from s3db_resource as c, s3db_rule as r where r.project_id = c.project_id and r.subject=c.entity and c.iid='0' and entity='".$entity."' and c.project_id = '".$project_id."'";

$sql = "select distinct(resource_id) from s3db_resource as c, s3db_rule as r where (r.project_id ".$regexp." '^".$project_id."$' or r.permission ".$regexp." '(^|_)".$project_id."_') and r.subject=c.entity and r.project_id=c.project_id and r.object='UID' and entity = '".$entity."' and iid = '0'";
#echo $sql;exit;
$db->query($sql, __LINE__, __FILE__);

	if($db->next_record())
		return $db->f('resource_id');
}

function fastRuleID4class($P)
{$regexp=$GLOBALS['regexp'];
#P needs $class_id, $db
extract($P);
$sql = "select rule_id from s3db_rule where subject_id = '".$class_id."' and verb='has UID' and object='UID'";
#$sql = "select rule_id from s3db_rule as r, s3db_resource as c where (r.project_id = c.project_id and r.subject = c.entity) and resource_id = '".$class_id."' and verb='has UID' and object='UID'";

$db->query($sql, __LINE__, __FILE__);

	if($db->next_record())
		return $db->f('rule_id');
}

function classID4verb($P)
{$regexp=$GLOBALS['regexp'];
#P needs $object, $project_id, $db
extract($P);
$sql = "select resource_id from s3db_resource as c, s3db_rule as r where
(r.project_id ".$regexp." '^".$project_id."$' or r.permission ~ '(^|_)".$project_id."_')
and r.subject=c.entity and r.project_id=c.project_id and r.object='UID' and iid='0' and entity = '".$verb."'";
#echo $sql;
$db->query($sql, __LINE__, __FILE__);

	if($db->next_record())
		return $db->f('resource_id');
}

function get_notes($item_id,$db)
{
	$sql = "select notes from s3db_resource where resource_id = '".$item_id."'";
	$db->query($sql,__LINE__,__FILE__);
	if($db->next_record())
		return ($db->f('notes'));

}
function get_user_key($user_id, $db)
{$regexp = $GLOBALS['regexp'];
$sql = "select key_id from s3db_access_keys where account_id ".$regexp." '^".$user_id."$' and expires > '".date('Y-m-d H:i:s',time())."'";

$db->query($sql, __LINE__, __FILE__);

	if($db->next_record())
		return $db->f('key_id');
	else {
		$sql = "insert into s3db_access_keys (key_id, account_id, expires, notes) values ('".random_string(15)."', '".$user_id."', '".date('Y-m-d H:i:s',time() + (1 * 1 * 60 * 60))."', 'key created for user access in remote deployment')";
		$db->query($sql, __LINE__, __FILE__);

		$sql = "select key_id from s3db_access_keys where account_id ".$regexp." '^".$user_id."$'";
		if($db->next_record())
		return $db->f('key_id');
	}
}

function list_instances_IDS($resource_info, $db)
	{
	
		#$db = $_SESSION['db'];
		
		$sql = 'select resource_id, notes from s3db_resource where entity=\''.$rule['object'].'\' and iid!=0 and project_id = '.$_REQUEST['project_id'].' order by notes';
		
		$db->query($sql, __LINE__, __FILE__);
		echo $sql;
		while($db->next_record())
		{
			$values[] = Array( 'resource_id'=>$db->f('resource_id'),
					    
						'notes'=>$db->f('notes'));
		}
		
		return $values;	
		
	}

function list_existing_values($info, $db)
	{
		if(is_object($_SESSION['db'])) $db = $_SESSION['db'];
		//$sql = "select distinct subject, verb, object, value from s3db_statement where subject='".$rule['subject']."' and verb='".$rule['verb']."' and object='".$rule['object']."' and value !=''";
		
	
		$sql = "select distinct r.subject, r.verb, r.object, s.value, s.file_name from s3db_rule as r, s3db_statement as s where r.rule_id = s.rule_id and r.rule_id ='".$info['rule_id']."' and value !='' and value !='&nbsp;' order by r.verb, r.object";
		
		
		$db->query($sql, __LINE__, __FILE__);
		//echo $sql;
		while($db->next_record())
		{
			$values[] = Array('rule_id'=>$db->f('rule_id'),
					    'subject'=>$db->f('subject'),
					    'verb'=>$db->f('verb'),
					    'object'=>$db->f('object'),
					    'file_name'=>$db->f('file_name'),
					    'value'=>$db->f('value'));
		}
		//print_r($values);
		//print_r(break_multiple_values($values));	
		return break_multiple_values($values);	
		//return $values;	
	}
	
	function get_info1($element, $element_id, $db)
	{
		if(!is_object($db))
			$db = $_SESSION['db'];
		
		#echo '<pre>';print_r($db);
		if($element=='class' || $element=='resource class')
		{$element='resource';
		$query_end .= " and iid='0'";
		}
		if($element=='user')
			$element = 'account';
		if($element=='instance' || $element=='resource instance')
		{$element='resource';
		$query_end .= " and iid!='0'";
		}
		if($element=='key')
		{
		$sql = "select * from s3db_access_keys where key_id='".$element_id."'".$query_end;
		}
		else
		$sql = "select * from s3db_".$element." where ".$element."_id='".$element_id."'".$query_end;
		
		#echo '<BR>'.$sql.'<BR>'; 
		
		$db->query($sql, __LINE__, __FILE__);

		if ($element == 'rule')
		{if($db->next_record())
		{
			$info = Array('project_id'=>$db->f('project_id'),
					'resource_id'=>$db->f('resource_id'),
					'rule_id'=>$db->f('rule_id'),
					'subject'=>$db->f('subject'),	
					'verb'=>$db->f('verb'),	
					'object'=>$db->f('object'),	
					'notes'=>$db->f('notes'),	
					'permission'=>$db->f('permission'),	
					'created_on'=>substr($db->f('created_on'), 0, 19),	
					'created_by'=>$db->f('created_by'),	
					'modified_on'=>$db->f('modified_on'),	
					'modified_by'=>$db->f('modified_by'));
		}
		}
		elseif($element == 'resource')
		{if($db->next_record())
		{
			$info = Array('resource_id'=>$db->f('resource_id'),
					 'project_id'=>$db->f('project_id'),
					'iid'=>$db->f('iid'),
					'entity'=>$db->f('entity'),
					'resource_class_id'=>$db->f('resource_class_id'),
					'notes'=>$db->f('notes'),
					'created_on'=>$db->f('created_on'),
					'created_by'=>$db->f('created_by'),	
					'modified_on'=>$db->f('modified_on'),
					'modified_by'=>$db->f('modified_by'));
		}
		}

		elseif($element == 'project')
		{if($db->next_record())
		{
			$info=Array('project_id'=>$db->f('project_id'),
					   'project_name'=>$db->f('project_name'),
					   'project_owner'=>$db->f('project_owner'),
					   'project_description'=>$db->f('project_description'),
					    'project_folder'=>$db->f('project_folder'),
					   'project_status'=>$db->f('project_status'),
					   'created_on'=>$db->f('created_on'),
					   'created_by'=>$db->f('created_by'),
					   'modified_on'=>$db->f('modified_on'),
					   'modified_by'=>$db->f('modified_by'));
		}
		}
		

		elseif($element == 'account')
		{if($db->next_record())
		{
			$info=Array('account_id'=>$db->f('account_id'),
				      'account_lid'=>$db->f('account_lid'),	
				      'account_pwd'=>$db->f('account_pwd'),	
				      'account_uname'=>$db->f('account_uname'),	
				      'account_group'=>$db->f('account_group'),
					  'account_email'=>$db->f('account_email'),
				      'account_phone'=>$db->f('account_phone'),	
				      'account_type'=>$db->f('account_type'),
					  
				      'account_addr_id'=>$db->f('account_addr_id'),	
				      'account_status'=>$db->f('account_status'),
					  'created_on'=>$db->f('created_on'),
					  'created_by'=>$db->f('created_by'));
			
			if($account['account_addr_id'] > 0)
			{	
				$sql = "select * from s3db_addr where addr_id='".$account['account_addr_id']."'";
				$db->query($sql, __LINE__, __FILE__);
				if($db->next_record())
				{
					$info['addr1']=$db->f('addr1');
					$info['addr2']=$db->f('addr2');
				    $info['city']=$db->f('city');	
				    $info['state']=$db->f('state');
				    $info['postal_code']=$db->f('postal_code');
				    $info['country']=$db->f('country');
				}
			}
		}
		}
		elseif($element == 'key')
		{if($db->next_record())
					$info=Array('key_id'=>$db->f('key_id'),
				        'expires'=>$db->f('expires'),
						'account_id'=>$db->f('account_id'),
						'notes'=>$db->f('notes'));
		}
		
		elseif($element == 'statement')
		{
			if($db->next_record())
			{$info = Array('statement_id'=>$db->f('statement_id'),
					 'project_id'=>$db->f('project_id'),
					'resource_id'=>$db->f('resource_id'),
					'rule_id'=>$db->f('rule_id'),
					'value'=>$db->f('value'),
					'notes'=>$db->f('notes'),
					'created_on'=>$db->f('created_on'),
					'created_by'=>$db->f('created_by'),	
					'modified_on'=>$db->f('modified_on'),
					'modified_by'=>$db->f('modified_by'),
					'mime_type'=>$db->f('mime_type'),
					'file_name'=>$db->f('file_name'));
			}
		
		
		}
#echo '<pre>';print_r($info);
		return $info;
	}

function get_info($element, $element_id, $db)
	{
		if(!is_object($db))
			$db = $_SESSION['db'];
		
		#echo '<pre>';print_r($db);
		if($element=='class' || $element=='resource class')
		{$element='resource';
		$query_end .= " and iid='0'";
		}
		if($element=='user')
			$element = 'account';
		if($element=='instance' || $element=='resource instance')
		{$element='resource';
		$query_end .= " and iid!='0'";
		}
		if($element=='key')
		{
		$sql = "select * from s3db_access_keys where key_id='".$element_id."'".$query_end;
		}
		else
		$sql = "select * from s3db_".$element." where ".$element."_id='".$element_id."'".$query_end;
		
		#echo '<BR>'.$sql.'<BR>'; 
		
		$db->query($sql, __LINE__, __FILE__);

		if ($element == 'rule')
		{if($db->next_record())
		{
			$info = Array('project_id'=>$db->f('project_id'),
					'resource_id'=>$db->f('resource_id'),
					'rule_id'=>$db->f('rule_id'),
					'subject'=>$db->f('subject'),	
					'verb'=>$db->f('verb'),	
					'object'=>$db->f('object'),	
					'notes'=>$db->f('notes'),	
					'permission'=>$db->f('permission'),	
					'created_on'=>substr($db->f('created_on'), 0, 19),	
					'created_by'=>$db->f('created_by'),	
					'modified_on'=>$db->f('modified_on'),	
					'modified_by'=>$db->f('modified_by'));
		}
		}
		elseif($element == 'resource')
		{if($db->next_record())
		{
			$info = Array('resource_id'=>$db->f('resource_id'),
					 'project_id'=>$db->f('project_id'),
					'iid'=>$db->f('iid'),
					'entity'=>$db->f('entity'),
					'resource_class_id'=>$db->f('resource_class_id'),
					'notes'=>$db->f('notes'),
					'created_on'=>$db->f('created_on'),
					'created_by'=>$db->f('created_by'),	
					'modified_on'=>$db->f('modified_on'),
					'modified_by'=>$db->f('modified_by'));
		}
		}

		elseif($element == 'project')
		{if($db->next_record())
		{
			$info=Array('project_id'=>$db->f('project_id'),
					   'project_name'=>$db->f('project_name'),
					   'project_owner'=>$db->f('project_owner'),
					   'project_description'=>$db->f('project_description'),
					   'project_status'=>$db->f('project_status'),
					   'created_on'=>$db->f('created_on'),
					   'created_by'=>$db->f('created_by'),
					   'modified_on'=>$db->f('modified_on'),
					   'modified_by'=>$db->f('modified_by'));
		}
		}
		

		elseif($element == 'account')
		{if($db->next_record())
		{
			$info=Array('account_id'=>$db->f('account_id'),
				      'account_lid'=>$db->f('account_lid'),	
				      'account_pwd'=>$db->f('account_pwd'),	
				      'account_uname'=>$db->f('account_uname'),	
				      'account_group'=>$db->f('account_group'),
					  'account_email'=>$db->f('account_email'),
				      'account_phone'=>$db->f('account_phone'),	
				      'account_type'=>$db->f('account_type'),
					  
				      'account_addr_id'=>$db->f('account_addr_id'),	
				      'account_status'=>$db->f('account_status'),
					  'created_on'=>$db->f('created_on'),
					  'created_by'=>$db->f('created_by'));
			
			if($account['account_addr_id'] > 0)
			{	
				$sql = "select * from s3db_addr where addr_id='".$account['account_addr_id']."'";
				$db->query($sql, __LINE__, __FILE__);
				if($db->next_record())
				{
					$info['addr1']=$db->f('addr1');
					$info['addr2']=$db->f('addr2');
				    $info['city']=$db->f('city');	
				    $info['state']=$db->f('state');
				    $info['postal_code']=$db->f('postal_code');
				    $info['country']=$db->f('country');
				}
			}
		}
		}
		elseif($element == 'key')
		{if($db->next_record())
					$info=Array('key_id'=>$db->f('key_id'),
				        'expires'=>$db->f('expires'),
						'account_id'=>$db->f('account_id'),
						'notes'=>$db->f('notes'));
		}
		
		elseif($element == 'statement')
		{
			if($db->next_record())
			{$info = Array('statement_id'=>$db->f('statement_id'),
					 'project_id'=>$db->f('project_id'),
					'resource_id'=>$db->f('resource_id'),
					'rule_id'=>$db->f('rule_id'),
					'value'=>$db->f('value'),
					'notes'=>$db->f('notes'),
					'created_on'=>$db->f('created_on'),
					'created_by'=>$db->f('created_by'),	
					'modified_on'=>$db->f('modified_on'),
					'modified_by'=>$db->f('modified_by'),
					'mime_type'=>$db->f('mime_type'),
					'file_name'=>$db->f('file_name'));
			}
		
		
		}
#echo '<pre>';print_r($info);
		return $info;
	}
function admins($key, $user_id, $db)
{
	#user admin (belongs to group 1) can change/add/view anything in any group - 222
	$uid = 'U';
	$shared_with = 'G1';
	#echo '<pre>';print_r($db);
	#$admins_query = select(compact('uid', 'shared_with', 'db'));
	#$admins_query = "select account_id from s3db_account where account_type = 'a' or account_id in (select id from s3db_permission where shared_with = '".$shared_with."' and uid ".$GLOBALS['regexp']." '^".$uid."') or account_id = '1'";
	#$admins_query = "select account_id from s3db_account where account_type = 'a' or account_id = '1'";
	$admins_query = "select id from s3db_permission where shared_with = 'G1' and uid ".$GLOBALS['regexp']." '^U' or id in (select account_id from s3db_account where account_type = 'a')";
	
	#echo $admins_query;exit;
	$db->query($admins_query, __LINE__, __FILE__);
	#echo '<pre>';print_r($db);
	while($db->next_record())
	{
		
		#$admins[] = str_replace($GLOBALS['Did'].'/', '', $db->f('account_id'));
		$admins[] = $db->f('id');
	
	}
	#echo 'ola<pre>';print_r($admins);exit;
	return ($admins);

}
	function user_is_admin($user_id, $db)
{
	$admins = admins($_REQUEST['key'], $user_id, $db);
	
	if(in_array($user_id, $admins))
		return (True);
	else {
	
	$sql = "select account_group from s3db_account where account_id='".$user_id."'";

	#echo $sql;
	$db->query($sql, __LINE__, __FILE__);
	
	if($db->next_record())
		$group = $db->f('account_group');
	if($group=='a')
		return True;
		else	
		return False;
	}
		
}

function user_is_public($user_id, $db)
{
	$user_info = URI('U'.$user_id, $user_id, $db);
	if($user_info['account_type']=='p')
		return (true);
	else {
		#find the group of users called "public"
		#find all the users in that group
		#return true if this user in that group
		return (false);
	}
}
function user_already_exist($imp_user_id, $inputs, $db)
	{
		#$db = $_SESSION['db'];
		
		if ($imp_user_id!='') {
			$sql = "select account_id from s3db_account where account_lid='".$inputs['account_lid']."' and account_id!='".$imp_user_id."'";
		}
		else {
			$sql = "select account_id from s3db_account where account_lid='".$inputs['account_lid']."'";
		}
		
		
		$db->query($sql, __LINE__, __FILE__);
		#echo $sql;exit;
		if($db->next_record())
		{
			return True;
		}
		else 
		{
			return False;
		}
	}

function email_already_exist($Z)
	{#email_already_exist(compact('inputs', 'user_id' ,'db'))
	extract($Z);
		#$db = $_SESSION['db'];
		#echo '<pre>';print_r($inputs);
		if ($inputs['user_id']!='') {
			$sql = "select account_id from s3db_account where account_email='".$inputs['account_email']."' and account_id!='".$inputs['user_id']."'";
		}
		else {
			$sql = "select account_id from s3db_account where account_email='".$inputs['account_email']."'";
		}
		
		
		$db->query($sql, __LINE__, __FILE__);
		#echo $sql;exit;
		if($db->next_record())
		{
			return True;
		}
		else 
		{
			return False;
		}
	}

	function user_in_group($user_to_check,$group_info,$user_who_asks, $db)
	{
		$user_id = $user_who_asks;
		$sql = "select uid from s3db_permission where shared_with = 'G".$group_info['group_id']."'";
		$db->query($sql,__LINE__, __FILE__);
		while($db->next_record())
		{
			$uid = $db->f('uid');
			if(ereg('^U(.*)', $uid, $user_id))
					$account_ids[] = $user_id[1];
		}
		
		#$s3ql=compact('user_id','db');
		#$s3ql['select']='user_id';
		#$s3ql['from']='users';
		#$s3ql['where']['group_id']=$group_info['account_id'];
		#$s3ql['where']['user_id']=$user_to_check;

		
		#$group_users = S3QLaction($s3ql);
		
		#this returns true if the user is the owner of the group or he is admin; commented out because it is preventing owener of the group from adding himself to the group
		if($group_info['created_by']==$user_to_check || $user_to_check=='1') {
			return true;
		}
		
		if (is_array($account_ids) && !empty($account_ids)) {
		
		#$group_users = account_id_as_key($group_users);
		#$account_ids = array_keys($group_users);

		
		
		
		if (in_array($user_to_check, $account_ids)) {
			
			return true;

		}
		else {
			return False;
		}
		
		}
	}
function user_created_by_user($user_to_edit, $user_admin, $db)
{#user_created_by_user is a functionto check if a specific user that is admin, has permission to delete an account of another user. He should only delete accont that he created.

	$user_id = $user_admin;
	$s3ql=compact('user_id','db');
	$s3ql['select']='*';
	$s3ql['from']='users';
	$s3ql['where']['created_by']=$user_id;

	$my_users = S3QLaction($s3ql);
	
	#taking the accountid as key indexes makes it easier to check if specified user can be edited/deleted
	
	if ($user_to_edit==$user_id) { #its me, I can nuke myself :-)
			return (True);
		}
		elseif ($user_id=='1') {
			return (True);
		} 
	else {#its not me and i am not admin...did i create users?
		
		if (!is_array($my_users)) {
			return (False);
		}
		else {
			
			$my_users = account_id_as_key($my_users);
			$user_ids = array_keys($my_users);
			
			
			if (in_array($user_to_edit, $user_ids)) {#did I create it?
				return (True);
			}
			
			else {
				return (False);
			}
			}
	
	
	
		}
}

function is_user_exist($newuser, $db)
	{
		#$db = $_SESSION['db'];
		
		$sql = "Select account_id from s3db_account where account_lid='".$newuser['account_lid']."'";
		$db->query($sql, __LINE__, __FILE__);
		if($db->next_record())
		{
			return True;
		}
		else 
		{
			return False;
		}
	}

function access_key_exists($input_key, $db)

{

#$found_request = find_entry('access_keys', '*', 'key_id', array('key_id'=>$input_key, 'db'=>$db));
$found_request = get_entry('access_keys', 'account_id','key_id',$input_key, $db);

if($found_request!='') return True;
else return False;


}



function findUserResources($user_id, $resource_type, $db)
{$regexp = $GLOBALS['regexp'];
$s3codes = $GLOBALS['s3codes'];
$s3tables = $GLOBALS['s3tables'];
$s3ids = $GLOBALS['s3ids'];

$messages = $GLOBALS['message'];
	
	$table = $s3codes[$resource_type];
	if($table=='') return ($resource_type.' is not a valid resource code identifyer');
	$cols = $GLOBALS['dbstruct'][$table];
	
	#map resource to the right s3db table
	$table_id = $s3ids[$table];
	$table = $s3tables[$table];
	
	
	$sql = "select * from s3db_".$table." where ".$table_id." in (select id from s3db_permission where uid ".$regexp." '^".$resource_type."' and shared_with ".$regexp." '^U".$user_id."' and permission_level !".$regexp." '^0')";
	$db->query($sql, __LINE__, __FILE__);
	
	echo $sql;
	while($db->next_record())
		{
			$resultStr .= "\$data[] = Array(";
					
					
					
					foreach ($cols as $col)
						
					{
						#if($db->f($col)!='')
						{
						$resultStr .= "'".$col."'=>'".addslashes($db->f($col))."'";
						if($col != end($cols))
						$resultStr .= ",";
						}
					}
	
					
					$resultStr .= ");";
					
					
		
			
		
		}
		eval($resultStr);
		
		return ($data);


	
}

function findUserResourceIDS($user_id, $resource_type, $db)
{$regexp = $GLOBALS['regexp'];
$s3codes = $GLOBALS['s3codes'];
$s3tables = $GLOBALS['s3tables'];
$s3ids = $GLOBALS['s3ids'];

$messages = $GLOBALS['message'];
	
	$table = $s3codes[$resource_type];
	if($table=='') return ($resource_type.' is not a valid resource code identifyer');
	$cols = $GLOBALS['dbstruct'][$table];
	
	#map resource to the right s3db table
	$table_id = $s3ids[$table];
	$table = $s3tables[$table];
	
	
	$sql = "select id from s3db_permission where uid ".$regexp." '^".$resource_type."' and shared_with ".$regexp." '^U".$user_id."' and permission_level !".$regexp." '^0'";
	$db->query($sql, __LINE__, __FILE__);
	
	#echo $sql;
	while($db->next_record())
		{
			$resultStr .= "\$data[] = Array(";
					
					
					
					foreach ($cols as $col)
						
					{
						#if($db->f($col)!='')
						{
						$resultStr .= "'".$col."'=>'".addslashes($db->f($col))."'";
						if($col != end($cols))
						$resultStr .= ",";
						}
					}
	
					
					$resultStr .= ");";
					
					
		
			
		
		}
		eval($resultStr);
		
		return ($data);


	
}

function findUserProjects($user_id, $db)
{$regexp = $GLOBALS['regexp'];
$element = 'projects';
$ucode = strtoupper(substr($element, 0,1));
	
	if($user_id!='1')
	$sql = "select * from s3db_project where project_status = 'A' and project_id in  (".str_replace('*', 'substr(uid, 2, length(uid))',select(array('uid'=>'P', 'shared_with'=>'U'.$user_id)))." and permission_level ".$regexp." '^(1|2)')";
	else {
		$sql = "select project_id from s3db_project";
	}
	#echo $sql ;exit;
	$db->query($sql, __LINE__, __FILE__);
	
	while($db->next_record())
		{
			$projects[] = $db->f('project_id');
		}
	
	#this will only catch non -remote. Go for the remotes
	$permissions_sql = str_replace('*', 'substr(uid, 2, length(uid)) as uid', select(array('uid'=>'P', 'shared_with'=>'U'.$user_id))." and permission_level ".$regexp." '^(1|2)'");
	$remote_permissions_query = str_replace("uid ".$regexp." '".$ucode."'", "uid ".$regexp." '".$ucode."http://|".$ucode."https://|".$ucode."D([0-9]+)_'", $permissions_sql);
	#echo $remote_permissions_query;
	$db->query($remote_permissions_query, __LINE__, __FILE__);
	
	while($db->next_record())
		{
			$remote = $db->f('uid');
			if(is_array($projects))
			array_push($projects, $remote);
			else
				$projects[]=$remote;
			
		}

	return ($projects);
	
}


function findUserProjects1($user_id, $db)
{	$regexp = $GLOBALS['regexp'];

	
		if ($user_id=='1') {
			$sql = "select project_id from s3db_project where project_status = 'A'";
		}
		else {
			
			$groups = findUserGroups($user_id, $db);
			if(is_array($groups))
			$groups_list = create_element_list($groups);
			
			
			
			$sql = "select distinct(project_id) as project_id from s3db_project where project_id!='0' and project_status = 'A' and (project_id in (select id from s3db_permission where uid ".$regexp." '^P' and shared_with ".$regexp." '^U".$user_id."' and permission_level ".$regexp." '^2') or (project_id in (select id from s3db_permission where uid ".$regexp." '^P' and shared_with ".$regexp." '^U".$user_id."' and permission_level ".$regexp." '^1') and created_by = '".$user_id."'))";
	
		}


		#echo $sql;exit;
		$db->query($sql, __LINE__, __FILE__);
		while($db->next_record())
		{
			$projects[] = $db->f('project_id');
		}
	return ($projects);

}

function findGroupProjects($group_id, $db)
{$regexp = $GLOBALS['regexp'];

$sql = "select distinct(project_id) as project_id from s3db_project where project_id in (select id from s3db_permission where shared_with = 'G".$group_id."' and permission_level !".$regexp."'^0' and uid ".$regexp." '^P')";

$db->query($sql, __LINE__, __FILE__);
		while($db->next_record())
		{
			$projects[] = $db->f('project_id');
		}
	return ($projects);
}

function findUserGroups($user_id, $db)
{$regexp = $GLOBALS['regexp'];

$sql = "select distinct(group_id) as group_id from s3db_account_group where account_id = '".$user_id."'";
$db->query($sql, __LINE__, __FILE__);
		while($db->next_record())
		{
			$groups[] = $db->f('group_id');
		}
	return ($groups);

	
}

function findUserRules($user_id, $db)

{
$regexp = $GLOBALS['regexp'];

#start with finding projects where user is allowed;
$user_projects = findUserProjects($user_id, $db);

#build the query for rules;
#$projectlist=create_project_id_list($user_projects);
$projectlist=create_list($user_projects);
$projectPermissionList = create_element_list($user_projects);
$permissionlist = create_permission_list($user_projects);

#$sql = "select rule_id from s3db_rule where (project_id ".$regexp." '".$projectlist."' or permission ".$regexp." '".$permissionlist."')";
$sql = "select distinct(rule_id) from s3db_rule where (project_id regexp '".$projectlist."' or project_id in (select id from s3db_permission where shared_with regexp '^P".$projectPermissionList."' and uid regexp '^R' and permission_level regexp '^(1|2)') or rule_id in (select id from s3db_permission where shared_with regexp 'U".$user_id."' and uid regexp '^R' and permission_level regexp '^(1|2)'))";

$db->query($sql, __LINE__, __FILE__);
		while($db->next_record())
		{
			$rules[] = $db->f('rule_id');
		}

return ($rules);
}

function findUserClasses($user_id, $db)

{
$regexp = $GLOBALS['regexp'];

#start with finding projects where user is allowed;
$user_projects = findUserProjects($user_id, $db);

#build the query for rules;
$projectlist=create_list($user_projects);
$permissionlist = create_permission_list($user_projects);
#$permissionlist = create_element_list($user_projects);

#$sql = "select distinct(resource_id) as resource_id from s3db_resource where resource_id!='' and iid = '0' and (project_id regexp '^".$projectlist."' or resource_id in (select id from s3db_permission where shared_with regexp '^P".$permissionlist."' and uid regexp '^C' and permission_level regexp '^(1|2)') or resource_id in (select id from s3db_permission where shared_with regexp 'U".$user_id."$' and uid regexp '^C' and permission_level regexp '^(1|2)'))";
$sql = "select distinct(resource_id) as resource_id from s3db_resource, s3db_rule where iid = '0' and subject = entity and object = 'UID' and s3db_rule.project_id = s3db_resource.project_id and (s3db_rule.project_id ".$regexp." '".$projectlist."' or s3db_rule.permission ".$regexp." '".$permissionlist."')";

$db->query($sql, __LINE__, __FILE__);
		while($db->next_record())
		{
			$classes[] = $db->f('resource_id');
		}

return ($classes);
}

function findUserInstances($user_id, $db)

{
$regexp = $GLOBALS['regexp'];

#start with finding projects where user is allowed;
$user_classes = findUserClasses($user_id, $db);
$classlist = create_list($user_classes);

#$sql = "select distinct(resource_id) as resource_id from s3db_resource where resource_class_id ".$regexp." '".$classlist;
$sql = "select distinct(resource_id) as resource_id from s3db_resource where (resource_class_id ".$regexp." '".$classlist."' or resource_id in (select id from s3db_permission where shared_with regexp 'U".$user_id."$' and uid regexp '^I' and permission_level regexp '^(1|2)'))";
#echo $sql;
$db->query($sql, __LINE__, __FILE__);
		while($db->next_record())
		{
			$instances[] = $db->f('resource_id');
		}

return ($instances);
}

function findClassInstances($class_id, $db)
{
	if($class_id=='') return ("Class_id missing");
	
	$sql = "select * from s3db_resource where resource_class_id = '".$class_id."' and iid='1' and status = 'A'";
	
	$db->query($sql, __LINE__, __FILE__);
		
		while($db->next_record())
		{
			$instances[] = $db->f('resource_id');
		}

return ($instances);
}

function findClassRules($class_id, $db)
{
	if($class_id=='') return ("Class_id missing");
	
	$sql = "select * from s3db_rule where (subject_id = '".$class_id."' or object_id = '".$class_id."') and status = 'A'";
	
	$db->query($sql, __LINE__, __FILE__);
		
		while($db->next_record())
		{
			$rules[] = $db->f('rule_id');
		}

return ($rules);
}

function findNonSharedSubjectClass($C)
{	extract($C);
	
	$sql = "select resource_id, rule_id, entity, subject, verb, object, class_id, iid from s3db_rule, s3db_resource where s3db_rule.project_id = s3db_resource.project_id and entity=subject and resource_id = '".$class_id."' and iid=0";

	$db->query($sql, __LINE__, __FILE__);
		while($db->next_record())
		{
			$rule_classes[] = array('classes'=>$db->f('resource_id'), #the deformed hybrid :-)
								'rules'=>$db->f('rule_id'));
		}

		return ($rule_classes);



}

function has_permission($permission_info, $db,$user_id=0, $model='nsy')
{#function has permission retrieves the values for a speciific user and a specific resource from the permissions table
#Syntax: has_permission(compact('uid', 'shared_with'), $db);
#echo '<pre>';print_r($permission_info);

switch ($permission_info['stream']) {
	case "upstream":
		$sql = "select * from s3db_permission where uid = '".$permission_info['shared_with']."' and shared_with = '".$permission_info['uid']."'";
	break;
	case "downstream":
		$sql = "select * from s3db_permission where uid = '".$permission_info['uid']."' and shared_with = '".$permission_info['shared_with']."'";
	break;
	default :
		$sql = "select * from s3db_permission where uid = '".$permission_info['uid']."' and shared_with = '".$permission_info['shared_with']."'";
	break;
}

#echo $sql;
$db->query($sql, __LINE__, __FILE__);
		if($db->next_record())
		{
		$p = $db->f('permission_level');
		$p = str_replace(array(0,1,2), str_split($model), $p);
		return 	$p;
		#return $db->f('permission_level');	
		}
		elseif($permission_info['shared_with']=='1'){
		$p = str_replace(array(0,1,2), str_split($model),'222');
		return ($p);
		}
		else {
			return (False);
		}
}

function statement_exists($S)
	{
		extract($S);
		
		if(is_array($statement_info)) extract($statement_info);
		if(is_array($info)) extract($info);
		$instance_id = ($instance_id!='')?$instance_id:$resource_id;
		$statement_id = ($statement_id!='')?$GLOBALS['regexp'].'^'.$statement_id.'$':"!=''";

		$sql = "select statement_id from s3db_statement where resource_id='".$instance_id."' and rule_id='".$rule_id."' and value='".$value."' and statement_id".$statement_id;
		#echo $sql;exit;
		$db->query($sql, __LINE__, __FILE__);
		if($db->next_record())
			return True;
		else		
			return False;
	}


function fastStatementId($Z)
{#this function finds the statement tha identifies the creation of instance (in rule where object = UID. Either instance_id or instance_info should come in $Z and, of course, $db object
extract($Z);

$instance_id = ($instance_id!='')?$instance_id:$instance_info['instance_id'];

$sql = "select * from s3db_statement where rule_id in (select rule_id from s3db_rule where object='UID' and subject = (select entity from s3db_resource where resource_id = '".$instance_id."' and iid = '1')) and resource_id = '".$instance_id."'";

$db->query($sql, __LINE__, __FILE__);
	if($db->next_record())
	return ($db->f('statement_id'));;	
	
}

function user_type($user_id, $db)
{
	$sql = "select account_type from s3db_account where account_id = '".$user_id."'";
	$db->query($sql, __LINE__, __FILE__);
	if($db->next_record())
	return ($db->f('account_type'));	
	

}

function findUserInheritedResources($element, $user_id, $db)
{#function findUserInheritedResources($element, $user_id, $db) detects all ids, of type $element, where user has "select"  access.
	
	switch ($element) {
		case 'user':#is the special cas of deployment, find all resources that user owns in this deployments
		$ids = array($user_id);
		#$ids = findUserDeploymnets($user_id, $db); In the future, this might be relevant. 
		break;
		case 'project':
		$ids = findUserProjects($user_id, $db);
		break;
		case 'rule':
		$ids = findUserRules($user_id, $db);
		break;
		case 'class':
		$ids = findUserClasses($user_id, $db);
		break;
		case 'instance':
		$ids = findUserInstances($user_id, $db);
		break;
		
	}
	return ($ids);
	
}

//function findDidUrl($Did, $db)
//{
//	
//	
//	$sql = "select * from s3db_deployment where deployment_id = '".substr($Did,1, strlen($Did))."'";
//	#echo $sql;
//	$db->query($sql, __LINE__, __FILE__);
//	if($db->next_record())
//	{
//	$did_url = array('url'=>$db->f('url'),
//						'checked_on'=>$db->f('checked_on'),
//						'checked_valid'=>$db->f('checked_valid'),
//						'publickey'=>$db->f('publickey'));
//	
//	}
//	
//	return ($did_url);	
//	
//}
//
//function insertDidUrl($data, $db)
//{	#echo '<pre>';print_r($data);
//	$sql = "insert into s3db_deployment (deployment_id, url, publickey,checked_on, checked_valid) values ('".trim($data['deployment_id'])."', '".trim($data['url'])."', '".trim($data['publicKey'])."', now(), '".$data['checked_valid']."')";
//	#echo $sql;exit;
//	$db->query($sql, __LINE__, __FILE__);
//	$dbdata = get_object_vars($db);
//	#echo '<pre>';print_r($dbdata);exit;
//	if($dbdata['Errno']=='0')
//		return (True);
//	else {
//		return (False);
//	}
//}

function userAllowedIDS($inputs)
{$regexp = $GLOBALS['regexp'];
	#find all resources of type uid where "shared_with" has access
extract($inputs);
$U = substr($inputs['uid'],0,1);

##First look at id that are shared with the user
$sql = "select id,permission_level from s3db_permission where shared_with = 'U".$user_id."' and uid ".$regexp." '^".$U."'";

$db->query($sql,__LINE__,__FILE__);
while ($db->next_record()) {
	$id[] = array('id'=>$db->f('id'), 'permission_level'=>$db->f('permission_level'));
}

##Now look for the ID that are shared with groups where the user belongs to
$groups = findUserGroupsstr($user_id, $db);
$sql = "select id,permission_level from s3db_permission where shared_with ".$GLOBALS['regexp']." 'G'".$groups." and uid ".$regexp." '^".$U."'";
while ($db->next_record()) {
	$id[] = array('id'=>$db->f('id'), 'permission_level'=>$db->f('permission_level'));
}

##This part is a bit trickier and it has to do with the permission inheritance - given that user has permission on a project, into which resources is that permission inherited?


#echo '<pre>';print_r($id);exit;

#looking for upstream or downstraem?
$wanted=($_REQUEST['wanted']!='')?$_REQUEST['wanted']:$GLOBALS['s3codes'][strtoupper(substr($uid,0,1))];
#echo in_array(array_search($wanted, $GLOBALS['plurals']), array_keys($GLOBALS['COREids']));

#translate wanted as searched parameter (the one in id) and the non-wanted into shared_with parameter.
#score resources method 2
$max_dist_from_statement = array('G'=>5, 'U'=>4,'P'=>3, 'C'=>2, 'R'=>2,'I'=>1, 'S'=>0);
$max_dist = $max_dist_from_statement;

#max dist is the max number of iterations to be performed on permisions
#echo '<pre>';print_r($max_dist);
#element we are looking for
$S = substr($inputs['shared_with'],0,1);



#the number of iteration will be difference between them
$I = abs($max_dist[$S]-$max_dist[$U]);

for ($i=0; $i < $I; $i++) {#remember we are starting in 0!!!
	
	if($stream=='upstream')
	{$uid = 'uid';
	$shared_with = 'shared_with';
	if($strictsharedwith) $regexpA['uid'] = '=';
	else  $regexpA['uid'] = $GLOBALS['regexp'];
			
	if($strictuid) $regexpA['shared_with'] = '=';
	else  $regexpA['shared_with'] = $GLOBALS['regexp'];
	}
	else {
	
	$uid = 'shared_with';
	$shared_with = 'uid';
	if($strictsharedwith) $regexpA['shared_with'] = '=';
	else  $regexpA['shared_with'] = $GLOBALS['regexp'];
			
	if($strictuid) $regexpA['uid'] = '=';
	else  $regexpA['uid'] = $GLOBALS['regexp'];
	
	}

	



		
	if($sql=='')
	{	
		
		##Select elements immediate to the user
		$sql .= "select id from s3db_permission where shared_with = ";
		if(strlen($inputs[$shared_with])>1)
		$sql .=  $shared_with." ".$regexpA[$shared_with]." '".$inputs[$shared_with]."' and ";
		$sql .= "(".$uid." ".$regexpA[$uid]." '".$inputs[$uid]."'";
	}
	else
	{	
		$sql .= " or ".$uid." in (select ".$shared_with." from s3db_permission where ".$uid." ".$regexpA[$uid]." '".$inputs[$uid]."'";
	}
	}


#not put in as many ending parenthesis as iterations
$sql .= str_repeat(")", $I);
#echo $stream;
#echo $sql;
return ($sql);


}
function select($inputs,$starter=true)
{$regexp = $GLOBALS['regexp'];
	#find all resources of type uid where "shared_with" has access

extract($inputs);
#looking for upstream or downstraem?
$wanted=($_REQUEST['wanted']!='')?$_REQUEST['wanted']:$GLOBALS['s3codes'][strtoupper(substr($uid,0,1))];
#echo in_array(array_search($wanted, $GLOBALS['plurals']), array_keys($GLOBALS['COREids']));

#translate wanted as searched parameter (the one in id) and the non-wanted into shared_with parameter.
#score resources method 2
$max_dist_from_statement = array('G'=>5, 'U'=>4,'P'=>3, 'C'=>2, 'R'=>2,'I'=>1, 'S'=>0);
$max_dist = $max_dist_from_statement;

#max dist is the max number of iterations to be performed on permisions
#echo '<pre>';print_r($max_dist);
#element we are looking for
$S = substr($inputs['shared_with'],0,1);
$U = substr($inputs['uid'],0,1);

#Trim the output
if(!$select) 
	$select = '*';

#the number of iteration will be difference between them
$I = abs($max_dist[$S]-$max_dist[$U]);

for ($i=0; $i < $I; $i++) {#remember we are starting in 0!!!
	
	if($stream=='downstream')
	{$uid = 'uid';
	$shared_with = 'shared_with';
	if($strictsharedwith) $regexpA['uid'] = '=';
	else  $regexpA['uid'] = $GLOBALS['regexp'];
			
	if($strictuid) $regexpA['shared_with'] = '=';
	else  $regexpA['shared_with'] = $GLOBALS['regexp'];
	}
	else {
	
	$uid = 'shared_with';
	$shared_with = 'uid';
	if($strictsharedwith) $regexpA['shared_with'] = '=';
	else  $regexpA['shared_with'] = $GLOBALS['regexp'];
			
	if($strictuid) $regexpA['uid'] = '=';
	else  $regexpA['uid'] = $GLOBALS['regexp'];
	
	}


		
	if($sql=='')
	{	
		if($starter)
		$sql .= "select ".$select." from s3db_permission where ";
		##Commented on 8 jan 2009... I no longer see the purpose of this piece of code :-(
		#if(strlen($inputs[$shared_with])>1)
		#$sql .=  $shared_with." ".$regexpA[$shared_with]." '".$inputs[$shared_with]."' and ";
		$sql .= "(".$uid." ".$regexpA[$uid]." '".$inputs[$uid]."'";
	}
	else
	{	
		$sql .= " or ".$uid." in (select ".$shared_with." from s3db_permission where ".$uid." ".$regexpA[$uid]." '".$inputs[$uid]."'";
	}
	}


#not put in as many ending parenthesis as iterations
$sql .= str_repeat(")", $I);

##Added 8 Jan 2009 to retrievee uid where user ias allowed as well
#$sql .= " or shared_with = '".$inputs['shared_with']."'";

#echo $stream;
#echo $sql;exit;
return ($sql);


}

function select1($inputs)
{$regexp = $GLOBALS['regexp'];
	#find all resources of type uid where "shared_with" has access

extract($inputs);
#looking for upstream or downstraem?
$wanted=($_REQUEST['wanted']!='')?$_REQUEST['wanted']:$GLOBALS['s3codes'][strtoupper(substr($uid,0,1))];
#echo in_array(array_search($wanted, $GLOBALS['plurals']), array_keys($GLOBALS['COREids']));

#translate wanted as searched parameter (the one in id) and the non-wanted into shared_with parameter.
#score resources method 2
$max_dist_from_statement = array('G'=>5, 'U'=>4,'P'=>3, 'C'=>2, 'R'=>2,'I'=>1, 'S'=>0);
$max_dist = $max_dist_from_statement;

#max dist is the max number of iterations to be performed on permisions
#echo '<pre>';print_r($max_dist);
#element we are looking for
$S = substr($inputs['shared_with'],0,1);
$U = substr($inputs['uid'],0,1);


#the number of iteration will be difference between them
$I = abs($max_dist[$S]-$max_dist[$U]);

for ($i=0; $i < $I; $i++) {#remember we are starting in 0!!!
	
	if($stream=='upstream')
	{$uid = 'uid';
	$shared_with = 'shared_with';
	if($strictsharedwith) $regexpA['uid'] = '=';
	else  $regexpA['uid'] = $GLOBALS['regexp'];
			
	if($strictuid) $regexpA['shared_with'] = '=';
	else  $regexpA['shared_with'] = $GLOBALS['regexp'];
	}
	else {
	
	$uid = 'shared_with';
	$shared_with = 'uid';
	if($strictsharedwith) $regexpA['shared_with'] = '=';
	else  $regexpA['shared_with'] = $GLOBALS['regexp'];
			
	if($strictuid) $regexpA['uid'] = '=';
	else  $regexpA['uid'] = $GLOBALS['regexp'];
	
	}


		
	if($sql=='')
	{	
		
		$sql .= "select * from s3db_permission where (".$uid." ".$regexpA['uid']." '".$inputs[$uid]."') or ";
		#$sql .= "select * from s3db_permission where ";
		if(strlen($inputs[$shared_with])>1)
		$sql .=  $shared_with." ".$regexpA[$shared_with]." '".$inputs[$shared_with]."' and ";
		$sql .= "(".$uid." ".$regexpA[$uid]." '".$inputs[$uid]."'";
	}
	else
	{	
		$sql .= " or ".$uid." in (select ".$shared_with." from s3db_permission where ".$uid." ".$regexpA[$uid]." '".$inputs[$uid]."'";
	}
	}


#not put in as many ending parenthesis as iterations
$sql .= str_repeat(")", $I);
#echo $stream;
#echo $sql;
return ($sql);


}

function objectResourcANDvalueInstance($R)
{#$R must contain at least rule_id, value, user_id, db
#functions returns true if object is resource AND value is instance of that object OR if  object is not resource and there is no
extract($R);

$rule_info=URIinfo('R'.$rule_id, $user_id, $key, $db);

$object_id = $rule_info['object_id'];
if($object_id=='')
	return (True);
else {
	
	$intance_info = URIinfo('I'.$value, $user_id, $key, $db);
	
	if(!is_array($intance_info) || empty($intance_info) || $intance_info['class_id']!=$object_id)
		return (False);
		else {
			return (True);
		}
}


}

function getRuleValidation($rule_id, $key, $user_id, $db)
{

$rule_info=URIinfo('R'.$rule_id, $user_id, $key, $db);
return ($rule_info['validation']);

}

function findPassword($user_id, $db)
{
$sql = "select account_pwd from s3db_account where account_id = '".$user_id."'";
$db->query($sql, __FILE__, __LINE__);
if ($db->next_record()) {
	
	return $db->f('account_pwd');
}
}

function isLocal($uid, $db)
{
	$letter = strtoupper(substr($uid,0,1));
    $core=$GLOBALS['s3codes'][$letter];
	$table = $GLOBALS['s3tables'][$core];
	
	$sql="select * from s3db_".$table." where ".$GLOBALS['s3ids'][$core]."='".ereg_replace('^'.$letter, '', $uid)."'";
	#echo $sql;exit;
	

	$db->query($sql, __FILE__, __LINE__);
	if ($db->next_record()) {
	
	return true;
	}
	else {
		return (False);
	}
}
	
function publicUserId($db)
{
	$sql = "select * from s3db_account where account_lid = 'public' and account_type = 'p'";

	$db->query($sql,__LINE__,__FILE__);
	if($db->next_record())
	{$account = Array('account_id'=>$db->f('account_id'),
				      'account_lid'=>$db->f('account_lid'),	
				      'account_pwd'=>$db->f('account_pwd'),	
				      'account_uname'=>$db->f('account_uname'),
					  'account_group'=>$db->f('account_group'),	
				      'account_email'=>$db->f('account_email'),	
				      'account_phone'=>$db->f('account_phone'),	
				      'account_type'=>$db->f('account_type'),	
				      'account_addr_id'=>$db->f('account_addr_id'),	
				      'account_status'=>$db->f('account_status'));
	}
	return ($account);
}

function findUserGroupsStr($user_id, $db)
{
	$sql = "select id from s3db_permission where shared_with ".$GLOBALS['regexp']." '^G' and uid = 'U".$user_id."'";
	$db->query($sql,__LINE__,__FILE__);
	while ($db->next_record()) {
		if($groups!='') $groups .='|';
		$groups .= $db->f('id');
	}
	return ($groups);
}

function createdBy($uid,$db)
	{
			$letter = strtoupper(substr($uid,0,1));
			$uid_info = uid($uid);
			$id = ereg_replace('^'.$letter, '', $uid_info['uid']);
			
			$sql = "select created_by from s3db_".$GLOBALS['s3tables'][$GLOBALS['s3codes'][$letter]]." where ".$GLOBALS['s3ids'][$GLOBALS['s3codes'][$letter]]." = '".$id."' limit 1";

				
			$db->query($sql, __LINE__,__FILE__);
				
				if($db->next_record())
					return ($db->f('created_by'));
	}

function RuleHasObjectId($statement_id, $db)
{
	$sql = "select object_id from s3db_rule where rule_id in (select rule_id from s3db_statement where statement_id = '".$statement_id."')";
	#echo $sql.'<br />';
	$db->query($sql);
	if($db->next_record())
	{$d = $db->f('object_id');
		if($d!='')
		return (True);
		
	}
	return (False);
	
}
?>