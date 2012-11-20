<?php
	#Sharing is a property of S3DB deployments that enables two way sharing of data. 
	#This includes a few function necessary to make sharing a reality
	function findDidUrl($Did, $db) {
		$sql = "select * from s3db_deployment where deployment_id = '".substr($Did,1, strlen($Did))."'";
		$db->query($sql, __LINE__, __FILE__);
		if($db->next_record()) {
			$did_url = array(
							'url'=>$db->f('url'),
							'checked_on'=>$db->f('checked_on'),
							'checked_valid'=>$db->f('checked_valid'),
							'publickey'=>$db->f('publickey')
						);
		}
		return ($did_url);	
	}

	function updateDidUrl($data, $db) {
		$sql = "update s3db_deployment set checked_on = now(), checked_valid = '".$data['checked_valid']."', url = '".trim($data['url'])."' where deployment_id = '".$data['deployment_id']."'";
		$db->query($sql, __LINE__, __FILE__);
		if($db->next_record()) {
			return True;
		} else {
			return False;
		}
	}

	function insertDidUrl($data, $db) {
		$sql = "insert into s3db_deployment (deployment_id, url, publickey,checked_on, checked_valid) values ('".trim($data['deployment_id'])."', '".trim($data['url'])."', '".trim($data['publicKey'])."', now(), '".$data['checked_valid']."')";
		$db->query($sql, __LINE__, __FILE__);
		$dbdata = get_object_vars($db);
		#echo '<pre>';print_r($dbdata);exit;
		if($dbdata['Errno']=='0') {
			return (True);
		} else {
			return (False);
		}
	}

	function mothershipAskUrl($did, $mothership) {
		if(!$mothership) {
			$mothership = $GLOBALS['s3db_info']['deployment']['mothership'];
		}
		#test mothership
		$mothership = (substr($mothership,strlen($mothership)-1,1)=='/')?$mothership:$mothership.'/';
		$ms_query =	 $mothership.'URI.php?uid='.$did.'&format=php';
		$a = @fopen($ms_query, 'r');
		if($a) {
			$tmp = stream_get_contents($a);
			$ms_data = unserialize($tmp);
			if($ms_data[0]['url']!="") {
				$did_info = $ms_data[0];
			} else {
				return (false);
			}
		} else {
			//if origin is more complex than a simple URL, discover the mothership URL;
			return (false);
		}
		return ($did_info);
	}

	function DidURL($uid_info, $db) {
		if(!is_array($uid_info)) {
			$did = $uid_info;
		} else {
			$did = $uid_info['did'];
		}
		$did_info = findDidUrl($did, $db); #internal - does it exist on inside table?
		if($did_info['url']) {
			$did_is_local = 1;
		} elseif ((strtotime(date('Y-m-d G:i:s'))-strtotime($did_info['checked_valid']))<(24*60*60)) {
			$did_is_recent = 1;
		}
		if(!$did_is_local || !$did_is_recent) {
			//ask the mothership; if origin is not a url, then the url must be retrieved first; how far will this go will depend on user input!
			$GoDeep = $_REQUEST['resolve_level'];$deepLevel = 1;
			while(preg_match('/^D/',$uid_info['origin']) && $deepLevel<=$GoDeep) {
				$ms_resolve = uid_resolve($uid_info['origin']);
				$ms_info = mothershipAskUrl($uid_info['origin'], $ms_resolve['origin']);
				if($ms_info['url']!=='') {
					$uid_info['origin'] = $ms_info['url'];
				}
				$deepLevel++;
			}
			$did_info = mothershipAskUrl($uid_info['did'], $uid_info['origin']);
		} else {
			$did_is_local = true;
		}
		
		##If still no url is found, exit with an error message
		if(!$did_info['url']) {
			$return ="A url could not be resolved for Deployment ".$uid_info['did'];
		} else {
			$did_url = (substr($did_info['url'],strlen($did_info['url'])-1,1)=='/')?$did_info['url']:$did_info['url'].'/';
		}	
		return (array($did_url,$did_info['publicKey']));
	}
?>