<?php
#rdfproject.php parses a project in s3db into n3.
#reads database info from the session or from key

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
ini_set('display_errors',1);
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
$format = $inputs['format'];

$FinalFfilename = ($inputs['file']!='')?$inputs['file']:$GLOBALS['s3db_info']['server']['db']['uploads_folder'].$GLOBALS['s3db_info']['server']['db']['uploads_file'].'/project'.$project_id.'_requested_'.$user_id.'_'.date('m.d.y-His').'.n3';

#create a file and start writting to it
#$fid = fopen($FinalFfilename, 'a+');
$fid = fopen($FinalFfilename, 'w');

if(!is_file($FinalFfilename)){
echo "Could not create file ".$FinalFfilename.'. User apache may not have permission to write to that directory';
exit;
}


$url = S3DB_URI_BASE;

##All the namespaces that will be used throughout go here
$ns = array('dc'=>'http://purl.org/dc/elements/1.1/','dcterms'=>'http://purl.org/dc/terms/','rdfs'=> 'http://www.w3.org/2000/01/rdf-schema#', 'rdf'=>'http://www.w3.org/1999/02/22-rdf-syntax-ns#', 'owl'=>'http://www.w3.org/2002/07/owl#','s3db'=>'http://www.s3db.org/core#', 's3dbpc'=>'http://www.s3db.org/permission_codes#',	'xsd'=>'http://www.w3.org/2001/XMLSchema#', 'foaf'=>'http://xmlns.com/foaf/0.1/', 'did'=>$GLOBALS['s3db_info']['deployment']['mothership'].$GLOBALS['Did'],''=>$url.((substr($url,strlen($url)-1,1)=='/')?'':'/'));

$N3coreNames = array('deployment'=>'s3db:s3dbDeployment','project'=>'s3db:s3dbProject', 'collection'=>'s3db:s3dbCollection', 'rule'=>'s3db:s3dbRule', 'item'=>'s3db:s3dbItem', 'statement'=>'s3db:s3dbStatement', 'user'=>'s3db:s3dbUser', 'group'=>'s3db:s3dbGroup');

if(!$inputs['nocore']) {
$core = fread(fopen('core.n3', 'r'), filesize('core.n3'));
$n3 .= sprintf('%s', $core);
#$n3 .= sprintf('%s', 'doc:'.$GLOBALS['Did'].' a s3db:s3dbDeployment .'.chr(10).chr(10));

}

#############################################################################
##define the Classes in the ontology of s3db
if($inputs['all']==1){
$s3Types = array('deployment'=>array('user', 'group', 'project'),
				'group'=>array('user'),
				'project'=>array('collection', 'rule'),
				'collection'=>array('item'), 
				'rule'=>array('statement'));
}
else {
	##avoid non domain stuff
   $s3Types = array('deployment'=>array('user', 'group', 'project'),
				'group'=>array('user'),
				'project'=>array('collection', 'rule'),
				'collection'=>array('item'),##still leave the collections because of the verbs, but add a rule not to output all other items
				);
}


##############################################################
#the unique identifier for each table
$s3idNames = $GLOBALS['COREids']; 

#now is there any id specified?

$specifiedInput = rootIDinfo($s3idNames, $inputs, $argv, $user_id, $key, $db);
extract($specifiedInput);

if(!$specified_id_info['view'])
	{echo formatReturn($GLOBALS['messages']['no_permission_message'],"User does not have access in this ".$specified_id_type, $input['format']);
	exit;
	}
$triples = array();
$rootUID = letter($specified_id_type).$specifiedInput['rootID'];
$rootTriples = rdf_encode(array(0=>$specified_id_info),letter($specified_id_type), 'array', $db);
$triples=array_merge($triples, $rootTriples);
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
		
		$n3permissions .= sprintf(n3UID('U'.$user_code).' s3dbpc:VCU'.$pcode.' '.n3UID($ruid_info['uid']).' .').chr(10);
		
		##triples fo the rdfapi
		$tr = array(0=>array('s'=>$ns[''].'U'.$user_code, 'p'=>$ns['s3dbpc'].$pcode,'o'=>$ns[''].$rootUID, 'p_type'=>'uri','o_type'=>'uri'));
		$triples=array_merge($triples, $tr);
	}
}

#EVERTHING THAT HAS A LABEL IS A CLASS. This includes verbs, object and instances
 $CT = compact('triples', 'specified_id_type', 'specified_id', 'specified_id_info', 's3Types', 'user_id', 'db','inputs');
$triples = class_triples($triples, $specified_id_type, $specified_id, $specified_id_info, $s3Types, $user_id, $db, $inputs);


if($format=='rdf'){
	
	$a['ns']=$ns;
	ini_set("include_path", S3DB_SERVER_ROOT."/pearlib/arc". PATH_SEPARATOR. ini_get("include_path"));
	include_once("ARC2.php");
	$parser = ARC2::getComponent('RDFXMLParser', $a);
	$index = ARC2::getSimpleIndex($triples, false) ; /* false -> non-flat version */
	$rdf_doc = $parser->toRDFXML($index,$ns);
	
	}
	elseif($format=='turtle'){
	ini_set("include_path", S3DB_SERVER_ROOT."/pearlib/arc". PATH_SEPARATOR. ini_get("include_path"));
	include_once("ARC2.php");
	$a['ns'] = $ns;
	$parser = ARC2::getComponent('TurtleParser', $a);
	$index = ARC2::getSimpleIndex($triples, false) ; /* false -> non-flat version */
	$rdf_doc = $parser->toTurtle($index,$ns);
	}
	elseif($format=='json' || $format=='rdf-json'){
	ini_set("include_path", S3DB_SERVER_ROOT."/pearlib/arc". PATH_SEPARATOR. ini_get("include_path"));
	include_once("ARC2.php");
	$a['ns'] = $ns;
	$parser = ARC2::getComponent('RDFJSONSerializer', $a);
	$index = ARC2::getSimpleIndex($triples, false); 
	$rdf_doc = $parser->toRDFJSON($index, $ns);
	}
	else{
	$format='n3';
	ini_set("include_path", S3DB_SERVER_ROOT."/pearlib/arc". PATH_SEPARATOR. ini_get("include_path"));
	include_once("ARC2.php");
	$a['ns'] = $ns;
	$parser = ARC2::getComponent('NTriplesSerializer', $a);
	$index = ARC2::getSimpleIndex($triples, false) ; /* false -> non-flat version */
	$rdf_doc = $parser->toNTriples($index, $ns);
	}


fwrite($fid, $rdf_doc);
fclose($fid);
chmod($FinalFfilename, 0777);

##Prepare to output
$linkname=random_string('10').'.'.$format;
$filelink = $GLOBALS['URI'].'/extras/'.$linkname;

if(!copy($FinalFfilename, S3DB_SERVER_ROOT.'/extras/'.$linkname))
		{
		echo  "Could not copy the file. This could be because Apache does not have 'write' permission on the s3db folder or the /extras/.";
		exit;
		}

if(in_array('link', array_keys($inputs))) {
	echo $filelink;
}
elseif(in_array('filename', array_keys($inputs))){
echo ($FinalFfilename);
}
else {
	if($inputs['download']!='no' && $inputs['download']!='0' && $inputs['download']!='false'){
	header("Content-Type: application/octet-stream");
	header("Content-Disposition: attachment; filename=".$FinalFfilename);
	header("Content-Transfer-Encoding: binary");
	}	
		echo file_get_contents($FinalFfilename);
		exit;
}

exit;


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

function class_triples($triples, $root, $specified_id, $specified_id_info, $s3Types, $user_id, $db,$inputs, $s3db=array(), $fork=0, $nest=0)
{	
	$s3idNames = $GLOBALS['COREids'];
	
	##this is the else, but since we re returninng...
	foreach ($s3Types[$root] as $k=>$a_class) {
	$uid_p = letter($specified_id).$specified_id_info[$specified_id];
	
	
	#each class has a descriptive statement
	
	$a_class_id = $s3idNames[$a_class];
	
	$a_class_letter = strtoupper(substr($a_class,0,1));
	$a_class_type = $GLOBALS['s3codes'][$a_class_letter];
	$s3ql=compact('user_id','db');
	$s3ql['select']='*';
	$s3ql['from']=$GLOBALS['plurals'][$a_class];
	$s3ql['where'][$specified_id]=$specified_id_info[$specified_id];
	
	if(ereg('(rule|statement)', $a_class_type))
		{$s3ql['where']['object']="!=UID";}
	
	
	$subClasses = S3QLaction($s3ql);#find them, output them. 
	#$verbs=array();
	$s3db[$uid_p][letter($a_class)] = $subClasses; 	
	
	#triples for teh rdf api
	
	$subClassTriples = rdf_encode($subClasses,letter($GLOBALS['plurals'][$a_class]), 'array', $db,$namespaces,$subClasses);
	
	if(!empty($subClassTriples)){
	$triples = array_merge($triples, $subClassTriples);
	}
	
	
	
	if(is_array($subClasses) && is_array($s3Types[$a_class])){
		##prepare triples for the rdf-api
			
			foreach ($subClasses as $subSub=>$subSubInfo) {
			if($inputs['all']==1 || ($a_class=='collection' && $subSubInfo['name']=='s3dbVerb')){
			$triples = class_triples($triples, $a_class, $a_class_id, $subSubInfo, $s3Types, $user_id, $db,$s3db, $fork, $nest=1);
			}
			
		}
		
	}
	
	
	$nest = 0;
	$fork++;	
	}
	
	return ($triples);
}

?>