<?
$db_engine  = $GLOBALS['s3db_info']['server']['db']['db_type'];
$hostname =$GLOBALS['s3db_info']['server']['db']['db_host'];
$dbname = $GLOBALS['s3db_info']['server']['db']['db_name'];
$user = $GLOBALS['s3db_info']['server']['db']['db_user'];
$pass = $GLOBALS['s3db_info']['server']['db']['db_pass'];

if ($db_engine == 'mysql')
{
$connect = mysql_connect($hostname);

$db = mysql_create_db($dbname, $connect);

$sql = "grant all privileges on ".$db_engine.".* to ".$user." identified by ".$pass."";
mysql_db_query($dbname, $sql, $connect);

$sql = 'flush privileges';
mysql_db_query($dbname, $sql, $connect);

mysql_close ($connect);

}

else
{
#creating a db in postgres without root user

$dbconn = pg_connect("host=".$hostname." user=".$user." password=".$pass." dbname=template1");

if (!$dbconn) {
  echo "An error occured in connecting to pgSQL database.";
  exit;
}

$query = pg_query($dbconn, "create database ".$dbname." with owner ".$user);

if (!$query) {
  echo "An error occured creating the database.";
  exit;
}

pg_close($dbconn);

}