<!doctype html public "-//w3c//dtd html 4.0 transitional//en">
<html>
<head>
	<title> s3db  </title>
<?php
	/*
	 * @author Helena F Deus <helenadeus@gmail.com>
	 * @license http://www.gnu.org/copyleft/gpl.html GNU General Public License
	 * @package S3DB http://www.s3db.org
	 * 
	 */
	ini_set('display_errors',0);
	if($_REQUEST['su3d']) {
		ini_set('display_errors',1);
	}

	if(file_exists('config.inc.php')) {
		include('config.inc.php');
	} else {
		echo '<meta http-equiv="refresh" content= "0; target="_parent" url="login.php?error=2">';
		exit;
	}
	
	//Get the key, send it to check validity
	$key = $_GET['key'];
	include_once('core.header.php');
	if(!$key) $key=get_user_key($_SESSION['user']['account_id'], $_SESSION['db']);
	//$args ='?key='.$_request['key'].'&url='.$_request['url'].'&project_id='.$_request['project_id'].'&resource_id='.$_request['resource_id'];
	
	//include_once 'webActions.php';
	echo '<frameset  rows="20%,80%" border="2">';
	echo '<frame src="'.$action['header'].'&section_num=5" name="header">';
	//echo '<frame src="'.$action['sparqlform'].'" name="sparqlframes" border="1">'; 
	echo '<frame src="'.$action['sparqlform'].'&key='.$key.'" name="sparqlframes" border="1">'; 
	echo '</frameset>';
?>
</head>
<body>
</body>
</html>