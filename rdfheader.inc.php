<?php
ini_set('display_errors',0);
	if($_REQUEST['su3d'])
	ini_set('display_errors',1);
define("RDFAPI_INCLUDE_DIR", S3DB_SERVER_ROOT."/pearlib/rdfapi-php/api/");
include(RDFAPI_INCLUDE_DIR . "RdfAPI.php");
include(RDFAPI_INCLUDE_DIR . "syntax/SyntaxN3.php");
include(RDFAPI_INCLUDE_DIR . "syntax/SyntaxRDF.php");
ini_set("include_path", S3DB_SERVER_ROOT."/pearlib/arc". PATH_SEPARATOR. ini_get("include_path"));
include_once("ARC2.php");

//@set_time_limit(0);
//@ini_set('memory_limit', '2560M');
//@ini_set('upload_max_filesize', '128M');
//@ini_set('post_max_size', '256M');
//@ini_set('max_input_time', '-1');
//@ini_set('max_execution_time', '3000');
//@ini_set('expect.timeout', '-1');
//@ini_set('default_socket_timeout', '-1');

function ntriples2php($data)
	{

	// Prepare RDF
	$rdfInput = $data;

	// Show the submitted RDF

	// Create a new MemModel
	$model = ModelFactory::getDefaultModel();
	$n3pars = new n3Parser();

	// Load and parse document
	#$model->load($rdfInput);
	$model=$n3pars->parse2model($rdfInput);

	// Set the base URI of the model
	#$model->setBaseURI("http://www3.wiwiss.fu-berlin.de".$HTTP_SERVER_VARS['PHP_SELF']."/DemoModel#");
	
	$model->setBaseURI(S3DB_URI_BASE);
	
	return ($model);

	}

function arc_ntriples2php($datafile)
	{

	ini_set("include_path", S3DB_SERVER_ROOT.'/pearlib/arc'.PATH_SEPARATOR.ini_get("include_path"));
	if(in_array("ARC2.php", get_included_files()));
	include_once("ARC2.php");
	$parser = ARC2::getRDFParser();
	$parser->parse($datafile);
	$triples = $parser->getTriples();
	if(!$triples){
	$parser->parse('file://'.$datafile);
	$triples = $parser->getTriples();
	}
	return ($triples);

	}

function rdf2php($doc)
	{

	// Prepare RDF
	#$rdfInput = $data;

	// Show the submitted RDF

	// Create a new MemModel
	$model = ModelFactory::getDefaultModel();
	
	$model->load($doc);
	
	return ($model);

	}

function pushId($C)
	{$Id=array();
		for ($c=0; $c < count($C); $c++) {
		
		$tmpid = get_object_vars($C[$c]['?subj']);#there must be a better way to remove the emcapsulation... jujst haven't found it yet.
		$tmpid = $tmpid['uri'];
		
		array_push($Id, $tmpid);
		}	
	$Id = array_unique($Id);
	return ($Id);
}

function pushURI($C, $q,$LabelOrUri)
	{$Id=array();
		for ($c=0; $c < count($C); $c++) {
		
		$tmpid = get_object_vars($C[$c][$q]);#there must be a better way to remove the emcapsulation... jujst haven't found it yet.
		$tmpid = $tmpid[$LabelOrUri];
		
		array_push($Id, $tmpid);
		}	
	
	return ($Id);
}



?>