<?php
#sparql serializer is an API that serializes a sparql query into an S3QL query

##HEADER: Read the file and include the functions for parsing the RDF
ini_set('display_errors',0);
	if($_REQUEST['su3d'])
	ini_set('display_errors',1);
if(file_exists('config.inc.php'))
	{
		include('config.inc.php');
	}
	else
	{
		Header('Location: index.php');
		exit;
	}
include('dbstruct.php');
@ini_set('allow_url_fopen', true);
@set_time_limit(0);
$x = ini_set('memory_limit', '500M');
#echo ini_get('memory_limit');exit;
@ini_set('upload_max_filesize', '128M');
@ini_set('post_max_size', '256M');
@ini_set('max_input_time', '-1');
@ini_set('max_execution_time', '3000');
@ini_set('expect.timeout', '-1');
@ini_set('default_socket_timeout', '-1');

ini_set('display_errors',0);
if($_REQUEST['su3d'])
ini_set('display_errors',1);

define("RDFAPI_INCLUDE_DIR", S3DB_SERVER_ROOT."/pearlib/rdfapi-php/api/");
include(RDFAPI_INCLUDE_DIR . "RdfAPI.php");
include(RDFAPI_INCLUDE_DIR . "syntax/SyntaxN3.php");
include(RDFAPI_INCLUDE_DIR . "syntax/SyntaxRDF.php");

if(is_array($argv))
foreach ($argv as $argin) {
	if(ereg('(format|url|noHTML)=(.*)', $argin, $zz))
		$in[$zz[1]] = $zz[2];
}
else {
	$in = $_REQUEST;	
}

$rdf = ($in['url']=='')?"http://ibl.mdanderson.org/TCGA/extras/NUavLcFy5fn6WsN/KfN1YnDBCN4dD6j.project126/TCGADATA.rdf":urldecode($in['url']);


$mysparql = '
PREFIX rdfs: <http://www.w3.org/2000/01/rdf-schema#>
PREFIX rdf: <http://www.w3.org/1999/02/22-rdf-syntax-ns#>
PREFIX : <http://ibl.mdanderson.org/TCGA/>
PREFIX s3db: <http://www.s3db.org/core#>
SELECT *
WHERE { ?x :R149 ?z FILTER regex(?z, "CGH") .
		}';
//PREFIX rdfs: <http://www.w3.org/2000/01/rdf-schema#>
//PREFIX rdf: <http://www.w3.org/1999/02/22-rdf-syntax-ns#>
//PREFIX : <http://http://ibl.mdanderson.org/TCGA/>
//PREFIX s3db: <http://www.s3db.org/core#>
//SELECT ?Item, ?Ilabel
//WHERE {?Item rdf:type :C186 .
//		?Item rdfs:label ?Ilabel .}
//
//";
//$mysparql = "
//PREFIX rdfs: <http://www.w3.org/2000/01/rdf-schema#>
//PREFIX rdf: <http://www.w3.org/1999/02/22-rdf-syntax-ns#>
//PREFIX : <http://http://ibl.mdanderson.org/TCGA/>
//PREFIX s3db: <http://www.s3db.org/core#>
//SELECT ?R,?Rlabel
//WHERE {?R rdfs:subClassOf :P126 .
//	   ?R rdf:type s3db:s3dbRule .
//	   ?R rdfs:label ?Rlabel .
//	   ?R rdf:subject :C186 .}
//
//";




$sparql = ($in['sparql']=='')?$mysparql:urldecode($in['sparql']);


##Interpret the query
$parsed = s3db_parse($sparql);
exit;







$out = ($in['format']=='')?'HTML':strtoupper($in['format']);

##serialize the data so that is does not take very long to re-load it 
$filename = md5($rdf);
$file_place = $GLOBALS['uploads'].'/';
if(!is_file($file_place.$filename)) {

#read the data into a model
$model = rdf2php($rdf);

if($model=='')
{
echo "dataset could not be parsed";
}
else
	{$s_model = serialize($model);
	
	file_put_contents($file_place.$filename, $s_model);
		
	}
}
else {
	$model = file_get_contents($file_place.$filename);
	$model = unserialize($model);
	
}

#echo '<pre>';print_r($model);
if(!$in['no_color']){
echo '
<html>
<head>

</head><body>
<form method="GET" action="'.$_SERVER['PHP-SELF'].'" id="sparqlform">
<h5>URL</h5>
<input type = "text" id="url" size = "100%" value="'.$in['url'].'" name="url">
<h5>SPARQL  <a href="http://www.w3.org/TR/rdf-sparql-query/" target="_blank">(help!!)</a></h5>
<br />

<textarea cols="100" id="sparql" rows="10" name = "sparql">'.stripslashes($sparql).'</textarea><br />
<input type="submit" value="SPARQL this!" id="submitsparql"></body>
</form>
';

}


$sparqlQueryFile = md5(urlencode($sparql)).date('Gis');
if(!is_file($file_place.$sparqlQueryFile))
{

	if(ereg('XML|HTML', $out))
	{
	#$incomingSparql = $sparql->_parseSparqlQuery($sparql);
	$find=$model->sparqlQuery($sparql, $out);
	
	echo $find;
	}
	else {
	$find=$model->sparqlQuery($sparql);
	#echo '<pre>';print_r($find);
	}
$s_find = serialize($find);
file_put_contents($file_place.$sparqlQueryFile, $s_find);

}
else {
	$s_find = file_get_contents($file_place.$sparqlQueryFile);
	$find = unserialize($s_find);
	if(ereg('XML|HTML', $out))
	{
	file_put_contents($GLOBALS['uploads'].'/sparqlResult.'.strtolower($out), $find);
	echo $find;
	}
	else {
	
	echo '<pre>';print_r($find);
	}

}


function rdf2php($doc)
	{

	// Prepare RDF
	$rdfInput = $data;

	// Show the submitted RDF

	// Create a new MemModel
	$model = ModelFactory::getDefaultModel();
	$model->load($doc);
	
	return ($model);

	}


function s3db_parse($queryString)
{
	if ($queryString) {
            $uncommentedQuery = s3db_uncomment($queryString);
            $tokenized = s3db_tokenize($uncommentedQuery);
            echo '<pre>';print_r($tokenized);exit;
			$parsed = parseQuery($tokenized);
        
		} else {
            return ("Querystring is empty.");
		}
}

function s3db_uncomment($queryString)
    {
        // php appears to escape quotes, so unescape them
          $queryString = str_replace('\"',"'",$queryString);
          $queryString = str_replace("\'",'"',$queryString);

        $regex ="/((\"[^\"]*\")|(\'[^\']*\')|(\<[^\>]*\>))|(#.*)/";
        return preg_replace($regex,'\1',$queryString);
    }

function s3db_tokenize($queryString)
    {
        $queryString = trim($queryString);
        $specialChars = array(" ", "\t", "\r", "\n", ",", "(", ")","{","}",'"',"'",";","[","]");
        $len = strlen($queryString);
        $this['tokens'][0]='';
        $n = 0;
        for ($i=0; $i<$len; ++$i) {
            if (!in_array($queryString{$i}, $specialChars)) {
                $this['tokens'][$n] .= $queryString{$i};
            } else {
                if ($this['tokens'][$n] != '') {
                    ++$n;
                }
                $this['tokens'][$n] = $queryString{$i};
                $this['tokens'][++$n] = '';
            }
        }
		return ($this);
    }

function parseQuery($tokenizedQuery)
    {
        do {
            switch (strtolower(current($tokenizedQuery['tokens']))) {
                case "base":
                    $this->parseBase();
                    break;
                case "prefix":
                    $this->parsePrefix();
                    break;
                case "select":
                    $this->parseSelect();
                    break;
                case "describe":
                    $this->parseDescribe();
                    break;
                case "ask":
                    $this->parseAsk('ask');
                    break;
                case "count":
                    $this->parseAsk('count');
                    break;
                case "from":
                    $this->parseFrom();
                    break;
                case "construct":
                    $this->parseConstruct();
                    break;
                case "where":
                    $this->parseWhere();
                    $this->parseModifier();
                    break;
                case "{":
                    prev($this->tokens);
                    $this->parseWhere();
                    $this->parseModifier();
                    break;
            }
        } while (next($this->tokens));

    }


?>