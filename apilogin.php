<?php
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
	include_once (S3DB_SERVER_ROOT.'/s3id.php');
	include_once (S3DB_SERVER_ROOT.'/dbstruct.php');
	include_once (S3DB_SERVER_ROOT.'/s3dbcore/uid_resolve.php');
	include_once (S3DB_SERVER_ROOT.'/s3dbcore/authentication.php');
	include_once (S3DB_SERVER_ROOT.'/s3dbcore/s3encription.php');
	include_once (S3DB_SERVER_ROOT.'/s3dbcore/display.php');
	include_once (S3DB_SERVER_ROOT.'/s3dbcore/callback.php');
	include_once (S3DB_SERVER_ROOT.'/s3dbcore/element_info.php');
	include_once (S3DB_SERVER_ROOT.'/s3dbcore/validation_engine.php');
	include_once (S3DB_SERVER_ROOT.'/s3dbcore/insert_entries.php');
	include_once (S3DB_SERVER_ROOT.'/s3dbcore/file2folder.php');
	include_once (S3DB_SERVER_ROOT.'/s3dbcore/update_entries.php');
	include_once (S3DB_SERVER_ROOT.'/s3dbcore/delete_entries.php');
	include_once (S3DB_SERVER_ROOT.'/s3dbcore/datamatrix.php');
	include_once (S3DB_SERVER_ROOT.'/s3dbcore/create.php');
	include_once (S3DB_SERVER_ROOT.'/s3dbcore/permission.php');
	include_once (S3DB_SERVER_ROOT.'/s3dbcore/list.php');
	include_once (S3DB_SERVER_ROOT.'/s3dbcore/S3QLRestWrapper.php');
	include_once (S3DB_SERVER_ROOT.'/s3dbcore/SQL.php');
	include_once (S3DB_SERVER_ROOT.'/s3dbcore/S3QLaction.php');
	include_once (S3DB_SERVER_ROOT.'/s3dbcore/htmlgen.php');
	include_once (S3DB_SERVER_ROOT.'/s3dbcore/acceptFile.php');
	include_once (S3DB_SERVER_ROOT.'/s3dbcore/URIaction.php');
	include_once (S3DB_SERVER_ROOT.'/s3dbcore/common_functions.inc.php');
	include_once (S3DB_SERVER_ROOT.'/s3dbcore/sharing.php');
	include_once (S3DB_SERVER_ROOT.'/s3dbcore/api.php');

	$format = $_REQUEST['format'];
	if($format=='') { $format='html'; }

	if(!$_REQUEST['key']) {
		$username = ($_REQUEST['username']!='')?$_REQUEST['username']:$_REQUEST['user_id'];
		$password= $_REQUEST['password'];
		$authority= $_REQUEST['authority'];
	
		list($valid,$user_info,$key,$expires)=login($username, $password, $authority);
		if(!$valid) {
			if(!$authority) {
				$msg = "Authentication failed. If you wish to authenticate with a specific authority other than local S3DB Deployment, please use the argument 'authority' to specify it.";
			} else {
				$msg = $user_info;
			}
			echo formatReturn($GLOBALS['error_codes']['no_permission_message'],$msg, $format, '');
			exit;
		}
		#is user was authenticated, create the token and return it
		if($valid) {
			#if there is no token, create it now
			if(!$key) { $key=random_string(15); }
			if(!$expires) { $expires = date('Y-m-d H:i:s', time()+(1 * 24 * 60 * 60)); }
			
			$db = CreateObject('s3dbapi.db');
			$db->Halt_On_Error = 'no';
			$db->Host     = $GLOBALS['s3db_info']['server']['db']['db_host'];
			$db->Type     = $GLOBALS['s3db_info']['server']['db']['db_type'];
			$db->Database = $GLOBALS['s3db_info']['server']['db']['db_name'];
			$db->User     = $GLOBALS['s3db_info']['server']['db']['db_user'];
			$db->Password = $GLOBALS['s3db_info']['server']['db']['db_pass'];
			$db->connect();
			
			#create a log indicating the user has logged in
			$user_lid = $user_info['account_lid'];
			$user_id = $user_info['account_id'];
			create_log($user_lid, $db);
		
			#Create the key
			$inputs = array('key_id'=>$key, 'expires'=>$expires, 'notes'=>'Key generated automatically via API', 'account_id'=>$user_id);
			$added = add_entry ('access_keys', $inputs, $db);
			$data[0] = $inputs;$letter ='E'; 
			$pack= compact('data', 'user_id','db', 'letter','t','format');
			if($added) {
				echo completeDisplay($pack);
				exit;
			} else {
				echo formatReturn('2', 'Your authentication was valid but a key could not be created.', $format,'');
				exit;	
			}
		}
	} else {
		#if a key has been provided, validate the key
		$key=$_REQUEST['key'];
		include_once('core.header.php');
	
		#$user_proj = $GLOBALS['users_project'];
		if(is_file($GLOBALS['uploads'].'/userManage.s3db')) {
			$user_proj = unserialize(file_get_contents($GLOBALS['uploads'].'/userManage.s3db'));
		}	
		if($user_id!='1') {
			$msg="A project to manage users has not been created. This project can only be created by the generic Admin users. Please add your Admin key to apilogin.php to create it automatically.";
			echo formatReturn('5',$msg, $format, '');
			exit;
		}
		#if it does not exist, create it and save it in config.inc.php;
		$user_proj=create_user_proj(compact('user_id','db','user_proj','timer'));
		
		#now, if query is not empyt, read it, parse it, interpret it.
		if($_REQUEST['query']) {
			$query =  $_REQUEST['query'];
			$q=compact('query','format','key','user_proj','user_id','db');
			$s3ql=parse_xml_query($q);
			##now interpret the query
			$q['s3ql']=$s3ql;
			$return=actBasedOnQuery($q);
		}
		#if user is trying to authenticate, one of the options will be query that user item on the users project for alternative authentication
	}
?>