<?php
	function listS3db($x) {
		if($GLOBALS['s3db_info']['server']['db']['db_type'] == 'mysql') { 
			$regexp = 'regexp'; 
		} else {
			$regexp = '~';
		}
		if(is_array($x)) { extract($x); }
		
		#Just a fix for those scripts taht don't have db var implements
		if(!is_object($db)) { $db = $_SESSION['db']; }
		
		#this contains the primeary key (or whatever in the table that cannot be empty) The goal is to reduce this extended query cases to only a few
		$s3idNames = array('projects'=>'project_id', 'project'=>'project_id','classes'=>'resource_id', 'instances'=>'resource_id','resource'=>'resource_id', 'rules'=>'rule_id', 'rule'=>'rule_id', 'statements'=>'statement_id', 'statement'=>'statement_id', 'access_keys'=>'key_id', 'file_transfer'=>'file_id', 'access_rules'=>'rule_id','access_log'=>'login_id','rule_change_log'=>'rule_id');
		
		#if(ereg('^(project|projects|instance|rule|statement|instances|rules|statements)$', $table) && $user_id!='1')
		if(ereg('^(project|projects|class|instance|rule|statement|classes|instances|rules|statements)$', $table) && $user_id!='1') {
			$query_end .=" and status = 'A'";
			$query_end .= " and (".$s3idNames[$table]." in (select id from s3db_permission where uid ~ '^".strtoupper(substr($table, 0,1))."' and shared_with = 'U".$user_id."' and permission_level ~ '[^(0)]') or (";
			$final = ")";
		}
		if(ereg('(project|statement_log|rule_change_log)', $table)) {
			if($project_list !='' && $user_id!='1'){
				$query_end .= " project_id ".$project_list.") or (project_owner = '".$user_id."')".$final;
			}
		}
		#if(ereg('^(rule|rules)$', $table)){
		if(ereg('^(rule|class|rules|classes)$', $table)) {
			if($project_list !='' && $permission_list !='') {
				$query_end .= "project_id ".$project_list." or permission ".$permission_list.")".$final;
			}
			if($project_id !='') {
				#the and can go in hre because for rules and classes, user projects are always in the query
				$query_end .= " and (project_id ".$project_id." or permission ".$permission.")";
				$cols = array_diff($cols, array('project_id', 'permission'));
			}
			if(ereg('(class|classes)',$table)) {
				$table='resource';
				$x['iid']='0';
			}
		}
		if(ereg('(intances|instances)',$table)) {
			$table='resource';
			$x['iid']='1';
			$select = str_replace('project_id','r.project_id',$select);
			if($user_id!='1') {
				$query_end .= " resource_class_id ".$class_list.")".$final;
			}
		}
		if(ereg('^(statement|statements)$', $table)) {
			if($rule_list!='') {
				$query_end .= " rule_id ".$rule_list.")".$final;
			}
			$table = 'statement';
		}
		if(ereg('access_rules', $table)) {
			if($rule_list!='' && $project_list!='') {
				$query_end .= " and (rule_id ".$rule_list." or project_id ".$project_list.")";
			}
		}
		if(ereg('(user|users|group|groups)',$table)) {
			$table = 'account';
		}
		#get the values for the columns, default will be "and"
		foreach($cols as $col) {
			if ($x[$col]!='') {
				$query_end .= " and ".$col." ".parse_regexp($x[$col]);
			}
		}
		#get the extra input SQL vars
		if(is_array($SQLextra)) {
			foreach($SQLextra as $extra=>$value) {
				$extras .= $value;
			}
		}
		#Retrieve only specific cols. Increase speed query and accept count and group by requests
		if($x['out']=='' || $x['out']=='*') {
			#this menas no specific function is being called, only general results of the query are to be in the output
			$select = '*';
		} else {
			$select = $x['out'];
			#detect if the user is calling an SQL function on select
			if ($SQLfun!='') {
				$extracol = $x['out'];
			}
			#if ($SQLfun == 'distinct')
		}
		
		#"Sensitive queries": project_id dependent or user dependent
		#if($table=='project') {
		#	$sql = "select distinct ".$select." from s3db_project where (project_owner='".$user_id."' or project_id in (select acl_project_id from s3db_project_acl where acl_account = '".$user_id."' and acl_rights ".$regexp." '^(1|2|3)'))".$query_end.$extras;
		#	echo $sql = "select distinct ".$select." from s3db_project where project_id!='0'".$query_end.$extras;
		#	echo  $sql = "select distinct ".$select." from s3db_project where project_id!='0'".$query_end.$extras;
		#}

		if($table=='resource' && $x['iid']=='0') { 		#a query on permissions, should work both with field project_id and permission
			#$to_replace = array(' permission', 'status');
			#$replacements = array(' s3db_rule.permission ', 's3db_rule.status');
			$shared_with_list = ($x['project_id']!='')?str_replace(array('~ \'', '^', '$\''),array('', '', '$'), $x['project_id']):str_replace(array('~ \'', '^', '$\''),array('', '', '$'), $project_list);
			$project_list = ($x['project_id']!='')?$x['project_id']:$project_list;
			$extras = str_replace('order by ', 'order by s3db_resource.', $extras);
			$query_end = str_replace('project_id', 's3db_rule.project_id', $query_end);
			$query_end = str_replace(' permission ', ' s3db_rule.permission ', $query_end);
			$query_end = str_replace('status', 's3db_rule.status', $query_end);
			#$sql = "select ".$select." from s3db_resource, s3db_rule where subject=entity and s3db_rule.project_id=s3db_resource.project_id and object='UID' ".$query_end.$extras;
			$sql = "select ".$select." from s3db_resource, s3db_rule where subject=entity and iid='0' and object='UID' and s3db_rule.project_id=s3db_resource.project_id and (rule_id in (select id from s3db_permission where shared_with ~ '^P(".$shared_with_list .")' and uid ~ '^R') or (s3db_rule.project_id ".$project_list."))".$query_end;
		} elseif($table=='account') {	
			if($user_id!='1') {
				$query_end = $query_end." and account_id!='1'"; #only admin can see grop admin/himself
			}	
			if($account_type=='g' && $x['imp_user_id']!='') {
				$sql = "select ".$select." from s3db_account where account_status = 'A' and ((account_id in (select group_id from s3db_account_group where account_id='".$x['imp_user_id']."') or created_by='".$x['imp_user_id']."'))".$query_end.$extras;
			} elseif($x['project_id']!='') {
				$extras = str_replace('order by ', 'order by a.', $extras);
				$sql = "select ".$select." from s3db_account as a, s3db_project_acl as b where (a.account_id = '".$user_id."' and a.account_id = b.acl_account and a.account_status = 'A') or (a.account_id = b.acl_account and b.acl_project_id ='".$x['project_id']."')".$query_end.$extras;
			} elseif($x['account_type']!='g' && $x['group_id']!='') {
				$sql = "select ".$select." from s3db_account where account_status = 'A' and account_id in (select account_id from s3db_account_group where group_id = '".$x['group_id']."')".$query_end.$extras;
			} elseif($x['account_type']!='g') {
				$sql = "select ".$select." from s3db_account, s3db_addr where (addr_id = account_addr_id or (account_addr_id = '-10' and addr_id = '1'))".$query_end.$extras;
			} elseif($account_type=='g') {
				$sql = "select ".$select." from s3db_".$table." where ".$table."_id!='0' ".$query_end.$extras;
			}
		} elseif(ereg('(access_keys|access_log|rule_change_log|file_transfer|statement_log)', $table)) {
			if($table=='statement_log') {
				$query_end = str_replace(' project_id', ' old_project_id',$query_end);
			}
			if($table!='rule_change_log' && $table!='access_rules') {
				$sql = "select ".$select." from s3db_".$table." where ".$s3idNames[$table]."!='0'".$query_end.$extras;
			} else {
				$sql = "select ".$select." from s3db_".$table." where ".$s3idNames[$table]."!='0'".$query_end.$extras;
			}
		} elseif($table=='access_rules') {
			if($x['project_id'] !='' && $x['rule_id']!='') {
				$sql = "select ".$select." from s3db_".$table." where ".$s3idNames[$table]."!='0'  and (project_id ".$x['project_id']." or rule_id ".$x['rule_id'].')'.$query_end.$extras;
			} elseif ($x['rule_id']!='') {
				$sql = "select ".$select." from s3db_".$table." where ".$s3idNames[$table]."!='0' and rule_id ".$x['rule_id'].''.$query_end.$extras;
			} elseif ($x['project_id'] !='') {
				$sql = "select ".$select." from s3db_".$table." where ".$s3idNames[$table]."!='0' and project_id ".$x['project_id'].''.$query_end.$extras;
			} else {
				$sql = "select ".$select." from s3db_".$table." where ".$s3idNames[$table]."!='0'".$query_end.$extras;
			}
		} elseif($table=='project_acl') {
			$extras = str_replace('order by ', 'order by a.', $extras);
			#$sql = "select ".$select." from s3db_".$table." where acl_project_id!='0' ".$query_end.$extras;
			$sql = "select ".$select." from s3db_account as a, s3db_project_acl as b where a.account_id in (select b.acl_account from s3db_project_acl where b.acl_project_id!='0' and b.acl_project_id = '".$project_id."' and a.account_id = b.acl_account)".$query_end.$extras;
		} else {
			$sql = "select ".$select." from s3db_".$table." where ".$table."_id!='0' ".$query_end.$extras;
		}
		$db->query($sql, __LINE__, __FILE__);
		$dbdata = get_object_vars($db);
		if($dbdata['Errno']!='0' && $table=='rules') {
			$sql = "select ".$select." from s3db_rule where project_id = '".$project_id."'".$query_end.$extras;
			$db->query($sql, __LINE__, __FILE__);
		}
		$_SESSION['sqlquery'] = $sql;
		if(ereg('[(projects)|(classes)|(rules)|(instances)|(statements)]', $table)) {
			$cols = array_merge($cols, array('project_id', 'permission')); #put the cols back for data retrieve
		}
		$cols = array_unique($cols);
		while($db->next_record()) {
			$resultStr .= "\$data[] = Array(";
			if($extracol!='') {
				$resultStr .= "'".$extracol."'=>'".$db->f($SQLfun)."',";
			}
			#if($db->f($col)!='')
			foreach($cols as $col) {
				$resultStr .= "'".$col."'=>'".addslashes($db->f($col))."'";
				if($col != end($cols)) {
					$resultStr .= ",";
				}
			}
			$resultStr .= ");";
			#evaluate the long string
			#eval($resultStr);
		}
		#evaluate the long string
		eval($resultStr);
		return $data;
	}
?>