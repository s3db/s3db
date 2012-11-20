<?php
	#function s3list lists all the resources in the element following "uid" in the s3core structure. For example, if element == rules, then s3list will list all the rules on a given project_id, provided project_id is specified. If element is statements, then s3list wil be expecting rule_id and resource_id or just one of them
	#s3list checks permissions directly
	function s3list($s3ql) {
		$regexp = $GLOBALS['regexp'];
		$dbstruct = $GLOBALS['dbstruct'];
		extract($s3ql);
		if(is_array($where)) {
			extract($where);
		}
		if(!$user_id) {
			return ('User authentication is required');
			exit;
		}
		if($order_by) {
			$order_by = ' order by '.$order_by;
		}
		if(!$select) {
			$select = '*';
		}
		if(!$from) {
			$from = 'projects';
		}

		$equality = '='; #by default, equality on query end be this, unless specified that equality should be a regular expression
		$cols = $dbstruct[$from];
		#Error messages
		$syntax_message = "Please provide all the necessary fields. For syntax instructions refer to <a href='http://www.s3db.org/documentation.html'>S3DB Documentation</a>";
		$success = '<error>0</error><message>'.$from.' '.$action.'ed '.$element_id.'</message>'; 
		$not_a_query = '<error>1</error><message>'.$from.' is not a valid S3element. Valid elements: groups, users, keys, projects, rules, statements, classes, instances, rulelog";</message>';
		$something_went_wrong = '<error>2</error><message>Failed to '.$action.' '.$from.'</message>';
		$something_missing = '<error>3</error><message>'.$syntax_message.'</message>';
		$repeating_action = '<error>4</error>';
		$no_permission_message = '<error>5</error>';
		$something_does_not_exist = '<error>5</error>';
		$wrong_query_for_purpose = '<error>6</error>';
		$wrong_input = '<error>7</error>';
		$no_output = '<error>8</error>';

		#alternative IDs that can be used for the query
		$alt = array(
					'keys'=>array('key_id'),
					'rulelog'=>array('rule_id'),
					'users'=>array('group_id', 'project_id'),
					'groups'=>array('user_id'),
					'projects'=>array('user_id'),
					'classes'=>array('project_id', 'rule_id'),
					'rules'=>array('project_id', 'class_id'),
					'instances'=>array('class_id', 'project_id'), 
					'statements'=>array('rule_id', 'instance_id', 'project_id'),
					'files'=>array('statement_id', 'rule_id', 'instance_id', 'project_id')
				);

		#if from is not one of these elements, sent the user back, query is invalid!
		if(!in_array($from, array_keys($alt))) {
			#check if user is inputing a sigular of one of the alt plurals
			$plurals = array_keys($alt);
			$singulars = array('key', 'rulelog', 'user', 'group','project', 'class', 'rule', 'instance', 'statement', 'file');
			$from = str_replace($singulars, $plurals, $from);
			
			#if still not in array, definitelly exit;
			if (!in_array($from, array_keys($alt))) {
				return ($not_a_query);
			}
		}

		#now replace on "where" the correct s3db names
		$s3map = array(
					'users'=>array(
								'user_id'=>'account_id',
								'login'=>'account_lid',
								'password'=>'account_pwd',
								'username'=>'account_uname',
								'email'=>'account_email',
								'phone'=>'account_phone',
								'address'=>'addr1',
								'address2'=>'addr2',
								'city'=>'city',
								'state'=>'state',
								'postal_code'=>'postal_code',
								'country'=>'country'
							),
					'groups'=>array(
								'group_id'=>'account_id',
								'groupname'=>'account_lid'
							),
					'keys'=>array(),
					'accesslog'=>array(
								'account_lid'=>'login_id', 
								'time'=>'login_timestamp',
							),
					'projects'=>array(),
					'project'=>array(),
					'instances'=>array(
								'class_id'=>'resource_class_id',
								'instance_id'=>'resource_id'
							),
					'instance'=>array(
								'class_id'=>'resource_class_id',
								'instance_id'=>'resource_id'
							),
					'classes'=>array('class_id'=>'resource_id'),
					'class'=>array('class_id'=>'resource_id'),
					'rules'=>array(),
					'rule'=>array(),
					'statements'=>array('instance_id'=>'resource_id'),
					'statement'=>array('instance_id'=>'resource_id'),
					'files'=>array()
				);

		foreach($alt[$from] as $s3id) {
			$s3dbId = $s3map[$from][$s3id];
			if($s3dbId=='') {
				$s3dbId = $s3id;
			}
			if($where[$s3id]!='') {
				#does it exist? What sort of resource is this? Type of id should be identified in the first letter (Class_id is C, rule_id is R...)
				$CRISP = strtoupper(substr($s3id, 0, 1));
				$id = $CRISP.$where[$s3id];
				$info[$where[$s3id]] = s3info(str_replace('_id', '', $s3id), $where[$s3id], $db);
				if(!is_array($info)) {
					return ($something_does_not_exist.'<message>'.$s3id.' '.$where[$s3id].' does not exist</message>');
				}
				if(!permissionOnResource(compact('user_id', 'db', 'id'))) {
					return ($no_permission_message.'<message>user does not have permission on '.$id.'</message>');
				}
				#does user have permission on this/these resources?
				$query_end .= " and ".$s3dbId." ".$equality." '".$where[$s3id]."'";
			}
		}
		$toreplace = array_keys($s3map[$from]);
		$replacements = array_values($s3map[$from]);
		$s3ql['select'] = str_replace($toreplace, $replacements, $query_end);
		
		#restrict the query to the rules where user is allowed
		$user_projects = findUserProjects($user_id, $db); #alternative to re-using s3list to query projects - still not sure which is faster...
		$s3ql=compact('user_id','db');
		$s3ql['select']='project_id';
		$s3ql['from']='projects';
		#$user_projects = s3list($s3ql);

		if(is_array($user_projects)) {
			$user_permission_list = create_permission_list($user_projects);
			$user_project_list = create_project_id_list($user_projects);
		}
		if(!is_array($user_projects)) {
			return ($no_output.'<message>User does not have permission in any project</message>');
		}
		if ($user_id!='1' && ereg('(projects|classes|rules|instances|statements|rulelog)', $from) && $where['project_id']=='') {
			#If query end is empty, it means no id was supplied. So list all 'resources' where user is allowed, which implies making a query in project.
			$query_end .= " and project_id ".$regexp." '".$user_project_list."'";
		}
		
		#When rule_id (or class_id) is supplied check if user has permission on a project that has permission on that rule (or class). If rule_id is not supplied
		#When instance_id is supplied, check if user has permission on the rule (or class) of that instance
		#array_keys contains the things to replace and array_values the replacements

		switch($from) {
			case 'keys':
			{
				$table = 'access_keys';
				$required = "expires > '".date('Y-m-d')."'";
				if($user_id!='1') {
					$required .= " and (account_id = '".$user_id."')";
				}
				break;
			}
			case 'rulelog':
			{
				$table = 'rule_change_log';
				$required = "rule_id !=''";
				break;
			}
			case 'users':
			{
				#expecting group_id or project_id
				#remove password from query fields
				$table = 'account';
				$required = "account_type = 'u' and account_status = 'A'";
				break;	
			}
			case 'groups':
			{
				$table = 'account';
				$required = "account_type = 'g' and account_status = 'A'";
				break;
			}
			case 'projects':
			{
				$table = 'project';
				$required = "project_status = 'A'";
				#if user is not admin, retrict this query to the projects user can view by extending queryend
				if($user_id!='1') {
					$required .= " and (project_owner = '".$user_id."' or project_id in (select acl_project_id from s3db_project_acl where acl_account = '".$user_id."' and acl_rights!='0'))";
				}
				break;
			}
			case 'classes':
			{
				#$table = 'resource';
				$table = 'resource, s3db_rule';
				$required = "iid = '0'";
				$select = str_replace('project_id', 's3db_rule.project_id', $select);
				$select = str_replace('notes', 's3db_resource.notes', $select);
				if($where['project_id']!='') {
					$query_end = str_replace("and project_id = '".$project_id."'", "and (entity = subject and verb = 'has UID' and object = 'UID' and s3db_resource.project_id = s3db_rule.project_id and (s3db_rule.project_id = '".$project_id."' or s3db_rule.permission ".$regexp." '(_|^)".$project_id."_'))", $query_end);
				}
				#restrict the query to the rules where user is allowed
				$query_end = str_replace("and project_id ".$regexp." '".$user_project_list."'", "and subject = entity and object = 'UID' and s3db_rule.project_id = s3db_resource.project_id and (s3db_rule.project_id ".$regexp." '".$user_project_list."' or s3db_rule.permission ".$regexp." '".$user_permission_list."')", $query_end);
				break;
			}
			case 'instances':
			{
				$table = 'resource';
				$required = "iid = '1'";
	
				#to avoid having to call s3list again, created this function that simulates finding user classes
				$classes = findUserClasses($user_id, $db);
				if(!is_array($classes)) {
					return ($no_output.'<message>User does not have permission in any classes</message>');
				}
				$classes_list = create_class_id_list($classes);
				$query_end = str_replace("and project_id ".$regexp." '".$user_project_list."'", "and resource_class_id ".$regexp." '".$classes_list."'", $query_end);
				break;
			}
			case 'rules':
			{
				$table = 'rule';
				$required = "rule_id !='0'";
				if($where['project_id']!='') {
					$query_end = str_replace("and project_id = '".$project_id."'", "and (project_id ".$regexp." '^".$project_id."$' or permission ".$regexp." '(_|^)".$project_id."_')", $query_end);
					if($where['class_id']!='') {
						$class_info = s3info('class', $where['class_id'], $db);
						$query_end = str_replace("and class_id = '".$where['class_id']."'", "and subject = '".$class_info['entity']."'", $query_end);
					}
				} elseif ($where['class_id']!='') { 		#no project_id but w/ class_id. If no project_id is indicated, it will have to find the correct subjects (which can be repeated if queried on several projects)
					$class_info = s3info('class', $where['class_id'], $db);
					$query_end = str_replace("and class_id = '".$where['class_id']."'", "and (subject_id = '".$where['class_id']."' or (subject = '".$class_info['entity']."' and project_id = '".$class_info['project_id']."'))",$query_end); #all that don't belong to this project will have to be queried by class_id.
				} else {
					$query_end = str_replace("and project_id ".$regexp." '".$user_project_list."'", " and (project_id ".$regexp." '".$user_project_list."' or permission ".$regexp." '".$user_permission_list."')", $query_end);
				}
				break;
			}
			case 'statements':
			{
				$table = 'statement';
				$required = "status ='A'";
	
				#user only has permission to a number of statement, those where he has permission on rule. Permission on rule propagates to permission on statement
				#alternative to calling s3list again:
				$rules = findUserRules($user_id, $db);
				if(!is_array($rules)) {
						return ($no_output.'<message>User does not have permission in any rules</message>');
				} else {
					$user_rule_list = create_rule_id_list($rules);
					$query_end = str_replace("and project_id ".$regexp." '".$user_project_list."'", "and rule_id ".$regexp." '".$user_rule_list."'", $query_end);
				}
				break;
			}
		}

		#POSSIBLY MOVE THIS PART TO A SEPARATE FUNCTION!!
		$sql = "select ".$select." from s3db_".$table." where ".$required." ".$query_end.$order_by;
		$db->query($sql, __LINE__, __FILE__);
		while($db->next_record()) {
			$resultStr .= "\$data[] = Array(";
			if ($extracol!='') {
				$resultStr .= "'".$extracol."'=>'".$db->f($SQLfun)."',";
			}
			foreach ($cols as $col) {
				#if($db->f($col)!='')
				#{
				$resultStr .= "'".$col."'=>'".addslashes($db->f($col))."'";
				if($col != end($cols)) {
					$resultStr .= ",";
				}
				#}
			}
			$resultStr .= ");";
		}
		#evaluate the long string
		eval($resultStr);
		if(is_array($data)) {
			if(!$nomap) {		#include stuff relevant for each element
				foreach($data as $element_info) {
					#$element_info['dataAcl'] = instanceAcl(array('instance_info'=>$element_info, 'user_id'=>$user_id, 'db'=>$db));
					$data1[] = include_all(array('elements'=>$from, 'element_info'=>$element_info, 'user_id'=>$user_id, 'db'=>$db));
				}
				$data = $data1;
			}
		} else {
			$data = $no_output.'<message>Your query returned no results</message>';
		}
		return ($data);
	}
?>