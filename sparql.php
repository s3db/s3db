<?php
/**
	
	* @author Helena F Deus <helenadeus@gmail.com>
	* @license http://www.gnu.org/copyleft/gpl.html GNU General Public License
	* @package S3DB http://www.s3db.org
*/
#sparql.php is an API that serializes a sparql query into an S3QL query
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


#include(S3DB_SERVER_ROOT.'/dbstruct.php');
include_once(S3DB_SERVER_ROOT.'/core.header.php');
include_once(S3DB_SERVER_ROOT.'/rdfheader.inc.php');
include_once(S3DB_SERVER_ROOT.'/s3dbcore/sparql_read7.php');
if(is_file(S3DB_SERVER_ROOT.'/pearlib/Benchmark/Timer.php')){
require_once S3DB_SERVER_ROOT.'/pearlib/Benchmark/Timer.php';
$timer = new Benchmark_Timer();
$timer->start();
}

#include(S3DB_SERVER_ROOT.'/html2cell.php');
if(ereg('^query=&',$_SERVER['argv'][0]))
	  $_SERVER['argv'][0] = ereg_replace('^query=&','query=?',$_SERVER['argv'][0]);

if(is_array($argv))
foreach ($argv as $argin) {
	if(ereg('(format|url|noHTML)=(.*)', $argin, $zz))
		$in[$zz[1]] = $zz[2];
}
else {
	$in = $_REQUEST;
	##this piece does not seem to make sense any more... I wonder why i needed it?!
	#ereg('query=(.*)$',$_SERVER['argv'][0],$tmp);
	
	if($tmp)  $in['query'] = urldecode($tmp[1]);
}
$in['query'] = stripslashes($in['query']);
$default_uri = S3DB_URI_BASE.((substr(S3DB_URI_BASE, strlen(S3DB_URI_BASE)-1, 1)!='/')?'/':'');


if(ereg('^(http.*)|(.*)\.(spql)',$in['query'],$tmp)){
$a= file_get_contents($tmp[0]);
	if($a) $in['query'] = $a;
	else {echo "Query file is invalid.";exit;}
}

if(!$in['query']){
echo "Please specify some SPARQL query using the syntax sparql.php?query=... .";exit;
}

$clean = false;
$complete=true;
$goparallel=false;
if($_REQUEST['link']) $link=1;
elseif($_REQUEST['redirect']) $redirect=1;
if($_REQUEST['complete']) $complete = $_REQUEST['complete'];
if($_REQUEST['clean']) $clean = $_REQUEST['clean'];
if($_REQUEST['goparallel']) $goparallel = $_REQUEST['goparallel'];
$format = ($_REQUEST['format']!='')?$_REQUEST['format']:'sparql-xml';

$I = compact('in', 'user_id','db','default_uri','complete','goparallel','link', 'redirect', 'clean', 'format');

list($valid, $result) = sparql($I);
#list($valid, $result) = sparql_api($I);

if($redirect){
	
	Header("Location: ".$result);
	exit;
}
if($link){
	echo "Your RDF document is available at ".S3DB_URI_BASE.ereg_replace('^'.S3DB_SERVER_ROOT, "",$result);
	exit;
}
if($_REQUEST['out']=='header' || ($in['format']=='json'))
	{
	header("HTTP/1.1 200 OK ");
	header("Content-Type: text/javascript");
	#header("Content-length: 1000");
	#header('Proxy-Connection: keep-alive');
	#header('Connection: keep-alive');
	}
echo $result;

#echo sparql_arc($I);#old version
exit;


?>