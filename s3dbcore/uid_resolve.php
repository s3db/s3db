<?php
	#uid_resolve checks whether a certain s3db uid is valid and breaks the relevant elements for it to be resolved to a URL
	#Helena F Deus, 280709

	//Uncomment this section for API calls
	/*
	if($_REQUEST['uid']!=""){
		#try these uid "D456P123D789C987I654", "D456|PDDRR123|D789|C987|I654"
		include_once('../config.inc.php');
		include_once('../dbstruct.php');
		include_once('callback.php');
		include_once('display.php');
		$uid_info = uid_resolve($_REQUEST['uid']);
		echo outputFormat(array('data'=>array($uid_info),'cols'=>array('uid','did','origin'), 'format'=>$_REQUEST['format']));
	} elseif($argv!="") {
		include_once('../config.inc.php');
		include_once('../dbstruct.php');
		include_once('callback.php');
		include_once('display.php');
		foreach ($argv as $arg) {
			preg_match_all("/(uid|format)=([^ ]+)/", $arg, $vars);
			if($vars[1][0]=='uid'){
				foreach ($vars[2] as $uid_to_check) {
					$uid_info= uid_resolve($uid_to_check);	
				}
			} elseif ($vars[1][0]=='format') {
				 $format = 	$vars[2][0];
			}
		}
		echo outputFormat(array('data'=>array($uid_info),'cols'=>array('uid','did','origin'), 'format'=> $format));
	}
	*/

	function uid_resolve($complete_uid, $sep='\:') {
		#Valid UID rules
		#	1.  Individual non-D entities must be preceded by a D entity 
		#	2.	D entities must always resolve to a URL
		#	3.	Entities are separated by #
		#	4.	Entities start with D U P C I R S
		#	5.	Entities must appear according to their order in the core model:
		#		a.	P cannot be preceded by C,R,I,S
		#		b.	C cannot be preceded by I,R
		#		c.	R cannot be preceded by S
		#		d.	I cannot be preceded by S
	
		$local_did = (ereg('^D',$GLOBALS['Did'])?$GLOBALS['Did']:'D'.$GLOBALS['Did']);
		$complete_uid=trim($complete_uid);
	
		#Recover id, if letter was appended; the uid portion is only the last portion; if there are any other elements before, the belong to container 
		#preg_match_all('/(#{1}|^)(D|P|U|C|R|I|S|http)([^#]+)/', $complete_uid,$uid_sep);
		#works with  D456#P123#SD789#C000, but not with  D456P123
		#preg_match_all('/(#{0,1}|^)(D|P|U|C|R|I|S|http)([^(D|P|U|C|R|I|S|http|#)]+)/', $complete_uid,$uid_sep);
	
		##must work both with D456#PD123#D789#C987#I789 and D456P123D789C987I789
		preg_match_all('/('.$sep.'[^'.$sep.']+|^[^'.$sep.']+)/', $complete_uid,$uid_sep);
		if(count($uid_sep[1])<=1) {
			preg_match_all('/(http|[DPUCRSI][^DPUCRSI]+)/', $complete_uid,$uid_non_sep);
			if($uid_non_sep[1][0]=='http') {
				//find where http ends by removing all other ids
				$tmpuid = $complete_uid;
				for ($i=1; $i < count($uid_non_sep[1]); $i++) {
					$tmpuid = str_replace($uid_non_sep[1][$i], '', $tmpuid);
				}
				$did_url = $tmpuid;
				$tmpDid = 'Dtmp';
				$new_tmp_uid = str_replace($did_url,$tmpDid,$complete_uid);
				$uid_non_sep[1][0] = $tmpDid;
				$uid_seq = $uid_non_sep;
			} else {
				$uid_seq = $uid_non_sep;
			}
		} else {
			$uid_seq =$uid_sep;
		}
		$uid_info['uid'] = ereg_replace($sep,"",end($uid_seq[1]));
		$uid_info['letter'] = substr($uid_info['uid'], 0,1);
		$uid_info['s3_id'] =  substr($uid_info['uid'], 1,strlen($uid_info['uid']));
	   	
		for($i=count($uid_seq[1])-2; $i >= 0 ; $i--) {
			$this_entity = ereg_replace("^".$sep,"", $uid_seq[1][$i]);
			if($Did!="") {
				##once the Did has been reached (and not until then) continue to gather the rest for the origin of this uid, in case there are any
				$uid_info['origin'] = $uid_seq[1][$i].$uid_info['origin'];
			}
			if(substr($this_entity, 0,1)=='D') {
				#we got it
				if($Did=="") {
					$Did = $this_entity;
				}
			} else {
				$this_entity = ereg_replace('^'.$uid_info['letter'],"",$this_entity);#in case the letter of uid has been used in from of did for permissions table
				if(substr($this_entity, 0,1)=='D') {
					#we got it
					if($Did=="") {
						$Did = $this_entity;
					}
				}
			}
		}
		
		if($Did) {
			if($Did=='Dtmp') {
				$uid_info['did'] = 	$did_url;
			} else {
				$uid_info['did'] = $Did;	
			}
		} else {
			$uid_info['did'] = $local_did;
		}
		if($uid_info['origin']=='') {
			$uid_info['origin'] = $GLOBALS['s3db_info']['deployment']['mothership'];
		}
		$uid_info['condensed'] = $uid_info['did'].stripslashes($sep).$uid_info['uid']; ##consensed is the short format of the uid, composed of a Did and the uid;
		return ($uid_info);
	}
?>