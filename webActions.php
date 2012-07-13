<?php
	ini_set('display_errors',0);
	if($_REQUEST['su3d']) {
		ini_set('display_errors',1);
	}
	$possible_args = array('id', 'key', 'project_id', 'user_id', 'group_id', 'class_id', 'instance_id', 'collection_id', 'item_id', 'rule_id', 'statement_id', 'literal_verb', 'literal_object', 'class_object', 'item_verb', 'action', 'su3d');

	foreach ($possible_args as $new_arg) {	//for all possible arguments, chose the ones that are not empty
		if ($_REQUEST[$new_arg]!='') {
			if($moreargs=='') {
				$moreargs .='?';
			} else { 
				$moreargs .='&';
			}
			$moreargs .= $new_arg.'='.$_REQUEST[$new_arg];
		}
	}

	//$args.= $moreargs;
	$args = $moreargs;
	if($args=='') $args='?';

	$action['login'] = S3DB_URI_BASE.'/login.php';
	$action['dbconfig'] = S3DB_URI_BASE.'/dbconfig.php';
	$action['setup'] = S3DB_URI_BASE.'/setup.php';
	$action['logout'] = S3DB_URI_BASE.'/logout.php';
	$action['home'] = S3DB_URI_BASE.'/home.php'.$args;
	$action['main'] = S3DB_URI_BASE.'/main.php'.$args;
	$action['header'] = S3DB_URI_BASE.'/frames/HeaderFrame.php'.$args;
	$action['projectFrames'] = S3DB_URI_BASE.'/frames/RestofFrames.php'.$args;
	$action['admin'] = S3DB_URI_BASE.'/admin/index.php'.$args;
	$action['projectstree'] = S3DB_URI_BASE.'/tigre_tree/projectsTree.php'.$args;
	
	$action['changeprofile'] = S3DB_URI_BASE.'/changeprofile.php';
	$action['listusers'] = S3DB_URI_BASE.'/admin/user.php'.$args;
	$action['remoteuser'] = S3DB_URI_BASE.'/admin/remoteuser.php'.$args;
	$action['createuser'] = S3DB_URI_BASE.'/admin/createuser.php'.$args;
	$action['edituser'] = S3DB_URI_BASE.'/admin/edituser.php'.$args;
	$action['deleteuser'] = S3DB_URI_BASE.'/admin/deleteuser.php'.$args;
	$action['accesslog'] = S3DB_URI_BASE.'/admin/accesslog.php'.$args;
	$action['viewuser'] = S3DB_URI_BASE.'/admin/viewuser.php'.$args;
	$action['listkeys'] = S3DB_URI_BASE.'/access_keys.php';
	
	$action['listgroups'] = S3DB_URI_BASE.'/admin/group.php'.$args;
	$action['editgroup'] = S3DB_URI_BASE.'/admin/editgroup.php'.$args;
	$action['deletegroup'] = S3DB_URI_BASE.'/admin/deletegroup.php'.$args;
	$action['creategroup'] = S3DB_URI_BASE.'/admin/creategroup.php'.$args;
	
	$action['listprojects'] = S3DB_URI_BASE.'/project/allprojects.php'.$args;
	$action['projectstree'] = S3DB_URI_BASE.'/tigre_tree/projectsTree.php'.$args;
	$action['map'] = S3DB_URI_BASE.'/mapproject.php'.$args;
	
	$action['project'] = S3DB_URI_BASE.'/project/project.php'.$args;
	$action['editproject'] = S3DB_URI_BASE.'/project/editproject.php'.$args;
	$action['createproject'] = S3DB_URI_BASE.'/project/insertproject.php'.$args;
	$action['remoteproject'] = S3DB_URI_BASE.'/project/remoteproject.php'.$args;
	$action['deleteproject'] = S3DB_URI_BASE.'/project/deleteproject.php'.$args;
	
	$action['sharerules'] = S3DB_URI_BASE.'/rule/sharerules.php'.$args;
	$action['inspectrules'] = S3DB_URI_BASE.'/rule/ruleinspector.php'.$args;
	$action['listrules'] = $action['inspectrules'];
	$action['ruleinspector'] = $action['inspectrules'];
	$action['rdfmenu'] = S3DB_URI_BASE.'/rdfmenu.php'.$args;
	$action['xmlimport'] = S3DB_URI_BASE.'/project/xmlimport.php'.$args;
	$action['rdfimport'] = S3DB_URI_BASE.'/rdfRestore.php'.$args;
	$action['excelimport'] = S3DB_URI_BASE.'/resource/xlsparse.php'.$args;
	
	$action['excelexport'] = S3DB_URI_BASE.'/xlsproject.php'.$args;
	$action['xmlexport'] = S3DB_URI_BASE.'/xmlproject.php'.$args;
	$action['rdfexport'] = S3DB_URI_BASE.'/rdf.php'.$args;
	
	$action['resource']  = S3DB_URI_BASE.'/resource/resource.php'.$args;
	$action['class'] =  S3DB_URI_BASE.'/resource/resource.php'.$args;
	$action['createclass'] = S3DB_URI_BASE.'/resource/createclass.php'.$args;
	$action['remoteclass'] = S3DB_URI_BASE.'/resource/remoteclass.php'.$args;
	$action['editclass'] = S3DB_URI_BASE.'/resource/editclass.php'.$args;
	$action['deleteclass'] = S3DB_URI_BASE.'/resource/deleteclass.php'.$args;
	
	$action['rule'] = S3DB_URI_BASE.'/resource/query_page.php'.$args;
	$action['querypage'] = S3DB_URI_BASE.'/resource/query_page.php'.$args;
	$action['queryresult'] = S3DB_URI_BASE.'/resource/queryresult.php'.$args;
	$action['listall'] = $action['queryresult'].'&listall=yes';
	$action['view'] = S3DB_URI_BASE.'/viewresource.php'.$args;
	$action['peek'] = S3DB_URI_BASE.'/resource/peek.php'.$args;
	$action['peekverb'] = S3DB_URI_BASE.'/rule/peekverb.php'.$args;
	$action['ruletemplate'] = $action['excelexport'].'&data=no';
	$action['insertinstance'] = S3DB_URI_BASE.'/resource/insertinstance.php'.$args;
	
	//$args = $args.'&rule_id='.$rule_id;
	$action['insertstatement'] = S3DB_URI_BASE.'/statement/insertstatement.php'.$args;
	$action['peekinstances']  = S3DB_URI_BASE.'/statement/existuid.php'.$args;
	$action['editrules'] = S3DB_URI_BASE.'/rule/ruleinspector.php'.$args;
	$action['createrule'] = S3DB_URI_BASE.'/rule/ruleinspector.php'.$args;
	$action['remoterule'] = S3DB_URI_BASE.'/rule/remoterule.php'.$args;
	$action['deleterule'] = S3DB_URI_BASE.'/rule/deleterule.php'.$args;
	$action['sharerules'] = S3DB_URI_BASE.'/rule/sharerules.php'.$args;
	
	//$args = $args.'&instance_id='.$_REQUEST['instance_id'];
	$action['peekinstances']  = S3DB_URI_BASE.'/statement/existuid.php'.$args;
	
	//$args = str_replace($args.'&rule_id='.$rule_id, '', $args);
	$action['item']  = S3DB_URI_BASE.'/item.php'.$args;
	$action['instance']  = S3DB_URI_BASE.'/resource/instance.php'.$args;
	$action['editinstance'] = S3DB_URI_BASE.'/resource/editinstance.php'.$args;
	$action['deleteinstance'] = S3DB_URI_BASE.'/resource/deleteinstance.php'.$args;
	$action['instanceform'] = S3DB_URI_BASE.'/resource/insertall.php'.$args;
	$action['editinstanceform'] = S3DB_URI_BASE.'/statement/editall.php'.$args;
	
	//$args = str_replace($args.'&statement_id='.$statement_id, '', $args);
	$action['insertstatement'] = S3DB_URI_BASE.'/statement/insertstatement.php'.$args;
	$action['editstatement'] = S3DB_URI_BASE.'/statement/editstatement.php'.$args;
	$action['deletestatement'] = S3DB_URI_BASE.'/statement/deletestatement.php'.$args;
	//$action['download']  = S3DB_URI_BASE.'/download.php'.$args;
	$action['download']  = S3DB_URI_BASE.'/download.php'.(($_REQUEST['key']!='')?'?key='.$_REQUEST['key']:'?');
	$action['selfupdate']  = S3DB_URI_BASE.'/selfupdate.php'.$args;
	$action['websparql']  = S3DB_URI_BASE.'/webSPARQL.php'.$args;
	$action['sparqlframes']  = S3DB_URI_BASE.'/frames/sparqlFrames.php'.$args;
	//$action['sparqlform']  = S3DB_URI_BASE.'/frames/sparqlForm.php'.$args;
	$action['sparqlform']  = "http://ibl.mdanderson.org/~mhdeus/sparql/s3db_endpoint.html?url=".S3DB_URI_BASE."/";
	
	$GLOBALS['webaction']=$action;
	$GLOBALS['action']=$action;
?>