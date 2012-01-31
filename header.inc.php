<?php
	/***************************************************************************\
        * S3DB                                                                     *
        * http://www.s3db.org                                                      *
        * Written by Chuming Chen <chumingchen@gmail.com>                          *
        * ------------------------------------------------------------------------ *
        * This program is free software; you can redistribute it and/or modify it  *
        * under the terms of the GNU General Public License as published by the    *
        * Free Software Foundation; either version 2 of the License, or (at your   *
        * option) any later version.                                               *
        * See http://www.gnu.org/copyleft/gpl.html for detail                      *
        \**************************************************************************/
		/***************************************************************************
		/																		  /
		/Modified by Helena Futscher de Deus <helenadeus@gmail.com>				  /
		/																		  /
		/*************************************************************************/
	 ini_set('display_errors',0);
	if($_REQUEST['su3d'])
	ini_set('display_errors',1);
	ini_set("include_path", S3DB_SERVER_ROOT.'/pearlib'. PATH_SEPARATOR. ini_get("include_path")); 	

	include_once(S3DB_SERVER_ROOT.'/s3dbapi/inc/common_functions.inc.php');
	include_once(S3DB_SERVER_ROOT.'/s3dbapi/inc/delete_entries.php');
	include_once(S3DB_SERVER_ROOT.'/s3dbapi/inc/insert_entries.php');
	include_once(S3DB_SERVER_ROOT.'/s3dbapi/inc/update_entries.php');
	include_once(S3DB_SERVER_ROOT.'/s3dbapi/inc/create.php');
	include_once(S3DB_SERVER_ROOT.'/s3dbapi/inc/list_elements.php');
	include_once(S3DB_SERVER_ROOT.'/s3dbapi/inc/element_info.php');
	include_once(S3DB_SERVER_ROOT.'/s3dbapi/inc/SQL.php');
	include_once(S3DB_SERVER_ROOT.'/s3dbapi/inc/htmlgen.php');
	include_once(S3DB_SERVER_ROOT.'/s3dbapi/inc/permissions.php');
	include_once(S3DB_SERVER_ROOT.'/s3dbapi/inc/validation_engine.php');
	include_once(S3DB_SERVER_ROOT.'/s3dbapi/inc/important_vars.php');
	require_once(S3DB_SERVER_ROOT.'/s3dbapi/inc/class.db.inc.php');
	require_once('Structures/DataGrid.php');
	#include_once(S3DB_SERVER_ROOT.'/usercheck.php');

	session_start();
	Header("Cache-control: private"); //IE fix
	//$db = $_SESSION['db'];
	//echo $db->User;
	if(!isset($_SESSION['user']))
	//if($_SESSION['user'] =='')
	{
		if ($_REQUEST['key']!='')
			if(check_key_validity($_REQUEST['key']))
			#echo 'ola';
		
		echo '<META HTTP-EQUIV="Refresh" Content= "0; URL="../login.php?error=2">';
			Header('Location: ?error=2');
		exit;
	}
	
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

	/* Program starts here */
	
	$tpl = CreateObject('s3dbapi.Template', $GLOBALS['s3db_info']['server']['template_dir']);
	$tpl->set_file(array(
		'header' => 'header.tpl'
		));
	
	$tpl->set_var('login_user', $_SESSION['user']['account_uname']);
	$tpl->set_var('current_time', date("D M j, G:i:s T Y"));
	$tpl->set_var('last_updated', date("D M j, G:i:s T Y", filemtime($_SERVER['SCRIPT_FILENAME'])));  
	#echo date("D M j, G:i:s T Y", filemtime($_SERVER['SCRIPT_FILENAME']));
	#this bit is to delete later (or not...) 
	
		if ($_SERVER['REQUEST_URI'] != S3DB_URI_BASE.'/project/index.php')
		$tpl->set_var('option', "<table border=0><tr><td><form method='POST' action='index.php?project_id=".$_REQUEST['project_id']."'><input type='radio' name='tree' value='tree'>Projects view<input type='radio' name='tree' value='resourcelist'>Resources view&nbsp;&nbsp;&nbsp;<input type='submit' value='Change'></form><input type='hidden' name='project_id' value='".$_REQUEST['project_id']."'><td><tr></table>");
		
		
		

	$tpl->set_block('header', 'head', '_header');
	$tpl->fp('_output', 'head', True);
	$tpl->set_block('header', 'menu', '_menu');
	$tpl->fp('_output', 'menu', True);
	//$tpl->set_var('section_num', '1');
	$tpl->set_var('uri_base', S3DB_URI_BASE);
	
	//Lena's: check if the user is allowed on the project


	//Lena's - Create the values for the get of project_id and resource_id

			//For intertable search, put this after the link: {main_rule}{main_resID}

	if (isset($_REQUEST['main_rule']))
	{$tpl->set_var('main_rule', '&main_rule='.$_REQUEST['main_rule'].'');
	}

	if (isset($_REQUEST['main_resID']))
	{$tpl->set_var('main_resID', '&main_resID='.$_REQUEST['main_resID'].'');
	}

	
//Lena's - put this after the link: {get_proj_id}{get_res_id}


	if (isset($_REQUEST['project_id']))
	{$tpl->set_var('get_proj_id', '?project_id='.$_REQUEST['project_id'].'');
	$tpl->set_var('get_proj_id&', 'project_id='.$_REQUEST['project_id'].'&');}
	
	if (isset($_REQUEST['entity_id']))
	{$tpl->set_var('get_res_id', '&entity_id='.$_REQUEST['entity_id'].'');
	}

//Lena's - Create the input type hidden with project_id and resource-id for submit buttons: {hidden_project}{hidden_entity} after every submit button
	
	if (isset($_REQUEST['project_id']))
	$tpl-> set_var('hidden_project', '<input type="hidden" name="project_id" value="'.$_REQUEST['project_id'].'">');
	if (isset($_REQUEST['entity_id']))
	$tpl-> set_var('hidden_entity', '<input type="hidden" name="entity_id" value="'.$_REQUEST['entity_id'].'">');

	
		//Lena's: create variable that holds project information and resource information to be available to all script; put this in every function that requires it - global $resource_info, $project_info;
		

		if($_REQUEST['main_resID'] !='' && $_REQUEST['main_rule'] !='')
			{
				$resource_info = Array('id'=>$_REQUEST['entity_id'],
					'entity'=>get_entity($_REQUEST['entity_id']),
					'main_rule' => $_REQUEST['main_rule'],
					'main_resID' => $_REQUEST['main_resID'], 
					//'main_res_ent'=> get_entity($_REQUEST['main_resID'])
					);
			}
	
		

		
		
	
	
	
	//If the link is used inside a header function, add this: ?project_id='.$_REQUEST['project_id'].'&entity_id='.$_REQUEST['entity_id'].'
	//End Lena's


	if($_SESSION['user']['account_group'] == 'a') 
	{

		
		$tpl->set_block('header', 'admin', '_admin');
	
		$tpl->fp('_output', 'admin', True);
	
		
	}
	else
	{
		$tpl->set_block('header', 'user', '_user');
		$tpl->fp('_output', 'user', True);
	}
	$dbtype = $GLOBALS['s3db_info']['server']['db']['db_type'];
?>
