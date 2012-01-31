<?php
#rdfproject.php parses a project in s3db into n3.
#reads database info from the session or from key
ini_set('display_errors',0);
if($_REQUEST['su3d'])
ini_set('display_errors',1);

if($_SERVER['HTTP_X_FORWARDED_HOST']!='')
			$def = $_SERVER['HTTP_X_FORWARDED_HOST'];
		else 
			$def = $_SERVER['HTTP_HOST'];
			
if(file_exists('config.inc.php'))
	{
		include('config.inc.php');
	}
	else
	{
		

		Header('Location: http://'.$def.'/s3db/');
		exit;
	}

$a = set_time_limit(0);
#ini_set('max_execution_time','30');
ini_set('upload_max_filesize', '128M');
ini_set('post_max_size', '256M');
ini_set('display_errors',0);
ini_set('memory_limit','3000M');

	$key = $_GET['key'];
	if($key=='') $key = $s3ql['key'];
	if($key=='') $key=$argv[1];	
	$file=$_REQUEST['file'];
	if($file=='') $file=$argv[3];
	if($id=='') $id = $argv[4];
	if($argv!=''){
	#whn the script is called via CLI, we need a direc way to accept the inputs. The syntax will be the saem (attr-value pairs)
	$inputsOrder = array('key','file','outputOption','uid');
	
	for ($i=1; $i <count($argv) ; $i++) {
		list($keyWord, $val) = explode('=',$argv[$i]);
		
		$inputs[$keyWord] = $val;
	}
	#$inputs['key'] = $argv[1];
	#$inputs['file'] = $argv[2];
	}
	else {
		$inputs = $_REQUEST;
	}
	#$inputs = ($argv!='')?$argv:$_REQUEST;

$key=$inputs['key'];
include_once('core.header.php');
include('dbstruct.php');


$FinalFfilename = ($inputs['file']!='')?$inputs['file']:$GLOBALS['s3db_info']['server']['db']['uploads_folder'].$GLOBALS['s3db_info']['server']['db']['uploads_file'].'/project'.$project_id.'_requested_'.$user_id.'_'.date('m.d.y-His').'.n3';

#echo $FinalFfilename;exit;
#####
#create a file and start writting to it
$fid = fopen($FinalFfilename, 'a+');
#echo '<pre>';print_r($inputs);echo $user_id;exit;
#echo $fid;exit;
if($user_id!='')
{

#$project_id = $_REQUEST['project_id'];

#$url = ($def=='')?$GLOBALS['s3db_info']['deployment']['url']:S3DB_URI_BASE;
$url = S3DB_URI_BASE;
#start building the string, prefix will be the very fisrt thing shouwing up
$n3 .= sprintf('%s', '@prefix dc: <http://purl.org/dc/elements/1.1/> .'.chr(10));
$n3 .= sprintf('%s', '@prefix dcterms: <http://purl.org/dc/terms/> .'.chr(10));
$n3 .= sprintf('%s', '@prefix rdfs: <http://www.w3.org/2000/01/rdf-schema#> .'.chr(10));
$n3 .= sprintf('%s', '@prefix rdf: <http://www.w3.org/1999/02/22-rdf-syntax-ns#> .'.chr(10));
$n3 .= sprintf('%s', '@prefix owl: <http://www.w3.org/2002/07/owl#> .'.chr(10));
$n3 .= sprintf('%s', '@prefix s3db: <http://www.s3db.org/core#> .'.chr(10));
$n3 .= sprintf('%s', '@prefix s3dbpc: <http://www.s3db.org/permission_codes#> .'.chr(10));
$n3 .= sprintf('%s', '@prefix xsd: <http://www.w3.org/2001/XMLSchema#> .'.chr(10));
$n3 .= sprintf('%s', '@prefix foaf: <http://xmlns.com/foaf/0.1/> .'.chr(10));
$n3 .= sprintf('%s', '@prefix did: <'.$GLOBALS['s3db_info']['deployment']['mothership'].$GLOBALS['Did'].'/> .'.chr(10).chr(10));
$n3 .= sprintf('%s', '@prefix : <'.$url.((substr($url,strlen($url)-1,1)=='/')?'':'/').'> .'.chr(10).chr(10));

$N3coreNames = array('deployment'=>'s3db:s3dbDeployment','project'=>'s3db:s3dbProject', 'collection'=>'s3db:s3dbCollection', 'rule'=>'s3db:s3dbRule', 'item'=>'s3db:s3dbItem', 'statement'=>'s3db:s3dbStatement', 'user'=>'s3db:s3dbUser', 'group'=>'s3db:s3dbGroup');

if(!$inputs['nocore']) {
$core = fread(fopen('core.n3', 'r'), filesize('core.n3'));
$n3 .= sprintf('%s', $core);
#$n3 .= sprintf('%s', 'doc:'.$GLOBALS['Did'].' a s3db:s3dbDeployment .'.chr(10).chr(10));

}
#else {
#	$N3coreNames = array('project'=>'rdfs:Class', 'collection'=>'rdfs:Class', 'rule'=>'rdf:Property', 'item'=>'rdf:Resource', 'statement'=>'rdf:Resource', 'user'=>'rdf:Resource', 'group'=>'rdf:Resource');
#}
#parse the core relatioships


#############################################################################
##define the Classes in the ontology of s3db

$s3Types = array('deployment'=>array('user', 'group', 'project'),
				'group'=>array('user'),
				'project'=>array('collection', 'rule'),
				'collection'=>array('item'), 
				'rule'=>array('statement'));
				
	

##############################################################
#the unique identifier for each table
$s3idNames = array('deployment'=>'deployment_id', 'project'=>'project_id', 'collection'=>'resource_id', 'item'=>'resource_id', 'rule'=>'rule_id', 'statement'=>'statement_id', 'user'=>'user_id', 'group'=>'group_id');

#$coreElements = array_keys($s3Elements);

#now is there any id specified?
$specifiedInput = rootIDinfo($s3idNames, $inputs, $argv, $user_id, $key, $db);
extract($specifiedInput);
$specified_id_code = $GLOBALS['s3codesInv'][$specified_id_type];

if(!$specified_id_info['view'])
	{echo $GLOBALS['messages']['no_permission_message'].'<message>User does not have access in this '.$specified_id_type.'.</message>';
 exit;
 }


$specified_id_info = array_filter($specified_id_info);
$ruid_info=uid($letter.$rootID);
#################################
#Determine what should be output

#Build the ROOT ontology
$verbs=array();

switch ($specified_id_code) {
	case 'D':
	
	if($ruid_info['Did']==$GLOBALS['Did'])
	{$rpre='doc:';$rsuf='';}
	else{ $rpre='<';$sruf='>';}

	$n3 .= sprintf($rpre.$ruid_info['uid'].$rsuf);
	$objectPredicates=array('rdfs:comment'=>(
									$specified_id_info[$COREcomment[$specified_id_type]]!='')?'"'.$specified_id_info[$COREcomment[$specified_id_type]].'"':'',
									#'a'=> $N3coreNames[$specified_id_type],
									'rdfs:label'=>'"'.$specified_id_info[$CORElabel[$specified_id]].'"',
									
									);
		#if(!$inputs['nocore']){
		#	$objectPredicates['a']=$N3coreNames[$specified_id_type];
		#}
break;
	case 'P':
	if($ruid_info['Did']==$GLOBALS['Did']){$pre=':';$suf='';}
	else{ $rpre='<';$rsuf='>';}
	$n3 .= sprintf(n3UID($ruid_info['uid']));
	$objectPredicates=array('rdfs:comment'=>(
									$specified_id_info[$COREcomment[$specified_id_type]]!='')?'"'.$specified_id_info['project_description'].'"':'',
									#'a'=> $N3coreNames[$specified_id_type],
									'rdfs:label'=>'"'.$specified_id_info[$CORElabel[$specified_id]].'"',
									'dcterms:creator'=>n3UID('U'.$specified_id_info['created_by']),
									'dcterms:created'=>'"'.$specified_id_info['created_on'].'"'
									);

					

		break;
	case 'R':
					$n3 .= sprintf(n3UID($ruid_info['uid']));
					$subject_id = n3UID('C'.$specified_id_info['subject_id']);
					
									
					$verb_id = ($specified_id_info['verb_id']=="")?":I".random_string(5):n3UID("I".$specified_id_info['verb_id']);
					#predicates can't be literals, so create a resource for literal verbs.
					#when the verb is not an ID, a random string is generated that will simulate the ID of an instance.
					if(!in_array($specified_id_info['verb'], array_keys($verbs)))
					{
					$addStat .= sprintf($verb_id);
					$addStat .= sprintf(' rdfs:label "'.$specified_id_info['verb'].'" ;').chr(10);
					if(!$inputs['nocore'])
					$addStat .= chr(9).sprintf(' a s3db:s3dbItem ;').chr(10);
					##Find collection of this item and output this information
					$item_info = s3info('item', $specified_id_info['verb_id'], $db);
					if(is_array($item_info))
					$addStat .= chr(9).sprintf(' a '.n3UID("C".$item_info['resource_class_id']).' .').chr(10).chr(10);
					else {#find a collection for the verbs
						$verbCollection = projectVerbClass(array('project_id'=>$specified_id_info['project_id'], 'db'=>$db,'user_id'=>$user_id));
						$addStat .= chr(9).sprintf(' a '.n3UID("C".$verbCollection['resource_id']).' .').chr(10).chr(10);
					}

					$verbs[$specified_id_info['verb']] =$verb_id;
					}
					else {
						$verb_id = $verbs[$specified_id_info['verb']];
												
					}
					
					
					$object_id = ($specified_id_info['object_id']=="")?'"'.$specified_id_info['object'].'"':n3UID("C".$specified_id_info['object_id']);
					
					
				$objectPredicates=array(
								
								'rdfs:label'=>'"'.$specified_id_info['subject'].' '.$specified_id_info['verb'].' '.$specified_id_info['object'].'"',
								#'dc:comment'=>'"'.$specified_id_info['notes'].'"', 
								'rdfs:subClassOf'=>n3UID('P'.$specified_id_info['project_id']), 
								'rdf:subject'=>$subject_id, 
								'rdf:predicate'=>$verb_id,
								'rdf:object'=>$object_id,
								'dcterms:creator'=>n3UID('U'.$specified_id_info['created_by']),
								'dcterms:created'=>'"'.$specified_id_info['created_on'].'"'
								);
				
				##When no core is needed (when the document is not meant to be reloaded, there is no need for 
				
				$addStat .= sprintf($subject_id.' '.$verb_id .' '.$object_id.' .').chr(10);
		break;
	
}

if(!$inputs['nocore']){
			$objectPredicates['a']=$N3coreNames[$specified_id_type];
		}

$objectPredicates=array_filter($objectPredicates);
#echo '<pre>';print_r($objectPredicates);exit;

#echo '<pre>';print_r($s3Types[$specified_id_type]);exit;
foreach ($objectPredicates as $predicate=>$object) {
	$n3 .= chr(9).sprintf($predicate.' '.$object.(($predicate==end(array_keys($objectPredicates)))?' .'.chr(10):' ;')).chr(10);
}

#Export user permissions on object
#retrieve permission info on this URI

if(in_array('permissions', array_keys($inputs))){
$s3ql=compact('user_id','db');
$s3ql['from']='users';
$s3ql['where'][$specified_id]=$specified_id_info[$specified_id];
$users = S3QLaction($s3ql);
$me = $user_info;
$me = include_all(array('elements'=>'users', 'element_info'=>$me, 'user_id'=>$user_id, 'db'=>$db));
$me['permissionOnResource'] = $me['permission_level'];
array_push($users, $me);

$permissions=array_map('grab_permission', $users);
$users=grab_id('user', $users);
$specified_id_info['permissions']=array_combine($users, $permissions);

#echo '<pre>';print_r($specified_id_info['permissions']);
if(is_array($specified_id_info['permissions']))
	
	$n3permissions .= chr(10);sprintf($pre.$uid_info['uid'].$suf).chr(10);
	
	foreach ($specified_id_info['permissions'] as $user_code=>$pcode) {
	
	$n3permissions .= sprintf(n3UID($uid='U'.$user_code).' s3dbpc:VCU'.$pcode.' '.n3UID($ruid_info['uid']).' .').chr(10);
}

}
$n3 .= $n3permissions;
#echo $n3;exit;
fwrite($fid, $n3);

#echo $n3permissions;exit;
#echo '<pre>';print_r($s3Types);exit;
#now for the classes. What are rdfs:classes in an s3db ontology?
#EVERTHING THAT HAS A LABEL IS A CLASS. This includes verbs, object and instances
foreach ($s3Types[$specified_id_type] as $a_class) {
	#each class has a descriptive statement
	
	$a_class_id = $s3idNames[$a_class];
	
	$a_class_letter = strtoupper(substr($a_class,0,1));
	$a_class_type = $GLOBALS['s3codes'][$a_class_letter];
	$s3ql=compact('user_id','db');
	$s3ql['select']='*';
	$s3ql['from']=$GLOBALS['plurals'][$a_class];
	$s3ql['where'][$specified_id]=$specified_id_info[$specified_id];
	
	if(ereg('(rule|statement)', $a_class_type))
		$s3ql['where']['object']="!=UID";

	
	
	#echo '<pre>';print_r($s3ql);
	#exit;
	$subClasses = S3QLaction($s3ql);#find them, output them. 
	#$verbs=array();
	

	if(is_array($subClasses))
	foreach ($subClasses as $subClass_info) {
		
		
		$subClass_info = array_filter($subClass_info);
		#start by saying what sort of s3dbCore id this is.
		
		
		$n3 = getSubClassStats($a_class_letter.$subClass_info[$a_class_id], $subClass_info, $inData, $user_id, $db, $N3coreNames, $inputs); 
		#if($a_class_letter=='R'){
		
		#}
		fwrite($fid, $n3);		
	}
}
fclose($fid);
chmod($FinalFfilename, 0777);

##Prepare to output
$linkname=random_string('10').'.n3';
$filelink = $GLOBALS['URI'].'/extras/'.$linkname;

if(!@copy($FinalFfilename, S3DB_SERVER_ROOT.'/extras/'.$linkname))
		{
		echo  "Could not copy the file. This could be because Apache does not have 'write' permission on the s3db folder or the /extras/.";
		exit;
		}

if(in_array('output', array_keys($inputs)))
	{
	
	#Try reading simile. If the site is not up, give an error message
	
	$simileLink = 'http://simile.mit.edu/babel/translator?reader=n3&writer=rdf-xml&mimetype=text%2Fplain&url='.$filelink;
	$simile = @fopen($simileLink,'r');
	if(!$simile)
		{echo "Could not connect to the rdf conversion service (http://simile.mit.edu/babel), please make sure you are connected to the internet.";
		exit;
		}
	$translation = stream_get_contents($simile);
	if(ereg('<html>', $translation))
		{
		echo "Simile could not convert the file. Please copy the triples on this <a href='".$filelink."'>direct link</a> and submit it to <a href='http://simile.mit.edu/babel'>simile</a> for a conversion in your format of choice.";
		exit;
		}
	
	switch ($inputs['output']) {
		case 'rdf-xml':
		##Convert the n3 to a model; convert that model into rdf
		include_once(S3DB_SERVER_ROOT.'/rdfheader.inc.php');
		$model = ntriples2php($n3);
		$model->saveAs(S3DB_SERVER_ROOT.'/tmp/'.str_replace('.n3', '.rdf', $linkname), 'RDF'); 
		$filelink = str_replace('.n3', '.rdf', $filelink);
		$simileLink = 'http://simile.mit.edu/babel/translator?reader=n3&writer=rdf-xml&mimetype=text%2Fplain&url='.$filelink;
		break;

		case 'json':
		$filelink = str_replace('.n3', '.json', $filelink);
		$simileLink = 'http://simile.mit.edu/babel/translator?reader=n3&writer=exhibit-json&mimetype=text%2Fplain&url='.$filelink;
		break;
		
		default :
			$filelink = str_replace('.n3', '.rdf', $filelink);
			$simileLink = 'http://simile.mit.edu/babel/translator?reader=n3&writer=rdf-xml&mimetype=text%2Fplain&url='.$filelink;
		break;
	}
	$simile = @fopen($simileLink,'r');
	$translation = stream_get_contents($simile);
	file_put_contents($translation, $filelink);
	}
if(in_array('link', array_keys($inputs))) {
	echo "Link for n-triples <a href='".$filelink."'>".$filelink."</a><br />";
	
	$filelink = str_replace('.n3', '.rdf', $filelink);
	$simileLink = 'http://simile.mit.edu/babel/translator?reader=n3&writer=rdf-xml&mimetype=text%2Fplain&url='.$filelink;
	$simile = @fopen($simileLink,'r');
	$translation = stream_get_contents($simile);
	if($translation!='' && !ereg('<html>', $translation)) {
	@file_put_contents($translation, $filelink);
	echo "Link for xml-rdf <a href='".$filelink."'>".$filelink."</a><br />";
	}
	
	$filelink = str_replace('.n3', '.json', $filelink);
	$simileLink = 'http://simile.mit.edu/babel/translator?reader=n3&writer=exhibit-json&mimetype=text%2Fplain&url='.$filelink;
	$simile = @fopen($simileLink,'r');
	$translation = stream_get_contents($simile);
	if($translation!=''&& !ereg('<html>', $translation)){
	file_put_contents($translation, $filelink);
	echo "Link for Json <a href='".$filelink."'>".$filelink."</a><br />";
	}
	#Header('Location:'.$filelink);
	#exit;
}
else {
	if($inputs['download']!='no'){
	header("Content-Type: application/octet-stream");
	header("Content-Disposition: attachment; filename=".$linkname);
	header("Content-Transfer-Encoding: binary");
	}	
		echo file_get_contents($filelink);
		exit;
}

exit;

}

function addCollectionItemStats($user_id,$db, $class_id, $inputs)
{
			
			$s3ql=compact('user_id','db');
			$s3ql['from']='items';
			$s3ql['where']['collection_id']=$class_id;
			
			$items = S3QLaction($s3ql);
			
			if (is_array($items) && !empty($items)) {
			foreach ($items as $key=>$item_info) {
				if(!$inputs['nocore'])
				$n3 .= sprintf(':I'.$item_info['item_id'].' a s3db:s3dbItem .').chr(10).chr(10);

				$objectPredicates=array('rdfs:label'=>'"'.$item_info['notes'].'"',
											'a'=>':C'.$class_id);
			
				$objectPredicates = array_filter($objectPredicates);

				if(is_array($objectPredicates) && !empty($objectPredicates))
				{	#Global "this is a resource" statement
				$n3 .= sprintf(':I'.$item_info['item_id']);
				
				
				foreach ($objectPredicates as $predicate=>$object) {
					if($object!='""')
					$n3 .= sprintf("%s", ' '.$predicate.' '.$object.(($predicate==end(array_keys($objectPredicates)))?' .'.chr(10).chr(10):' ;'.chr(10).chr(9)));
				}
				}
			
			}
			}

			return ($n3);
			
}

function addRuleStats($user_id,$db, $url,$rule_id, $inputs)
{$COREletter = $GLOBALS['COREletter'];
	$s3ql=compact('user_id','db');
	$s3ql['from']='statements';
	$s3ql['where']['rule_id']=$rule_id;

	$stats = S3QLaction($s3ql);	
	
	
	$subject_name = 'item_id';	
	$predicate_name = 'rule_id';
	$object_name = 'item_id';
	
	if(is_array($stats) && !empty($stats))
	foreach ($stats as $key=>$stat_info) {
	if(!$inputs['nocore'])	
	$n3 .= sprintf(n3UID('S'.$stat_info['statement_id']).' a s3db:s3dbStatement .').chr(10).chr(10);
	
	if($stat_info['file_name']!='')
		{
		#find the file. Write it as base64encoded
			
			#echo '<pre>';print_r($stat_info);exit;
			$fileLocation = fileLocation($stat_info, $db);
			
			if($fileLocation!='')
			{
			
			if(!$inputs['files']){
			$content=@fread(@fopen($fileLocation, 'r'), @filesize($fileLocation));
			
			$content=base64_encode($content);
			$object='"s3dbFile_'.$stat_info['file_name'].'_'.$content.'"';
			}
			else {
				$object='"s3dbLink_'.$stat_info['file_name'].'_'.S3DB_URI_BASE.'/download.php?key='.$inputs['key'].'&statement_id='.$stat_info['statement_id'].'"';
			}
			#echo $object;exit;
			#echo $subClass_info['file_name'].chr(13).chr(10);
			}
		#ereg('<a href(.*)download.php(.*)>(.*)</a>', $stat_info['value'], $linkdata);
		#$statfilelink='<'.$url.'download.php'.str_replace('"', '', $linkdata[2]).'>';
		#$object=$statfilelink;
		}
	elseif($stat_info['object_id']!='')
		{
		$object=':'.$COREletter[$object_name].$stat_info['value'];

		}
		else {
			
			ereg('<a href=(.*)>(.*)</a>', $stat_info['value'], $links);
						if(!empty($links))
								$object = '"'.str_replace(array('"', '\''),array('', ''), $links[1]).'"';
						else					
							$object='"'.$stat_info['value'].'"';
			
			#$object='"'.htmlentities($stat_info['value']).'"';
			}

		$objectPredicates=array('rdf:subject'=>n3UID($COREletter[$subject_name].$stat_info[$subject_name]),
								'rdf:predicate'=>n3UID($COREletter[$predicate_name].$stat_info[$predicate_name]), 
									
								'rdf:object'=>$object);
	
		#$objectPredicates['rdfs:label'] ='"'.$stat_info['subject'].' '.$stat_info['instance_notes'].' (I'.$stat_info['instance_id'].') '.$stat_info['verb'].' '.$stat_info['object'].' '.(($stat_info['object_id']!='')?($stat_info['object_notes'].' (I'.$stat_info['value'].')'):(($stat_info['file_name']=='')?str_replace('"', '', $objectPredicates['rdf:object']):$stat_info['file_name'])).'"';
		$objectPredicates['rdfs:label'] ='"'.$stat_info['subject'].' '.$stat_info['instance_notes'].' I'.$stat_info['instance_id'].' '.$stat_info['verb'].' '.$stat_info['object'].' '.(($stat_info['object_id']!='')?($stat_info['object_notes'].' I'.$stat_info['value'].''):(($stat_info['file_name']=='')?str_replace('"', '', $objectPredicates['rdf:object']):$stat_info['file_name'])).'"';
				
		
		$objectPredicates = array_filter($objectPredicates);

				if(is_array($objectPredicates) && !empty($objectPredicates) && !$inputs['nocore'])
				{	#Global "this is a resource" statement
				$n3 .= sprintf(':S'.$stat_info['statement_id']);
				
				
				foreach ($objectPredicates as $predicate=>$object) {
					if($object!='""')
					$n3 .= sprintf("%s", ' '.$predicate.' '.$object.(($predicate==end(array_keys($objectPredicates)))?' .'.chr(10).chr(10):' ;'.chr(10).chr(9)));
				}
				}
			
		#unreified statement - the only one neeed where nocore is specified
		$n3 .= sprintf("%s", $objectPredicates['rdf:subject']);
		$n3 .= chr(9).sprintf("%s", $objectPredicates['rdf:predicate'].' '.(($subClass_info['object_id']!='')?$objectPredicates['rdf:object']:$objectPredicates['rdf:object']).' .').chr(10).chr(10);

}
	
return ($n3);
}

function addProjectRulesAndCollections($user_id,$db, $url, $project_id, $inData)
{
$s3ql=compact('user_id','db');
$s3ql['select']='*';
$s3ql['from']='collections';
$s3ql['where']['project_id']=$project_id;

$collections = S3QLaction($s3ql);

if(is_array($collections)){
	
	#echo '<pre>';print_r($collections);
	foreach ($collections as $key=>$collection_info) {
		$n3 .= getSubClassStats('C'.$collection_info['collection_id'], $collection_info, $inData, $user_id, $db, $N3coreNames, $inputs);
		#echo $n3;exit;
	}
}

$s3ql=compact('user_id','db');
$s3ql['select']='*';
$s3ql['from']='rules';
$s3ql['where']['project_id']=$project_id;

$rules = S3QLaction($s3ql);

if(is_array($rules)){
	
	foreach ($rules as $key=>$rule_info) {
		$n3 .= getSubClassStats('R'.$rule_info['rule_id'], $rule_info, $inData, $user_id, $db, $N3coreNames, $inputs);
		#echo $n3;exit;
	}

}

return ($n3);
}

function addGroupUsers($user_id,$db, $group_id)
{
	$s3ql=compact('user_id','db');
	$s3ql['select']='*';
	$s3ql['from']='users';
	$s3ql['where']['group_id']=$group_id;

	
	$users = S3QLaction($s3ql);
	$users = grab_id('user', $users);
	if(is_array($users))
	foreach ($users as $key=>$user_id) {
		$n3 .= sprintf(n3UID('U'.$user_id).' rdfs:subClassOf '.n3UID('G'.$group_id).' .').chr(10);
	}
	return ($n3);
}

function rootIDinfo($s3idNames, $REQUESTdat, $argv, $user_id, $key, $db)
{
	if(!in_array('uid', array_keys($REQUESTdat)))
	$specified_id = array_intersect($s3idNames, array_keys($REQUESTdat));
	else {
		
		$specified_id = $GLOBALS['COREletterInv'][letter($REQUESTdat['uid'])];
	}

#echo '<pre>';print_r($REQUESTdat);exit;

if(count($specified_id)!='1')
	{
	if(is_array($argv))
	$inData = array_diff($argv, array($key, 'rdf.php'));
	if(is_array($inData)){
	foreach ($inData as $key=>$value) {
		list($idname[], $id[])=explode('=', $value);

	}
	$specified_id = array_intersect($s3idNames, $idname);
	}
	
	if(count($specified_id)!='1')
		{
		
		echo $GLOBALS['messages']['something_missing']."<message>Please specify 1 and only 1 id for the root of the ontology</message>";
		exit;
		}
	else {
		$inData = array_combine($idname, $id);
		$rootID = $id[0];
		$specified_id = $idname[0];
	}
	}
	else {
		$inData = $REQUESTdat;
		$specified_id=array_combine(array('0'), $specified_id);
		$specified_id= $specified_id[0];
		$rootID = $REQUESTdat[$specified_id];
		
		if($rootID=='')
		{$rootID = ereg_replace('^'.letter($REQUESTdat['uid']), '', $REQUESTdat['uid']);
		$specified_id = $GLOBALS['COREletterInv'][letter($REQUESTdat['uid'])];
		}

	}


 $specified_id_type = array_search($specified_id, $s3idNames);
 $letter = strtoupper(substr($specified_id,0,1));
 
 $specified_id_info = URIinfo($letter.$rootID, $user_id, $key, $db);

return (compact('letter', 'specified_id', 'specified_id_type', 'specified_id_info', 'inData', 'rootID'));
}

function getSubClassStats($uid, $subClass_info, $inData, $user_id, $db, $N3coreNames, $inputs)
		{
		global $verbs;
		
		$uid_info = uid($uid);
		
		$letter = substr($uid,0,1);
		$a_class_type = $GLOBALS['s3codes'][substr($uid,0,1)];
		$a_class_id = $GLOBALS['COREids'][$a_class_type];
		$CORElabel = $GLOBALS['CORElabel'];
		$N3coreNames = ($N3coreNames!='')?$N3coreNames:$GLOBALS['N3coreNames'];

		
		if(!$inputs['nocore'])
			$n3 .= sprintf(n3UID($uid_info['uid']).' a '.$N3coreNames[$a_class_type].' .').chr(10).chr(10);
		
		
		switch ($a_class_type) {
			case 'user':
			if ($uid_info['Did']==$GLOBALS['s3db_info']['deployment']['Did']) {
				$objectPredicates=array('rdfs:subClassOf' => n3UID($uid_info['Did']),
										'rdfs:label'=>'"'.$subClass_info[$CORElabel[$a_class_id]].'"',
										'foaf:mbox'=>'"'.$subClass_info['account_email'].'"',
										'foaf:name'=>'"'.$subClass_info['account_uname'].'"',
										'foaf:password'=>($user_id==1)?'"'.findPassword(ereg_replace('^'.$letter, '',$uid_info['uid']), $db).'"':'',
										'dcterms:creator'=>n3UID('U'.$subClass_info['created_by']),
										'dcterms:created'=>'"'.$subClass_info['created_on'].'"');
			
			}
			else {
				$objectPredicates=array('rdfs:subClassOf' => n3UID($GLOBALS['s3db_info']['deployment']['Did']));
			}
			
			
			
			break;
			
			case 'group':
				$objectPredicates=array('rdfs:subClassOf' => ':'.$uid_info['Did'],
										'rdfs:label'=>'"'.$subClass_info[$CORElabel[$a_class_id]].'"',
										'dcterms:creator'=>n3UID('U'.$subClass_info['created_by']),
										'dcterms:created'=>'"'.$subClass_info['created_on'].'"');
			

			$addStat .= addGroupUsers($user_id,$db, $subClass_info[$a_class_id]);

			break;
			case 'project':
				$objectPredicates=array(
									'rdfs:subClassOf' => n3UID($uid_info['Did']),
									'rdfs:label'=>'"'.$subClass_info[$CORElabel[$a_class_id]].'"',
									'dcterms:creator'=>n3UID('U'.$subClass_info['created_by']),
									'dcterms:created'=>'"'.$subClass_info['created_on'].'"'
									);
					
				
				$addStat .= addProjectRulesAndCollections($user_id,$db, $url, $subClass_info[$a_class_id], $inData);	
				
				break;
			case 'collection':
					
			$objectPredicates=array(
										'rdfs:subClassOf'=>n3UID('P'.$subClass_info['project_id']), 
										'rdfs:label'=>'"'.$subClass_info['entity'].'"',
										'rdfs:comment'=>'"'.$subClass_info['notes'].'"',
										#'dcterms:creator'=>n3UID('U'.$subClass_info['created_by']),
										#'dcterms:created'=>'"'.$subClass_info['created_on'].'"'
										);
					
					
			if(!$inputs['nometa']){
			$objectPredicates['dcterms:creator'] =	n3UID('U'.$subClass_info['created_by']);
			$objectPredicates['dcterms:created'] =	'"'.$subClass_info['created_on'].'"';
			}
			
			if(in_array('all', array_keys($inputs)))
			$addStat .= addCollectionItemStats($user_id,$db, $subClass_info[$a_class_id], $inputs);

			#trying to see if the collection declarations are meesing up the query a lot
			if($inputs['nocore'])
			$n3 .= sprintf(n3UID($uid_info['uid']).' a '.$N3coreNames[$a_class_type].' .').chr(10).chr(10);
			
			break;
			case 'rule':
					
					$subject_id = n3UID('C'.$subClass_info['subject_id']);
					
									
					$verb_id = ($subClass_info['verb_id']=="")?":I".random_string(5):n3UID("I".$subClass_info['verb_id']);
					#predicates can't be literals, so create a resource for literal verbs.
					#when the verb is not an ID, a random string is generated that will simulate the ID of an instance.
					if(!in_array($subClass_info['verb'], array_keys($verbs)))
					{
					$addStat .= sprintf($verb_id);
					$addStat .= sprintf(' rdfs:label "'.$subClass_info['verb'].'" ;').chr(10);
					if(!$inputs['nocore'])
					$addStat .= chr(9).sprintf(' a s3db:s3dbItem ;').chr(10);
					##Find collection of this item and output this information
					$item_info = s3info('item', $subClass_info['verb_id'], $db);
					if(is_array($item_info))
					$addStat .= chr(9).sprintf(' a '.n3UID("C".$item_info['resource_class_id']).' .').chr(10).chr(10);
					else {#find a collection for the verbs
						$verbCollection = projectVerbClass(array('project_id'=>$subClass_info['project_id'], 'db'=>$db,'user_id'=>$user_id));
						$addStat .= chr(9).sprintf(' a '.n3UID("C".$verbCollection['resource_id']).' .').chr(10).chr(10);
					}

					$verbs[$subClass_info['verb']] =$verb_id;
					}
					else {
						$verb_id = $verbs[$subClass_info['verb']];
												
					}
					
					
					$object_id = ($subClass_info['object_id']=="")?'"'.$subClass_info['object'].'"':n3UID("C".$subClass_info['object_id']);
					
					
				$objectPredicates=array(
								
								'rdfs:label'=>'"'.$subClass_info['subject'].' '.$subClass_info['verb'].' '.$subClass_info['object'].'"',
								#'dc:comment'=>'"'.$subClass_info['notes'].'"', 
								'rdfs:subClassOf'=>n3UID('P'.$subClass_info['project_id']), 
								'rdf:subject'=>$subject_id, 
								'rdf:predicate'=>$verb_id,
								'rdf:object'=>$object_id,
								#'dcterms:creator'=>n3UID('U'.$subClass_info['created_by']),
								#'dcterms:created'=>'"'.$subClass_info['created_on'].'"'
								);
				
				if(!$inputs['nometa']){
					$objectPredicates['dcterms:creator'] = n3UID('U'.$subClass_info['created_by']);
					$objectPredicates['dcterms:created'] = '"'.$subClass_info['created_on'].'"';
				}
				
				##When no core is needed (when the document is not meant to be reloaded, there is no need for 
				
				$addStat .= sprintf($subject_id.' '.$verb_id .' '.$object_id.' .').chr(10);
				
				
				if(in_array('all', array_keys($inputs)))
				$addStat .= addRuleStats($user_id,$db, $url, $subClass_info[$a_class_id], $inputs);	

				#trying to see if the collection declarations are meesing up the query a lot
				if($inputs['nocore'])
				$n3 .= sprintf(n3UID($uid_info['uid']).' a '.$N3coreNames[$a_class_type].' .').chr(10).chr(10);
			
				
				break;
					case 'item':
						
					$objectPredicates=array('rdfs:label'=>'"'.$subClass_info['notes'].'"',
											'a'=>n3UID('C'.$subClass_info['class_id']),
											#'dcterms:creator'=>':U'.$subClass_info['created_by'],
											#'dcterms:created'=>'"'.$subClass_info['created_on'].'"'
											);
					if(!$inputs['nometa']){
					$objectPredicates['dcterms:creator'] = ':U'.$subClass_info['created_by'];
					$objectPredicates['dcterms:created'] = '"'.$subClass_info['created_on'].'"';
					}
				
					#trying to see if the collection declarations are meesing up the query a lot
					if($inputs['nocore'])
					$n3 .= sprintf(n3UID($uid_info['uid']).' a '.$N3coreNames[$a_class_type].' .').chr(10).chr(10);
			
				break;
					
					case 'statement':
					
					$subject_name = 'instance_id';	
					$predicate_name = 'rule_id';
					$object_name = 'instance_id';

					if($subClass_info['file_name']!='')
					{
					#find the file. Write it as base64encoded
						
						#echo '<pre>';print_r($subClass_info);
						
						$fileLocation = fileLocation($subClass_info, $db);
						
						if($fileLocation!='')
						{
						
						if(!$inputs['files']){
						$content=@fread(@fopen($fileLocation, 'r'), @filesize($fileLocation));
						
						$content=base64_encode($content);
						$object='"s3dbFile_'.$subClass_info['file_name'].'_'.$content.'"';
						}
						else {
							$object='"s3dbLink_'.$subClass_info['file_name'].'_'.S3DB_URI_BASE.'/download.php?key='.$inputs['key'].'&statement_id='.$subClass_info['statement_id'].'"';
						}
						#echo $object;exit;
						#echo $subClass_info['file_name'].chr(13).chr(10);
						}
					#echo $object;exit;
					#ereg('<a href(.*)download.php(.*)>(.*)</a>', $stat_info['value'], $linkdata);
					#$statfilelink='<'.$url.'download.php'.str_replace('"', '', $linkdata[2]).'>';
					#$object=$statfilelink;
					}
					
					elseif($subClass_info['object_id']!='')
					{
					$object=n3UID($COREletter[$object_name].$subClass_info['value']);
					
					}
					else {
						ereg('<a href=(.*)>(.*)</a>', $subClass_info['value'], $links);
						if(!empty($links))
								$object = '"'.str_replace(array('"', '\''),array('', ''), $links[1]).'"';
						else					
							$object='"'.$subClass_info['value'].'"';
					}


					
					$objectPredicates=array(
									#'rdf:subject'=>n3UID($COREletter[$subject_name].$subClass_info[$subject_name]),
									'rdf:subject'=>n3UID('I'.$subClass_info[$subject_name]),
									'rdf:predicate'=>n3UID('R'.$subClass_info[$predicate_name]), 
									
									#'rdf:object'=>($subClass_info['object_id']!='')?':'.$COREletter[$object_name].$subClass_info['value']:'"'.$subClass_info['value'].'"'
									'rdf:object'=>$object,
									'dcterms:creator'=>n3UID('U'.$subClass_info['created_by']),
									'dcterms:created'=>'"'.$subClass_info['created_on'].'"'
									);
					
				 #$objectPredicates['rdfs:label'] ='"'.$subClass_info['subject'].' '.$subClass_info['instance_notes'].' (I'.$subClass_info['instance_id'].') '.$subClass_info['verb'].' '.$subClass_info['object'].' '.(($subClass_info['object_id']!='')?($subClass_info['object_notes'].' (I'.$subClass_info['value'].')'):(($subClass_info['file_name']=='')?str_replace('"', '', $objectPredicates['rdf:object']):$subClass_info['file_name'])).'"';
				 $objectPredicates['rdfs:label'] ='"'.$subClass_info['subject'].' '.$subClass_info['instance_notes'].' I'.$subClass_info['instance_id'].' '.$subClass_info['verb'].' '.$subClass_info['object'].' '.(($subClass_info['object_id']!='')?($subClass_info['object_notes'].' I'.$subClass_info['value'].''):(($subClass_info['file_name']=='')?str_replace('"', '', $objectPredicates['rdf:object']):$subClass_info['file_name'])).'"';
				
				#when no core is requested, return just the reified stat
				if($inputs['nocore']) {
					$objectPredicates=array();
				}
				
				#unreified statement
				
				$addStat .= sprintf("%s", $objectPredicates['rdf:subject']);
				$addStat .= chr(9).sprintf("%s", $objectPredicates['rdf:predicate'].' '.(($subClass_info['object_id']!='')?$objectPredicates['rdf:object']:$objectPredicates['rdf:object']).' .').chr(10);
				break;
		}
		
		#export user permissions in rdf 
		if(in_array('p', array_keys($inData))){
		$s3ql=compact('user_id','db');
		$s3ql['from']='users';
		$s3ql['where'][$GLOBALS['COREids'][$a_class_type]]=$subClass_info[$GLOBALS['COREids'][$a_class_type]];
		$users = S3QLaction($s3ql);
		
		
		if(is_array($users)){
		$permissions=array_map('grab_permission', $users);
		$users=grab_id('user', $users);
		$specified_id_info['permissions']=array_combine($users, $permissions);
		}
		if(is_array($specified_id_info['permissions'])){
			$n3permissions .= chr(10);
			foreach ($specified_id_info['permissions'] as $user_code=>$pcode) {
			$n3permissions .= sprintf(n3UID('U'.$user_code).' s3dbpc:VCU'.$pcode.' '.n3UID($uid_info['uid']).' .').chr(10);
		}
		}
		}

		$objectPredicates = array_filter($objectPredicates);

		if(is_array($objectPredicates) && !empty($objectPredicates))
		{	#Global "this is a resource" statement
		$n3 .= sprintf(n3UID($uid_info['uid']));
		
		
		foreach ($objectPredicates as $predicate=>$object) {
			if($object!='""')
			$n3 .= sprintf("%s", ' '.$predicate.' '.$object.(($predicate==end(array_keys($objectPredicates)))?' .'.chr(10).chr(10):' ;'.chr(10).chr(9)));
		}
		}
		if(is_array($user2declare))
		foreach ($user2declare as $user=>$toDeclare) {
			$uid_info = uid($user);
			$addStat .= sprintf(n3UID('U'.$user), 'rdfs:label "'. getUserName($user, $db).'"').chr(10);
		}

		$n3 .=$addStat.$n3permissions;
		#echo $n3;exit;
		return ($n3);
	}
?>