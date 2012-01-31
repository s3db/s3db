<?php
ini_set('display_errors',0);
	if($_REQUEST['su3d'])
	ini_set('display_errors',1);
if(file_exists('config.inc.php'))
	{
		include('config.inc.php');
	}
	else
	{
		Header('Location: login.php?error=7');
		exit;
	}

$db_engine  = $GLOBALS['s3db_info']['server']['db']['db_type'];
$hostname =$GLOBALS['s3db_info']['server']['db']['db_host'];
$dbname = $GLOBALS['s3db_info']['server']['db']['db_name'];
$user = $GLOBALS['s3db_info']['server']['db']['db_user'];
$pass = $GLOBALS['s3db_info']['server']['db']['db_pass'];

if ($db_engine == 'mysql')
{
$connect = mysql_connect($hostname, 'root', '');
if (!$connect) {
   die('Could not connect: ' . mysql_error());
}
$sql = "create database ".$dbname."";
mysql_query($sql, $connect);

$sql = "grant all privileges on ".$dbname.".* to ".$user." identified by '".$pass."'";
mysql_db_query($dbname, $sql, $connect);

$sql = 'flush privileges';
mysql_db_query($dbname, $sql, $connect);

mysql_close ($connect);

}

?>