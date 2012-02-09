<?php
	##setup.php is an interface to configure this s3db installation. 
	#Helena F Deus (helenadeus@gmail.com)
	#ini_set('display_errors',0);
	#	if($_REQUEST['su3d'])
	ini_set('display_errors',0);
	if($_REQUEST['su3d'])
	ini_set('display_errors',1);

	$a = set_time_limit(0);
	if(file_exists('config.inc.php'))
	{
		include('config.inc.php');
		
		include_once(S3DB_SERVER_ROOT.'/s3dbcore/common_functions.inc.php');
		include(S3DB_SERVER_ROOT.'/s3dbcore/random_values.php');
		

		//echo S3DB_SERVER_ROOT.'/s3dbapi/inc/common_functions.inc.php';
	}
	else
	{
		$cwd = dirname($_SERVER['SCRIPT_FILENAME']);
		$email_host = 'mail'.strstr($_SERVER['HTTP_HOST'],'.');
		define(S3DB_SERVER_ROOT, $cwd);
		include_once(S3DB_SERVER_ROOT.'/s3dbcore/common_functions.inc.php');
		include(S3DB_SERVER_ROOT.'/s3dbcore/random_values.php');
	}
		//echo $GLOBALS['server_root'];

include_once(S3DB_SERVER_ROOT.'/s3dbcore/generate_key_pair.php');
include_once('html2cell.php');


	
	
	session_start();
	$tpl = CreateObject('s3dbapi.Template', 's3dbapi/templates/default');
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
	
	if(!empty($_POST['logout']))
      {
	       
		   $_SESSION['db'] ='';
            $_SESSION['user'] ='';   
			Header('Location: login.php');
			#echo '<META HTTP-EQUIV="Refresh" target="_parent" Content= "0; URL="login.php?error=1">';
			
            exit;
	       
	}
	
	if(($_SESSION['user']['login'] != $GLOBALS['s3db_info']['server']['site_config_admin'] || $_SESSION['user']['passwd'] !=$GLOBALS['s3db_info']['server']['site_config_admin_pass']) && $_SESSION['user']['account_lid']!='Admin')
	{
		$_SESSION['user']='';
	       session_destroy();
               Header('Location: login.php?error=3');
               exit;

	}
	
	$tpl->set_file(array(
                'config' => 'configure.tpl'
        ));
	 $tpl->set_block('config', 'header', '_config');
         $tpl->fp('_output', 'header', True);
	 
	 
	
	$tpl->set_block('config', 'site_configure', '_config');
         $tpl->fp('_output', 'site_configure', True);

	
	if($_POST['Save_configuration'] || $_POST['Create_configuration'])
	{	
		$Did = $GLOBALS['s3db_info']['deployment']['Did']; #first find the Did in the config
		
		if($_REQUEST['mothership']=='')	$mothership =  'http://root.s3db.org/';
		else {
			$mothership = $_REQUEST['mothership'];
		}
		
		

		if ($Did=='') {
		//mothserhip registration has been the weakest point in s3db deployment so far; as such, I am adding some redundancy here
		//unfortunatelly, root.s3db.org almost always breaks so it will be translated to its more direct url
		if($mothership=='http://root.s3db.org/')
			$mothership = 'http://204.232.200.16/s3dbCentral/';
		#is user connected to the internet?
			
			//$mothership = ($_POST['mothership_new']!='')?$_POST['mothership_new']:$_POST['mothership'];
			//$mothership = (substr($mothership,-1,1)=='/')?$mothership:$mothership.'/';
			
			#$connected = @fopen($mothership.'s3rl.php', "r"); 
			$connected = @fopen($mothership.'s3rl.php', "r"); 
			
			$message = stream_get_contents($connected);
			
		
			if($message=='') {
				$mothership = "http://204.232.200.16/s3dbCentral/";
				$connected = @fopen($mothership.'s3rl.php', "r"); 
				$message = stream_get_contents($connected);
				$newMs = $mothership;
				}
			
			
			if($message=='') {
				//Try again, this time with another url;
				$mothership =  'http://ibl.mdanderson.org/s3dbCentral';
				$connected = @fopen($mothership.'s3rl.php', "r"); 
				$message = stream_get_contents($connected);
				$newMs = $mothership;
			}

			if($message=='') {
				$mothership = "http://s3db.virtual.vps-host.net/root/";
				$connected = @fopen($mothership.'s3rl.php', "r"); 
				$message = stream_get_contents($connected);
				$newMs = $mothership;
				}

			if($message=='') {
				$validate_error =  'Could not connect to mothership '.$mothership.'. Please check your internet connection - s3db needs to be registered in order to allow distributed use. Refer to <a href="http://s3db.org">http://s3db.org</a> for instructions.';
				$tpl->set_var('error', $validate_error);
				$tpl->pfp('out','_output');
				exit;
			}
			
		
			
			
			$RSAkeys = generate_key_pair(); 			
			
			$deployInfo['mothership'] = $mothership;
			$protocol = ($_SERVER['HTTPS']!='')?'https://':'http://';
			$host =($_SERVER['HTTP_X_FORWARDED_HOST']!='')?$_SERVER['HTTP_X_FORWARDED_HOST']:$_SERVER['HTTP_HOST'];
			$dir= str_replace('setup.php', '', $_SERVER['PHP_SELF']);
			$publicKey = $RSAkeys['public'];
			
			$di = array('mothership'=>$deployInfo['mothership'], 'url'=>$_POST['uri_base'], 'publicKey'=>$publicKey, 'did_keywords'=>$_POST['deployment_keywords'], 'did_title'=>$_POST['site_title'],'did_intro'=>$_POST['site_intro'], 'userName'=>$_POST['userName'], 'email'=>$_POST['email']);
			
			
			list($valid,$deployment_info, $mothership) = send_public_key($di);
			
				if(!$valid)
					{$validate_error =  'Could not register this S3DB in mothership '.$mothership.'. Reason: '.$deployment_info.'.<br /> Refer to <a href="http://s3db.org">http://s3db.org</a> for instructions.';
					}
					
		}
		
		$inputs = Array('server_root'=>$_POST['server_root'],
				'uri_base'=>$_POST['uri_base'],	
				'site_logo'=>$_POST['site_logo'],	
				'site_title'=>$_POST['site_title'],	
				'site_intro'=>$_POST['site_intro'],	
				'site_config_admin'=>$_POST['site_config_admin'],	
				'site_config_admin_pass'=>$_POST['site_config_admin_pass'],	
				'db_type'=>$_POST['db_type'],
				'db_host'=>$_POST['db_host'],	
				'db_name'=>$_POST['db_name'],	
				'db_user'=>$_POST['db_user'],	
				'db_pass'=>$_POST['db_pass'],
				'uploads_folder'=>$_POST['uploads_folder'], 
				'email_host'=>$_POST['email_host'],
				'privateKey'=>$RSAkeys['private'],
				'publicKey'=>$RSAkeys['public'],
				'mothership'=>$mothership,
				'code_source'=>'http://ibl.mdanderson.org/central/',
				'Did'=>$deployment_info,
				'did_keywords'=>$_POST['deployment_keywords']);
		
		
		
		//print_r($inputs);			
		#echo '<pre>';print_r($_POST);
		#echo '<pre>';print_r($inputs);exit;
		
		$validate_error .= validate_inputs($inputs);
		
		
		if($validate_error !='')
		{
			$tpl->set_var('error', $validate_error);
			#$tpl->pfp('out','_output');
			
			#$tpl->set_var($validate_error.'_required', '*');	
		}
		else
		{
			
			if($_POST['Create_configuration'])
			{
				$config_file = create_configuration_file($inputs);
				
				if($config_file) #create the file
				{
					#$dbCreate = createS3DBDatabase($inputs);
					list($dbRunning, $msg) = testS3DB($inputs);
					
					if (!$dbRunning)
					{	
						unlink('config.inc.php');
						$tpl->set_var('error', 'Could not create the database. Please check that username/password are correct, or use a diffent name for your Database. Refer to README file/http://www.s3db.org for further instructions.');
					}
					
					
					$_SESSION['user']='DBAdmin';
					Header('Location: dbconfig.php');
					exit;
				}
			}
			elseif($_POST['Save_configuration'])
			{
				
				unlink('config.inc.php');
				$config_file = create_configuration_file($inputs);
				if($config_file)
				{
					list($dbRunning, $msg) = testS3DB($inputs);
					##Lets test the database - was it created?
					if(!$dbRunning)
					{
						
						unlink('config.inc.php');
						$tpl->set_var('error', 'Could not create the database. Please check if the database user and password is correct, or use a diffent name for your Database. Refer to README file/http://www.s3db.org for further instructions.');
					}
					
					
					$tpl->set_var('error', $cwd.'config.inc.php saved');
					#$tpl->set_var("db_config", '<input type="submit" name="db_config" value="Create administrator account">');
					
				}
			}	
		}
		$tpl->set_var('log_out', '<input type="submit" name="logout" value="Log Out">');
		
		
		
		if($_POST['Save_configuration'])
			$tpl->set_var('save_create', 'Save');
		else if($_POST['Create_configuration'])
			$tpl->set_var('save_create', 'Create');
		
	}
	
	//	else if($_POST['db_config'])
	//	{
	//		if (!createS3DBDatabase($inputs))
	//		{$tpl->set_var('error', 'Could not create the database. Please check if the database user and password is correct. You might need to install s3db mannually. Refer to README file/http://www.s3db.org for further instructions.');
	//		unlink('config.inc.php');
	//		}
	//		
	//		$_SESSION['user']='DBAdmin';
	//		Header('Location: dbconfig.php');
	//		exit;
	//			
	//	}
	
	##Default data
	if(!file_exists('config.inc.php'))
		{
			if(array_search('mysql', get_loaded_extensions())!='')
			{$db_server[] = 'mysql';
			$db_option .='<option value="mysql">MySQL</option>';
			$db_dialog ='Mysql'; 
			}
			if(array_search('pgsql', get_loaded_extensions())!='')
			{$db_server[] = 'pgsql';
			$db_option .='<option value="pgsql" selected>PostgreSQL</option>';
			$db_dialog = ($db_dialog!='')?($db_dialog.' and Pgsql'):'Pgsql'; 
			}
			if(empty($db_server))
					{$tpl->set_var('database_message', '<font color="red">No Database was found. S3DB needs the PHP extension for MySQL or PostgreSQL to be loaded.</font>'); 	
					
					}
			else {
					$tpl->set_var('database_message', $db_dialog.' was detected, default setting will be used. You can inspect/change them in the <a href="javascript:hideAndShow();">Advanced</a> options.');
				}
			
			$tpl->set_var('action_url', 'setup.php');
			$tpl->set_var('website_title', 'S3DB site configuration');
			$tpl->set_var('db_options', $db_option);
			$tpl->set_var('db_host_default', $_SERVER['SERVER_NAME']);
			$tpl->set_var('db_host_message', 'Type here the database host');
			$tpl->set_var($db_server.'_selected', 'selected');
			$tpl->set_var('site_config_admin', 'Admin');
			$tpl->set_var('site_config_admin_pass', random_string(10));
			$tpl->set_var('db_server', $db_server);
			
			
			$tpl->set_var('log_out', '<input type="submit" name="logout" value="Log Out">');
			$cwd = dirname($_SERVER['SCRIPT_FILENAME']);
			#$tpl->set_var('message', 'You can either copy <b>'.$cwd.'/config.inc.php.template</b> as <b>'.$cwd.'/config.inc.php</b> and modify it <br />or modify this form and click "Create Configuration" to create <b>'.$cwd.'/config.inc.php</b>'); 
			$tpl->set_var("save_create", 'Create');
			$https = ($_SERVER['HTTPS']!='')?('https://'):('http://');
			$uri_base= $https.(($_SERVER['HTTP_X_FORWARDED_SERVER']!='')?$_SERVER['HTTP_X_FORWARDED_SERVER']:$_SERVER['SERVER_NAME']).'/'.strtok($_SERVER['PHP_SELF'], '/');
			
			##Let's try and capture the ip, if user did not provide one => THIS PIECE DOES NOT WORK BECAUSE SESSION ON LOCAL FALLS WHEN INTERFACE IS RE-DIRECTED TO THE IP ADDRESS
			#$ip= captureIp();
			#$protocol = ($_SERVER['HTTPS']!='')?'https://':'http://';
			#$uri_base = $protocol.(($ip!='')?$ip:$_SERVER['SERVER_ADDR']).str_replace('setup.php', '', $_SERVER['REQUEST_URI']);
		
			
			#$uri_base= substr($_SERVER['REQUEST_URI'], 0, strrpos($_SERVER['REQUEST_URI'], '/'));
			$tpl->set_var('S3DB_SERVER_ROOT', S3DB_SERVER_ROOT);
			$tpl->set_var('server_root', $cwd);
			$tpl->set_var('email_host',$email_host);
			$tpl->set_var('uri_base', $uri_base);
			$tpl->set_var('site_logo', $cwd.'/images/s3db.png');
			$tpl->set_var('site_title', '');
			$tpl->set_var('site_intro', 'The motivation for this work is the increasing complexity and the constantly changing nature of this type of data, which represents a difficult obstacle for the more conventional use of relational or XML paradigms.');
			$tpl->set_var('db_host', 'localhost');
			$tpl->set_var('db_name', 's3db');
			#$tpl->set_var('mothership', 'http://s3db.virtual.vps-host.net/central/');
			###Add more motherships here/create the function to find motherships here
			#$motherships .= '<option value="http://s3db.virtual.vps-host.net/central/">http://s3db.org</option>';
			#$motherships .= '<option value="http://ibl.mdanderson.org/central/">http://s3db.org</option>';
			#$motherships .= '<option value="'.$uri_base.'">Local</option>';

			#$tpl->set_var('list_motherships', $motherships);
			$tpl->set_var('mothership_intro', "This S<sup>3</sup>DB will be registered in:");
			$tpl->set_var('mothership_text', '<input type="text" id="mothership" value = "http://root.s3db.org/" name="mothership" disabled>
			<BR><input type="checkbox" onClick="alert(\'Please make sure you are introducing a VALID S3DB mothership\');$(\'#mothership\').attr(\'disabled\', false)" unchecked>Choose another mothership');
			
			
			#$tpl->set_var('uncheck', '<input type="checkbox" name="register" style="background: lightyellow" size="30" checked><font color="navy" size="2"> Uncheck this box if you do not wish this S<sup>3</sup>DB implementation to be registered within the S<sup>3</sup>DB community.</font>');
			$tpl->set_var('db_user', 's3dbuser');
			if($db_server=='pgsql')
			$tpl->set_var('dbuser_message', 'This user must exist in pgsql previous to S3DB installation. ');
			elseif($db_server=='mysql')
			$tpl->set_var('dbuser_message', '(Default Database user and password may be changed.)');
			
			$tpl->set_var('db_pass', random_string(5));
			$tpl->pfp('out','_output');
		}
		else
		{
			
			$tpl->set_var('log_out', '<input type="submit" name="logout" value="Log Out">');
			$tpl->set_var('message', 'You can change your '.$cwd.'/config.inc.php by modified this form and click "Save Configuration"'); 
			$tpl->set_var("save_create", 'Save');
			
			if($GLOBALS['s3db_info']['server']['db']['db_type']=='mysql') $mysqlSelected = ' selected';
			else {
				$psqlSelected = ' selected';	
				}
			
			

			
			
			if($GLOBALS['s3db_info']['deployment']['mothership']!='http://root.s3db.org/' && $GLOBALS['s3db_info']['deployment']['mothership']!='')
			{
			$mothership_text .= '<select name="mothership">';
			$mothership_text .= '<option value="http://root.s3db.org/">http://s3db.org</option>';
			$mothership_text .= '<option value="'.$GLOBALS['s3db_info']['deployment']['mothership'].'">'.$GLOBALS['s3db_info']['deployment']['mothership'].'</option>';	
			$mothership_text .= '</select>';
			$tpl->set_var('mothership_intro', "Register this S<sup>3</sup>DB in:");
			}
			else {
				$mothership_text = '<input type="hidden" value = "http://root.s3db.org/" name="mothership"><b>http://s3db.org</b>';
				$tpl->set_var('mothership_intro', "This S<sup>3</sup>DB will be registered in:");
			}
			
			
			$tpl->set_var('mothership_text', $mothership_text);

			$inputs = Array('server_root'=>S3DB_SERVER_ROOT,
				'uri_base'=>S3DB_URI_BASE,	
				'site_logo'=>$GLOBALS['s3db_info']['server']['logo_file'],	
				'site_title'=>$GLOBALS['s3db_info']['server']['site_title'],	
				'site_intro'=>$GLOBALS['s3db_info']['server']['site_intro'],	
				'site_config_admin'=>$GLOBALS['s3db_info']['server']['site_config_admin'],	
				'site_config_admin_pass'=>$GLOBALS['s3db_info']['server']['site_config_admin_pass'],	
				'db_type'=>$GLOBALS['s3db_info']['server']['db']['db_type'],
				'db_host'=>$GLOBALS['s3db_info']['server']['db']['db_host'],	
				'db_name'=>$GLOBALS['s3db_info']['server']['db']['db_name'],	
				'db_user'=>$GLOBALS['s3db_info']['server']['db']['db_user'],	
				'db_pass'=>$GLOBALS['s3db_info']['server']['db']['db_pass'],
				'uploads_folder'=>$GLOBALS['s3db_info']['server']['db']['uploads_folder'], 
				'uploads_file'=>$GLOBALS['s3db_info']['server']['db']['uploads_file'],
				'email_host'=>$GLOBALS['s3db_info']['server']['email_host'],
				'privateKey'=>$GLOBALS['s3db_info']['deployment']['private_key'],
				'publicKey'=>$GLOBALS['s3db_info']['deployment']['public_key'],
				'mothership'=>$GLOBALS['s3db_info']['deployment']['mothership'],
				'Did'=>$GLOBALS['s3db_info']['deployment']['Did'],
				'did_keywords'=>$GLOBALS['s3db_info']['deployment']['keywords'],
				'userName'=>$GLOBALS['s3db_info']['deployment']['userName'],
				'email'=>$GLOBALS['s3db_info']['deployment']['email'],
				'db_options'=>'<option value="mysql" '.$mysqlSelected.'>Mysql</option><option value="pgsql" '.$psqlSelected.'>Postgres</option>',
				'list_motherships'=>$motherships);
		
		#echo '<pre>';print_r($inputs);
				
				
				foreach ($inputs as $var_name=>$value) {
					$tpl->set_var($var_name, $value);	
				}
				$tpl->set_var("save_create", 'Save');
				#$tpl->set_var("db_config", '<input type="submit" name="db_config" value="Create administrator account/Configuration">');
				$tpl->pfp('out','_output');
				
		}
		
	
       # $tpl->pfp('out','_output');
	
	function validate_inputs($inputs)
	{
		if($inputs['server_root'] == '')
		{
			$return = 'Sserver root';	
		}
		else if($inputs['uri_base'] == '')
		{
			$return = 'URI base';	
		}
		#else if($inputs['site_title'] == '')
		#{
		#	$return =  'Site Title';	
		#}
		else if($inputs['site_config_admin'] == '')
		{
			$return =  'Site Config Admin';	
		}
		else if($inputs['site_config_admin_pass'] == '')
		{
			$return =  'site_config_admin_pass';	
		}
		else if($inputs['db_type'] == '')
		{
			$return =  'Database Type';	
		}
		else if($inputs['db_host'] == '')
		{
			$return =  'Database Host';	
		}
		else if($inputs['db_name'] == '')
		{
			$return =  'Database Name';	
		}
		else if($inputs['db_user'] == '')
		{
			$return =  'Database User';	
		}
		elseif(!is_writeable($inputs['server_root']))
		{
			return('S3DB (Apache user) must be have write permissions to '.$inputs['server_root']);	
		}
		elseif(!createS3DBDatabase($inputs))
		{
			return('Could not create the database. Please check if the database user and password is correct, or change the name of your Database. Refer to README file or to http://www.s3db.org for further instructions.');	
		}
		else {
			return ('');
		}
		return $return.' cannot be empty';
	}
	
	function create_configuration_file($inputs)
	{	
		if($GLOBALS['s3db_info']['server']['db']['uploads_file']=='')
		$randomfilename = random_string('15');
		else {
			$randomfilename = $GLOBALS['s3db_info']['server']['db']['uploads_file'];
		}
	 	

		$content .= sprintf("%s\n", "<?php");
		$content .= sprintf("\t%s\n", "define('S3DB_SERVER_ROOT', '".$inputs['server_root']."');");
	 	$content .= sprintf("\t%s\n", "define('S3DB_URI_BASE', '".$inputs['uri_base']."');");
	 	$content .= sprintf("\t%s\n", "\$GLOBALS['s3db_info']['server']['email_host']='".$inputs['email_host']."';");
		$content .= sprintf("\t%s\n", "\$GLOBALS['s3db_info']['server']['site_config_admin']='".$inputs['site_config_admin']."';");
	 	$content .= sprintf("\t%s\n", "\$GLOBALS['s3db_info']['server']['site_config_admin_pass']='".$inputs['site_config_admin_pass']."';");
	 	$content .= sprintf("\t%s\n", "\$GLOBALS['s3db_info']['server']['logo_file']='".$inputs['site_logo']."';");
	 	$content .= sprintf("\t%s\n", "\$GLOBALS['s3db_info']['server']['site_title']='".addslashes($inputs['site_title'])."';");
	 	$content .= sprintf("\t%s\n", "\$GLOBALS['s3db_info']['server']['site_intro']='".addslashes($inputs['site_intro'])."';");
	 	$content .= sprintf("\t%s\n", "\$GLOBALS['s3db_info']['server']['db']['db_type']='".$inputs['db_type']."';");
	 	$content .= sprintf("\t%s\n", "\$GLOBALS['s3db_info']['server']['db']['db_host']='".$inputs['db_host']."';");
	 	$content .= sprintf("\t%s\n", "\$GLOBALS['s3db_info']['server']['db']['db_name']='".$inputs['db_name']."';");
	 	$content .= sprintf("\t%s\n", "\$GLOBALS['s3db_info']['server']['db']['db_user']='".$inputs['db_user']."';");
	 	$content .= sprintf("\t%s\n", "\$GLOBALS['s3db_info']['server']['db']['db_pass']='".$inputs['db_pass']."';");
		$content .= sprintf("\t%s\n", "\$GLOBALS['s3db_info']['server']['db']['uploads_file']='".$randomfilename."';");
		$content .= sprintf("\t%s\n", "\$GLOBALS['s3db_info']['server']['db']['uploads_folder']='".$inputs['uploads_folder']."';");
		$content .= sprintf("\t%s\n", "\$GLOBALS['s3db_info']['deployment']['private_key']='".$inputs['privateKey']."';");
		$content .= sprintf("\t%s\n", "\$GLOBALS['s3db_info']['deployment']['public_key']='".$inputs['publicKey']."';");
		$content .= sprintf("\t%s\n", "\$GLOBALS['s3db_info']['deployment']['mothership']='".$inputs['mothership']."';");
		$content .= sprintf("\t%s\n", 
		"\$GLOBALS['s3db_info']['deployment']['code_source']='".$inputs['code_source']."';");
		$content .= sprintf("\t%s\n", "\$GLOBALS['s3db_info']['deployment']['Did']='".$inputs['Did']."';");
		$content .= sprintf("\t%s\n", "\$GLOBALS['s3db_info']['deployment']['did_keywords']='".addslashes($inputs['did_keywords'])."';");
		$content .= sprintf("\t%s\n", "\$GLOBALS['s3db_info']['deployment']['userName']='".addslashes($inputs['userName'])."';");
		$content .= sprintf("\t%s\n", "\$GLOBALS['s3db_info']['deployment']['email']='".addslashes($inputs['email'])."';");
		#$content .= sprintf("\t%s\n",  "\$GLOBALS['s3db_info']['server']['allow_peer_authentication']='".$inputs['P2P']."';");
		$content .= sprintf("%s\n", "?>");
		//echo $content;
		$filename='config.inc.php';
   		
		#create the folder for the statements files submitted by the user
		
		

		
		if(!is_dir($inputs['uploads_folder']))
		{mkdir($inputs['uploads_folder']);
		chmod($inputs['uploads_folder'], 0777);
		}

		if(!is_dir($inputs['uploads_folder'].$randomfilename)) 
		{mkdir($inputs['uploads_folder'].$randomfilename, 0777);
		chmod($inputs['uploads_folder'].$randomfilename, 0777);
		}
			
			if(!is_dir($inputs['uploads_folder'].$randomfilename)) 
				{
				#echo 'extras/'.$randomfilename;
				echo "Please make sure apache has write permission.";
				exit;
			}
			else {
				@mkdir($inputs['uploads_folder'].$randomfilename.'/tmps3db');
				chmod($inputs['uploads_folder'].$randomfilename.'/tmps3db', 0777);
			}
	
			$indexfile = $inputs['uploads_folder'].$randomfilename.'/index.php';
			$string_on_index = 'Folder cannot be accessed';
			file_put_contents($indexfile, $string_on_index);
			chmod($indexfile, 0777); 
   			#fclose($handle2);
 		
		if(!is_dir($inputs['uploads_folder'].$randomfilename.'/tmps3db/')) {
					mkdir($inputs['uploads_folder'].$randomfilename.'/tmps3db/');
					chmod($inputs['uploads_folder'].$randomfilename.'/tmps3db/',0777);
			}
			
			
		
			
		
		if (!$handle = @fopen($filename, 'w'))
		{
         		echo "Cannot open file ($filename)";
         		exit;
   		}
   		if (fwrite($handle, $content) === FALSE) 
		{
       			echo "Cannot write to file ($filename)";
       			exit;
   		}
 		chmod($filename, 0777); 
   		fclose($handle);
		return True;
	}

function createS3DBDatabase($inputs)
{

$db_engine  = $inputs['db_type'];
$hostname =$inputs['db_host'];
$dbname = $inputs['db_name'];
$user =$inputs['db_user'];
$pass = $inputs['db_pass'];
#echo $hostname.' '.$dbname.' '.$user.' '.$pass;
if ($db_engine == 'mysql')
{$connect = @mysql_connect($hostname, $user, $pass);

if (!$connect) {
   $connect = @mysql_connect($hostname, 'root', '');
   #die('Could not connect: ' . mysql_error());
	 if (!$connect)
   return (False);
   
}
$sql = "create database ".$dbname."";

@mysql_query($sql, $connect);

$sql = "grant all privileges on ".$dbname.".* to ".$user."@".$hostname." identified by '".$pass."'";
@mysql_db_query($dbname, $sql, $connect);

$sql = 'flush privileges';
@mysql_db_query($dbname, $sql, $connect);

@mysql_close ($connect);

}
elseif ($db_engine == 'pgsql') {
$connect = pg_connect("host=".$hostname." dbname=template1 user=".$user." password=".$pass."");
if (!$connect) {
# die('Could not connect: ' . pg_last_error());
   return (False);
   
}

$sql = "create database ".$dbname."";

$pg = pg_query ($connect, $sql);


}
return True;
}

function testS3DB($inputs)
{
	#echo '<pre>';print_r($inputs);exit;
	if(is_file('config.inc.php'))
	include('config.inc.php');
	else
	return (False);
	
	$db = CreateObject('s3dbapi.db');
	$db->Halt_On_Error = 'no';
	$db->Host     = $inputs['db_host'];
	$db->Type     = $inputs['db_type'];
	$db->Database = $inputs['db_name'];
	$db->User     = $inputs['db_user'];
	$db->Password = $inputs['db_pass'];
	
	$db->connect();
	
	if($db->Errno=='0')
	{return array(True);}
	else
	{	
	return array(False, "Connection to database failed! Please make sure username/password are correct");
	}
	
}

function send_public_key($U)
{extract($U);
#try the mothership first. If not workking, try direct url. 

#send a url and a key by post to mothership
#$url2register = $mothership."s3rl.php?format=php";
if(!preg_match('/\/$/',$mothership)) $mothership .='/';


$url2register = $mothership."s3rl.php?format=php";


$a = fopen($url2register, "r");
$b = stream_get_contents($a);
if(unserialize($b)=="" && preg_match('/src="([^"]*)"/', $b, $match)){
	$b=file_get_contents($match[1]);
}

if(unserialize($b)=="" && $mothership=='http://root.s3db.org/'){
	
	while (unserialize($b)=="" && $i<5) {
		//change the ms;
		//$mothership = "http://s3db.virtual.vps-host.net/root/";
		
		if($i>=2){
			$mothership = 'http://ibl.mdanderson.org/central/';
		}
		if($i>=3){//this means that root is finally offline - go the alternative ms
			$mothership = "http://204.232.200.16/s3dbCentral/";
		}
		
		#$url2register = $mothership."s3rl.php?format=php";
		$url2register = $mothership."s3rl.php?format=php";

		$a = fopen($url2register, "r");
		$b = stream_get_contents($a);
		$i++;
	}
}
if($i>5){
	//Register failed
	return (array(false, "Mothership ".$mothership." does not seem to be responding"));
}



$url2register .= "&url=".urlencode($url);
$url2register .= "&publicKey=".urlencode($publicKey);

#Because data parameters can get big and messy, will use a POST for mothership
$data = array();

if($did_title) $data["name"] = urlencode($did_title);
if($did_intro) $data["description"] = base64_encode($did_intro);
if($did_keywords) $data["keywords"] = urlencode($did_keywords);
if($userName) $data["username"] = urlencode($userName);
if($email) $data["email"] = urlencode($email);

//if curl is installed, we can just USE it!
if (in_array('curl', get_loaded_extensions())) {
	//traverse array and prepare data for posting (key1=value1)
	foreach ( $data as $key => $value) {
		$post_items[] = $key . '=' . $value;
	}
	//create the final string to be posted using implode()
	$post_string = implode ('&', $post_items);
	//create cURL connection
	
	$curl_connection = 
	  curl_init($url2register);
	
	//set options
	curl_setopt($curl_connection, CURLOPT_CONNECTTIMEOUT, 30);
	curl_setopt($curl_connection, CURLOPT_USERAGENT, 
	  "Mozilla/4.0 (compatible; MSIE 6.0; Windows NT 5.1)");
	curl_setopt($curl_connection, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($curl_connection, CURLOPT_SSL_VERIFYPEER, false);
	curl_setopt($curl_connection, CURLOPT_FOLLOWLOCATION, 1);
	
	//set data to be posted
	curl_setopt($curl_connection, CURLOPT_POSTFIELDS, $post_string);
	//perform our request
	$response = curl_exec($curl_connection);
	
	curl_close($curl_connection);
	
	//show information regarding the request
	//print_r(curl_getinfo($curl_connection));
	//echo curl_errno($curl_connection) . '-' . 
	//				curl_error($curl_connection);
	
	//close the connection
	
}
else {
	$postdata = http_build_query($data);
	
	#Prepare the POST
	$opts = array(
	  'http'=>array(
		'method'=>"POST",
		'header'=>"Content-type: application/x-www-form-urlencoded",
		'content' => $postdata
	  )
	);
	$context = stream_context_create($opts);
	$response = file_get_contents($url2register, false, $context);
	
}

if($response){
	 
	$msg=unserialize($response);
	if($msg=='' && preg_match('/src="([^"]*)"/', $response, $match)){
		$msg=unserialize(file_get_contents($match[1]));
	}
	$msg=$msg[0];
	
	if($msg['deployment_id']!=''){
	return array(true,$msg['deployment_id'], $mothership);
	}
	else {
		return (array(false, $msg['message'], $mothership));
	}
	
	

}
	return (array(False, 'Root deployment could not be contacted. You can try again later or register your deployment in an alternative Root deployment, such as http://q.s3db.org/s3dbCentral/'));
}
?>