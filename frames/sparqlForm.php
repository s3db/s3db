<?php
/**
	
	* @author Helena F Deus <helenadeus@gmail.com>
	* @license http://www.gnu.org/copyleft/gpl.html GNU General Public License
	* @package S3DB http://www.s3db.org
*/
##Display a form for submitting a sparql query and a target deployment
ini_set('display_errors',0);
	if($_REQUEST['su3d'])
	ini_set('display_errors',1);
if(file_exists('../config.inc.php'))
	{
		include('../config.inc.php');
	}
	else
	{
		Header('Location: ../index.php');
		exit;
	}

$section_num = '5';
$website_title = $GLOBALS['s3db_info']['server']['site_title'].' - S3DB SPARQL endpoint';
$content_width='80%';

#include(S3DB_SERVER_ROOT.'/dbstruct.php');
include_once(S3DB_SERVER_ROOT.'/core.header.php');
include_once(S3DB_SERVER_ROOT.'/rdfheader.inc.php');
include_once(S3DB_SERVER_ROOT.'/s3dbcore/sparql_read.php');

##Add some style into the page
include(S3DB_SERVER_ROOT.'/s3style.php');

if(is_array($argv))
foreach ($argv as $argin) {
	if(ereg('(format|url|noHTML)=(.*)', $argin, $zz))
		$in[$zz[1]] = $zz[2];
}
else {
	$in = $_REQUEST;	
}
$start = strtotime(date('His'));

#
##This is the default query. It will immediatelly execute once the user clicks on the "SPARQL" tab
#
$in['Did'] = ($in['Did']!='')?$in['Did']:S3DB_URI_BASE;
$in['url'] = $in['Did'];

$prefixes = ($in['prefixes']!='')?$in['prefixes']:'PREFIX rdfs: <http://www.w3.org/2000/01/rdf-schema#>
PREFIX rdf: <http://www.w3.org/1999/02/22-rdf-syntax-ns#>
PREFIX s3db: <http://www.s3db.org/core.owl#> 
PREFIX : <'.$in['Did'].((substr($in['Did'], strlen($in['Did'])-1, 1)=='/')?'':'/').'>';

$target = ($in['Did']!='')?$in['Did']:S3DB_URI_BASE;

$in['from'] = explode(',',$target);

foreach ($in['from'] as $fromURI) {
	$targets .= "FROM <".trim($fromURI)."> ";
}

$in['select'] = ($in['sparql']!='')?$in['sparql']:'
SELECT * WHERE { 
?project_id a s3db:s3dbProject .
OPTIONAL { ?project_id rdfs:label ?ProjectName . }
OPTIONAL { ?project_id rdfs:comment ?ProjectNotes . }
}';


$in['query'] = $prefixes.$in['select'];
$in['format']='php';

$I = compact('in', 'user_id','db');
$sparqlOutput = sparql($I);

$sparqlData = unserialize($sparqlOutput);
if(is_array($sparqlData)){
$cols = array_keys($sparqlData[0]);

$data2display = s3db_display($sparqlData, $cols);

#$z = array('data'=>$sparqlData,'cols'=>$cols, 'format'=>$in['format']);
#$data2display = outputFormat($z);
}
echo '
		<html>
		<head>

		</head>
		<body>
		<form method="GET" action="sparqlForm.php" id="sparqlform">
		<table class="top">
			<tr class="oddbold">
				<td>SPARQL  <a href="http://www.w3.org/TR/rdf-sparql-query/" target="_blank">(help!!)</a></td>
			</tr>
			
			<tr class="box">
				<td><textarea cols="100" id="sparql" rows="10" name = "sparql">'.stripslashes($in['select']).'</textarea><br /></td>
			</tr>
			<tr>
			<td><br /></td>
			</tr>
			<tr class="oddbold">
				<td><a href="" title="You may specify where you want your query to be targeted or, by default, local deployment will be used. Use commas to separate all your target deployments (for example http://xxx/s3db, http://yyy/s3db)">Target Deployment</a></td>
			</tr>
			<tr class="box">
				<td><input type="hidden" name="key" value="'.$in['key'].'"/>
				<input type="hidden" name="format" value="'.$in['format'].'"/>
				<input type = "text" id="Did" size = "100%" value="'.$in['Did'].'" name="Did">
				</td>
			</tr>
			<tr>
			<td><br /></td>
			</tr>
			<tr class="oddbold">
				<td><a href="#" title="Prefixes may be used in the query as short surrogates for long URI. You may add your own prefixes here">Prefixes</a></td>
			</tr>
			<tr class="box">
				<td>
				<textarea id="prefixes" cols = "100" rows="3" name="prefixes" style="background-color: #F4F4F4">'.$prefixes.'
				</textarea>
				</td>
			</tr>
			<tr>
			<td><br /></td>
			</tr>
			
			<tr>
				<td><input type="submit" value="SPARQL this!" id="submitsparql"></td>
			</tr>
			<tr>
			<td><br /></td>
			</tr>
			<tr>
			<td>Query took '.(strtotime(date('His'))-$start).' sec</td>
			</tr>
			
			<tr>
			<td>'.$data2display.'</td>
			</tr>



		</table>
				
		';
		
if($_REQUEST['callback'])##serialize and put in a javscript var
{


##
$varName =ereg_replace('^\?','',$_REQUEST['callback']);

##Create a JSON variable that can be read and interpreted by the javascript in the other frame


if(is_array($sparqlData)){
$newLeaf = array();

foreach ($sparqlData as $key=>$data) {
$coreVar = isS3DBCore($data[$_REQUEST['callback']]);
$forJS = $coreVar['letter'].$coreVar['value'];
switch ($coreVar['letter']) {
	case 'D':
		$nextCallback = '?project_id';
		$nextVar = ereg_replace('^\?','',$nextCallback);
		$newLeaf[$key][1] = '[ "'.$forJS.'", 0 ]';
		$newLeaf[$key][2] = '[ "Projects", "../frames/sparqlForm.php?query=select distinct ?project_id where { '.$nextCallback.' a s3db:s3dbProject . }&callback=?project_id&leadInd='.$key.'"];';
		
	
	break;
	
	case 'P':
		$nextCallback = '?rule_id';
		$nextVar = ereg_replace('^\?','',$nextCallback);
		$newLeaf[$key][1] = '[ "'.$forJS.'", 0 ]';
		$newLeaf[$key][2] = '[ "Collections", "../frames/sparqlForm.php?query=select distinct ?collection_id where { ?collection_id a s3db:s3dbCollection . ?collection_id rdfs:subClassOf :'.$forJS.' . }&callback=?collection_id&leadInd='.$key.'"];';
		$newLeaf[$key][3] = '[ "Rules", "../frames/sparqlForm.php?query=select distinct ?rule_id where { ?rule_id a s3db:s3dbRule . ?rule_id rdfs:subClassOf :'.$forJS.' . }&callback=?rule_id&leadInd='.$key.'"];';
		
		break;
	
	case 'C':
		$nextCallback = '?item_id';
		$nextVar = ereg_replace('^\?','',$nextCallback);
		$newLeaf[$key][1] = '[ "'.$forJS.'", "../frames/sparqlForm.php?query='.urlencode('select * where { '.$nextCallback.' a s3db:s3db'.$GLOBALS['COREidsInv'][$nextVar].' .  }').'&callback='.$nextCallback.'&leadInd='.$key.'" ]';
		$newLeaf[$key][2] = '[ "Items", "../frames/sparqlForm.php?query=select distinct ?item_id where { ?item_id a s3db:s3dbItem . ?item_id rdf:type :'.$forJS.' . }&callback=?item_id&leadInd='.$key.'"];';
		$newLeaf[$key][3] = '[ "Rules", "../frames/sparqlForm.php?query=select distinct ?rule_id where { ?rule_id a s3db:s3dbRule . ?rule_id rdfs:subject :'.$forJS.' . }&callback=?statement_id&leadInd='.$key.'""];';
		
		break;
	default :
}

if($key!=max(array_keys($sparqlData))) $newLeaf[$key][1] .= ';';
}

$_SESSION[$_REQUEST['callback']] = $newLeaf;
#$newVar = 'zzz.js';
#file_put_contents('../tigre_tree/'.$newVar,serialize($newLeaf));
#chmod('../tigre_tree/'.$newVar,0777);
#echo $leaf;exit;
echo '<script type="text/javascript">parent.sparqltrial.location = (\'../tigre_tree/sparqlTrial.php?newLeaf='.base64_encode(serialize($newLeaf)).'&callback='.$_REQUEST['callback'].'\')</script>';
}
/*
if(is_array($sparqlData)){
$leaf = 'var newLeaf = [];';

foreach ($sparqlData as $key=>$data) {
$coreVar = isS3DBCore($data[$_REQUEST['callback']]);
$forJS = $coreVar['letter'].$coreVar['value'];
$leaf .= 'newLeaf['.$key.'] = [ "'.$forJS.'", "../frames/sparqlForm.php?query='.urlencode('select * where { ?project_id a s3db:s3dbProject .  }').'&callback='.$nextCallback.'&leadInd='.$key.'" ]';
if($key!=max(array_keys($sparqlData))) $leaf .= ';';
}

$newVar = 'zzz.js';
file_put_contents('../tigre_tree/'.$newVar,$leaf);
chmod('../tigre_tree/'.$newVar,0777);
#echo $leaf;exit;
echo '<script type="text/javascript">parent.sparqltrial.location = (\'../tigre_tree/sparqlTrial.php?newLeaf='.$newVar.'&callback='.$_REQUEST['callback'].'\')</script>';
}

*/

##Create a javascript var that will be sent to the other frame
//echo '<script type="text/javascript" src="../tigre_tree/coreTree.js"></script>';
//
//
//
//foreach ($sparqlData as $key=>$data) {
//	$coreVar = isS3DBCore($data[$_REQUEST['callback']]);
//	$forJS = $coreVar['letter'].$coreVar['value'];
//	
//	#Depending on the callback, we'll be changing diferent portions of the tree
//	#Now reload the other frame with the new tree
//	switch ($varName) {##deployment_id corresponds to index 
//		case 'deployment_id':
//			$Branch = 0;
//			$FirstLeaf = 3;
//			$Leaf = '["'.$forJS.'", "../frames/sparqlForm.php?query=select * where { ?project_id a s3db:s3dbProject . ?project_id rdfs:subClassOf :'.$forJS.' . }&callback=?project_id"]';
//			break;
//
//		default :
//	}
//echo '<script type="text/javascript">TREE_ITEMS['.$Branch.']['.$FirstLeaf.'] = '.$Leaf.'</script>';
//
//$FirstLeaf++;
//}
//
//echo '<script type="text/javascript">setCookie("TREE_ITEMS",TREE_ITEMS,7);</script>';
//
//$treePhp ='<script type="text/javascript">alert(TREE_ITEMS);</script>';
//file_put_contents('../tigre_tree/sparqlNewTree.js',$treePhp);
//chmod('../tigre_tree/sparqlNewTree.js',0777);
//
//##And now reload the tree with the new sparql tree
//#Iclude tree_tpl
//echo '<script language="JavaScript" src="../tigre_tree/sparql_tree.js"></script>';
//echo '<script language="JavaScript" src="../tigre_tree/tree.js"></script>';
//#echo '<script type="text/javascript">parent.sparqltrial.TREE_ITEMS = parent.sparqlform.TREE_ITEMS</script>';
//echo '<script type="text/javascript">parent.sparqltrial.location = (\'../tigre_tree/sparqlTrial.php?newTree=sparqlNewTree.js\')</script>';
#echo '<script type="text/javascript">var php=js2php (TREE_ITEMS); </script>';
#echo '<script type="text/javascript">setCookie("treePHP",php,7);</script>';
#echo '<pre>';print_r($_COOKIE);

#echo '<pre>';print_r($treePhp);

}
#echo s3db_display($sparqlData, $cols);
exit;
?>
