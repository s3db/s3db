<?php
#login.php is the interface for user login into s3db/ Redirects admins to home.php and normal users to main.php
#Helena F. Deus (helenadeus@gmail.com)

	ini_set('display_errors',0);
	if($_REQUEST['su3d'])
	ini_set('display_errors',1);

	if(file_exists('config.inc.php'))
	{
		include('config.inc.php');
	}
	else
	{
		Header('Location: index.php');
		exit;
	}
	session_start();
	
	include_once(S3DB_SERVER_ROOT.'/dbstruct.php');
	include_once(S3DB_SERVER_ROOT.'/s3dbcore/uid_resolve.php');
	include_once(S3DB_SERVER_ROOT.'/s3dbcore/s3encription.php');
	include_once(S3DB_SERVER_ROOT.'/s3dbcore/authentication.php');
	include_once(S3DB_SERVER_ROOT.'/s3dbcore/display.php');
	include_once(S3DB_SERVER_ROOT.'/s3dbcore/callback.php');
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
	include_once(S3DB_SERVER_ROOT.'/s3dbcore/common_functions.inc.php');
	include_once(S3DB_SERVER_ROOT.'/s3dbcore/api.php');
	include_once(S3DB_SERVER_ROOT.'/s3id.php');

	Header("Cache-control: private"); //IE fix
	foreach($_GET as $name => $value)
	{
		if (ereg('s3db_',$name))
		{
			$extra_vars .= '&' . $name . '=' . urlencode($value);
		}
	}

	if ($extra_vars)
	{
		$extra_vars = '?' . substr($extra_vars,1,strlen($extra_vars));
	}

	


        if(!empty($_POST['login']) && $_POST['submit'])
    	{
			
		if($_POST['login'] == $GLOBALS['s3db_info']['server']['db']['db_user'] && $_POST['passwd']==$GLOBALS['s3db_info']['server']['db']['db_pass'])
		{
			$_SESSION['user']='DBAdmin';
			Header('Location: dbconfig.php');
			exit;
		}
		else if($_POST['login'] == $GLOBALS['s3db_info']['server']['site_config_admin'] && $_POST['passwd']==$GLOBALS['s3db_info']['server']['site_config_admin_pass'])
		{
			$_SESSION['user']['login']=$_POST['login'];
			$_SESSION['user']['passwd']=$_POST['passwd'];
			Header('Location: setup.php');
			exit;
		}
		else
		{
			
			$username= $_POST['login'];
			$password= $_POST['passwd'];
			$authority=$_POST['login_authorities'];
			
			$format='php';
			list($valid,$user_info)=login($username, $password, $authority);
			
			
			if($valid)
			{
				$db = CreateObject('s3dbapi.db');
				$db->Halt_On_Error = 'no';
				$db->Host     = $GLOBALS['s3db_info']['server']['db']['db_host'];
				$db->Type     = $GLOBALS['s3db_info']['server']['db']['db_type'];
				$db->Database = $GLOBALS['s3db_info']['server']['db']['db_name'];
				$db->User     = $GLOBALS['s3db_info']['server']['db']['db_user'];
				$db->Password = $GLOBALS['s3db_info']['server']['db']['db_pass'];
				$db->connect();
				$_SESSION['db'] = $db;
				$_SESSION['user'] = $user_info;
				session_regenerate_id();
				#create_log($_POST['login']);

				##If user is Admin, use this login to update/check ip with the mothership
				#send message to update url
				if($_POST['login']=='Admin'){
				include('core.header.php');
				//require_once 'pearlib/RSACrypt/RSA.php';
				
				$mothership = $GLOBALS['s3db_info']['deployment']['mothership'];
				$Did = $GLOBALS['s3db_info']['deployment']['Did'];
				$publicKey=$GLOBALS['s3db_info']['deployment']['public_key'];
				
				if($Did!='' && $Did!='localhost' && $_REQUEST['offline']!=='on')
					{
					##First check if my ip has changed
					$ip= captureIp();
					
					$protocol = ($_SERVER['HTTPS']!='')?'https://':'http://';
					$url = $protocol.(($ip!='')?$ip:$_SERVER['SERVER_ADDR']).str_replace('login.php', '', $_SERVER['REQUEST_URI']);
					$newUrl = $url;
					$mothershipCall= $mothership.'s3rl.php?format=php&Did='.$Did;
					$mothershipData = stream_get_contents(@fopen($mothershipCall, 'r'));
					#echo $mothershipCall;exit;
					#$msg=html2cell($mothershipData);$msg = $msg[2];
					$msg=unserialize($mothershipData);$msg = $msg[0];
					
					if(trim($msg['url'])!=$url)
						{
						update_url_registry(compact('mothership', 'newUrl', 'publicKey', 'Did'));
						}
					
					
					#echo '<pre>';print_r($resp);
					}
					
				}
				//$weburl = $GLOBALS['s3db_info']['server']['webserver_url'];	
				//Header('Location:'.$weburl.'/home.php');
				//Header('Location:http://'.$_SERVER['HTTP_HOST'].S3DB_URI_BASE.'/home.php');
				
				#Header('Location:home.php');
				if($_REQUEST['su3d']) $sued='&su3d=1';
				if($_REQUEST['offline']) $sued.='&offline='.$_REQUEST['offline'];
				Header('Location:home.php?'.$sued);
			}
			else {
				$_SESSION['db'] = '';	
				$_SESSION['user'] = '';	
				$_SESSION['error']=$msg;
				Header('Location:login.php?error=3');
				exit;				
			}
		}	
		
	}
	elseif(!empty($_SESSION['openId']['success']))
	{
		#echo 'openID Validated: '.$_SESSION['openIdsuccess'];exit;
		 ##Create user with this UID
		
		//		$s3ql=compact('user_id','db');
		//		$s3ql['from']='users';
		//		$s3ql['where']['user_id']=$_SESSION['openId']['url'];
		//		#$s3ql['where']['permission_level']='212';
		//		
		//		$done = S3QLaction($s3ql);
		//		if(!is_array($done)){
		//			
		//		}
		//		$msg=html2cell($done);
		//		$msg=$msg[2];
		//		if($msg['error_code'])
		//		echo '<pre>';print_r($s3ql);exit;
	}
#	phpinfo();
       
function auth_user($login, $password, $db)
{
		//echo $password;
		//echo $_SESSION['db'];
		#Try regular login first
		if(!$db){
		$db = $_SESSION['db'];
		}
		$user_info= localUserInfo($db, $login, $password);
		if($user_info){
		$_SESSION['user'] = $user_info;
		return (True);
		}
		else
		{
			##Now try the email and the key

			$sql = "select * from s3db_account where (account_email='".$login."' or account_lid = '".$login."') and account_id in (select account_id from s3db_access_keys where key_id = '".$password."')";
			
			
			$user_info = localUserInfo($db, $login, $password, $sql);
			
			if($user_info){
			$user_info['account_key'] = $password;
			$_SESSION['user'] = $user_info;	
			}
			else {
				#Let's see if remotelly it works
				#Ok, so may key does not exist, let's go all the way and find this user wherever he "lives"
				$sql = "select * from s3db_account where (account_email='".$login."' or account_id = '".$login."')";
					#echo $sql;
					$db->query($sql, __LINE__, __FILE__);
					
					$db->next_record();
					#echo '<pre>';print_r($db);
					if($db->f('account_id') != '')
					{
					
					$account_id = $db->f('account_id');
					#what's the url where this user can log in?
					ereg('(.*)U[0-9]+$',$db->f('account_id'), $url_info);
					
					$remoteURL = $url_info[1].'apilogin.php?user_id='.$db->f('account_id').'&password='.$password.'&format=php';
					
					$a = @fopen($remoteURL,'r');
					if(!$a){
					$_SESSION['db'] = '';	
					$_SESSION['user'] = '';	
					Header('Location:login.php?error=3');
					exit;
					}
					else {
						$b=stream_get_contents($a); 
						
						$key_info = unserialize($b);
						
						if($key_info[0]['key_id']!='') {
							$key=$key_info[0]['key_id'];
							#Now request the user info
							$remoteURL = $url_info[1].'URI.php?key='.$key_info[0]['key_id'].'&format=php'; 
							
							$c = fopen($remoteURL,'r');
							if($c){
								if(!ereg('@',$login)){
								$remote_user_info = unserialize(stream_get_contents($c));
								
								$account_info = $remote_user_info[0];
								
								$sql = "update s3db_account set account_lid = '".$account_info['account_lid']."', account_uname = '".$account_info['account_uname']."', account_email = '".$account_info['account_email']."', account_type='r' where account_id = '".$db->f('account_id')."'";
								
								$db->query($sql, __LINE__,__FILE__);
								
								$user_info = array('account_id' => $login,
							'account_lid' => $account_info['account_lid'],
							'account_uname' => $account_info['account_uname'],
							'account_group' => 'r',
							'account_type'=>'r',
							'account_key'=>$key_info[0]['key_id']);
							
							}
							else {
								$sql = "select * from s3db_account where (account_lid='".$login."' or account_email='".$login."')";
								
								$user_info = localUserInfo($db, $login, $password, $sql);
								
								if($user_info){
								$user_info['account_key'] = $key_info[0]['key_id'];
								$user_info['account_type'] = 'r';
								
								
								}
								
								
							}
							#Import the key for the next hour
							$sql = "insert into s3db_access_keys (key_id, account_id, expires, notes) values ('".$key."', '".$account_id."', '".date('Y-m-d H:i:s',time() + (1 * 1 * 60 * 60))."', 'Remote key, will expire in 1h')";
							$_SESSION['user'] = $user_info;
							$db->query($sql, __LINE__, __FILE__);
							return (True);
							}
							
							
						}
					}

					}

			}
			
			
			$_SESSION['db'] = '';	
			$_SESSION['user'] = '';	
			Header('Location:login.php?error=3');
			exit;
		}
}		

function auth_user_original($login, $password)
	{
		//echo $password;
		//echo $_SESSION['db'];
		$db = $_SESSION['db'];
		$sql = "select * from s3db_account where (account_email='".$login."' or account_lid = '".$login."') and account_pwd='".md5($password)."'";
		//echo $sql;
		$db->query($sql, __LINE__, __FILE__);
		$db->next_record();
		if($db->f('account_id') != '')
		
			if($db->f('account_status')=='A')
			{	
				$user_info = array('account_id' => $db->f('account_id'),
				      'account_lid' => $db->f('account_lid'),
				      'account_uname' => $db->f('account_uname'),
				      'account_group' => $db->f('account_group'));
				$_SESSION['user'] = $user_info;	
				return True;
			}
			else
			{
				Header('Location:login.php?error=5');
				exit;
			}
	
		else
		{
			$_SESSION['db'] = '';	
			$_SESSION['user'] = '';	
			Header('Location:login.php?error=3');
			exit;
		}
	}

function localUserInfo($db,$login,$password, $sql='')
{					
		if($sql==''){
		$sql = "select * from s3db_account where (account_lid='".$login."' or account_email='".$login."') and account_pwd='".md5($password)."'";
		}
		#echo $sql;
		$db->query($sql, __LINE__, __FILE__);
		$db->next_record();
		if($db->f('account_id') != '')
		
			if($db->f('account_status')=='A')
			{	
				$user_info = array('account_id' => $db->f('account_id'),
				      'account_lid' => $db->f('account_lid'),
				      'account_uname' => $db->f('account_uname'),
				      'account_group' => $db->f('account_group'),
					  'account_type' => $db->f('account_type'));
					
				
				return $user_info;
			}
			else
			{
				Header('Location:login.php?error=5');
				exit;
			}
}
	
	

	function check_user_group($user_id)
	{
		$db = $_SESSION['db'];
		//$db->Debug = 1;
		$db->query("select account_lid from s3db_account, s3db_account_group where s3db_account_group.account_id='".$user_id."' and s3db_account_group.group_id = s3db_account.account_id", __LINE__, __FILE__);
		/*$user_group =Array();
		$count = $db->num_rows();
		for($idx = 0; $idx < $count; ++$idex)
		{
			$db->next_record();
			$user_group = array(
				'$idx' => $db->f('account_lid'));
		}*/
		$i=0;
		while($db->next_record()); 
		{
			$user_group[$i] = "? ".$db->f('account_lid');
			$_SESSION['group'] = $db->f('account_lid');
			$i++;
		}
		return $user_group;
	}
	
	function clear_session()
	{
		if($_SESSION['db']!='')
		{
			$_SESSION['db']->disconnect();
		}
		if($_SESSION['user']!='')
		{
			$_SESSION['user']='';
			//echo "i am here";
		}
		if($_REQUEST['project_id']!='')
		{
			$_REQUEST['project_id']='';
			//echo "i am here";
		}
		if($_REQUEST['entity_id']!='')
		{
			$_REQUEST['entity_id']='';
			//echo "i am here";
		}

		$_SESSION = array();
		if($GLOBALS['dbsetup']!='')
		{
			$GLOBALS['dbsetup']->disconnect();
		}
		$GLOBALS = array();

		// If it's desired to kill the session, also delete the session cookie.
		// Note: This will destroy the session, and not just the session data!
		/*if (isset($_COOKIE[session_name()])) 
		{
   			setcookie(session_name(), '', time()-42000, '/');
		}
		*/
		// Finally, destroy the session.
		session_destroy();
		//session_regenerate_id();
	}	
	 
	function check_logoutcode($code)
        {	
			
		//echo " I am checking logout";
                switch($code)
                {
                        case 1:
				#clear_session();
                                return 'You have successfully logged out.';
                                break;
                        case 2:
				#clear_session();
                                return 'Sorry, your session has expired, please login again.';
                                break;
                        case 3:
				#clear_session();
								$err=($_SESSION['error']=='')?'Bad login or password.':$_SESSION['error'];
								$_SESSION=array();
                                return $err;
                                break;
                        case 4:
				clear_session();
                                return 'You do not have permission to set up S3DB database.';
                                break;
                        case 5:
				clear_session();
                                return 'Your account no longer active.';
                                break;
                        case 6:
				clear_session();
                                return 'You do not have administrator privilege on this action.';
                                break;
                        case 7:
				clear_session();
                                return 'You need to setup your config.inc.php first';
                                break;
						case 8:
						{
						#clear_session();
						$_SESSION=array();
								   					
									return ('To recover your password, type your username and click "forgot password" again. You will get an email with instructions.');
						}
							break;
						case 9:
						{
					clear_session();
								   					
									return ('User '.$_REQUEST['login'].'  was not found');
						}
								break;
						case 10:
						{
					clear_session();
								   					
									return ('An email was sent to verify your account');
						}
								break;
                        default:
                                return '&nbsp;';
                }
        }
		
		
		
		
		
   #get the username from whatever's in the username field
   
	$tpl = CreateObject('s3dbapi.Template', $GLOBALS['s3db_info']['server']['template_dir']);
	$tpl->set_var('error',check_logoutcode($_GET['error']));

	//$tpl->set_var('login_url', S3DB_URI_BASE . '/login.php' . $extra_vars);
	$tpl->set_var('login_url',  'login.php' . $extra_vars);
	$tpl->set_var('registration_url',$GLOBALS['s3db_info']['server']['webserver_url'] . '/registration/');
	//$tpl->set_var('version',$GLOBALS['s3db_info']['server']['versions']);
	if(is_file('version.txt')){
	$ver = file_get_contents('version.txt');
	}
	if(!$ver) {
		$ver = '3.5.3';
	}
	$tpl->set_var('version',$ver);
	#$tpl->set_var('version','3.5.2');
	
	
	##Now for the license information
	$li = @file_get_contents('license.txt');
	
	if($li)
	$tpl->set_var('license',$li);
	
	$Did=(!ereg('^D',$GLOBALS['s3db_info']['deployment']['Did']))?'D'.$GLOBALS['s3db_info']['deployment']['Did']:$GLOBALS['s3db_info']['deployment']['Did'];
	
	$tpl->set_var('Did',$Did);
	$tpl->set_var('ms',$GLOBALS['s3db_info']['deployment']['mothership']);
	$tpl->set_var('cookie',$last_loginid);
	
	$tpl->set_var('login_top', 'images/login_top.gif');
	$tpl->set_var('username','Username');
	
	
		
	if ($_REQUEST['error']!=8) {
		$lost_password_link .= '<a href="#" onClick="window.location=\'login.php?error=8\'"><nobr>forgot password</nobr></a>';
	}
	else {

		#$lost_password_link .= '<a href="#" onClick="window.location=\'reset_password.php?login=username\'">reset password</a>';
		$lost_password_link .= '<a href="#" onClick="reset_password_redirect()"><nobr>forgot password</nobr></a>';
	}
	    
	$tpl->set_var('lost_my_password','&nbsp;&nbsp;'.$lost_password_link);
	
	
	if ($_SESSION['db'] == '')
	$tpl->set_var ('input_login', '<input type="text" name="login" id="login" value=""><input type="checkbox" name="offline">Offline');
	else 
	$tpl->set_var ('input_login', $_SESSION['user']['account_lid'].'<input type="hidden" name="login" value="'. $_SESSION['user']['account_lid'].'"><input type="checkbox" name="offline">Offline');
	
	#Set up the parameters for openID authentication; form data
	
	if($_REQUEST['openid_identifier']!='' && $_REQUEST['openid_identifier']!='http://')
	$tpl->set_var ('input_openID', '<input type="text" size="35" style="background: #FFFFFF url('.S3DB_URI_BASE.'/images/openid_small_logo.png) no-repeat scroll 0pt 50%; padding-left: 18px !important; padding-top: 0.2em !important" name="openid_identifier" id="openID" value="'.$_REQUEST['openID'].'">');
	else
	$tpl->set_var ('input_openID', '<input type="text" size="35" style="background: #FFFFFF url('.S3DB_URI_BASE.'/images/openid_small_logo.png) no-repeat scroll 0pt 50%; padding-left: 18px !important; padding-top: 0.2em !important" name="openid_identifier" id="openid_identifier" value="http://">');
	
	$tpl->set_var('password','Password');
	$tpl->set_var('login','Login');
	if ($_SESSION['db'] != '')
	$tpl->set_var('different_user_link','
					<td width="40%">&nbsp;</td>
					<td align="left"><a href="'.S3DB_URI_BASE.'/logout.php'.'">Sign in as a different user</a><br /><br />&nbsp;&nbsp;&nbsp;&nbsp;</td>
				'); 
	else  $tpl->set_var('different_user_link','<td width="40%"><br /><br /></td>');
	
	##NEW CODE; relies on existing endorsed_authorieis
	
	
	$authorities = trustedAuth();
	
	
	if(!empty($authorities)){
			$auth_login .= '<TD align="RIGHT" width="40%"><font color="#000000"><i>Authority</i>:&nbsp;</font></TD>
					 <TD>';
			$auth_login .= '<select name="login_authorities" id="authorities" style="width: 155px">';
			$auth_login .= '<option value="" selected>S3DB</option>';
			foreach ($authorities as $auth_info) {
			#if($auth_info['True/False']=='T') $endorsed='(e)';
			if(eregi('^t', $auth_info['Endorsed'])) $endorsed='(e)';
			else $endorsed='';
			$auth_login .= '<option value="'.$auth_info['DisplayLabel'].'">'.$auth_info['DisplayLabel'].' '.$endorsed.'</option>';	
			}
			$auth_login .= '</select>';
			$auth_login .= '</TD>';
		}
	$tpl->set_var('authorities', $auth_login);	

	
	$tpl->set_var('website_title', $GLOBALS['s3db_info']['server']['site_title'].' - login');
	$tpl->set_var('template_set',$GLOBALS['s3db_info']['server']['login_template_set']?$GLOBALS['s3db_info']['server']['login_template_set']:'default');
	$tpl->set_var('bg_color',($GLOBALS['s3db_info']['server']['login_bg_color']?$GLOBALS['s3db_info']['server']['login_bg_color']:'FFFFFF'));
	$tpl->set_var('bg_color_title',($GLOBALS['s3db_info']['server']['login_bg_color_title']?$GLOBALS['s3db_info']['server']['login_bg_color_title']:'#e6e6e6'));
	$tpl->set_var('autocomplete', ($GLOBALS['s3db_info']['server']['autocomplete_login'] ? 'autocomplete="off"' : ''));
	$tpl->set_file(array(
		'header' => 'configheader.tpl',
		//'footer' => 'footer.tpl'
		'login_form' => 'login.tpl'
	));
	$tpl->parse('_login', 'header', True);
	$tpl->parse('_login', 'login_form', True);
	//$tpl->parse('_login', 'footer', True);
	$tpl->pfp('loginout','_login');

//print_r (get_included_files());
?>
