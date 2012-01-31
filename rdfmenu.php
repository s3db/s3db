<?php
	#project_page displays general information on the project;
	#Includes links to resource pages, xml and rdf export 
	#Helena F Deus (helenadeus@gmail.com)

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
	
$key = $_GET['key'];
#Get the key, send it to check validity

include_once('core.header.php');
#include all the javascript functions for the menus...
include('S3DBjavascript.php');
$action = $GLOBALS['action'];
if($key)
	$user_id = get_entry('access_keys', 'account_id', 'key_id', $key, $db);
	else
	$user_id = $_SESSION['user']['account_id'];

$project_info = URIinfo('P'.$project_id, $user_id, $key, $db);


?>

<table class="top" width = "25%">
<tr class="info"><td>How would you like your RDF</tr></td>
<tr class="info"><td><b>RDF/N3</b></td></tr>
<tr class="odd"><td><input type="button" value = "Just the Schema" onClick="window.location='<?php echo $action['rdfexport']; ?>'"></td></tr>
<tr class="even"><td><input type="button" value = "Schema & Data" onClick="window.location='<?php echo $action['rdfexport'].'&all'; ?>'"></td></tr>
<tr class="even"><td><input type="button" value = "Schema & Data & Permissions" onClick="window.location='<?php echo $action['rdfexport'].'&all&permissions'; ?>'"></td></tr>
<tr class="info"><td></td></tr>
<tr class="info"><td><b>RDF/XML (parsing by  <a href="http://simile.mit.edu/babel" target="_blank">Simile</a>. Requires online availalability.) </b></td></tr>
<tr class="odd"><td><input type="button" value = "Just the Schema" onClick="window.location='<?php echo $action['rdfexport'].'&output=xml-rdf'; ?>'"></td></tr>
<tr class="even"><td><input type="button" value = "Schema & Data" onClick="window.location='<?php echo $action['rdfexport'].'&all&output=xml-rdf'; ?>'"></td></tr>
<tr class="even"><td><input type="button" value = "Schema & Data & Permissions" onClick="window.location='<?php echo $action['rdfexport'].'&all&output=xml-rdf&permissions'; ?>'"></td></tr>
<tr class="info"><td><b>URL Links</b></td></tr>
<tr class="even"><td><input type="button" value = "Just the Schema" onClick="window.location='<?php echo $action['rdfexport'].'&link'; ?>'"></td></tr>
<tr class="even"><td><input type="button" value = "Schema & Data" onClick="window.location='<?php echo $action['rdfexport'].'&all&link'; ?>'"></td></tr>
<tr class="even"><td><input type="button" value = "Schema & Data & Permissions" onClick="window.location='<?php echo $action['rdfexport'].'&all&link&permissions'; ?>'"></td></tr>
</table>