<?php
	#core.filekeyheader is a special case of core.header.php, it works only with temporary filekeys and has the specific purpose of working with the API for uploading file fragments
	#Input filekey
	#Helena F Deus, November 8, 2006
	ini_set('display_errors',0);
	if($_REQUEST['su3d']) {
		ini_set('display_errors',1);
	}
	if(file_exists('config.inc.php')) {
		include('config.inc.php');
	} else {
		Header('Location: index.php');
		exit;
	}
	ini_set("include_path", S3DB_SERVER_ROOT.'/pearlib'. PATH_SEPARATOR. ini_get("include_path"));
      
	require_once(S3DB_SERVER_ROOT.'/s3dbcore/class.db.inc.php');
	include_once(S3DB_SERVER_ROOT.'/s3dbcore/common_functions.inc.php');
	include_once (S3DB_SERVER_ROOT.'/s3dbcore/acceptFile.php');
	#include_once (S3DB_SERVER_ROOT.'/s3dbcore/callback.php');
		
	if(!$filekey) { $filekey = $_REQUEST['filekey']; }
	if ($filekey=='') {
		echo $message.'A Key to enter S3DB is missing.';
	} elseif($filekey!='') {		#Check if every parameter necessary to access the database is present
		$db = CreateObject('s3dbapi.db');
	 	$db->Halt_On_Error = 'no';
        $db->Host     = $GLOBALS['s3db_info']['server']['db']['db_host'];
        $db->Type     = $GLOBALS['s3db_info']['server']['db']['db_type'];
        $db->Database = $GLOBALS['s3db_info']['server']['db']['db_name'];
        $db->User     = $GLOBALS['s3db_info']['server']['db']['db_user'];
        $db->Password = $GLOBALS['s3db_info']['server']['db']['db_pass'];
        $db->connect();
		
		$filekey_valid = check_filekey_validity($filekey, $db);
		#Delete all expired file_ids
		delete_expired_file_ids(date('Y-m-d G:i:s'), $db);

		if(!$filekey_valid) {
			echo "Filekey is not valid";
			exit;
		} else {
			include_once(S3DB_SERVER_ROOT.'/s3dbcore/uid_resolve.php');
			#include (S3DB_SERVER_ROOT.'/s3dbcore/list_elements.php');		
			include (S3DB_SERVER_ROOT.'/s3dbcore/element_info.php');
			include (S3DB_SERVER_ROOT.'/s3dbcore/SQL.php');
			include (S3DB_SERVER_ROOT.'/s3dbcore/validation_engine.php');
			include (S3DB_SERVER_ROOT.'/s3dbcore/insert_entries.php');
			#include (S3DB_SERVER_ROOT.'/s3dbcore/project_folder.php');
			include (S3DB_SERVER_ROOT.'/s3dbcore/display.php');
			#include (S3DB_SERVER_ROOT.'/s3dbcore/search_resource.php');
			include (S3DB_SERVER_ROOT.'/s3dbcore/file2folder.php');
			include (S3DB_SERVER_ROOT.'/s3dbcore/update_entries.php');
			include (S3DB_SERVER_ROOT.'/s3dbcore/delete_entries.php');
			include (S3DB_SERVER_ROOT.'/s3dbcore/datamatrix.php');
			include (S3DB_SERVER_ROOT.'/s3dbcore/callback.php');
			include (S3DB_SERVER_ROOT.'/s3dbcore/create.php');
			#include (S3DB_SERVER_ROOT.'/s3dbcore/permissions.php');
		}
	}

	function delete_expired_file_ids($date, $db) {
		$sql = "delete from s3db_file_transfer where expires < '".$date."'";
		$db->query($sql, __LINE__, __FILE__);
		$dbdata = get_object_vars($db);
		if($dbdata['Errno']==0) {
			return TRUE;
		} else {
			return FALSE;
		}
	}
?>