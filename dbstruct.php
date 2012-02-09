<?php
$dbstruct = array(
				'deployments'=>array('deployment_id', 'url', 'checked_on', 'publickey', 'created_on','name', 'modified_on'),
				'users'=>array('account_id','account_lid', 'account_status','account_uname', 'account_email','account_phone', 'account_type', 'account_group','created_on', 'created_by', 'addr1', 'addr2', 'city', 'state', 'postal_code', 'country', 'account_addr_id'), 
				'groups'=>array('account_id','account_lid','account_uname', 'account_type','created_on', 'created_by'),
				'keys'=>array('key_id', 'account_id','expires','notes','uid'),
				'filekeys'=>array('filekey', 'filename', 'filesize','status','expires', 'created_by'),
				'accesslog'=>array('session_id','login_timestamp', 'login_id', 'ip'), 
				'projects'=>array('project_id', 'project_name', 'project_status','project_description', 'project_owner','project_folder', 'created_on', 'created_by'), 
				'rules'=>array('rule_id', 'subject', 'verb', 'object', 'notes', 'project_id', 
				'created_on', 'created_by', 'permission', 'subject_id', 'object_id', 'verb_id', 'validation'), 
				'requests'=>array('project_id', 'rule_id', 'account_id', 'notes', 'status', 'requested_on', 'uri'),
				'classes'=>array('resource_id', 'entity', 'notes', 'project_id','created_on', 'created_by', 'iid'), 
				'instances'=>array('resource_id', 'resource_class_id','entity', 'notes', 'project_id','created_on', 'created_by' ,'iid'), 
				'statements'=>array('statement_id', 'value', 'notes', 'file_name', 'file_size','rule_id', 'resource_id', 'project_id', 'created_on', 'created_by', 'status'),
				'rulelog'=>array('rule_id', 'old_subject', 'old_verb', 'old_object', 'old_notes', 'project_id', 'created_on', 'created_by', 'action', 'action_timestamp', 'action_by'), 
				'project_users'=>array('project_id', 'user_id', 'permission_level'), 
				'statement_log'=>array('statement_log_id', 'statement_id', 'value', 'notes', 'old_resource_id', 'old_rule_id', 'old_resource_id', 'old_project_id', 'created_on', 'created_by', 'action', 'action_timestamp', 'action_by'),
				'user'=>array('account_id','account_lid', 'account_status','account_uname', 'account_email','account_phone', 'account_type', 'account_group','created_on', 'created_by', 'addr1', 'addr2', 'city', 'state', 'postal_code', 'country', 'account_addr_id'), 
				'group'=>array('account_id','account_lid','account_uname', 'account_type','created_on', 'created_by'),
				'key'=>array('key_id', 'account_id','expires','notes','uid'),
				'filekey'=>array('filekey', 'filename', 'filesize','status','expires', 'created_by'),
				'project'=>array('project_id', 'project_name', 'project_status','project_description', 'project_owner','project_folder', 'created_on', 'created_by'), 
				'rule'=>array('rule_id', 'subject', 'verb', 'object', 'notes', 'project_id', 
				'created_on', 'created_by', 'permission', 'subject_id', 'object_id', 'verb_id', 'validation'), 
				'requests'=>array('project_id', 'rule_id', 'account_id', 'notes', 'status', 'requested_on', 'uri'),
				'class'=>array('resource_id', 'entity', 'notes', 'project_id','created_on', 'created_by', 'iid'), 
				'instance'=>array('resource_id', 'resource_class_id','entity', 'notes', 'project_id','created_on', 'created_by' ,'iid'), 
				'statement'=>array('statement_id', 'value', 'notes', 'file_name', 'file_size','rule_id', 'resource_id', 'project_id', 'created_on', 'created_by', 'status'),
				'permission'=>array('uid','shared_with','permission_level', 'id_num', 'id_code','created_by','created_on'),
				);
$dbstruct['collections']=$dbstruct['classes'];
$dbstruct['collection']=$dbstruct['class'];
$dbstruct['items']=$dbstruct['instances'];
$dbstruct['item']=$dbstruct['instance'];
$dbstruct['deployment']=$dbstruct['deployments'];

$s3tables = array('accesslog'=>'access_log','deployments'=>'deployment', 'users'=>'account', 'groups'=>'account', 'keys'=>'access_keys', 'projects'=>'project', 'classes'=>'resource', 'collections'=>'resource', 'instances'=>'resource','items'=>'resource', 'rules'=>'rule', 'statements'=>'statement', 'filekeys'=>'file_transfer', 'rulelog'=>'rule_change_log','deployment'=>'deployment', 'user'=>'account', 'group'=>'account', 'key'=>'access_keys', 'project'=>'project', 'class'=>'resource','collection'=>'resource', 'instance'=>'resource', 'item'=>'resource','rule'=>'rule', 'statement'=>'statement', 'filekey'=>'file_transfer','permission'=>'permission','permissions'=>'permission'); #both singular word and plural word will work

$s3ids = array('accesslog'=>'session_id', 'deployment'=>'deployment_id', 'user'=>'account_id', 'group'=>'account_id', 'key'=>'key_id', 'project'=>'project_id', 'class'=>'resource_id', 'collection'=>'resource_id', 'instance'=>'resource_id', 'item'=>'resource_id', 'rule'=>'rule_id', 'statement'=>'statement_id', 'filekey'=>'file_id', 'rulelog'=>'rule_id','deployments'=>'deployment_id', 'users'=>'account_id', 'groups'=>'account_id', 'keys'=>'key_id', 'projects'=>'project_id', 'classes'=>'resource_id', 'collections'=>'resource_id', 'instances'=>'resource_id', 'items'=>'resource_id', 'rules'=>'rule_id', 'statements'=>'statement_id', 'filekeys'=>'file_id','permission'=>'uid');

$COREids = array('deployment'=>'deployment_id', 'project'=>'project_id', 'collection'=>'collection_id', 'item'=>'item_id', 'rule'=>'rule_id', 'statement'=>'statement_id', 'user'=>'user_id', 'group'=>'group_id'
#, 'collection'=>'collection_id', 'item'=>'item_id'
);


$s3codes = array('D'=>'deployment', 'U'=>'user', 'G'=>'group', 'K'=>'key', 'P'=>'project', 'C'=>'collection', 'I'=>'item', 'R'=>'rule', 'S'=>'statement', 'F'=>'file', 'L'=>'rulelog');

$s3codesInv = array('deployment'=>'D', 'user'=>'U', 'group'=>'G', 'project'=>'P','class'=>'C', 'collection'=>'C', 'instance'=>'I', 'item'=>'I', 'rule'=>'R', 'statement'=>'S', 'file'=>'F', 'rulelog'=>'R','key'=>'K');

$COREletter = array('deployment_id'=>'D', 'user_id'=>'U', 'group_id'=>'G', 'project_id'=>'P',  'subject_id'=>'C', 'verb_id'=>'I', 'object_id'=>'C', 'item_id'=>'I', 'rule_id'=>'R', 'statement_id'=>'S', 'collection_id'=>'C');

#$tmp1=array_values($COREletter);$tmp2=array_keys($COREletter);
#$GLOBALS['COREletterInv'] = array_combine($tmp1,$tmp2);
$GLOBALS['COREletterInv']=unserialize('a:8:{s:1:"D";s:13:"deployment_id";s:1:"U";s:7:"user_id";s:1:"G";s:8:"group_id";s:1:"P";s:10:"project_id";s:1:"C";s:13:"collection_id";s:1:"I";s:7:"item_id";s:1:"R";s:7:"rule_id";s:1:"S";s:12:"statement_id";}');
#this is a temporary solution because running this piece on the console is giving me an error message

$GLOBALS['pointer'] = array('created_by'=>'user_id','project_owner'=>'user_id','subject_id'=>'collection_id','object_id'=>'collection_id', 'verb_id'=>'item_id');

$GLOBALS['propertyURI'] = array(
								'D'=>array('name'=>'http://www.w3.org/2000/01/rdf-schema#label', 
									'description'=>'http://www.w3.org/2000/01/rdf-schema#comment',
									'created_on'=>'http://purl.org/dc/terms/created',
									'created_by'=>'http://purl.org/dc/terms/creator'),
								'U'=> array('login'=>'http://www.w3.org/2000/01/rdf-schema#label', 
								'email'=>'http://xmlns.com/foaf/0.1/mbox',
								'password'=>'http://xmlns.com/foaf/0.1/password',
								'name'=>'http://xmlns.com/foaf/0.1/name',
								'created_on'=>'http://purl.org/dc/terms/created',
								'created_by'=>'http://purl.org/dc/terms/creator'
								),
								
								'G'=>array('name'=>'http://www.w3.org/2000/01/rdf-schema#label',
								'created_on'=>'http://purl.org/dc/terms/created',
								'created_by'=>'http://purl.org/dc/terms/creator'),
								
								'P'=>array('name'=>'http://www.w3.org/2000/01/rdf-schema#label', 
									'description'=>'http://www.w3.org/2000/01/rdf-schema#comment',
									'created_on'=>'http://purl.org/dc/terms/created',
									'created_by'=>'http://purl.org/dc/terms/creator'),
								'C'=>array( 
									'entity'=>'http://www.w3.org/2000/01/rdf-schema#label',
									'notes'=>'http://www.w3.org/2000/01/rdf-schema#comment',
									'created_on'=>'http://purl.org/dc/terms/created',
									'created_by'=>'http://purl.org/dc/terms/creator'),

								'R'=>array(
								'subject_id'=>'http://www.w3.org/2000/01/rdf-schema#domain', 
								'verb_id'=>'http://www.s3db.org/core#predicate',
								'verb'=>'http://www.w3.org/2000/01/rdf-schema#label',
								'object'=>'http://www.s3db.org/core#object',
								'object_id'=>'http://www.w3.org/2000/01/rdf-schema#range',
								'notes'=>'http://www.w3.org/2000/01/rdf-schema#comment',
								'created_on'=>'http://purl.org/dc/terms/created',
								'created_by'=>'http://purl.org/dc/terms/creator',
								'validation'=>'http://www.s3db.org/core#validation'),

								'I'=>array('collection_id'=>'http://www.w3.org/1999/02/22-rdf-syntax-ns#type', 
								'notes'=>'http://www.w3.org/2000/01/rdf-schema#label',
								'created_on'=>'http://purl.org/dc/terms/created',
								'created_by'=>'http://purl.org/dc/terms/creator'),

								'S'=>array('item_id'=>'http://www.s3db.org/core#subject', 
								'rule_id'=>'http://www.s3db.org/core#predicate', 
								'value'=>'http://www.s3db.org/core#object', 
								'notes'=>'http://www.w3.org/2000/01/rdf-schema#comment',
								'created_on'=>'http://purl.org/dc/terms/created',
								'created_by'=>'http://purl.org/dc/terms/creator')
								);
 
$CORElabel = array('deployment_id'=>'name', 'user_id'=>'account_lid', 'group_id'=>'account_lid', 'project_id'=>'project_name', 'class_id'=>'entity', 'subject_id'=>'subject', 'verb_id'=>'verb', 'object_id'=>'object', 'instance_id'=>'notes', 'rule_id'=>'verb', 'statement_id'=>'value', 'created_by'=>'account_lid');

$COREcomment = array('deployment'=>'description', 'project'=>'project_description', 'class'=>'notes', 'rule'=>'notes', 'statement'=>'notes');


$messages = array('syntax_message'=>"For syntax instructions refer to <a href='http://s3db.org/documentation'>S3DB Documentation</a>",
						'success'=>' Error code: <error>0</error>', 
						'not_a_query'=>' Error code: <error>1</error><message>'.$target.' is not a valid S3element. Valid elements: groups, users, keys, filekeys, projects, rules, statements, classes, instances, rulelog</message>',
						'something_went_wrong'=>' Error code: <error>2</error> ',	
						'something_missing'=>' Error code: <error>3</error> ',
						'repeating_action'=>' Error code: <error>4</error> ',
						'no_permission_message'=>' Error code: <error>5</error> ',
						'something_does_not_exist'=>' Error code: <error>6</error> ',
						'wrong_query_for_purpose'=>' Error code: <error>7</error> ',
						'wrong_input'=>' Error code: <error>8</error> ',
						'nothing_to_change'=>' Error code: <error>9</error> ',
						'no_results'=>' Error code: <error>10</error>',
						'not_valid'=>' Error code: <error>11</error>',
						'nothing_to_change'=>' Error code: <error>12</error>');

$GLOBALS['error_codes'] = array('success'=>0,'not_a_query'=>1,'something_went_wrong'=>2,'something_missing'=>3,'repeating_action'=>4, 'no_permission_message'=>5,'something_does_not_exist'=>6,'wrong_query_for_purpose'=>7, 'wrong_input'=>8,'wrong_input'=>9,'no_results'=>10,'not_valid'=>11,'nothing_to_change'=>12);
						
						
$GLOBALS['N3coreNames']=array('deployment'=>'s3db:deployment','project'=>'s3db:project', 'collection'=>'s3db:collection', 'rule'=>'s3db:rule', 'item'=>'s3db:item', 'statement'=>'s3db:statement', 'user'=>'s3db:user', 'group'=>'s3db:group');

$GLOBALS['N3Names']=array('deployment'=>'deployment','project'=>'project', 'collection'=>'collection', 'rule'=>'rule', 'item'=>'item', 'statement'=>'statement', 'user'=>'user', 'group'=>'group');

$GLOBALS['plurals'] = array('deployment'=>'deployments','key'=>'keys', 'user'=>'users', 'group'=>'groups', 'project'=>'projects', 'class'=>'classes', 'instance'=>'instances', 'rule'=>'rules', 'statement'=>'statements', 'file'=>'files', 'collection'=>'collections', 'item'=>'items');

#$GLOBALS['singulars'] = array_combine(array_values($GLOBALS['plurals']), array_keys($GLOBALS['plurals']));
$GLOBALS['singulars'] = unserialize('a:12:{s:11:"deployments";s:10:"deployment";s:4:"keys";s:3:"key";s:5:"users";s:4:"user";s:6:"groups";s:5:"group";s:8:"projects";s:7:"project";s:7:"classes";s:5:"class";s:9:"instances";s:8:"instance";s:5:"rules";s:4:"rule";s:10:"statements";s:9:"statement";s:5:"files";s:4:"file";s:11:"collections";s:10:"collection";s:5:"items";s:4:"item";}');

$GLOBALS['common_attr'] = array('id', 'label','description','creator','created');
$GLOBALS['s3map'] = array('deployments'=>array('id'=>'deployment_id',
												'label'=>'name',
												'description'=>'message',
												'creator'=>'created_by',
												'created'=>'created_on'),
						 'users'=>array('user_id'=>'account_id',
										'login'=>'account_lid',
										'name'=>'account_lid',
										'fullname'=>'account_uname',
										'username'=>'account_uname',
										#'password'=>'account_pwd',
										'email'=>'account_email',
										'phone'=>'account_phone',
										'address'=>'addr1',
										'address2'=>'addr2',
										'id'=>'account_id',
										'label'=>'login',
										'description'=>'account_uname',
										'creator'=>'created_by',
										'created'=>'created_on'),
							'groups'=>array('group_id'=>'account_id',
											'groupname'=>'account_lid',
											'name'=>'account_lid'),
							'keys'=>array('description'=>'notes'),
							'filekeys'=>array(),
							'requests'=>array(),
							'permission'=>array(),
							'accesslog'=>array('account_lid'=>'login_id', 'time'=>'login_timestamp',),
							'projects'=>array('name'=>'project_name',	
												'description'=>'project_description',
												'comment'=>'project_description',
												'id'=>'project_id',
												'label'=>'project_name',
												'creator'=>'created_by',
												'created'=>'created_on'),
							'items'=>array('class_id'=>'resource_class_id',
												'collection_id'=>'resource_class_id',
												'instance_id'=>'resource_id',
												'item_id'=>'resource_id',
												'description'=>'notes',
												'comment'=>'notes',
												'id'=>'resource_id',
												'label'=>'$entity : $notes',
												'creator'=>'created_by',
												'created'=>'created_on'),
				
							'collections'=>array('class_id'=>'resource_id',
											'name'=>'entity',
											'collection_id'=>'resource_id',
											'description'=>'notes',
											'comment'=>'notes',
											'id'=>'resource_id',
											'label'=>'entity',
											'creator'=>'created_by',
											'created'=>'created_on'),
							'rules'=>array('description'=>'notes',
										   'comment'=>'notes',
										   'id'=>'rule_id',
											'label'=>'$subject [$verb] $object',
											'creator'=>'created_by',
											'created'=>'created_on'),
							'rulelog'=>array(),
							'statement_log'=>array(),
							'statements'=>array('instance_id'=>'resource_id',
												'item_id'=>'resource_id',
												'id'=>'statement_id',
												'label'=>'value',
												'description'=>'notes',
												'comment'=>'notes',
												'creator'=>'created_by',
												'created'=>'created_on'), 
							'files'=>array('instance_id'=>'resource_id','item_id'=>'resource_id',
							));
$GLOBALS['s3map']['classes'] = $GLOBALS['s3map']['collections'];
$GLOBALS['s3map']['instances'] = $GLOBALS['s3map']['items'];

$GLOBALS['s3mask'] = array('class_id'=>'collection_id', 'resource_class_id'=>'collection_id', 'instance_id'=>'item_id');
$GLOBALS['s3input'] = array(
						'key'=>array('key', 'expires', 'notes'),
						'project'=>array('project_id','name','description', 'created_by', 'created_on'),
						'collection'=>array('collection_id', 'project_id', 'name', 'notes', 'created_by', 'created_on'),
						'rule'=>array('rule_id', 'project_id',  'subject_id', 'verb','verb_id', 'object', 'object_id','validation', 'created_by', 'created_on','notes'),
						'item'=>array('item_id', 'collection_id', 'notes', 'created_by', 'created_on'),
						'statement'=>array('statement_id', 'item_id', 'rule_id', 'value', 'notes', 'created_by', 'created_on'),
						'file' => array('statement_id', 'item_id', 'rule_id', 'filekey', 'notes', 'created_by', 'created_on'),
						'user' => array('user_id', 'login', 'name','email', 'created_by', 'created_on'),
						'group'=>array('group_id', 'name', 'created_by', 'created_on'));

$GLOBALS['queriable'] = array(
						'deployment'=>array('deployment_id', 'url', 'checked_on', 'publickey'),
						'project'=>array('project_id', 'project_name', 'project_status','project_description', 'project_owner','project_folder', 'created_on', 'created_by', 'acl', 'uid','uri','permission_level','change','add_data'), 
						'group'=>array('group_id','name','groupuname', 'created_on', 'created_by', 'uid','uri','change','add_data'),
						'user'=>array('user_id','username', 'account_status','account_uname', 'account_email','account_phone', 'account_type', 'account_group','created_on', 'created_by', 'addr1', 'addr2', 'city', 'state', 'postal_code', 'country', 'uid','uri','change','add_data'),
						'collection'=>array('collection_id', 'entity', 'name', 'notes', 'description', 'project_id','created_on', 'created_by', 'uid','uri','name','description','permission_level','change','add_data'),
						'item'=>array('item_id', 'collection_id','entity',  'notes', 'project_id','created_on', 'created_by' ,'iid', 'uid','uri','change','add_data'),
						'rule'=>array('rule_id', 'subject', 'verb', 'object', 'subject_id', 'verb_id', 'object_id', 'notes','description','validation', 'project_id', 'created_on', 'created_by', 'uid','uri','permission_level','change','add_data'),
						'statement'=>array('statement_id', 'value', 'notes', 'file_name', 'file_size','rule_id', 'item_id','resource_id','project_id', 'created_on', 'created_by', 'uid','uri','subject','verb','object','description','permission_level','change','add_data'),
						);

#$GLOBALS['inherit'] = array('project_id'=>array('deployment_id'),'collection_id'=>array('project_id'),'rule_id'=>array('project_id','subject_id','verb_id','object_id'),'item_id'=>array('collection_id'),'statement_id'=>array('rule_id','item_id','value'));
$GLOBALS['inherit'] = array('project_id'=>array(),'collection_id'=>array('project_id'),'rule_id'=>array('project_id','subject_id','verb_id','object_id'),'item_id'=>array('collection_id'),'statement_id'=>array('rule_id','item_id','value'));


$GLOBALS['inherit_code'] = array('P'=>array(),'C'=>array('P'), 'R'=>array('P','C','I'),'I'=>array('C'),'S'=>array('R','I'));
$GLOBALS['next_element'] = array('P'=>array('C','R'),'C'=>array('I','R'), 'R'=>array('S'),'I'=>array('S'),'S'=>array());

$GLOBALS['not_uid_specific'] = array('http://www.w3.org/2000/01/rdf-schema#label','http://www.w3.org/2000/01/rdf-schema#comment','http://purl.org/dc/terms/created','http://purl.org/dc/terms/creator');

$GLOBALS['CORElabel'] = $CORElabel;
$GLOBALS['COREletter']=$COREletter;
$GLOBALS['COREids'] = $COREids;
$GLOBALS['regexp'] = ($GLOBALS['s3db_info']['server']['db']['db_type']=='mysql')?'regexp':'~';
$GLOBALS['swap'] = array('C'=>'rule_id', 'I'=>'statement_id');
$GLOBALS['dbstruct'] = $dbstruct;
$GLOBALS['s3tables'] = $s3tables;
$GLOBALS['s3ids'] = $s3ids;
$GLOBALS['s3codes'] = $s3codes;
$GLOBALS['messages'] = $messages;
$GLOBALS['s3codesInv'] = $s3codesInv;
#$GLOBALS['protocol'] = ($_SERVER['HTTPS']!='')?'https://':'http://';
$https = ($_SERVER['HTTPS']!='')?('https://'):('http://');
$GLOBALS['URI'] = $https.$_SERVER['SERVER_NAME'].'/'.strtok($_SERVER['PHP_SELF'], '/');
$GLOBALS['Did'] = ($GLOBALS['s3db_info']['deployment']['Did']!='')?$GLOBALS['s3db_info']['deployment']['Did']:(($_SERVER['HTTPS']!='')?'https://':'http://'.$_SERVER['SERVER_NAME'].'/'.strtok($_SERVER['PHP_SELF'], '/'));
$GLOBALS['Did'] = (is_numeric(substr($GLOBALS['Did'],0,1)))?"D".$GLOBALS['Did']:$GLOBALS['Did'];
$GLOBALS['uploads'] = $GLOBALS['s3db_info']['server']['db']['uploads_folder'].$GLOBALS['s3db_info']['server']['db']['uploads_file'].'/';
$GLOBALS['endorsed']=$GLOBALS['s3db_info']['deployment']['endorsed_authorities'];

?>
