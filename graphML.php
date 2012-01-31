<?php
#map/index displays the map with input from the core
	#Includes links to resource pages, xml and rdf export 
	#Helena F Deus (helenadeus@gmailo.com)
	ini_set('display_errors',1);
	if($_REQUEST['su3d'])
	ini_set('display_errors',0);

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
$key = $_REQUEST['key'];
include_once('core.header.php');

##Find the projecct
if($_REQUEST['project_id']==''){
echo formatReturn($GLOBALS['error_codes']['something_missing'], "Please provide a project_id.", $format);
exit;
}

$project_id = $_REQUEST['project_id'];
$project_info = URIinfo('P'.$project_id, $user_id, $key, $db);
if(!$project_info['view']){

echo  formatReturn($GLOBALS['error_codes']['no_permission_message'], "You don't have permission to view this project.", $format);
exit;
}
##$getAllRules
$s3ql=compact('user_id','db');
$s3ql['from']='rule';
$s3ql['where']['project_id']=$project_id;

$rules=S3QLaction($s3ql);

##now separate the the rules, get the collections an drules
$nodes=array();
$node_names=array();
$edges=array();
foreach ($rules as $rule_info) {
	
	if(!in_array($rule_info['subject_id'], array_keys($nodes)))
	{
	$nodes[$rule_info['subject_id']]['name'] = $rule_info['subject'];
	
	}
	if($rule_info['object_id']!='' && !in_array($rule_info['object_id'], $nodes))
	{
	$nodes[$rule_info['object_id']]['name'] = $rule_info['object'];
	$nodes[$rule_info['object_id']]['color'] = 'red';
	$edges[$rule_info['rule_id']]['target'] = $rule_info['object_id'];

	}
	else {
		##non-object_id also deserve a node :)
		$nodes[$rule_info['rule_id']]['name'] = $rule_info['object'];
		$nodes[$rule_info['rule_id']]['color'] = 'green';
		$edges[$rule_info['rule_id']]['target'] = $rule_info['object'];
	}
	$edges[$rule_info['rule_id']]['source'] = $rule_info['subject_id'];
	$edges[$rule_info['rule_id']]['verb'] = $rule_info['verb'];


}

$graphML = '
<?xml version="1.0" encoding="UTF-8"?>
<graphml xmlns="http://graphml.graphdrawing.org/xmlns"  
    xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
    xsi:schemaLocation="http://graphml.graphdrawing.org/xmlns
     http://graphml.graphdrawing.org/xmlns/1.0/graphml.xsd">
  <key id="d0" for="node" attr.name="color" attr.type="string"/>
  <key id="d1" for="edge" attr.name="weight" attr.type="double"/>
  <key id="d2" for="node" attr.name="labels" attr.type="string"/>
  <graph id="G" edgedefault="undirected">';
  
#now the fun part, building the final XML
foreach ($nodes as $node_id=>$node_info) {
	$graphML .= '
	<node id="'.$node_id.'">
		<data key="d0">'.$node_info['color'].'</data>
		<data key="d2">'.$node_info['name'].'</data>
	</node>';
}

#now edges
foreach ($edges as $edge_id=>$edge_info) {
	$graphML .= '
	<edge id="'.$edge_id.'" source="'.$edge_info['source'].'" target="'.$edge_info['target'].'">
		<data key="d1">'.$edge_info['verb'].'</data>
	</edge>';
}

$graphML .= '
	</graph>
</graphml>';

echo $graphML;
?>