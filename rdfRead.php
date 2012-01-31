<?php
#This script reads and rdf file and creates a model of it in S3DB notation
#Helena F Deus, March 12,2008

function rdfRead($F)
{extract($F);
	

$fileclean=$file.'_clean';

#Remove the files


#$root = ($_REQUEST['root']!='')?$_REQUEST['root']:$argv[4];
$root = $inputs['root'];
$inData=$inputs;



#Before parsing the RDF, remove the files from the document
#$data = removeFiles($file); ##COMPLETED IN A SEPARATE SCRIPT (momentarily because of segmantation fault)
$data = fread(fopen($fileclean, 'r+'), filesize($fileclean));
;
 

define("RDFAPI_INCLUDE_DIR", S3DB_SERVER_ROOT."/pearlib/rdfapi-php/api/");
include(RDFAPI_INCLUDE_DIR . "RdfAPI.php");
include(RDFAPI_INCLUDE_DIR . "syntax/SyntaxN3.php");
include(RDFAPI_INCLUDE_DIR . "syntax/SyntaxRDF.php");
include(S3DB_SERVER_ROOT.'/s3dbcore/rdf.resources.inc.php');
include(S3DB_SERVER_ROOT.'/rdfheader.inc.php');

extract($resources);


#Create an RDF model from the raw data
#echo "Parsing the RDF data".chr(10);
if(!is_file($file.'_model') ||  $inputs['clean']==1){

#$model = ntriples2php($data); ##=> Crashing when the model is too big :-( :-(
if(filesize($fileclean)<20000){
	
	$model = ntriples2php($data);
}
else {#For big files, use the big file library ( which actually is smaller..)

$model = arc_ntriples2php($fileclean);
  
$m = ModelFactory::getDefaultModel();
if(empty($model))
	{echo "Your file could not be parsed.";
	exit;
	}

foreach ($model as $triple) {
	$mS = new Resource($triple['s']);
	$mP = new Resource($triple['p']);
	$mO = ($triple['o_type']=='literal')?new Literal($triple['o']):new Resource($triple['o']);
	$s = new Statement($mS, $mP, $mO);
	$m->add($s);
}

$model=$m;



}

if(!empty($model))
	unlink($fileclean);
else {
	echo "Your file seems to be empty.";
	exit;
}
$tmp = serialize($model);
file_put_contents($file.'_model', $tmp);
}
else {
$model = unserialize(file_get_contents($file.'_model'));
}
#Create an S3DB model from the RDF model
#echo "Building and S3DB structure from the RDF data".chr(10).chr(13);
if(!is_file($file.'_s3db') || $inputs['clean']==1){

$s3db = model2deploy($model, $resources, $root, $inData);
#$s3db = arc_model2deploy($model, $resources, $root, $inData);
$tmp = serialize($s3db);
file_put_contents($file.'_s3db', $tmp);

}
else {
	 $s3db = unserialize(file_get_contents($file.'_s3db'));
}



#include('rdfWrite.php');


return ($s3db);
}

function arc_model2deploy($model, $resources, $root, $inData)
	{
	
	$Did = array();
	if($root=='') $root='deployment';
	if(in_array('load', array_keys($inData))) $uidReLoad=1;
	
	
	#echo '<pre>';print_r($model);exit;
	#find deployment
	#$P = $model->find(NULL, $rType, $cP); 
	#$P = get_object_vars($P);
	$entities = array('user', 'group','project', 'collection', 'item', 'rule');
	
	for ($i=0; $i <count($entities); $i++) {
		
		
		$letter = strtoupper(substr($entities[$i],0,1));
		$id = array();
		#find Projects 
		eval('$o=$c'.$letter.';');
		
		$a = $model->find(NULL, $rType, $o);
		$a=get_object_vars($a);
		#echo '<pre>';print_r($model);exit;
		for ($p=0; $p <count($a['triples']) ; $p++) {
			$tmpId = $a['triples'][$p]->getLabelSubject();
			array_push($id, $tmpId);
			
			#P is a resource to be queried.
			$rTmp = new Resource($tmpId);

			#find label of this P
			$id[$p] = modelInfoRDF($entities[$i], $model, $rTmp, $resources,$uidReLoad);
			#echo '<pre>';print_r($id);exit;
			
		}
		$Did[$letter] = $id;
	}
	$Did['permissions']=findResourceUsers($model, $uidReLoad);
		
	return ($Did);

	
	}



function model2deploy($model, $resources, $root, $inData)
	{
	extract($resources);
	
	$Did = array();
	if($root=='') $root='deployment';
	if(in_array('load', array_keys($inData))) $uidReLoad=1;
	
	
	#echo '<pre>';print_r($model);exit;
	#find deployment
	#$P = $model->find(NULL, $rType, $cP); 
	#$P = get_object_vars($P);
	$entities = array('user', 'group','project', 'collection', 'item', 'rule');
	
	for ($i=0; $i <count($entities); $i++) {
		
		
		$letter = strtoupper(substr($entities[$i],0,1));
		$id = array();
		#find Projects 
		eval('$o=$c'.$letter.';');
		
		$a = $model->find(NULL, $rType, $o);
		#echo '<pre>';print_r($a);exit;
		$a=get_object_vars($a);
		#echo '<pre>';print_r($model);exit;
		for ($p=0; $p <count($a['triples']) ; $p++) {
			$tmpId = $a['triples'][$p]->getLabelSubject();
			array_push($id, $tmpId);
			
			#P is a resource to be queried.
			$rTmp = new Resource($tmpId);

			#find label of this P
			$id[$p] = modelInfoRDF($entities[$i], $model, $rTmp, $resources,$uidReLoad);
			
			
		}
		$Did[$letter] = $id;
	}
	$Did['permissions']=findResourceUsers($model, $uidReLoad);
		
	return ($Did);

	
	}





function findCoreId($element, $model, $resources, $Id,$uidReLoad)
	{
		switch ($element) {
			case 'user':
				$sparql = 'SELECT ?subj WHERE { ?subj <http://www.w3.org/1999/02/22-rdf-syntax-ns#type> <http://www.s3db.org/core#s3dbUser>. ?subj <http://www.w3.org/2000/01/rdf-schema#subClassOf> <'.$Id .'> }';
			
			
			break;
			
			case 'collection':
				$sparql = 'SELECT ?subj WHERE { ?subj <http://www.w3.org/1999/02/22-rdf-syntax-ns#type> <http://www.s3db.org/core#s3dbCollection>. ?subj <http://www.w3.org/2000/01/rdf-schema#subClassOf> <'.$Id .'> }';
			
			break;
			case 'rule':

				$sparql = 'SELECT ?subj WHERE { ?subj <http://www.w3.org/1999/02/22-rdf-syntax-ns#type> <http://www.s3db.org/core#s3dbRule>. ?subj <http://www.w3.org/2000/01/rdf-schema#subClassOf> <'.$Id .'> }';

			break;
			case 'item':
				
				$sparql = 'SELECT ?subj WHERE { ?subj <http://www.w3.org/1999/02/22-rdf-syntax-ns#type> <http://www.s3db.org/core#s3dbItem>. ?subj <http://www.w3.org/1999/02/22-rdf-syntax-ns#type> <'.$Id .'>}';
				
				
			break;

			case 'statement':
			
				$sparql = 'SELECT ?subj WHERE { ?subj <http://www.w3.org/1999/02/22-rdf-syntax-ns#type> <http://www.s3db.org/core#s3dbStatement>. ?subj <http://www.w3.org/1999/02/22-rdf-syntax-ns#predicate> <'.$Id .'>}';
				

			break;
			}
		  
		  		 		
		
		  $C = $model->sparqlQuery($sparql);
		 
		  $id = array();
			
		  #on each collection, find the label and items
		  $id = pushId($C);
		 
		  $id = array_values($id);
		  
				
			for ($c=0; $c < count($id); $c++) {
						
					
					$rTmpid = new Resource($id[$c]);
					$info = modelInfoRDF($element, $model, $rTmpid, $resources,$uidReLoad);
					
					#find all items inside collections/all statement inside rules + items
					#find all items inside collections
										
					switch ($element) {
					

					case 'collection':
					
					$items = '';
						$items = findCoreId('item', $model, $resources, $id[$c],$uidReLoad);
						
						if (!empty($items[0])) {
							$info['I'] = $items;
						}
						
						
										
					break;
					case 'rule':
						
						$stats = '';
						

						$stats = findCoreId('statement', $model, $resources, $id[$c],$uidReLoad);
						
												
						if (!empty($stats[0])) {
							$info['S'] = $stats;
						}
					break;
					}
					
					$I[$c]=$info;
					
			
				}
		
		return ($I);
	}

function modelInfoRDF($element, $model, $rTmpId, $resources,$uidReLoad)
	{extract($resources);
		

		$litId = get_object_vars($rTmpId);
		$idName = $litId['uri'];
		
		switch ($element) {
		
			case 'user':
			$data_vars = array('login'=>new Resource('http://www.w3.org/2000/01/rdf-schema#label'), 
								'email'=>new Resource('http://xmlns.com/foaf/0.1/mbox'),
								'password'=>new Resource('http://xmlns.com/foaf/0.1/password'),
								'name'=>new Resource('http://xmlns.com/foaf/0.1/name'),
								'created_on'=>new Resource('http://purl.org/dc/terms/created'),
								'created_by'=>new Resource('http://purl.org/dc/terms/creator'));

			$id['user_id'] = $litId['uri'];
			break;
			
			case 'group':
			$data_vars = array('name'=>new Resource('http://www.w3.org/2000/01/rdf-schema#label'),
								'created_on'=>new Resource('http://purl.org/dc/terms/created'),
								'created_by'=>new Resource('http://purl.org/dc/terms/creator'));
			
			$id['group_id'] = $litId['uri'];
			#Who belonged to this group?
			$users = findCoreId('user', $model, $resources, $id['group_id'],$uidReLoad);
			$id['U']=$users;
			
			
			break;
			
			case 'project':
			$data_vars = array('name'=>new Resource('http://www.w3.org/2000/01/rdf-schema#label'), 
									'description'=>new Resource('http://www.w3.org/2000/01/rdf-schema#comment'),
									'created_on'=>new Resource('http://purl.org/dc/terms/created'),
									'created_by'=>new Resource('http://purl.org/dc/terms/creator'));
			$id['project_id'] = $litId['uri'];
			
			#find all subclasses of this P (rules and collections). It's an AND query (subClassOf P and s3dbCollection)
			#$collections = findCoreId('collection', $model, $resources, $id['project_id'],$uidReLoad);
			#$id['C'] = $collections;
			#$rules = findCoreId('rule', $model, $resources, $id['project_id'],$uidReLoad);
			#$id['R'] = $rules;
			break;
			
			case 'collection':
			$data_vars = array('project_id'=>new Resource('http://www.w3.org/2000/01/rdf-schema#subClassOf'), 
								'name'=>new Resource('http://www.w3.org/2000/01/rdf-schema#label'), 
									'notes'=>new Resource('http://www.w3.org/2000/01/rdf-schema#comment'),
									'created_on'=>new Resource('http://purl.org/dc/terms/created'),
									'created_by'=>new Resource('http://purl.org/dc/terms/creator'));
			
			$id['collection_id'] = $litId['uri'];
			$items = findCoreId('item', $model, $resources, $id['collection_id'],$uidReLoad);
			$id['I']=$items;
			
			break;
			case 'rule':
			$data_vars = array('project_id'=>new Resource('http://www.w3.org/2000/01/rdf-schema#subClassOf'), 
								'subject_id'=>new Resource('http://www.w3.org/1999/02/22-rdf-syntax-ns#subject'), 
								'verb_id'=>new Resource('http://www.w3.org/1999/02/22-rdf-syntax-ns#predicate'), 
								'object'=>new Resource('http://www.w3.org/1999/02/22-rdf-syntax-ns#object'), 
								'notes'=>new Resource('http://www.w3.org/2000/01/rdf-schema#comment'),
								'validation'=>new Resource('http://www.s3db.org/core#validation'),
								'created_on'=>new Resource('http://purl.org/dc/terms/created'),
								'created_by'=>new Resource('http://purl.org/dc/terms/creator'));

			$id['rule_id'] = $litId['uri'];
			
			$statements = findCoreId('statement', $model, $resources, $id['rule_id'],$uidReLoad);
			$id['S']=$statements;
			
			
			
			break;
			case 'item':
			$data_vars = array('collection_id'=>new Resource('http://www.w3.org/1999/02/22-rdf-syntax-ns#type'), 
								'notes'=>new Resource('http://www.w3.org/2000/01/rdf-schema#label'),
								'created_on'=>new Resource('http://purl.org/dc/terms/created'),
								'created_by'=>new Resource('http://purl.org/dc/terms/creator'));

			
			$id['item_id'] = $litId['uri'];
			break;	
							
			
			case 'statement':
				$data_vars = array('item_id'=>new Resource('http://www.w3.org/1999/02/22-rdf-syntax-ns#subject'), 
								'rule_id'=>new Resource('http://www.w3.org/1999/02/22-rdf-syntax-ns#predicate'), 
								'value'=>new Resource('http://www.w3.org/1999/02/22-rdf-syntax-ns#object'), 
								'notes'=>new Resource('http://www.w3.org/2000/01/rdf-schema#comment'),
								
								'created_on'=>new Resource('http://purl.org/dc/terms/created'),
								'created_by'=>new Resource('http://purl.org/dc/terms/creator'));
				$id['statement_id'] = $litId['uri'];	
								
				
			break;

	}
			if(!empty($idName)) {
				foreach ($data_vars as $s3dbParam=>$rdfResource) {
			
					$tmpTriples = $model->find($rTmpId, $rdfResource, NULL);
					$tmpTriples = get_object_vars($tmpTriples);
					
					if($element=='item' && count($tmpTriples['triples']>1))
					{
					$newTriples = array();
					foreach ($tmpTriples['triples'] as $key=>$tri) {
						$z = get_object_vars($tmpTriples['triples'][$key]);
						$z['obj'] = get_object_vars($z['obj']);
						
						if($z['obj']['uri']!='http://www.s3db.org/core#s3dbItem')
						{
							$newTriples[] = $tri;
						}
						
					}
					$tmpTriples['triples'] = $newTriples;
					
					}
					
					
					if(is_object($tmpTriples['triples'][0])){
					#Treat relation UID as literals
					
					
					if($s3dbParam=='object'){
						$tmp = $tmpTriples['triples'][0]->getObject();
						$tmp = $tmp->toString();
						
						ereg('^(Literal|Resource)\(\"(.*)\"\)',$tmp, $type);
							if ($type[1]=='Resource') {
							$s3dbParam='object_id';
							}
							else {
							$s3dbParam='object';
							}
							$tmp = $type[2];
						}
						
						else {
						$tmp = $tmpTriples['triples'][0]->getLabelObject();	
						
						}
						$id[$s3dbParam] = $tmp;
						
						if($uidReLoad)	{					
						#Reset the ID of the element
						ereg('(.*)/(.*)', $idName,$nameId);
						
						$id['original']=$id[$element.'_id'];
						$id[$element.'_id']=substr($nameId[count($nameId)-1],1,strlen($nameId[count($nameId)-1]));
						
						#Reset the ID of the elements associated with the element
						if(in_array($s3dbParam, array('subject_id', 'verb_id', 'object_id', 'collection_id', 'project_id', 'created_by', 'item_id', 'rule_id')))
							{$oldID='';
							ereg('(.*)/(.*)', $tmp, $oldID);
							
							$id[$s3dbParam]=substr($oldID[count($oldID)-1],1,strlen($oldID[count($oldID)-1]));
							}
						
						}
					}
					}
				
				if($element=='rule' && $id['object_id']!='' && $uidReLoad)
						{	
						
						for ($s=0; $s < count($id['S']); $s++) {
							$oldID='';
							ereg('(.*)/(.*)',$id['S'][$s]['value'], $oldID);
							$id['S'][$s]['value']=substr($oldID[count($oldID)-1],1,strlen($oldID[count($oldID)-1]));
						}
						
						
						}
				

				
				}
				if($element=='collection'){
					for ($i=0; $i < count($id['I']); $i++) {
					$id['I'][$i]['collection_id']=$id['collection_id'];
					}
				
				}
				
				return ($id);
	
	}

###CALLBACK FUNCTIONS


function findResourceUsers($model, $uidReLoad)
{

			for ($v=0; $v <=2 ; $v++) {
				for ($c=0; $c <= 2; $c++) {
					for ($u=0; $u <=2 ; $u++) {
						$pcode = $v.$c.$u;
						$sparql = 'SELECT ?subj, ?obj WHERE { ?subj <http://www.s3db.org/permission_codes#VCU'.$pcode.'> ?obj . }';

						$C = $model->sparqlQuery($sparql);
						
						for ($f=0; $f <count($C) ; $f++) {
							$C[$f]['?subj'] = get_object_vars($C[$f]['?subj']);
							$C[$f]['?obj'] = get_object_vars($C[$f]['?obj']);

							$P[$f]['shared_with'] = uidSimplify($C[$f]['?subj']['uri'], $uidReLoad,1);
							$P[$f]['uid'] = uidSimplify($C[$f]['?obj']['uri'], $uidReLoad,1);
							$P[$f]['permission_level']=$pcode;
						 }
					}
				}
			}
			
			return $P;
		  
		 
		 
}

function uidSimplify($uid, $uidReLoad, $letter)
{
	
	if($uidReLoad)
	{
		
		ereg('(.*)/(.*)',$uid, $oldID);
		if(!$letter)
			return (substr($oldID[count($oldID)-1],1,strlen($oldID[count($oldID)-1])));
		else
			return (substr($oldID[count($oldID)-1],0,strlen($oldID[count($oldID)-1])));	
		
	}
	else {
		return ($uid);
	}
	
}


?>