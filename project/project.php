<?php
	#project_page displays general information on the project;
	#Includes links to resource pages, xml and rdf export 
	#Helena F Deus (helenadeus@gmail.com)
	ini_set('display_errors',0);
	if($_REQUEST['su3d']) {
		ini_set('display_errors',1);
	}
	if($_SERVER['HTTP_X_FORWARDED_HOST']!='') {
		$def = $_SERVER['HTTP_X_FORWARDED_HOST'];
	} else {
		$def = $_SERVER['HTTP_HOST'];
	}	
	if(file_exists('../config.inc.php')) {
		include('../config.inc.php');
	} else {
		Header('Location: http://'.$def.'/s3db/');
		exit;
	}
	
	$key = $_GET['key'];
	#Get the key, send it to check validity

	include_once('../core.header.php');

	#if($key) {
	#	$user_id = get_entry('access_keys', 'account_id', 'key_id', $key, $db);
	#} else {
	#	$user_id = $_SESSION['user']['account_id'];
	#}

	#Universal variables
	$sortorder = $_REQUEST['orderBy'];
	$direction = $_REQUEST['direction'];
	$project_id = $_REQUEST['project_id'];
	$uid_info = uid($project_id);
	#$acl = find_final_acl($user_id, $project_id, $db);
	$project_info = URIinfo('P'.$project_id, $user_id, $key, $db);
	$uni = compact('db', 'acl','user_id','key', 'project_id', 'dbstruct', 'sortorder', 'direction');

	#relevant extra arguments
	#$args = '?key='.$_REQUEST['key'].'&project_id='.$_REQUEST['project_id'];

	#Define the page actions
	include('../webActions.php'); #include the specification of the link map. Must be put in here becuase arguments vary.
	
	if ($project_id=='') {
		echo "Please specify a project_id";
		exit;
	} elseif(!$project_info['view']) {
		echo "User does not have access in this project.";
		exit;
	} else {
		#$project_info = S3QLinfo('project', $project_id, $user_id,$db);
		#$project_info = URI('P'.$project_id, $user_id, $db);
		include '../S3DBjavascript.php';
?>	
<TABLE  width=100% border=0>
	<!-- Project description -->
	<TR>
		<TD>
			<table  border=0 class="intro" width="100%"  align="center">
				<tr bgcolor="#99CCFF">
					<td colspan="3" align="center" ><FONT Face="Arial" SIZE="3" COLOR="navy">Project Details</FONT></td>
				</tr>
				<tr class="content">
					<td width="20%">Project Name: </td>
<?php
		echo '
					<td>
						<b>'.$project_info['project_name'].'</b>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;';
		if ($project_info['change']) { 		#cannot change it here if remote
			if($uid_info['Did']==$GLOBALS['Did']) {
				echo '<a href="'.$action['editproject'].'" title="Edit project ('.$project_info['project_id'].')">Edit</a>&nbsp;&nbsp;&nbsp;';
			}
			echo '<a href="'.$action['deleteproject'].'" title="Delete project ('.$project_info['project_id'].')">Delete</a>&nbsp;&nbsp;&nbsp;';
		}
		#if(count($resources)>0){
		echo '
						<a href=# onclick="parent.ProjectsFrames.location.href = \'../frames/ProjectsFrames.php?project_id='.$_REQUEST['project_id'].'\'">Map (embed)</a>
						&nbsp;&nbsp;&nbsp;<a href=# onclick="window.open(\''.$action['map'].'\')" '.$disable.'></B>Map<B></a></b>
					</td>';
		#}
?>
				</tr>
<?php
		echo '
				<tr class="">
					<td>Project Description: </td>
					<td><b>'.$project_info['project_description'].'</b></td>
				</tr>
				<tr class="">
					<td>Project Owner: </td>
					<td><b>'.find_user_loginID(array('account_id'=>$project_info['project_owner'], 'db'=>$db)).'</b></td>
				</tr>
				<tr class="">
					<td>Created By: </td>
					<td><b>'.find_user_loginID(array('account_id'=>$project_info['created_by'], 'db'=>$db)).'</b></td>
				</tr>
				<tr class="">
					<td>Created On: </td>
					<td><b>'.$project_info['created_on'].'</b></td>
				</tr>
				<tr class="">
					<td>Project_id: </td>
					<td><b>'.$project_info['project_id'].'</b></td>
				</tr>';
?>
			</table>
		</TD>
	</TR>
	<!-- Resources -->
	<TR>
		<TD>
<?php
		$_SESSION[$user_id]['resources'][$project_id]='';
		if (is_array($_SESSION[$user_id]['resources'][$project_id]) && !empty($_SESSION[$user_id]['resources'][$project_id])) {
			$resources = $_SESSION[$user_id]['resources'][$project_id];
		} else {
			$s3ql=compact('user_id','db');
			$s3ql['select']='*';
			$s3ql['from'] = 'collections';
			$s3ql['where']['project_id'] = $project_id;
			$s3ql['order_by']='entity';
			$resources = S3QLaction($s3ql);
			$_SESSION[$user_id]['resources'][$project_id] = $resources;
		}
		#buttons for resources
		if(is_array($resources)) {
			foreach($resources as $resource_info) {
				#$resource_info['rule_id'] = get_rule_id_by_entity_id($resource_info['resource_id'], $resource_info['project_id'], $db);
				#$resource_info = include_all('class', $resource_info, $user_id, $db);
				if ($resource_info['rule_id']=='') {
					$resource_info['rule_id'] = ruleId4entity(array('entity'=>$resource_info['entity'], 'project_id'=>$resource_info['project_id'], 'db'=>$db));
				}
				#find the number of instances per class
				if($_SESSION[$user_id][$project_id]['instances'][$resource_info['resource_id']]!='') {
					$instances = $_SESSION[$user_id][$project_id]['instances'][$resource_info['resource_id']];
				} 
				/*
				#else {
					$s3ql=compact('user_id','db'); ==>Commented until the query is faster
					$s3ql['select'] = 'count(resource_id)';
					$s3ql['from'] = 'items';
					$s3ql['where']['collection_id']=$resource_info['resource_id'];
					#$s3ql['where']['project_id'] = $_REQUEST['project_id'];
					#$s3ql['where']['entity'] = $resource_info['entity'];
					$instances = S3QLaction($s3ql);
					
					$instance_count = (is_array($instances))?count($instances):0;

					#if there is a session, leave the instances in the sessions
					if(is_array($instances)) {
						foreach ($instances as $instance_info) { 
							$_SESSION['s3db']['P'][$project_id]['C'][$resource_info['resource_id']]['I'][$instance_info['instance_id']] = $instance_info;
						}
					}
				}
				if(!empty($instance_count)) {
					$resource_info['Ninstances'] = $instance_count;
				} else {
					$resource_info['Ninstances'] = 0;
				}
				#$resources_list .='<input type="button" onclick="window.location=\''.$action['resource'].'&class_id='.$resource_info['resource_id'].'&rule_id='.$resource_info['rule_id'].'\'" value="'.$resource_info['entity'].'"> ('.$resource_info['Ninstances'].')&nbsp;&nbsp;&nbsp;&nbsp;';
				*/
				if($resource_info['entity']!='s3dbVerb') {
					$resources_list .='<input type="button" onclick="window.location=\''.$action['resource'].'&class_id='.$resource_info['resource_id'].'\'" value="'.$resource_info['entity'].'"> <!-- ('.$resource_info['Ninstances'].') --> &nbsp;&nbsp;&nbsp;&nbsp;';
				} else {
					$verbClass = $resource_info;
				}
			}
		} else {
			$disable = 'disabled';
			#if($acl == '3')
			if($project_info['add_data']) {
				$noresourceMsg = 'You do not have any classes yet. Please create a new classes first.';	
			} else {
				$noresourceMsg = 'You do not have any classes yet. You also do not have permission to create new classes.';
			}
		}
		
		#extra buttons for resources
		#if($acl=='3')
		if ($project_info['change']) {
			$resources_list.='<br /><br />&nbsp;&nbsp;&nbsp;<input type="button" value="Create New" size="20" onClick="window.location=\''.$action['createclass'] .'\'">';
			$resources_list.='&nbsp;&nbsp;&nbsp;<input type="button" value="Link to Remote" size="20" onClick="window.location=\''.$action['remoteclass'] .'\'">';
			#$resources_list.='&nbsp;&nbsp;&nbsp;<input type="button" value="Share rules" size="20" onClick="window.location=\''.$action['sharerules'].'\'"> (Share rules and resources with other projects)<br />';
			$resources_list.='&nbsp;&nbsp;&nbsp;<input type="button" value="Rule Inspector" size="20" onClick="window.location=\''.$action['inspectrules'].'\'">';
			$resources_list .='&nbsp;&nbsp;&nbsp;<input type="button" onclick="window.location=\''.$action['resource'].'&class_id='.$verbClass['resource_id'].'\'" value="Verbs"> '.(($verbClass['Ninstances']!='')?'('.$verbClass['Ninstances'].')':'').'&nbsp;&nbsp;&nbsp;&nbsp;';
			#$resources_list.='&nbsp;&nbsp;&nbsp;<input type="button" value="Remote Rule" size="20" onClick="window.location=\''.$action['remoterule'].'\'"><br /><br />';
		}
		echo '<table  border=0 class="intro" width="100%"  align="center">
		<tr  bgcolor="#99CCFF"><td  colspan="3" align="center"><FONT Face="Arial" SIZE="3" COLOR="navy">Collections</td></tr>';
		echo '<tr><td>'.$noresourceMsg.'</td></tr>';
		echo '<tr><td>'.$resources_list.'</td></tr>';
		
		#project management section
		$project_actions .= '<center><TABLE>';
		$project_actions .= '<input type="button" value="Excel" size="20" onClick="window.location=\''.$action['excelexport'].'\'" '.$disable.'>&nbsp;&nbsp;&nbsp;';
		$project_actions .= '<input type="button" value="XML" size="20" onClick="window.location=\''.$action['xmlexport'].'\'">&nbsp;&nbsp;&nbsp;';
		#$project_actions .= '<input type="button" value="Export Project in RDF" size="20" onClick="window.location=\''.$action['rdfexport'].'\'">&nbsp;&nbsp;&nbsp;';
		$project_actions .= '<input type="button" value="RDF" size="20" onClick="window.open(\''.$action['rdfmenu'].'\',null,\'height=350,width=400,resizable=1,scrollbars=1\')">&nbsp;&nbsp;&nbsp;';
		$project_actions .= '<BR></TABLE></center>';
		
		echo '<table  border=0 class="intro" width="100%"  align="center">
		<tr  bgcolor="#99CCFF"><td  colspan="3" align="center"><FONT Face="Arial" SIZE="3" COLOR="navy">Export</td></tr>';
		echo '<tr><td>'.$project_actions.'</tr></td>';
		
		#Find all the users involved in the project
		#$P = compact('db', 'user_id', 'project_id', 'sortorder', 'direction');
		#$shared_users = list_project_users($P);
		#$shared_users = list_shared_users($uni);
		
		if (is_array($_SESSION[$user_id]['users'][$project_id])) {
			$shared_users = $_SESSION[$user_id]['users'][$project_id];
		} else {
			$s3ql=compact('user_id','db');
			$s3ql['select']='*';
			$s3ql['from']='users';
			$s3ql['where']['project_id']=$project_id;
			if($_REQUEST['orderBy']!='') {
				$s3ql['order_by']=$_REQUEST['orderBy'].' '.$_REQUEST['direction'];
			}
			$shared_users = S3QLaction($s3ql);
			#$_SESSION[$user_id]['users'][$project_id] = $shared_users;
		}
		#increment with the owner and the user that is visualizing the project
		if(is_array($shared_users) && !empty($shared_users)) {
			echo '<table  border=0 class="intro" width="100%"  align="center">
			<tr  bgcolor="#99CCFF"><td  colspan="3" align="center"><FONT Face="Arial" SIZE="3" COLOR="navy">Users</td></tr>';
			echo render_elements($shared_users, $acl, array('User ID','Login', 'User Name', 'Permissions'), 'account_acl');
		}
	}
	include '../footer.php'
?>
