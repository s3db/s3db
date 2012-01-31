<?php
#redirect me to my IP
ini_set('display_errors',0);
	if($_REQUEST['su3d'])
	ini_set('display_errors',1);
$cwd = dirname($_SERVER['SCRIPT_FILENAME']);
	define('S3DB_SERVER_ROOT', $cwd);
	$uri_base = substr($_SERVER['REQUEST_URI'], 0, strrpos($_SERVER['REQUEST_URI'], '/')); 	
	define('S3DB_URI_BASE', $uri_base);
	if (!file_exists('./config.inc.php'))
	{
		Header('Location: setup.php');
		/*$pwd =`pwd`;	
		die('<b>S3DB</b> Configuration file "config.inc.php" not found in '.$pwd.'. Please copy "config.inc.php-dist" to "config.inc.php" and adjust it according to your needs!');*/
		exit;	
		
	}
	include('./config.inc.php');
	//echo S3DB_URI_BASE;
	
	$GLOBALS['sessionid'] = isset($_GET['sessionid'])? $_GET['sessionid'] : $_COOKIE['sessionid'];
	if (! $GLOBALS['sessionid'])
	{
		$schema = strcasecmp($_SERVER['HTTPS'], 'on')?'http':'https';
		Header('Location:'.$schema.'://'.$_SERVER['HTTP_HOST'].S3DB_URI_BASE.'/login.php');
		//Header('Location: login.php');
		exit;
	}
	else 
	{
		Header('Location: home.php');
	}

function http_test_existance($url) {
 return (($fp = @fopen($url, 'r')) === false) ? false : @fclose($fp);
}


?>
