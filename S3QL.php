<?php
#S3QL reads query strings in XML and returns an output in html or tab

	#Syntax of the XML query: #S3QL.php?query=<select>collections</select><where><project_id>xxx</project_id><where>
	#S3QL.php?query=<select>rules</select><where><project_id>xxx</project_id><where>
	#S3QL.php?query=<select>items</select><where><project_id>xxx</project_id><lass_id>yyy</class_id><where>
	#http://s3db.org/documentation

	#Helena F Deus, November 8, 2006

ini_set('display_errors',0);
if($_REQUEST['su3d'])
ini_set('display_errors',1);

	

#Endpoint validation.

if(file_exists('config.inc.php'))
	{
	include('config.inc.php');
	$s3ql['connection'] = 'successfull';
	
	}
	else
	{
	$s3ql['connection'] = 'unsuccessfull';
	exit;
	}

#Get the key, send it to check validity
#When the key goes in the header URL, no need to read the xml, go directly to the file
include_once(S3DB_SERVER_ROOT.'/dbstruct.php');
include_once(S3DB_SERVER_ROOT.'/s3dbcore/display.php');
include_once(S3DB_SERVER_ROOT.'/s3dbcore/callback.php');

#Profilling... 

require_once S3DB_SERVER_ROOT.'/pearlib/PEAR.php';
if(is_file(S3DB_SERVER_ROOT.'/pearlib/Benchmark/Timer.php')){
require_once S3DB_SERVER_ROOT.'/pearlib/Benchmark/Timer.php';
$timer = new Benchmark_Timer();
$timer->start();
}
ereg('query=(.*)(&amp;)*', $_SERVER['argv'][0], $tmp);
$query = ($_REQUEST['query']!='')?$_REQUEST['query']:html_entity_decode($tmp[1]);
#echo $query;exit;
$s3ql = readInputMessage($query, $timer); #read the message from the URL input;
#echo 'ola<pre>';print_r($s3ql);

$key=$s3ql['key'];
if(eregi('on|^t|true',$s3ql['graph'])) $complete = true;

include_once('s3ql.header.php');#core.header.php manages all the user authentication
	$format = ($s3ql['format']!='')?$s3ql['format']:$_REQUEST['format'];
	if($format=='') $format='html';
	$s3ql['format'] = $format;
	
	#these represent all the queries that can be performed on the tables
	$table = $GLOBALS['s3tables'][$s3ql['from']];

	

	if ($s3ql['insert']=='user' && $s3ql['where']['password']!='') {
		
			echo formatReturn('6','Password cannot be inserted via the API. Please leave this field empty and a random password will be sent to the user\'s email',$format,'');
		}
	if (($s3ql['edit']=='user'|| $s3ql['update']=='user') && ($s3ql['set']['password']!='' || $s3ql['set']['email']!='')) {
		
			echo formatReturn('6','Password and email cannot be changed via the API.',$format,'');
			
	}
	
	#input the struct into the S3QLaction function
	$s3ql['db'] = $db;
	$s3ql['user_id'] = $user_id;
	$s3qlOut = S3QLaction($s3ql,$timer);

	if(!is_array($s3qlOut))
	{	echo ($s3qlOut);
		exit;
	}
	elseif(ereg('rulelog|statementlog|accesslog|permission',$s3ql['from']))
	{
	 $letter ='E';

	}
	else {
	$letter = letter($s3ql['from']);
	}
	#When the result is an array, rules or any other, run display to give values as tab delimited
	
	$data = $s3qlOut;
	
	
	$t=$GLOBALS['s3codes'][$letter];
	$t=$GLOBALS['plurals'][$t];
		
	$pack= compact('s3qlOut','data','s3ql','letter', 'returnFields','t','format', 'db','timer','complete');
	#echo '<pre>';print_r($pack);
	
	if($format=='json'){
	header("HTTP/1.1 200 OK ");
	header("Content-Type: text/javascript");
	}
	
	echo completeDisplay($pack);
	if($_REQUEST['su3d'])
	{
		echo "Total results: ".count($data);
		if($timer) 
			$timer->display();
	}
	exit;


#finish key valid
 function readInputMessage($query)
{
#echo '<pre>';print_r($$_SERVER[]);

$xml = stripslashes($query);

if($action=='')
	$action='select';

#Determine if XML is a URL or a string
	
	if (ereg('^http.*', $xml))
	{
	
	$xmlFile= @file_get_contents($xml);
	
	
	if(empty($xmlFile))
		{
		echo (formatReturn($GLOBALS['error_codes']['something_missing'],'Query file is not valid', $_REQUEST['format'],''));
		exit;
		}
	else {
		$xml = $xmlFile;
	}
	
	
	}
	elseif(ereg('^(select|insert|edit|update|grant)', $query, $action)) {
	
	#it is text, read it frmo text	
	
	$Q = explode(' ', $query);


	#if(ereg('^(projects|rules|classes|instances|statements|users|groups|keys)', $Q[1]))
	if(in_array($Q[1], array_keys($GLOBALS['s3input'])))
		{
		
		$s3ql['from']=$Q[1];
		if(array_search("in", $Q)){
		$where_ind = array_search("in", $Q);
		$s3ql['where'][$Q[$where_ind+1]] = $Q[$where_ind+2];}
		
		}
	else
		{
		$s3ql[$action[1]] = $Q[1];
		}
		
		if(array_search("in", $Q)){
		$where_ind = array_search("in", $Q);
		$s3ql['where'][$Q[$where_ind+1]] = $Q[$where_ind+2];
		}

		if(array_search("where", $Q)){
		$where_ind = array_search("where", $Q);
		if($Q[$where_ind+2]=='=')
		$s3ql['where'][$Q[$where_ind+1]] = $Q[$where_ind+3];
		else {
			$s3ql['where'][$Q[$where_ind+1]] = $Q[$where_ind+2].$Q[$where_ind+3];
		}
		}
		
		
	
	#find a "from". If there is one, then the from and the next for a key=>value pair
	
	if(array_search('from', $Q))
		$s3ql['from'] = $Q[array_search('from', $Q)];
		
	if(array_search('where', $Q)) #if there is a 'where' in the array, them capture the following field=>value pairs
		{$pairsA = range(array_search('where', $Q)+1, count($Q)+1, 4);
		$pairsB = range(array_search('where', $Q)+2, count($Q)+2, 4);
		$equality = range(array_search('where', $Q)+3, count($Q)+3, 4);
		$intersect = range(array_search('where', $Q)+2, count($Q)+4, 4);
		}

	}

	#clean up values that might affect parsing the xml
	ereg('<value>(.*)</value>', $xml, $val);
	if($val[1]!='')
		$xml = str_replace('<value>'.$val[1].'</value>', '<value>'.base64_encode($val[1]).'</value>', $xml);
	ereg('<notes>(.*)</notes>', $xml, $notes);
	if($notes[0]!='')
		{$xml = str_replace('<notes>'.$notes[1].'</notes>', '<notes>'.base64_encode($notes[1]).'</notes>', $xml);
		}
	if($xml!=''){
	
	try {
    $tmp = @simplexml_load_string($xml);
	if(!$tmp){
		$tmp = @simplexml_load_string(urldecode($xml));
	}
	
	
	if(!$tmp) {
        throw new Exception(formatReturn($GLOBALS['error_codes']['something_went_wrong'],'XML query is badly formatted. Please check your start/end tags', $_REQUEST['format'],''));
    }
	#$timer->setMarker('XML parsed');

	}
	catch(Exception $e) {
		
		print $e->getMessage();
		exit;
	}
	
	$xml = $tmp;

	#When there is no XML, rely on GET
	$s3ql = $xml;
	$s3ql = get_object_vars($s3ql);
	
	#echo '<pre>';print_r($s3ql);	
	#strtolower
	foreach ($s3ql as $attr=>$attrvalue) {
								
		if(is_object($s3ql[$attr])){
			$tmp = get_object_vars($s3ql[$attr]);
			
			foreach ($tmp as $newattr=>$newvalue) {
				$Ls3ql[strtolower($attr)][strtolower($newattr)]=$newvalue;
				
			}
			
			
		}
		else {
			$Ls3ql[strtolower($attr)] = $attrvalue;
			
		}
	}
	$s3ql = $Ls3ql;
	
	$s3ql['key'] = ($s3ql['key']!='')?$s3ql['key']:$_REQUEST['key'];
	
	
	if(get_object_vars($s3ql['where'])!='')
	$s3ql['where'] = get_object_vars($s3ql['where']);
	elseif($_REQUEST['where']!='')
	$s3ql['where'] = $_REQUEST['where'];
	
	
	if($s3ql['where']['value']!='')
		$s3ql['where']['value'] = base64_decode($s3ql['where']['value']);
	if($s3ql['where']['notes']!='')
		$s3ql['where']['notes'] = base64_decode($s3ql['where']['notes']);
	#echo '<pre>';print_r($s3ql);exit;
	#if(get_object_vars($s3ql['where']['or'])!='')
	#$s3ql['where']['or'] = get_object_vars($s3ql['where']['or']);
	#elseif($_REQUEST['where']['or']!='')
	#$s3ql['where']['or'] = $_REQUEST['where']['or'];
	
	if(get_object_vars($s3ql['set'])!='')
		{$s3ql['set'] = get_object_vars($s3ql['set']);
		if($s3ql['set']['value']!='')
		$s3ql['set']['value'] = base64_decode($s3ql['set']['value']);
		if($s3ql['set']['notes']!='')
		$s3ql['set']['notes'] = base64_decode($s3ql['set']['notes']);
		
		}
	elseif($_REQUEST['set']!='')
		$s3ql['set']=$_REQUEST['set'];

	$s3ql['select']=($s3ql['select']!='')?$s3ql['select']:(($_REQUEST['select']!='')?$_REQUEST['select']:(((is_array($s3ql) && in_array('from', array_keys($s3ql))))?'*':''));
	
	$s3ql['from']=($s3ql['from']!='')?$s3ql['from']:(($_REQUEST['from']!='')?$_REQUEST['from']:'projects');
	
	if($s3ql=='') 
	{$s3ql = $_GET;
	}

	if($s3ql['format']=='' && $_REQUEST['format']!='')
	$s3ql['format'] = $_REQUEST['format'];
	#interpred the "or" and  "and"
	}
	else {
		$s3ql['select']='*';
		$s3ql['from']='projects';
	}
#echo '<pre>';print_r($s3ql);
return $s3ql;

}


?>