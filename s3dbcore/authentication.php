<?php
#authentication is a collection of functions to verify the credentials of both local and remote users.
#resolve the url where the username and password should be sent to

function login($username, $password, $authority=false)
{
	#try locally first
	if($username!='' && $password!='')
		{

		#connect to the db
		$db = CreateObject('s3dbapi.db');
		$db->Halt_On_Error = 'no';
				$db->Host     = $GLOBALS['s3db_info']['server']['db']['db_host'];
				$db->Type     = $GLOBALS['s3db_info']['server']['db']['db_type'];
				$db->Database = $GLOBALS['s3db_info']['server']['db']['db_name'];
				$db->User     = $GLOBALS['s3db_info']['server']['db']['db_user'];
				$db->Password = $GLOBALS['s3db_info']['server']['db']['db_pass'];
				$db->connect();
		
		}
		else {
			return (array(false, "Please provive username and password"));exit;
		}
	#if(!$authority || eregi('^s3db$',$authority)){
	list($valid,$user_info,$message) = auth_user_api($username, $user_id,$password, $db);
	#	}

	if($valid){
		return (array(true, $user_info)); exit;
	}
	
	
	
	#next, try an account in one of the account authorities
	if(!$valid){	
	  #need first to check if authority is within authorities
	 list($authorityValid,$reqAuth,$user_proj)= checkValidAuthority($authority, $db);
	 
	 
	  #now find the protocol; transform the username according to template
	  #is there a username template
	  if(!$reqAuth){
		  return (array(false, "Bad login or password."));
	  }
	  
	  if(trim($reqAuth['Template'])!==''){// && !ereg('\@', $username)){
	  
	  $complexUsername = str_replace('%username%', $username,  trim($reqAuth['Template']));
	  }
	  else{
	  $complexUsername=$username;
	  }
	 
	  if($reqAuth['serviceAccountUserName']!='')
		{
	  	if($reqAuth['serviceAccountPassword']!='')
			{
			$serv_account= array('username'=>trim($reqAuth['serviceAccountUserName']),'password'=>trim($reqAuth['serviceAccountPassword']), 'dnbase'=>trim($reqAuth['dnbase']));
			}
		}
	  
	   #what is the protocol??						   
	 
	  $protocolName= $user_proj['protocols']['names'][array_search($reqAuth['Protocol'], $user_proj['protocols']['item_id'])];
	  $protInd = array_search($reqAuth['Protocols'], $user_proj['protocols']['item_id']);
	  $protocolName =  $user_proj['protocols']['names'][$protInd];
	 
	  #what is the connecting url/server? Can an accoun be created for this user automatically?
	  $createAccount=false;   
	  $server =$reqAuth['URI']; 
	  $createAccount = eregi('^t', $reqAuth['Endorsed'])?true:false; 
	  switch ($protocolName) {
		case 'ldap':
		
		list($valid) = ldap_auth($server,$complexUsername, $password,$serv_account);
			
		break;
		case 'ftp':
		
		list($valid, $token) = ftp_auth($server,$complexUsername, $password);
		break;
		case 'http' :
			 
			switch ($authority) {
				
				case 'google':
				#for some stupid reason, googl eis going uppercase. this simplifies things
				#$authority = strtolower($authority);
				list($valid,$token) = google_auth($server,$complexUsername, $password);	
				
				$token=md5($token);
				$expires = date('Y-m-d', time()+(1 * 24 * 60 * 60)); ##token actually lasts for 2 weeks :O, but i will leave it 24 h for now.
				break;
				
				default :
					
					if(eregi('^s3db',$authority)){
					#start by resolving the s3db uri
					ereg('(s3db:){0,1}(.*)$',$authority,$s3);
					list($s3_auth,$s3root,$s3name)=$s3;
					#list($discovered, $url) = discover_url($s3name);
					
					list($valid, $token, $expires)=s3db_auth($server,$complexUsername, $password);#try s3db auth
					
					
					}
					else {
						$valid=0;
						$token = "User was not validated.";

					}
			}
	  }
	 
	  if(!$valid){
		
		return (array(False, "User was not validated."));
	  }
	  else {
		
		
		#now find who this user authentication belongs to
		$user_complex_id = $protocolName.':'.$authority.':'.$username;
		
		$s3ql=array('user_id'=>'1','db'=>$db);#TO BE CHANGED once user can access his own resource 
		$s3ql['from']='authentication';
		list($queried, $data)=apiQuery($s3ql, $user_proj);
		
		if($queried)
		  {
			foreach ($data as $authen) {
				
				if($authen['authentication_id']==$user_complex_id) {
					$authenticated_user_id = $authen['user_id'];
				}

			}
		  }
		
		if(!$authenticated_user_id && $valid)
		  {
			#User was authenticated but does he exist in this deplyment. If not, can an accoun be craeted?';
			 $sql = "select * from s3db_account where (account_id='".$user_complex_id."' or account_lid='".$user_complex_id."') and account_status='A'";
			 
			 $db->query($sql);
			 
			 if(!$db->next_record()){
			
			 if($createAccount){
				$email =(ereg('@',$username))?$username:($username.'@'.$authority);	 #TO BE CHANGED once user_proj returns the template for building the email
				#$account_name =(ereg('@',$username))?$username:($authority.':'.$username);
				$account_id = s3id();
				$account_name=$user_complex_id;
				$sql = "insert into s3db_account (account_id, account_lid,account_pwd,account_uname,account_email,account_addr_id,created_on,created_by,account_status,account_type) values ('".$account_id."','".$account_name."','".random_string(15)."','".$account_name."','".$email."','0','now()','1','A','u')";
				
				$db->query($sql);
				
				$user_info = array('account_id'=>$account_id,'account_lid'=>$account_name, 'account_type'=>'r');
			 }
			 else {
				 return (array(False, "User was validated but does not have an account nor can one be created for him."));
			 }
			 }
			 else {
				 
				 $user_info = array('account_id'=>$db->f('account_id'), 'account_type'=>$db->f('account_type'), 'account_lid'=>$db->f('account_lid'));
			 }
			
		   }
		elseif($valid && $authenticated_user_id) {
			
			$key=$token;
			
			$sql = "select * from s3db_account where account_status = 'A' and account_id = '".ereg_replace('^U','',$authenticated_user_id)."'";
						   
			$db->query($sql);
					  
				if($db->next_record()){
					$user_info = array('account_id'=>$authenticated_user_id, 'account_type'=>$db->f('account_type'), 'account_lid'=>$db->f('account_lid'));
				}
				else {
					return (array(false, 'User was validated but account is not active'));exit;
				}
			}
			else {
				return (array(false));
			}
			
			return (array(true,$user_info,$token,$expires));
			
		}
		
	  }
	  
}


function auth($username,$password,$format='html',$createkey=true,$authorities='')
{	
  
	if($username!='' && $password!='')
	{

	#connect to the db
	$db = CreateObject('s3dbapi.db');
	$db->Halt_On_Error = 'no';
			$db->Host     = $GLOBALS['s3db_info']['server']['db']['db_host'];
			$db->Type     = $GLOBALS['s3db_info']['server']['db']['db_type'];
			$db->Database = $GLOBALS['s3db_info']['server']['db']['db_name'];
			$db->User     = $GLOBALS['s3db_info']['server']['db']['db_user'];
			$db->Password = $GLOBALS['s3db_info']['server']['db']['db_pass'];
			$db->connect();
	
	
	
	list($valid,$user_info,$message)= auth_user_api($username, $user_id,$password, $db);
	
	if(!$valid){
	#does this user have another account? try it remotelly
	#in case an authority has been endorsed: if authority is complex, build the username taking that into account
		if($authorities){
			$create_account=false;
			foreach ($GLOBALS['endorsed'] as $ord=>$end) {
				if($end==$authorities){
					$email = $username.((substr($end,0,1)=='@')?'':'@').$end;
					$userLabel = $end.':'.$username;
					$protocol = $GLOBALS['s3db_info']['deployment']['endorsed_protocol'][$ord];
					$auth=$GLOBALS['s3db_info']['deployment']['endorsed_server'][$ord];
					$userComplexId=	$protocol.':'.$authorities.':'.$username;
				   	#$email = $username.((substr($end,0,1)=='@')?'':'@').$end;
					
					#$protocol = $GLOBALS['s3db_info']['deployment']['endorsed_protocol'][$ord];
					#$userLabel = $protocol.':'.$end.':'.$username;
					#$auth=$GLOBALS['s3db_info']['deployment']['endorsed_server'][$ord];

					if($protocol=='ldap'){
					
					$userComplexName=$protocol.':'.$auth.':'.ereg_replace('cn=email','cn='.$username, $GLOBALS['s3db_info']['deployment']['endorsed_ldap_rns'][$ord]);
					$create_account=$GLOBALS['s3db_info']['deployment']['endorsed_automated'][$ord];
					
					}
					elseif($protocol=='http'){
					$userComplexName=$protocol.':'.$auth.':'.ereg_replace('email',$username, $GLOBALS['s3db_info']['deployment']['endorsed_ldap_rns'][$ord]);
					}
				}
			}
		}
	
	if($userComplexName==''){$userComplexName=$username;} #
	list($valid,$token,$expires)=univ_authenticate($userComplexName, $password, $serv_account);
	
	
	#if user was validated remotelly, user_info will correspond to the remote user. Also, a key has been generated; that is what will be used for the remainder of this session.
	if($valid)
		{
			
			#even if user was authenticated remotelly, he still needs to have been created as user of this deployment
			if($email=='') {$email=$username;}
			if($userComplexId!='') {
				$username=$userComplexId;
				$account_id = s3id();
				}
			#else {$account_id=$username;}

			$sql = "select * from s3db_account where (account_id='".$account_id."' or account_email='".$email."') and account_status='A'";
			#$sql = "select * from s3db_account where (account_id='".$username."' or account_email='".$username."') and account_status='A'";
			
			$db->query($sql);
			if(!$db->next_record()){
			##If these account have been endorsed as trustworthy, we can, at this point, create an account for the user
				if($create_account){
				#$adminUser=1;
				
				/*$s3ql=array('user_id'=>$adminUser,'db'=>$db);
				$s3ql['insert']='user';
				$s3ql['where']['user_id']=$email;
				$s3ql['where']['email']=$email;
				$done = S3QLaction($s3ql);
				*/
				
				$sql = "insert into s3db_account (account_id, account_lid,account_pwd,account_uname,account_email,account_addr_id,created_on,created_by,account_status,account_type) values ('".$account_id."','".$userLabel."','".random_string(15)."','".$email."','".$email."','0','now()','1','A','u')";
				
				
				$db->query($sql);
				$user_info = array('account_id'=>$account_id,'account_lid'=>$userLabel, 'account_type'=>'r');
				}
				elseif(is_file($GLOBALS['uploads'].'/userManage.s3db')) #is this an alternative user authentication?
				{
			  
				$user_proj = unserialize(file_get_contents($GLOBALS['uploads'].'/userManage.s3db'));
			    
				if(!$user_proj){return (array(false,"User project does not exist"));exit;}
				
				$sql = "select * from s3db_statement where rule_id = '".$user_proj['email']['rule_id']."' and value = '".$username."'";
				
				$db->query($sql);
				if($db->next_record())
					{
					$item_id = $db->f('resource_id');
					
					if($item_id){
					 $sql = "select * from s3db_statement where resource_id = '".$item_id."' and rule_id = '".$user_proj['user_id']['rule_id']."'";
						
					$db->query($sql);
					if($db->next_record()){
						   $sql = "select * from s3db_account where account_status = 'A' and account_id = '".ereg_replace('^U','',$db->f('value'))."'";
						   
							
							$db->query($sql);
						  
						   if($db->next_record()){
						   $user_info = array('account_id'=>ereg_replace('^U','',$db->f('value')), 'account_type'=>'u');
						   }
						}
						else {
							return (array(false,''));
						}
					
					}
					else {
						
						return (array(false,''));	
					
					}
					}
					else {
					return (array(false,''));	
					}
				}
				else{		
				return (array(false,formatReturn($GLOBALS['error_codes']['wrong_input'], 'User '.$username.' does not have permission in this deployment. If you think you should have permission, please inform the administrator of this deployment.', $format,'')));
				$valid=0;
				exit;
				}
			
			}
		else{
			$key=$token; $user_info = array('account_id'=>$account_id,'account_lid'=>$username, 'account_type'=>'r');
			# $user_info = array('account_id'=>$username, 'account_type'=>'r');
			if(strtotime($expires)>strtotime(date('Y-m-d', time()+(1 * 24 * 60 * 60)))){
			return(array(false,formatReturn($GLOBALS['error_codes']['wrong_input'], 'S3DB received a key which expires after 24h. For security reasons, a key must be valid for 24h or less', $format,''), $user_info));
				exit;
			}
		}
		}
	else {
		return (array(false,formatReturn($GLOBALS['error_codes']['wrong_input'],$token,$format,'')));
	}
	}
	else {
		#local user was validated
		$key=random_string(15);
		$expires=date('Y-m-d', time()+(1 * 24 * 60 * 60));
	}

	if($valid)
		{
			$user_id = $user_info['account_id'];
			create_log($user_id, $db);
			
			if($createkey)
				{
				if(!$key) $key=random_string(15);
				if(!$expires) $expires = date('Y-m-d', time()+(1 * 24 * 60 * 60));
					$inputs = array('key_id'=>$key, 'expires'=>$expires, 'notes'=>'Key generated automatically via API', 'account_id'=>$user_id);
					$added = add_entry ('access_keys', $inputs, $db);
					$data[0] = $inputs;$letter ='E'; 
					$pack= compact('data', 'user_id','db', 'letter','t','format');
				if($added){
				return(array(true,completeDisplay($pack), $user_info));
				exit;
				}
				else {
				return(array(false,formatReturn('2', 'Your authentication was valid but a key could not be created.', $format,'')));
				exit;	
				}

				}
			else{
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
			return(array(true,formatReturn('0', 'User validated.', $format,''), $user_info));
				exit;
			}
			
			

		}
		else {
			return(array(false,formatReturn($GLOBALS['error_codes']['wrong_input'], 'Please provide a valid username and password', $format,'')));
			exit;
		}
	}
	elseif($username!='')
	{
	return(array(false,formatReturn($GLOBALS['error_codes']['wrong_input'], 'Please provide a valid password', $format,'')));
	exit;
	}
	elseif($password!='')
	{
	return(array(false,formatReturn($GLOBALS['error_codes']['wrong_input'], 'Please provide a valid username', $format,'')));
	exit;
	}
	else {
	return(array(false,formatReturn($GLOBALS['error_codes']['something_missing'], 'Please provide a valid username and password', $format,'')));
	exit;
	}
}


function univ_authenticate($user_id, $pass, $auth=false,$prot=false,$serv_account=false)
{
	#some authorities can be endorsed - that means that, if they are authenticated, a user account may be created frmo them immediatelly;


	if(ereg('^(http:|https:|ldap:|ftp:|smtp:){0,1}(.*):(.*)$',$user_id,$res) && !$prot){
	#ereg('([a-zA-Z0-9]+:){2,}',$user_id,$res);
	list($usId, $prot, $auth, $email)=$res;
	$valid=0;
	$prot=ereg_replace(':$','',$prot);
	}
	else {
	$email= $user_id;
	}

	switch ($prot) {

	case 'ldap':

		#if($auth=='mdanderson'){
		#$server = 'ldaps://ldap.mdanderson.org:636';
		#}
		#elseif($auth=='eApps') {
		#$server = 'ldap://s3db.virtual.vps-host.net';	
		#if(!$email) $email = 'cn=Manager, dc=my-domain, dc=com';
		#if(!$pass) $pass = 's3db.virtual.vps-host.net';
		#}
		$server = $auth;
		list($valid,$token) = ldap_auth($server,$email, $pass,$serv_account);
		

	break;

	case 'ftp':
		$valid = ftp_auth($auth,$email,$pass);
		
	break;

	default :
		
		switch ($auth) {
			case 'google':
			
			list($valid,$token) = google_auth("https://www.google.com/accounts/ClientLogin",$email, $pass);	
			$token=md5($token);
			$expires = date('Y-m-d', time()+(1 * 24 * 60 * 60)); ##token actually lasts for 2 weeks :O, but i will leave it 24 h for now.
			break;
			
			default :
				
				if(ereg('^s3db',$auth)){
				#start by resolving the s3db uri
				ereg('(s3db:){0,1}(.*)$',$auth,$s3);
				list($s3_auth,$s3root,$s3name)=$s3;
				switch ($s3name) {
				case 'TCGA':
					$url = 'http://ibl.mdanderson.org/TCGA';
				break;
			
				default :
					$url = $GLOBALS['s3db_info']['deployment']['mothership'].$s3name;
				}
				
				list($valid, $token, $expires)=s3db_auth($url,$email, $pass);#try s3db auth

				
				}
				else {
					$valid=0;
					$token = "User was not validated.";

				}
		}
	}
	return (array($valid, $token,$expires));
}

function ldap_auth($server,$email, $pass,$serv_account=false)
{
	$ext=get_loaded_extensions();
	if(!in_array('ldap',$ext)){
	return (array(False, "The LDAP PHP module is disabled."));
	}

	if(!ereg('^ldap',$server)) $server = 'ldap:\\'.$server;


	$ldapconn = @ldap_connect($server);

	$ldapbind = @ldap_bind($ldapconn,$email, $pass);	

	/* Too many invalid login block the account :-( :-(
	if(!$ldapbind){
	#try decrypting the password
	$pass = @decrypt($pass, $GLOBALS['s3db_info']['deployment']['private_key']);
	$ldapbind = @ldap_bind($ldapconn,$email, $pass);

	}
	*/
	if($ldapbind &&  $pass!=''){

	return (array(True));
	}
	else {
	#use the service account, if one was provided


	if($serv_account){

	extract($serv_account);

	}
	$password = decrypt($password, $GLOBALS['s3db_info']['deployment']['private_key']);
	$ldapbind = @ldap_bind($ldapconn,$username, $password);	

	/* Too many invalid login block the account :-( :-(
	if(!$ldapbind){
	#try again, decrypt pass first
		
	$ldapbind = @ldap_bind($ldapconn,$username, $password);	
	}
	*/
	if(!$ldapbind){
	return (array(False, "The service account is invalid."));
	}
	else{

	$ureturn=@ldap_search($ldapconn, $dnbase, 'cn='.$email);

	$uent=@ldap_first_entry($ldapconn, $ureturn);
	$bn=@ldap_get_dn($ldapconn, $uent);

	if($pass!='' && $bn!='')
	{
	$lbind=@ldap_bind($ldapconn, $bn, $pass);
	if($lbind){
		return (array(True));
	}
	}

	}


	return (False);
	}
}

function s3db_auth($auth,$email, $pass)
{
	$uri =(substr($auth, -1, 1)=='/')?$auth:$auth.'/';
	$uri .= 'apilogin.php?username='.$email.'&password='.$pass.'&format=php';
	
	
	$d=@fopen($uri, "r");
	$dd=@stream_get_contents($d);
	$data=@unserialize($dd);
	
	if($data[0]['key_id']!=''){
	return (array(True, $data[0]['key_id'], $data[0]['expires']));
	}
	else {
		return (array(False));
	}
}

function google_auth($auth,$email, $pass)
{
	$gacookie="gacookie";
	$url = $auth;
	$postdata="Email=".$email."&Passwd=".$pass."&accountType=GOOGLE&service=apps";
	$referer='https://www.google.com/accounts/ClientLogin';
	$result=doPost(compact('gacookie','postdata','url','referer'));
	
	
	ereg('Auth=(.*)',$result,$googToken);
	$Token = $googToken[1];
	if($Token){
	return (array(True, $Token));
	}
	else {
		return (array(False));
	}

}

function ftp_auth($auth,$email,$pass)
{	
	$conn_id = ftp_connect($auth); 

	// login with username and password
	$login_result = ftp_login($conn_id, $email,$pass); 	

		if($login_result){
		return (array(True, $login_result));
		}
		else {
			return (array(False));
		}
}

function doPost($d)
{
	extract($d);
	$ch = curl_init();
	curl_setopt ($ch, CURLOPT_URL,$url);
	curl_setopt ($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
	#curl_setopt ($ch, CURLOPT_USERAGENT, "Mozilla/5.0 (Windows; U; Windows NT 5.1; en-US; rv:1.8.1.6) Gecko/20070725 Firefox/2.0.0.6");
	curl_setopt ($ch, CURLOPT_TIMEOUT, 60);
	curl_setopt ($ch, CURLOPT_FOLLOWLOCATION, 1);
	curl_setopt ($ch, CURLOPT_RETURNTRANSFER, 1);
	curl_setopt ($ch, CURLOPT_COOKIEJAR, $gacookie);
	curl_setopt ($ch, CURLOPT_COOKIEFILE, $gacookie);
	curl_setopt ($ch, CURLOPT_REFERER, $referer);
	curl_setopt ($ch, CURLOPT_POSTFIELDS, $postdata);
	curl_setopt ($ch, CURLOPT_POST, 1);
	$AskApache_result = curl_exec ($ch);
	curl_close($ch);
	$result = $AskApache_result;
	return ($result);	
}


function auth_user_api($login, $user_id,$password, $db)
	{
		
		if($login){
		$sql = "select * from s3db_account where (account_lid='".$login."' or account_email='".$login."' or account_id='".ereg_replace('^U','',$login)."') and account_pwd='".md5($password)."'";
		#echo $sql;
		}
		elseif($user_id){
		
		if(ereg('U([0-9]+)$',$user_id, $uid))
			{$u = $uid[1];}
		else {
			$u = $user_id;
		}
		$sql = "select * from s3db_account where (account_id='".$u."' or account_email='".$u."') and account_pwd='".md5($password)."'";
		}
		
		$db->query($sql, __LINE__, __FILE__);
		$db->next_record();
		
		
		if($db->f('account_id') != '') {
			
			if($db->f('account_status')=='A')
			{	
				$user_info = array('account_id' => $db->f('account_id'),
				      'account_lid' => $db->f('account_lid'),
				      'account_uname' => $db->f('account_uname'),
				      'account_group' => $db->f('account_group'),
					'account_type' => $db->f('account_type'));
				$_SESSION['user'] = $user_info;	
				
				return $validate = array(True, $user_info);
			}
			
	
		else
		{
			return (array(false,'bad login or password'));
			$_SESSION['db'] = '';	
			$_SESSION['user'] = '';	
			exit;
		}
		}
		return (array(False,'bad login or password'));
	}
	
	
	function create_log($login_id, $db)
	{
		
		$sql ="insert into s3db_access_log (login_timestamp, session_id, login_id, ip) values(now(), '".session_id()."','".$login_id."','".$_SERVER['REMOTE_ADDR']."')";
		//$sql ="insert into s3db_access_log (login_timestamp, login_id, ip) values(now(),"."'".$login_id."','".$_SERVER['REMOTE_ADDR']."')";
		//echo $sql;
		$db->query($sql, __LINE__, __FILE__);
		$db->next_record();
		$sql = "update s3db_account set account_last_login_on =now(), account_last_login_from ='".$_SERVER['REMOTE_ADDR']."' where account_lid='".$login_id."'";
		//echo $sql;
		$db->query($sql, __LINE__, __FILE__);
		$db->next_record();
	}


function validate_authentication($s3ql,$user_id,$db)
	{	 
		
			
			$format=($s3ql['format']!='')?$s3ql['format']:'html';
			#user must already exist before a new authentication can be created for him
			$user2update = 	ereg_replace('^U','',$s3ql['where']['user_id']);
			$sql = "select * from s3db_account where account_id ='".$user2update."'";
			
			$db->query($sql);
			if(!$db->next_record() && $s3ql['insert']!=''){
				
				$msg=formatReturn($GLOBALS['error_codes']['something_missing'],'User '.$user2update.' does not exist.', $format,'');
				return (array(false, $msg));
				exit;
			}
			
			
			#autentication must not exist already for any other user. This is a task for the Admin
			$user_proj=file_get_contents($GLOBALS['uploads'].'/userManage.s3db');$user_proj=unserialize($user_proj);
			$sql = "select * from s3db_statement where rule_id = '".$user_proj['email']['rule_id']."' and value ='".$s3ql['where']['authentication_id']."'";
			
			#echo $sql;exit;
			$db->query($sql);
			
			if($db->next_record() && $s3ql['insert']!=''){
				
				$msg=formatReturn($GLOBALS['error_codes']['something_missing'],'Authentication '.$s3ql['where']['authentication_id'].' already exists.', $format,'');
				return (array(false, $msg));
				exit;
			}

			#authentication must not also exist as a remote user id
			$sql = "select * from s3db_account where account_id ='".$s3ql['where']['authentication_id']."'";
			#echo $sql;exit;
			$db->query($sql);
			
			if($db->next_record() && $s3ql['insert']!=''){
				
				$msg=formatReturn($GLOBALS['error_codes']['something_missing'],'Authentication '.$s3ql['where']['authentication_id'].' already exists.', $format,'');
				return (array(false, $msg));
				exit;
			}
			
			return (array(true));	
					
		
	}
function insert_authentication_tuple($c)
	{
		#user project is a structure that holds the information, from s3db, about users.
		#for example: $user_project = array('project_id'=>'1','users'=>array('collection_id'=>'2'), 'groups'=>array('collection_id'=>'3'), 'email'=>array('rule_id'=>'4'), 'user_id'=>array('rule_id'=>'5'),'group_users'=>array('rule_id'=>'6'));
		
		extract($c);
			
		if(!$user2add && is_array($s3ql))
		$user2add = ereg_replace('^U','',$s3ql['where']['user_id']);
		
		$s3ql_new=compact('user_id','db');
		$s3ql_new['from']='statement';
		$s3ql_new['where']['rule_id']=$user_proj['user_id']['rule_id'];
		$s3ql_new['where']['value']=$user2add;
		$done = S3QLaction($s3ql_new);
		
		if(!empty($done)){
		$user_proj['users']['items'][$user2add]['item_id']=$done[0]['item_id'];
		$user_proj['users']['items'][$user2add]['statement_id']=$done[0]['statement_id'];
		
		}
		else{
		
		#let's create it
		$s3ql_new=compact('user_id','db');
		$s3ql_new['insert']='item';
		$s3ql_new['where']['notes']=$user2add;
		$s3ql_new['where']['collection_id']=$user_proj['users']['collection_id'];					
		$s3ql_new['format']='php';
		
		
		$done = S3QLaction($s3ql_new);
		
		$msg=unserialize($done);$msg=$msg[0];
		$user_proj['users']['items'][$user2add]['item_id']= $msg['item_id'];
		if($msg['item_id']){
		file_put_contents($GLOBALS['uploads'].'user_proj', serialize($user_proj));
		
		
		#give user permission to his own item
		$s3ql_new=compact('user_id','db');
		$s3ql_new['insert']='user';
		$s3ql_new['where']['user_id']=$user2add;
		$s3ql_new['where']['item_id']=$user_proj[$user2add]['I'];
		$s3ql_new['where']['permission_level']='yyy';
		$s3ql_new['format'] = 'php';
		S3QLaction($s3ql_new);
		//$msg=unserialize($done);$msg = $msg[0];
		
		#and don't give him permission to any of the other items in the colection, so this we manipulate at the collection level
		$s3ql_new=compact('user_id','db');
		$s3ql_new['insert']='user';
		$s3ql_new['where']['user_id']=$user2add;
		$s3ql_new['where']['collection_id']=$user_proj['users']['collection_id'];
		$s3ql_new['where']['permission_level']='ynyn';
		$s3ql_new['format'] = 'php';
		S3QLaction($s3ql_new);
		//$msg=unserialize($done);$msg = $msg[0];

		#but do let him add to the rule, as long as the item is his own
		$s3ql_new=compact('user_id','db');
		$s3ql_new['insert']='user';
		$s3ql_new['where']['user_id']=$user2add;
		$s3ql_new['where']['rule_id']=$user_proj['email']['rule_id'];
		$s3ql_new['where']['permission_level']='ynys';
		$s3ql_new['format'] = 'php';
		
		S3QLaction($s3ql_new);
		}
		
		#now let's create an statement for this user
		if(!$user_proj[$user2add]['R'.$user_proj['user_id']['rule_id']]){
		$s3ql_new=compact('user_id','db');
		$s3ql_new['insert']='statement';
		$s3ql_new['where']['rule_id']=$user_proj['user_id']['rule_id'];
		$s3ql_new['where']['item_id']=$user_proj['users']['items'][$user2add]['item_id'];
		$s3ql_new['where']['value']=$user2add;
		$s3ql_new['format']='php';
		$done = S3QLaction($s3ql_new);
		
		$msg=unserialize($done);$msg = $msg[0];
		
		if($msg['statement_id']){ 
		$s3ql_new=compact('user_id','db');
		$s3ql_new['insert']='user';
		$s3ql_new['where']['user_id']=$user2add;
		$s3ql_new['where']['statement_id']=$msg['statement_id'];
		$s3ql_new['where']['permission_level']='ynn';#can see but cannot touch
		$s3ql_new['format'] = 'php';
		S3QLaction($s3ql_new);
		
		
		$user_proj[$user2add]['R'.$user_proj['user_id']['rule_id']]=$msg['statement_id'];}
		file_put_contents($GLOBALS['uploads'].'/userManage.s3db', serialize($user_proj));
		}
		
		
		
		
		}
		
		return ($user_proj);
	}

function create_authentication_proj($db)
{
	#If user is not generic admin, he cannot create the project
	$user_id='1';
	
	if(!is_object($db)){
		#this part stays contained in this function
		$db = CreateObject('s3dbapi.db');
		$db->Halt_On_Error = 'no';
		$db->Host     = $GLOBALS['s3db_info']['server']['db']['db_host'];
		$db->Type     = $GLOBALS['s3db_info']['server']['db']['db_type'];
		$db->Database = $GLOBALS['s3db_info']['server']['db']['db_name'];
		$db->User     = $GLOBALS['s3db_info']['server']['db']['db_user'];
		$db->Password = $GLOBALS['s3db_info']['server']['db']['db_pass'];
		$db->connect();
		}
	if(eregi('^usermanagement$', $_REQUEST['clean'])){
		if(is_file($GLOBALS['uploads'].'user_proj')) {unlink($GLOBALS['uploads'].'user_proj');}
	}
	$user_proj = @unserialize(@file_get_contents($GLOBALS['uploads'].'user_proj'));
	
	
	$project_name = 'Config';
	$important_collections = array('Users','Groups', 'Protocols','Authorities');
	$important_rules = array('Users'=>array(
												array('verb'=>'authenticationAlternative','object'=>'email'),			
												array('verb'=>'identifier','object'=>'user_id')),
							'Authorities'=>array(array('verb'=>'validatesThrough','object'=>'Protocols'), 
														array('verb'=>'validatesAt','object'=>'URI'), 
														array('verb'=>'have','object'=>'DisplayLabel'),
														array('verb'=>'is','object'=>'Endorsed'),
														array('verb'=>'usernameReplaces','object'=>'Template'),
														array('verb'=>'ldap','object'=>'serviceAccountUserName'),
														array('verb'=>'ldap','object'=>'serviceAccountPassword'),
														array('verb'=>'ldap','object'=>'dnbase')
														)
							);
	$default_items = array('Protocols'=>array('ldap'=>'','ftp'=>'','http'=>''),			
							'Authorities'=>array('google'=>array('URI'=>'https://www.google.com/accounts/ClientLogin', 'Protocols'=>'http', 'DisplayLabel'=>'google')));
	
	
	#and now, as a final obsessive step... try to create it again;
	
	$user_proj=check_user_management(compact('user_proj', 'user_id','db', 'important_collections','important_rules','default_items','project_name'));
	
	return ($user_proj);

	
}

function check_user_management1($C)
	{extract($C);
			
		#Now let's check again if we have every necessary item
		#Project
		#$user_proj=array();
		$s3ql=compact('user_id','db');
		$s3ql['from']='project';
		$s3ql['where']['name']=$project_name;
		$s3ql['order_by']='created_on desc';
		$done = S3QLaction($s3ql);
		
		if($user_proj['project_id']!="" && $user_proj['project_id']!=$done[0]['project_id']){
			$user_proj = array();
			$user_proj['project_id']= $done[0]['project_id'];
			unlink($GLOBALS['uploads'].'user_proj');
			$file_fill=1;#this means that the project in file is not in sync with the proj on s3db
		}
		else {
			$user_proj['project_id'] = $done[0]['project_id'];
		}
		
		if($done[0]['project_id']==""){
		
			 $s3ql=compact('user_id','db');
			 $s3ql['insert']='project';
			 $s3ql['where']['name']=$project_name;
			 $s3ql['format']='php';
			 
			 $done = S3QLaction($s3ql);$msg=unserialize($done);$msg = $msg[0];
			 $user_proj['project_id']  = $msg['project_id'];	
			 
		##Make project data public
		$s3ql=compact('user_id','db');
		$s3ql['insert']='user';
		$s3ql['where']['project_id']=$user_proj['project_id'];
		$s3ql['where']['user_id']='2';
		$s3ql['where']['permission_level']='nnnynn';
		$s3ql['format']='php';
		$done = S3QLaction($s3ql);$msg=unserialize($done);$msg = $msg[0];
		if($msg['project_id']) $user_proj['project_id'] = $msg['project_id'];
		}
		
		
		
		#Collections and Rules
		
		foreach ($important_collections as $name) {
			##Now chceck for collections and rule; if there were created as part of the original file project, we don't need to create/check them again
			
			if($file_fill || !$user_proj[strtolower($name)]['collection_id']){

			$s3ql=compact('user_id','db');
			$s3ql['from']='collections';
			$s3ql['where']['project_id']=$user_proj['project_id'];
			$s3ql['where']['name']=$name;
			$done = S3QLaction($s3ql);
			
			
			if(!$done[0]['collection_id']){			
			$s3ql=compact('user_id','db');
			$s3ql['insert']='collection';
			$s3ql['where']['project_id']=$user_proj['project_id'];
			$s3ql['where']['name']=$name;
			$s3ql['format']='php';
			$done = S3QLaction($s3ql);$msg=unserialize($done);$msg = $msg[0];
			
			$user_proj[strtolower($name)]['collection_id'] = $msg['collection_id'];
			}
			else {
			$user_proj[strtolower($name)]['collection_id'] = $done[0]['collection_id'];	
			}
			
			}
		}
		
			
		
	   foreach ($important_rules as $subName=>$subNameRule) {
				foreach ($subNameRule as $ind=>$rule_info) {
				
				
				#if(!is_array($user_proj[strtolower($subName)]['rule_objects']) || !in_array($rule_info['object'], $user_proj[strtolower($subName)]['rule_objects'])){
					if($file_fill || !$user_proj[$rule_info['object']]['rule_id']){
					$s3ql=compact('user_id','db');
					$s3ql['insert']='rule';
					$s3ql['where']['project_id']=$user_proj['project_id'];
					$s3ql['where']['subject_id']=$user_proj[strtolower($subName)]['collection_id'];
					$s3ql['where']['verb']=$rule_info['verb'];
					if(in_array($rule_info['object'], $important_collections)){
						$s3ql['where']['object_id']=$user_proj[strtolower($rule_info['object'])]['collection_id'];
						
					}
					else {
						$s3ql['where']['object']=$rule_info['object'];
					}
					$s3ql['format']='php';
					$done = S3QLaction($s3ql);$msg=unserialize($done);$msg = $msg[0]; 
					
					$user_proj[$rule_info['object']]['rule_id'] = $msg['rule_id'];
					
					$user_proj[strtolower($subName)]['rules'][$ind]=$msg['rule_id'];
					$user_proj[strtolower($subName)]['rule_objects'][$ind]=($s3ql['where']['object_id']!='')?$s3ql['where']['object_id']:$s3ql['where']['object'];
					
					$user_proj[strtolower($subName)]['rule_object_is_id'][$ind]=($s3ql['where']['object_id']!='')?1:0;
				   
				}
				
				#Make statements in some authorities rule hidden from public
				if(eregi('serviceAccountPassword|serviceAccountUserName|Template',$rule_info['object'])){
					$s3ql=compact('user_id','db');
					$s3ql['insert']='user';
					$s3ql['where']['rule_id']=$user_proj[$rule_info['object']]['rule_id'];
					$s3ql['where']['user_id']='2';
					$s3ql['where']['permission_level']='ynnN';
					$s3ql['format']='php';
					$done = S3QLaction($s3ql);$msg=unserialize($done);$msg = $msg[0];
				}
				
				}
		}
		
	
		##Items

		foreach ($default_items as $collection_name=>$itemData) {
			  
			   foreach ($itemData as $itemNotes=>$itemStatVals) {
				   
					if($file_fill || $user_proj[strtolower($collection_name)]['items'][$itemNotes]['item_id']==''){
						$s3ql=compact('user_id','db');
						$s3ql['insert']='item';
						$s3ql['where']['collection_id']=$user_proj[strtolower($collection_name)]['collection_id'];
						$s3ql['where']['notes']=$itemNotes;
						$s3ql['format']='php';
						$done = S3QLaction($s3ql);$msg=unserialize($done);$msg = $msg[0];
						$user_proj[strtolower($collection_name)]['items'][$itemNotes]['item_id']=$msg['item_id'];
						
						
						
					}
					
					$jin++;
					if(is_array($itemStatVals)){
					foreach ($itemStatVals as $objectName=>$objectValue) {
						if($file_fill || $user_proj[strtolower($collection_name)][$itemNotes]['statements'][$objectName]['statement_id']=='')
						{
							$s3ql=compact('user_id','db');
							$s3ql['insert']='statement';
							$s3ql['where']['item_id']=$user_proj[strtolower($collection_name)]['items'][$itemNotes]['item_id'];
							#find the right rule_id
							$right_rule_id = array_search($objectName, $user_proj[strtolower($collection_name)]['rule_objects']);
							
							$s3ql['where']['rule_id']=$user_proj[strtolower($collection_name)]['rules'][$right_rule_id];
							#determine if the object value should be retrieved from another collection's items
							if($user_proj[strtolower($collection_name)]['rule_object_is_id'][$right_rule_id]){
								$s3ql['where']['value']= $user_proj[strtolower($objectName)]['items'][$objectValue]['item_id'];
							}
							else{
							$s3ql['where']['value']=$objectValue;
							}
							$s3ql['format']='php';
							$done = S3QLaction($s3ql);
							$msg=unserialize($done);$msg = $msg[0];
							$user_proj[strtolower($collection_name)][$itemNotes]['statements'][$objectName]['statement_id']=$msg['statement_id'];
							$user_proj[strtolower($collection_name)][$itemNotes]['statements'][$objectName]['value']=$objectValue;
							
							
						}
					}
					}
			   }
			  
		}
	  	$user_proj['protocols']['names'] = 	array_keys($user_proj['protocols']['items']);
		$user_proj['protocols']['item_id']=array();
		foreach ($user_proj['protocols']['names'] as $nm) {
			$user_proj['protocols']['item_id'][] = 	$user_proj['protocols']['items'][$nm]['item_id'];
		}
		file_put_contents($GLOBALS['uploads'].'user_proj', serialize($user_proj));
		#echo '<pre>';print_r($user_proj);exit;
		return ($user_proj);

	}

function check_user_management($C)
	{extract($C);
		
		#Now let's check again if we have every necessary item
		#Project
		#$user_proj=array();
		$s3ql=compact('user_id','db');
		$s3ql['from']='project';
		$s3ql['where']['name']=$project_name;
		$s3ql['order_by']='created_on desc';
		$done = S3QLaction($s3ql);
		
		if($user_proj['project_id']!="" && $user_proj['project_id']!=$done[0]['project_id']){
			$user_proj = array();
			$user_proj['project_id']= $done[0]['project_id'];
			unlink($GLOBALS['uploads'].'user_proj');
			$file_fill=1;#this means that the project in file is not in sync with the proj on s3db
		}
		else {
			$user_proj['project_id'] = $done[0]['project_id'];
		}
		
		if($done[0]['project_id']==""){
		
			 $s3ql=compact('user_id','db');
			 $s3ql['insert']='project';
			 $s3ql['where']['name']=$project_name;
			 $s3ql['format']='php';
			 
			 $done = S3QLaction($s3ql);$msg=unserialize($done);$msg = $msg[0];
			 $user_proj['project_id']  = $msg['project_id'];	
			 
		##Make project data public
		$s3ql=compact('user_id','db');
		$s3ql['insert']='user';
		$s3ql['where']['project_id']=$user_proj['project_id'];
		$s3ql['where']['user_id']='2';
		$s3ql['where']['permission_level']='nnnynn';
		$s3ql['format']='php';
		$done = S3QLaction($s3ql);$msg=unserialize($done);$msg = $msg[0];
		if($msg['project_id']) $user_proj['project_id'] = $msg['project_id'];
		}
		
		
		
		#Collections and Rules
		
		foreach ($important_collections as $name) {
			##Now chceck for collections and rule; if there were created as part of the original file project, we don't need to create/check them again
			
			if($file_fill || !$user_proj[strtolower($name)]['collection_id']){
		    $done=array();
			$s3ql=compact('user_id','db');
			$s3ql['from']='collections';
			$s3ql['where']['project_id']=$user_proj['project_id'];
			$s3ql['where']['name']=$name;
			$done = S3QLaction($s3ql);
			
			
			if(!$done[0]['collection_id']){			
			$done=array();
			$s3ql=compact('user_id','db');
			$s3ql['insert']='collection';
			$s3ql['where']['project_id']=$user_proj['project_id'];
			$s3ql['where']['name']=$name;
			$s3ql['format']='php';
			$done = S3QLaction($s3ql);$msg=unserialize($done);$msg = $msg[0];
			
			$user_proj[strtolower($name)]['collection_id'] = $msg['collection_id'];
			}
			else {
			$user_proj[strtolower($name)]['collection_id'] = $done[0]['collection_id'];	
			}
			
			}
		}
		
			
		
	   foreach ($important_rules as $subName=>$subNameRule) {
				
				foreach ($subNameRule as $ind=>$rule_info) {
				$done=array();
				if($file_fill || !$user_proj[$rule_info['object']]['rule_id']){
				
				$s3ql=compact('user_id','db');
				$s3ql['from']='rules';
				$s3ql['where']['project_id']=$user_proj['project_id'];
				$s3ql['where']['subject']=$subName;
				$s3ql['where']['verb']=$rule_info['verb'];
				$s3ql['where']['object']=$rule_info['object'];
				$done = S3QLaction($s3ql);
				
				
				if(!$done[0]['rule_id'])
				{	$done=array();
					$s3ql=compact('user_id','db');
					$s3ql['insert']='rule';
					$s3ql['where']['project_id']=$user_proj['project_id'];
					$s3ql['where']['subject_id']=$user_proj[strtolower($subName)]['collection_id'];
					$s3ql['where']['verb']=$rule_info['verb'];
					if(in_array($rule_info['object'], $important_collections)){
						$s3ql['where']['object_id']=$user_proj[strtolower($rule_info['object'])]['collection_id'];
						
					}
					else {
						$s3ql['where']['object']=$rule_info['object'];
					}
					$s3ql['format']='php';
					$done = S3QLaction($s3ql);$msg=unserialize($done);$msg = $msg[0]; 
					
					$user_proj[$rule_info['object']]['rule_id'] = $msg['rule_id'];
					
					$user_proj[strtolower($subName)]['rules'][$ind]=$msg['rule_id'];
					$user_proj[strtolower($subName)]['rule_objects'][$ind]=($s3ql['where']['object_id']!='')?$s3ql['where']['object_id']:$s3ql['where']['object'];
					
					$user_proj[strtolower($subName)]['rule_object_is_id'][$ind]=($s3ql['where']['object_id']!='')?1:0;
				   
				}
				else {
				$user_proj[$rule_info['object']]['rule_id'] = $done[0]['rule_id'];
				$user_proj[strtolower($subName)]['rules'][$ind]=$done[0]['rule_id'];
				$user_proj[strtolower($subName)]['rule_objects'][$ind]=($s3ql['where']['object_id']!='')?$s3ql['where']['object_id']:$s3ql['where']['object'];
					
				$user_proj[strtolower($subName)]['rule_object_is_id'][$ind]=($s3ql['where']['object_id']!='')?1:0;
				}
				}
				#Make statements in some authorities rule hidden from public
				if(eregi('serviceAccountPassword|serviceAccountUserName|Template',$rule_info['object'])){	$done=array();
					$s3ql=compact('user_id','db');
					$s3ql['insert']='user';
					$s3ql['where']['rule_id']=$user_proj[$rule_info['object']]['rule_id'];
					$s3ql['where']['user_id']='2';
					$s3ql['where']['permission_level']='ynnN';
					$s3ql['format']='php';
					$done = S3QLaction($s3ql);$msg=unserialize($done);$msg = $msg[0];
				}
				
				}
		}
		
	
		##Items

		foreach ($default_items as $collection_name=>$itemData) {
			  
			   foreach ($itemData as $itemNotes=>$itemStatVals) {
				   $done=array();
					$this_item_id="";
					if($file_fill || $user_proj[strtolower($collection_name)]['items'][$itemNotes]['item_id']==''){
						
						$s3ql=compact('user_id','db');
						$s3ql['from']='item';
						$s3ql['where']['collection_id']=$user_proj[strtolower($collection_name)]['collection_id'];
						//$s3ql['where']['notes']=$itemNotes;
						$done = S3QLaction($s3ql);
						if($done){
						foreach ($done as $item_info) {
							if($item_info['notes']==$itemNotes){
								$this_item_id = $item_info['item_id'];
							}
						}
						}
					
					
					if(!$this_item_id){
						
						$s3ql['insert']='item';
						$s3ql['where']['collection_id']=$user_proj[strtolower($collection_name)]['collection_id'];
						$s3ql['where']['notes']=$itemNotes;
						$s3ql['format']='php';
						$done = S3QLaction($s3ql);$msg=unserialize($done);$msg = $msg[0];
						$user_proj[strtolower($collection_name)]['items'][$itemNotes]['item_id']=$msg['item_id'];
						  $this_item_id = $user_proj[strtolower($collection_name)]['items'][$itemNotes]['item_id'];
						
						
					}
					else {
						$user_proj[strtolower($collection_name)]['items'][$itemNotes]['item_id']= $this_item_id;
						
					}
					}
					
					$jin++;
					if(is_array($itemStatVals)){
					foreach ($itemStatVals as $objectName=>$objectValue) {
						$done=array();
						if($file_fill || $user_proj[strtolower($collection_name)][$itemNotes]['statements'][$objectName]['statement_id']=='')
						{
							$s3ql=compact('user_id','db');
							$s3ql['from']='statement';
							$s3ql['where']['item_id']=$user_proj[strtolower($collection_name)]['items'][$itemNotes]['item_id'];
							#find the right rule_id
							$right_rule_id = array_search($objectName, $user_proj[strtolower($collection_name)]['rule_objects']);
							
							$s3ql['where']['rule_id']=$user_proj[strtolower($collection_name)]['rules'][$right_rule_id];
							#determine if the object value should be retrieved from another collection's items
							if($user_proj[strtolower($collection_name)]['rule_object_is_id'][$right_rule_id]){
								$s3ql['where']['value']= $user_proj[strtolower($objectName)]['items'][$objectValue]['item_id'];
							}
							else{
							$s3ql['where']['value']=$objectValue;
							}
							$done = S3QLaction($s3ql);
						
						if(!$done[0]['statement_id']){
							$s3ql=compact('user_id','db');
							$s3ql['insert']='statement';
							$s3ql['where']['item_id']=$user_proj[strtolower($collection_name)]['items'][$itemNotes]['item_id'];
							#find the right rule_id
							$right_rule_id = array_search($objectName, $user_proj[strtolower($collection_name)]['rule_objects']);
							
							$s3ql['where']['rule_id']=$user_proj[strtolower($collection_name)]['rules'][$right_rule_id];
							#determine if the object value should be retrieved from another collection's items
							if($user_proj[strtolower($collection_name)]['rule_object_is_id'][$right_rule_id]){
								$s3ql['where']['value']= $user_proj[strtolower($objectName)]['items'][$objectValue]['item_id'];
							}
							else{
							$s3ql['where']['value']=$objectValue;
							}
							$s3ql['format']='php';
							$done = S3QLaction($s3ql);
							$msg=unserialize($done);$msg = $msg[0];
							if($rule_id=='16229'){
							echo '<pre>';print_r($done);
							echo '<pre>';print_r($msg);
							echo '<pre>';print_r($s3ql);
							}
							
							$user_proj[strtolower($collection_name)][$itemNotes]['statements'][$objectName]['statement_id']=$msg['statement_id'];
							$user_proj[strtolower($collection_name)][$itemNotes]['statements'][$objectName]['value']=$objectValue;
							
							
						}
						else {
						$user_proj[strtolower($collection_name)][$itemNotes]['statements'][$objectName]['statement_id']=$done[0]['statement_id'];	
						}
						}
					}
					}
			   }
			  
		}
	  	
		$user_proj['protocols']['names'] = 	array_keys($user_proj['protocols']['items']);
		$user_proj['protocols']['item_id']=array();
		foreach ($user_proj['protocols']['names'] as $nm) {
			$user_proj['protocols']['item_id'][] = 	$user_proj['protocols']['items'][$nm]['item_id'];
		}
		file_put_contents($GLOBALS['uploads'].'user_proj', serialize($user_proj));
		
		return ($user_proj);

	}

function check_user_management2($C)
	{extract($C);
			
		#Now let's check again if we have every necessary item
		#Project
		#$user_proj=array();
		$s3ql=compact('user_id','db');
		$s3ql['from']='project';
		$s3ql['where']['name']=$project_name;
		$s3ql['order_by']='created_on desc';
		$done = S3QLaction($s3ql);
		
		if($user_proj['project_id']!="" && $user_proj['project_id']!=$done[0]['project_id']){
			$user_proj = array();
			$user_proj['project_id']= $done[0]['project_id'];
			unlink($GLOBALS['uploads'].'user_proj');
			$file_fill=1;#this means that the project in file is not in sync with the proj on s3db
		}
		else {
			$user_proj['project_id'] = $done[0]['project_id'];
		}
		
		if($done[0]['project_id']==""){
		
			 $s3ql=compact('user_id','db');
			 $s3ql['insert']='project';
			 $s3ql['where']['name']=$project_name;
			 $s3ql['format']='php';
			 
			 $done = S3QLaction($s3ql);$msg=unserialize($done);$msg = $msg[0];
			 $user_proj['project_id']  = $msg['project_id'];	
			 
		##Make project data public
		$s3ql=compact('user_id','db');
		$s3ql['insert']='user';
		$s3ql['where']['project_id']=$user_proj['project_id'];
		$s3ql['where']['user_id']='2';
		$s3ql['where']['permission_level']='nnnynn';
		$s3ql['format']='php';
		$done = S3QLaction($s3ql);$msg=unserialize($done);$msg = $msg[0];
		if($msg['project_id']) $user_proj['project_id'] = $msg['project_id'];
		}
		
		
		
		#Collections and Rules
		
		foreach ($important_collections as $name) {
			##Now chceck for collections and rule; if there were created as part of the original file project, we don't need to create/check them again
			
			if($file_fill || !$user_proj[strtolower($name)]['collection_id']){
		    $done=array();
			$s3ql=compact('user_id','db');
			$s3ql['from']='collections';
			$s3ql['where']['project_id']=$user_proj['project_id'];
			$s3ql['where']['name']=$name;
			$done = S3QLaction($s3ql);
			
			
			if(!$done[0]['collection_id']){			
			$done=array();
			$s3ql=compact('user_id','db');
			$s3ql['insert']='collection';
			$s3ql['where']['project_id']=$user_proj['project_id'];
			$s3ql['where']['name']=$name;
			$s3ql['format']='php';
			$done = S3QLaction($s3ql);$msg=unserialize($done);$msg = $msg[0];
			
			$user_proj[strtolower($name)]['collection_id'] = $msg['collection_id'];
			}
			else {
			$user_proj[strtolower($name)]['collection_id'] = $done[0]['collection_id'];	
			}
			
			}
		}
		
			
		
	   foreach ($important_rules as $subName=>$subNameRule) {
				
				foreach ($subNameRule as $ind=>$rule_info) {
				$done=array();
				if($file_fill || !$user_proj[$rule_info['object']]['rule_id']){
				
				$s3ql=compact('user_id','db');
				$s3ql['from']='rules';
				$s3ql['where']['project_id']=$user_proj['project_id'];
				$s3ql['where']['subject']=$subName;
				$s3ql['where']['verb']=$rule_info['verb'];
				$s3ql['where']['object']=$rule_info['object'];
				$done = S3QLaction($s3ql);
				}
				
				if(!$done[0]['rule_id'])
				{	$done=array();
					$s3ql=compact('user_id','db');
					$s3ql['insert']='rule';
					$s3ql['where']['project_id']=$user_proj['project_id'];
					$s3ql['where']['subject_id']=$user_proj[strtolower($subName)]['collection_id'];
					$s3ql['where']['verb']=$rule_info['verb'];
					if(in_array($rule_info['object'], $important_collections)){
						$s3ql['where']['object_id']=$user_proj[strtolower($rule_info['object'])]['collection_id'];
						
					}
					else {
						$s3ql['where']['object']=$rule_info['object'];
					}
					$s3ql['format']='php';
					$done = S3QLaction($s3ql);$msg=unserialize($done);$msg = $msg[0]; 
					
					$user_proj[$rule_info['object']]['rule_id'] = $msg['rule_id'];
					
					$user_proj[strtolower($subName)]['rules'][$ind]=$msg['rule_id'];
					$user_proj[strtolower($subName)]['rule_objects'][$ind]=($s3ql['where']['object_id']!='')?$s3ql['where']['object_id']:$s3ql['where']['object'];
					
					$user_proj[strtolower($subName)]['rule_object_is_id'][$ind]=($s3ql['where']['object_id']!='')?1:0;
				   
				}
				else {
				$user_proj[$rule_info['object']]['rule_id'] = $done[0]['rule_id'];
				$user_proj[strtolower($subName)]['rules'][$ind]=$done[0]['rule_id'];
				$user_proj[strtolower($subName)]['rule_objects'][$ind]=($s3ql['where']['object_id']!='')?$s3ql['where']['object_id']:$s3ql['where']['object'];
					
				$user_proj[strtolower($subName)]['rule_object_is_id'][$ind]=($s3ql['where']['object_id']!='')?1:0;
				}
				
				#Make statements in some authorities rule hidden from public
				if(eregi('serviceAccountPassword|serviceAccountUserName|Template',$rule_info['object'])){	$done=array();
					$s3ql=compact('user_id','db');
					$s3ql['insert']='user';
					$s3ql['where']['rule_id']=$user_proj[$rule_info['object']]['rule_id'];
					$s3ql['where']['user_id']='2';
					$s3ql['where']['permission_level']='ynnN';
					$s3ql['format']='php';
					$done = S3QLaction($s3ql);$msg=unserialize($done);$msg = $msg[0];
				}
				
				}
		}
		
	
		##Items

		foreach ($default_items as $collection_name=>$itemData) {
			  
			   foreach ($itemData as $itemNotes=>$itemStatVals) {
				   $done=array();
					if($file_fill || $user_proj[strtolower($collection_name)]['items'][$itemNotes]['item_id']==''){
						
						$s3ql=compact('user_id','db');
						$s3ql['from']='item';
						$s3ql['where']['collection_id']=$user_proj[strtolower($collection_name)]['collection_id'];
						$s3ql['where']['notes']=$itemNotes;
						$done = S3QLaction($s3ql);
					}
					
					if(!$done[0]['item_id']){
						
						$s3ql['insert']='item';
						$s3ql['where']['collection_id']=$user_proj[strtolower($collection_name)]['collection_id'];
						$s3ql['where']['notes']=$itemNotes;
						$s3ql['format']='php';
						$done = S3QLaction($s3ql);$msg=unserialize($done);$msg = $msg[0];
						$user_proj[strtolower($collection_name)]['items'][$itemNotes]['item_id']=$msg['item_id'];
						
						
						
					}
					else {
						$user_proj[strtolower($collection_name)]['items'][$itemNotes]['item_id']=$done[0]['item_id'];
					}
					
					$jin++;
					if(is_array($itemStatVals)){
					foreach ($itemStatVals as $objectName=>$objectValue) {
						$done=array();
						if($file_fill || $user_proj[strtolower($collection_name)][$itemNotes]['statements'][$objectName]['statement_id']=='')
						{
							$s3ql=compact('user_id','db');
							$s3ql['from']='statement';
							$s3ql['where']['item_id']=$user_proj[strtolower($collection_name)]['items'][$itemNotes]['item_id'];
							#find the right rule_id
							$right_rule_id = array_search($objectName, $user_proj[strtolower($collection_name)]['rule_objects']);
							
							$s3ql['where']['rule_id']=$user_proj[strtolower($collection_name)]['rules'][$right_rule_id];
							#determine if the object value should be retrieved from another collection's items
							if($user_proj[strtolower($collection_name)]['rule_object_is_id'][$right_rule_id]){
								$s3ql['where']['value']= $user_proj[strtolower($objectName)]['items'][$objectValue]['item_id'];
							}
							else{
							$s3ql['where']['value']=$objectValue;
							}
							$done = S3QLaction($s3ql);
						}
						if(!$done[0]['statement_id']){
							$s3ql=compact('user_id','db');
							$s3ql['insert']='statement';
							$s3ql['where']['item_id']=$user_proj[strtolower($collection_name)]['items'][$itemNotes]['item_id'];
							#find the right rule_id
							$right_rule_id = array_search($objectName, $user_proj[strtolower($collection_name)]['rule_objects']);
							
							$s3ql['where']['rule_id']=$user_proj[strtolower($collection_name)]['rules'][$right_rule_id];
							#determine if the object value should be retrieved from another collection's items
							if($user_proj[strtolower($collection_name)]['rule_object_is_id'][$right_rule_id]){
								$s3ql['where']['value']= $user_proj[strtolower($objectName)]['items'][$objectValue]['item_id'];
							}
							else{
							$s3ql['where']['value']=$objectValue;
							}
							$s3ql['format']='php';
							$done = S3QLaction($s3ql);
							$msg=unserialize($done);$msg = $msg[0];
							$user_proj[strtolower($collection_name)][$itemNotes]['statements'][$objectName]['statement_id']=$msg['statement_id'];
							$user_proj[strtolower($collection_name)][$itemNotes]['statements'][$objectName]['value']=$objectValue;
							
							
						}
						else {
						$user_proj[strtolower($collection_name)][$itemNotes]['statements'][$objectName]['statement_id']=$done[0]['statement_id'];	
						}
					}
					}
			   }
			  
		}
	  	
		$user_proj['protocols']['names'] = 	array_keys($user_proj['protocols']['items']);
		$user_proj['protocols']['item_id']=array();
		foreach ($user_proj['protocols']['names'] as $nm) {
			$user_proj['protocols']['item_id'][] = 	$user_proj['protocols']['items'][$nm]['item_id'];
		}
		file_put_contents($GLOBALS['uploads'].'user_proj', serialize($user_proj));
		
		return ($user_proj);

	}

function create_authentication_proj1($db)
{
	#If user is not generic admin, he cannot create the project
	#if(!user_is_admin($user_id, $db)){
	#echo "User does not have permission to create management project. Sorry :-(";
	#return (false);
	#exit;
	#}
	#@rename($GLOBALS['uploads'].'Config','lixo');
	$user_id='1';
	
	if(!is_object($db)){
		#this part stays contained in this function
		$db = CreateObject('s3dbapi.db');
		$db->Halt_On_Error = 'no';
		$db->Host     = $GLOBALS['s3db_info']['server']['db']['db_host'];
		$db->Type     = $GLOBALS['s3db_info']['server']['db']['db_type'];
		$db->Database = $GLOBALS['s3db_info']['server']['db']['db_name'];
		$db->User     = $GLOBALS['s3db_info']['server']['db']['db_user'];
		$db->Password = $GLOBALS['s3db_info']['server']['db']['db_pass'];
		$db->connect();
		}
	
	/* This part to be replaced by creating the project from scratch
	if(is_file($GLOBALS['uploads'].'Config')){
		$Config = unserialize(file_get_contents($GLOBALS['uploads'].'Config'));
		chmod($GLOBALS['uploads'].'Config',0777);
	}
	else {

		##First create the project. This will rely on a datafile stored inside mothership
		$fileURL = 'http://ibl.mdanderson.org/central/download.php?&statement_id=1370054';
		$a=@fopen($fileURL, "r");
		if($a){
		$file = S3DB_SERVER_ROOT.'/P1143.n3';
		file_put_contents($file, stream_get_contents($a));
		chmod($file, 0777);
		}
		elseif(is_file(S3DB_SERVER_ROOT.'/P1134.n3'))	
			{$file =S3DB_SERVER_ROOT.'/P1134.n3';}
		else {
			#echo "Could not read authentications project :-(";
			return (false);
			exit;
		}
		$inputs['load']='1';
		#now parse the rdf
		$F = compact('file','db','user_id', 'inputs');

		$Config=rdfRestore($F);
		file_put_contents($GLOBALS['uploads'].'Config', serialize($Config));

	}
	*/
	#echo '<pre>';print_r($Config);exit;;
	
	#if(!is_array($Config))
	#	 $Config = array('P'=>array(), 'C'=>array(),'R'=>array(),'I'=>array(),'S'=>array());
	#now reorganize this structure to fit the user_proj structure 
	#what happens if there is not Config?
	#$Config='';
	
	#$user_project = array('project_id'=>'','users'=>array('collection_id'=>''), 'groups'=>array('collection_id'=>''), 'email'=>array('rule_id'=>''), 'user_id'=>array('rule_id'=>''),'group_users'=>array('rule_id'=>''));
	$user_proj = @unserialize(@file_get_contents($GLOBALS['uploads'].'user_proj'));
	
	
	$important_collections = array('Users','Groups', 'Protocols','Authorities');
	$important_rules = array('Users'=>array(
												array('verb'=>'authenticationAlternative','object'=>'email'),			
												array('verb'=>'identifier','object'=>'user_id')),
							'Authorities'=>array(array('verb'=>'validatesThrough','object'=>'Protocols'), 
														array('verb'=>'validatesAt','object'=>'URI'), 
														array('verb'=>'have','object'=>'DisplayLabel'),
														array('verb'=>'is','object'=>'Endorsed'))
							);
	$default_items = array('Protocols'=>array('ldap'=>'','ftp'=>'','http'=>''),			
							'Authorities'=>array('google'=>array('URI'=>'https://www.google.com/accounts/ClientLogin', 'Protocols'=>'http', 'DisplayLabel'=>'google')));
	
	
	#and now, as a final obsessive step... try to create it again;
	
	$user_proj=check_user_management(compact('user_proj', 'user_id','db', 'important_collections','important_rules','default_items'));
	return ($user_proj);

	/*
   if(is_array($Config))
	foreach ($Config as $letter=>$elem_info) {
		foreach ($elem_info as $ind=>$data_info) {
			
			if($letter=='P'){
				
				$user_project['project_id'] = $data_info['project_id'];

			}
			if($letter=='C'){
				
				if(in_array($data_info['name'], $important_collections)){
					
					$user_proj[strtolower($data_info['name'])]['collection_id']=$data_info['collection_id'];
				}
			}
			
			
			if($letter=='R'){
				if($data_info['object']=='email' && $data_info['subject_id']=$user_proj['users']['collection_id'])
				{$user_proj['email']['rule_id']=	$data_info['rule_id'];}
				if($data_info['object']=='user_id' && $data_info['subject_id']=$user_proj['users']['collection_id']){
				$user_proj['user_id']['rule_id']=	$data_info['rule_id'];
				}
				if($data_info['subject_id']==$user_proj['authorities']['collection_id']){
				$user_proj['authorities']['rules'][] = $data_info['rule_id'];
				$user_proj['authorities']['rule_objects'][] = ($data_info['object_id']=='')?$data_info['object']:$data_info['object_id'];
				}
			}
			if($letter=='I'){
				if($data_info['collection_id']==$user_proj['protocols']['collection_id']){
					$user_proj['protocols']['items'][$data_info['notes']]['item_id'] =$data_info['item_id'];
					$user_proj['protocols']['names'][] = $data_info['notes'];
					$user_proj['protocols']['item_id'][] = $data_info['item_id'];

				}
				if($data_info['collection_id']==$user_proj['authorities']['collection_id']){
					$user_proj['authorities']['items'][$data_info['notes']]['item_id'] =$data_info['item_id'];
				}
			}
			if($letter=='S'){
				$user_proj['authorities']['statements']['I'.$data_info['item_id']]['R'.$data_info['rule_id']][] =$data_info['value'];
			}
		}
	}
*/

}

function trustedAuth()
	{
			
		#if(is_file($GLOBALS['uploads'].'user_proj')) 
		#$user_proj = unserialize(file_get_contents($GLOBALS['uploads'].'user_proj'));
		
		$user_proj = create_authentication_proj($db);
		
		#if($_REQUEST['clean']){
		#$user_proj['authorities']['local_data']=false;
		#}
		#echo '<pre>';print_r($user_proj);exit;
		#query only if not in the file 
		if(!$user_proj['authorities']['local_data']){
		$db = CreateObject('s3dbapi.db');
		$db->Halt_On_Error = 'no';
		$db->Host     = $GLOBALS['s3db_info']['server']['db']['db_host'];
		$db->Type     = $GLOBALS['s3db_info']['server']['db']['db_type'];
		$db->Database = $GLOBALS['s3db_info']['server']['db']['db_name'];
		$db->User     = $GLOBALS['s3db_info']['server']['db']['db_user'];
		$db->Password = $GLOBALS['s3db_info']['server']['db']['db_pass'];
		$db->connect();


		$s3qlnew=array('user_id'=>'1','db'=>$db);#public should be able to query there
		$s3qlnew['from']='authority';
		
		list($valid,$auth) = apiQuery($s3qlnew);
		
		
		if($valid)
		{	$user_proj['authorities']['local_data']=$auth;
			file_put_contents($GLOBALS['uploads'].'user_proj', serialize($user_proj));
			return ($auth);
		}
		else {
			return (array());
		}
		}
		else {
			return ($user_proj['authorities']['local_data']);
		}
	}
		
function checkValidAuthority($authority, $db) {
	$user_proj = create_authentication_proj($db,'1'); #still dbating whether this should be a public project.
	
	  if(!empty($user_proj)){
		
		#find the authority in the list of authority labels
		$s3ql=array('user_id'=>'1','db'=>$db);#to be changes once user is given permission on project
		$s3ql['from']='authority';
		$s3ql['where']['DisplayLabel']=$authority;
		list($valid,$data)=apiQuery($s3ql,$user_proj);
		
		if(is_array($data[0]))
		  	
			$reqAuth = $data[0];
				
		}
		
	   if(!is_array($reqAuth))
			return (array(false));
		else {
			
			return (array(true, $reqAuth, $user_proj));
		  }
	}

function authenticate_remote_user($key, $url)
{

	#URL contains info on user in the last part of the path. (for example: URL=https://ibl.mdanderson.org/s3db/U4) 
	#$user_id_info = uid($url);
	$user_id_info = uid_resolve($url);
	if(ereg_replace('^D','',$user_id_info['Did'])==ereg_replace('^D','',$GLOBALS['s3db_info']['deployment']['Did'])){
		#same uri as local, authentication failed
		return 1;
		exit;
	}

				
	$db = CreateObject('s3dbapi.db');
	$db->Halt_On_Error = 'no';
	$db->Host     = $GLOBALS['s3db_info']['server']['db']['db_host'];
	$db->Type     = $GLOBALS['s3db_info']['server']['db']['db_type'];
	$db->Database = $GLOBALS['s3db_info']['server']['db']['db_name'];
	$db->User     = $GLOBALS['s3db_info']['server']['db']['db_user'];
	$db->Password = $GLOBALS['s3db_info']['server']['db']['db_pass'];
	$db->connect();
				
	#Find URL
	list($did_url) = DidURL($user_id_info, $db);

	if(!$did_url){
		return (4);exit;
	}

	#Validate User in remote; 
	##This is done by calling the apifunction keyCheck, which requires a key and a user_id; 
	$call1 = $did_url.'keyCheck.php?key='.$key.'&user_id='.$user_id_info['uid'].'&format=php';
	$tmpKC = @fopen($call1,'r');
	if(!$tmpKC){
		return (4);exit;
	}

	$keyValidated = stream_get_contents($tmpKC);$keyValidated = unserialize($keyValidated);
	$keyValidated = $keyValidated[0];
	
	if($keyValidated['error_code']==0){
		#User was validated with uid associated with remote deployment; These users cannot write anything is this deployment and their permissions are limited to the resources they were granted permission on. A filter is implemented that can be changed by the creator (Remote)	
		insert_access_log(array('user_id'=>$user_id_info['condensed'], 'db'=>$db));
		
		##Temporarily copy the key for this user
		$I = array('key_id'=>$key,
					'account_id'=>$user_id_info['condensed'],
					'expires'=>date('Y-m-d H:i:s',time() + (1 * 60 * 60)),
					'notes'=>'Key for remote user created automatically by the API. Expires in 1 hour.'
					);
		add_entry ('access_keys', $I, $db);
		delete_expired_keys($date, $db);
		
		return (0);exit;
	}
	else {
		return 1;
		exit;
	}
}

		
?>