<?php
#core.header.php remotely or locally identifies a user with an account by key. When key is not provided, it check for a session
#authenticatge kicks in when a key is not recognized. 
	#Helena F. Deus (hdeus@s3db.org)

	
 #check if there is a valid key. If key is valid, continue. If there is no key, check for a sesion, only then discinnect
        ini_set('display_errors',0);
	if($_REQUEST['su3d'])
	ini_set('display_errors',1);
		ini_set("include_path", S3DB_SERVER_ROOT.'/pearlib'. PATH_SEPARATOR. ini_get("include_path"));
		$a=set_time_limit(0);
		ini_set('memory_limit','3000M');
		ini_set('upload_max_filesize', '128M');
		ini_set('post_max_size', '256M');
		ini_set('display_errors',0);
		if($_REQUEST['su3d'])
		ini_set('display_errors',1);

		#this contains the object for finding the database
		require_once(S3DB_SERVER_ROOT.'/s3dbcore/class.db.inc.php');
		#this one if for sending emails
		
		
		include_once(S3DB_SERVER_ROOT.'/s3dbcore/uid_resolve.php');
		include_once(S3DB_SERVER_ROOT.'/s3dbcore/sharing.php');
		require_once(S3DB_SERVER_ROOT.'/pearlib/Net/SMTP.php');
		#this contains function to initialize an object
		include_once(S3DB_SERVER_ROOT.'/s3dbcore/s3encription.php');
		include_once(S3DB_SERVER_ROOT.'/s3dbcore/common_functions.inc.php');
		include_once(S3DB_SERVER_ROOT.'/s3dbcore/move2s3db.php');
		include_once(S3DB_SERVER_ROOT.'/s3dbcore/search_resource.php');
		include_once(S3DB_SERVER_ROOT.'/s3dbcore/callback.php');
		include_once(S3DB_SERVER_ROOT.'/s3dbcore/display.php');
		include_once(S3DB_SERVER_ROOT.'/s3dbcore/create.php');
		include_once(S3DB_SERVER_ROOT.'/dbstruct.php');
		include_once(S3DB_SERVER_ROOT.'/s3id.php');
		include_once(S3DB_SERVER_ROOT.'/s3dbcore/insert_entries.php');
		include_once(S3DB_SERVER_ROOT.'/s3dbcore/delete_entries.php');
		include_once(S3DB_SERVER_ROOT.'/s3dbcore/s3email.php');
		include_once(S3DB_SERVER_ROOT.'/s3dbcore/SQL.php');
		include_once(S3DB_SERVER_ROOT.'/s3dbcore/URIaction.php');
		include_once(S3DB_SERVER_ROOT.'/html2cell.php');
		#include_once(S3DB_SERVER_ROOT.'/s3dbcore/find_acl.php');
		include_once(S3DB_SERVER_ROOT.'/s3dbcore/S3QLRestWrapper.php');
		include_once(S3DB_SERVER_ROOT.'/webActions.php');
		include_once(S3DB_SERVER_ROOT.'/s3dbcore/permission.php');
		include_once(S3DB_SERVER_ROOT.'/s3dbcore/S3QLaction.php');
		include_once(S3DB_SERVER_ROOT.'/s3dbcore/validation_engine.php');
		include_once(S3DB_SERVER_ROOT.'/s3dbcore/element_info.php');
		include_once(S3DB_SERVER_ROOT.'/s3dbcore/file2folder.php');
		include_once(S3DB_SERVER_ROOT.'/s3dbcore/update_entries.php');
		include_once(S3DB_SERVER_ROOT.'/s3dbcore/datamatrix.php');
		include_once(S3DB_SERVER_ROOT.'/s3dbcore/list.php');
		include_once(S3DB_SERVER_ROOT.'/s3dbcore/htmlgen.php');
		include_once(S3DB_SERVER_ROOT.'/s3dbcore/acceptFile.php');
		include_once(S3DB_SERVER_ROOT.'/s3dbcore/s3list.php');
		include_once(S3DB_SERVER_ROOT.'/s3dbcore/CORElist.php');
		include_once(S3DB_SERVER_ROOT.'/s3dbcore/authentication.php');
		include_once(S3DB_SERVER_ROOT.'/s3dbcore/api.php');
		require_once('Structures/DataGrid.php');
		
		#echo '<pre>';print_r($_REQUEST);exit;
		#if(empty($_REQUEST['PHPSESSID']))
		session_start();
	
		#Header("Cache-control: private"); //IE fix
		if($_SERVER['HTTP_X_FORWARDED_HOST']!='')
			$def = $_SERVER['HTTP_X_FORWARDED_HOST'];
			else 
			$def = $_SERVER['HTTP_HOST'];
						
			if($_SERVER['https']=='on') $http = 'https://';
			else $http = 'http://';

		
		#key can be sent by any format, as long as this function is called after a variable called "key" has been populated. If not, we can aalways try and get it from the URL
		if($key=='')
		$key = $_REQUEST['key'];
		
		
		
		if ($url=='') {
			$url = ($_REQUEST['url']!='')?$_REQUEST['url']:$_REQUEST['user_id'];
		}

		if (empty($key[0])) {
			#check is a session was opened
		 
	
		if($_SESSION['db']!='')
			
			{ 
				
				$db = $_SESSION['db'];
				$user_id = $_SESSION['user']['account_id'];
				#$user_info =  s3info('user', $user_id, $db);
				$user_info =  $_SESSION['user'];
				$tpl = CreateObject('s3dbapi.Template', $GLOBALS['s3db_info']['server']['template_dir']);
				
				
			}
		else
			{
			
			
			if(!ereg('(S3QL|s3ql|URI|rdf|rdfimport|rdfexport|mapproject|resource|project|item|rule|ruleinspector|s3dbfile|sparql|msredirect|download|graphML|api|dictionary|s3rl).php$',$_SERVER['PHP_SELF']))
				{
				#echo 'Your session has expired. <a href = "../login.php" target="_top">Click here to login again</a>';
				#echo "<script>window.location.href='../login.php'</script>";
				#echo '<META HTTP-EQUIV="REFRESH" CONTENT="URL=../login.php">';
				header('Location:'.S3DB_URI_BASE.'/login.php?error=2');
				exit;
				}
			
			#for the API, assume the identify of the "public" user
			$db = CreateObject('s3dbapi.db');
			$db->Halt_On_Error = 'no';
			$db->Host     = $GLOBALS['s3db_info']['server']['db']['db_host'];
			$db->Type     = $GLOBALS['s3db_info']['server']['db']['db_type'];
			$db->Database = $GLOBALS['s3db_info']['server']['db']['db_name'];
			$db->User     = $GLOBALS['s3db_info']['server']['db']['db_user'];
			$db->Password = $GLOBALS['s3db_info']['server']['db']['db_pass'];
			$db->connect();
			
			$user_info = publicUserId($db);
			$user_id = $user_info['account_id'];
			
			if($user_id=='')
			{
			echo (formatReturn($GLOBALS['error_codes']['something_missing'],'A public user was not found. To authenticate a user, please specify a key', $_REQUEST['format'], ''));
			exit;
			}
	
			}
		}
		elseif($key!='')
		{
		
		$key_valid = authenticate($key, $url);
		
		switch ($key_valid) {
			case 0:	{
			$db = CreateObject('s3dbapi.db');
			$db->Halt_On_Error = 'no';
			$db->Host     = $GLOBALS['s3db_info']['server']['db']['db_host'];
			$db->Type     = $GLOBALS['s3db_info']['server']['db']['db_type'];
			$db->Database = $GLOBALS['s3db_info']['server']['db']['db_name'];
			$db->User     = $GLOBALS['s3db_info']['server']['db']['db_user'];
			$db->Password = $GLOBALS['s3db_info']['server']['db']['db_pass'];
			$db->connect();
			
			
			
			
			#if user has been authenticated, then fire away! he can see data!!
			$key_info = get_entry('access_keys', 'account_id,uid', 'key_id', $key, $db);
			$user_id = $key_info['account_id'];
			
			#if no user_id was found, but it was authenticated, then it is a remote login
			$user_info = s3info('user', $user_id, $db);
			


			#if a uid was specified for this key, s3ql should NOT ALLOW any more queries other than the ones specified in uid
			$args = '?key='.$key;
			
			
			break;
			}
			case 2:{
			list($db, $user_info, $user_id)=loginAsPublic();
			
			break;
			}
			case 1:{	#echo '<message>Key not valid. If this is a remote key, please provide url where user is located (for example: http://mylocalhost/s3db/U4)</message>';
			$format = $_REQUEST['format'];
			if($format=='') $format='html';
			
			echo formatReturn('1','Key is not valid. If this is a remote key, please provide url where user is managed (for example: user_id=http://mylocalhost/s3db/U4)', $format,'');
			#break;
			exit;
			}
			case 3:{
			echo formatReturn('3', 'Key does not match user_id provided.', $_REQUEST['format'],'');
			#break;
			exit;
			}
			case 4:{
			echo formatReturn('4','remote user not found', $_REQUEST['format'],'');
			#break;
			exit;
			}
			default:
				exit;
		}
	
	}
		
	
	
	#include the rest
			if (is_object($db) && $user_id!='') {
			
			if ($GLOBALS['s3db_info']['server']['db']['db_type'] == 'mysql') 
			$regexp = 'regexp';
			else $regexp = '~';

			$GLOBALS['regexp'] = $regexp;
			
			
			
			}
			else {
			echo formatReturn($GLOBALS['error_codes']['something_went_wrong'], "Could not connect to Database. Please ask your Administrator to check if the database is running", $format,'');
			exit;
			}
			
			#if($key)
			#$user_id = get_entry('access_keys', 'account_id', 'key_id', $key, $db);
			#elseif($user_info){
			
			#$user_id = $user_info['account_id'];
			#}
			#elseif($_SESSION['user']['account_id'])
			#$user_id = $_SESSION['user']['account_id'];
			#else {
			##
			#}

			#$user_info = s3info('user', $user_id, $db);
			#echo $user_id;
			#echo '<pre>';print_r($user_info);exit;

function authenticate($key, $url)
{
			
		
		if($key!='')
		{
			
		
		$key_valid = check_key_validity($key, $db);
		


		if($key_valid)
		{
		
		return 0;
		
		}
		elseif (!$key_valid) {
	#if key is not valid, check if there is a username (including remote url) and a key		
		
		#$url = $_REQUEST['url'];
		
		if ($url=='') {
			
			#sorry, no access :-(
			return 1;
			exit;
		}
		
		else {
		#URL contains info on user in the last part of the path. (for example: URL=https://ibl.mdanderson.org/s3db/U4) 
		$user_id_info = uid($url);
		
		$db = CreateObject('s3dbapi.db');
			$db->Halt_On_Error = 'no';
			$db->Host     = $GLOBALS['s3db_info']['server']['db']['db_host'];
			$db->Type     = $GLOBALS['s3db_info']['server']['db']['db_type'];
			$db->Database = $GLOBALS['s3db_info']['server']['db']['db_name'];
			$db->User     = $GLOBALS['s3db_info']['server']['db']['db_user'];
			$db->Password = $GLOBALS['s3db_info']['server']['db']['db_pass'];
			$db->connect();
			
			
			#test url validity
			$user = $user_id_info['uid'];
			$url2call = remoteURLretrieval($user_id_info, $db);
				
			#now remove the user from the uri, to get to the real URL
			$rawUrl = $url2call.'/URI.php?key='.$key;
			#echo $rawUrl;exit; 
			if(!http_test_existance($rawUrl))
				return (4);
			
			#go to remote url URI.php to find a username and user_id	
			#check if the key that was provided is valid in the remote url
				$h=fopen($rawUrl , 'r');
				$urldata =	fread($h, '10000');
				$account_info = html2cell($urldata);
		
		#if key is valid in the remote url, check if locally the user has been authorized to access data (there should be an entry on users table where the username is the url+user_id+uname(since this one can be changed, it might not be such a good idea to keep it here. Alternativelly, unam has to be remotelly verified once in a while...)
		
		if (is_array($account_info)) {
			

			#data has been found in remote url
			if ($user == $user_id_info['Did'].'/'.'U'.$account_info[2]['account_id']) {
				
				if (validate_remote_user($account_info[2], $url, $key)) {#user was authenticated IN REMOTE!!! Now we have to authenticate it in local
				#create a key for this user that is the same as the one he just provided
				
				return (0);
				}
				else {
					
					#we can introduce it now...let's allow for this option to be configured with s3db config
					if($GLOBALS['s3db_info']['server']['allow_peer_authentication']=='1')
					{ if (insert_remote_user($account_info[2], $url)){
						if (validate_remote_user($account_info[2], $url, $key)) #now we can validate it again
						return (0);
						else
						return (2);	
						}
						else {
							return(5);
						}
					
					
					}
					else {
						
						return (2);
					}
					

					
				}
			}
			else {
			return (3);
			}
			}
			else {
				return (4);
			}
		
		

		}
		}
		}

		#if there is a session, no need to authenticate the user again
		elseif ($key=='') 
		{
		if ($_SESSION['db']!='') {
		$db = $_SESSION['db'];
		$user_id = $_SESSION['user']['account_id'];
		}
		elseif(in_array('key', array_keys($_REQUEST)))
			{#the url seems prepared to take in a key, but it is empty
			echo '<S3QL>';
			echo '<error>0</error>';
			echo '<connection>Successfully connected to <uri>'.$http.$def.S3DB_URI_BASE.'/</uri></connection><BR>';
			echo '<message>Please provide a key to access S3DB</message><BR>';
			echo '<message>For syntax specification and instructions refer to http://s3db.org/apibasic.html</message>';
			echo '</S3QL>';
			exit;
			}
		else
			{
			#no key and no session found
			echo '<body onload="window.parent.location=\''.S3DB_URI_BASE.'/login.php?error=2\'">';
			exit;
			
			}
		
		}
}
		
		
		
		
function check_key_validity($key)
{#this function temporarily opens db, which SHOULD NOT LEAVE THIS FUNCTION!
#it is only used to authenticate the key

		$db = CreateObject('s3dbapi.db');
	 	$db->Halt_On_Error = 'no';
        $db->Host     = $GLOBALS['s3db_info']['server']['db']['db_host'];
        $db->Type     = $GLOBALS['s3db_info']['server']['db']['db_type'];
        $db->Database = $GLOBALS['s3db_info']['server']['db']['db_name'];
        $db->User     = $GLOBALS['s3db_info']['server']['db']['db_user'];
        $db->Password = $GLOBALS['s3db_info']['server']['db']['db_pass'];
        $db->connect();
		
		$sql = "select * from s3db_access_keys where key_id='".$key."' and expires>='".date('Y-m-d H:i:s')."'";
		
		#echo $sql;
		$db->query($sql, __LINE__, __FILE__);
		if($db->next_record())
		{
			
			$account_id = $db->f('account_id');
			#find the account_uname
			$sql = "select account_lid from s3db_account where account_id = '".$account_id."'";
			$db->query($sql, __LINE__, __FILE__);
			if($db->next_record()) $username = $db->f('account_lid');
			
			
			$sql ="insert into s3db_access_log (login_timestamp, session_id, login_id, ip) values(now(), 'key:".$key."','".$username."','".$_SERVER['REMOTE_ADDR']."')";
			$db->query($sql, __LINE__, __FILE__);

			delete_expired_keys($date, $db);
			 
			return  True;

		}
		else return False;
	  
}


function insert_remote_user($account_info, $url)
{

		$db = CreateObject('s3dbapi.db');
		$db->Halt_On_Error = 'no';
		$db->Host     = $GLOBALS['s3db_info']['server']['db']['db_host'];
        $db->Type     = $GLOBALS['s3db_info']['server']['db']['db_type'];
        $db->Database = $GLOBALS['s3db_info']['server']['db']['db_name'];
        $db->User     = $GLOBALS['s3db_info']['server']['db']['db_user'];
        $db->Password = $GLOBALS['s3db_info']['server']['db']['db_pass'];
        $db->connect();

		#user will be self created, so to distinguish from the ones admin created, change account_type. To make it faster, i'm using s3qlaction that checks user validity, etc, but it might be safer to just create user directly on the sql
		$user_id = '1';
		$s3ql=compact('user_id','db');
		$s3ql['insert']='user';
		$s3ql['where']['login'] = $url.'#'.$account_info['account_lid'];
		$s3ql['where']['email'] = 'selfcreated@s3db.org';
		$s3ql['where']['username']= $account_info['account_uname'];
		$s3ql['where']['account_group']='r'; #r as in remote
		
		#echo '<pre>';print_r($s3ql);
		$done = S3QLaction($s3ql);
		
		ereg('<message>(.*)</message><user_id>([0-9]+)</user_id>', $done, $s3qlout);

		$user_id = $s3qlout[2];
		if ($user_id!='') {
		return ($user_id);	
		}
		else {
			return ($done);
		}
		

}

function loginAsPublic()
	{
	#for the API, assume the identify of the "public" user
	$db = CreateObject('s3dbapi.db');
	$db->Halt_On_Error = 'no';
	$db->Host     = $GLOBALS['s3db_info']['server']['db']['db_host'];
	$db->Type     = $GLOBALS['s3db_info']['server']['db']['db_type'];
	$db->Database = $GLOBALS['s3db_info']['server']['db']['db_name'];
	$db->User     = $GLOBALS['s3db_info']['server']['db']['db_user'];
	$db->Password = $GLOBALS['s3db_info']['server']['db']['db_pass'];
	$db->connect();
	
	$user_info = publicUserId($db);
	$user_id = $user_info['account_id'];
	
	if($user_id=='')
	{
	return (formatReturn($GLOBALS['error_codes']['something_missing'],'A public user was not found. To authenticate a user, please specify a key', $_REQUEST['format'], ''));
	exit;
	}	
	return (array($db, $user_info, $user_id));
}

?>