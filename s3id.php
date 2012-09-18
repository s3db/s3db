<?php
function s3id() {
	#finds a new id from a file that keeps growing as new ids are added
	#this function will be included in several scripts, which means that path will change. Need to keep path fixed.
	include('config.inc.php');

	# Get value from DB
	$s3id = null;
	$newid = null;
	$dbo = CreateObject('s3dbapi.db');
	$dbo->Halt_On_Error = 'no';
	$dbo->Host     = $GLOBALS['s3db_info']['server']['db']['db_host'];
	$dbo->Type     = $GLOBALS['s3db_info']['server']['db']['db_type'];
	$dbo->Database = $GLOBALS['s3db_info']['server']['db']['db_name'];
	$dbo->User     = $GLOBALS['s3db_info']['server']['db']['db_user'];
	$dbo->Password = $GLOBALS['s3db_info']['server']['db']['db_pass'];
	
	#$dbo->connect();
	//Check if table exists
	$sql = "SELECT COUNT(1) as myCount FROM s3db_config";
	$res = $dbo->query($sql, __LINE__, __FILE__);
	if($res) {
		$sql = "SELECT config_value FROM s3db_config WHERE config_name='s3id'";
		$dbo->query($sql, __LINE__, __FILE__);
		if($dbo->next_record()) {
			$s3id = $dbo->f('config_value');
			$newid = intval($s3id) + 1;
			$sql = "UPDATE s3db_config SET config_value=$newid WHERE config_name='s3id'";
			$dbo->query($sql, __LINE__, __FILE__);
		} else {
			$sql  = "INSERT INTO s3db_config (config_name, config_value, config_type, config_note, created_on, modified_on, modified_by) ";
			$sql .= "VALUES ('s3id','3', 'int','s3id', CURRENT_TIMESTAMP, CURRENT_TIMESTAMP, {$_SESSION['user']['account_id']})";
			$dbo->query($sql, __LINE__, __FILE__);
			$newid = 3;
		}
		#$dbo->disconnect();
		return $newid;
	} else {
		//TODO: Consider creating table since it may not exist and handling it with multiple databases products 
		$dbo->disconnect();
		return false;
	}
}
?>