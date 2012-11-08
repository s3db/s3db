<?php
	/****************************************************************************\
     * S3DB                                                                     *
     * http://www.s3db.org                                                      *
     * Written by Chuming Chen <chumingchen@gmail.com>                          *
     * ------------------------------------------------------------------------ *
     * This program is free software; you can redistribute it and/or modify it  *
     * under the terms of the GNU General Public License as published by the    *
     * Free Software Foundation; either version 2 of the License, or (at your   *
     * option) any later version.                                               *
     * See http://www.gnu.org/copyleft/gpl.html for detail                      *
    \****************************************************************************
	 *																			*
	 *Modified by Helena Deus <hdeus@s3db.org>									*
	\****************************************************************************/
	#Helena F Deus (helenadeus@gmail.com)
	ini_set('display_errors',0);
	if($_REQUEST['su3d']) {
		ini_set('display_errors',1);
	}
	if(file_exists('../config.inc.php')) {
		include('../config.inc.php');
	} else {
		Header('Location: index.php');
		exit;
	}
	   
	include_once(S3DB_SERVER_ROOT.'/header_pop_ups.inc.php');

	if(!isset($_SESSION['user'])) {
		Header('Location: ../login.php?error=2');
		exit;
	}
                                                                                              
	if($_GET['entity'] !='') {
		$entity = $_GET['entity'];
	}
		
	if($_REQUEST['project_id'] == '') {
		$tpl->set_var('message', 'You are not working with any project yet. Please select a working <a href="../project/index.php">project</a> by clicking the <b>Name</b> of project.<br /><br />');	
	} elseif($resource_acl != -1 && $acl != 0 && $acl != 1 && $acl != 2 && $acl != 3) {		#USER PERMISSIONS
		echo 'You are not allowed on this project.';
	} else {
		$table = compact('entity', 'project_info', 'resource_info', 'db', 'user_id');
		echo generate_table($table);
	}
	
	function generate_table($table) {
		extract($table);
		if($project_id =='') { $project_id = $project_info['project_id']; }
		$R = array('db'=>$db, 'project_id'=>$project_id, 'subject'=>$resource_info['entity'], 'object'=>'!=UID');
		$rules = list_shared_rules($R);
		
		###Fetch all the objects and all of the verbs to create the header
		if(empty($_SESSION['show_me'])) {
			if(is_array($rules)) {
				$verbs = array_map('grab_verb', $rules);
			}
		} else {
			$verbs = array_map('grab_verb', $_SESSION['show_me']);
		}
		$total_objects = 0;
		if(is_array($verbs)) {
			foreach($verbs as $i =>$value) {
				#Make a new query on rules, this time to find the object that has this specific verb
				if(empty($_SESSION['show_me'])) {
					$objects = array_map('grab_object', $rules, $verbs);
				} else {
					$objects = array_map('grab_object', $_SESSION['show_me'], $verbs);
				}
				$total_objects += count($objects);
			}
		}
		$total_objects += 2;
	
		$All = array('db'=>$db, 'entity'=>$resource_info['entity'], 'project_id'=>$project_id, 'rules'=>$rules, 'verbs'=>$verbs, 'objects'=>$objects, 'format'=>'html', 'color'=>'on');
		#Create the html part of the table
		#$out = sprintf("%s\n", '<table width=100% border=1 style="border-collapse: collapse;">');
		$out.= sprintf("%s\n", '	<tr><td colspan="'.$total_objects.'">Data of '.$entity.' (Project: '.$project['name'].')</td></tr>');
		$out.= sprintf("%s\n", '	<tr><td colspan="'.$total_objects.'">Page '.$_SESSION['current_page'].'</td></tr>');
		$out.= create_datamatrix_header($All);
		$out.= render_datamatrix_values($All);
		#$out.= sprintf("%s\n", '</table>');
		return $out;
	}
?>
