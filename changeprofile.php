<?php
	/***************************************************************************\
	* S3DB                                                                     *
	* http://www.s3db.org			                                   *
	* Written by Chuming Chen <chumingchen@gmail.com>                          *
	* ------------------------------------------------------------------------ *
	* This program is free software; you can redistribute it and/or modify it  *
	* under the terms of the GNU General Public License as published by the    *
	* Free Software Foundation; either version 2 of the License, or (at your   *
	* option) any later version.						   * 
	* See http://www.gnu.org/copyleft/gpl.html for detail                      *
	\**************************************************************************
	*																			*
	*Modified by Helena F Deus <hdeus@s3db.org>									*
	\**************************************************************************/
	 ini_set('display_errors',0);
	if($_REQUEST['su3d'])
	ini_set('display_errors',1);
	if(file_exists('./config.inc.php'))
	{
		include('./config.inc.php');
	}
	else
	{
		Header('Location: ./log.php?error=7');
		exit;
	}
		
	include(S3DB_SERVER_ROOT.'/header.inc.php');
	$useredited = get_user_info($_SESSION['user']['account_id']);
	$tpl->set_var('account_id', $useredited['account_id']);	
	$tpl->set_var('account_addr_id', $useredited['account_addr_id']);	
	
	$G = compact('db', 'sortorder', 'direction', 'user_id');
	$groups = list_all_groups($G);
	
	
	$tpl->set_file(array('profile'=> 'profile.tpl', 'footer'=>'footer.tpl'));
	$tpl->set_block('profile', 'top', '_contents');
	$tpl->set_block('profile', 'profile_edit', '_contents');
	$tpl->set_block('profile', 'bottom', '_contents');
	if($_POST['submit'])
	{
		$updateduser = Array('account_lid'=>$useredited['account_lid'],
				 'account_id'=>$useredited['account_id'],
				 'account_status'=>$useredited['account_status'],
				 'account_type'=>$useredited['account_type'],
				 'account_uname'=>$useredited['account_uname'],
				 'account_group' => $useredited['account_group'],
				'created_by'=>$useredited['created_by'],
				 'account_pwd'=>$_POST['account_pwd'],
				 'account_pwd_2'=>$_POST['account_pwd_2'],
				 'addr1'=>$_POST['addr1'],
				 'addr2'=>$_POST['addr2'],
				 'city'=>$_POST['city'],
				 'state'=>$_POST['state'],
				 'postal_code'=>$_POST['postal_code'],
				 'country'=>$_POST['country'],
				 'account_addr_id'=>$_POST['account_addr_id'],
				 'account_email'=>$_POST['account_email'],
				 'account_phone'=>$_POST['account_phone']);
		//print_r($updateduser);	
		$action = 'edit';
		$other = compact('db', 'user_id', 'action');
		$validity = validate_user_inputs($updateduser, $other);
		switch($validity)
		{
			case 0:
				if(!update_user($updateduser))
				{
					if ($_SESSION['user']['account_uname']!='guest')
					$tpl->set_var('message', 'Failed to update your profile!');
					else
					$tpl->set_var('message', 'Guest user cannot change profile!');
				}
				else {
					$tpl->set_var('message', 'Your profile has been updated!');				
					$useredited = $updateduser;
					
				}
				break;
			case 1:
				$tpl->set_var('loginid_required', '*');
				$tpl->set_var('message', 'Login is required');
				break;
			case 2:
				$tpl->set_var('active_required', '*');
				$tpl->set_var('message', 'Acccount active needs to be checked');
				break;
			case 3:
				$tpl->set_var('firstname_required', '*');
				$tpl->set_var('message', 'First Name is required');
				break;
			case 4:
				$tpl->set_var('lastname_required', '*');
				$tpl->set_var('message', 'Last Name is required');
				break;
			case 5:
				$tpl->set_var('password_required', '*');
				$tpl->set_var('message', 'Password is required');
				break;
			case 6:
				$tpl->set_var('password2_required', '*');
				$tpl->set_var('message', 'You need to re-type your password to confirm');
				break;
			case 7:
				$tpl->set_var('password_required', '*');
				$tpl->set_var('password2_required', '*');
				$tpl->set_var('message', 'Re-typed password does not match');
				break;
			case 8:
				$tpl->set_var('message', 'User'.$useredited['account_lid'].'already exists');
				break; 
			}
	};
	 $tpl->set_var('image_path', '..');
	$tpl->set_var('group_list', create_static_group_list($groups, $useredited['account_id']));
	
	$tpl->set_var('section_num', '2');
	$tpl->set_var('action_url', 'changeprofile.php');
	$tpl->set_var('website_title',  $GLOBALS['s3db_info']['server']['site_title'].' - change profile');
	$tpl->set_var('edit_message', 'My Profile');
	$tpl->set_var('content_width', '70%');
	$tpl->set_var('action', '<input type="submit" name="submit" value="Update My Profile">');
	$tpl->set_var('account_lid', $useredited['account_lid']);		
	
	$tpl->set_var('account_status', ($useredited['account_status']=='A'?'Active':'Inactive'));
	$tpl->set_var('account_uname', $useredited['account_uname']);
	$tpl->set_var('account_type', ($useredited['account_type']=='u')?'User':'Group');
	$tpl->set_var('account_last_login_on', substr($useredited['account_last_login_on'], 0, 19));
	$tpl->set_var('account_last_login_from', $useredited['account_last_login_from']);
	$tpl->set_var('account_last_pwd_changed_on', substr($useredited['account_last_pwd_changed_on'], 0, 19));
	$tpl->set_var('account_last_pwd_changed_by', $useredited['account_last_pwd_changed_by']);
	$tpl->set_var('created_on', substr($useredited['created_on'], 0, 19));
	$tpl->set_var('created_by', find_user_loginID($useredited['created_by']));
	$tpl->set_var('modified_by', find_user_loginID($useredited['modified_by']));
	$tpl->set_var('modified_on', substr($useredited['modified_on'], 0, 19));
	$tpl->set_var('addr1', $useredited['addr1']);
	$tpl->set_var('addr2', $useredited['addr2']);
	$tpl->set_var('city', $useredited['city']);
	$tpl->set_var('state', $useredited['state']);
	$tpl->set_var('postal_code', $useredited['postal_code']);
	$tpl->set_var('country', $useredited['country']);
	$tpl->set_var('account_email', $useredited['account_email']);
	$tpl->set_var('account_phone', $useredited['account_phone']);
        $tpl->fp('_output', 'top', True);
        $tpl->fp('_output', 'profile_edit', True);
        $tpl->fp('_output', 'bottom', True);
        $tpl->parse('_output', 'footer', True);
        $tpl->pfp('out','_output');
	
	
?>
