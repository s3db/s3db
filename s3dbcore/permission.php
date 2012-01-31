<?php
/**
	
	* @author Helena F Deus <helenadeus@gmail.com>
	* @license http://www.gnu.org/copyleft/gpl.html GNU General Public License
	* @package S3DB http://www.s3db.org
*/
#permission.php is part of the s3db library and contains functions that are used to propagate user permissions
#Helena F Deus



function remotePermissions($Z)
	{	extract($Z);
		
		
		if(!is_file($GLOBALS['uploads'].'/permissions'.$shared_with_user.'.sql') &&		!is_file($GLOBALS['uploads'].'/permissions.sql'))
		{	
			$queryMethod = 'a';
			
		}
		else {
		
		$queryMethod = file_get_contents($GLOBALS['uploads'].'/queryMethod');	
		}

		if(!$queryMethod)
			$queryMethod = 'a';
		
		
		if(!is_array($uidQuery['array_ids']))
		{
		return (array());
		}
		
		$shared = array_keys($uidQuery['array_ids']);
		
		if($timer) $timer->setMarker('Checking remote permissions');

		$queryMethod='a';

		
		$sql = "select ".$GLOBALS['s3ids'][$GLOBALS['s3codes'][$letter]]." from s3db_".$GLOBALS['s3tables'][$GLOBALS['s3codes'][$letter]];

		if($shared_with_query){
		foreach ($shared_with_query as $shared_with_id) {
		$swLetter = letter($shared_with_id);
		$swUID = eregi_replace('^'.$swLetter, '',$shared_with_id); 
		
		if($moreSql=='')
			$moreSql .= " where ";
		else 
			$moreSql .= " and ";
		
		$moreSql .= $GLOBALS['s3ids'][$GLOBALS['s3codes'][$swLetter]]." = '".$swUID."'";
		}
		$sql .= $moreSql;
		}
		
		$db->query($sql,__LINE__,__FILE__);

		while($db->next_record())
			{
			
			$local_uid[] =$db->f($GLOBALS['s3ids'][$GLOBALS['s3codes'][$letter]]);
			
			}
		
		##Now intersect local_uid with shared. The resulting uid will be the remote
		if(is_array($local_uid) && is_array($shared))
		$remoteUID = array_diff($shared, $local_uid);
		else $remoteUID =array();
		#$remoteUID = array_diff($shared, $local_uid);
		
		##Sometimes numeric ids end up in this result. Those the do have url are remote; other are local but without being native to any one resource
		foreach ($remoteUID as $tmp=>$remote_id) {
			if(is_numeric($remote_id))
				{$local_not_native[] = $remote_id;
				$remoteUID[$tmp]='';
				}
		}
		
		$remoteUID = array_filter($remoteUID);
		
		return (array($remoteUID,$local_not_native));
		
		
	}


function permission4resource($P)
{
	extract($P);
	if($P['shared_with']=='') $P['shared_with'] = 'U'.$P['user_id'];
	if($P['uid']=='') $P['uid']=$P['id'];
	
	if($P['shared_with']=='U1') 
	
	{return ('YYY');}

	
	
	$toFind = $P['uid'];
	$shared_with_user = $P['shared_with'];
	#$Z = compact('toFind', 'shared_with_user','user_id', 'db','uidQuery', 'timer');
	$Z = compact('toFind', 'shared_with_user','user_id', 'db','uidQuery', 'timer','toFindInfo');
	
	$results = permissionPropagation($Z);
	
	
	
	if($results[$toFind])
	{	##For now, put the result back in the model in which it was found
		
		return ($results[$toFind]);
	}
	else {
		
		return ('nnn');	##Old Mode for now
	}

	
}

	
#NEW CODE FOR PERMISSION PROPAGATION
function user_included_bottom_up_propagation_list($shared_with,$uid,$user_id,$db,$model='nsy')
	{	global $timer;
		#function user_included_bottom_up_propagation_list will retrieve all uid, including those shared by users/groups (broader focus than bottom_up_propaation
	$sql = select(array('shared_with'=>'U','uid'=>$uid,'db'=>$db,'user_id'=>$user_id,'stream'=>'upstream'),false);
	$sql = "select * from s3db_permission where uid = '".$uid."' and ".$sql;
	
	$db->query($sql);
	

	while ($db->next_record()) {
		$shared_with = $db->f('shared_with');
		$uid =  $db->f('uid');	
		$pl = $db->f('permission_level');
		$X[$shared_with] = $pl;
	}
	
	if(is_array($X)){
		foreach ($X as $k=>$x) {
			$X[$k]=str_replace(array('0','1','2'), str_split($model), $x);
		}
	}
	
	
	if($timer) $timer->setMarker('Finished running '.$sql);
	return ($X);
	}
		

function bottom_up_propagation_list($toFind, $db,$X=array(), $Hlist=array(),$toFindInfo=array())
	{
	#this function will build a list where the keys are the parent ids and the values are the ids to which the permission propagates to
	global $timer;

	if(empty($toFindInfo) && $toFind!=letter($toFind)){
		$toFindInfo = s3info($GLOBALS['s3codes'][letter($toFind)], substr($toFind, 1, strlen($toFind)), $db);
		
		if(letter($toFind)=='S'){
			##Find also info on the rule because that is where object_id will be found
			$ruleInfo = s3info('rule',$toFindInfo['rule_id'], $db);
			$borrowed = array('subject','subject_id','verb','verb_id', 'object','object_id');
			foreach ($borrowed as $tmp) {
				$toFindInfo[$tmp] = $ruleInfo[$tmp];	
			}
		}
		if(letter($toFind)=='I'){
			 $toFindInfo['collection_id'] = $toFindInfo['resource_class_id'];
			 $toFindInfo['item_id'] = $toFindInfo['resource_id'];
		}
	}

	$coreLetter=letter($toFind);
	$idvalue=ereg_replace('^'.$coreLetter,'', $toFind);

	if(is_file($Hlist)) {$Hlist = unserialize(file_get_contents($Hlist));}

	#build propagation list screen s3db for the corresponding core element and builds the interaction list from the query result
	$element = $GLOBALS['s3codes'][$coreLetter];
	$table =  $GLOBALS['s3tables'][$GLOBALS['s3codes'][$coreLetter]];
	$specific_id = $GLOBALS['COREletterInv'][$coreLetter];
	$table_id =  $table.'_id';
	$mother_ids = $GLOBALS['inherit'][$specific_id];
	
	if(letter($toFind)=='S' && $toFindInfo['object_id']=='')
		{
		$mother_ids = array('rule_id','item_id');
		}
	if(letter($toFind)=='R' && $toFindInfo['object_id']=='')
		{
		$mother_ids = array('project_id','subject_id','verb_id');
		}

	if(!empty($mother_ids))
	$table_mother_ids = translate_id_to_tables($mother_ids, $element);
	else  $table_mother_ids = array();

	$sql = "select * from s3db_".$table;
  	$connector = " where";
	if($idvalue){
	 $sql .= " ".$connector." ".$table_id." = '".$idvalue."'";
	 $connector = " and";
	}
	if($coreLetter=='I'){
	 $sql .= " ".$connector." iid='1'";
	}
	if($coreLetter=='C'){
	 $sql .= " ".$connector." iid='0'";
	}


	$db->query($sql,__FILE__,__LINE__);
  

	while($db->next_record()){
		$id_value = $db->f($table_id);
		
		$newHlist=array();#newHlist is just a way to distinguish those ids whose parent have aleady been found and those that haven't. It should always be smaller or equal sized to Hlist because it only contains data discovered in the present iteration
		#organize Hlist by parent->children
		if(!empty($mother_ids)){
		$sw_letter='';
		foreach ($table_mother_ids as $i=>$mother) {
			$sw_letter = $GLOBALS['COREletter'][$mother_ids[$i]];
			$sw_value = $db->f($mother);		
			if($mother=='value') 
				{  if(is_uid($sw_value)) $sw_letter='I'; }

			
			
			if($sw_letter!='' && $sw_value){
			$sw = $sw_letter.$sw_value;
			
			$uid = $coreLetter.$id_value;
			if(!is_array($Hlist[$sw])){
				$Hlist[$sw] = array();
			}
			if(!is_array($newHlist[$sw])){
				$newHlist[$sw] = array();
			}
			
			if(!in_array($uid, $Hlist[$sw]))
				{array_push($Hlist[$sw], $uid);
				array_push($newHlist[$sw], $uid);
				}
			}
		}
		}
		
		}
	

	if($timer) $timer->setMarker('owner id found');
	#There is another way to get to any id. The ones we have just found are the direct inheritance. Now we need to find those that were created on the permissions table - that is, the shared ones

	if($toFind!=$coreLetter) $where_str =  "uid='".$toFind."'";
	else $where_str =  "uid ".$GLOBALS['regexp']." '^".$toFind."'";

	$pSql = "select uid,shared_with from s3db_permission where ".$where_str." and id not in (".ereg_replace('\*',$table_id,$sql).")";
	
	$db->query($pSql);



	while($db->next_record()){
		 $sw = $db->f('shared_with');
		 $uid = $db->f('uid');
		 $possible_parents = $GLOBALS['inherit_code'][letter($uid)];
		 
		 if(in_array(letter($sw), $possible_parents)){
			 if(!is_array($Hlist[$sw])){
					$Hlist[$sw] = array();
					$newHlist[$sw] = array();
				}
			
			if(!in_array($uid, $Hlist[$sw]))
			 {array_push($Hlist[$sw], $uid);
			 array_push($newHlist[$sw], $uid);
			 }
		}
	}

	if($timer) $timer->setMarker('shared id found');

	#now lets get all from parent category. If the range of Hlist keys is small, then it makes sense to only query those; otherwise, a query on all parent makes more sense.

	#save the var in disk
	$Hlist_file = $GLOBALS['uploads'].'Hlist'.rand(100,200);
	
	@file_put_contents($Hlist_file,serialize($Hlist));
	@chmod($Hlist_file, 0777);

	if(count($newHlist)>100){ #this is where newHlist is important: we avoind going through items in the list that we went through already
		
		if(!empty($mother_ids)){
		foreach ($mother_ids as $mother_id) {
		$toFindM=letter($mother_id);
		$Hlist = top_down_propagation_list($toFindM,array($mother_id), $db,$Hlist_file,$toFindInfo);	
		
		}
		}
	}
	else {
		
		if(!empty($newHlist) && !empty($mother_ids))
			foreach ($newHlist as $parent_id=>$kids) {
			
			#$Hlist = bottom_up_propagation_list($parent_id, $db,$X,$Hlist_file);	
			$tmpList = bottom_up_propagation_list($parent_id, $db,$X,$Hlist);	
			$Hlist = array_merge($Hlist, $tmpList);
			
		}
	}

	return ($Hlist);
	}



function top_down_propagation_list($toFind, $root, $db, $Hlist=array(), $all_datafile='',$all_sharedfile='',$j=0)
	{ global $timer;
	$n=5;
	#top down propagation list finds the children, and the children's children , of the parents indicated in root. Propagation stops when the core element that is being seeked (toFInd) is reached.
	if(is_file($Hlist)) {$Hlist = unserialize(file_get_contents($Hlist));}
	if(is_file($all_data_file)) {$all_data = unserialize(file_get_contents($all_data_file));unlink($all_data_file);}
	if(is_file($all_sharedfile)) {$all_shared = unserialize(file_get_contents($all_sharedfile));unlink($all_sharedfile);}
	
	
	if(!is_array($root)) $root = array($root);
	$e = letter($toFind);
	$e_upstream = get_open_gates($e);
	
	
	#how many of the root are of the same kind? (that is, if 
	$letters=array();
	foreach ($root as $p) {
		
		$l = letter($p);
		if(empty($letters[$l])){
			$letters[$l]=0;
			$parent_uid[$l] = array();
		}
		$letters[$l] ++; 
		array_push($parent_uid[$l], $p);
	}
	
	
	#for those that have + 10 elements and belong to the $e_upstream (that is, are open gates in the flood), search for every downstream element regardelss of parent id
	
	foreach ($letters as $pl=>$p_times) {
		#these must be declared in order to find the correct parent id
		$p_element = $GLOBALS['s3codes'][$pl];
		$p_element_idname =  $GLOBALS['COREids'][$p_element];
		$next_tables = $GLOBALS['next_element'][$pl];
	    
		if(is_array($next_tables))
		foreach ($next_tables as $tC) {
			#qhery this only if this table has not been queried yet; since one row may contain several parents ((for example, a rule inherits from a project but also form the subject collection
			
			if(in_array($tC, $e_upstream) || $tC==$e){
				if(!is_array($all_data[$tC])){
				
				$element = $GLOBALS['s3codes'][$tC];
				$tn = $GLOBALS['s3tables'][$GLOBALS['s3codes'][$tC]];
				$tn_id = $GLOBALS['s3ids'][$element];
				#beause collection and item are in the same table, this correction is needed; replace collection_id with "resource_class_id" in the query
				$parent_id = ($GLOBALS['s3map'][$GLOBALS['plurals'][$element]][$p_element_idname]!='')?$GLOBALS['s3map'][$GLOBALS['plurals'][$element]][$p_element_idname]:$p_element_idname;
				
				

				#retrieve all elements of this time from the table
				$sql = "select * from s3db_".$tn."";
				$connector = "where";
				if($tC=='I'){
				 $sql .= " ".$connector." iid='1'";
				 $connector = "and";
				}
				if($tC=='C'){
				 $sql .= " ".$connector." iid='0'";
				 $connector = "and";
				}
				if($tC=='R'){
				 $sql .= " ".$connector." object!='UID'";
				 $connector = "and";
				}
				
				
				#if there are very few propagation points from this letter, reduce the query to only those - building Hlist will be much faster this way
				if($p_times<$n){
				
				
					foreach ($parent_uid[$pl] as $tmp) {
					$id_value = ereg_replace(letter($tmp),'',$tmp);
					if($tC=='R' && $pl=='I'){
					#when tC is R, it migrates from C and I, but let's not go through it at this point because it takes too long; query bottom up at the subject_id,verb_id,object_id level may be faster
					#I know something is wrong with this query to reduce the data, but i don't know waht... problem is that the collections that show up here are only those where user has direct assigned permssion, not the ones that migrate from the project
					#$sql .= " ".$connector." ((subject_id = '".$id_value."') or (object_id = '".$id_value."')) ";

					}
					
					else {
					$sql .= " ".$connector." (".$parent_id." = '".$id_value."') ";	
					}
					
					$connector = "or";
					}	
				
				}
				
			

				$db->query($sql);
				if($timer) $timer->setMarker('entity query run: '.$sql);
				while ($db->next_record()) {
					
					$cols = $GLOBALS['dbstruct'][$GLOBALS['plurals'][$element]];
										
					$uid = $tC.$db->f($tn_id);
					
					
					for ($i=0; $i < count($cols); $i++) {
						$all_data[$tC][$uid][$cols[$i]] = $db->f($cols[$i]); 	
					
					}
					
				}
				
				
				
				}
				
				 
				
				#also look for children of this p in the permissions table
				if(!is_array($all_shared[$pl.$tC])){
				
				if($tC=='R' && ($pl=='C' || $pl=='I')) #see explanation for exception above
					{
					#bypass to do bbottom up
					#echo 'ola';exit;
					}
				else {
				$sqlP = "select shared_with,uid from s3db_permission ";
				$connector = "where";
				if($p_times<$n){
					foreach ($parent_uid[$pl] as $tmp) {
					$sqlP .= $connector." (shared_with = '".$tmp."') ";
					$connector = "or";
					}
					$connector = "and";
				}
				else {
				$sqlP .= $connector." shared_with ".$GLOBALS['regexp']." '^".$pl."'";
				$connector = "and";
				}
				$sqlP .= " ".$connector." uid ".$GLOBALS['regexp']." '^".$tC."'";
				$sqlP .= " ".$connector." id not in (".str_replace("*",$tn_id,$sql).")";

				
				$db->query($sqlP);
				if($timer) $timer->setMarker('permission query run: '.$sqlP);
				#Since there can only be 1 uid, 1shared_with per row, we can build Hlist right away
				while ($db->next_record()) {
					$uid = $db->f('uid');
					$sw = $db->f('shared_with');
					
					#save the data found for general queries
					if($p_times>$n){
						$all_shared[$pl.$tC][$sw] = $uid;
					}
					else {
						if(strlen($sw)>1){
						if(!is_array($Hlist[$sw])){	$Hlist[$sw]=array();}
						if(!in_array($uid, $Hlist[$sw])){
						array_push($Hlist[$sw], $uid);
						$moreC[] = $uid;
						}
						}
					}

					

				}
				}
				}
				
				
				#now distribute the all shared across parent/child connectors
				if(is_array($all_shared[$pl.$tC]))
				foreach ($all_shared[$pl.$tC] as $sw=>$uid) {
				
					if(strlen($sw)>=1){
					if(!is_array($Hlist[$sw])){
							$Hlist[$sw]=array();
						}
					if(!in_array($uid, $Hlist[$sw]))
					{
						if(in_array(letter($uid), $e_upstream)){
						array_push($Hlist[$sw], $uid);
						$moreC[] = $uid;
						}
					}
					}
				}
				
				
			#now redistribute all_data according to the parent being explored
			
			
			if(is_array($all_data[$tC]))
			foreach ($all_data[$tC] as $uid=>$element_info) {
				##a little trick to asusme subject-Id and object_id as collections; verb_id as items;
				if($tC=='R'){ 
					if($pl=='C') { $sw = $pl.$element_info['subject_id']; }
					if($pl=='I') { $sw = $pl.$element_info['verb_id']; }
				}
				else {
				$sw = $pl.$element_info[$parent_id];	
				}
				
				#when there is not data for parent (for example, object_id is empty), there is not need to propagate
				if(strlen($sw)>1){
					if(!is_array($Hlist[$sw])){$Hlist[$sw]=array();}
					if(!in_array($uid,$Hlist[$sw])){
						array_push($Hlist[$sw], $uid);
						#is there child data for this element yet?
						#if(!($e=='R' && !ereg('^S',$uid))) #because there can be some many items, and rule query should be fast, will not propagate from I to rule, for now...
						if(in_array(letter($uid), $e_upstream))
						$moreC[] = $uid;
					}
				}
			}
			}
		}
			
		

			#if all entities were queries, put data in a file to save time next time (but not if only a subset was queried => TO BE COMPLETED
			#And now build the seed that will be used to propagate permissions to the next level
			
			if(!empty($moreC)){
			$all_data_file = $GLOBALS['uploads'].'alldata_tmp'.rand(100,200);
			file_put_contents($all_data_file, serialize($all_data));
			if($timer) $timer->setMarker('Saving data in a file.');
			#save the var in disk
			$Hlist_file = $GLOBALS['uploads'].'Hlist'.rand(100,200);
			file_put_contents($Hlist_file,serialize($Hlist));chmod($Hlist_file, 0777);

			#save the shared
			$shared_file = $GLOBALS['uploads'].'shared'.rand(100,200);
			file_put_contents($shared_file,serialize($all_shared));chmod($shared_file, 0777);
			$j++;
			$Hlist = top_down_propagation_list($toFind, $moreC, $db, $Hlist_file, $all_data_file,$shared_file,$j);
			
			}
			
		}
	
	
	
	return ($Hlist);
}


function user_centric_propagation_list($user_id,$db)
	{global $timer;
		$sql = "select uid,shared_with,permission_level from s3db_permission where shared_with = 'U".$user_id."'";		
		$db->query($sql);
		
		while($db->next_record()) {
			$uid = $db->f('uid');
			$pl = $db->f('permission_level');
			$X[$uid] = $pl;
		}
		
		if($timer) $timer->setMarker('Found user centric propagation vector');
		
		return ($X);
	}

function permissionPropagation($Z)
{
	#NEW NEW
	extract($Z);
	
	$model='nsy';
	
	##Seems to be working; requires further testing
	##This vector is an indication of what propagates from the user
	$X = user_centric_propagation_list($user_id,$db);

	##If $X includes roles, percolate the user allowed list to those roles 
	if(is_array($X))
	foreach ($X as $X_uid=>$X_pl) {
		if(substr($X_uid, 0,1)=='U'){
			$role = substr($X_uid, 1, strlen($X_uid));
			$roles[] = role_propagation_list($role,$db,$X_pl);
		}
	}
	
	##Now merge all the roles into X
	if(is_array($roles))
	foreach ($roles as $r=>$role_X) {
		foreach ($role_X as $uid1=>$pl1) {
			if($X[$uid1]){
			$tmp=s3dbMerge(array($X[$uid1], $pl1));
			$X[$uid1] = $tmp;
			}
			else {
			$X[$uid1] = $pl1;	
			}
		}
	}
	
	
	
	
	
	##FIRST: Colect every relation from the permissions table
	
	#According to the amount of data that needs to be discovered to build the matrix, the bottom up, top-down,or middle point approach will be selected.
	
	if($toFind!=letter($toFind)){
	
	$Hlist = bottom_up_propagation_list($toFind,$db,$X); }
	elseif($toFind==letter($toFind)	 && !$shared_with_query){
	
	$X=user_centric_propagation_list($user_id,$db);
	
	
	#Find the initial propagation list by finding all of the resources in the class of the requested resource
	if(is_array($X))
	$starter=array_keys($X);
	
	$Hlist = top_down_propagation_list($toFind,$starter,$db,$toFindInfo);
	
	}
	elseif($shared_with_query){
	
	#when a uid is specified, propagation will only occur form that point onwards
	$Hlist=array();
	foreach ($shared_with_query as $up) {
	
	$Hlist = bottom_up_propagation_list($up	, $db,$X,$Hlist);
	
	}
	 
	$Hlist = top_down_propagation_list($toFind,$shared_with_query,$db,$Hlist);
	
	
	}
	
	#now find the vector that will be used to propagate the permissions: the one captured from downtream finding permissions the user may have;
	
	#if(is_array($Hlist))
	
	
	if(is_array($X))
	{
	#convert pl to the right model
	$model_p = str_split($model);
	foreach ($X as $key_id=>$key_pl) {
		$X[$key_id] = str_ireplace(array('0','1','2'), $model_p, $key_pl);
	}
	
	if(is_array($X) && is_array($Hlist))
	$result = s3dbPercolate($Hlist,$X,$toFind,$result=array(), $u=1,$state=3,$model);
	}
	else {
		$result = $X;
	}
	
	if(is_array($result))
		if($model=='012')
		{	foreach ($result as $a=>$b) {
			$result[$a] = str_ireplace(array('n','s','y'), array('0','1','2'), $b);
			}
		}
		elseif($model=='nsy')
		{foreach ($result as $a=>$b) {
		$result[$a] = str_ireplace(array('0','1','2'), array('n','s','y'), $b);
		}
		}
	
	
	if($timer) $timer->setMarker('Permissions percolated for '.$toFind);;
	
	#$timer->display();	
	 	
	return ($result);
}

function role_propagation_list($role,$db,$filter='NNN',$it=1)
	{
	
	$nxt_X = user_centric_propagation_list($role,$db);
	
	if(is_array($nxt_X) && !empty($nxt_X)){
			foreach ($nxt_X as $nxt_uid=>$nxt_pl) {
				$tmp1=s3dbMerge(array($nxt_pl, $filter));
				$X[$nxt_uid]=$tmp1;
				
				if(substr($nxt_uid,0,1)=='U')	 {
					
					if($role!=$nxt_uid && $it<10){
					$it++;
					
					$upperRole = substr($nxt_uid,1,strlen($nxt_uid));
					
					##Permission has already been merged from the previous; do not need to merge it again
					
					$tmp2=role_propagation_list($upperRole,$db,$nxt_pl,$it);
					
						if(is_array($tmp2)){
						$X=array_merge($X, $tmp2);
						}
						
					}
				}
				
			}
			
			}
	
		
	return ($X);	
	
	}
#ENDS NEW CODE FOR PERMISSION PROPAGATION
function s3dbPercolate1($Net,$X,$toFind,$res=array(), $it=1,$state=3,$model='nsy')
	{global $timer;
	##NEW NEW - Still much slower than the new one 
	##X will contain the states from the previous iteration; results will always contain this iterations (1) and the previous (0)
	//if(empty($results)) $results[0] = $X;
	
	if(is_file($Net)) {	$T = unserialize(file_get_contents($Net));	unlink($Net); $Net= $T;}
	if(is_file($X)) {	$U = unserialize(file_get_contents($X));	unlink($X); $X= $U;}
	if($timer) $timer->setMarker('Getting data net from file');		
	
	#change the state vector according to the nodes that the node being analysed is connected
	#for ($it=1; $it < 100; $it++) {#100 itreations max!
		 #start by creating a state vector equal to the previous;
		# $res = $X;
		 if(is_array($X)){
			foreach ($X as $a=>$b) {
				#recover the ids that are to be matched at the end
				
				if(ereg('^'.$toFind, $a,$m))
				{
				 if($res[$a])
					{
					
					 $res[$a] = s3dbMerge(array($res[$a], $b),$state,$model);
					 
					 }
				 else
					{ $res[$a] = $b;}
				}
			}
		 }
		 
		 if(is_array($Net))	{
		 foreach ($Net as $node=>$permissionMigratesTo) {
			if($timer) $timer->setMarker('Propagating from node '.$node);
			
			##to make this recursion faster, only propagete permission that are relevant for toFind - if, for example, toFind is C then propagating from R is not necessary
			if($X[$node]){
				##perform the operation in the children of the node in question such that the new state can be achieved
				
				$s = s3dbMigrate($X[$node]);
				list($self, $migrated) = $s;
				
				
				##now merge this result with whatever is already in the results
				$teams = array($X[$node], $self);
				$X1[$node] = s3dbMerge($teams);
				
				
				##for each of the children of the node in question
				if(is_array($permissionMigratesTo) && $migrated){
				$hasChild ++;
				foreach ($permissionMigratesTo as $c=>$childNode) {
						
						##need for speed - continue only of this childNode is relevant for the unit toFind; This makes this specefici for s3db and the condition must be removed in order to make it generic
						if($X[$childNode]){
						$teams = array($X[$childNode], $migrated);
						}
						else {
						$teams = array($migrated);	
						}
						
						$res_tmp = s3dbMerge($teams);
						if($res_tmp) $X1[$childNode] = $res_tmp;
					
					}
				
				}
				
				
				}
				else {
					list($self, $migrated) = array(str_repeat('-', $state),str_repeat('-', $state));
				}
				
			}
			
			if(serialize($X1)==serialize($X) || $it==4) {
				
				
				##Filter results by $toFInd to save on CPU later on
				foreach ($X as $uidF=>$itsP) {
				 
				 if(strlen($toFind)==1){
					
					if(substr($uidF, 0, 1)==$toFind){
						$out[$uidF] = $itsP;
					}
				 }
				 else {
					if($toFind==$uidF){
						$out[$uidF] = $itsP;
					}
				 }
				}
				return ($out);
			}
			elseif($hasChild!=0 && $it<=4 && is_array($X1)) {
				$it++;
				
				$T = S3DB_SERVER_ROOT.'/tmp/percolation'.random_string(10);
				if(@file_put_contents($T, serialize($Net))) $Net = $T;
				
				$U = S3DB_SERVER_ROOT.'/tmp/percolation'.random_string(10);
				if(@file_put_contents($U, serialize($X))) $X = $U;
				
				if($timer) $timer->setMarker('Moving towards iteration '.$it);
				
				$res = s3dbPercolate($Net,$X1,$toFind,$X,$it,$state,$model);
				return ($res);
			}
			else {
				return ($res);
			}
		 }
		 else {
			##Filter results by $toFInd to save on CPU later on
				foreach ($X as $uidF=>$itsP) {
				 
				 if(strlen($toFind)==1){
					
					if(substr($uidF, 0, 1)==$toFind){
						$out[$uidF] = $itsP;
					}
				 }
				 else {
					if($toFind==$uidF){
						$out[$uidF] = $itsP;
					}
				 }
				}
				return ($out);
		 }
}



function s3dbPercolate($Tlist,$X,$toFind,$result=array(), $u=1,$state=3,$model='nsy',$timer=array())
	{
	##OLD OLD	
	#Percolate performs the same function as calc, except that it does it with the transverse matrix. That way, all the children's pl may be calculated at once

	global $timer;		
			##Start by reading Tlist from file
			if(is_file($Tlist))
			{
			$T = unserialize(file_get_contents($Tlist));		
			unlink($Tlist);
			$Tlist = $T;
			}

	
	   
		if(is_array($X))
		foreach ($X as $a=>$b) {
			#recover the ids that are to be matched at the end
			
			if(ereg('^'.$toFind, $a,$m))
			{
			 if($result[$a])
				{
				
				 $result[$a] = s3dbMerge(array($result[$a], $b),$state,$model);
				 
				 }
			 else
				{ $result[$a] = $b;}
			}
		}
		
		#to save time, assign local permissions when they exist
		if(is_array($Tlist))
		foreach ($Tlist as $parent=>$children) {
			 #migrate permission from $parent to $children
				if($timer) $timer->setMarker('Propagationg permisions for '.$parent);
				if($X[$parent]){
				
				$s = s3dbMigrate($X[$parent]);
				
				list($x_self,$x_next) = $s;
				
				
				if(is_array($children)){
				$hasChild ++;
				foreach ($children as $child) {
				#$b are the X that propagate towards $a 
				if($x_next)
					{
					//let's merge this child with existing results 
						if(!$X1[$child]){ //child might have 2 parents in the same iteration (for example, one collection shared by 2 P
						$X1[$child] = $x_next;				
						}
						else {
							$X1[$child] =  s3dbMerge(array($X1[$child], $x_next),$state,$model);
						
						}					
					
					}
				}
				
				}
				}
				
		}
		 
		if($hasChild!=0 && $u<=10 && is_array($X1))
			{
			$u++;
			
			##Now put Tlist back in a file so that it saves CPU
			$T = $GLOBALS['s3db_info']['server']['db']['uploads_folder'].$GLOBALS['s3db_info']['server']['db']['uploads_file'].'/Hlist'.random_string(10);
			
			if(!@file_put_contents($T, serialize($Tlist)))
				$T = $Tlist;
			if($timer) $timer->setMarker('Going to iteration '.$u);
			$result = s3dbPercolate($T,$X1,$toFind,$result, $u,$state,$model, $timer);
			}
		
		return ($result);

		
	}

function s3dbMigrate($x, $gen=1, $state=3)
	{
		#s3dbMigrate will break the permission level into states of 3; when the number of digits is 1, repeats tha digit 3 times; when it is 2, repeats the sencond one; when is 3, returns the 3; when is more than 3, it breaks into into the next sequence of 3
		if(!is_string($x))
				{
				echo "Permission must be string";
				exit;
				}
		else {
			$x=ereg_replace(' |\|', '', $x);
		}
		
		if(strlen($x)<=$state) {
				
				$next=permeate($x);
				$self = $next;
				
		}
		else {
				$self = $x;
				$next = substr($x, $state, strlen($x)-1);
				
				if(strlen($next)<=$state) {
				 $next=permeate($next);
				}
		}
				
		
		if($gen>1)
			list($self,$next)=s3dbMigrate($next,$state,$gen-1);
		return (array($self,$next));
	}

function s3dbMerge($teams,$state=3)
	{	global $timer;
		#permissionCompare compares the result from two permissions migrated upstream and selects the result based on the rules:
	# if one is uppercase, one lowercase, the uppercase wins;
	# if both are uppercase, the most restrictive wins
	# if both are lowercase, the least restrictive wins
	# if one migrates, use the other
	# these rules rely on the assumption that A>S>N>-
	# Used for permission model as of 01012009
	#When there are more than 2 teams, the 3 and onward will be compared against the result of team 0 with team 1;
	
	$x = $teams[0];
	$y = $teams[1];

	if(strlen($x)<$state)
		$x = permeate($x,$state);
	if(strlen($y)<$state)
		$y=permeate($y,$state);


	for ($i=0; $i < $state; $i++) {
			$x_[$i] = substr($x, $i, 1);
			$y_[$i] = substr($y, $i, 1);
			
			$upper='';$lower='';
			ereg('([A-Z]+)', $x_[$i].$y_[$i],$upper);
			ereg('([a-z]+)', $x_[$i].$y_[$i],$lower);
				
				switch (strlen($upper[1])){
				case 0:
									
					switch (strlen($lower[1])) {
						case 2:
						# if both are lowercase, the least restrictive wins
						$concatenation = $lower[1]; $priority='generous';
						$z_[$i] = digitDecide($concatenation,$priority);
						
						break;
						case 1:
						# if one migrates, use the other;
						$z_[$i] = $lower[1];
						break;
						
						default:
						#if both migrate, use the migrate sign
						$z_[$i] = $x_[$i];
						break;

						
					}
					break;
				case 1:
					#if one is uppercase, one lowercase, the uppercase wins:
					$z_[$i] = $upper[1];
					break;
				case 2:
					# if both are uppercase, the most restrictive wins
					$concatenation = $upper[1];$priority='restrictive';
					$z_[$i] = digitDecide($concatenation,$priority);
					break;

				}
		
	}

	if(is_array($z_))
	$z = implode("",$z_);


	if(count($teams)>2)
		{
		$newTeams =  array_splice($teams, 2);
		array_push($newTeams , $z);
		$z = s3dbMerge($newTeams,$state);
		
		}
	
	##Added 160609
	##s3dbMerge only takes care of the permissions up to the state; if there are more than those, keep then asociated with the URI
	if(strlen($z)<strlen($x)){
		$z = $z.substr($x, strlen($z), strlen($z));
	}
	if(strlen($z)<strlen($y)){
		$z = $z.substr($y, strlen($z), strlen($z));
	}
	return ($z);
	}

function digitDecide($c,$priority='generous',$model='nsy')
	{
	# Used for permission model as of 01012009
	$literal = 	str_split($model);
	$numeric = 	range(0,3);

	$tmpLit	= str_split($c);
	$str2num =str_replace($literal, $numeric, strtolower($c));
	$tmpNum=str_split($str2num);	
	if($priority=='restrictive')
	$z_ = $tmpLit[array_search(min($tmpNum), $tmpNum)];
	elseif($priority=='generous')
	$z_ = $tmpLit[array_search(max($tmpNum), $tmpNum)];
	return ($z_);
	}
				

function permeate($P,$state=3)
	{
	# Used for permission model as of 01012009
		switch (strlen($P)) {
			case 1:
				$y=str_repeat($P, $state);
					break;
			case 2:
				$y=substr($P, 0,1).str_repeat(substr($P,1,1), $state-1);
			break;
			case 3:
				$y=$P;
			break;
		}
		return ($y);
	}

function permission_level($pl, $uid, $user_id, $db)
	{
		
		if(ereg('([0-2])([0-2])([0-2])',$pl, $dl)){
		$a['view']= ($dl[1]=='2')?1:(($dl[1]=='1' && createdBy($uid,$db)==$user_id)?1:0);
		$a['edit']= ($dl[2]=='2')?1:(($dl[2]=='1' && createdBy($uid,$db)==$user_id)?1:0);
		$a['use']= ($dl[3]=='2')?1:(($dl[3]=='1' && createdBy($uid,$db)==$user_id)?1:0);
		$a['propagate']= $a['use'];
		$a['add_data'] = $a['use'];
		}
		else {
			if(strlen($pl)<2)
				$a['edit'] = 0;
			if(strlen($pl)>1)
				$a['view'] = 0;
		}
		return ($a);
	}

function userAcl($E)
{	
	extract($E);
	#$E must contain at least element_info, user_id_who_asks, and db
	#user acl will depend on user being included in 1 of 3 categories: 
	
	$admins = admins($key, $user_id_who_asks, $db);
	#echo '<pre>';print_r($admins);exit;
	$uid = 'U'.$element_info['account_id'];
	#$uid = $GLOBALS['Did'];
	$shared_with = 'U'.$user_id_who_asks;

	$has_permission = has_permission(compact('uid', 'shared_with'), $db);
	
	if(!$model)
		$model = 'nsy'; 
	$literal = str_split($model);
	$order = range(0,3);

	
	if($user_id_who_asks=='1')
	{
	#return ('222');
	$maxPerm = str_repeat($literal[2],3);
	return ($maxPerm);
	}
	#elseif(in_array($user_id_who_asks, $admins))
	elseif(in_array($user_id_who_asks, $admins) || user_type($user_id_who_asks, $db)=='a')
	{
	if($element_info['account_id']==$user_id_who_asks || $element_info['created_by']==$user_id_who_asks)
		{$maxPerm = str_repeat($literal[2],3);
		return ($maxPerm);
	}
	else {
		$Perm = $literal[2].$literal[1].$literal[2];	   ##212
	
		return ($Perm);
	}
	}
	elseif(user_type($user_id_who_asks, $db)=='p'){
		
		if($element_info['account_type']=='p')
		return ($literal[2].$literal[0].$literal[1]); #201 or yns
		else {
			return ($literal[2].$literal[0].$literal[0]);  #200 or ynn
		}
		}
	elseif(user_type($user_id_who_asks, $db)=='u')
	{	
		if(in_array('U'.$element_info['user_id'], $admins))
		return ($literal[2].$literal[0].$literal[0]);
		else 
		return ($literal[2].$literal[1].$literal[1]);
	}
	
}

function  groupAcl($element_info, $user_id, $db)
{
	$permission_info = array('uid'=>'G'.$element_info['group_id'], 'shared_with'=>'U'.$user_id);
	$has_permission = has_permission($element_info, $db);
	#echo '<pre>ola';print_r($element_info);
	#echo $has_permission;exit;
	if($has_permission!='') return ($has_permission);
	else {
		if(user_is_admin($user_id, $db))
			return ('222'); 
		if($element_info['created_by']==$user_id)
			return ('211');#the owner of the group can view all users, change the ones he creates and add data to the group, but not the users
		if(user_in_group($user_id,$element_info,$user_id, $db))
			return ('211'); #a user in group can add users to group, change none he added and view all users
		if(user_type($user_id, $db)!='p') {
			return ('200');#a user not in group cannot add/change anything, but can view the group
		}
		
			return ('000');
		
	}
}

?>