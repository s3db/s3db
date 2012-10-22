<?php
	#dbconfig.php creates a database, database tables and prompts the user for an Admin password (this is the "see all" general admin)
	ini_set('display_errors',0);
	if($_REQUEST['su3d']) {
		ini_set('display_errors',1);
	}
	if(file_exists('config.inc.php')) {
		include('config.inc.php');
	} else {
		#echo 'no config';
		Header('Location: login.php?error=7');
		exit;
	}
//	ini_set('display_errors',0);
//	if($_REQUEST['su3d'])
//	ini_set('display_errors',1);
//	include_once(S3DB_API_INC.'/common_functions.inc.php');
		
	include_once(S3DB_SERVER_ROOT.'/s3dbcore/s3encription.php');
	include_once(S3DB_SERVER_ROOT.'/s3dbcore/common_functions.inc.php');
	
	session_start();
	//Header("Cache-control: private");
	//echo $_SESSION['user'];
	/* Program starts here */
	
	$Did = $GLOBALS['s3db_info']['deployment']['Did'];
	
	if($_SESSION['user'] == '') {
		#echo "user is empty";
		Header('location: login.php?error=2');
		exit;
	} elseif($_SESSION['user'] != 'DBAdmin' && $_SESSION['user']['account_lid']!='Admin') {
		echo "user is not admin";
		Header('location: login.php?error=4');
		exit;
	}

	//echo $GLOBALS['s3db_info']['server']['template_dir'];
	$tpl = CreateObject('s3dbapi.Template', $GLOBALS['s3db_info']['server']['template_dir']);
	if(!isset($GLOBALS['dbsetup'])) {
		$dbsetup = CreateObject('s3dbapi.dbsetup');
		$GLOBALS['dbsetup'] = $dbsetup;
		#now create the tables
		$GLOBALS['dbsetup']->create_tables();
	} 
	
	foreach($_GET as $name => $value) {
		if (preg_match('/s3db_/',$name)) {
			$extra_vars .= '&' . $name . '=' . urlencode($value);
		}
	}

	if ($extra_vars) {
		$extra_vars = '?' . substr($extra_vars,1,strlen($extra_vars));
	}
	
	#
	#echo '<pre>';print_r($_POST);exit;
	$mothership = $GLOBALS['s3db_info']['deployment']['mothership'];
	if(!empty($_POST['logout'])) {
		$_SESSION['db'] ='';
		//$GLOBALS['dbsetup']->db->disconnect();
		#  echo '<META HTTP-EQUIV="Refresh" Content= "0; URL="../login.php?error=1">';
		Header('Location: login.php?error=1');
		exit;
	} elseif (!empty($_POST['createtables'])) {
		#echo '<pre>';print_r($GLOBALS['dbsetup']);	
		$GLOBALS['dbsetup']->create_tables();	
		
		#else {
		#	$tpl->set_var('db_action_text', 'Database could not be created. You must create it manually');
		#	exit;
		#}
	} elseif(!empty($_POST['register'])) {
		#send message to update url
		$newUrl = preg_replace('/\?su3d=1/','',$_POST['NewUrl']);
		$mothership = $GLOBALS['s3db_info']['deployment']['mothership'];
		
		if($Did!='' && $Did!='localhost') {
			list($valid, $resp) = update_url_registry(compact('mothership', 'newUrl', 'publicKey', 'Did'));
		}
		if ($valid) {
			$tpl->set_var('updated', 'URL updated');	
		} else {
			$tpl->set_var('updated', 'Could not update URL, please check 1) if you are online and 2) if this deployment ID ('.$Did.') is registered in '.$mothership);	
		}
//	} else if (!empty($_GET['action']) && $_GET['action']=='droptables') {
	} elseif (!empty($_POST['droptables'])) {
		$tpl->set_var('db_action_text', 'Are you sure you want to drop all the tables? You will lose all your data in the database');
		$tpl->set_var('db_action', '<input type="submit" name="confirmdroptables" value="&nbsp;&nbsp;OK&nbsp;&nbsp;">&nbsp;&nbsp;&nbsp;&nbsp;<input type="submit" name="rejectdroptables" value="Cancel">');
	} elseif (!empty($_POST['confirmdroptables'])) {
		#echo " I am here";
		#$GLOBALS['dbsetup']->drop_tables();	
	} elseif(!empty($_POST['createadmin']) &&!isset($_SESSION['adminset'])) {
		create_admin_account($_POST['adminpassword'], $_POST['adminpasswordcheck'], $tpl);	
	} elseif(!empty($_POST['adminlogin'])) {
		Header('location: home.php');
		exit;
   	} elseif(!empty($_POST['updateadmin'])) {
		update_admin_account($_POST['adminpassword'], $_POST['adminpasswordcheck'], $tpl);	
	}
	
	if($GLOBALS['s3db_info']['deployment']['Did']!='localhost') {
		$tpl->set_var('registry_header', '<td colspan="2" align="center"><font color="{account_text}"><b>S<sup>3</sup>DB Registry</b></font></td>');
		$tpl->set_var('registry_info', '<td align="center" width="20%"><img src="{account_action_img}"></td><td>You can use this section to update your registry information at {mothership}.<br />New URL:&nbsp;<input type="text" name = "NewUrl" value="{current_ip}">&nbsp;&nbsp;&nbsp;&nbsp;<input type="submit" value="Update URL" name="register">&nbsp;&nbsp;&nbsp;<font color="red">{updated}</font>
	    <BR>
		{Current_deploymenent_ID} 
		</td>');
	}
	
	$tpl->set_var('logo_url',($GLOBALS['s3db_info']['server']['logo_url']?$GLOBALS['s3db_info']['server']['logo_url']:'http://www.s3db.org/logo.png'));
	$tpl->set_var('logo_file',($GLOBALS['s3db_info']['server']['logo_file']?$GLOBALS['s3db_info']['server']['logo_file']:'images/banner.jpg'));
	$tpl->set_var('action_url', 'dbconfig.php');	
	$tpl->set_var('action_url', 'dbconfig.php');
	$tpl->set_var('db_bg', 'royalblue');
	$tpl->set_var('db_text', 'white');
	$tpl->set_var('db_title', 'Database Setup');
	$tpl->set_var('db_action_img', 'images/redsphere.png');
	$tpl->set_var('account_bg', 'royalblue');
	$tpl->set_var('account_title', 'Administrator Account Setup');
	$tpl->set_var('account_text', 'white');
	$tpl->set_var('account_action_img', 'images/redsphere.png');
	$tpl->set_var('mothership', $mothership);
	#$tpl->set_vat('default_mothership', 'http://s3db.virtual.vps-host.net/central/');

	if(preg_match('/localhost/',S3DB_URI_BASE)) {
		$ip= captureIp();
		$protocol = ($_SERVER['HTTPS']!='')?'https://':'http://';
		$url = $protocol.(($ip!='')?$ip:$_SERVER['SERVER_ADDR']).str_replace('dbconfig.php', '', $_SERVER['REQUEST_URI']);
	} else {
		$protocol = ($_SERVER['HTTPS']!='')?'https://':'http://';	
		$url = S3DB_URI_BASE;
	}
		
	if(!preg_match('/localhost|127.0.0.1/', $url)) {
		$tpl->set_var('current_ip', $url);
	}
	$tpl->set_var('Current_deploymenent_ID', 'Current deployment ID: '.$GLOBALS['Did']);
	$tpl->set_var('account_text', 'white');
	$tpl->set_var('autocomplete', ($GLOBALS['s3db_info']['server']['autocomplete_login'] ? 'autocomplete="off"' : ''));
	$tpl->set_file(array(
				'header' => 'configheader.tpl',
				'dbconfig' => 'dbconfig.tpl',
				'adminacct' => 'adminacct.tpl',
				//'footer' => 'footer.tpl'
				'adminacctdone' => 'adminacctdone.tpl'
			));
	// $tpl->set_var('account_action_text', 'No admin and demo accounts created');
	#include ('createdb.php');
	
	if($GLOBALS['dbsetup']->loaddb() == 0) {
		$tpl->set_var('db_action_img', 'images/redsphere.png');
		$tpl->set_var('db_action_text', 'Check whether your database server is running. If yes, make sure you create a database and a database user privileged to fully access this database and adjust your config.inc.php according');
		//$tpl->set_var('db_action', '<input type="submit" name="logout" value="Log Out">');
		$tpl->parse('_conf','header', True);
		$tpl->parse('_conf','dbconfig', True);
	} else {
		if (!$GLOBALS['dbsetup']->detectdb()) {
			if(!$tpl->get_var('db_action_text')) {
				$tpl->set_var('db_action_text', 'S<sup>3</sup>DB database has already been created. Now you need to create database tables');
			}
			$tpl->set_var('db_action_img', 'images/redsphere.png');
			if(!$tpl->get_var('db_action')) {
				$tpl->set_var('db_action', '<input type="submit" name="createtables" value="Create Tables">');
			}
			$tpl->parse('_conf','header', True);
			$tpl->parse('_conf','dbconfig', True);
			//$tpl->parse('_conf','footer', True);
       	} else {
			if(!$tpl->get_var('db_action_text')) {
				$tpl->set_var('db_action_text', 'S<sup>3</sup>DB database and tables were successfully created. You can now proceed to creating the administrator account.');  
				if($_SESSION['user']['account_lid']=='Admin') {
					$tpl->set_var('db_action_config', '<input type="button" name="setup" value="Change Database Setup" onClick = "window.location=\'setup.php\'">');
				}
			}
      		if(!$tpl->get_var('db_action')) {
				#   	$tpl->set_var('db_action', '<input type="checkbox" name="register" checked>Register this S<sup>3</sup>DB at '.$GLOBALS['s3db_info']['deployment']['mothership']);
				$tpl->set_var('db_action_img', 'images/greensphere.png');
      		}
			if(!detect_admin() && $_SESSION['adminset'] =='') {
				//echo "not set";
				//$tpl->set_var('account_title', 'Admin Account Setup ');  
				$tpl->set_var('account_name', 'Administrator account ');  
				$tpl->set_var('passname', 'adminpassword');  
				$tpl->set_var('passcheckname', 'adminpasswordcheck');  
				$tpl->set_var('submitname', 'createadmin');  
				$tpl->set_var('submitvalue', 'Create Admin Account');  
				$tpl->parse('_conf','header', True);
				$tpl->parse('_conf','dbconfig', True);
				$tpl->parse('_conf','adminacct', True);
			} elseif(!empty($_POST['resetadminpass'])) {
				$tpl->set_var('account_name', 'Administrator account ');  
				$tpl->set_var('passname', 'adminpassword');  
				$tpl->set_var('passcheckname', 'adminpasswordcheck');  
				$tpl->set_var('submitname', 'updateadmin');  
				$tpl->set_var('submitvalue', 'update Admin Account');  
				$tpl->parse('_conf','header', True);
				$tpl->parse('_conf','dbconfig', True);
				$tpl->parse('_conf','adminacct', True);
			} else {
				//$tpl->set_var('account_title', 'Admin Account Setup ');  
				//echo "set";
				$tpl->set_var('account_action_img', 'images/greensphere.png');
				if(!empty($_SESSION['adminset'])) {
					$admin_set = 'Administrator account has been created (Login: Admin). &nbsp;&nbsp;&nbsp;&nbsp;<input type="submit" name="adminlogin" Value="Enter S3DB">';
				} else {
					$admin_set = 'Administrator account has been created (Login: Admin).&nbsp;&nbsp;&nbsp;&nbsp;<input type="submit" name="resetadminpass" Value="Reset Password">';
				}
				$tpl->set_var('account_action_text', $admin_set);
				$tpl->set_var('logout_button','<input type="submit" name="logout" value="Log Out">');
				$tpl->parse('_conf','header', True);
				$tpl->parse('_conf','dbconfig', True);
				$tpl->parse('_conf','adminacctdone', True);
			}
		}
	}
	$tpl->pfp('conf','_conf');
		
	function detect_admin() {
		$db = $GLOBALS['dbsetup']->db;
		$sql= "select account_lid from s3db_account where account_uname ='Admin'";
		$db->query($sql, __LINE__, __FILE__);
		$db->next_record();
		//echo "hey ".$db->f('account_lid');
		if($db->f('account_lid') =='Admin') {
			##Check if deployment_id already exists. If not,create it at this point. 
			$DidNum = preg_replace('/^D/','',$GLOBALS['s3db_info']['deployment']['Did']);
			$sql = "select * from s3db_deployment where deployment_id = '".$DidNum."'";
			$db->query($sql, __LINE__,__FILE__);
				
			if($db->f('deployment_id')!=$DidNum) {
				$sql = "insert into s3db_deployment (deployment_id, url, publickey, checked_on, checked_valid) values ('".$DidNum."','".S3DB_URI_BASE."', '".$GLOBALS['s3db_info']['deployment']['public_key']."',now(), now())";
				$db->query($sql, __LINE__, __FILE__);
				$db->query("insert into s3db_permission (uid, shared_with, permission_level, id, created_by, created_on) values('U2', 'D".$DidNum."', '002', 2, '1', now())", __LINE__, __FILE__);
				$db->query("insert into s3db_permission (uid, shared_with, permission_level, id, created_by, created_on) values('G3', 'D".$DidNum."', '002', 3, '1', now())", __LINE__, __FILE__);
				$db->query("insert into s3db_permission (uid, shared_with, permission_level, id, created_by, created_on) values('U1', 'D".$DidNum."', '222', 1, '1', now())", __LINE__, __FILE__);
			}
			//echo "True";
			return True;		
		} else {
			//echo "False";
			return False;		
		}
	}
	
	function update_admin_account($passwd1, $passwd2, $tpl) {
		if($passwd1 != $passwd2) {
			$tpl->set_var('account_msg', 'Re-typed password does not match the password');		
		} else {
			$db = $GLOBALS['dbsetup']->db;
			$sql = "update s3db_account set account_pwd='".md5($passwd1)."', modified_on=now(), modified_by='-100' where account_lid='Admin'";
			$db->query($sql, __LINE__, __FILE__);
		}
	}	
	function create_admin_account($passwd1, $passwd2, $tpl) {
		if($passwd1 != $passwd2) {
			//echo $passwd1;
			//echo $passwd2;
			$tpl->set_var('account_msg', 'Re-typed password does not match the password');		
		} else {
			$db = $GLOBALS['dbsetup']->db;
			$db->query("select account_lid from s3db_accounts where account_uname ='Admin'", __LINE__, __FILE__);
			if($db->f('account_lid') =='Admin') {
				//echo $db->f('account_lid');
				$tpl->set_var('account_msg', 'Admin account already exists');
			} else {
				#Insert deployment_id in deployments table
				$sql = "insert into s3db_deployment (deployment_id, url, publickey, checked_on, checked_valid) values ('".preg_replace('/^D/','',$GLOBALS['s3db_info']['deployment']['Did'])."','".S3DB_URI_BASE."', '".$GLOBALS['s3db_info']['deployment']['public_key']."',now(), now())";
				$db->query($sql, __LINE__, __FILE__);
				
				//$db->Debug = True;
				$db->query("insert into s3db_addr (addr_id) values('0')", __LINE__, __FILE__);	
				$db->query("insert into s3db_account (account_id, account_lid, account_pwd, account_uname, account_group, account_type, created_on, created_by) values('1', 'Admin','".md5($passwd1)."', 'Admin', 'a', 'g', now(), '-100')", __LINE__, __FILE__);
				$sql = "insert into s3db_permission (uid, shared_with, permission_level, id, created_by, created_on) values('U1', 'G1', '222', 1, '-100', now())";
				#echo $sql;
				$db->query($sql, __LINE__, __FILE__);

				##Now insert public user and public group
				$db->query("insert into s3db_account (account_id, account_lid, account_pwd, account_uname, account_group, account_type, created_on, created_by) values('2', 'public','".md5('public')."', 'public', 'p', 'p', now(), '1')", __LINE__, __FILE__);
				$dbdata = get_object_vars($db);
				$db->query("insert into s3db_account (account_id, account_lid, account_pwd, account_uname, account_group, account_type, created_on, created_by) values('3', 'group_public','".md5('group_public')."', 'group_public', 'g', 'g', now(), '1')", __LINE__, __FILE__);
				
				$db->query("insert into s3db_permission (uid, shared_with, permission_level, id, created_by, created_on) values('G3', 'D".$GLOBALS['s3db_info']['deployment']['Did']."', '002', 3, '1', now())", __LINE__, __FILE__);
				
				#Finally, insert user public in group public
				$sql = "insert into s3db_permission (uid, id, shared_with, permission_level, created_by, created_on, pl_view, pl_change, pl_use, id_num, id_code, shared_with_num, shared_with_code) values ('U2','2', 'G3', '202', '-100', now(), '2','0','2', '2','U', '3', 'G')";
				$db->query($sql, __LINE__, __FILE__);
				#$db->query("insert into s3db_shared (uid_code,uid_num, relation, shared_with_code, shared_with_num, created_by, created_on) values('U',2,'S','G', 3, 1, now())", __LINE__, __FILE__);
				#$db->query($sql, __LINE__, __FILE__);
				#echo $sql;exit;
				
				$numDid = substr($GLOBALS['s3db_info']['deployment']['Did'], 1, strlen($GLOBALS['s3db_info']['deployment']['Did']));
				$pubKey = $GLOBALS['s3db_info']['deployment']['public_key'];
				$sql = "insert into s3db_deployment (deployment_id, url, publickey, checked_on, checked_valid) values ('".$numDid."','".(($_SERVER['HTTPS']!='')?'https://':'http://'.$_SERVER['SERVER_NAME'].'/'.strtok($_SERVER['PHP_SELF'], '/'))."', '".$pubKey."',now(), now())";
				
				$db->query($sql, __LINE__, __FILE__);
				#echo '<pre>';print_r($dbdata);
				//echo $account_id;
				if($dbdata['Errno']=='0') {
					$db->query("insert into s3db_account_group (account_id, group_id) values('".$account_id."', '".$account_id."')", __LINE__, __FILE__);
					$_SESSION['adminset'] = $account_id;
					$user_info = array(
									'account_id' => $account_id,
									'account_lid' => 'Admin',
									'account_uname' => 'Admin',
									'account_group' => 'a'
								);
				    $_SESSION['user'] = $user_info;
				
					if($_SESSION['db']=='') {
						$db = CreateObject('s3dbapi.db');
						$db->Halt_On_Error = 'no';
						$db->Host     = $GLOBALS['s3db_info']['server']['db']['db_host'];
						$db->Type     = $GLOBALS['s3db_info']['server']['db']['db_type'];
						$db->Database = $GLOBALS['s3db_info']['server']['db']['db_name'];
						$db->User     = $GLOBALS['s3db_info']['server']['db']['db_user'];
						$db->Password = $GLOBALS['s3db_info']['server']['db']['db_pass'];
						$db->connect();

						$_SESSION['db'] = $db;
        			}
				}
			}
		}
	}
?>
