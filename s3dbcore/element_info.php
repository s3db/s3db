<?php
/**
	
	* @author Helena F Deus <helenadeus@gmail.com>
	* @license http://www.gnu.org/copyleft/gpl.html GNU General Public License
	* @package S3DB http://www.s3db.org
*/



function get_rule_info($element_id)
	{
		$db = $_SESSION['db'];
	$sql = "select * from s3db_rule where rule_id='".$element_id."'";
		$db->query($sql, __LINE__, __FILE__);

		if($db->next_record())
		{
			$rule = Array('entity_id'=>$db->f('entity_id'),
					'rule_id'=>$db->f('rule_id'),
					'project_id'=>$db->f('project_id'),
					'subject'=>$db->f('subject'),	
				    'subject_id'=>$db->f('subject_id'),	
					'verb'=>$db->f('verb'),	
					'verb_id'=>$db->f('verb_id'),	
					'object'=>$db->f('object'),	
				    'object_id'=>$db->f('object_id'),	
					'notes'=>$db->f('notes'),	
					'created_on'=>substr($db->f('created_on'), 0, 19),	
					'created_by'=>$db->f('created_by'),	
					'modified_on'=>$db->f('modified_on'),	
					'modified_by'=>$db->f('modified_by'),
					'permission'=>$db->f('permission'));
		}
		return $rule;
	}


function get_group_info($account_id)
	{
		$db =$_SESSION['db'];
		$sql = "select account_id, account_lid from s3db_account where account_id ='".$account_id."'";
		$db->query($sql, __LINE__, __FILE__);
		if($db->next_record())
		{
			$group = Array('account_id'=>$db->f('account_id'), 
					'account_lid'=>$db->f('account_lid'));
		}
		return $group;
	}



function get_resource_info($id, $iid)
	{
		$db= $_SESSION['db'];
	    $sql ="select * from s3db_resource where resource_id='".$id."' and iid=".$iid."";
		$db->query($sql, __LINE__, __FILE__);
		if($db->next_record())
		{
			$resource = Array('resource_id'=>$db->f('resource_id'),
					 'project_id'=>$db->f('project_id'),
					//'owner'=>$db->f('owner'),
					'uid'=>$db->f('uid'),
					'entity'=>$db->f('entity'),
					'resource_class_id'=>$db->f('resource_class_id'),
					'notes'=>$db->f('notes'),
					'created_on'=>substr($db->f('created_on'), 0, 19),
					'created_by'=>$db->f('created_by'),	
					'modified_on'=>substr($db->f('modified_on'), 0, 19),
					'modified_by'=>$db->f('modified_by'));
		}
		return $resource;
	}

function find_rule_id($subject, $verb, $object, $project_id, $db)
	{
		
		if($db=='') $db = $_SESSION['db'];
	
		
		if ($GLOBALS['s3db_info']['server']['db']['db_type'] == 'mysql')
		$sql = "select rule_id from s3db_rule where subject = '".$subject."' and verb='".$verb."' and object='".$object."' and permission regexp '(".$project_id."$|".$project_id."_)'";
		else
		$sql = "select rule_id from s3db_rule where subject = '".$subject."' and verb='".$verb."' and object='".$object."' and permission ~~ '%".$project_id."\\\_%'";

	#echo $sql;
		$db->query($sql, __LINE__, __FILE__);
		if($db->next_record())
			return $db->f('rule_id');
		
		
		

	}

function find_statement_id($rule_id, $UID, $project_id)

{

$db = $_SESSION['db'];

$sql = "select statement_id from s3db_statement where rule_id='".$rule_id."' and resource_id='".$UID."'";

#echo $sql.'<BR>';
$db->query($sql, __LINE__, __FILE__);

while($db->next_record())
		{
		$statement_id[] = $db->f('statement_id');

		}
	#echo $statement_id;
	return $statement_id;


}


function find_owner_project_id($element, $element_id, $db)

{
	if ($db=='') $db = $_SESSION['db'];
    

		$sql = "select project_id from s3db_".$element." where ".$element."_id='".$element_id."'";
		
		$db->query($sql, __LINE__, __FILE__);
		
		if($db->next_record())
			return $db->f('project_id');
	





}

function get_statement_info_by_rule($rule_id, $UID, $project_id)
	{
		$db= $_SESSION['db'];
		$sql ="select * from s3db_statement where rule_id='".$rule_id."' and resource_id='".$UID."'";
		#$sql ="select * from s3db_statement where rule_id=".$rule_id." and resource_id=".$UID."";
		
		#echo $sql;
		$db->query($sql, __LINE__, __FILE__);
		while($db->next_record())
		{
			$statement = Array('statement_id'=>$db->f('statement_id'),
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
		return $statement;
	}

function get_statement_info($S)
	{
		extract($S);
		#echo '<pre>';print_r($S);
		#$db= $_SESSION['db'];
		$sql ="select r.subject, r.verb, r.object, s.statement_id, s.project_id, s.resource_id, s.rule_id, s.value, s.file_name, s.notes, s.created_on, s.created_by, s.modified_by, s.modified_on from s3db_rule as r, s3db_statement as s where r.rule_id = s.rule_id and s.statement_id='".$statement_id."'";
		$db->query($sql, __LINE__, __FILE__);
		if($db->next_record())
		{
			$statement = Array('statement_id'=>$db->f('statement_id'),
					 'project_id'=>$db->f('project_id'),
					 'resource_id'=>$db->f('resource_id'),
					'rule_id'=>$db->f('rule_id'),
					//'owner'=>$db->f('owner'),
					'subject'=>$db->f('subject'),
					'verb'=>$db->f('verb'),
					'object'=>$db->f('object'),
					'value'=>$db->f('value'),
					'file_name'=>$db->f('file_name'),
					'notes'=>$db->f('notes'),
					'created_on'=>$db->f('created_on'),
					'created_by'=>$db->f('created_by'),	
					'modified_on'=>$db->f('modified_on'),
					'modified_by'=>$db->f('modified_by'));
		}
		return $statement;
	}
	

	function get_statement_info_editstat($id)
	{
		$db= $_SESSION['db'];
		$sql ="select r.subject, r.verb, r.object, s.statement_id, s.project_id, s.resource_id, s.rule_id, s.value, s.file_name, s.notes, s.created_on, s.created_by, s.modified_by, s.modified_on from s3db_rule as r, s3db_statement as s where r.rule_id = s.rule_id and s.statement_id='".$id."'";
		//$sql ="select * from s3db_statement where statement_id='".$id."'";
		$db->query($sql, __LINE__, __FILE__);
		if($db->next_record())
		{
			$statement = Array('statement_id'=>$db->f('statement_id'),
					 'project_id'=>$db->f('project_id'),
					'rule_id'=>$db->f('rule_id'),
					'resource_id'=>$db->f('resource_id'),
					'subject'=>$db->f('subject'),
					'verb'=>$db->f('verb'),
					'object'=>$db->f('object'),
					'value'=>$db->f('value'),
					'file_name'=>$db->f('file_name'),
					'notes'=>$db->f('notes'),
					'created_on'=>$db->f('created_on'),
					'created_by'=>$db->f('created_by'),	
					'modified_on'=>$db->f('modified_on'),
					'modified_by'=>$db->f('modified_by'));
		}
		return $statement;
	}	


function get_statement_info_from_ID($statement_id, $project_id)
	{
		$db= $_SESSION['db'];
		$sql ="select * from s3db_statement where rule_id='".$rule_id."' and resource_id='".$UID."'";
		#$sql ="select * from s3db_statement where rule_id=".$rule_id." and resource_id=".$UID."";
		$db->query($sql, __LINE__, __FILE__);
		while($db->next_record())
		{
			$statement = Array('statement_id'=>$db->f('statement_id'),
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
		return $statement;
	}

function find_object_info($object, $project_id)

{
		$shared_rules = list_shared_rules ($project_id, '', $object);
		
		#echo '<pre>'.$object;print_r($shared_rules);
		#When looking at the shared rules, find which one hve UID as object, those are the ones that are reosurces
		
		if ($object == $shared_rules[0]['subject'])
			return $shared_rules[0];

}
function get_user_short_info($id)
	{
		$db= $_SESSION['db'];
		$sql ="select * from s3db_account where account_id='".$id."'";
		$db->query($sql, __LINE__, __FILE__);
		if($db->next_record())
		{
			$user = Array('account_id'=>$db->f('account_id'),
					'account_lid'=>$db->f('account_lid'),
					'account_uname'=>$db->f('account_uname'),
					'account_group'=>$db->f('account_group'));
		}
		return $user;
	}

function find_login_id($account_id, $db)
{
	$sql = "select account_lid from s3db_account where s3db_account.account_id='".$account_id."'";

	if($db->next_record())
			{
				$login_id = $db->f('account_lid');
			}
	return $login_id;
}

function get_user_info($account_id)
	{
		
		$db = $_SESSION['db'];
		
		$sql = "select * from s3db_account where s3db_account.account_id='".$account_id."'";
		
		$db->query($sql, __LINE__, __FILE__);
		
		//$user = '';	
	    if($db->next_record())
		{
			$account = Array('account_id'=>$db->f('account_id'),
				      'account_lid'=>$db->f('account_lid'),	
				      'account_pwd'=>$db->f('account_pwd'),	
				      'account_uname'=>$db->f('account_uname'),
					  'account_group'=>$db->f('account_group'),	
				      'account_email'=>$db->f('account_email'),	
				      'account_phone'=>$db->f('account_phone'),	
				      'account_type'=>$db->f('account_type'),	
				      'account_addr_id'=>$db->f('account_addr_id'),	
				      'account_status'=>$db->f('account_status'));
			
			
			if($account['account_addr_id'] > 0)
			{	
				$sql = "select * from s3db_addr where addr_id='".$account['account_addr_id']."'";
				$db->query($sql, __LINE__, __FILE__);
				if($db->next_record())
				{
					$addr = Array ('addr1'=>$db->f('addr1'),	
				      			'addr2'=>$db->f('addr2'),	
				      			'city'=>$db->f('city'),	
				      			'state'=>$db->f('state'),	
				     			'postal_code'=>$db->f('postal_code'),	
				      			'country'=>$db->f('country'));
				}
			}
			if(!is_array($addr))
			{
				$addr = Array('addr1'=>'', 
					      'addr2'=>'',	
					      'city'=>'',	
					      'state'=>'',	
					      'postal_code'=>'',	
					      'country'=>'');	
			}
		
		} 
		
		$user = array_merge($account, $addr);
		
		//print_r($user);
		return $user;	 
	}
	

	function get_project_info($project_id)
	{
		$db = $_SESSION['db'];
		$sql = "select * from s3db_project where project_id='".$project_id."'";
		$db->query($sql, __LINE__, __FILE__);
		if($db->next_record())
		{
			$project_info=Array('project_id'=>$db->f('project_id'),
					   'project_name'=>$db->f('project_name'),
					   'project_owner'=>$db->f('project_owner'),
					   'project_description'=>$db->f('project_description'),
					   'project_status'=>$db->f('project_status'),
					   'created_on'=>$db->f('created_on'),
					   'created_by'=>$db->f('created_by'),
					   'modified_on'=>$db->f('modified_on'),
					   'modified_by'=>$db->f('modified_by'));

		} 
		return $project_info;
	}
	

	
	function get_project_resources($project_id)
        {
                $db = $_SESSION['db'];
             
				if ($GLOBALS['s3db_info']['server']['db']['db_type']=='mysql')
				$sql = "select distinct entity, resource_id from s3db_resource where iid='0' and project_id='".$project_id."' order by entity";
				else
				$sql = "select distinct on (entity) entity, resource_id from s3db_resource where iid='0' and project_id='".$project_id."' order by entity";
               
				$db->query($sql, __LINE__, __FILE__);
				 while($db->next_record())
                {
                        $resources[] = Array('resource_id'=>$db->f('resource_id'),
						'entity'=>$db->f('entity'));

                }
				
				
				
		

				//echo $sql;
                
               
                //print_r($resources);
                return $resources;
        }

	function get_project_rules($P)
	{
		extract ($P);
		
		if ($GLOBALS['s3db_info']['server']['db']['db_type'] == 'mysql')
		$regexp = "regexp '(".$project_id."$|".$project_id."_)'";
		else
		$regexp = "~~ '%".$project_id."\\\_%'";
		
		#$db = $_SESSION['db'];
		
		
		//$sql = "select * from s3db_rule where object !='UID' and rule_id in (select distinct rule_id from s3db_statement) order by rule_id";
		//$sql = "select * from s3db_rule where rule_id in (select distinct rule_id from s3db_statement) order by rule_id";
		$sql = "select * from s3db_rule where permission ".$regexp."";
		//echo $sql;
		$db->query($sql, __LINE__, __FILE__);
		while($db->next_record())
		{
			$rules[] = Array('rule_id'=>$db->f('rule_id'),
					//'owner'=>$db->f('owner'),
					'resource_id'=>$db->f('resource_id'),
					'subject'=>$db->f('subject'),
					'verb'=>$db->f('verb'),
					'object'=>$db->f('object'),
					'notes'=>$db->f('notes'),
					'created_on'=>$db->f('created_on'),
					'created_by'=>$db->f('created_by'),
					'modified_on'=>$db->f('modified_on'),
					'modified_by'=>$db->f('modified_by'));
		}
		//echo count($rules);		
		return $rules;		
	}	
	
function find_involved_projects()
	{
		$db = $_SESSION['db'];
		$sql = "select acl_project_id, acl_rights from s3db_project_acl where acl_account='".$_SESSION['user']['account_id']."' and acl_rights !=0";
		//echo $sql;
		$db->query($sql, __LINE__, __FILE__);
		while($db->next_record())
		{
			$involved_projects[] = Array('project_id'=>$db->f('acl_project_id'),
						   'rights'=>$db->f('acl_rights'));
		}
		return $involved_projects;
	}
			
	
?>