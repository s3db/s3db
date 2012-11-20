<?php
	function query_user_dictionaries($s3ql,$db, $user_id,$format='html') {
		if($s3ql['format']!='') { $format = $s3ql['format']; }
		
		#Decide the names
		$d['projectName'] = 	's3dbDictionary';
		$d['collections'] = array('s3dbNamespaces','s3dbLinks') ;
		$d['rules'] = array(
						array('s3dbNamespaces','uses','qname'),
						array('s3dbNamespaces','hasReference','url'),
						array('s3dbLinks','refersToUid','entity_id'),
						array('s3dbLinks','refersToUid','s3dbNamespaces'),
						array('s3dbLinks','refersToUid','term'),
						array('s3dbLinks','makesUseOf','s3dbNamespaces'),
						array('s3dbLinks','makesUseOf','term'),
						array('s3dbLinks','makesUseOf','relation'),
						array('s3dbLinks', 'hasValue','value'),
						array('s3dbLinks', 'hasValue','s3dbNamespaces'),
						array('s3dbLinks', 'hasValue','term')
					);
		$d['namespaces'] = array('rdf'=>'http://www.w3.org/1999/02/22-rdf-syntax-ns#','rdfs'=>'http://www.w3.org/2000/01/rdf-schema#','owl'=>'http://www.w3.org/2002/07/owl#','dc'=>'http://purl.org/dc/terms/',''=>$GLOBALS['URI'].'/', 'foaf'=>'http://xmlns.com/foaf/0.1/');

		#create the project
		$Dict = create_dictionary_project($d,$db,$user_id);
	
		#Read and interpret the query: is it a select, an insert, an update or a delete? 
		if(!$s3ql['db']) {
			$s3ql['db'] = $db;
			$s3ql['user_id'] = $user_id;   
		}
		if($s3ql['insert']!='') {
			$target=$s3ql['insert'];
			$action = 'insert';
		}
		if($s3ql['update']!='' || $s3ql['edit']!='') {
		   $target=$s3ql['update'];
		   $action = 'update';
		}
		if($s3ql['delete']!='') {
		   $target=$s3ql['delete'];
		   $action = 'delete';
		}
		if($s3ql['from']!='') {
		   $target=$s3ql['from'];
		   $action = 'select';
		}
		if($target=='namespaces') { $target = 'namespace'; }
		if($target=='links') { $target = 'link'; }
		#For actions, let's use the first dictionary by default; later I will figure out a way to let the user chose the dictionary
		$di=0;
		switch ($target) {
			case 'namespace':
				if($action=='insert') {
					list($valid,$msg) = check_ns_validity($s3ql['where'],$Dict[$di],$db,$user_id,$action);#using the first project found
					if($valid) {
						$Dict[0] = insert_new_ns($s3ql['where'],$Dict[$di],$db,$user_id);
						$msg = "Qname inserted";
						$error_code = '0';
						file_put_contents(S3DB_SERVER_ROOT.'/tmp/dict', serialize($Dict));
					}
				}
				if($action=='update' || $action=='delete') {		##REFER IN THE DOCUMENTATION THAT QNAME CANNOT BE CHANGED, AS IT IS BEING USED AS AN ID
					list($valid,$msg) = check_ns_validity($s3ql['where'],$Dict[$di],$db,$user_id,$action);#using the first project found
					if($valid) {
						$x = call_user_func($action."_ns", $s3ql['where'],$Dict[$di],$db,$user_id);
						list($w,$msg) = $x;
						$error_code = '0';
					}
				}
				if($action=='select') {
					foreach ($Dict as $di=>$Dict_info) {
						$s3qlI=compact('user_id','db');
						$s3qlI['from']='items';
						$s3qlI['where']['collection_id']=$Dict_info['collections']['s3dbNamespaces'];
						$items_of_namespace = S3QLaction($s3qlI);
						if(is_array($items_of_namespace)) {
							foreach ($items_of_namespace as $qname_info) {
								#find the statements
								$item_id=$qname_info['item_id'];
								$Dict[$di]['items']['s3dbNamespaces'][$qname_info['notes']] = $item_id;
								
								$s3qlS=compact('user_id','db');
								$s3qlS['from']='statements';
								$s3qlS['where']['item_id']=$item_id;
								$done = S3QLaction($s3qlS);
								if(!empty($done)) {
									foreach ($done as $stat_info) {
										$data[$item_id]['item_id'] = $item_id;
										$data[$item_id][$stat_info['object']] = $stat_info['value'];	
									}
								}
							}
						}
					}
					$cols = array('item_id','qname','url');
					$data = trim_query_results($s3ql['where'],$data,$cols);
					if($format=='array') {
						return ($data);
					} else {
						file_put_contents(S3DB_SERVER_ROOT.'/tmp/dict', serialize($Dict));
						$z = compact('data','cols', 'format');
						return(outputFormat($z));
					}
				}
				return(formatReturn($error_code, $msg, $format,""));
				#return ($msg);
				break;
			case 'link':
				if($action=='select') {
					foreach ($Dict as $di=>$Dict_info) {
						if(!is_array($Dict[$di]['items']['s3dbLinks']) || empty($Dict[$di]['items']['s3dbLinks'])) {
							$Dict[$di]['items']['s3dbLinks'] = array();
							#find all the items of s3dbLinks
							$s3qlI=compact('user_id','db');
							$s3qlI['from']='items';
							$s3qlI['where']['collection_id']=$Dict_info['collections']['s3dbLinks'];
							$done = S3QLaction($s3qlI);
								
							if(is_array($done) && !empty($done)) {
								foreach ($done as $link_info) {
									#find the statements
									$item_id = 	$link_info['item_id'];
									$s3qlS=compact('user_id','db');
									$s3qlS['from']='statements';
									$s3qlS['where']['item_id']=$item_id;
									$stats = S3QLaction($s3qlS);
									
									if(!empty($stats)) {
										foreach ($stats as $stat_info) {
											$data[$item_id]['item_id'] = $item_id;
											$data[$item_id][$stat_info['subject'].'|'.$stat_info['verb'].'|'.$stat_info['object']] = $stat_info['value'];	
										}
									}
								}
							}
						} else {
							$data =  $Dict[$di]['items']['s3dbLinks'];
						}
					}
					
					#for this purpose, concatenate the namespace with the term
					$cols = array('link_id','uid','relation','value');
					if(is_array($data)) {
						foreach ($data as $iid=>$info) {
							if($info['s3dbLinks|makesUseOf|s3dbNamespaces'] && $info['s3dbLinks|makesUseOf|term']) {
								$data[$iid]['relation'] =  array_search($info['s3dbLinks|makesUseOf|s3dbNamespaces'], $Dict_info['items']['s3dbNamespaces']).':'.$info['s3dbLinks|makesUseOf|term'];
							} else {
								$data[$iid]['relation'] = $info['s3dbLinks|makesUseOf|relation'];
							}
							if($info['s3dbLinks|hasValue|s3dbNamespaces'] && $info['s3dbLinks|hasValue|term']) {
								$data[$iid]['value'] =  array_search($info['s3dbLinks|hasValue|s3dbNamespaces'], $Dict_info['items']['s3dbNamespaces']).':'.$info['s3dbLinks|hasValue|term'];
							} else {
								$data[$iid]['value'] = 	$info['s3dbLinks|hasValue|value'];
							}
							$data[$iid]['link_id'] = $data[$iid]['item_id'];
							$data[$iid]['uid'] = $info['s3dbLinks|refersToUid|entity_id'];
							if($data[$iid]['uid']=='') {
								$data[$iid]['uid'] = $info['s3dbLinks|refersToUid|uid'];
							}
							$Dict[$di]['items']['s3dbLinks'][$data[$iid]['link_id']]=$data[$iid];
						}
						$data = trim_query_results($s3ql['where'],$data,$cols);
						
						##leavon only the interesting cols
						if($s3ql['select']!=""  && $s3ql['select']!="*"){
							$interesting = explode(",",str_replace(" ","",$s3ql['select']));
						} else {
							$interesting = $cols;
						}
						$data = leave_only_interesting_cols($data,$interesting);
					}
					if(eregi('array',$format)) {
						return ($data);
					} else {
						file_put_contents(S3DB_SERVER_ROOT.'/tmp/dict', serialize($Dict));
						$namespaces_needed=1;
						$z = compact('data','cols', 'format','namespaces_needed','db','user_id');
						return(outputFormat($z));
					}
				} else {
					$Dict[$di]['items']['s3dbLinks'] = array();
					file_put_contents(S3DB_SERVER_ROOT.'/tmp/dict', serialize($Dict));
					$link_id = '';
					list($valid, $msg,$error_code) = check_notation_validity($s3ql['where'],$Dict[$di],$db,$user_id,$action);
					if($valid) {
						$x = call_user_func($action."_not", $s3ql['where'],$Dict[$di],$db,$user_id);
						list($w,$msg) = $x;
						if($w) {
							if($action=='insert') {
								$link_id = array('link_id'=>$msg);
							}
							$msg = 'Link '.ereg_replace('e$','',$action).'ed';
							$error_code = '0';
						} else {
							$error_code = '2';
						}
					} else {
						$error_code = '3';#something missing
					}
				}
				return(formatReturn($error_code, $msg, $format,$link_id));
				break;
		}
	}

	function create_dictionary_project($d,$db,$user_id) {
		#there is only 1 project per deployment that belongs to the Admin
		if(is_file(S3DB_SERVER_ROOT.'/tmp/dict') && $_REQUEST['clean']!='dictionary') {
			$Dict = unserialize(file_get_contents(S3DB_SERVER_ROOT.'/tmp/dict'));
		}
		if(!$Dict) {
			#start by going over this user's project in order to find s3dbDisctionary projects;
			$s3ql=array('user_id'=>'1','db'=>$db);
			$s3ql['from']='project';
			$s3ql['where']['name']=$d['projectName'];
			$done = S3QLaction($s3ql);
	
			if(empty($done)) {
				$s3ql=array('user_id'=>'1','db'=>$db); #Admin creates the project, collections and rules, regular users have permission ynn|yny|ys; public has permission ynn|yny|n
				$s3ql['insert']='project';
				$s3ql['where']['name']=$d['projectName'];
				$s3ql['where']['description']='This is a project, created for s3db management purposes, where attributes of S3DB uid may be extended.';
				$s3ql['format']='php';
				$done = S3QLaction($s3ql);
				$msg=unserialize($done);$msg = $msg[0];
				
				if($msg['project_id']!='') {
					return (false);
				} else {		 #because there can be many, make it already an array even through we are only creating 1
					$Dict[0] = array('project_id'=>$msg['project_id']);
				}
			} else {
				foreach($done as $project_info) {
					$Dict[] = array('project_id'=>$project_info['project_id']);
				}
			}
			foreach ($Dict as $di=>$dict_info) {
				$s3ql=array('user_id'=>'1','db'=>$db);
				$s3ql['from']='collections';
				$s3ql['where']['project_id']=$dict_info['project_id'];
				$done = S3QLaction($s3ql);
				
				#now create 2 collections: s3dbNamespaces; s3dbRelations
				foreach ($d['collections'] as $name) {
					if(!empty($done) && is_array($done)) {
						foreach ($done as $collection_info) {
							if($collection_info['name']==$name) {
								$Dict[$di]['collections'][$name] =  $collection_info['collection_id'];
							}
						}
					}
					if($Dict[$di]['collections'][$name]=="") {
						$s3ql=array('user_id'=>'1','db'=>$db);
						$s3ql['insert']='collection';
						$s3ql['where']['project_id']=$dict_info['project_id'];
						$s3ql['where']['name']=$name;
						$s3ql['format']='php';
						$done = S3QLaction($s3ql);
						$msg=unserialize($done);$msg = $msg[0];
						if($msg['collection_id']=="") {
							$Dict[$di]['collections'][$name] = false;
						} else {
							$Dict[$di]['collections'][$name] =  $collection_info['collection_id'];
						}
					}
				}
				#and now for the rules
				$s3ql=array('user_id'=>'1','db'=>$db);
				$s3ql['from']='rules';
				$s3ql['where']['project_id']=$dict_info['project_id'];
				$done = S3QLaction($s3ql);
			
				foreach ($d['rules'] as $triple) {
					if(is_array($done) && !empty($done)) {
						foreach($done as $rule_info) {
							if($rule_info['subject_id']==$Dict[$di]['collections'][$triple[0]] && $rule_info['verb']==$triple[1] && $rule_info['object']==$triple[2]) {
								$Dict[$di]['rules'][$triple[0].'|'.$triple[1].'|'.$triple[2]] = $rule_info['rule_id'];
							}
						}
					}
					if($Dict[$di]['rules'][$triple[0].'|'.$triple[1].'|'.$triple[2]]=='') {
						$s3ql=array('user_id'=>'1','db'=>$db);
						$s3ql['insert']='rule';
						$s3ql['where']['project_id']=$dict_info['project_id'];
						$s3ql['where']['subject_id']=$Dict[$di]['collections'][$triple[0]];
						$s3ql['where']['verb']=$triple[1];
						if(in_array($triple[2],$d['collections'])) {
							$s3ql['where']['object_id']=$Dict[$di]['collections'][$triple[2]];
						} else {
							$s3ql['where']['object'] = $triple[2];
						}
						$s3ql['format']='php';
						$done = S3QLaction($s3ql);
						$msg=unserialize($done);$msg = $msg[0];
						if($msg['rule_id']=="") {
							$Dict[$di]['rules'][$triple[0].'|'.$triple[1].'|'.$triple[2]] = false;
						} else {
							$Dict[$di]['rules'][$triple[0].'|'.$triple[1].'|'.$triple[2]] = $msg['rule_id'];
						}
					}
				}

				#now let's insert the default namespaces
				$s3ql=array('user_id'=>'1','db'=>$db);
				$s3ql['from']='items';
				$s3ql['where']['collection_id']=$Dict[$di]['collections']['s3dbNamespaces'];
				$done = S3QLaction($s3ql);
				foreach ($d['namespaces'] as $ns=>$url) {
					if(!empty($done)) {
						foreach($done as $item_info){ 
							#if($item_info['notes']==$ns){
							$Dict[$di]['items']['s3dbNamespaces'][$item_info['notes']] = $item_info['item_id'];
							#}
						}
					}
					if($Dict[$di]['items']['s3dbNamespaces'][$ns]=="") {
						$Dict[$di] = insert_new_ns(array('qname'=>$ns,'url'=>$url),$Dict[$di],$db,'1');
						file_put_contents(S3DB_SERVER_ROOT.'/tmp/dict', serialize($Dict));
					}
				}
			}
			file_put_contents(S3DB_SERVER_ROOT.'/tmp/dict',serialize($Dict));
		}
		if($user_id!="1") {
			## add this users to dictionary project when he attempts to use it
			$s3ql=array('user_id'=>'1','db'=>$db);
			$s3ql['insert']='user';
			$s3ql['where']['project_id']=$Dict[0]['project_id'];
			$s3ql['where']['user_id']=$user_id;
			$s3ql['where']['permission_level']='snnynyyss';
			$s3ql['format']='php';
			$done = S3QLaction($s3ql);$msg=unserialize($done);$msg = $msg[0];
		}
		return ($Dict);
	}

	##Section dedicated to namespaces
	function check_ns_validity($params,$Dict,$db,$user_id,$action='insert') {
		if($params['qname']=="" && $action=='insert') {
			return (array(false, 'Qname must not be empty.'));
		}
		if($params['url']=="" && $action=='insert') {
			return (array(false, 'url must not be empty.'));
		}
		#namespace must not already exist
		if($action=='insert' && in_array($params['qname'], array_keys($Dict['items']['s3dbNamespaces'])) ) {
			return (array(false, 'Qname '.$params['qname'].' already exists.'));
		}
		#namespace must exist before it can be deleted
		if(($action=='update' || $action=='delete') && !in_array($params['qname'], array_keys($Dict['items']['s3dbNamespaces']))){
			return (array(false, 'Qname '.$params['qname'].' must exist before '.$action.'.'));
		}
		#url must be a valid resource( This is debateble)
		#if(!@fopen($params['url'],'r') && $action!="delete") {
		#	return (array(false, 'URL '.$params['url'].' is not a valid url.'));
		#}
		#does this exist already? if yes, don't insert it aggain
		return (array(true));
	}

	function insert_new_ns($params,$Dict,$db,$user_id) {		#params should be an array with keys qname and url
		$ns = $params['qname'];
		$url = $params['url'];
		$s3ql=compact('user_id','db');
		$s3ql['insert']='item';
		$s3ql['where']['collection_id']=$Dict['collections']['s3dbNamespaces'];
		$s3ql['where']['notes']=$ns;
		$s3ql['format']='php';
		$done = S3QLaction($s3ql);
		 
		$msg=unserialize($done);$msg = $msg[0];
		if($msg['item_id']!='') {
			$Dict['items']['s3dbNamespaces'][$ns]	= $msg['item_id'];
			$s3ql=compact('user_id','db');
			$s3ql['insert']='statement';
			$s3ql['where']['item_id']=$Dict['items']['s3dbNamespaces'][$ns];
			$s3ql['where']['rule_id']=$Dict['rules']['s3dbNamespaces|uses|qname'];
			$s3ql['where']['value']=$ns;
			$s3ql['format']='php';
			$done = S3QLaction($s3ql);
			$msg=unserialize($done);$msg = $msg[0];
			$Dict['statements'][$ns][] = $msg['statement_id'];
		
			$s3ql=compact('user_id','db');
			$s3ql['insert']='statement';
			$s3ql['where']['item_id']=$Dict['items']['s3dbNamespaces'][$ns];
			$s3ql['where']['rule_id']=$Dict['rules']['s3dbNamespaces|hasReference|url'];
			$s3ql['where']['value']=$url;
			$s3ql['format']='php';
			$done = S3QLaction($s3ql);
			$msg=unserialize($done);$msg = $msg[0];
			$Dict['statements'][$ns][] = $msg['statement_id'];
		}
		return ($Dict);
	}

	function update_ns($params,$Dict,$db,$user_id) {
		#what are the statements?
		$item_id = $Dict['items']['s3dbNamespaces'][$params['qname']];
		$s3ql=compact('user_id','db');
		$s3ql['from']='statements';
		$s3ql['where']['item_id']=$item_id;
		$done = S3QLaction($s3ql);
		foreach ($done as $stat_info) {
			if($stat_info['rule_id']==$Dict['rules']['s3dbNamespaces|hasReference|url']) {
				$stat_id = $stat_info['statement_id'];
			}
		}
		if($stat_id) {
			$s3ql=compact('user_id','db');
			$s3ql['update']='statement';
			$s3ql['where']['statement_id']=$stat_id;
			$s3ql['set']['value']=$params['url'];
			$s3ql['format']='php';
			$done = S3QLaction($s3ql);$msg=unserialize($done);$msg = $msg[0];
		}
		if($msg['error_code']=='0') {
			return (array(true, "URL updated"));
		} else {
			if($stat_id=="") {
				return (array(false,'Could not find statement_id. You may need to ask your adminitrator to change this'));
			} else {
				return (array(false,$msg['message']));
			}
		}
	}

	function delete_ns($params,$Dict,$db,$user_id) {
		$s3ql=compact('user_id','db');
		#which one is being deleted?
		$item_id = $Dict['items']['s3dbNamespaces'][$params['qname']];
		$s3ql=compact('user_id','db');
		$s3ql['delete']='item';
		$s3ql['where']['item_id']=$item_id;
		$s3ql['flag']='all';
		$s3ql['format']='php';
		$done = S3QLaction($s3ql);$msg=unserialize($done);$msg = $msg[0];

		if($msg['error_code']=='0') {
			$Dict['items']['s3dbNamespaces'] = array_delete($Dict['items']['s3dbNamespaces'], $params['qname']);
			file_put_contents(S3DB_SERVER_ROOT.'/tmp/dict', serialize($Dict['items']['s3dbNamespaces']));
			return (array(true, "Qname deleted"));
		} else {
			return (array(false,$msg['message']));
		}
	}

	##Section dedicated to notations
	function check_notation_validity($params, $Dict,$db,$user_id,$action) {
		#all 3 params must exist
		if($action=='insert' && (!$params['uid'] || !$params['relation'] || !$params['value'])){
			return (array(false,'Both uri, relation and value must be provided to insert a link', '3'));
		}
		#uid must be a valid s3db uri
		if(!ereg('^(D|P|U|G|C|R|I|S)[a-zA-Z0-9]+$', $params['uid'])) {
			if($action=='insert' || ($action=='update' && $params['uid']!='')) {
				return array(false, 'uid must be a valid S3DB uid', '7');
			}
		}
		#relation must contain a namespace existing in the namespaces
		list($ns, $rest) = explode(':',$params['relation']);
		$keys = array_keys($Dict['items']['s3dbNamespaces']);
		if($ns == "" || !in_array(trim($ns),array_keys($Dict['items']['s3dbNamespaces']))) {
			if($action=='insert' || ($action=='update' && $params['relation']!="")) {
				return array(false, 'Relation must refer to an existing notation, such as '.implode(', ',array_keys($Dict['items']['s3dbNamespaces'])).' You may first create the namespace','3');
			}
		}
		#when an update is requested, link_id (or item_id) must be present
		if($params['link_id']=="" && ereg('update|delete',$action)) {
			return (array(false,"Update and delete requires link_id to be provided", '3'));
		}
		#when the value has a namespace, it must be a valid namespace; otherwise, it will be a literal or if the user wishes to use a user defined URL, he may go ahead and do it
		list($ns, $rest) = explode(':',$params['value']);
		if($ns != "" && !in_array($ns,array_keys($Dict['items']['s3dbNamespaces']))) {
			if($action=='insert' || ($action=='update' && $params['value']!="")) {
				return array(false, 'If you wish to use a namespace to define the value uid, please use one of the following (or create a new one using the correct syntax): '.implode(', ',array_filter(array_keys($Dict['items']['s3dbNamespaces']))));
			}
		}
		#link must not already exist, otherwise it will break update and delete
		return (array(true));
	}

	function insert_not($params,$Dict,$db,$user_id) {
		#first insert the item that will be the subject of the new relation unless it already exists
		#use as notes the entity id to create the equivalent item
		$s3ql=compact('user_id','db');
		$s3ql['insert']='item';
		$s3ql['where']['collection_id']=$Dict['collections']['s3dbLinks'];
		$s3ql['where']['notes']=$params['uid'];
		$s3ql['format']='php';
		$done = S3QLaction($s3ql);$msg=unserialize($done);$msg = $msg[0];
	
		if($msg['item_id']) {
			$d['item_id'] =$msg['item_id']; 
			#now insert its statements; 4 stats will be added, one for uri, one for namespace used, one for term used, one for target of the link
			$s3ql=compact('user_id','db');
			$s3ql['insert']='statement';
			$s3ql['where']['item_id']=$d['item_id'];
			$s3ql['where']['rule_id']=$Dict['rules']['s3dbLinks|refersToUid|entity_id'];
			$s3ql['where']['value']=$params['uid'];
			$s3ql['format']='php';
			$done = S3QLaction($s3ql);
			$msg=unserialize($done);$msg = $msg[0];
			if($msg['statement_id']) {
				$d['uid'] = $params['uid'];
			}
			#Both relation and value can have namespace; their rules vary only at the verb
			$verbs = array('relation'=>'makesUseOf','value'=>'hasValue');
			foreach($verbs as $p=>$verb) {
				$ns_id='';$ns='';$term='';
				#list($ns,$term) = explode(':',$params[$p]);
				preg_match('/^([^:]*):(.*)$/', $params[$p], $preg);
				$ns = $preg[1]; $term = $preg[2];
				if($ns) {
					$ns_id =  $Dict['items']['s3dbNamespaces'][$ns];
				} else {
					#this means use wants to specify some other resource. I will not allow this for now. Even if user wants to use some other dictionary, he must create a namespace for it
					$ns = $params[$p];
				}
				if($ns_id) {
					$s3ql=compact('user_id','db');
					$s3ql['insert']='statement';
					$s3ql['where']['item_id']=$d['item_id'];
					$s3ql['where']['rule_id']=$Dict['rules']['s3dbLinks|'.$verb.'|s3dbNamespaces'];
					$s3ql['where']['value']=$ns_id;
					$s3ql['format']='php';
					$done = S3QLaction($s3ql);
					$msg=unserialize($done);$msg = $msg[0];
					if($msg['statement_id']) {
						$d['s3dbLinks|'.$verb.'|s3dbNamespaces'] = $ns_id;
					}
					
					$s3ql=compact('user_id','db');
					$s3ql['insert']='statement';
					$s3ql['where']['item_id']=$d['item_id'];
					$s3ql['where']['rule_id']=$Dict['rules']['s3dbLinks|'.$verb.'|term'];
					$s3ql['where']['value']=$term;
					$s3ql['format']='php';
					$done = S3QLaction($s3ql);
					$msg=unserialize($done);$msg = $msg[0];
					if($msg['statement_id']) {
						$d['s3dbLinks|'.$verb.'|s3dbNamespaces'] = $term;
					}
				} else {
					$s3ql=compact('user_id','db');
					$s3ql['insert']='statement';
					$s3ql['where']['item_id']=$d['item_id'];
					$s3ql['where']['rule_id']=$Dict['rules']['s3dbLinks|'.$verb.'|'.$p];
					$s3ql['where']['value']=$ns;
					$s3ql['format']='php';
					$done = S3QLaction($s3ql);
					$msg=unserialize($done);$msg = $msg[0];
					if($msg['statement_id']) $d['relation'] = $ns;
				}
			}
		}
		return (array(true, $d['item_id']));
	}

	function update_not($params,$Dict,$db,$user_id) {
		#update relies on item_id being provided; so lets look up the stats of the item
		#should update not rely on the fact that there is only 1 triple with a certain structure? 
		$s3ql=compact('user_id','db');
		$s3ql['from']='statements';
		$s3ql['where']['item_id']=$params['link_id'];
		$done = S3QLaction($s3ql);

		#now, does any of this stats have a value in the param that is specified to change?
		#what are the params that the user is trying to change?
		$params=array_delete($params,'link_id');
		$verbs = array('relation'=>'makesUseOf','value'=>'hasValue', 'entity_id'=>'refersToUid');
		if(is_array($params)) {
			foreach($params as $toChange=>$newValue) {
				#translate the params to rule_ids 
				if($toChange=='uid') {
					$rules2change = array('uid'=>$Dict['rules']['s3dbLinks|refersToUid|entity_id']);
				}
				$ns="";	
				if(ereg('relation|value',$toChange)) {
					$rules2change = array('namespace'=>$Dict['rules']['s3dbLinks|'.$verbs[$toChange].'|s3dbNamespaces'],'term'=>$Dict['rules']['s3dbLinks|'.$verbs[$toChange].'|term']);
					list($ns, $rest) = explode(':',$newValue);
				}	
				if(is_array($done)) {
					foreach ($done as $stat_info) {
						foreach ($rules2change as $what=>$rule_id) {
							if($rule_id!="" && $stat_info['rule_id']==$rule_id) {
								#did the value change?
								if($ns)	{
									if($what=='namespace') { $newValue =  $Dict['items']['s3dbNamespaces'][$ns]; }
									if($what=='term') { $newValue =  $rest; }
								}
								if($newValue!=$stat_info['value']) {
									$s3qlS=compact('user_id','db');
									$s3qlS['update']='statement';
									$s3qlS['where']['statement_id']=$stat_info['statement_id'];
									$s3qlS['where']['value']=$newValue;
									$s3qlS['format']='php';
									$out= S3QLaction($s3qlS);
									$msg=unserialize($out);$msg = $msg[0];
									if($msg['error_code']!='0') {
										$error .= $msg['message'];
									}
								}
							}
						}
					}
				}
			}
		}
		if($error=='') {
			return (array(true));
		} else {
			return (array(false, $error));
		}
	}

	function delete_not($params,$Dict,$db,$user_id) {
		#all rightt.. you asked for it
		$s3ql=compact('user_id','db');
		$s3ql['delete']='item';
		$s3ql['where']['item_id']=$params['link_id'];
		$s3ql['flag']='all';
		$s3ql['format']='php';
		$done = S3QLaction($s3ql);
		$msg=unserialize($done);$msg = $msg[0];
		
		if($msg['error_code']=='0') {
			return (array(true));
		} else {
			return (array(false, $msg['message']));
		}
	}

	function trim_query_results($where,$data,$queriable) {
		#now it's time to trim the data according to user requests
		#now discover which authority is being requested
		$newData=array();
		foreach ($data as $tuple) {
			if($where!='') {
				#match the where array keys with the data array keys
				$query=array_intersect(array_keys($where), $queriable);
				foreach ($query as $query_attr) {
					#does it have regular expressions?
					$qval='';
					ereg('(\~|\!|\i )(.*)',$where[$query_attr],$qval);
					if(!$qval) {		#if no reg exp are specified, do a precise match
						if($where[$query_attr]!=$tuple[$query_attr]){
							$tuple=array();
						}
					} elseif($qval[1]=='~') {
						if(!ereg($qval[2], $tuple[$query_attr])) {
							$tuple=array();
						}
					} elseif($qval[1]=='i ') {
						if(!eregi($qval[2],  $tuple[$query_attr])) {
							$tuple=array();
						}
					} elseif($qval[1]=='!') {		#when what is asked for is different, clear if the are equal
						if($qval[2]==$tuple[$query_attr]) {
							$tuple=array();
						}
					}
				}
			}
			$newData[] = $tuple;
		}
		$data=array_values(array_filter($newData));	
		return ($data);
	}

	function leave_only_interesting_cols($data,$interesting) {
		foreach ($data as $d=>$data_values) {
			foreach ($data_values as $k=>$v) {
				if(in_array($k, $interesting)) {
					$new_data_values[$d][$k] = $v;
				}
			} 
			$new_data = $new_data_values;
		}
		return ($new_data);
	}
?>