<?php
	function CORElist($C) {
		#function CORElist lists all the resources in the element downstream of the "uid" in the s3core structure. For example, if element == rules, then s3list will list all the rules on a given project_id, provided project_id is specified. If element is statements, then s3list wil be expecting rule_id and resource_id or just one of them
		#Syntax CORElist(compact($child, array('rule_id'=>$rule_id, 'item_id'=>$item_id), $db)); where child is the name of the elements to retrieve; parante_ids is an array where the type of id is specified in the key
		$regexp = $GLOBALS['regexp'];
		$dbstruct = $GLOBALS['dbstruct'];
		$messages = $GLOBALS['messages'];
		extract($C);
		extract($parent_ids);
		$from = $child;
		if(!$from) {
			$from = 'projects';
		}
		if(!$select) {
			$select = '*';
		}
		$equality = '='; #by default, equality on query end be this, unless specified that equality should be a regular expression

		#Error messages
		$syntax_message = "Please provide all the necessary fields. For syntax instructions refer to <a href='http://www.s3db.org/documentation.html'>S3DB Documentation</a>";
		$success = '<error>0</error><message>'.$from.' '.$action.'ed '.$element_id.'</message>'; 
		$not_a_query = '<error>1</error><message>'.$from.' is not a valid S3element. Valid elements: groups, users, keys, projects, rules, statements, collections, items, rulelog";</message>';
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
					'collections'=>array('project_id', 'rule_id'),
					'rules'=>array('project_id', 'collection_id', 'subject_id', 'object_id'),
					'items'=>array('collection_id', 'project_id'), 
					'statements'=>array('rule_id', 'item_id', 'collection_id', 'project_id'),
					'files'=>array('statement_id', 'rule_id', 'item_id', 'project_id')
				);

		#if from is not one of these elements, sent the user back, query is invalid!
		if (!in_array($from, array_keys($alt))) {
			#check if user is inputing a sigular of one of the alt plurals
			$plurals = array_keys($alt);
			$singulars = array('key', 'rulelog', 'user', 'group','project', 'collection', 'rule', 'item', 'statement', 'file');
			$from = str_replace($singulars, $plurals, $from);
			$cols = $dbstruct[$from];
			
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
					'accesslog'=>array('account_lid'=>'login_id', 'time'=>'login_timestamp',),
					'projects'=>array(),
					'project'=>array(),
					'items'=>array(
								'collection_id'=>'resource_class_id',
								'item_id'=>'resource_id'
							),
					'item'=>array(
								'collection_id'=>'resource_class_id',
								'item_id'=>'resource_id'
							),
					'collections'=>array('collection_id'=>'resource_id'),
					'collection'=>array('collection_id'=>'resource_id'),
					'rules'=>array(),
					'rule'=>array(),
					'statements'=>array('item_id'=>'resource_id'),
					'statement'=>array('item_id'=>'resource_id'),
					'files'=>array()
				);
		foreach($alt[$from] as $s3id) {
			$s3dbId = $s3map[$from][$s3id];
			if($s3dbId=='') {
				$s3dbId = $s3id;
			}
			if($parent_ids[$s3id]!='') {
				#does it exist? What sort of resource is this? Type of id should be identified in the first letter (collection_id is C, rule_id is R...)
				$CRISP = strtoupper(substr($s3id, 0, 1));
				$id = $CRISP.$parent_ids[$s3id];
				$info[$parent_ids[$s3id]] = s3info(str_replace('_id', '', $s3id), $parent_ids[$s3id], $db);
				if (!is_array($info)) {
					return ($something_does_not_exist.'<message>'.$s3id.' '.$parent_ids[$s3id].' does not exist</message>');
				}
				#does user have permission on this/these resources?
				$query_end .= " and ".$s3dbId." ".$equality." '".$parent_ids[$s3id]."'";
			}
		}
		$toreplace = array_keys($s3map[$from]);
		$replacements = array_values($s3map[$from]);
		$s3ql['select'] = str_replace($toreplace, $replacements, $query_end);

		#all queries will run AS IF ADMIN WAS RUNNING THEM
		switch ($from) {
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
			case 'users':		#expecting group_id or project_id
			{
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
			case 'collections':
			{
				#$table = 'resource';
				$table = 'resource, s3db_rule';
				$required = "iid = '0' and s3db_rule.status = 'A'";
				$select = str_replace('project_id', 's3db_rule.project_id', $select);
				$select = str_replace('notes', 's3db_resource.notes', $select);
				if($parent_ids['project_id']!='') {
					$query_end = str_replace("and project_id = '".$project_id."'", "and (entity = subject and verb = 'has UID' and object = 'UID' and s3db_resource.project_id = s3db_rule.project_id and (s3db_rule.project_id = '".$project_id."' or s3db_rule.permission ".$regexp." '(_|^)".$project_id."_'))", $query_end);
				}
				#restrict the query to the rules where user is allowed
				$query_end = str_replace("and project_id ".$regexp." '".$user_project_list."'", "and subject = entity and object = 'UID' and s3db_rule.project_id = s3db_resource.project_id and (s3db_rule.project_id ".$regexp." '".$user_project_list."' or s3db_rule.permission ".$regexp." '".$user_permission_list."')", $query_end);
				break;
			}
			case 'items':
			{
				$table = 'resource';
				$required = "iid = '1' and status = 'A'";
				#to avoid having to call s3list again, created this function that simulates finding user collections
				$classes = findUserClasses($user_id, $db);
				if(!is_array($classes)) {
					return ($no_output.'<message>User does not have permission in any collections</message>');
				}
				$classes_list = create_class_id_list($classes);
				$query_end = str_replace("and project_id ".$regexp." '".$user_project_list."'", "and resource_class_id ".$regexp." '".$classes_list."'", $query_end);
				break;
			}
			case 'rules':
			{
				$table = 'rule';
				$required = "status ='A'";
				if($parent_ids['project_id']!='') {
					$query_end = str_replace("and project_id = '".$project_id."'", "and (project_id ".$regexp." '^".$project_id."$' or permission ".$regexp." '(_|^)".$project_id."_')", $query_end);
					if($parent_ids['collection_id']!='') {
						$class_info = s3info('collection', $parent_ids['collection_id'], $db);
						$query_end = str_replace("and collection_id = '".$parent_ids['collection_id']."'", "and (subject_id = '".$parent_ids['collection_id']."' or object_id = '".$parent_ids['collection_id']."')", $query_end);
					}
				} elseif ($parent_ids['collection_id']!='') { 		#no project_id but w/ collection_id. If no project_id is indicated, it will have to find the correct subjects (which can be repeated if queried on several projects)
					$class_info = s3info('collection', $parent_ids['collection_id'], $db);
					#$query_end = str_replace("and collection_id = '".$parent_ids['collection_id']."'", "and (subject_id = '".$parent_ids['collection_id']."' or (subject = '".$class_info['entity']."' and project_id = '".$class_info['project_id']."'))",$query_end); #all that don't belong to this project will have to be queried by collection_id.
					$query_end = str_replace("and collection_id = '".$parent_ids['collection_id']."'", "and (subject_id = '".$parent_ids['collection_id']."' or object_id = '".$parent_ids['collection_id']."')",$query_end);
				} else {
					$query_end = str_replace("and project_id ".$regexp." '".$user_project_list."'", " and (project_id ".$regexp." '".$user_project_list."' or permission ".$regexp." '".$user_permission_list."')", $query_end);
				}
				break;
			}
			case 'statements':
			{
				$table = 'statement';
				$required = "status = 'A'";
				if($parent_ids['collection_id']!='') {
					#find all the statements in items that belong to this collection.
					$instance_ids = findClassInstances($parent_ids['collection_id'], $db);
					$rule_ids = findClassRules($parent_ids['collection_id'], $db);#these would be all the rules that use the collection as either subject or object
					$instance_list = create_list($instance_ids);
					$rule_list = create_list($rule_ids);
					if(is_array($instance_ids) && is_array($rule_ids)) {
						$query_end = str_replace("and collection_id = '".$parent_ids['collection_id']."'", "and (resource_id ".$regexp." '".$instance_list."' or rule_id ".$regexp." '".$rule_list."')", $query_end);
					} elseif(is_array($instance_ids) && !is_array($rule_ids)) {
						$query_end = str_replace("and collection_id = '".$parent_ids['collection_id']."'", "and (resource_id ".$regexp." '".$instance_list."')", $query_end);
					} elseif(!is_array($instance_ids) && is_array($rule_ids)) {
						$query_end = str_replace("and collection_id = '".$parent_ids['collection_id']."'", "and (rule_id ".$regexp." '".$rule_list."')", $query_end);
					}
				}
				break;
			}
		}
	
		#POSSIBLY MOVE THIS PART TO A SEPARATE FUNCTION!!
		$sql = "select ".$select." from s3db_".$table." where ".$required." ".$query_end.$order_by;
		$db->query($sql, __LINE__, __FILE__);
		$cols = $dbstruct[$from];
		while($db->next_record()) {
			$resultStr .= "\$data[] = Array(";
			if ($extracol!='') {
				$resultStr .= "'".$extracol."'=>'".$db->f($SQLfun)."',";
			}
			foreach ($cols as $col) {
				#if($db->f($col)!='') {
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