<?php
function S3QLaction($s3ql)
{
	
	extract($s3ql);
	
	#grab a few relevant varuales
	$regexp = $GLOBALS['regexp'];
	$dbstruct = $GLOBALS['dbstruct'];
	#map a few vairables
	$s3map = $GLOBALS['s3map'];
	$format = $s3ql['format'];
	#Error messages
	extract($GLOBALS['messages']);
	

#database and user identification	
	if(!is_object($db)) 
	{$db = $_SESSION['db'];
	}

	$key=($_REQUEST['key'])?$_REQUEST['key']:$s3ql['key'];
	$user_id = ($user_id)?$user_id:$_SESSION['user']['account_id'];
	$user_info = s3info('users', $user_id, $db);

	if (!$user_id && !$db) {
		if (!$key) {
		return (formatReturn($GLOBALS['error_codes']['no_permission_message'], 'Please specify user_id and db or a key', $format,''));
		}
	#re-chekc if user provided is the same for key provided	
	}
	
	$s3ql = array_diff_key($s3ql, array('db'=>'', 'user_id'=>'')); #take out from the array what needed to be included for wihitn S3DB queries
	if ($s3ql['update']!='') {
		$s3ql['edit'] = $s3ql['update'];#update is closer to SQL, although original was edit. Must keep edit to be backward compatible
		$s3ql=array_filter(array_diff_key($s3ql, array('update'=>1)));
		}

#identify the action
	$possible_actions = array('insert', 'edit', 'delete', 'select', 'update', 'grant');
	foreach ($possible_actions as $someaction) {
		if ($s3ql[$someaction]!='') {
		$action = $someaction;
		}
	}
	
	#if there is nothing as action, assume a select
	if ($action=='') {
		$action = 'select';
	}
	
	#identify the target
	if (ereg('(insert|edit|update|delete|grant)', $action)) {
		$s3ql['from'] = ($s3ql[$action]=='')?$_REQUEST[$action]:$s3ql[$action];
	}
	elseif (ereg('(select)', $action)) {
		$s3ql['from'] = ($s3ql['from']=='')?$_REQUEST['from']:$s3ql['from'];
	}

	#if there is no target, assume projects
	if ($s3ql['from']=='') {
		$s3ql['from'] = 'projects';
	}
	if($s3ql['from']=='permission')
		$s3ql['from'] = 'user';
	if($s3ql['from']=='class')
		$s3ql['from']= 'collection';
	if($s3ql['from'] =='instance')
		$s3ql['from'] = 'item';
	
	
	#these are targets ONLY for insert/edit/delete. Select takes plurals... was a bad idea, I know :-( but is much more intuitive :-)
	$possible_targets = array('permission', 'user', 'group', 'key', 'project', 'collection', 'item', 'rule', 'statement', 'filekey');

#start taking action

switch ($action) {
	case 'select':
		{
		$data = selectQuery(compact('s3ql', 'db','user_id', 'format'));
		return ($data);
		break;
		} #Close select queries
		
	case 'insert':
		{
		
		
		#echo '<pre>';print_r($s3ql);exit;
		#map s3ql input to s3db structure requirements
		
		if($s3ql['insert']=='class')
				$s3ql['insert']='collection';
		if($s3ql['insert']=='instance')
			$s3ql['insert']='item';
		if($s3ql['where']['notes']!='')
			$s3ql['where']['notes'] = $s3ql['where']['notes'];
		if($s3ql['where']['value']!='')
			$s3ql['where']['value'] = $s3ql['where']['value'];
		
		
			
			##build inputs and oldvalues for validation and insert functions
			$tranformed = S3QLselectTransform(compact('s3ql', 'db', 'user_id'));
			$s3ql= $tranformed['s3ql'];$element = $s3ql['insert'];
			
			
			$element_id = $s3ql['where'][$element.'_id'];	
			
			$letter = strtoupper(substr($element,0,1));
			$uid = $letter.$element_id;


			$required = array(
						'key'=>array(),
						'project'=>array('project_name'),
						'collection'=>array('project_id', 'entity'),
						'rule'=>array('project_id', 'subject_id', 'verb', 'object'),
						'item'=>array('collection_id'),
						'statement'=>array('item_id', 'rule_id', 'value'),
						'file' => array('item_id', 'rule_id', 'filekey'),
						'user' => array('account_lid', 'account_email'),
						'group'=>array('account_lid'));

			
			if(!in_array($element, array_keys($required)))
			{
				return (formatReturn($GLOBALS['error_codes']['wrong_input'], $element.' is not a valid S3DB element. Valid elements: key, project, collection, rule, item, statement, file',$format,''));
			
			}
			
			#if a subject is provided instead of a subject id in rule, dont break because of that. Find the subject
			#THIS PART NEEDS TO B HERE BECAUSE IT THE MANDATORY FIELDS ARE 'OR'
			if($element=='rule')
			{
				$s3ql=ruleInputsInfer($s3ql, $db, $user_id);
			}	
		#echo '<pre>';print_r($s3ql);
			
		#translate some s3ql inputs into s3db names:
		#IS there anythi ng still missing? There are 2 types fo required inputs: thsoe from the user and those into the table. The firstare verified here, the rest are verified in "validation"
		
		$diff=array_diff($required[$element],array_keys($s3ql['where'])); 
		
			if($element_id=='' && !empty($diff))
			return formatReturn($GLOBALS['error_codes']['something_missing'],'Please provide all the necessary fields: '.rtrim(array_reduce($required[$element], "comma_split"), ", ").'. '.$syntax_message, $s3ql['format'], '');
		
		
		#echo '<pre>';print_r($required[$element]);exit;
		
			
		#if there is any sort of id, check if user has permissions on that. In case of statement, permission must be checked on both rule and instance
		$inserteable = 	array(
						#'deployment'=>'deployment_id',
						'group'=>'group_id',
						'user'=>'user_id',
						'project'=>'project_id',
						'rule'=>'rule_id', 
						'collection'=>'collection_id',
						'item'=>'item_id',
						'statement'=>'statement_id',
						
						);

				
		
		#insert overal view

		#element_id is not empty
			#upstream resource provided
				#if all permissions clear up, grant permission to upper on loewer score;
			#upstream resource not provided
				#infer deployment if user, group or project, else nothing to do
		#element_id is empty
			#upstream resources provided
				#all permissions clear up, create new entry.
		
		#scoreTable will allow us to score the elements according to their position in the inheritance model. To nisert an "inserteable" A into an "inserteable" B, 
		
		$scoreTable=array_reverse($inserteable, 0);
		
		$scoreTable = array_combine(array_keys($scoreTable), range(1,count($inserteable)));
		
		
		$elementScore = $scoreTable[$element];#check the score of target. All other score will be chacked against this one
		
		#for user, group and project, inserts occur in deployment (local). Except when there is indication on group or any other Id.
			$input_ids = array_intersect($inserteable, array_keys($s3ql['where']));
			
			if(ereg('^(U|G|P)$', $letter) && (count($input_ids)<=1 || count(array_filter(array_diff_key($s3ql['where'], array($element.'_id'=>''))))==0))
			{
			
			$s3ql['where']['deployment_id']=($s3ql['where']['deployment_id']!='')?$s3ql['where']['deployment_id']:substr($GLOBALS['Did'], 1, strlen($GLOBALS['Did']));
			$info[$GLOBALS['Did']]=URI($GLOBALS['Did'], $user_id,  $db);
			$permission2add[$GLOBALS['Did']] = $info[$GLOBALS['Did']]['add_data'];
			$core_score[$GLOBALS['Did']] = 8;
			
			
			}
		
		
		#echo '<pre>';print_r($input_ids);exit;
		#echo '<pre>';print_r($inserteable);
		#echo '<pre>';print_r($s3ql);exit;
		############################
		#this next segment finds all the s3ids in the query, and checks permission of user/session on it (user/session beause user ccna be using a group)
		#echo '<pre>';print_r($s3ql);
		if (ereg('^(U|G|P|C|R|I|S|F)$', strtoupper(substr($element, 0,1)))) {
		
		foreach ($inserteable as $s3element=>$id) {
			
			if ($s3ql['where'][$id]!='') {
			$element_name = $s3element;
			$id_name = $id;
			
			
			$uid_info=uid(strtoupper(substr($element, 0,1)).$s3ql['where'][$id_name]);
			$element_info = retrieveUIDInfo($s3element, $id, $scoreTable, $s3ql, $letter, $input_ids, $user_id, $db);
			#echo '<pre>';print_r($element_info);
			$info[strtoupper(substr($element_name, 0,1)).$s3ql['where'][$id_name]] = $element_info;			
			$permission2add[strtoupper(substr($element_name, 0,1)).$s3ql['where'][$id_name]] = $element_info['add_data'];
			$core_score[strtoupper(substr($element_name, 0,1)).$s3ql['where'][$id_name]] = $scoreTable[$element_name];

				
			
			#when element id is present (customized elemnt-id, and is the only ID, and id already exists, user cannot recreat it. To update it, he must go through update. That is the only ID that can "Not" exist 
				if ($id==$GLOBALS['s3ids'][$element] && !is_array($element_info)){
					#if a particular id was not found and user is trying to customize a new element_id, then user will have permission to add to it.
					
					$permission2add[strtoupper(substr($element_name, 0,1)).$s3ql['where'][$id_name]] = '1';
				}	
				else
				{
				if(!is_array($element_info) && $uid_info['Did']==$GLOBALS['Did'])#for remote resources, allow insert withour requiring validation.. for now. For inserting projects witha specific uid, 
					{
					return (formatReturn($GLOBALS['error_codes']['no_results'], 'Resource '.strtoupper(substr($element_name, 0,1)).$s3ql['where'][$id_name].' was not found', $format,''));
					#if($s3ql['format']=='')
					#return ('<TABLE><TR><TD>error_code</TD><TD>message</TD></TR><TR><TD>'.ereg_replace('[^(0-9)]', '',$GLOBALS['messages']['something_does_not_exist']).'</TD><TD>>Resource '.strtoupper(substr($element_name, 0,1)).$s3ql['where'][$id_name].' was not found</TD></TR></TABLE>');
					#else
					#return ($GLOBALS['messages']['something_does_not_exist'].'<message>Resource '.strtoupper(substr($element_name, 0,1)).$s3ql['where'][$id_name].' was not found</message>');
					}
				}
			
		}
		}
		#echo 'ola<pre>';print_r($info);exit;
		#echo '<pre>';print_r($permission2add);
		#echo '<pre>';print_r($core_score);
		#exit;
		
		$result = array_combine($core_score, $permission2add);#score as index and permissions as values
		
		#a group and a user can be inserted in any one resource... as long as user does have permission on the resource
				if(ereg('^(U|G)$', $letter))
				{
				
				if($result[min(array_keys($result))] || ($user_info['account_type']=='a') && max(array_keys($result))==8)
				$result[max(array_keys($result))]='1';
				}
				$has_permission2add = $result[max(array_keys($result))];#this means the highest scored element does NOT have permission to add
		
		
		#echo '<pre>';print_r($result);exit;
		#how many IDS?Min ID is 1; if two, then it can be inserting a statement or adding remote resource on local resource
		#print $info
		
		####If any s3ids were found, Variable $info was created, and variable $permission2add was created from the first.
			
		#now,interpret what was found. 
		#Permissions need to be checek if any ID is supplied that already exists. 
		#if (ereg('(group|user|project|collection|rule|item|statement|file)', $element)) {
		if (ereg('(G|U|P|C|R|I|S|F)', strtoupper(substr($element, 0,1)))) {
		
		if (count($info)=='1' || (count($info)=='2' && $info[$GLOBALS['Did']]!='') ||  (count($info)=='2' && ereg('^(statement|file)$', $element))) {
			
			
			#is this ID from the element we are trying to insert?
				#does it exist?
				
				if($s3ql['where'][$GLOBALS['COREids'][$element]]!='' && isLocal($uid, $db) && !$info[$uid]['is_remote']) {#cannot recreate id. Do nothing.
				return(formatReturn($GLOBALS['error_codes']['wrong_input'], $uid.' already exists. Could not recreate it.', $format,''));
				
				}
				elseif (count($info)=='1' && $element_id!='') {
					
					return (formatReturn($GLOBALS['error_codes']['something_missing'], 'Please provide the uid where this '.$element.' should be inserted.', $format,''));
				}
				
				
			
				else {
				
				#take inputs, validate them, check permission on ONE id, create resource. Do the switch cases here.
				
					
					
					if($has_permission2add) {
						#this means the highest value on permission2asd is 1. 
						
					if($info[$uid]['to_create']=='1' || $element_id=='') {
					
					
					$create_info = $s3ql['where'];
					#echo '<pre>';print_r($create_info);	exit;	
					#echo 'ola';exit;
					$inputs = gatherInputs(array('element'=>$element, 'info'=>$info,'to_create'=>$create_info, 'user_id'=>$user_id, 'db'=>$db));
					$info=$inputs;
					#echo 'inputs<pre>';print_r($inputs);exit;
					if(!is_array($inputs))
						{	
						
						return (formatReturn('3', $inputs, $format,''));
						}
					$validity = validateInputs(compact('element', 'inputs', 'oldvalues', 'info', 'db', 'action', 'key','user_id','format'));
					#echo 'validity<pre>';print_r($validity);exit;
					if($validity[0])
					{	
						$key=$s3ql['key'];
						$inserted = insert_s3db(compact('element', 'inputs', 'user_id', 'db', 'key'));
						#echo '<pre>';print_r($inserted);exit;
						
						return (formatReturn('0',$inserted[4], $format, array($element.'_id'=>$inserted[$element.'_id'])));
						
					}
					else {
						#echo '<pre>';print_r($validity);
						return (formatReturn($validity['error_code'],$validity['message'], $format,''));
						}
				}
				elseif($info[$uid]['is_remote']=='1') {#insert the permission on local
			#remote users an dgroups are inserted ON TABLE
			if(ereg('user|group|project', $element))
					{
					#echo '<pre>';print_r($info[$uid]);exit;
					$create_info = $info[$uid];
					$create_info['account_email']=($info[$uid]['account_email']=='')?'s3db@s3db.org':$info[$uid]['account_email'];
					$create_info['account_lid']=($info[$uid]['account_lid']!='')?$info[$uid]['account_lid']:$info[$uid]['account_id'];
					
					$inputs = gatherInputs(array('element'=>$element, 'info'=>$info,'to_create'=>$create_info, 'user_id'=>$user_id, 'db'=>$db));
					#echo '<pre>';print_r($inputs);exit;
					if(!is_array($inputs))
						{return ($inputs);}
					
					$validity = validateInputs(compact('element', 'inputs', 'oldvalues', 'info', 'db', 'action', 'key'));
					#echo '<pre>';print_r($validity);exit;
					if($validity[0])
					{	$key=$s3ql['key'];
						$inserted =insert_s3db(compact('element', 'inputs', 'user_id', 'db', 'key'));
						
						return (formatReturn('0', $element.' inserted.', array($element.'_id'=>$inserted[$element.'_id'], $s3ql['format'])));
						
					}
					else {
						return ($validity[1]);
					}
					}
				
				$permission_info = array('uid'=>$uid,'shared_with'=>'U'.$user_id,'permission_level'=>$info[$uid]['acl']);
					
					$permission_added = insert_permission(compact('permission_info', 'db', 'user_id', 'info'));
					
					if(!$permission_added)
					$permission_added = update_permission(compact('permission_info', 'db', 'user_id', 'info'));
					if($permission_added){
						return (formatReturn($GLOBALS['error_codes']['success'], $uid." shared_with in ".$permission_info['shared_with'], $format,''));
						#return $GLOBALS['messages']['success']."<message> ".$uid." shared_with in ".$permission_info['shared_with']."</message>";
						}
					else {
						return (formatReturn($GLOBALS['error_codes']['something_went_wrong'], "Could not share ".$uid." with ".$permission_info['shared_with'], $format,''));
						#return $GLOBALS['messages']['something_went_wrong']."<message>Could not share ".$uid." with ".$permission_info['shared_with']."</message>";
					}
				}
			}
			
			else {
				$no_permission_id = array_search('0', $permission2add);
				return (formatReturn($GLOBALS['error_codes']['no_permission_message'], 'User does not have permission to insert in '.$no_permission_id, $format,''));
				exit;
				#return ($GLOBALS['messages']['no_permission_message'].' Reason: <message>User does not have permission to insert in '.$no_permission_id.'</message>');
			}
			

			}
			}

			elseif(count($info)>=2) #NOT a physical insert, but a virtual insert in an existing resource
				{	
				
				#echo '<pre>';print_r($info);exit;
				#2 or + ids in info.
				#these IDS can be entity_id OR membership
				
				if($element_id!='' && !$info[$uid]['to_create']) #this automatically means that the second id refers to membership.
				{
				#grant permissions
				
				$shared_with = array_diff(array_keys($permission2add), array($uid));#take uid from the keys of permission2add, that point to the uid we are sharing with
				$shared_with = $shared_with[0];
				
				$add_resource_on_resource = substr(has_permission(compact('uid', 'shared_with'), $db), 2,1);
				
				
				
				if(!$has_permission2add)#statement has rule_id and instance_id, user must have permission on both.
					return (formatReturn($GLOBALS['error_codes']['no_permission_message'], 'User does not have permission to insert in resource '.key($permission2add), $format,''));
					#return ($GLOBALS['messages']['no_permission_message'].'<message>User does not have permission to insert in resource '.key($permission2add).'</message>');

				if($result[max(array_keys($result))]=='0' && $result[min(array_keys($result))]=='1' && $add_resource_on_resource!='1' && $element!='user')
						return (formatReturn($GLOBALS['error_codes']['something_missing'], 'To share '.$uid.' owner of '.$shared_with.' must insert first '.$uid.' in '.$shared_with.'.', $s3ql['format'], ''));
				else {
					#if is remote and user cna insert in resource, must be inserted first
						
						if($info[$uid]['to_create'])
						{	
						
						$create_info = $s3ql['where'];
						#echo '<pre>';print_r($create_info);	exit;			
						$inputs = gatherInputs(array('element'=>$element, 'info'=>$info,'to_create'=>$create_info, 'user_id'=>$user_id));
						
						if(!is_array($inputs))
						return ($inputs);
						
						
						$validity = validateInputs(compact('element', 'inputs', 'oldvalues', 'info', 'db', 'action', 'key'));
						
						
						
						if($validity[0])
							{
							$key=$s3ql['key'];
							$inserted = insert_s3db(compact('element', 'inputs', 'user_id', 'db', 'key'));
							return (formatReturn('0', $element.' inserted.', array($element.'_id'=>$inserted[$element.'_id'], $s3ql['format'])));
							}
						else {
							return ($validity[1]);
						}
						
						}

						if($info[$uid]['is_remote'])
						{
						#the other iD, non element id, should be the upper ID, where user shoulsd already have intert permission
						
						$diff=array_diff(array_keys($permission2add), array($uid));
						$shared_with = $diff[0];
						
						
						$permission_info = array('uid'=>$uid,'shared_with'=>$shared_with,'permission_level'=>$info[$uid]['acl']);
					
						

						$permission_added = insert_permission(compact('permission_info', 'db', 'user_id', 'info'));
					
						if(!$permission_added)
						$permission_added = update_permission(compact('permission_info', 'db', 'user_id', 'info'));
						
						if($permission_added){
						
						return formatReturn($GLOBALS['error_codes']['success'], $uid." inserted in ".$shared_with, $s3ql['format'], '');
						}
						
						else {
						return (formatReturn($GLOBALS['error_codes']['something_went_wrong'], "Could not share ".$uid." with ".$permission_info['shared_with'], $format,''));
						#return $GLOBALS['messages']['something_went_wrong']."<message>Could not share ".$uid." with ".$permission_info['shared_with']."</message>";
					}
						
						}

						if(!$info[$uid]['to_create'] && $s3ql['where']['permission_level']=='')
						{
						#does it exist already in upper resource?
						$diff=array_diff(array_keys($permission2add), array($uid));
						$shared_with = $diff[0];
						
						$sql = str_replace($GLOBALS['regexp'], '=', select(compact('uid', 'shared_with')));
						#echo $sql;exit;
						$db->query($sql, __LINE__, __FILE__);
					
						if($db->next_record())
							return (formatReturn($GLOBALS['error_codes']['repeating_action'], $uid.' already shared with '.$shared_with.'. You can change its level of permission by indicating permission_level.', $s3ql['format'],''));
						}
				}
				
				
				#share according to permissions
				$uid2share = array_search(min($core_score), $core_score);
				$shared_with = array_search(max($core_score), $core_score);
				$uid_info = uid($uid2share);
				

				
				if(($result[max(array_keys($result))]=='1') || ($add_resource_on_resource && $result[min(array_keys($result))]=='1')) #permission to add on upstream resource
					{
					#echo 'ola';exit;
					
					$case ='2';
					$uid_info = uid($uid2share);
					
					if($uid_info['Did']==$GLOBALS['Did'])
						$uid2share = $uid_info['uid'];
					#$uid2share = strtoupper(substr($uid_info['uid'],0,1)).$GLOBALS['Did'].'/'.$uid_info['uid'];
					
					$permission_info = array('uid'=>$uid2share,
													'shared_with'=>$shared_with,
													'permission_level'=>($s3ql['where']['permission_level']!='')?$s3ql['where']['permission_level']:'210',
													);
					
					#echo '<pre>';print_r($permission_info);exit;
					
					$validity = validate_permission(compact('permission_info', 'user_id', 'db', 'info'));#grant project_id permission on rule_id
					#echo $validity;exit;
					
					
					
							if($validity=='0')
								$permission_added = insert_permission(compact('permission_info', 'db', 'user_id', 'info'));#grant rule_id permission on project_id
							elseif($validity=='2')
								$permission_added = update_permission(compact('permission_info', 'db', 'user_id', 'info'));
							elseif($validity=='6' && ereg('^G', $shared_with) && ereg('^U', $uid))
								{
								
								$permission_added = insert_permission(compact('permission_info', 'db', 'user_id', 'info'));#grant rule_id permission on project_id
								$permission_added = update_permission(compact('permission_info', 'db', 'user_id', 'info'));
								}
							#can insert, special case, quick fix
							elseif($validity=='6')
								return (formatReturn($GLOBALS['error_codes']['no_permission_message'], 'User must have permission '.$permission_info['permission_level'].' or greater to grant permission '.$permission_info['permission_level'].' on '.$permission_info['shared_with'], $format,''));
								#return ($GLOBALS['messages']['no_permission_message'].'<message>User must have permission '.$permission_info['permission_level'].' or greater to grant permission '.$permission_info['permission_level'].' on '.$permission_info['shared_with'].'.</message>');
							
					
					}
			
				elseif($result[max(array_keys($result))]=='1' && $result[min(array_keys($result))]=='0') #permission to add on upstream resource
					{
					$case ='1';
					
					if($uid_info['Did']==$GLOBALS['Did'])
						$uid2share= strtoupper(substr($uid_info['uid'],0,1)).$GLOBALS['Did'].'/'.$uid_info['uid'];
					
					$permission_info = array('shared_with'=>$shared_with,
													'uid'=>$uid2share,
													'permission_level'=>'001');
										
					
					$permission_added = insert_permission(compact('permission_info', 'db', 'user_id', 'info'));
					if(!$permission_added)
					$permission_added = update_permission(compact('permission_info', 'db', 'user_id', 'info'));
							#This step will leave rule insert pending until owner of the rule comes by and inserts it in project
					
					
					}
				
				if($permission_added)
						{
						#Missing: Create an entry in access_rules with "Pending" statuss
						if($case =='1')
						return (formatReturn($GLOBALS['error_codes']['success'], "Permission on ".$permission_info['uid']." requested and pending.", $format,''));
						#return $GLOBALS['messages']['success']."<message> Permission on ".$permission_info['uid']." requested and pending.</message>";
						else {
							return (formatReturn($GLOBALS['error_codes']['success'],$permission_info['uid']." inserted in ".$permission_info['shared_with'], $s3ql['format'], ''));
						}
									
						}
					else {
						return (formatReturn($GLOBALS['error_codes']['something_went_wrong'], "Could not share ".$permission_info['uid']." with ".$permission_info['shared_with'], $s3ql['format'],''));
					}
				
				
				}
				elseif($info[$uid]['to_create'] || $info[$uid]['is_remote']) {#insert IF is remote or was asserted to be inserted
					
					if(is_array($info[$uid]) && $info[$uid]['is_remote'])
						$create_info = $info[$uid];
					else
						$create_info = $s3ql['where'];
					
					
					$inputs = gatherInputs(array('element'=>$element, 'to_create'=>$create_info, 'user_id'=>$user_id, 'info'=>$info));
					
					#echo '<pre>';print_r($inputs);exit;
					if(!is_array($inputs))
						return ($inputs);
			
					$validity = validateInputs(compact('element', 'inputs', 'oldvalues', 'info', 'db', 'action', 'key'));
					
					if($validity[0])
					{	$key=$s3ql['key'];
						$inserted =insert_s3db(compact('element', 'inputs', 'user_id', 'db', 'key'));
						
						return (formatReturn('0', $element.' inserted.', array($element.'_id'=>$inserted[$element.'_id']), $s3ql['format']));
						
					}
						else {
						return ($validity[1]);
					}
						
					
						}
					}
				}
			}
			
		
		
		
		#permissions to add are stored in $permission2add, but when we are inserting an existing idA on an existing idB, we do not need permission to add_data on A, only on B. So the users does not need insert permission on idA, if idA is further down the graph then idB.
		#if there is only 1 id, and there is no insert permission, it can break
		
				
		
			#start some special cases
			switch ($element) {
			case 'key':
			{##INSERT KEY
				
					#when no key is given, generate a random one
					if ($s3ql['where']['key_id']=='') 
					$s3ql['where']['key_id'] = random_string('15');
					if($s3ql['where']['expires']=='')
						$s3ql['where']['expires']=date('Y-m-d H:i:s',time() + (1 * 24 * 60 * 60));#expires in 24h

					#user can chose to insert a key for a specific ID, be it group, project, rule or statement (anywhere where permissions can be defined)

					
					$I['inputs'] = array_merge($s3ql['where'], array('account_id'=>$user_id));
					
					$validate = validate_access_key_inputs(array('inputs'=>$I['inputs'], 'db'=>$db, 'user_id'=>$user_id));
					
					switch ($validate)
					{
					case 0: 
						{
						return (formatReturn($GLOBALS['error_codes']['something_missing'],'Expiration date is missing', $s3ql['format'], ''));
						break;
						}
					case 1: 
						{return (formatReturn($GLOBALS['error_codes']['wrong_input'],'Key is too short. Please input a key longer than 10 char', $s3ql['format'], ''));
						break;
						}
					case 2:	
						{return (formatReturn($GLOBALS['error_codes']['wrong_input'],'Invalid date format', $s3ql['format'], ''));
						break;
						}
					case 3:	
						{return (formatReturn($GLOBALS['error_codes']['repeating_action'],'Key '.$s3ql['where']['key_id'].' is not valid. Please chose another key', $s3ql['format'], ''));
						break;
						}
					case 4:	
						{return (formatReturn($GLOBALS['error_codes']['wrong_input'],'Expiration date must be bigger than present date.', $s3ql['format'], ''));
						break;
						}
					
					case 6:	
						{return (formatReturn($GLOBALS['error_codes']['wrong_input'],'UID '.$s3ql['where']['UID'].' does not exist', $s3ql['format'], ''));
						break;
						}
					case 7:	
						{return (formatReturn($GLOBALS['error_codes']['no_permission_message'],'UID '.$s3ql['where']['UID'].' does not belong to user.', $s3ql['format'], ''));
						break;
						}
					case 8:	
						{return (formatReturn($GLOBALS['error_codes']['wrong_input'],'Please use only numbers and letter in your keys.', $s3ql['format'], ''));
						break;
						}
					case 5:	
						{
						add_entry ('access_keys', $I['inputs'], $db);
						
						$output = formatReturn($GLOBALS['error_codes']['success'], 'Key created.',$s3ql['format'], array('key_id'=>$s3ql['where']['key_id']));
						
						return ($output);
						
						}

				
					}
			break;		
			}
			
			case 'file':
			{
				$resource_id = ($s3ql['where']['item_id']!='')?$s3ql['where']['item_id']:$s3ql['where']['instance_id'];
				$rule_id = $s3ql['where']['rule_id'];
				
				$filekey = $s3ql['where']['filekey'];
				$notes = $s3ql['where']['notes'];
				
				if($resource_id=='' ||$rule_id=='' ||$filekey=='')
				{
					return (formatReturn($GLOBALS['error_codes']['something_missing'], 'Please provide all the necessary inputs: rule_id, item_id, filekey', $format,''));
					#return ($GLOBALS['messages']['something_missing'].'<message>Please provide all the necessary inputs: rule_id, item_id, filekey</message>');
							
				}

				#Check permission on inserting statements for specific projects
						#Check permission on inserting statements for specific projects
						$rule_info = $info['R'.$rule_id];
						$instance_info = $info['I'.$resource_id];
						
						#$instance_info = URIinfo('I'.$resource_id, $user_id, $key, $db);
						
						if($rule_info['object']=='UID')
						{
							return (formatReturn($GLOBALS['error_codes']['wrong_input'], 'Please use this query only for rules that do NOT enumerate classes. For inserting on other rules, use the query for insert instance', $format, ''));
							#return $wrong_input."<message>Please use this query only for rules that do NOT enumerate classes. For inserting on other rules, use the query for insert instance</message>";
						
						}
						elseif (!is_array($instance_info)) {
							return (formatReturn($GLOBALS['error_codes']['no_results'], 'Item '.$resource_id.' was not found', $format,''));
							#return ($something_does_not_exist.'<message>Instance '.$resource_id.' was not found</message>');
						}
						elseif ($instance_info['resource_class_id']!=$rule_info['subject_id']) {
							return (formatReturn($GLOBALS['error_codes']['wrong_input'],'Subject of rule does match Class of instance',$format,''));
						#return $wrong_input."<message>Subject of rule does match Class of instance</message>";
						}
						elseif($filekey=='')
							return (formatReturn($GLOBALS['error_codes']['something_missing'], 'Please indicate a filekey for this file',$format,''));
							#return $wrong_input."<message>Please indicate a filekey for this file</message>";
						

				
				#Find out if the file already exists in the tmp directory
				$fileFinalName = get_entry('file_transfer', 'filename', 'filekey', $filekey, $db);
				$file_id = get_entry('file_transfer', 'file_id', 'filekey', $filekey, $db);
				ereg('([A-Za-z0-9]+)\.*([A-Za-z0-9]*)$',$fileFinalName, $tokens);
				$name = $tokens[1];
				$extension= $tokens[2];
				#list($name, $extension) = explode('.', $fileFinalName);
				$maindir = $GLOBALS['s3db_info']['server']['db']['uploads_folder'].$GLOBALS['s3db_info']['server']['db']['uploads_file'].'/tmps3db';
				

				$old_file = $maindir.'/'.$file_id.'.'.$extension;
				
					if(!is_file($old_file)) 
					return (formatReturn($GLOBALS['error_codes']['something_does_not_exist'], 'File not found, please upload file first.', $format,''));
					#return $something_does_not_exist."<message>File not found, please upload file first.</message>";
					else
					{
						
								#project_id will be that of the rule, except if user does not have permission on it.
							$project_info = URI('P'.$rule_info['project_id'], $user_id, $db);
						
							$project_id = ($s3ql['where']['project_id']!='')?$s3ql['where']['project_id']:(($project_info['add_data'])?$class_info['project_id']:'');

							if($project_id =='')
						#find which of the user projects can insert instances in this class.
							{
							$project_id = $rule_info['project_id'];
							#$user_projects = findUserProjects($user_id, $db);
							//							$user_projects = array_map('grab_project_id', $user_projects);
							//							
							//							
							//							#find the projects that can access the rule
							//							$allowed_projects = array_filter(explode('_', $rule_info['permission']));
							//							
							//							$both = array_intersect($allowed_projects, $user_projects);
							//							
							//							if (is_array($both)) {
							//								foreach ($both as $key=>$allowed_project_id) {
							//									if(substr(has_permission(array('uid'=>'R'.$rule_id, 'shared_with'=>'P'.$allowed_project_id), $db), 2,1))
							//										$project_id = $allowed_project_id;
							//								}
							//							}
							}

							
						
						if($project_id=='') 
							return (formatReturn($GLOBALS['error_codes']['something_went_wrong'], 'Failed to find a project_in for this intance', '', $s3ql['format']));
						
								$value = project_folder_name ($project_id, $db);
								$created_by = $user_id;
								$filesize = filesize($old_file);
								$filename = $fileFinalName;
								
								##Create the row in the statements table
								$create_info = $s3ql['where'];
								#echo '<pre>';print_r($s3ql);
								$inputs = gatherInputs(array('element'=>'file', 'info'=>$info,'to_create'=>$create_info, 'user_id'=>$user_id, 'db'=>$db));
								$info=$inputs;
								
								if(!is_array($inputs))
									{	
									return(formatReturn('3', $inputs, $s3ql['format'],''));
									}
								$validity = validateInputs(compact('element', 'inputs', 'oldvalues', 'info', 'db', 'action', 'key','user_id'));
								#echo '<pre>';print_r($validity);exit;
								if($validity[0])
								{	
									$key=$s3ql['key'];
									$inserted = insert_s3db(compact('element', 'inputs', 'user_id', 'db', 'key'));
									
									##Move the file
									$S = compact('user_id', 'project_id', 'resource_id', 'rule_id', 'value', 'notes', 'created_by', 'filename', 'filesize', 'extension', 'db');
									$S['statement_id']=$inserted['statement_id'];
									$S['uploadedfile'] = $old_file;
									
									$fileRelocated = movefile2folder($S);
									
									if(!$fileRelocated)#delete the statement
									{$sql = "delete from s3db_statement where statement_id = '".$S['statement_id']."'";
									$db->query($sql, __FILE__, __LINE__);
									#echo $sql;
									return (formatReturn('2', 'File could not be imported. Please try again.', '', $s3ql['format']));
									
									#unlink($old_file);
									}
									else{
									return (formatReturn($GLOBALS['error_codes']['success'], 'File inserted.', $s3ql['format'], array('file_id'=>$inserted['1'])));
									#if($s3ql['format']=='')
									#	return ('<TABLE><TR><TD>error_code</TD><TD>message</TD><TD>'.$element.'_id</TD></TR><TR><TD>'.ereg_replace('[^(0-9)]', '', $inserted[3]).'</TD><TD>'.$inserted[4].'</TD><TD>'.$inserted[$element.'_id'].'</TD></TR></TABLE>');
																			
									#else
									#	return ($inserted[1]);
									}
									
								}
								else {
									#echo '<pre>';print_r($validity);
									return (formatReturn(ereg_replace('[^(0-9)]', '', $inserted[3]), $validity[1], $format,''));
									#if($s3ql['format']=='')
									#	return ('<TABLE><TR><TD>error_code</TD><TD>message</TD></TR><TR><TD>'.ereg_replace('[^(0-9)]', '', $inserted[3]).'</TD><TD>'.$validity[1].'</TD></TR></TABLE>');
									#else
									#return ($validity[1]);
								}
							
								##Move the file
								if($statement_inserted)
									{	
									
									$S['statement_id']=find_latest_UID('statement', $db);
									$S['uploadedfile'] = $old_file;
									$fileRelocated = movefile2folder($S);

									if ($fileRelocated) 
										{
										
										return (formatReturn($GLOBALS['error_codes']['success'], "File inserted", array('file_id'=>$S['file_id']), $s3ql['format']));

															
										}
									else {
										return (formatReturn($GLOBALS['error_codes']['something_went_wrong'], 'Failed to move file', $format,''));
									}
									#else return $something_went_wrong."<message>Failed to move file</message>";
									
						
									}
										
					} 
					
					#This ends "is not a file"
			}#This ends insert file

			break;
			}#finish element switch
		break;
		} #Finish insert
	case 'edit':
		{
		if($s3ql['edit']=='class')
			$s3ql['edit']='collection';
		if($s3ql['edit']=='instance')
			$s3ql['edit']='item';
		if($s3ql['set']['notes']!='')
			$s3ql['set']['notes'] = utf8_encode($s3ql['set']['notes']);
		if($s3ql['set']['value']!='')
			$s3ql['set']['value'] = utf8_encode($s3ql['set']['value']);


		#$element = $s3ql[$action];
		$element = $s3ql['edit'];
		#echo 'ola<pre>';print_r($s3ql);exit;
		$set = array('project'=>array('project_name', 'project_description', 'project_owner', 'permission_level'),
						'collection'=>array('project_id', 'entity', 'notes'),
						'rule'=>array('project_id', 'subject', 'verb', 'object', 'subject_id', 'verb_id', 'object_id', 'notes', 'validation'),
						'item'=>array('project_id', 'collection_id', 'notes'),
						'statement'=>array('project_id', 'item_id', 'rule_id', 'value', 'notes'),
						'user'=>array('account_lid','account_pwd', 'account_uname', 'account_email', 'account_phone', 'addr1', 'addr2', 'account_type', 'city', 'postal_code', 'state', 'country', 'account_status'),
						'group'=>array('account_lid'));
			
		$E = compact('db', 'user_id', 's3ql');
		
		#first of all, is this a valid target?
		if(!in_array($s3ql['edit'], array_keys($set)))
		{
			return formatReturn($GLOBALS['error_codes']['wrong_input'], $s3ql['edit']." is not a valid S3DB element. Valid elements: project, collection, rule, item, statement", $s3ql['format'],'');
		
		}
		
		#is there an ID to locate the appropriate resource?
		if($s3ql['where'][$element.'_id'] == '')
			{
				return formatReturn($GLOBALS['error_codes']['something_missing'], 'ID of '.$element.' to edit is missing', $s3ql['format'],'');
				
			}
		
		if($s3ql['set']=='')
		   {	
			#is it in where?
			$s3ql['set']=array_diff_key($s3ql['where'], array($element.'_id'=>''));
			if($s3ql['set']=='')
			return formatReturn($GLOBALS['error_codes']['something_missing'], 'Please specify what you want to update.'.$syntax_message, $s3ql['format'],'');
			
			}
		
		#interpret input
		$s3map=$GLOBALS['s3map'];
		
				
				foreach ($s3map[$GLOBALS['plurals'][$element]] as $alter_name=>$name) {
						if($s3ql['set'][$alter_name]!='')
						$s3ql['set'][$name]=$s3ql['set'][$alter_name];
					
				}
			
				$s3ql['set'] = array_diff_key($s3ql['set'], $s3map[$GLOBALS['plurals'][$element]]);
				$s3ql['set'] = array_filter($s3ql['set']);
			

		
		#detect is something that is something in set that cannot be updated
		$test_set = array_intersect($set[$element], array_keys($s3ql['set']));
		$extra_fields = array_diff(array_keys($s3ql['set']), $test_set);
		
		if(count($s3ql['set'])>count($test_set))#this means that there are fields that don't exist 
		foreach ($extra_fields as $field_name) {
			
			
			$output .= '<message>Warning: '.$field_name.' is not a valid property of '.$element.'. '.$field_name.' will not be updated. Valid properties: '.rtrim(array_reduce($set[$element], 'comma_split'), ', ').'</message>';
		}
		
		#retrieve information about resource					
				$element_id = $s3ql['where'][$element.'_id'];
				$uid = strtoupper(substr($element,0,1)).$element_id;
				$e_info=URIinfo($uid, $user_id, $key, $db);
				#echo '<pre>';print_r($e_info);
				
				if(!is_array($e_info))
				return (formatReturn($GLOBALS['error_codes']['something_does_not_exist'], ''.$element.' '.$element_id.' was not found.'));
				elseif(!$e_info['change'])
				return (formatReturn($GLOBALS['error_codes']['no_permission_message'], 'User does not have permission to change this '.$element, $s3ql['format'],''));

		
				
				foreach ($e_info as $field=>$data) {
					
					if($s3ql['set'][$field]!='' || $field=='notes')
						if(in_array($field, $set[$element]))
						{
						
						$oldvalues[$field] = $e_info[$field];
						$e_info[$field] = $s3ql['set'][$field];
						$inputs[$field] = $s3ql['set'][$field];
						}
					
				}
				
		
		#echo '<pre>';print_r($inputs);
		switch ($element) {
			
		
		case 'user':{##EDIT USER
			$user_to_change_info = get_info('user', $element_id, $db);#this is necessary because password will not come in the $e_info var. 
			
			#permission was checked before the switch
			
			#map values
								$s3map = array('login'=>'account_lid',
											'password'=>'account_pwd',
											'username'=>'account_uname',
											'email'=>'account_email',
											'phone'=>'account_phone',
											'address'=>'addr1',
											'address2'=>'addr2',
											'city'=>'city',
											'state'=>'state',
											'postal_code'=>'postal_code',
											'country'=>'country',
											'account_type'=>'account_type');

			#encript the password
			
			#echo '<pre>';print_r($s3ql);exit;
			if ($s3ql['set']['password']!='' || $s3ql['set']['account_pwd']) {
				$s3ql['set']['password'] = ($s3ql['set']['account_pwd']!='')?md5($s3ql['set']['account_pwd']):md5($s3ql['set']['password']);
			}
			else {
				$s3ql['set']['password']=$user_to_change_info['account_pwd'];
			}
				#echo '<pre>';print_r($s3ql);
			#login, password and email cannot be deleted so if they come empty, fill them out with the old values
			$non_erasable = array('login', 'email', 'username', 'password');
			
			foreach ($non_erasable as $fieldname) {
			if (in_array($fieldname, array_keys($s3ql['set']))) {
				if ($s3ql['set'][$fieldname]=='') {
				return (formatReturn($GLOBALS['error_codes']['wrong_input'], 'login, email, username and password cannot be deleted', $s3ql['format'],''));
				}
			}
			elseif (!in_array($fieldname, array_keys($s3ql['set']))) {
			#then start filling out input with the old values
			
			$inputs[$s3map[$fieldname]] = $e_info[$s3map[$fieldname]];
			}
			}
			#now map the valid values
			foreach (array_keys($s3ql['set']) as $set) {
							
			if (in_array($set, array_keys($s3map))) {
				
				if($s3ql['set'][$set]!='') {
				
					$inputs[$s3map[$set]] =$s3ql['set'][$set];
				
				}
			}
			
			}
			#echo '<pre>';print_r($e_info);
			$inputs['account_type'] = ($s3ql['set']['account_type']!='')?$s3ql['set']['account_type']:$user_to_change_info['account_type'];
			$inputs['account_status'] = ($s3ql['set']['account_status']!='')?$s3ql['set']['account_status']:$user_to_change_info['account_status'];
			$inputs['account_group'] = $inputs['account_type'];
			
			#replace in $e_info the values with the inputs. First clean the existing one, then merge with the new one
			$user_info = array_diff_key($e_info, $inputs);
			$user_info = array_merge($user_info, $inputs);
			
			
			$validity = validate_user_inputs(array('inputs'=>$inputs, 'imp_user_id'=>$e_info['account_id'], 'db'=>$db, 'action'=>'update'));
			
			$info=$e_info;
			#echo '<pre>';print_r($inputs);
			#$validity = validateInputs(compact('element','info', 'inputs', 'oldvalues', 'user_id', 'db'));
			#echo '<pre>';print_r($validity);
			switch($validity)
			{
				case 0:
				#echo '<pre>';print_r($user_info);	exit;
				if(!update_user(compact('user_info', 'db', 'user_id')))
					{
						#$output .= $something_went_wrong;
						return (formatReturn($GLOBALS['error_codes']['something_went_wrong'], 'User could not be updated. Undetermined reasons.', $s3ql['format'], ''));
					}
					else {
						#$output .= $GLOBALS['messages']['success'];	
						#$output .= '<message> User updated</message>';
						return (formatReturn($GLOBALS['error_codes']['success'],'User updated', $s3ql['format'],''));
											
					}
					break;
				case 1:
					
					#$output .= $GLOBALS['messages']['something_missing'];
					#$output .= '<message> Login is missing.'.$syntax_message.'</message>';
					return (formatReturn($GLOBALS['error_codes']['something_missing'], 'Login is missing.'.$syntax_message, $s3ql['format'],''));
					break;
				case 2:
					
					#$output .= $GLOBALS['messages']['something_missing'];
					#$output .= '<message> Account_type is missing.'.$syntax_message.'</message>';
					return (formatReturn($GLOBALS['error_codes']['something_missing'], 'Account_type is missing.'.$syntax_message,$s3ql['format'],''));
					break;
				case 3:
					#$output .= $GLOBALS['messages']['something_missing'];
					#$output .= '<message> Username is missing.'.$syntax_message.'</message>';
					return (formatReturn($GLOBALS['error_codes']['something_missing'], 'Username is missing.'.$syntax_message, $s3ql['format'],''));
					break;
				case 4:
					#$output .= $GLOBALS['messages']['something_missing'];
					#$output .= '<message> Account status is missing.'.$syntax_message.'</message>';
					return (formatReturn($GLOBALS['error_codes']['something_missing'], 'Account status is missing.'.$syntax_message, $s3ql['format'],''));
					break;
				case 5:
					$output .= $GLOBALS['messages']['something_missing'];
					$output .= '<message> Password is missing.'.$syntax_message.'</message>';
					return (formatReturn($GLOBALS['error_codes']['something_missing'], 'Password is missing.'.$syntax_message, $s3ql['format'],''));
					break;
				case 6:
					#$output .= $GLOBALS['messages']['something_missing'];
					return (formatReturn($GLOBALS['error_codes']['something_missing'], '',$s3ql['format'],''));
					break;
				case 7:
					#$output .= $GLOBALS['messages']['something_missing'];
					return (formatReturn($GLOBALS['error_codes']['something_missing'], '',$s3ql['format'],''));
					break;
				case 8:
					#$output .=$GLOBALS['messages']['repeating_action'].'<message> User '.$inputs['account_lid'].' already exists</message>';
					return (formatReturn($GLOBALS['error_codes']['something_missing'], 'User '.$inputs['account_lid'].' already exist',$s3ql['format'],''));
					break; 
				}
				
		break;
		}
		case 'group':{##EDIT GROUP
			
			$info = $e_info;
			$group_id = $info['group_id'];
			
			$validity = validateInputs(compact('element', 'inputs', 'oldvalues', 'info', 'db', 'action', 'key'));
			if($validity[0])
					{
						if (update_group(compact('inputs', 'group_id', 'user_id', 'db'))) {
							return (formatReturn($GLOBALS['error_codes']['success'], 'G'.$group_id.' successfully updated',$s3ql['format'],''));
							#return ($GLOBALS['messages']['success'].'<message>G'.$group_id.' successfully updated</message>');
						}
						else {
							return (formatReturn($GLOBALS['error_codes']['something_went_wrong'], 'G'.$group_id.' could not be updated. Reason undetermined.',$s3ql['format'],''));
							#return ($GLOBALS['messages']['something_went_wrong']);
						}
						break;
					}
			else {
				return ($validity[1]);
			}
				
			break;
			}	
		case 'project':	{##EDIT PROJECT
					
				$project_info =  $e_info;
				$U = compact('project_info', 'db', 'user_id');
				#$validity = validate_project_inputs($U);
				$info = $e_info;
				$validity = validateInputs(compact('element', 'inputs', 'oldvalues', 'info', 'db', 'action', 'key'));
				if($validity[0])
				{
					if(update_project($U))
						{
							return formatReturn($GLOBALS['error_codes']['success'], $element." P".$element_id." updated.", $s3ql['format'],'');
							
							
						}
						else
						{
							return formatReturn($GLOBALS['error_codes']['something_went_wrong'], 'Failed to update project!',$s3ql['format'],'');
						}
				}
				else {
					#break validity in error and message
					ereg('<error>([0-9]+)</error>(.*)<message>(.*)</message>', $validity[1], $valOut);
					return (formatReturn($valOut[1],$valOut[3], $s3ql['format'],''));
				}
				
			break;	
		}
			
		case 'collection':{##EDIT CLASS
			$resource_info = $e_info;
			$editresource = compact('db','user_id', 'resource_info', 'action', 'inputs', 'oldvalues');
			$info = $e_info;
			$validity = validateInputs(compact('element', 'inputs', 'oldvalues', 'info', 'db', 'action', 'key'));
			#echo '<pre>';print_r($validity);exit;
			#echo $validity = validate_resource_inputs($editresource);exit;
			if($validity[0]){
				if(update_resource($editresource))
						{#$validity[1].'<br><message>'.$element.' updated</message>';
						$output .= formatReturn('0', $element." C".$element_id.' updated', $format, '');
						return ($output);
						#return ($output);
						}
			}
			else {
				ereg('<error>([0-9]+)</error>(.*)<message>(.*)</message>', $validity[1], $valOut);
				return (formatReturn($valOut[1],$valOut[3], $s3ql['format'],''));
				#return ($validity[1]);
			}
			
		
		break;
		}
		case 'item':{##EDIT INSTANCE
		
			#echo '<pre>';print_r($oldvalues);exit;
			#Does this resource exist?
			$instance_id = $element_id;
			$info = $e_info;
			$notes = $s3ql['set']['notes'];
			
			$R = compact('info', 'inputs', 'oldvalues','db', 'user_id');
			$validity = validateInputs(compact('element', 'inputs', 'oldvalues', 'info', 'db', 'action', 'key'));
			if($validity[0]){
				if(update_resource_instance($R))
				
						{
											
						return (formatReturn('0',$element." I".$element_id." updated", $s3ql['format'],''));
						
						#$action = 'edit';
						#$statement_info = $info;
						
						#return ($output);
						}
			}
			else {
				ereg('<error>([0-9]+)</error>(.*)<message>(.*)</message>', $validity[1], $valOut);
				return (formatReturn($valOut[1],$valOut[3], $s3ql['format'],''));
				#return ($validity[1]);
			}
			
		
		break;
		}
		
		case 'rule':##EDIT RULE
		{
				
				$rule_id = $element_id;
				$info = $e_info;
				
				if($info['object']=='UID')
				{
					if($s3ql['where']['subject']!='')
					{#redirect to change class?
						
						$res3ql =array_diff_key($s3ql, array('edit'=>'', 'update'=>''));
						$res3ql['where'] = array_diff_key($res3ql['where'], array('rule_id'=>''));
						$res3ql = array_merge($res3ql, compact('db', 'user_id'));
						$res3ql['edit']='class';
						$res3ql['where']['class_id']=$info['subject_id'];

						
						$done = S3QLaction($res3ql);
						
						return ($done);
					}
					else {
						return (formatReturn($GLOBALS['error_codes']['wrong_input'], 'Rule '.$element_id.' cannot be edited. To change the subject of the relation please use edit class', $s3ql['format']));
					}
				}
				else
					{
				#permission was verified before switch
				if ($s3ql['set']['subject_id']!='') {
					
					#for log, need to keep track of old literal as well.
					$oldvalues['subject'] = $info['subject'];
					
					
					$class_info = s3info('class', $s3ql['set']['subject_id'], $db);
						if (!is_array($class_info)) {
							return (formatReturn($GLOBALS['error_codes']['something_does_not_exist'], 'Class '.$s3ql['set']['subject_id'].' does not exist', $format,''));
							#return ($something_does_not_exist.'<message>Class '.$s3ql['set']['subject_id'].' does not exist</message>');
						}
					$info['subject_id'] = $s3ql['set']['subject_id'];
					$info['subject']=$class_info['entity'];
				}
				else {
					if ($s3ql['set']['subject']!='') {
					$oldvalues['subject_id'] = $info['subject_id'];#for log, need to keep track of old literal as well.
					$info['subject_id'] = fastClassID(array('entity'=>$s3ql['set']['subject'],'project_id'=>$project_id, 'db'=>$db));
					
					
				}
				
				}
				if ($s3ql['set']['verb_id']!='') {
					$oldvalues['verb'] = $info['verb'];#for log, need to keep track of old literal as well.

					$instance_info = URI('I'.$s3ql['set']['verb_id'], $user_id, $db);
					if (!is_array($instance_info)) {
							return (formatReturn($GLOBALS['error_codes']['something_does_not_exist'], 'Instance '.$s3ql['set']['verb_id'].' does not exist', $s3ql['format'],''));
						}
						else {
							$info['verb'] = $instance_info['notes'];
						}
				}
				else {
					
				#turn a literal verb into an instance of a class
				#class exists in project? no? create it;else find it's
				$VerbClass = projectVerbClass(array('project_id'=>$info['project_id'], 'db'=>$db,'user_id'=>$user_id));
				
				if(!$VerbClass)
				{
				
				$to_create = array('project_id'=>$info['project_id'], 'entity'=>'s3dbVerb', 'notes'=>'Collection created by S3DB for holding Verbs');
				$inputs = gatherInputs(array('element'=>'collection', 'to_create'=>$to_create, 'db'=>$db, 'user_id'=>$user_id));
				$inserted = insert_s3db(array('element'=>'collection', 'inputs'=>$inputs, 'user_id'=>$user_id, 'db'=>$db));
				
				#try again;
				$VerbClass = projectVerbClass(array('project_id'=>$info['project_id'], 'db'=>$db,'user_id'=>$user_id));
				}
				
				#now create the instanceVerb
				if($VerbClass=='')
					return (formatReturn($GLOBALS['error_codes']['something_went_wrong'], 'Rule Could not be updated. No collection was found for the verbs', $s3ql['format'],''));
				
				$inputs = array('resource_class_id'=>$VerbClass['resource_id'], 'project_id'=>$info['project_id'], 'notes'=>($s3ql['where']['verb']!='')?$s3ql['where']['verb']:$info['verb'], 'created_by'=>$user_id, 'entity'=>$VerbClass['entity'], 'status'=>'A');
				
				$inserted = insert_s3db(array('element'=>'instance', 'inputs'=>$inputs, 'user_id'=>$user_id, 'db'=>$db));
				
				$info['verb_id']=$inserted['instance_id'];
				$info['verb']=verb4instanceID(array('key'=>$s3ql['key'],'instance_id'=>$info['verb_id'], 'db'=>$db));
			#echo '<pre>';print_r($info);exit;
			}
				
			if ($s3ql['set']['object_id']!='') {
					$oldvalues['object'] = $info['object'];#for log, need to keep track of old literal as well.
					$class_info = URI('C'.$s3ql['set']['object_id'], $user_id, $db);
					
					
						if (!is_array($class_info)) {
							return (formatReturn($GLOBALS['error_codes']['something_does_not_exist'], 'Collection '.$s3ql['set']['object_id'].' does not exist', $s3ql['format'],''));
						}
					
					$info['object_id']=$s3ql['set']['object_id'];
					$info['object']=$class_info['entity'];
					
				}
				else {
					if ($s3ql['set']['object']!='') {
					$oldvalues['object_id'] = $info['object_id'];#for log, need to keep track of old literal as well.
					
					$info['object_id'] = fastClassID(array('entity'=>$s3ql['set']['object'],'project_id'=>$project_id, 'db'=>$db));
					}
				}
				
				
				$validity = validateInputs(compact('element', 'inputs', 'oldvalues', 'info', 'db', 'user_id', 'action', 'key'));
				#echo '<pre>';print_r($info);exit;
				if($validity[0]){
					if(update_rule(compact('info','inputs', 'oldvalues', 'db', 'user_id')))
							{
							
							return (formatReturn('0',$element.' updated', $s3ql['format'],''));
							#$output .= $validity[1].'<br><message>'.$element.' updated</message>';
						
							#return ($output);
							}
					else {
						return formatReturn($validity[1], $validity[2], $format, '');
					}
				}
				else {
						return formatReturn($validity[1], $validity[2], $format, '');
					}
				
				}
				break;
		#This closes edit rules
		}
		case 'statement':##EDIT STATEMENT
		{
				$statement_id = $element_id;
				$value = $s3ql['set']['value'];
				$notes = $s3ql['set']['notes'];

				
				$rule_id = get_entry('statement', 'rule_id', 'statement_id', $statement_id, $db);
				$object = get_entry('rule', 'object', 'rule_id', $rule_id, $db);
				
				$statement_info = $e_info;
				
				$project_id = $statement_info['project_id'];
				$acl = find_final_acl($user_id, $project_id, $db);

				#When the value is not being updated, use the old value for the update
				if ($value=='') 
					$value = $statement_info['value'];
				if(!in_array('notes', array_keys($s3ql['set'])))
					$notes  = $statement_info['notes'];
				
				#Does the user have permission to change this statement?
				if(!$statement_info['change'])
				{return formatReturn($GLOBALS['error_codes']['no_permission_message'], "User does not have permission to change this statement", $s3ql['format'], '');	
				
				}
				
				#Must be a valid resource UID when the object of the rule is a resource
				elseif (resourceObject(array('rule_id'=>$statement_info['rule_id'], 'project_id'=>$project_id, 'db'=>$db)) && !resource_found(array('rule_id'=>$statement_info['rule_id'], 'user_id'=>$user_id, 'project_id'=>$project_id, 'value'=>$value,'db'=>$db)))
				{return formatReturn($GLOBALS['error_codes']['wrong_input'], "Value for this statement must be a valid resource_id from class ".$object, $s3ql['format'], '');
				
				}
				
				#For statements that contain files, user must delete the statement first and add the updated version
				elseif($statement_info['filename']!='')
				{return formatReturn($GLOBALS['error_codes']['wrong_input'],"Statements that contain files must be deleted first and the updated version of the file uploaded",$s3ql['format'], '');
				
				}
				elseif(!validate_statement_value($statement_info['rule_id'],$value, $db))
				{$rule_info = s3info('rule', $statement_info['rule_id'], $db);
				return (formatReturn($GLOBALS['error_codes']['wrong_input'],'The rule of this statement requires validation. Please input value in the format: '.$rule_info['validation'],$s3ql['format'], ''));
				}
				else
					{
					
					
					$modified_by = $user_id;
					#$oldvalues = array_filter($oldvalues);
					#$inputs = array_filter($inputs);
					
					#echo '<pre>';print_r($oldvalues);exit;
					$S = compact('statement_id', 'statement_info', 'oldvalues', 'inputs', 'value', 'notes', 'modified_by', 'db', 'user_id');
					$updated = update_statement($S);

					if($updated) 
						{
					
						return (formatReturn($GLOBALS['error_codes']['success'], $element." updated", $s3ql['format'], ''));
						}
					else return formatReturn($GLOBALS['error_codes']['something_went_wrong'], 'Statement update failed.', $s3ql['format'], '');

					}
							
				
		break;
		
		}
		}#close switch element
		break;
		}#close switch action
	case 'delete':
		{
		#echo '<pre>';print_r($s3ql);
			if($s3ql['delete']=='class')
				$s3ql['delete']='collection';
			if($s3ql['delete']=='instance')
			$s3ql['delete']='item';
			
			#echo '<pre>';print_r($s3ql);
			#map queries
			if($s3ql['where']['resource_id']!='')
				if($s3ql['delete']=='instance')
				$s3ql['where']['item_id'] = $s3ql['where']['resource_id'];
				elseif ($s3ql['delete']=='class') {
				$s3ql['where']['collection_id'] = $s3ql['where']['resource_id'];
			}
			
			
			$element = $s3ql['delete'];
			$letter = letter($element);
			$possible = array('key','project','collection','rule','item', 'statement','user', 'group', 'permission');
			
			$D = compact('db', 'user_id', 's3ql');

			if(!in_array($element, array_keys($possible)))
				{#return $not_a_query;
				return (formatReturn($GLOBALS['error_codes']['not_a_query'], $element.' is not a valid s3db element.', $format, ''));
				}
			
			
			#is there an ID to locate the appropriate resource?
			$element_id = $s3ql['where'][$element.'_id'];
			
			if($element_id=='' && $element!='permission')
				return(formatReturn($GLOBALS['error_codes']['something_missing'], 'Please specify '.$element.'_id'.' to delete', $format,''));
				#return ($something_missing.'<message>Please specify '.$element.'_id'.' to delete</message>');
			
			$uid_info=uid($element_id);
			if(!ereg('^(U|G|P|C|R|I|S)', $uid_info['uid']))
				$uid_info['uid'] = strtoupper(substr($element,0,1)).$uid_info['uid'];
			$uid =$uid_info['uid'];
			#$e_info = URIinfo($uid['Did'].'/'.$uid['uid'],$user_id,$key, $db);
			
			#how many "deleateable" ids are on the query?
			$deleteable = array(
						'group'=>'group_id',
						'user'=>'user_id',
						'project'=>'project_id',
						'rule'=>'rule_id', 
						'collection'=>'collection_id',
						'item'=>'item_id',
						'statement'=>'statement_id');

			
			#scoreTable will allow us to score the elements according to their position in the inheritance model. To chose the correct permission level
		
			$scoreTable=array_reverse($deleteable, 0);
			$scoreTable = array_combine(array_keys($scoreTable), range(1,count($deleteable)));
			
			
			if(ereg('user|project|group', $element) && count(array_filter(array_diff_key($s3ql['where'], array($element.'_id'=>'', 'confirm'=>''))))==0)
			{
			
			$s3ql['where']['deployment_id']=substr($GLOBALS['Did'],1,strlen($GLOBALS['Did']));
			$info[$GLOBALS['Did']]=URI($GLOBALS['Did'], $user_id, $db);
			
			$permission2delete[$GLOBALS['Did']] = $info[$GLOBALS['Did']]['delete_data'];
			$core_score[$GLOBALS['Did']] = 8;
			#if(ereg('user|group', $element) && )
			#$s3ql['flag']='resource'; #delete just the resource 'user', 'group' or 'project';
			}
			
			#echo '<pre>';print_r($s3ql);exit;
			if (ereg('(user|group|project|collection|rule|item|statement|file|permission)', $element)) {
			
			foreach ($deleteable as $s3element=>$id) {
			#echo $s3element;
			if ($s3ql['where'][$id]!='') {#for this, this will allow removing 1 permission at a time.
				$element_name = $s3element;
				$id_name = $id;
				$uid4info = uid($s3ql['where'][$id]);
				#if(!ereg('^(U|G|P|C|I|S|R)', $letter))
				$uid4info['uid'] = strtoupper(substr($element_name, 0,1)).$uid4info['uid'];
				
				$uid2check=$uid4info['uid'];
				$element_info = URIinfo($uid2check, $user_id, $key, $db);
				
				$info[strtoupper(substr($element_name, 0,1)).$s3ql['where'][$id_name]] = $element_info;		
				$permission2delete[strtoupper(substr($element_name, 0,1)).$s3ql['where'][$id_name]] = $element_info['delete_data'];
				$core_score[strtoupper(substr($element_name, 0,1)).$s3ql['where'][$id_name]] = $scoreTable[$element_name];

				
				#when deleting a rule on a project, user does not need to be able to change the rule, he only needs to be able to change project.
				#$core_score = 
				
				if(!is_array($element_info))
					if($uid4info['Did']==$GLOBALS['Did'])
				{
					return (formatReturn($GLOBALS['error_codes']['something_does_not_exist'], 'Resource '.strtoupper(substr($element_name, 0,1)).$s3ql['where'][$id_name].' was not found', $format, ''));
					
				}
					


				}
				
			}
			#echo '<pre>';print_r($info);exit;
			
			}
			
			#if user is unlinking a resource from another, he needs permission on the upstream one, 
			
			#for example, owner of a porject that is deleting a rule from a project. The owner of the project who does not want to share the rule anymore should instead remove grant permissions from it. Similar as in "insert", but the other way around
			

			#the simplest case is when a user is "removing himself" from a resource - that is when flag is standard and there is only 1 s3id.
			#echo '<pre>';print_r($core_score);exit;
			
	if (ereg('(user|group|project|collection|rule|item|statement|file)', $element)) {
		if(count($core_score)=='1' || (count($core_score)=='2' && $info[$GLOBALS['Did']]!=''))
			{
			
			$s3ql['flag']=($s3ql['flag']!='')?$s3ql['flag']:'all';
			#$uid2delete = key($permission2delete);
			
			#when user requests to be removed from a resource, remove resource and all dependencies where user has access. 
			$children = array('deployment'=>array('project', 'user', 'group'),
							 'user'=>array(),
							'group'=>array(),
							'project'=>array('rule', 'collection'),
							  'rule'=>array('statement'),
							  'collection'=>array('item'), 
							  'item'=>array('statement'),
							  'statement'=>array());
							#even though deleting rule would mean deleting all statements on this class, permission on all statements must be verified as it is downstram
				
				#while there are children, build dependencies. Some resources have + 1 child
				$dependencies = array();
				
				
				foreach ($children[$element] as $child) {
				$Ds3ql=compact('user_id','db');
				$Ds3ql['from']=$child;
				$Ds3ql['where'][$element.'_id']=$element_id;
				$tmp = S3QLaction($Ds3ql);
				
				if(is_array($tmp))			
				$dep_resource[$child] =$tmp;
				
				if(is_array($dep_resource[$child]))
				foreach ($dep_resource[$child] as $key=>$Dinfo) {
					
					$dep_key = strtoupper(substr($child, 0,1)).$Dinfo[$GLOBALS['s3ids'][$child]];
					$info[$dep_key]=$Dinfo;
					$dependencies[$dep_key]=$Dinfo; #when deleitng any element the parent_id in this case will be the id we are trying to delete

					if(!empty($children[$child]))
						{
							$Gs3ql=compact('user_id','db');
							$Gs3ql['from']=$children[$child][0];
							$Gs3ql['where'][$child.'_id']=$Dinfo[$child.'_id'];
							$tmp = S3QLaction($Gs3ql);
							if(is_array($tmp))
							$dep_resource[$children[$child][0]] = $tmp;
							
						}
				}
				
				
				}
				
				
				#to avoid key = 0 on change, push the array 1 element forward.
				$delete = array();
				if(is_array($dependencies) && !empty($dependencies)) {
				
				$delete = array_map('grab_delete', $dependencies);
				$delete=array_combine(array_keys($dependencies), $delete);
				
				}
			#echo '<pre>';print_r($dep_resource);exit;
		
			switch ($s3ql['flag']) {
				case 'unlink':{
					#unlink from where?
					$tounlink = array_filter(array_diff_key($s3ql['where'], array($GLOBALS['COREids'][$element]=>'')));
					
					if($s3ql['where']['user_id']!='')#remove another user
					if(max($permission2delete)!='0')
						$user_to_remove = $s3ql['where']['user_id'];
					else {
						return (formatReturn($GLOBALS['error_codes']['no_permission_message'], 'User does not have permission to remove user '.$s3ql['where']['user_id'].' from resource '.key($permission2delete), $format, ''));
						#return ($no_permission_message.'<message>User does not have permission to remove user '.$s3ql['where']['user_id'].' from resource '.key($permission2delete).'</message>');
					}
					else {$user_to_remove = $user_id;}
					
					
					#remove user from every dependency
					foreach ($delete as $uid_depend=>$allowed) {#being allowed here is only going to affect removing another user that is not "self" from a resource
					
					
					$dep_permission_info=array('uid'=>$uid_depend, 'shared_with'=>'U'.$user_to_remove, 'permission_level'=>'000');
					
					if($user_to_remove == $user_id && has_permission($dep_permission_info, $db)!=''){
						if(delete_permission(array('permission_info'=>$dep_permission_info, 'db'=>$db, 'info'=>$info)))
						#$output .= $success.'<message>User '.$user_to_remove.' removed from resource '.$uid_depend.'.</message><br>';
						$output .= formatReturn($GLOBALS['error_codes']['success'], 'User '.$user_to_remove.' removed from resource '.$uid_depend, $format,'');
						}
					elseif($user_to_remove != $user_id && $allowed && has_permission($dep_permission_info)!=''){
						
						if(delete_permission(array('permission_info'=>$dep_permission_info, 'db'=>$db, 'info'=>$info)))
						#$output .= $success.'<message>User '.$user_to_remove.' removed from resource '.$uid_depend.'.</message><br>';
						$output .= formatReturn($GLOBALS['error_codes']['success'], 'User '.$user_to_remove.' removed from resource '.$uid_depend, $format, '');
						}
					elseif($user_to_remove != $user_id && !$allowed){
						#$output .= $success.'<message>User does not have permission to remove'.$user_to_remove.' from resource '.$uid_depend.'.</message><br>';
						$output .= formatReturn($GLOBALS['error_codes']['no_permission_message'], 'User does not have permission to remove'.$user_to_remove.' from resource '.$uid_depend, $format, '');
						}

						
					}
					
					#now remove the resource from user			
					
					$shared_with = array_search(max($core_score), $core_score);
					$uid2remove = str_replace($GLOBALS['Did'].'/', '', $uid);
					
					if (ereg('^D', $shared_with)) {#when shared_with is deploymet, we reach the highest level: remove user from tables.
						
						
						deleteCoreResource($uid2remove, $user_id, $db);
						insertLogs($uid2remove, $info, $user_id, $db);
					}
					#$uid = array_search(min($core_score), $core_score);
					$permission_info=array('uid'=>$uid2remove, 'shared_with'=>'U'.$user_to_remove, 'permission_level'=>'000', 'info'=>$info);
					
					
					$has_permission = has_permission($permission_info, $db);
					if($has_permission!='' && $has_permission!='000')
						$done=delete_permission(compact('permission_info', 'db', 'user_id', 'info'));
					elseif($has_permission=='')
						$done=insert_permission(compact('permission_info', 'db', 'user_id', 'info'));
					
					
					if($done|| $has_permission=='000')
						$output .= formatReturn($GLOBALS['error_codes']['success'], 'User '.$user_to_remove.' removed from resource '.key($permission2delete).'.', $s3ql['format'], '');
					else {
						$output .= formatReturn($GLOBALS['error_codes']['something_went_wrong'], 'User '.$user_to_remove.' was NOT removed from resource '.key($permission2delete), $s3ql['format'], '');
					}
				$return_message =  ($output);}
			
			break;
			case 'resource':
				{
				$uid2remove = str_replace($GLOBALS['Did'].'/', '', $uid);
				if(max($permission2delete)=='0')
					return (formatReturn($GLOBALS['error_codes']['no_permission_message'], 'User does not have permission to remove user '.$s3ql['where']['user_id'].' from resource '.key($permission2delete).' If you want to remove this resource from view use flag "unlink"', $format, ''));
					#return ($no_permission_message.'<message>User does not have permission to remove user '.$s3ql['where']['user_id'].' from resource '.key($permission2delete).' If you want to remove this resource from view use flag "unlink"</message>');
				else{
				#when user is actually deleting a resource, he must have "change" permission on it. He does not need "change" permission on all dependencies. 
				
				if(deleteCoreResource($uid2remove, $user_id, $db))
					{
					$return_message = formatReturn($GLOBALS['error_codes']['success'], 'Resource '.$uid.' deleted. Resources that depend on '.$uid.' may still exist', $format, '');
					#if($s3ql['format']=='')
					#$return_message = ('<TABLE><TR><TD>error_code</TD><TD>message</TD></TR><TR><TD>'.ereg_replace('[^(0-9)]', '',$GLOBALS['messages']['success']).'</TD><TD>Resource '.$uid.' deleted. Resources that depend on '.$uid.' may still exist</TD></TR></TABLE>');
					#else 
					#$return_message =  ($success.'<message>Resource '.$uid.' deleted. Resources that depend on '.$uid.' may still exist</message>');#not a hard core delete.
					
					insertLogs($uid, $info, $user_id, $db);
					}
				}
					
				
				}
			break;
			case 'all':
				{
				
				if(max($permission2delete)=='0')
				return (formatReturn($GLOBALS['error_codes']['no_permission_message'], 'User does not have permission to delete '.$s3ql['where']['user_id'].' If you intend to remove this resource from your projects use flag "unlink"', $format, ''));
				#return ($no_permission_message.'<message>User does not have permission to delete '.$s3ql['where']['user_id'].' If you intend to remove this resource from your projects use flag "unlink"</message>');
				else {
				
				#echo '<pre>';print_r($delete);exit;
				#start deleting dependencies
				foreach ($delete as $uid_depend=>$allowed) {
					$permission_info = array('uid'=>$uid_depend, 'shared_with'=>'U'.$user_id ,'permission_level'=>'000');
					
					if($allowed)
						if(deleteCoreResource($uid_depend, $user_id, $db))
						{
						$output .= formatReturn($GLOBALS['error_codes']['success'], ''.$uid_depend.' deleted', $s3ql['format'],'');
						#if($s3ql['format']=='')
						#$output .= '<TABLE><TR><TD>error_code</TD><TD>message</TD></TR><TR><TD>'.ereg_replace('[^(0-9)]', '',$GLOBALS['messages']['success']).'</TD><TD>'.$uid_depend.' deleted</TD></TR></TABLE>';
						#else 
						#$output .= $success.'<message>'.$uid_depend.' deleted</message><br>';
						}
					elseif(has_permission($permission_info, $db)!='') {
								if(delete_permission(compact('permission_info', 'db', 'user_id', 'info')))
								$output .= $success.'<message>Permission on '.$uid_depend.' removed for '.$user_id.'</message><br>';

						}
					#echo '<pre>';print_r($info);exit;
					insertLogs($uid_depend, $info, $user_id, $db);
					}

				#Now delete everything that shared this collection in permission tables
				$uid_info = uid($uid);
				$sql = "delete from s3db_permission where uid = '".$uid."' or shared_with = '".$uid."'";
				
				#echo $sql;exit;
				$db->query($sql, __LINE__, __FILE__);
				
				
				#and now delete the resource itseld
				
				$uid = strtoupper(substr($element, 0,1)).$element_id;
				if(deleteCoreResource($uid, $user_id, $db))
					{
					$output .= formatReturn($GLOBALS['error_codes']['success'], $uid.' deleted', $s3ql['format'], '');
					insertLogs($uid, $info, $user_id, $db);
					}
				else {
					$output .= $something_went_wrong.'<message>Could not delete '.$uid.'</message>';
				}
				$return_message = ($output);
				}
				
				
			
			
			}
			break;
			
			}
			}
			elseif(count($core_score)>1) {
				if(ereg('^G', array_search(max($core_score), $core_score)) && array_search(min($core_score), $core_score)=='U'.$user_id)
				$permission2delete[array_search(max($core_score), $core_score)]='1';#if the user is removing himself from group
				
				
				
				if(max($permission2delete)=='0')
				{
				return (formatReturn($GLOBALS['error_codes']['no_permission_message'], 'User does not have permission to remove user '.$s3ql['where']['user_id'].' from resource '.key($permission2delete).' If you want to remove this resource from view use flag "unlink"', $format, ''));
				
				}
				#can only pass if the user has access to remove data from the highest scored
				$result = array_combine($core_score, $permission2delete);#score as index and permissions as values
				
				#a group and a user can be inserted in any one resource... as long as user does have permission on the resource
				if(ereg('user|group', $element) && $result[min(array_keys($result))])
				$result[max(array_keys($result))]='1';
				
				
				$double_permission = array('statement'=>array('2', '4'));#2 and 4 are the scores the statement needs in the score: rules and instances

				if((in_array($element, array_keys($double_permission))) && min(array($result[$double_permission[$element][0]], $result[$double_permission[$element][1]]))=='0')
				$result = array_combine(array($double_permission[$element][0], $double_permission[$element][1] ), array('0', '0'));

				
				#result only checks upstream permissions, but is idB allowed to insert itself on idA?
				if($result[max(array_keys($result))]=='0'){#this means the highest scored element does NOT have permission to delete
					
					$ids = array_keys($permission2delete);
									
					#some ids can be swapped, that is class is swapped with rule "hasUID" and instance is swapped with statement of rule "hasUID"
					$swap = array('C'=>'rule_id', 'I'=>'statement_id');
					foreach ($ids as $to_swap) {
						
						if(in_array(substr($to_swap, 0,1), array_keys($swap)))
						{$letter = substr($to_swap, 0,1);
						$new_id = strtoupper(substr($swap[$letter], 0,1)).$info[$to_swap][$swap[$letter]];
						}
						else {
							$new_id = $to_swap;
						}
						$ids1[] = $new_id;
					}
					$ids = $ids1;
					
					$recalc_permission2delete = $permission2delete;
					$has_permission = has_permission(array('uid'=>$ids[0], 'shared_with'=>$ids[1]), $db);
					if(ereg('2$', $has_permission) || (ereg('1$', $has_permission) && $element_info['created_by']==$user_id));#does the idB have insert permission on idA? Change the score :-)
					$recalc_permission2delete[$ids[0]] = substr($has_permission, 2,1);

					#check again the result
					$recalc_result = array_combine($core_score, $recalc_permission2delete);#score as index and permissions as values

					#echo '<pre>';print_r($recalc_result);exit;
					if($recalc_result[max(array_keys($recalc_result))]=='0')
					return (formatReturn($GLOBALS['error_codes']['no_permission_message'], 'User does not have permission to delete in resource '.array_search('0', $permission2delete), $format, ''));
					#return ($GLOBALS['messages']['no_permission_message'].'<message>User does not have permission to delete in resource '.array_search('0', $permission2delete).'</message>');

					

					
			}
			}
		#still there? Ok, we are ready to remove resource from another resource
					$shared_with = array_search(max($core_score), $core_score);
					$uid = array_search(min($core_score), $core_score);
					$return_message =  removePermission(compact('uid', 'shared_with', 'db', 'info', 'user_id'));
					insertLogs($uid, $info, $user_id, $db);
			
			return ($return_message);
			}
			
			
			#begin cases not considered in "deleteable" and those that need extra operations like rule_log and statement_log insertions
			switch ($element) {
			 case 'key':	{#DELETE KEY
				#does this key belong to this user?
				
				if($user_id!='1' && $e_info['account_id']!=$user_id)
				{	return (formatReturn($GLOBALS['error_codes']['no_permission_message'], 'This key cannot be deleted', $format,''));
					#return $no_permission_message."<message>This key cannot be deleted</message>";
				
				}
				
				
				$D['table']='access_keys';
				$D['element']='key';
				$D['element_id']=$element_id;

				$deleted = delete_element($D);
				if($deleted)
					{
					return (formatReturn($GLOBALS['error_codes']['success'], $element." ".$element_id." deleted",$format, ''));
					#$output .= $success;
					#$output .= $element." deleted<BR>";
					#$query = S3QLRestWrapper(array('key'=>$key));
								
					#$output .= '<a href =" '.$query['url'].$query['s3ql'].'<select>*</select><from>'.$element.'s</from></S3QL>">List '.$element.'s</a>';
					#return ($output);
					}
				else
				return formatReturn($GLOBALS['error_codes']['something_went_wrong'], "Failed to delete key", $format, '');
				
			 break;	
			}
			
			case 'permission':{#DELETE PERMISSION
			#permission to delete this permission was checked before switch
			#it will delete permission from downstream resources via upstream but NOT the other way around. 
			
			$permission_info['shared_with']=($s3ql['where']['user_id']!='')?'U'.$s3ql['where']['user_id']:array_search(max($core_score), $core_score);
			$permission_info['uid'] = array_search(min($core_score), $core_score);
			#delete only if user has permission to change.
			$me = array('uid'=>$permission_info['uid'], 'shared_with'=>'U'.$user_id,'db'=>$db,'user_id'=>$user_id);
			$meOnUid = permission4resource($me);
			$tmp = permission_level($meOnUid, $permission_info['uid'], $user_id, $db);
			if(!$tmp['edit'])
				{
				return (formatReturn($GLOBALS['error_codes']['no_permission_message'], "User does not have permission to change ".$permission_info['uid'], $s3ql['format'], ''));
				}
			
			
			if(delete_permission(compact('permission_info', 'db', 'info', 'user_id')))
				{insertLogs($permission_info['uid'], $info, $user_id, $db);
				return (formatReturn($GLOBALS['error_codes']['success'], 'Permission on '.$permission_info['uid'].' removed', $format, ''));
				}
			}

			case 'rule':{
			
			insert_rule_log(array('action'=>'delete', 'rule_info'=>$info['R'.$element_id],'oldvalues'=>$info['R'.$element_id], 'inputs'=>array(), 'db'=>$db, 'user_id'=>$user_id));
			}
			break;

			case 'statement':{
			
			}
			break;
			case 'class':{
			
			}
			break;
			}#finish switch eleent	
		} #finish delete
	case 'grant':
		{
		$permission_info['permission_level'] = $s3ql['grant'];
		
		$shareables = array('project'=>'project_id',
							'rule'=>'rule_id', 
							'class'=>'class_id',
							'instance'=>'instance_id',
							'statement'=>'statement_id');
		$shared_with = array('project'=>'project_id',
							'user'=>'user_id',
							'group'=>'group_id',
							);
						
						foreach ($shareables as $name=>$id) {
							if ($s3ql['where'][$id]!='') {
								$element_name = $name;
								$id_name = $id;
								$permission_info['uid'] = strtoupper(substr($name, 0, 1)).$s3ql['where'][$id];
								$permission_info['id'] = $s3ql['where'][$id];
								
								
								
							}
						}
						foreach ($shared_with as $name1=>$id1) {
							if ($s3ql['where'][$id1]!='') {
								$element_name1 = $name1;
								$id_name1 = $id1;
								$permission_info['shared_with'] = strtoupper(substr($name1, 0, 1)).$s3ql['where'][$id1];
								
								}
						}
					
		$permission_info['uid'] = ($s3ql['on']!='')?$s3ql['on']:(($permission_info['uid']!='')?$permission_info['uid']:'');
		
		$permission_info['shared_with'] = ($s3ql['to']!='')?$s3ql['to']:(($permission_info['shared_with']!='')?$permission_info['shared_with']:'');
		$permission_info['id'] = substr($permission_info['uid'], 1, strlen($permission_info['uid']));
		
		$info[$permission_info['uid']] = URI($permission_info['uid'], $user_id, $db);
		$info[$permission_info['shared_with']] = URI($permission_info['shared_with'], $user_id, $db);
		#validate the inputs
		
		$validity = validate_permission(compact('permission_info', 'user_id', 'db', 'info'));
		
		switch ($validity) {
			case 0:
				#lets insert it
				if(insert_permission(compact('permission_info', 'db', 'user_id', 'info','info')))
					#if this the operation of sharing a rule by the owner of the rule. To remove later
					if(ereg('^R', $permission_info['uid']) && ereg('^P', $permission_info['shared_with']) && !ereg('^0', $permission_info['permission_level']))
						{
						$res3ql=compact('user_id','db');
						$res3ql['insert']='rule';
						$res3ql['where']['project_id']=substr($permission_info['shared_with'], 1, strlen($permission_info['shared_with']));
						$res3ql['where']['rule_id'] = substr($permission_info['uid'], 1, strlen($permission_info['uid']));
						#$done = S3QLaction($s3ql);
						}

					return (formatReturn($GLOBALS['error_codes']['success'],$permission_info['uid'].' was shared with '.$permission_info['shared_with'].' with permission level '.$permission_info['permission_level'], $format, ''));
					#return ($success.'<message>'.$permission_info['uid'].' was shared with '.$permission_info['shared_with'].' with permission level '.$permission_info['permission_level'].'</message>');
				break;
			case 1:
				return (formatReturn($GLOBALS['error_codes']['wrong_input'], 'Please provide a 2 or 3 digit (range 0-2) permission_level value for this user:view/update/insert permission.'.$GLOBALS['messages']['syntax_message'], $format,''));
			#return ($wrong_input.'<message>Please provide a 2 or 3 digit (range 0-2) permission_level value for this user:view/update/insert permission.'.$GLOBALS['messages']['syntax_message'].'</message>');
				break;
			case 2:
				#This means an update and not an insert is in order
				
				if(update_permission(compact('permission_info', 'db', 'user_id', 'info')))
					{#if this the operation of sharing a rule -by the owner of the rule. To remove later
					if(ereg('^R', $permission_info['uid']) && ereg('^P', $permission_info['shared_with']) && !ereg('^0', $permission_info['permission_level']))
						{
						
						#insert_rule_remotelly(array('project_id'=>$permission_info, 'rule_id'=>, 'db'=>$db));
						$res3ql=compact('user_id','db');
						$res3ql['insert']='rule';
						$res3ql['where']['project_id']=substr($permission_info['shared_with'], 1, strlen($permission_info['shared_with']));
						$res3ql['where']['rule_id'] = substr($permission_info['uid'], 1, strlen($permission_info['uid']));
						
						#$done = S3QLaction($res3ql);
						
						}
					}	
				return (formatReturn($GLOBALS['error_codes']['success'], $permission_info['uid'].' was shared with '.$permission_info['shared_with'].' with permission level '.$permission_info['permission_level'], $format, ''));
				#return ($success.'<message>'.$permission_info['uid'].' was shared with '.$permission_info['shared_with'].' with permission level '.$permission_info['permission_level'].'</message>');
				break;
			case 3:
				return (formatReturn($GLOBALS['error_codes']['wrong_input'], 'Numeric part of uid must match id', $format,''));
				#return ($wrong_input.'<message>numeric part of uid must match id</message>');
				break;
			case 4:
			{
				return (formatReturn($GLOBALS['error_codes']['something_does_not_exist'], $permission_info['shared_with'].' was not found', $format, ''));
				break;
			}
			case 5:
				return (formatReturn($GLOBALS['error_codes']['something_does_not_exist'], $permission_info['uid'].' was not found', $format,''));
				break;
			case 6:
			return (formatReturn($GLOBALS['error_codes']['no_permission_message'], 'Please chose a level of permission that is equal or smaller than '.$element_info['permission_level'].'.', $format,''));
				break;
			case 7:
			return (formatReturn($GLOBALS['error_codes']['wrong_input'], 'Permission cannot be specified on collection (C) or item (I)', $format,''));
				break;
			case 8:
			return (formatReturn($GLOBALS['error_codes']['wrong_input'], 'uid to share or user to share with is empty', $format, ''));
				break;

		}
		}
}#close switch action
}
#Finish the function

function S3QLselectTransform($Z)
{
			extract($Z);
			#$Z must contain at lease s3ql, user_id, $db
			 #What table is being asked for?
			$dbstruct = $GLOBALS['dbstruct'];
			$s3map=$GLOBALS['s3map'];
			
			#echo '<pre>';print_r($s3ql);exit;
			#Read the cols to display into an array, if columns are specified, or leave it to the appropriate section to determine which cols to display
			$target = $s3ql['from'];
			$cols = $dbstruct[$table];
			
			
			#build the array that will always be sent to display
			$P = compact('db', 'user_id', 'format', 'table', 'cols');
			
			#map a few queries
			#on select
			
			if(!in_array($target, array_keys($s3map)))
				$target = $GLOBALS['plurals'][$target];

			if($s3ql['select']!='*'){
			$s3ql_out=ereg_replace(' ', '', $s3ql['select']);#take out all the spaces
			
			$returnFields = explode(',', $s3ql_out);
			
			#return fields are used in THE END of select queries, just before the return but need to defined before s3ql is sent to query
			
			
			#array_keys contains the things to replace and array_values the replacements
			$toreplace = array_keys($s3map[$target]);
			$replacements = array_values($s3map[$target]);
			$s3ql['select'] = str_replace($toreplace, $replacements, $s3ql['select']);
			$s3ql['select'] = ($s3ql['select']=='')?'*':$s3ql['select'];
			
			
				#$s3ql['where'] = str_replace($toreplace, $replacements, $s3ql['where']);
			}
			
			foreach ($s3map[$target] as $replaceMe=>$withMe) {
				
				if($s3ql['where'][$replaceMe]!='' && !in_array($replaceMe,  array_keys($GLOBALS['COREletter'])) && !in_array($replaceMe,  array_keys($GLOBALS['s3mask'])))
				{
				$s3ql['where'][$withMe] = $s3ql['where'][$replaceMe];
				$s3ql['where'][$replaceMe] = '';
				}
				
				if($s3ql['where'][$replaceMe]!='' && in_array($replaceMe,  array_keys($GLOBALS['s3mask'])))
				{
				$s3ql['where'][$GLOBALS['s3mask'][$replaceMe]] = $s3ql['where'][$replaceMe];
				$s3ql['where'][$replaceMe]='';
				
				}
				
			}
			#echo '<pre>';print_r($s3ql);exit;
			if(is_array($s3ql['where']))
			$s3ql['where'] = array_filter($s3ql['where']);
			#echo '<pre>';print_r($s3ql);exit;
			return compact('s3ql', 'returnFields');
}

function retrieveUIDInfo($s3element, $id, $scoreTable, $s3ql, $letter, $input_ids, $user_id, $db)
{
	#echo '<pre>';print_r($db);
			if ($s3ql['where'][$id]!='') {
				$element_name = $s3element;
				$id_name = $id;
				$letterSub = strtoupper(substr($id_name,0,1));
				$core=$GLOBALS['s3codes'][$letterSub];
				#is this remote?
				$uid_info = uid($letterSub.$s3ql['where'][$id_name]);
				$islocal=isLocal($letterSub.$s3ql['where'][$id_name], $db);
				#echo '<pre>';print_r($uid_info);
				
				#try regular URI (internal)
				if($islocal)# || $uid_info['Did']==$GLOBALS['Did'])
				{
				
				$id = strtoupper(substr($element_name, 0,1)).$s3ql['where'][$id_name];
				$localuid = $letterSub.$s3ql['where'][$id_name];
				
				$element_info = URI($localuid, $user_id, $db);


				if($element_info['acl']=='')
					$element_info['acl'] = permissionOnResource(array('shared_with'=>'U'.$user_id, 'uid'=>$id, 'db'=>$db,'user_id'=>$user_id,'strictuid'=>1,'strictsharedwith'=>1));

				if($id_name==$element."_id") {					
				
				if(!is_array($element_info))
					{
					if($uid_info['uid']!='')
					#search for forbidden characters in id.
					{
					$forbidden = array('#', ':', '/');
					$test_str=str_split($uid_info['uid']);
					foreach ($forbidden as $f) {
						if(array_search($f, $test_str)){
							if($s3ql['format']=='')
							return ('<TABLE><TR><TD>error_code</TD><TD>message</TD></TR><TR><TD>'.ereg_replace('[^(0-9)]', '',$GLOBALS['messages']['wrong_inputs']).'</TD><TD>Please do not use any of the following character when specifying IDs: '.array_reduce($forbidden, "comma_split").'</TD></TR></TABLE>');
							else
							return ($GLOBALS['messages']['wrong_inputs'].'<message>Please do not use any of the following character when specifying IDs: '.array_reduce($forbidden, "comma_split").'</message>');
						}
					}
					
					}
					$element_info=array('to_create'=>'1');	
					}
				else {
					#echo '<pre>';print_r($element_info);
					$element_info['to_create']='0';
					#only valid when resource being inserted in another resource.
				}
				
				}
				}
				else
				#try external URI (external) but only for uid that are not being inserted and there is not indication of literal data
				{
				#interpreting user input: if this is uid beloing to the object being inserted and there are literal values, assume the provided ID is a literal and is meant to create "de novo"
				
				$diff = array_diff(array_keys($s3ql['where']), array($GLOBALS['COREids'][$s3element]));
				
				if ($id_name==$GLOBALS['COREids'][$s3ql['insert']] && !empty($diff) && !ereg('^(U|G|P)$', $letter)) {
					$element_info['to_create']='1';
				}
				else {
					
				#when inserting a remote user, project or group - when + ids. if this is user, most liel remoteURI will return no permission on the remote deployment
				
				if(ereg('^(U|G|P)$', $letter)){
					
					
					if(count($input_ids)>1)#user,group and project must always exist in deployment,even if remote
					{
						$element_info = URI($s3ql['where'][$id_name], $user_id, $db);
						
					}
					elseif(ereg('^P$', $letter))
					{
						
						
						$element_info = URI($s3ql['where'][$id_name], $s3ql['key'], $user_id, $db);
						
						if(is_array($element_info))
						{$element_info['is_remote']='1';	
						}
						else {
							#Id does not exist. Will assume the goal is to create a new.
							$element_info=array('to_create'=>'1');
							$diff=array_diff(array_keys($s3ql['where']), $GLOBALS['COREids'], array('permission_level'));
							
							
						}
					}
					else {
						$element_info=array('is_remote'=>'1',
											'account_id'=>$s3ql['where'][$id_name]);
					}
				}
				else {
					#check if is remote only if no other parameters are provided.
					$diff=array_diff(array_keys($s3ql['where']), $GLOBALS['COREids'], array('permission_level'));
					
					if (!empty($diff) && $id_name == $GLOBALS['COREids'][$s3ql['insert']]) {
							$element_info = array('to_create'=>1);	
						}
					else {
					
					$element_info = remoteURI($s3ql['where'][$id_name], $s3ql['key'], $user_id, $db);
					
					if(is_array($element_info))
					{$element_info['is_remote']='1';	
					}
					
				}
				}
				}
				}
				return ($element_info);	
				
				
			}
		
}

function includeAllData($pack)
{extract($pack);
	$letter = letter($s3ql['from']);
	$element = $GLOBALS['s3codes'][$letter];
		#echo 'ola';exit;
	if(count($data)>=50){
		
		if(!ereg('^U|^G', $letter))
		$ids = permissionPropagation($letter, 'U'.$user_id,$user_id, $db,$uidQuery);
	}
	
	
		
		#echo 'ola';exit;
		#echo '<pre>';print_r($ids);exit;
		##Remove from data the uids that do not exist in ids
		$str = $GLOBALS['s3ids'][$GLOBALS['s3codes'][$letter]];
		$re_issued = array();
		
		if(is_array($data))
		foreach ($data as $ind=>$array) {
			$uid = $letter.$array[$str];
			
			
			if($uid!=''){
				
				if($user_id!='1'){
				
				if(is_array($ids)){
				if(!ereg('^U|^G', $letter))
					{
					
					if($ids[$uid]!='' && (substr($ids[$uid],0,1)=='2' || (substr($ids[$uid],0,1)=='1' && createdBy($uid,$db)==$user_id)))
					{$array['acl'] = $ids[$uid];	}
				 else 
					{ 
					 $array=array();
					 }
					}
				}
				else {
					
					$strictuid = 1;$strictsharedwith=1;$shared_with = 'U'.$user_id;
					$P=compact('uid','shared_with','user_id','db','strictuid','strictsharedwith','stream');
					if(!ereg('^U|^G', $letter))
					$array['acl'] = permissionOnResource($P);
					else {
						$array['acl'] = userAcl(array('key'=>$key, 'element_info'=>$array, 'user_id_who_asks'=>$user_id, 'db'=>$db));
					}
					#$array['acl'] = permission4Resource($P);
					
				}
				}
				else {
					$array['acl'] = '222';
				}
				
			}
			
			
			#echo '<pre>';print_r($array);
			if(!empty($array)){
			$re_issued[$uid] = $array;
			$element_info = $re_issued[$uid];
			#echo '<pre>';print_r($element_info);exit;
			$element_info = include_all(compact('letter', 'info','elements', 'element_info', 'user_id', 'db','key'));
			#$element_info['permissionOnResource']=permissionOnResource($D);
			
			#echo '<pre>';print_r($element_info);
			
			
			$element_info['uri'] = S3DB_URI_BASE.'/'.$letter.$array[$str];
			$pack['uid'] = $uid;
			$pack['info'] = $element_info;
			
			$element_info = filterDataForQuery($pack);
			#echo '<pre>';print_r($element_info);exit;
			$re_issued[$uid] = $element_info;
			}
		
		
	}
	#echo '<pre>';print_r($re_issued);exit;
	$data = array_filter($re_issued);
	#echo '<pre>';print_r($data);exit;
	return ($data);
	
}

function filterDataForQuery($pack)
{extract($pack);
	$letter = letter($uid);
	$info_ID = $GLOBALS['COREletterInv'][$letter];
	
	$uid_info = uid($uid);
	$uid_info['uid'] = ($info['uid']!='')?$info['uid']:$uid_info['uid'];
	
	$info['uid'] = ereg_replace('^D','D|',$uid_info['Did']).(($letter!='U')?'|U|'.$user_id:'').'|'.$letter.'|'.$info[$info_ID].'|';
	
	
	if(substr($info['acl'],0,1)=='0' || (substr($info[$acl],0,1)=='1' && createdBy($uid,$db)!=$user_id))
	{
	
	$info = array();
	}

	if($s3ql['where']!='')
		{
			foreach ($s3ql['where'] as $query_field=>$query_value) {
				
				#map the query_field first. because s3ql was transformed. To avoid being deleted
				if(in_array($query_field,  $GLOBALS['s3map'][$GLOBALS['s3codes'][$letter].'s']) && $info[$query_field]!=$query_value)
				{	
					$query_field = @array_search($query_field, $GLOBALS['s3map'][$elements]);
				}

				$whereLetter = letter($query_field);
				if(in_array($query_field, $GLOBALS['COREids']) && ereg('^(U|G)$',$letter) && $letter.$query_value!=$whereLetter.$query_value) {
				
				
				$D=array('shared_with'=>$uid, 'uid'=>$whereLetter.$query_value, 'strictsharedwith'=>1, 'stricuid'=>1,'db'=>$db, 'user_id'=>$user_id, 'stream'=>'upstream');##Look for shared_with in uid instead of uid in shared_with
								
				$info['permissionOnResource']=permissionOnResource($D);
				#echo '<pre>';print_r($info);exit;
					
				}
				#echo '<pre>';print_r($info);exit;
				$tmp=$GLOBALS['s3codes'][$letter];
				
				if($info[$query_field]!=$query_value && in_array($query_field, $GLOBALS['queriable'][$tmp])) {
					
					if(!in_array($query_field, $cols) && !in_array($query_field, $GLOBALS['COREids']))#remove those that were already selected before removing 
					{	#is there regular expresion in the query?
					
					if(ereg("!=\'(.*)\'", stripslashes($query_value), $regout))									
							{
							
							if($info[$query_field] == $regout[1])
							$info=array();
							}
						#is it a not? In that case, remove only those that do not match query field
					else{
						$info=array();
						}
					}
			}
			
			}
	}
	
	return ($info);
}



function ruleInputsInfer($s3ql, $db,$user_id) 
	{								$s3ql['where']['subject']=($s3ql['where']['subject']!='')?$s3ql['where']['subject']:(($s3ql['where']['subject_id']!='')?subject4subjectID(array('key'=>$s3ql['key'], 'subject_id'=>$s3ql['where']['subject_id'], 'db'=>$db)):'');

			
			if($s3ql['where']['verb_id']==''){
				#turn a literal verb into an instance of a class
				#class exists in project? no? create it;else find it's
				$VerbClass = projectVerbClass(array('project_id'=>$s3ql['where']['project_id'], 'db'=>$db,'user_id'=>$user_id));
				
				if(!$VerbClass)
				{
				
				$to_create = array('project_id'=>$s3ql['where']['project_id'], 'entity'=>'s3dbVerb', 'notes'=>'Collection created by S3DB for holding Verbs');

				$inputs = gatherInputs(array('element'=>'collection', 'to_create'=>$to_create, 'db'=>$db, 'user_id'=>$user_id));
				
				$inserted = insert_s3db(array('element'=>'collection', 'inputs'=>$inputs, 'user_id'=>$user_id, 'db'=>$db));
				
				#try again;
				$VerbClass = projectVerbClass(array('project_id'=>$s3ql['where']['project_id'], 'db'=>$db,'user_id'=>$user_id));
				}
				
				#now create the instanceVerb
				$inputs = array('resource_class_id'=>$VerbClass['resource_id'], 'project_id'=>$s3ql['where']['project_id'], 'notes'=>$s3ql['where']['verb'], 'created_by'=>$user_id, 'entity'=>$VerbClass['entity'], 'status'=>'A');
				#echo '<pre>';print_r($inputs);exit;
				
				$inserted = insert_s3db(array('element'=>'item', 'inputs'=>$inputs, 'user_id'=>$user_id, 'db'=>$db));


				
				#echo '<pre>';print_r($inserted);exit;
				$s3ql['where']['verb_id']=$inserted['item_id'];

			#echo '<pre>';print_r($s3ql);exit;
			}
			$s3ql['where']['verb']=verb4instanceID(array('key'=>$s3ql['key'],'instance_id'=>$s3ql['where']['verb_id'], 'db'=>$db));
			
			$s3ql['where']['object']=($s3ql['where']['object']!='')?$s3ql['where']['object']:(($s3ql['where']['object_id']!='')? object4objectID(array('key'=>$s3ql['key'],'object_id'=>$s3ql['where']['object_id'], 'db'=>$db)):$s3ql['where']['object_id']);
		#echo '<pre>';print_r($s3ql);exit;
		return ($s3ql);

	}


function filterByElement($s3ql, $user_id,$db)
{

			switch ($s3ql['from']) {
				case 'users':{
				
					#$user_query_const .= " and account_type !=".$regexp." '(u|p|a|r)'";
					$user_query_const .= " and account_type != 'g'";
					
					if(!user_is_admin($user_id, $db) || $s3ql['where']['account_status']=='')
					$user_query_const .= " and account_status = 'A'";
					
					

					if ($s3ql['where']['group_id']!='') {
						$group_info = s3info('group', $s3ql['where']['group_id'], $db);
						
					if (!is_array($group_info)) {
							return (False);
							echo formatReturn($GLOBALS['error_codes']['something_does_not_exist'], 'Group '.$s3ql['where']['group_id'].' does not exist', $s3ql['format'],'');
							
							}
					else {
							
							#$group_members_query=str_replace("*", "substr(uid, 2, length(uid))", select(array('uid'=>'U'.$s3ql['where']['user_id'], 'shared_with'=>'G'.$s3ql['where']['group_id'])));
							$group_members_query=str_replace("*", "replace(substr(uid, 2, length(uid)), '".$GLOBALS['Did'].'/U'."', '')", select(array('uid'=>'U'.$s3ql['where']['user_id'], 'shared_with'=>'G'.$s3ql['where']['group_id'])));
							$user_query_const .= " and account_id in (".$group_members_query.")";
							
							#group_id is artifical, don't use it in determining output
							$s3ql['where'] =array_diff_key($s3ql['where'], array('group_id'=>''));
							$s3ql['where'] =array_filter($s3ql['where']);

							}
				
						}
					
					#if there is any coreID, this is a check on permissions. Check first if user can query the resource, then check for permission
					$COREids = $GLOBALS['COREids'];
					foreach($COREids as $s3code=>$s3coreId)
					{
					
					if($s3ql['where'][$s3coreId]!='' && $s3coreId!='user_id')
						{
							
							$stream="upstream";
							$id_name = $s3coreId;
							$code_id = $s3ql['where'][$s3coreId];
							$uid = strtoupper(substr($s3code, 0,1)).$s3ql['where'][$s3coreId];
							$element_info = URIinfo($uid, $user_id, $s3ql['key'], $db);
							
							if(!is_array($element_info)){
								
								echo formatReturn($GLOBALS['error_codes']['something_missing'], $uid.' was not found', $s3ql['format'],''); 
								return (false);
							}
							if(!$element_info['view'])
							{
								echo formatReturn($GLOBALS['error_codes']['no_permission_message'], $uid.' was not found', $s3ql['format'],''); 
								return (false);
													
							}
							
							$user_query_const .= " and account_id in (".str_replace('*', 'substr(shared_with,2,length(shared_with))', select(array('shared_with'=>'U', 'uid'=>$uid, 'stream'=>'upstream'))).")";
						}
					}
				
					
				
			}	
			break;
			case 'groups':{
					#secial query will be pefrformed on listS3DB.
					$user_query_const .= " and account_type ".$GLOBALS['regexp']." '(g)'";
					
					if($group_id!='1' || $s3ql['where']['account_status']!='I')
					$user_query_const .= " and account_status = 'A'";
					
					if ($s3ql['where']['user_id']!='') {
						{$user_members =  select(array('uid'=>'U'.$s3ql['where']['user_id'], 'shared_with'=>'G'.$s3ql['where']['group_id'], 'stream'=>'upstream'));
							$user_members_query=str_replace("*", "substr(shared_with, 2, length(shared_with))", $user_members);
							$user_query_const .= " and account_id in (".$user_members_query.")";
							#$user_query_const .= " and account_id in (select group_id from s3db_account_group where account_id ".$regexp." ".$s3ql['where']['user_id'].")";
						
						$s3ql['where'] = array_diff_key($s3ql['where'], array('user_id'=>''));
						$s3ql['where'] =array_filter($s3ql['where']);
						}
					}
					
					#implicated user id. When queried with user_id, this query gives all the groups where user_id is involved, which are all the groups he can change.
			}
				
			break;				
			case 'accesslog':	{
						
						#if(!user_is_admin($user_id, $db)) 
						if($user_id!='1' && !user_is_admin($user_id, $db))
						{	
							
							echo formatReturn($GLOBALS['error_codes']['no_permission_message'], 'User does not have permission to see accesslog', $s3ql['format'],''); 	
							return (False);					
						}
			}
			break;

			case 'rulelog':{
					
					
					if($user_id!='1')
					{	$user_projects = findUserProjects($user_id, $db);
						$user_query_const .= " and project_id ".$regexp." ".create_list($user_projects)."";
					
					}
					

			}
			break;
			case 'keys':{
					
					$P['table'] = 'access_keys';
					if($user_id!='1')
					$user_query_const .= " and account_id = '".$user_id."'";
					
			}
			break;
			case 'filekeys':{
					$P['table'] = 'file_transfer';
					if($user_id!='1')
					$user_query_const .= " and created_by = '".$user_id."'";
					
			}
			break;
			case 'projects':{
					
					if($user_id!='1' && $s3ql['where']['project_status']!='I')
					$user_query_const .= " and project_status = 'A'";
			}
			break;
			
			case 'requests':{
					
					if($user_id!='1'){
						
						$user_rules = findUserRules($user_id, $db);
						$user_query_const .= " and rule_id ".$regexp." '".create_list($user_rules)."'";
						
						}
					
			}
			break;

			case 'rules':
			{
			
			if($s3ql['where']['class_id']!='')
				{$class_info = URI('C'.$s3ql['where']['class_id'], $user_id, $db);
				$user_query_const .= " and (subject_id = '".$class_info['resource_id']."' or object_id = '".$class_info['resource_id']."')";}
			
			}

			break;
			case 'statements':
			{
			#
			if($s3ql['where']['class_id']!='')
				{$class_info = URI('C'.$s3ql['where']['class_id'], $user_id, $db);
				$user_query_const .= " and rule_id = '".$class_info['rule_id']."'";
				}
			
			}

			break;	
			case 'collections':{
				
				$user_query_const .= " and iid = '0'";
				
				if ($s3ql['where']['rule_id']!='') {
					$element_info = URI('R'.$s3ql['where']['rule_id'], $user_id, $db);
					$user_query_const .= " and resource_id '^".$regexp." ".fastClassID(array('entity'=>$element_info['subject'], 'project_id'=>$element_info['project_id'], 'db'=>$db))."'$'";
					}
				}
				
				break;
			case 'items':{
				$user_query_const .= " and iid = '1'";
				
				if ($s3ql['where']['rule_id']!='') {
					$element_info = URI('R'.$s3ql['where']['rule_id'], $user_id, $db);
					$user_query_const .= " and resource_class_id '^".$regexp." ".fastClassID(array('entity'=>$element_info['subject'], 'project_id'=>$element_info['project_id'], 'db'=>$db))."'$'";
					}
				}
				break;
			}
			return ($user_query_const);
}

function simpleQueryUID($shared_with_query, $element, $db)
{
##Create 150408 for performing faster queries
$shared_with_query = array_unique($shared_with_query);
$str_ids='';
	for ($i=0; $i < count($shared_with_query); $i++) {
		$uidletter = strtoupper(substr($element,0,1));$swletter = letter($shared_with_query[$i]);
		
		if($id=='') 
			{
			$uid = $GLOBALS['regexp']." '^".$uidletter."'";
			}
		else {
			$uid = $GLOBALS['regexp']." '^".$uidletter."(".$id.")$'";
			}
				
		
		$score = array('D'=>0, 'G'=>1, 'U'=>2,'P'=>3, 'C'=>4,'R'=>4,'I'=>5, 'S'=>6);
		$jump=$score[$uidletter]-$score[$swletter];
		#Added 120508 for enabling query on items of project
		if($jump>1)
		{
			$middleQuery='';
			switch ($uidletter) {
				case 'I':
					$middleQuery = simpleQueryUID($shared_with_query, 'collection', $db);
					$middleQuery = str_replace("id = '", "shared_with = 'C", $middleQuery['str_ids']);
					
				break;
				case 'S':
					$middleQuery = simpleQueryUID($shared_with_query, 'rule', $db);
					$middleQuery = str_replace("id = '", "shared_with = 'R", $middleQuery['str_ids']);
				break;
				
				
			}
		}
		
		if($score[$swletter] < $score[$uidletter])
		{
		
		
		$id_query = "select * from s3db_permission where uid ".$uid." and shared_with = '".$shared_with_query[$i]."'";
		
		if($middleQuery!='')
		$id_query .= "or (".$middleQuery.")";
		
		$db->query($id_query, __FILE__, __LINE__);
		#echo $id_query;
		#echo '<pre>';print_r($db);
		while ($db->next_record()) {
			if($i!=(count($shared_with_query)-1)){
			if($id!='') $id .= '|';
			$id .= $db->f('id');
			}
			else {
				
				if($finalUID!='') $finalUID .= ' or ';
				$finalUID .= $GLOBALS['s3ids'][$element]." = '".$db->f('id')."'";
				
				$array_ids[$db->f('id')]=$db->f('permission_level');
				#$str_ids = ($str_ids=='')?($db->f('id')):('|'.$db->f('id'));
				$str_ids .= ($str_ids=='')?(" id = '".$db->f('id')."'"):(" or id = '".$db->f('id')."'");
			}

		}
		}
		else
		{
		$tmp = $shared_with_query[$i];
		$shared_with= $uid;
		$uid = $tmp;
		$id_query = "select substr(shared_with,2,length(shared_with)) as shared_with_num,permission_level from s3db_permission where shared_with ".$shared_with." and uid = '".$uid."'";
		$db->query($id_query, __FILE__, __LINE__);
		#echo $id_query;
		#echo '<pre>';print_r($db);
		while ($db->next_record()) {
			if($i!=(count($shared_with_query)-1)){
			if($sw!='') $sw .= '|';
			$sw .= $db->f('shared_with_num');
			}
			else {
				
				if($finalUID!='') $finalUID .= ' or ';
				$finalUID .= $GLOBALS['s3ids'][$element]." = '".$db->f('shared_with_num')."'";
				
				$array_ids[$db->f('shared_with_num')]=$db->f('permission_level');
				#$str_ids = ($str_ids=='')?($db->f('id')):('|'.$db->f('id'));
				$str_ids .= ($str_ids=='')?(" substr(shared_with,2,length(shared_with)) = '".$db->f('shared_with_num')."'"):(" or substr(shared_with,2,length(shared_with)) = '".$db->f('shared_with_num')."'");
			}

		}
		}
		
		
		
	}
	#echo '<pre>';print_r($array_ids);exit;
	###$GLOBALS['s3ids'][$element]
	#Now through the query into the main CORE table
	if($finalUID!='')
	{$finalUID = " and (".$finalUID.")";
	$return=compact('finalUID', 'str_ids', 'array_ids');
	return ($return);
	}
	else {
		return (False);
	}
	
	
	#echo $tmpQuery;exit;
	
	
}

function permissionsQuery($s3ql, $element, $element_id, $user_id, $db){
	$letter = strtoupper(substr($s3ql['from'], 0,1));
	$shared_with_user = 'U'.$user_id;
	$ids = permissionPropagation($letter, $shared_with_user,$user_id, $db);
	#echo '<pre>';print_r($ids);exit;
	if(!empty($ids)){
	foreach ($ids as $uid=>$pl) {
		$finalUID .= ($finalUID=='')?" ".$GLOBALS['s3ids'][$element]." = '".substr($uid,1,strlen($uid))."'":" or ".$GLOBALS['s3ids'][$element]." = '".substr($uid,1,strlen($uid))."'";
		$array_ids[$uid] = $pl;
		$str_ids .= ($str_ids=='')?substr($uid,1,strlen($uid)):'|'.substr($uid,1,strlen($uid));
	}
	$finalUID = " and (".$finalUID.")";
	
	$return =compact('finalUID', 'str_ids', 'array_ids');
	return $return;
	}
	else {
		return (False);
	}
	
}

		
function selectQuery($D)
	{extract($D);
			
			if($s3ql['from']=='deployment') ##To integrate with the remainint queries
			{
			
				$data[0] = array('mothership'=>$GLOBALS['s3db_info']['deployment']['mothership'], 'deployment_id'=>$GLOBALS['s3db_info']['deployment']['Did'], 'description'=>$GLOBALS['s3db_info']['server']['site_intro'],'url'=>S3DB_URI_BASE, 'message'=>'Successfully connected to deployment '.$GLOBALS['s3db_info']['deployment']['Did'].'. Please provice a key to query the data (for example: '.(($_SERVER['https']=='on')?'https://':'http://').$def.S3DB_URI_BASE.'/URI.php?key=xxxxxxxx. For syntax specification and instructions refer to http://s3db.org/');

				
				return $data;
			
			}
			#echo '<pre>';print_r($s3ql);
			if(in_array($s3ql['from'], array_keys($GLOBALS['plurals'])))
				$s3ql['from']=$GLOBALS['plurals'][$s3ql['from']];
			#echo '<pre>';print_r($s3ql);exit;
			if($s3ql['from']=='classes')
				$s3ql['from']='collections';
			if($s3ql['from']=='instances')
				$s3ql['from']='items';
			if($s3ql['from']=='keys' && $_SESSION['db']=='')
			{
		
			return (formatReturn($GLOBALS['error_codes']['not_a_query'], 'Access keys cannot be queried in the API.', $s3ql['format'],''));
			exit;
			}

			$target = $s3ql['from'];
			$letter = strtoupper(substr($s3ql['from'],0,1));
			$table = strval($target);
			$element = $target;
			$cols = $GLOBALS['dbstruct'][$target];
			$element_id = $s3ql['where'][$GLOBALS['s3ids'][$element]];
			
			
			if($table!='' && !in_array($table, array_keys($GLOBALS['dbstruct'])))
			{			
			return (formatReturn($GLOBALS['error_codes']['not_a_query'], 'Not a valid query.', '', $s3ql['format']));
			}
			
			#manage data in select
			#echo '<pre>';print_r($s3ql);
			
			
			#array_keys contains the things to replace and array_values the replacements
			if($s3ql['select']!='' && $s3ql['select']!='*'){
				$s3ql_out=ereg_replace(' ', '', $s3ql['select']);#take out all the spaces
				$returnFields = explode(',', $s3ql_out);
			
				if(!ereg($GLOBALS['s3ids'][$element], $s3ql['select']))
					if(ereg('count|max|min', $s3ql['select']))
					{
					$SQLfun = ereg_replace("\(.*\)", "",$select);
					$SQLfun = ereg_replace("count as count", "count",$SQLfun);
					$s3ql['select'] = '*';
					}
					else {
					$s3ql['select'] .= ','.$GLOBALS['s3ids'][$element];
					}
				
					
			}
			else
			$s3ql['select']='*';
			#echo '<pre>';print_r($s3ql);exit;
			
			$toreplace = array_keys($GLOBALS['s3map'][$target]);
			
			$replacements = array_values($GLOBALS['s3map'][$target]);
			$s3ql['select'] = str_replace($toreplace, $replacements, $s3ql['select']);
			
			
			$select = urldecode($s3ql['select']);
			$select = eregi_replace('uid', $GLOBALS['s3ids'][$element].' as uid', $select);
			$select = eregi_replace('uri', $GLOBALS['s3ids'][$element].' as uri', $select);
				
			
				#echo $P['out'].$P['SQLfun'];
				if($select==$SQLfun) $SQLfun='';
			
			
			#$s3ql_where_keys = str_replace(array('item_id', 'collection_id'), array('instance_id', 'class_id'), array_keys($s3ql['where']));
			#$s3ql['where'] = array_combine($s3ql_where_keys, $s3ql['where']);
			
			
			#transofrmt s3ql and get the return Fields
			$tranformed = S3QLselectTransform(compact('s3ql', 'db', 'user_id'));
			extract($tranformed);

			
			#If there is any sort of S3 UID in the query, check its score when compared to the from 
			$score = array('D'=>'7', 'G'=>'6', 'U'=>'5', 'P'=>'4', 'C'=>'3', 'R'=>'3', 'I'=>'2', 'S'=>'1');
			$fromScore = $score[strtoupper(substr($target,0,1))];

			$s3Ids = array_merge($GLOBALS['COREids'], array('rulelog'=>'rule_id', 'statementlog'=>'statement_id'));
			#echo '<pre>';print_r($s3ql);exit;
			
			$shared_with_query = array();
			
				foreach($s3Ids as $COREelement=>$COREelement_id)
					{
					
					if($s3ql['where'][$COREelement_id]!='' && !ereg('^~|regexp', $s3ql['where'][$COREelement_id]))
						{
						
						$id_name = $COREelement_id;
						$id_letter = strtoupper(substr($id_name,0,1));
						$whereScore[strtoupper(substr($id_name,0,1)).$s3ql['where'][$COREelement_id]] = $score[strtoupper(substr($id_name,0,1))];#when idNameScore is < $fromScore, then we know: we are trying to query all resources that can view another particular resource (for example,all users that can view project x

						#echo $id_name;exit;
						$uid = strtoupper(substr($COREelement, 0, 1)).$s3ql['where'][$COREelement_id];
						$uid_info=uid($uid);
						
						
						$element_info = URIinfo($uid, $user_id, $key, $db);
						$WhereInfo[$uid_info['uid']]=$element_info;
						#echo '<pre>';print_r($user_info);
						#echo '<pre>';print_r($element_info);exit;
						if (!is_array($element_info)) {
							
							return formatReturn($GLOBALS['error_codes']['something_does_not_exist'], $uid.' does not exist',$s3ql['format'],'');
							exit;
						}
						elseif ($id_letter!=strtoupper(substr($element, 0,1))) { ##Shared_with is any UID that can eb shared with any of the elements being requested (for example, Collection_id is shared_with Project, but Project_id is not shared  with Project
							
							array_push($shared_with_query, $uid);
							#do permissions on this uid propagate?
							#echo '<pre>';print_r($whereScore);exit;
						}
						else {
							$self_id = $s3ql['where'][$COREelement_id];
							if(!$element_info['view'])
								{return formatReturn($GLOBALS['error_codes']['no_permission_message'], 'User does not have permission on '.$uid,$s3ql['format'],'');
								exit;
								}

						}
						}

					}
			#echo '<pre>';print_r($WhereInfo);exit;
			
			if($self_id!=''){
			$data[0] = $element_info;
			
			}
			else{
			#start building the query:
			$user_query = "select ".$select." from s3db_".$GLOBALS['s3tables'][$table];
			
				if(!user_is_admin($user_id, $db))
				$cols = array_diff($cols, array('account_pwd', 'account_phone', 'account_email', 'project_folder'));#remove a few cols from query
				
			#echo $user_query;exit;	
			#now add some constrains necessary due to the type of resource
			
			if(!(user_is_admin($user_id, $db) && $s3ql['where']['status']=='I'))
			if(ereg('projects|classes|instances|rules|statements', $target))
				$status = "status!='I' and ";
			$user_query_const .= " where ".$status.$GLOBALS['s3ids'][$target]."!='0'";
			
			###
			#Filter query according to the element being requested
			$user_query_const .= filterByElement($s3ql, $user_id,$db);
			if($user_query_const)
			$user_query .= $user_query_const;
			else {
				exit;
			}
			
			if (!empty($shared_with_query) && strtoupper(substr($target, 0,1))!='U') {
			 
			##Added 15Apr08 for faster queries (taking too long on MySQL using table joins)
			$uidQuery = simpleQueryUID($shared_with_query, $element, $db);
			
			if($uidQuery)
				{extract($uidQuery);
				$user_query .= $finalUID;
				}
			elseif($user_id!='1') {
					
					return formatReturn($GLOBALS['error_codes']['no_results'], 'Your query on '.$target.' did not return any results', $format,'');
				}
			}
			#echo 'ola'.$uidQuery;exit;
			
			#now constrainthe query to resources that user cann access. Check for inherited permissions and direct permissions. Project is connected to deployment, rule and class to project, and so on. (see S3DB third report for the schema)
			
			#Fetch the cols of what is to be returned. Check for SqL functions. This will only affect the output
				
				if($s3ql['select']!='')
					{
					$out = urldecode($s3ql['select']);
					
					$SQLfun = ereg_replace("\(.*\)", "",$out);
					$SQLfun = ereg_replace("count as count", "count",$SQLfun);
					$P['out'].$P['SQLfun'];
					if($out==$SQLfun) $SQLfun='';
					else {
						$extracol = $out;
					}
					}
					
			#echo $SQLfun;
			#Extract from the s3ql the value that are part of the syntax and assume the rest are the SQL extras (limit, creted_by, etc)
				$syntax = array('key', 'select', 'from', 'where', 'format');
					 
				foreach($s3ql as $i=>$value)
					if(!in_array($i, $syntax) && $value!='')
				{
					$SQLextra[$i] = ' '.ereg_replace('_', ' ' ,$i).' '.$value;
					
				
				}
					#echo '<pre>';print_r($SQLextra);exit;
				
			#if there is orderby, move to the beginnign of the array
				if($SQLextra['order_by']!='') 
					{		
						$SQLextra=array_merge(array('order_by'=>$SQLextra['order_by']),$SQLextra); 
					}
				
				if(is_array($SQLextra))
				foreach ($SQLextra as $key=>$value) {
					$query_extra .= $value;
				}
				
			
			#Put in $P the values of what is queried, add to cols, if not already there, whatever is queried. Check if there are regular expressions anywhere. equalit will be replace by the regular expression
			$cols = $GLOBALS['dbstruct'][$table];
			
			foreach($cols as $col)
					{
					if($s3ql['where'][$col]!='')
						{
						if(!in_array($col, $GLOBALS['COREids']) && $col!=$GLOBALS['COREids'][$element]){
						$user_query_fields .= ' and '.$col.'  '.parse_regexp($s3ql['where'][$col]);
						}
						$P[$col]=parse_regexp($s3ql['where'][$col]);
						}
					
					}

			#glue them together.
			$user_query .= $user_query_fields.$query_extra;
			
			###Finally perform the query on whatever table is specified
			#$user_query = "select * from s3db_resource where resource_class_id = '389';";
			//echo $user_query;
			//exit;
			#run it
			#complete query on LOCAL resources
			$db->query($user_query, __LINE__, __FILE__);
			$dbdata = get_object_vars($db);
			
			#echo '<pre>';print_r($dbdata);
			#exit;
			
			if($dbdata['Errno']!='0')
			{	return formatReturn	($GLOBALS['error_codes']['something_went_wrong'], $dbdata['Error'], $format,'');
				
			}
			
			#put it in a nice structured variable
				$cols = $GLOBALS['dbstruct'][$target];
				if(is_array($returnFields) && $extracol=='')
				$cols = array_unique(array_merge($cols, $returnFields));
				
				
				#echo '<pre>';print_r($cols);
				while($db->next_record())
						{	
						#echo '<pre>';print_r($db);
						$resultStr .= "\$data[] = Array(";
					
						if ($extracol!='')
						$resultStr .= "'".$extracol."'=>'".$db->f($SQLfun)."',";
							
						foreach ($cols as $col)
								
							{
								$resultStr .= "'".$col."'=>'".addslashes($db->f($col))."'";
								if($col != end($cols))
								$resultStr .= ",";
								if($col==$GLOBALS['s3ids'][$target])
									$retrieved['ids_str'] .= ($retrieved['ids_str']=='')?$db->f($col):'|'.$db->f($col);
							}
					
							$resultStr .= ");";
				
						}
						#echo $resultStr;
						#evaluate the long string
						eval($resultStr);
							
					
					if(is_array($data))
					$data = array_filter($data);
			
			#echo '<pre>';print_r($data);
			#BEFORE outputting data, are there any remote resources where the user is allowed?
				$ucode = strtoupper(substr($element, 0,1));
				$ucode_and_id = $ucode.$element_id;
				
				
				##Added ability to search locally on april 15 to optimize queries
				if(!ereg('users|group|projects|keys|rulelog|statementlog', $s3ql['from']) && !ereg('true|1', $s3ql['where']['local'])){
				#REMOTE USERS< GROUPS< PROJECTS ARE INSERTED INTO DEPLOYMENT,M NO NEED TO FIND THEM AGAIN
				
				
				#Remote permissions query: changed 23Mar08 for specifying query in uid
				$remote_permissions_query .= "select * from s3db_permission where uid ".$regexp." '^".$GLOBALS['s3codesInv'][$GLOBALS['singulars'][$s3ql['from']]]."' and permission_level ".$regexp." '(1|2)$'";
				
				#the idea is to select anything that does not exist locally
				$remote_permissions_query .= " and id not in (select ".$GLOBALS['s3ids'][$GLOBALS['s3codes'][$letter]]." from s3db_".$GLOBALS['s3tables'][$GLOBALS['s3codes'][$letter]].")";
				
				if($user_self_query!='')
					$remote_permissions_query .= " and id = '".$self_id."'";
				if($uidQuery!='')
					{$remote_permissions_query .= " and id ".$regexp." '^(".$uidQuery['str_ids'].")$'";
				}
				if($permissionsQuery!='')
					{$remote_permissions_query .= " and id ".$regexp." '^(".$permissionsQuery['str_ids'].")$'";
				}
				
				#echo $remote_permissions_query;exit;
				$db->query($remote_permissions_query, __LINE__, __FILE__); #this will return ALL the elements shared by USER
				
				while($db->next_record())
					{
					
					$remote_id[] =array('uid'=>$db->f('uid'),
												'shared_with' =>$db->f('shared_with'),
												'permission_level' =>$db->f('permission_level'));
					
					}
				
				if(is_array($remote_id)){
						foreach ($remote_id as $rem_id) {
						
						#$rem_uid = substr($rem_id['uid'],1,strlen($rem_id['uid']));
						$rem_uid = $rem_id['uid'];
						$rem_resource_data =URIinfo($rem_uid, $user_id,$s3ql['key'], $db);
						#echo '<pre>';print_r($rem_resource_data);exit;
						if(is_array($rem_resource_data)){
						$rem_resource_data['shared_with']=$rem_id['shared_with'];
						$rem_resource_data['uid']=$rem_id['uid'];
						$rem_resource_data['permission_level']=$rem_id['permission_level'];
						}
						#echo '<pre>';print_r($rem_resource_data);exit;
						#concatenate them in the results; THIS SHOWS ONLY REMOTE RESOURCES THAT ARE AVAILABLE AT THE MOMENT!
						
						if(is_array($s3ql['where']))
						foreach ($s3ql['where'] as $query_field=>$query_value) {
							
							if($query_value!=$rem_resource_data[$query_field])
							{
								if(!in_array($query_field, $GLOBALS['COREids']))
								{$rem_resource_data=array();
								}
							}
						}
						
						if(is_array($data) && is_array($rem_resource_data))	
							array_push($data, $rem_resource_data);
						elseif(is_array($rem_resource_data) && !empty($rem_resource_data))
						$data[] = $rem_resource_data;
						}
					}
				}
				if(is_array($array))
				$data = array_filter($data);
				
				#echo '<pre>';print_r($data);exit;
			
			
			#now we're ready to display the data			
				$pack= compact('data', 'whereScore', 'WhereInfo', 'fromScore', 's3ql', 'key', 'target', 'db', 'user_id', 'cols', 'returnFields', 'target','uidQuery');
				if(!ereg('keys|accesslog',$s3ql['from']))
				$data = includeAllData($pack);
			}	
			#echo '<pre>';print_r($data);exit;
				#$data = filterDataForQuery($data, $cols,$returnFields, $whereScore, $fromScore, $s3ql, $key, $target, $db, $user_id);
				
				
									
					if(is_array($data) && !empty($data))
						{$data = array_combine(range(0,count($data)-1), $data);
						return $data;
						}
						else {
						
						return formatReturn($GLOBALS['error_codes']['no_results'], 'Your query returned no results', $format,'');
						
						}
				
	}

?>