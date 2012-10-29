<?php
	ini_set('display_errors',0);
	if($_REQUEST['su3d']) {
		ini_set('display_errors',1);
	}

	$username = $_REQUEST['login'];
	#check if this username exists
	if (!reset_password($username)) {
		echo formatReturn($GLOBALS['error_codes']['something_missing'], "No email was found related to this user. Please ask an administrator of your database to replace your password.", $_REQUEST['format'],'');
	}
	exit;

	function reset_password($username) {		#this function call the db but it should NOT leave this one function
		include('config.inc.php');
		include_once(S3DB_SERVER_ROOT.'/webActions.php');
		require_once(S3DB_SERVER_ROOT.'/s3dbcore/class.db.inc.php');
		include_once(S3DB_SERVER_ROOT.'/s3dbcore/common_functions.inc.php');
		include_once(S3DB_SERVER_ROOT.'/dbstruct.php');
		require_once(S3DB_SERVER_ROOT.'/pearlib/Net/SMTP.php');
		include_once(S3DB_SERVER_ROOT.'/s3dbcore/validation_engine.php');
		include_once(S3DB_SERVER_ROOT.'/s3dbcore/SQL.php');
		include_once(S3DB_SERVER_ROOT.'/s3dbcore/s3email.php');
		include_once(S3DB_SERVER_ROOT.'/s3dbcore/display.php');
		include_once(S3DB_SERVER_ROOT.'/s3dbcore/callback.php');
		include_once(S3DB_SERVER_ROOT.'/s3dbcore/S3QLRestWrapper.php');
		//include_once(S3DB_SERVER_ROOT.'/s3dbcore/find_acl.php');
		include_once(S3DB_SERVER_ROOT.'/s3dbcore/URIaction.php');
		include_once(S3DB_SERVER_ROOT.'/s3dbcore/S3QLaction.php');
	
		$db = CreateObject('s3dbapi.db');
		$db->Halt_On_Error = 'no';
		$db->Host     = $GLOBALS['s3db_info']['server']['db']['db_host'];
		$db->Type     = $GLOBALS['s3db_info']['server']['db']['db_type'];
		$db->Database = $GLOBALS['s3db_info']['server']['db']['db_name'];
		$db->User     = $GLOBALS['s3db_info']['server']['db']['db_user'];
		$db->Password = $GLOBALS['s3db_info']['server']['db']['db_pass'];
		$db->connect();
		
		#do a query on user to find useremail		
		$sql = "select account_id,account_email from s3db_account where account_lid='".$username."' and account_type ".$GLOBALS['regexp']." '^(u|a|r)$'";
		$db->query($sql, __LINE__, __FILE__);
		if($db->next_record()) {
			$email = $db->f('account_email');
			$user_id = $db->f('account_id');
		} else {
			header('Location:login.php?error=9&login='.$username);
			exit;
		}

		#create a temporary key for his user
		$s3ql=compact('user_id','db');
		$s3ql['insert']='key';
		$s3ql['where']['expires']=date('Y-m-d H:i:s',time() + (1 * 1 * 60 * 60));
		$s3ql['where']['notes']='Temporary key generated automatically for password recovery';
		$s3ql['format']='php';
		$done = S3QLaction($s3ql);
		$msg=unserialize($done);$msg = $msg[0];
	
		#send an email to user with a link to change profile by using this temporary key
		$key_id = $msg['key_id'];
		if ($key_id!='') {
			if($_SERVER['HTTP_X_FORWARDED_HOST']!='') {
				$def = $_SERVER['HTTP_X_FORWARDED_HOST'];
			} else { 
				$def = $_SERVER['HTTP_HOST'];
			}	
			if($_SERVER['https']=='on') { 
				$http = 'https://'; 
			} else { 
				$http = 'http://'; 
			}
			$url = $action['edituser'] .'&key='.$key_id.'&id='.$user_id;
		
			$message .= sprintf("%s\n\n", 'Dear '.$username);
			$message .= sprintf("%s\n", 'A password reset was requested for your account');
			$message .= sprintf("%s\n", 'To reset your account go to '.$url);
			$message .= sprintf("%\n\n", 'This link will expire in 1 hour');
			$message .= sprintf("%s\n",'The S3DB team.(http://www.s3db.org)');
			$message .= sprintf("%s\n\n",'Note: Please do not reply to this email, this is an automated message');
		
			$subject = 'Your S3DB account - lost password request';
		
			if ($email=='') {
				return False;
			} else {
				$tosent=compact('email', 'subject', 'message');
				send_email($tosent);
			}
	
			#redirect back to login
			header('Location:'.$action['login'].'?error=10');
		}
		#once the user logs in delete this temporary key (maybe put a pattern on it, such that a temporary key is always recognized)
	}
?>