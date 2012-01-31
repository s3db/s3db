<?php
#rdfRestore.php reads and rdf file, removes the embedded data, and writes it to s3db
ini_set('display_errors',0);
if($_REQUEST['su3d'])
ini_set('display_errors',1);

if(file_exists('config.inc.php')){
		include('config.inc.php');
		
}
else {	
	Header('Location: login.php');
		exit; 
}
@set_time_limit(0);
@ini_set('memory_limit', '256M');
@ini_set('upload_max_filesize', '128M');
@ini_set('post_max_size', '256M');
@ini_set('max_input_time', '-1');
@ini_set('max_execution_time', '-1');
@ini_set('expect.timeout', '-1');
@ini_set('default_socket_timeout', '-1');


if($argv!=''){
	#whn the script is called via CLI, we need a direc way to accept the inputs. The syntax will be the saem (attr-value pairs)
	$inputsOrder = array('key','file','outputOption','uid');
	
	for ($i=1; $i <count($argv) ; $i++) {
		list($keyWord, $val) = explode('=',$argv[$i]);
		
		$inputs[$keyWord] = $val;
	}
	}
	else {
		$inputs['key'] = $_GET['key'];
		$inputs['file']=$_REQUEST['file'];
		$inputs['load']=$_REQUEST['load'];
		if($inputs['key']=='') $inputs['key'] = $s3ql['key'];
	}
 
$key=$inputs['key'];
include_once('core.header.php');
include('dbstruct.php');
$file=$inputs['file'];
$F = compact('file','db','user_id', 'inputs');

rdfRestore($F);

if($_SESSION['db']!='')
		{
		Header('Location:'.$GLOBALS['action']['listprojects']);
		exit;
		}

?>