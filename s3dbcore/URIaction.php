<?php
	/**
	 * @author Helena F Deus <helenadeus@gmail.com>
	 * @license http://www.gnu.org/copyleft/gpl.html GNU General Public License
	 * @package S3DB http://www.s3db.org
	 */
	
	#this function has no other purpose than to call functions s3info and include_all because was having trouble in doing it all inside s3info
	function URI($uid, $user_id, $db) { 
		#UID AS IT EXISTS IN TABLE. 
		#function URi return the metadata on any s3db element. It is also a way to check if a specific element exists.
		$letter = strtoupper(substr($uid, 0,1));
		$id = ltrim($uid, $letter);
		$core=$GLOBALS['s3codes'][$letter];
		if($uid=='') {
			$letter = 'U';
			$id = $user_id;
		}
		if(!in_array($core, array_keys($GLOBALS['s3ids']))) {
			return ($uid." is not a valid S3DB UID. ");
		}
		#$elements = $S3elements[$letter];#chose the correct element from the letter
		$elements = $core;
		$element_info = s3info($core, $id, $db);
	
		#and finally include things like the class_id of rules, the rules of statements, the permission acl, etc
		if(is_array($element_info)) {
			$element_info = include_all(compact('elements', 'element_info', 'user_id', 'db','key'));
		}	
		return ($element_info);
	}

	function s3info($elements, $id, $db) {
		#s3info is the engine that performs a query in a specific id, given the element were id should be found
		$s3tables = $GLOBALS['s3tables'];
		$s3ids = $GLOBALS['s3ids'];
		$dbstruct = $GLOBALS['dbstruct'];
		$letter = $GLOBALS['s3codesInv'][$elements];
	
		if(!in_array($elements, array_keys($dbstruct))) {		#turn singulars into plurals... 
			$_elements = $elements.'s';
			if (!in_array($_elements, array_keys($dbstruct))) {		#try again
				$_elements = $elements.'es'; #i know, it's a class... damn this hacking :-D
			}
			$elements = $_elements;
		}
		$table = $s3tables[$elements]; #chose the correct table from the element
		$idName = $s3ids[$elements]; #choose the correct primary key from the table
		$Touts = $dbstruct[$elements]; #all the elements that should come in the output that exist in the table. additioanl ouputs will vary depending on the element in question 

		if(ereg('^(P|C|R|S|I)$', $letter)) {
			$query_end .= " and status = 'A'";
		}
		if(ereg('^(U)$',$letter) && $id!='1') {
			#$sql = "select * from s3db_".$table.", s3db_addr where (s3db_account.account_addr_id=s3db_addr.addr_id or (s3db_account.account_addr_id='-10' and s3db_addr.addr_id='1')) and s3db_account.".$idName." = '".$id."'";
			$sql = "select * from s3db_".$table." where s3db_account.".$idName." = '".$id."'";
			#echo $sql;
		} elseif(ereg('^(C)$',$letter)) {
			$sql = "select * from s3db_".$table." where ".$idName." = '".$id."' and iid='0'".$query_end;
		} elseif(ereg('^(I)$',$letter)) {
			$sql = "select * from s3db_".$table." where ".$idName." = '".$id."' and iid='1'".$query_end;
		} elseif(ereg('^(S)$',$letter)) {
			$sql = "select * from s3db_".$table." where ".$idName." = '".$id."'".$query_end;
		#} elseif(ereg('^(D)$',$letter)) {
		#	$sql = "select * from s3db_".$table." where ".$idName." = '".$id."' or ".$idName." = '".$letter.$id."'";
		} else {
			$sql = "select * from s3db_".$table." where ".$idName." = '".$id."' ".$query_end;
		}
		$db->query($sql, __LINE__, __FILE__);
		while($db->next_record()) {
			$resultStr .= "\$data[] = Array(";
			foreach($Touts as $col) {
				$resultStr .= "'".$col."'=>'".addslashes($db->f($col))."'";
				if($col != end($Touts)) {
					$resultStr .= ",";
				}
			}
			$resultStr .= ");";
		}
		eval($resultStr);
		#if nothing was returned, it means uid does not exits
		if(!is_array($data)) {
			return (false);
			#return ($GLOBALS['messages']['something_does_not_exist'].'<message>UID '.$id.' was not found</message>');
			#return formatReturn($GLOBALS['error_codes']['something_does_not_exist'], 'UID '.$id.' was not found', $format,'');
		} else {
			return ($data[0]);
		}
	}

	function include_all($x) {
		extract($x);
	
		#add a few extra variables that will be usefull in the output;
		#x = array('elements'=>, 'element_info'=>, 'user_id'=>, 'db'=>)
		#Example: $data = include_all(compact('elements', 'element_info', 'user_id', 'db'));
		#when there is no resource_class_id, find it from the project where instance was created. WILL ASSUME THAT RESOURCE_CLASS_ID FILLED OUT IS A REQUIREMENT FOR ALL S3DB THAT SHARE RULES 
		if($_REQUEST['project_id']=='') {
			$project_id = $element_info['project_id'];
		} else {
			$project_id = $_REQUEST['project_id'];
		}
		if(!$model) { $model = 'nsy'; }
		if($letter=='') {
			$letter = strtoupper(substr($elements, 0,1));
		}
	
		if(is_array($GLOBALS['s3map'][$GLOBALS['plurals'][$GLOBALS['s3codes'][$letter]]])) {
			foreach($GLOBALS['s3map'][$GLOBALS['plurals'][$GLOBALS['s3codes'][$letter]]] as $replace=>$with) {
				preg_match_all('/\$([A-Za-z0-9_]+)/',$with, $sim);
				if(is_array($sim[1]) && !empty($sim[1])) {
					foreach($sim[1] as $a) {
						if($element_info[$a]) {
							$tmp[] = stripslashes($element_info[$a]);
						}
					}
					$element_info[$replace] = str_replace($sim[0], $tmp, $with);
				} else {
					$element_info[$replace] = $element_info[$with];	
				}
			}
		}
		#if element is a class, return the class id
		if($letter=='D') {
			if($element_info['deployment_id']!="") {
				$element_info['acl'] = ($user_id=='1')?'222':((user_is_admin($user_id, $db))?'212':((user_is_public($user_id, $db))?'210':'211'));
			}
			$element_info['created_by']=$user_id;
			$element_info['description']=$GLOBALS['s3db_info']['server']['site_intro'];
			$element_info['name']=$GLOBALS['s3db_info']['server']['site_title'];
			if($element_info['deployment_id']==$GLOBALS['s3db_info']['deployment']['Did']) {
				$element_info['self']=1;
				$element_info['url'] = S3DB_URI_BASE;
			}
		}
		if($letter=='G') {
			$e = 'groups';
			#$element_info['group_id'] = $element_info['account_id'];
			#$element_info['groupname'] = $element_info['account_uname'];
			#$element_info['acl'] = groupAcl($element_info, $user_id, $db);
			$uid_info = uid($element_info['account_id']);
			$element_info['deployment_id'] =ereg_replace('^D', '', $uid_info['Did']);
			$strictuid=1;$strictsharedwith=1;
			$uid='G'.$element_info['group_id'];
			$shared_with = 'U'.$user_id;
			#$element_info['acl'] = permissionOnResource(compact('user_id', 'shared_with', 'db', 'uid','key','strictsharedwith','strictuid'));
			$element_info['acl'] = groupAcl($element_info, $user_id, $db, $timer);
			if($timer) {
				$timer->setMarker('Included resource information for '.$letter);
			}
		}
		if($letter=='U') {
			if($element_info['account_addr_id']!='') {
				$sql = "select * from s3db_addr where addr_id = '".$element_info['account_addr_id']."'";
				$fields = array('addr1', 'addr2', 'city', 'state', 'postal_code', 'country');
				$db->query($sql);
				while($db->next_record()) {
					for ($i=0; $i < count($fields); $i++) {
						$element_info[$fields[$i]]=$db->f($fields[$i]);
					}
				}
				$element_info=array_delete($element_info,'account_addr_id');
			}
			$element_info['user_id'] = $element_info['account_id'];
			$element_info['username'] = $element_info['account_uname'];
			$element_info['login'] = $element_info['account_lid'];
			$element_info['address']=$element_info['addr1'];
			$uid_info = uid($element_info['account_id']);
			$element_info['deployment_id'] =ereg_replace('^D', '', $uid_info['Did']);
			
			if($user_id!='1' && $element_info['created_by']!=$user_id && $element_info['account_id']!=$user_id) { 		#if user is not seing himself and user is not admin and user was not the creator of element, then hide address, email, phone, etc.
				$keys2Remove = array('account_email'=>'', 'account_phone'=>'', 'addr1'=>'', 'addr2'=>'', 'city'=>'', 'state'=>'', 'postal_code'=>'', 'country'=>'');
				if(is_array($element_info)) {
					$element_info = array_diff_key($element_info, $keys2Remove);
				}
			}
			if($user_id!='1' && $element_info['created_by']!=$user_id && $user_id!=$element_info['account_id']) {
				if(is_array($element_info)) {
					$element_info = array_diff_key($element_info, array('account_type'=>'', 'account_status'=>''));
				}
			} else {
				//if this user has been created with a filter, what is that filter
				$permission_info = array('uid'=>'U'.$element_info['created_by'],'shared_with'=>'U'.$element_info['account_id']);
				$hp = has_permission($permission_info, $db);
				if($hp) {
					$element_info['filter']=$hp;
				}
			}
			if(is_array($element_info)) {
				$element_info = array_diff_key($element_info, array('account_pwd'=>''));
			}
			$user_id_who_asks = $user_id;
			$uid = 'U'.$element_info['user_id'];
			$shared_with = $user_id_who_asks;
			$strictuid=1;$strictsharedwith=1;
			$onPermissions = compact('user_id', 'shared_with', 'db', 'uid','key','strictsharedwith','strictuid');
			if($element_info['acl']=='') {
				$element_info['acl'] = userAcl(compact('key', 'element_info', 'user_id_who_asks', 'db'));
			}
		}
		if($letter=='P') {
			$element_info['name'] = $element_info['project_name'];
			$element_info['description'] = $element_info['project_description'];
			$id = 'P'.$element_info['project_id'];
			$uid = 'P'.$element_info['project_id'];
		}
		if($letter=='C') {
			$element_info['class_id'] = $element_info['resource_id'];
			$element_info['collection_id'] = $element_info['class_id'];
			$element_info['name'] = $element_info['entity'];
			$element_info['description'] = $element_info['notes'];
			#project_id to search for rule_id will be the same from the class
			$uid = 'C'.$element_info['resource_id'];
		}
		#if element is a rule, return the class_id of the subject. If the object is a class, return the object_id... to discuss with jonas
		if ($letter=='R') {
			$uid = 'R'.$element_info['rule_id'];
		}
		#if this is an instance, return the class_id => ASSUMING THAT EVERY S3DB THAT HAS SHARED RULES HAS RESOURCECLASSID IN INSTANCE. 
		if ($letter=='I') {
			if($element_info['resource_class_id']!='') {
				$element_info['class_id'] = $element_info['resource_class_id'];
			}
			$element_info['instance_id'] = $element_info['resource_id'];
			$element_info['item_id'] = $element_info['instance_id'];
			$element_info['collection_id'] = $element_info['class_id'];
			$instance_id = $element_info['instance_id'];
			$uid = 'I'.$element_info['instance_id'];
		}
		if($letter=='S') {
			$uid = 'S'.$element_info['statement_id'];
			$info[$id] = $element_info;
			$statement_id = $element_info['statement_id'];
			$element_info['instance_id'] = $element_info['resource_id'];
			$element_info['item_id']=$element_info['instance_id'];
			$element_info['instance_notes']= $info['I'.$element_info['instance_id']]['notes'];
			if($info['R'.$element_info['rule_id']]=='') {
				$info['R'.$element_info['rule_id']] = s3info('rule', $element_info['rule_id'], $db);
			}
			$element_info['object_notes']= notes($element_info['value'], $db);
			$element_info['project_folder']=$element_info['value'];
			$element_info = include_fileLinks($element_info, $db);
			$element_info['subject'] = $info['R'.$element_info['rule_id']]['subject'];
			$element_info['verb'] = $info['R'.$element_info['rule_id']]['verb'];
			$element_info['object'] = $info['R'.$element_info['rule_id']]['object'];
			$element_info['subject_id'] = $info['R'.$element_info['rule_id']]['subject_id'];
			$element_info['verb_id'] = $info['R'.$element_info['rule_id']]['verb_id'];
			$element_info['object_id'] = $info['R'.$element_info['rule_id']]['object_id'];
		}
		##When the resource is remote_uri, there is no propagation of permission;
		if(!$element_info['remote_uri']) {
			$strictuid=1;$strictsharedwith=1;
			$shared_with = 'U'.$user_id;
			$toFindInfo = $element_info;
			$onPermissions = compact('user_id', 'shared_with', 'db', 'uid','key','strictsharedwith','strictuid','timer','toFindInfo');
			if($element_info['acl']=='') {
				$element_info['acl'] = permission4Resource($onPermissions);
			}
			$element_info['permission_level'] = $element_info['acl'];
			if(!$element_info['effective_permission']) {
				$element_info['effective_permission'] = $element_info['acl'];
			}
			if(!$element_info['assigned_permission']) {
				$pp= array('uid'=>$uid, 'shared_with'=>$shared_with);
				$tmp = has_permission($pp, $db);
				if($tmp) { 
					$element_info['assigned_permission']=$tmp;
				} else {
					$element_info['assigned_permission']='---';
				}
			}
		}
		#Define if ser can view or not view data. View is the first number in the 3d code. 
		$permission2user = permissionModelComp($element_info['permission_level']);
	
		##According to the model, change the values of assigned_permission from prevous versions
		$element_info['assigned_permission'] = str_replace(array('0','1','2'), str_split($model), $element_info['assigned_permission']);
	
		$isOwner = 	($element_info['created_by']==$user_id);
		$element_info['view'] = allowed($permission2user, 0,$isOwner,$state=3, $model);
		$element_info['change'] = allowed($permission2user, 1,$isOwner,$state=3, $model);
		$element_info['propagate'] = allowed($permission2user, 2,$isOwner,$state=3, $model);
		#create the element "delete", in case it is eventually created...For now it is the same as change
		$element_info['delete'] = 	$element_info['change'];
		$element_info['delete_data'] = $element_info['add_data'];
		$element_info['add_data'] = $element_info['propagate'];

		$uid_info = uid_resolve($uid);
		$element_info['uid'] = $uid_info['condensed'];
		if(is_file(S3DB_SERVER_ROOT.'/.htaccess')) {
			$element_info['uri'] = S3DB_URI_BASE.'/'.$element_info['uid'];
		} else {
			$element_info['uri'] = S3DB_URI_BASE.'/URI.php?uid='.$element_info['uid'];
		}
		return ($element_info);
	}

	function remoteURI($uid, $key, $user_id, $db) {
		#function remoteURI performs a call on a remote Did for retrieving information on a specific s3id 
		#syntax: remoteURI($uid, $key, $db)
		if(is_array($uid)) {
			$uid_info = $uid;
		} else {
			$uid_info = uid_resolve($uid);
		}
		$local_user = S3DB_URI_BASE.'/'.'U'.$user_id;
		$letter = letter($uid_info['uid']);
		$uid =  $uid_info['uid'];
	
		if(ereg('^[UGPCRIS]', $letter)) {
			$numeric_id = substr($uid,1,strlen($uid)); #if uid brings a letter, leave just a the id
			$numeric_did = substr($uid_info['did'],1,strlen($uid_info['did']));
		} else {
			$numeric_did = $uid_info['origin']; 
		}
	
		##If Did is not a url, it must be found first
		$a = @fopen($numeric_did,'r');
		if(!$a) {
			list($did_url) = DidURL($uid_info, $db);
		} else {
			$did_url = $numeric_did;
			fclose($a);
		}
	
		#First let's try calling the remote resource without authentication; it might be a public resource
		$did_query = trim($did_url).'URI.php?uid='.$uid.'&format=php';
		$tmpH = @fopen($did_query,'r');
		if(!$tmpH) {
			#could not read or is not an S3DB deployment
		   	$return = "Deployment ".$did_url." does not appear to be a valid url";
		} else {
			$tmpData = stream_get_contents($tmpH);
			$uid_info = unserialize($tmpData);
		   	$uid_info = $uid_info[0];	
		
			##when is a "no permission" error code, tyr again with the key; all others, exit
			if($uid_info['error_code']!='' && $uid_info['error_code']!='5') {
				return $uid_info[0]['message'];
			} elseif($uid_info['error_code']=='5') {
				$did_query .= '&key='.$key.'&user_id='.$local_user;
				$tmpH = @fopen($did_query,'r');
				$tmpData = stream_get_contents($tmpH);
				$uid_info = unserialize($tmpData);
				$uid_info = $uid_info[0];
			}
			$return = $uid_info;
		}
		#now update true url in local
		if(!$did_is_local) {
			insertDidUrl($did_info, $db);
		} elseif(!$did_is_recent) {
			#if check was not valid, do not update that field
			if($tmpH) { $did_info['checked_valid'] = date('Y-m-d G:i:s'); }
			updateDidUrl($did_info, $db);
		}
		return ($return);	
	}

	function remoteURIOLD($uid, $key, $user_id, $db) {
		#function remoteURI performs a call on a remote Did for retrieving information on a specific s3id 
		#syntax: remoteURI($uid, $key, $db)
		#uid should be a concatenation of Did and user_id. Did is either a URL or an alphanumeric string that can be called on mothership
		#find this user's id
		#$local_user = $GLOBALS['Did'].'/'.'U'.$user_id;
	
		#$myip = captureIp();
		#$myip = ($myip!='')?$myip:$_SERVER['SERVER_NAME'];
		#$local_user = (($_SERVER['HTTPS']!='')?'https://':'http://'.$myip.'/'.strtok($_SERVER['PHP_SELF'], '/')).'/'.'U'.$user_id;
		#test Did. Is it a url? or a way to find a url?
		#ereg('(.*)(/|_)(D|U|G|P|C|R|I|S)([0-9]+$)', $uid, $out);
		#ereg('(D(.*)|http://(.*)|https://(.*))(_|/)(U|G|P|C|R|I|S)([0-9]+$|D|http://|https://)', $uid, $out);
		$local_user = S3DB_URI_BASE.'/'.'U'.$user_id;
		$uid_info=uid($uid);
		
		$letter = substr($uid_info['uid'],0,1);
		if(ereg('^(U|G|P|C|R|I|S)', $uid)) {
			$uid = substr($uid,1,strlen($uid)); #if uid brings a letter, leave just a the id
			$Did = substr($uid_info['Did'],1,strlen($uid_info['Did']));
		} else {
			$Did = $uid_info['Did'];
		}
		$remoteId = $uid_info['uid'];
		
		#test Did. if is not url, must find url first
		#First let's try calling the remote resource without authientication; it might be a public resource
		ereg('^(D|http.*)/(D|P|C|R|I|S|G|U)([0-9]+)', $uid, $uid_in_remote);
		
		$did_call = $Did.'/URI.php?uid='.$uid_in_remote[2].$uid_in_remote[3].'&format=php';
		$did_data = stream_get_contents(@fopen($did_call,'r'));
		$msg=unserialize($did_data);$msg = $msg[0];
		#$msg=html2cell($did_data);$msg = $msg[2];
		if($msg['uri']!='') { 		#Good, it's a public resource
			return ($msg);
		}
		$did_call = $Did.'/URI.php?key='.$key.'&user_id='.$local_user.'&uid='.$uid_in_remote[2].$uid_in_remote[3];
		$did_data = stream_get_contents(@fopen($did_call,'r'));
		
		if($did_data=='') {		#find $Did url , #is it registered in my internal table?
			$did_url = findDidUrl($Did, $db); #internal - does it exist on inside table?
			$dateDiff_min= (strtotime(date('Y-m-d H:i:s'))-strtotime($did_url['checked_valid']))/60;
					
			#did_url empty? Mothership working?#checked no longer than an hour?
			if(empty($did_url['url']) || $dateDiff_min>60) {
				$mothership = ($uid_info['MS']!='')?$uid_info['MS']:$GLOBALS['s3db_info']['deployment']['mothership'];
				#because s3db.org is under sourceforge, find the real url of that mother ship first.

				if(ereg('http://s3db.org|http://www.s3db.org', $mothership)) {
					if(http_test_existance('http://s3db.org/ms.txt')) {
						$handle = fopen ('http://s3db.org/ms.txt', 'rb');
						$real_ms = stream_get_contents($handle);
						fclose($handle);
					} else {
						$real_ms = 'http://s3db.virtual.vps-host.net/central/';
					}
					if(ereg('frameset', $real_ms)) {
						ereg('src="(http.*" )', $real_ms, $out);	
						if(http_test_existance(trim($out[1],"\" "))) {
							$mothership = fread(fopen(trim($out[1],"\" "), 'r'),'100');
						}
					}
				}
				if(http_test_existance($mothership)) {
					#call mothership, find true url
					$true_url = fread(fopen($mothership.'/s3rl.php?Did='.$Did,'r'), '100000');
					if(!empty($true_url)) {
						$data = html2cell($true_url);
					}
					$data[2]['deployment_id']=substr($Did, 1,strlen($Did));
					if(http_test_existance(trim($data[2]['url']))) {
						$data[2]['checked_valid']=date('Y-m-d H:i:s');
					} else {
						$data[2]['checked_valid']='';
					}
					#now update true url in local
					if(empty($did_url)) {
						insertDidUrl($data[2], $db);
					} else {
						updateDidUrl($data[2], $db);
					}
					#and define the variable
					$url = trim($data[2]['url']);
				} else {		#motherhsips seems to be down... try asking the url that gave the this uid for a URL.
					#need the url from the deployment where this ID is being shared from.
				}
			} else {
				$url = trim($did_url['url']);
			}
		} else {
			$url = $Did;
		}
		#build the call url
		$url=(substr($url,-1)=='/')?$url:$url.'/';
		$key=($key!='')?$key:get_user_key($user_id, $db);
		$url2call = $url.'URI.php?uid='.$remoteId.'&key='.$key.'&user_id='.$local_user;
		if(!http_test_existance($url2call)) {
			return $GLOBALS['messages']['something_does_not_exist'].'<message>'.$remoteId.' does not appear to be a valid remote resource</message>';
		}
		$data = array('uid'=>$remoteId, 'key'=>$key, 'user_id'=>$local_user);

		#now try to access it. I am assuming user already has access in the remote resource
		$h=fopen($url2call, 'r');
		$urldata =	fread($h, '10000');
		
		if($urldata=='') {
			return "could not find user on the url provided";
		} else {
			#now, which part of the data am I waiting? what element is this?
			#$relevant_fields = $GLOBALS['dbstruct'][$GLOBALS['s3codes'][substr($remoteId, 0,1)]];
			$element = $GLOBALS['s3codes'][substr($remoteId, 0,1)];
			$id_name = $GLOBALS['s3ids'][$element];
			
			#some remote header require translation
			$remote_resource_names = array(
										'created_by'=>'user_id', 
										'project_owner'=>'user_id',
										'resource_id'=>(ereg('I|S', substr($remoteId, 0,1)))?'instance_id':'class_id',
										'subject_id'=>'class_id',
										'object_id'=>'class_id',
										'verb_id'=>'instance_id'
									);
			$data = html2cell($urldata);
			if(is_array($data)) {
				$relevant_fields = $data[1];
				$data = $data[2];
				$relevant_data = array_intersect_key($data, array_flip($relevant_fields));

				#whatever points to resources must come with the remote ID
				foreach ($relevant_data as $fieldName=>$fieldData) {
					if(in_array($fieldName, array_keys($remote_resource_names)) || ereg('_id$', $fieldName) && !ereg('http://|https://|_', $fieldData)) {
						$uidLetter = ($remote_resource_names[$fieldName]!='')?strtoupper(substr($remote_resource_names[$fieldName], 0,1)):strtoupper(substr($fieldName, 0,1));
						if($fieldData!='') {
							$DidData[$fieldName] = $Did.'/'.$uidLetter.$fieldData;
						}
					} else {
						$DidData[$fieldName] = $fieldData;
					}
				}

				#translate old acl into new permission_levels
				if($DidData['acl']!='' && strlen($DidData['acl'])=='1') {
					$DidData['acl']=($DidData['acl']=='3')?'222':(($DidData['acl']=='2' && ereg('I|S', $letter)?'222':((($DidData['acl']=='2' && ereg('P|C|R', $letter)?'202':(($DidData['acl']=='1' && ereg('P|C|R', $letter)?'201':(($DidData['acl']=='1' && ereg('I|S', $letter)?'211':(($DidData['acl']=='0')?'000':'000'))))))))));
				}
				#return the original uid to the apporpriate id_name
				$DidData[$id_name]=$uid;
				
				#figure out if user also has local permission on this resource
				$info = $DidData;
				$id=$letter.$uid;
				$P=permissionOnResource(compact('info', 'key', 'user_id', 'db', 'id'));
				#given permission on 2 deploykents, (local+rmote), find which one user has the most permission
				
				if($P!='' && $DidData['acl']!='') {
					$view = max(array(substr($DidData['acl'],0,1), substr($P,0,1)));
					$change = max(array(substr($DidData['acl'],1,1), substr($P,1,1)));
					$add_data = max(array(substr($DidData['acl'],2,1), substr($P,2,1)));
					$DidData['acl']	=$view.$change.$add_data;
				} else {
					$DidData['acl'] = ($P!='')?$P:$DidData['acl'];
				}
				$element_info = $DidData;
				#Define if ser can view or not view data. View is the first number in the 3 d code. It ranges from 0 to 2
				if (ereg('^2', $element_info['acl']) || (ereg('^1', $element_info['acl']) && $element_info['created_by']==$user_id)) {		#2 means user can view anything associated with this resource (downstream). 1 means he can see, as long as resource was created by himself
					$element_info['view'] = '1';#yes, access is granted.
				} else {
					$element_info['view'] = '0';#no, sorry :-(
				}

				#Decide if user can change (update) or not change data on resource
				$change_digit = substr($element_info['acl'], 1, strlen($element_info['acl']));#it is the second digit who specifies this
				if(ereg('^2', $change_digit) || (ereg('^1', $change_digit) && $element_info['created_by']==$user_id)) {
					$element_info['change'] = '1';#yes, you can change it... or update it...
				} else {
					$element_info['change'] = '0';#nope.
				}
			
				#can user insert data in this resource? Information is in the very last digit. In case it only has 2 digits, reading th last digit will work too because it propagates
				if(ereg('2$', $element_info['acl']) || (ereg('1$', $element_info['acl']) && $element_info['created_by']==$user_id)) {
					$element_info['add_data'] = '1';
				} else {
					$element_info['add_data'] = '0';
				}
			
				#create the element "delete", in case it is eventually created...For now it is the same as change
				$element_info['delete'] = 	$element_info['change'];
				$element_info['delete_data'] = $element_info['add_data'];
				return ($element_info);
			} else {
				#return ($GLOBALS['messages']['something_went_wrong'].'<message> Deployment '.$Did.' responded: '.$urldata.'</message>');
				return formatReturn($GLOBALS['error_codes']['no_results'], 'Deployment '.$Did.' responded: '.$urldata, $format,'');
			}
		}
	}

	function URIinfo($uid, $user_id, $key, $db,$timer=array()) {
		#$uid_info = uid($uid);
		$uid_info = uid_resolve($uid);
		$uid_info['Did'] = substr($uid_info['did'], 1, strlen($uid_info['did']));//just to avoid breaking the code, Did is going to be deploymentID withouth the "D" at the beginning and did with the "D"
		if(!$uid_info['s3_id'] || !$uid_info['letter']) {
			//this is not a valid id!
			return (false);	
		}
		$element = $GLOBALS['s3codes'][$uid_info['letter']];

		//if element is a deployment, then a local representation is possible (cached), in all other cases only look for local info if there is no information on the deployment (would waste time otherwise). 
		if(preg_replace('/^D/', '', $uid_info['Did'])==preg_replace('/^D/', '', $GLOBALS['Did'])  || $uid_info['Did']==S3DB_URI_BASE || $uid_info['letter']=='D') {
			$local_info = s3info($element,$uid_info['s3_id'], $db);
		}
		if($uid_info['uid']=='D'.$GLOBALS['Did']) {
			#this is the local deployment; data is collected from config.inc
		    $local_info = array(
		    				'mothership'=>$GLOBALS['s3db_info']['deployment']['mothership'],			
							'deployment_id'=>$GLOBALS['s3db_info']['deployment']['Did'],
							'self'=>'1' ,
							'description'=>$GLOBALS['s3db_info']['server']['site_intro'],
							'url'=>S3DB_URI_BASE, 
							'message'=>'Successfully connected to deployment '.$GLOBALS['s3db_info']['deployment']['Did'].'. Please provice a key to query the data (for example: '.(($_SERVER['https']=='on')?'https://':'http://').$def.S3DB_URI_BASE.'/URI.php?key=xxxxxxxx. For syntax specification and instructions refer to http://s3db.org/',
							'publickey'=>$GLOBALS['s3db_info']['deployment']['public_key'],
							'checked_on'=>date('Y-m-d G:i:s'),
							'id'=>$GLOBALS['s3db_info']['deployment']['Did'],
							'label'=>$GLOBALS['s3db_info']['deployment']['name'],
							'creator'=>1,
							'created'=>''
		    			);
		}
		if(is_array($local_info) && !empty($local_info)) {
			$uid_info['Did'] = $GLOBALS['Did'];
			$uid_info['uid'] = $uid;
			$element_info = $local_info;
			$letter = strtoupper(substr($element,0,1));
			$info = include_all(compact('elements', 'letter','element_info', 'user_id', 'db','key','timer'));
			$info['remote_uri']=0;
		}
		if($uid_info['Did']==$GLOBALS['Did'] || $uid_info['Did']==S3DB_URI_BASE) {
			if(is_array($local_info) && !empty($local_info)) {
				$uid = str_replace(S3DB_URI_BASE.'/', '', $uid_info['uid']);
				$uid_info['Did'] = $GLOBALS['Did'];
				$uid_info['uid'] = $uid;
				
				#$element_info = s3info($element, ereg_replace('^'.letter($uid), '', $uid), $db);;
				$element_info = $local_info;
				#$letter = strtoupper(substr($element,0,1));
				$letter = $uid_info['letter'];
				$info = include_all(compact('elements', 'letter','element_info', 'user_id', 'db','key'));
				$info['remote_uri']=0;
			} else {
				$info=false;
			}
			#$info = URI($uid_info['uid'], $user_id, $db);
			#$info['remote_uri']=0;
		} else {
			$key=($key!='')?$key:get_user_key($user_id, $db);
			#$info = remoteURI($letter.$uid_info['uid'], $key, $user_id, $db);
			$info = remoteURI($uid, $key, $user_id, $db);
			$info['remote_uri']=1;
			if(is_array($info)) {
				##foreach remote core id, re-reference all uids - they must be reference to remote uid
				foreach($info as $uidname=>$uidval) {
					if(ereg('_id$', $uidname)) {
						 $search = array_search($uidname, $GLOBALS['s3map'][$GLOBALS['plurals'][$element]]);
						 if($search) { 
						 	$newuidname = $search; 
						 } else { 
						 	$newuidname = $uidname; 
						 }
						 $info[$uidname] =   $uid_info['did'].'|'.strtoupper(substr($newuidname, 0,1)).$uidval;
					} elseif ($uidname=='created_by') {
						$info['created_by'] = $uid_info['did'].'|U'.$info['created_by'];
					}
				}
				$element_info = $info;
				$letter = $uid_info['letter'];
				$info = include_all(compact('elements', 'letter','element_info', 'user_id', 'db','key','timer'));
			}
			if(!is_array($info) || empty($info)) {
				$info = URI($uid, $user_id, $db);	
			}
			if(!is_array($info)) {
				$info=false;
			}
		}
		return ($info);
	}
?>