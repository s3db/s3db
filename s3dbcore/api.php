<?php
	#Helena F Deus (helenadeus@gmail.com)
	#if user is trying to authenticate, one of the options will be query that user item on the users project for alternative authentication

	function apiQuery($s3ql, $user_proj=false) {
		extract($s3ql);	
		#if it does not exist, create it and save it in config.inc.php;
		if(!$user_proj) {
			$user_proj = create_authentication_proj($s3ql['db'],$s3ql['user_id']);
		}
		if(!$user_proj) {
			$msg="A project to manage users has not been created. This project can only be created by the generic Admin users. Please add your Admin key to apilogin.php to create it automatically.";
			return (array(false, formatReturn('5',$msg, $format, '')));
			exit;
		}
		if($s3ql['insert']!='') {
			$target=$s3ql['insert'];
			$action = 'insert';
		}
		if($s3ql['update']!='') {
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
		if($target == 'authorities') { $target='authority'; }
		if($action=='insert') {
			switch($target) {
				case 'authentication':
					#does the user_id specified exist?
					#authentication_id is to always be built from what is provided
					$user2add = ereg_replace('^U','',$s3ql['where']['user_id']);
					
					#validate the authentication inputs
					if($s3ql['where']['authentication_id']=='') {
						if(!$s3ql['where']['authority'] || !$s3ql['where']['username']) {
							$msg= (formatReturn($GLOBALS['error_codes']['something_missing'],'Please provide all the necessary fields. These include either &lt;authentication_id&gt; or &lt;protocol&gt, &lt;authority&gt and &lt;username&gt', $_REQUEST['format'],''));
							return (array(false, $msg));
							exit;
						} else {
							$prot = $s3ql['where']['protocol'];
							$auth = $s3ql['where']['authority'];
							$email = $s3ql['where']['username'];
							$s3ql['where']['authentication_id']= (($prot!='http')?$prot.':':'').$auth.':'.$email;
							$s3ql['where']=array_delete($s3ql['where'],array('protocol','authority','username'));
						}
					}
					if($s3ql['where']['user_id']=='') {
						$s3ql['where']['user_id'] = $user_id;
						$user2add =  $user_id;
						#$msg= (formatReturn($GLOBALS['error_codes']['something_missing'],'Please provide the user_id whose authentication you wish to add.', $format,''));
						#return (array(false, $msg));
						#exit;
					}
					
					#this function will actually validate the authentication feasibility
					list($valid,$msg) = validate_authentication($s3ql,$user_id,$db);
					if($valid) {
						#does the user_id have an item assigned to him already?
						while (!$user_proj['users']['items'][$user2add]['item_id'] && $try<5) {
							$user_proj = insert_authentication_tuple(array('user_proj'=>$user_proj, 'user_id'=>'1','db'=>$db,'s3ql'=>$s3ql));
							#$user_proj = insert_authentication_tuple(compact('user_proj', 'user_id','db','s3ql'));
							$try++;
						}
						if($user_proj['users']['items'][$user2add]['item_id']=='') {
							$msg =(formatReturn($GLOBALS['error_codes']['something_missing'],"Could not create an item for this user.", $format,''));
							return (array(false, $msg));
							exit;
						}
						#now let's create an statement for this authentication. Since these can be many, we run the query either way and let s3ql tell us whether this already exists
						if(!is_array($user_proj[$user2add]['R'.$user_proj['email']['rule_id']])) { 
							$user_proj[$user2add]['R'.$user_proj['email']['rule_id']]=array();
						}
						$s3ql_new=compact('user_id','db');
						$s3ql_new['insert']='statement';
						$s3ql_new['where']['rule_id']=$user_proj['email']['rule_id'];
						$s3ql_new['where']['item_id']=$user_proj['users']['items'][$user2add]['item_id'];
						$s3ql_new['where']['value']=$s3ql['where']['authentication_id'];
						$s3ql_new['format']='php';
						$done = S3QLaction($s3ql_new);
						
						$msg=unserialize($done);$msg = $msg[0];
						
						if($msg['statement_id']) { 
							array_push($user_proj[$user2add]['R'.$user_proj['email']['rule_id']], $s3ql['where']['authentication_id']);
							file_put_contents($GLOBALS['uploads'].'/userManage.s3db', serialize($user_proj));
							$msg1 =  (formatReturn($GLOBALS['error_codes']['success'],"Authentication inserted", $format,array('authentication_id'=>$s3ql['where']['authentication_id'])));
							return (array(false, $msg1));
							exit;
						} elseif($msg['error_code']=='4') {
							$msg1= (formatReturn('4','The provided authentication already exists for this user.', $format,''));
							return (array(false, $msg1));
							exit;
						} elseif($msg['error_code']=='11') {
							$msg1= (formatReturn('7','Invalid authentication format. '.$msg['message'], $format,''));
							return (array(false, $msg1));
							exit;
						} elseif($msg['error_code']=='4') {
							$msg1 = (formatReturn($msg['error_code'],"Authentication already exists.", $format,''));
							return (array(false, $msg1));
							exit;
						} else {
							$msg1 = (formatReturn($msg['error_code'],$msg['message'], $format,''));
							return (array(false, $msg1));
							exit;
						}
					} else {
						return (array(false,$msg));
					}
					break;
				default :
			}
			#if($q_syntax['where']['authority_id']=='')
			#return ($user_proj);
			return (true);
		} elseif($action=='select') {
			#if authentication is being asked for
			switch ($target) {
				case 'authentication':
					##if user id is not indicated in the query, use self. S3DB will take care of permisison management
					if($s3ql['where']['user_id']!='') {
						$user2find = ereg_replace('^U','',$s3ql['where']['user_id']);
						if(!$user_proj[$user2find]['I']) {
							$s3ql_new=compact('user_id','db');
							$s3ql_new['from']='statement';
							$s3ql_new['where']['rule_id']=$user_proj['user_id']['rule_id'];
							$s3ql_new['where']['value']=$user2find;
							$done = S3QLaction($s3ql_new);

							if(is_array($done)) {
								$user_proj[$user2find]['I']=$done[0]['item_id'];
								$user_proj[$user2find]['R'.$user_proj['user_id']['rule_id']]=$done[0]['statement_id'];
							}
						}
						if($user_proj[$user2find]['I']!='') {
							$s3ql_new=compact('user_id','db');						  
							$s3ql_new['from']='statements';
							$s3ql_new['where']['item_id']=$user_proj[$user2find]['I'];
							$s3ql_new['where']['rule_id']=$user_proj['email']['rule_id'];
							$user_authentications[$user2find] = S3QLaction($s3ql_new);
						} else {
							$data=array();
						}
					} else {
						$s3ql_new=compact('user_id','db');						  
						$s3ql_new['from']='statements';
						$s3ql_new['where']['rule_id']=$user_proj['user_id']['rule_id'];
						$users = S3QLaction($s3ql_new);
						foreach ($users as $user_info) {
							$s3ql_new=compact('user_id','db');						  
							$s3ql_new['from']='statements';
							$s3ql_new['where']['item_id']=$user_info['item_id'];
							$s3ql_new['where']['rule_id']=$user_proj['email']['rule_id'];
							$tmp = S3QLaction($s3ql_new);
							$user_authentications[$user_info['value']] = $tmp;
						}
					}
					#to display data, choose the headers
					$headers = array('user_id','authentication_id','created_on');
					if(is_array($user_authentications)) {
						foreach($user_authentications as $user4auth=>$auths) {
							if(!empty($auths)) {
								foreach($auths as $auth_info) {
									$data[] = array('user_id'=>$user4auth, 'authentication_id'=>$auth_info['value'],'created_on'=>$auth_info['created_on']); 
								}
							}
						}
						return (array(true,$data,$headers));
					} else {
						$msg = (formatReturn($GLOBALS['error_codes']['something_missing'],"No authentications were found matching your search criteria!", $_REQUEST['format'],''));
						return (array(false, $msg));
					}
					break;
				case 'authority':
					#what is the collection_id of the collection that holds autohorittier
					$s3qlnew=compact('user_id','db');
					$s3qlnew['from']='items';
					$s3qlnew['where']['collection_id']=$user_proj['authorities']['collection_id'];#ups, just noticed i called it authorities; hehe, i'll leave it :-)
					$authorities = S3QLaction($s3qlnew);
					if(is_array($authorities) && !empty($authorities)) {
						foreach ($authorities as $tmp) {
							$authority_data[$tmp['item_id']]=array();	
						}
					}
				   	if(is_array($authorities) && !empty($authorities)) {
						#now find, for item, for each rule of authorities, the values
						$headers2show[]='item_id';
						foreach($user_proj['authorities']['rules'] as $auth_attr) {
							$s3qlnew=compact('user_id','db');
							$s3qlnew['from']='statements';
							$s3qlnew['where']['rule_id']=$auth_attr;
							$data_values= S3QLaction($s3qlnew);
							   
							#now reorganize them according to item_id
							if(is_array($data_values) && !empty($data_values)) {
								foreach($data_values as $stat) {
									#we expect 1 value per rule per item, but in case there is more, this is the right time to do it :-)
									if(is_array($authority_data[$stat['item_id']])) {
										if(!is_array($authority_data[$stat['item_id']][$auth_attr])) {
											$authority_data[$stat['item_id']][$auth_attr] = array();
										}
										array_push($authority_data[$stat['item_id']][$auth_attr], $stat);
									}
									#now stored header data
									if(!in_array($stat['object'], $headers2show)) {
										$headers2show[$stat['rule_id']]=$stat['object'];  
									}
								}
							}
						}
						$ItemLine = array();
						foreach ($authority_data as $item_id=>$rule_values) {
							$extraItemLine=0;
							$thisItemLine=array();
							$thisItemLine['item_id']=$item_id;
								
							#foreach ($rule_values as $rule_id=>$rule_value_stats) {
							foreach ($headers2show as $rule_id=>$headerName) {
								$rule_value_stats = array();
								if($headerName!='item_id') {
									if($rule_values[$rule_id]!='') {
										$rule_value_stats= $rule_values[$rule_id];
									}
									#every item will have a line. Except if the item has more than  1 statement per headers, in which case it will have as many as the number of stats
									if(count($rule_value_stats)>$extraItemLine) { $extraItemLine=count($rule_value_stats); }
									if($extraItemLine<=1) {
										$thisItemLine[$headers2show[$rule_id]]=$rule_value_stats[0]['value'];
									} 
									#else {
									#	#
									#}
									#$item_data_line[] = 
									#array('item_id'=>$item_id,
									#	  $headers2show
								}
							}
							array_push($ItemLine, $thisItemLine);
						}
						$data = $ItemLine;
						#save it
						$user_proj['authorities']['local_data']=$data;
						file_put_contents($GLOBALS['uploads'].'user_proj', serialize($user_proj));
						#now it's time to trim the data according to user requests
						#now discover which authority is being requested
						$newData=array();
						foreach($data as $tuple) {
							if($s3ql['where']!='') {
								#match the where array keys with the data array keys
								$query=array_intersect(array_keys($s3ql['where']), array_keys($tuple));
								foreach($query as $query_attr) {
									#does it have regular expressions?
									$qval='';
									ereg('(\~|\!|\i )(.*)',$s3ql['where'][$query_attr],$qval);
									if(!$qval) { 		#if no reg exp are specified, do a precise match
										if($s3ql['where'][$query_attr]!=$tuple[$query_attr]) {
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
							#if(eregi('^'.$projAuthority['DisplayLabel'].'$',$authority)){
							#	$reqAuth = $projAuthority;
							#	
							#}
						}
						$data=array_values(array_filter($newData));
						#save it
						$user_proj['authoritities']['local_data']=$data;
						file_put_contents($GLOBALS['uploads'].'user_proj', serialize($user_proj));
						$headers=array_values($headers2show);
						#return (array(true, $data,$headers));	
						#exit;
					}
					#now for each of the items, find the statements associated and create the corresponding array
					break;
				case 'protocol':
					$s3qlnew=compact('user_id','db');
					$s3qlnew['from']='items';
					$s3qlnew['where']['collection_id']=$user_proj['protocols']['collection_id'];
					$done = S3QLaction($s3qlnew);
					$headers=array('item_id','label','created_on');
					if(is_array($done)) {
						foreach ($done as $protocol) {
							$data[] = array('item_id'=>$protocol['item_id'], 'label'=>$protocol['notes'],'created_on'=>$protocol['created_on']); 	
						}
						#return (array(true,$data,$headers));
					}
					break;
				default :
					echo "Funcionality not developed yet.";
					exit;
			}
			#now it's time to trim the data according to user requests
			#now discover which authority is being requested
			$newData=array();
			foreach($data as $tuple) {
				if($s3ql['where']!='') {
					#match the where array keys with the data array keys
					$query=array_intersect(array_keys($s3ql['where']), array_keys($tuple));
					foreach($query as $query_attr) {
						#does it have regular expressions?
						$qval='';
						ereg('(\~|\!|\i )(.*)',$s3ql['where'][$query_attr],$qval);
						if(!$qval) { 		#if no reg exp are specified, do a precise match
							if($s3ql['where'][$query_attr]!=$tuple[$query_attr]) {
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
			if(is_array($data)) {
				return (array(true,$data,$headers));
			}
		} elseif($action=='delete' || $action=='update') {
			#only authentication_id is accepted as a parameter for deletion.
			if($target=='authentication') {
				if($s3ql['where']['authentication_id']=='') {
					$msg= (formatReturn($GLOBALS['error_codes']['something_missing'],'Please provide the value for &lt;authentication_id&gt; to be updated/deleted', $_REQUEST['format'],''));
					return (array(false,$msg));
					exit;
				} elseif($action=='update' && $s3ql['set']['authentication_id']=='') {
					$msg= (formatReturn($GLOBALS['error_codes']['something_missing'],'Please provide the authentication_id to replace.', $_REQUEST['format'],''));
					return (array(false,$msg));
					exit;
				} else {
					#delete an authentication will delete a statement. Which statement_id are we looking for?
					$s3ql_new=compact('user_id','db');
					$s3ql_new['from']='statements';
					$s3ql_new['where']['rule_id']=$user_proj['email']['rule_id'];
					$s3ql_new['where']['value']=$s3ql['where']['authentication_id'];
					$done = S3QLaction($s3ql_new);
					if(!$done[0]['statement_id']) {
						$msg= (formatReturn($GLOBALS['error_codes']['something_missing'],'Authentication '.$s3ql['where']['authentication_id'].' was not found or user does not have permission to delete.', $_REQUEST['format'],''));
						return (array(false,$msg));
						exit;
					} else {
						#we will let s3db permission manageent take care of permission to delete
						$s3ql_new=compact('user_id','db');
						$s3ql_new[$action]='statement';
						$s3ql_new['where']['statement_id']=$done[0]['statement_id'];
						if($action=='update') {
							$s3ql_new['set']['value']=$s3ql['set']['authentication_id'];
						}
						$done = S3QLaction($s3ql_new);
						if($done['error_code']=='0') {
							return (array(true,$done));
						} else {
							return (array(false,$done));
						}
					}
				}
			}
		}
	}
?>