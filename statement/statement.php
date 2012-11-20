<?php
	#istatement.php is the interface for accessing information on a statement
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
	$statement_id = $_REQUEST['statement_id'];
	$statement_info = URIinfo('S'.$statement_id, $user_id, $key, $db);
	#$statementAcl = statementAcl(compact('user_id', 'db', 'statement_id'));
	$project_id = $_REQUEST['project_id'];

	#relevant extra arguments
	#$args = '?key='.$_REQUEST['key'].'&project_id='.$_REQUEST['project_id'].'&statement_id='.$_REQUEST['statement_id'];
	#include('../webActions.php');

	if(!$statement_info['view']) {
		echo "User cannot view this statement";
		exit;
	}
	$grid = render_elements(array('0'=>$statement_info), $acl, $resource_acl, $_REQUEST['project_id'], $owner_project_id);
	$tpl->set_var('data_grid_statements', '');
	$tpl->set_block('statement', 'list_statements', '_statement');
	$tpl->parse('_output', 'list_statements', True);
	$tpl->pfp('out','_output');

	function render_resource($handle, $inserting_resource) {
		$handle->set_var('id', getResourceID($inserting_resource));
		$handle->set_var('entity', $inserting_resource['entity']);
		$handle->set_var('created_on', substr($inserting_resource['created_on'], 0, 19));
		$handle->set_var('created_by', find_user_loginID($inserting_resource['created_by']));
		$handle->set_var('notes', $inserting_resource['notes']);
	}	

	function getResourceID($resource) {
		global $resource_info, $project_info;
		$acl = find_user_acl($_SESSION['user']['account_id'], $_REQUEST['project_id'], $_SESSION['db']);
		$result =  str_pad($resource['resource_id'], 6, '0', STR_PAD_LEFT).'<br />';
		if($_SESSION['user']['account_id'] == $project_info['owner'] || $acl =='3' ||$acl =='2' ||$resource['created_by']==$_SESSION['user']['account_id']) {
			# $result .= $acl;
			$result .= getEditLink($resource['resource_id']). '&nbsp;&nbsp;'. getDeleteLink($resource['resource_id']);
		}
		return $result;
	}
                                                                                                  
	function getEditLink($resource) {
		$res = '<a href="#" onClick="window.open(\'../resource/editresource.php{get_proj_id}{get_res_id}&resource_id='.$resource.'\', \'editresource_'.$resource.'\', \'width=600, height=500, location=no, titlebar=no, scrollbars=yes, resizable=yes\')" title="Edit resource '.$resource.' )">Edit</a>';
		return $res;
	}
                                                                                                  
	function getDeleteLink($resource) {
		$res = '<a href="#" onClick="window.open(\'../resource/deleteresource.php{get_proj_id}{get_res_id}&resource_id='.$resource.'\', \'deleteresource_'.$resource.'\', \'width=600, height=500, location=no, titlebar=no, scrollbars=yes, resizable=yes\')" title="Delete resource '.$resource.'">Delete</a>';
		return $res;
	}
	#function printCreatedOn($params) {
	#	extract($params);
	#	return substr($record['created_on'], 0, 19);
	#}
	#function printEntity($params) {
	#	extract($params);
	#	return $record['entity'];
	#}
	#function printResourceNotes($params) {
	#	extract($params);
	#	return $record['notes'];
	#}
	
	function render_statements($inserting_resource, $acl, $resource_acl, $project_id, $owner_project_id) {
		$_SESSION['current_color']='0';
		$_SESSION['previous_verb']='';

		#$rules = get_all_rules($inserting_resource['entity'], $project_id, $owner_project_id);
		#$rules = get_all_rules($inserting_resource['entity'], $project_id, $owner_project_id);
		$rules = list_shared_rules(array('subject'=>$inserting_resource['entity'], 'object'=>'!=UID', 'project_id'=>$project_id, 'db'=>$_SESSION['db']));
		
		if(count($rules) > 0) {
			$stats ='';
			$index = 1;
			foreach($rules as $i=>$value) {
				$subject = $rules[$i]['subject'];
				$verb = $rules[$i]['verb'];
				$object = $rules[$i]['object'];
				$rule_id = $rules[$i]['rule_id'];
				$rule_notes = preg_replace('/\(.*\)/', '', $rules[$i]['notes']);
				//$rule_notes = $rules[$i]['notes'];
				$stats .= sprintf("\n%s\n", '<table width="100%" border="0"><tr bgcolor="lightyellow"><td colspan="2">');	
				if($verb=='has UID' && $object =='UID') {
					$stats .= sprintf("%s\n", ($index+$i).'. <font color="brown" size="2"> [ '.$subject.' | '.$verb.' | '.$object.' ]</font><br />&nbsp;&nbsp;&nbsp;&nbsp;<font color="dodgerblue">'.$rules[$i]['notes'].'</font>');	
				} else {
					$stats .= sprintf("%s\n", ($index+$i).'. '.printVerbRenderStats($verb).' | <font size=4><b>'.$object.'</b></font> </td></tr><tr><td>&nbsp;&nbsp;<font size-=2 color=gray>'.$rule_notes.'</font></td><td align="right">');
				}
				$stats .= sprintf("%s\n",' 		 <input type="button" value="Add"  onClick="window.open(\'insertstatement.php{get_proj_id}{get_res_id}&resource_id='.$inserting_resource['resource_id'].'&rule_id='.$rule_id.'\', \'_blank\', \'width=600, height=600, location=no, titlebar=no, scrollbars=yes, resizable=yes\')">');	
				$stats .= sprintf("%s\n", '	</td></tr>');
				$exist_stats = get_exist_stats($inserting_resource['resource_id'], $rule_id);
				
				$stat ='';
				if(count($exist_stats) > 0) {
					$stat .= sprintf("%s\n", '	<tr><td colspan="2"><font color="gray" size="2">');
					$stat .= render_substatements_without_grid($exist_stats, $acl); 
					$stat .= sprintf("%s\n", '	</font></td></tr>');
				}
				$stats .= $stat;
				$stats .= sprintf("%s\n", '	<tr><td colspan="2"><br>');
				$stats .= sprintf("%s\n", '	</td></tr>');
				$stats .=sprintf("%s\n", '</table>');	
			}
		}
		return $stats;
	}

	function render_substatements_without_grid($exist_stats, $acl) {
		$substats = '<table width="100%" border="0">';
		foreach($exist_stats as $i => $value) {
			#When the object is a a resource, create a button
			$O = array('project_id'=>$exist_stats[$i]['project_id'], 'rule_id'=>$exist_stats[$i]['rule_id'], 'db'=>$_SESSION['db']);
			if(object_is_resource($O)) {
				if (is_numeric($exist_stats[$i]['value'])) {
					$resource_id=$exist_stats[$i]['value'];			
				} else {
					$resource_id='';
				}
				if($resource_id !='' && $exist_stats[$i]['file_name'] =='') {
					$db = $_SESSION['db'];
					$sql = "select notes from s3db_resource where resource_id='".$resource_id."'";
					$db->query($sql, __LINE__, __FILE__);
					while($db->next_record()) {
						$instances = Array(
										'resource_id'=>$db->f('resource_id'),
										'notes'=>$db->f('notes'),
									);
					}
					$resource_class_id = get_resource_class_id($resource_id);
					$substats .= '<tr><td colspan="6"><input type="button" size="10" value="'.$instances['notes'].'" onClick="window.open(\'index.php{get_proj_id}&entity_id='.$resource_class_id.'&resource_id='.$resource_id.'\', \'_blank\', \'width=700, height=600, location=no, titlebar=no, scrollbars=yes, resizable=yes\')"><font size = 1 color = navy> (Id '.str_pad($resource_id, 6, '0', STR_PAD_LEFT).')</font></td></tr>';
				} elseif($exist_stats[$i]['file_name'] !='') {
					$statement_id = $exist_stats[$i]['statement_id'];
					$project_id = $exist_stats[$i]['project_id'];
					$file_name = $exist_stats[$i]['file_name'];
					$substats .= "<tr><td colspan=6><font color=dodgerblue size=3><b>File: <a href=download.php{get_proj_id}{get_res_id}&project_id=$project_id&resource_id=".$_REQUEST['resource_id']."&rule_id=".$exist_stats[$i]['rule_id']."&statement_id=$statement_id>$file_name<a/></b></font></td></tr>";
				}
			}
			$O = array('project_id'=>$exist_stats[$i]['project_id'], 'rule_id'=>$exist_stats[$i]['rule_id'], 'db'=>$_SESSION['db']);
			if(!object_is_resource($O)) {
				if($exist_stats[$i]['file_name'] =='') {
					if(ereg("^Hyperlink:", urldecode($exist_stats[$i]['value']))) {
						$substats .= "<tr><td colspan=6><font color=dodgerblue size=3><b>".urldecode($exist_stats[$i]['value'])."</b></font></td></tr>";
					} else {
						$substats .= "<tr><td colspan=6><font color=dodgerblue size=3><b>".urldecode($exist_stats[$i]['value'])."</b></font></td></tr>";
					}
					$substats .= "<tr><td width=50%>&nbsp;</td><td width=15%><font color=gray size=1>".substr($exist_stats[$i]['created_on'], 0, 19)."</font></td><td width=15%><font color=gray size=1>".find_user_loginID($exist_stats[$i]['created_by'])."</font></td><td width=10%><font color=gray size=1>".$exist_stats[$i]['notes']."</font></td><td width=10% align=right>".printActionLink($exist_stats[$i]['statement_id'], $acl, $exist_stats[$i]['created_by'])."</td></tr>";
				} elseif($exist_stats[$i]['file_name'] !='') {
					$statement_id = $exist_stats[$i]['statement_id'];
					$project_id = $exist_stats[$i]['project_id'];
					$file_name = $exist_stats[$i]['file_name'];
					$substats .= "<tr><td colspan=6><font color=dodgerblue size=3><b>File: <a href=download.php{get_proj_id}{get_res_id}&project_id=$project_id&resource_id=".$_REQUEST['resource_id']."&rule_id=".$exist_stats[$i]['rule_id']."&statement_id=$statement_id>$file_name<a/></b></font></td></tr>";
					$substats .= "<tr><td width=50%>&nbsp;</td><td width=15%><font color=gray size=1>".substr($exist_stats[$i]['created_on'], 0, 19)."</font></td><td width=15%><font color=gray size=1>".find_user_loginID($exist_stats[$i]['created_by'])."</font></td><td width=10%><font color=gray size=1>".$exist_stats[$i]['notes']."</font></td><td width=10% align=right>".printActionLink($exist_stats[$i]['statement_id'], $acl, $exist_stats[$i]['created_by'])."</td></tr>";
				}
			} else {
	            $substats .= "<tr><td width=50%>&nbsp;</td><td width=15%><font color=gray size=1>".substr($exist_stats[$i]['created_on'], 0, 19)."</font></td><td width=15%><font color=gray size=1>".find_user_loginID($exist_stats[$i]['created_by'])."</font></td><td width=10%><font color=gray size=1>".$exist_stats[$i]['notes']."</font></td><td width=10% align=right>".printActionLink1($exist_stats[$i]['statement_id'], $acl, $exist_stats[$i]['created_by'])."</td></tr>";
			}
		}
		$substats .= '</table>';
		return $substats;
	}
	
	function printVerbRenderStats($verb) {
		if($_SESSION['previous_verb'] =='') {
			$_SESSION['previous_verb'] = $verb;
		} elseif(!strsimilar($_SESSION['previous_verb'], $verb)) {
			$_SESSION['previous_verb'] = $verb;
			$_SESSION['current_color'] = intVal($_SESSION['current_color']) + 1;
		}
		switch(intVal($_SESSION['current_color'])%3) {
			case 0:
				return '<font color="red" size=4>'.$verb.'</font>';
				break;
			case 1:
				return '<font color="green" size=4>'.$verb.'</font>';
				break;
			case 2:
				return '<font color="blue" size=4>'.$verb.'</font>';
				break;
		}
	}
	
	function render_substatements($datasource, $order, $direction) {
		$orderBy = $order;
		$dir = $direction;

		# Create the DataGrid, bind it's Data Source
		$dg =& new Structures_DataGrid(); // Display 20 per page
		$dg->bind($datasource);
		
		# Define DataGrid's columns
		$dg->addColumn(new Structures_DataGrid_Column(null, null, null, array('width'=>'50%', 'align'=>'left', 'valign'=>'top'), null, 'printValue()'));
		$dg->addColumn(new Structures_DataGrid_Column(null, null, null, array('width'=>'15%', 'align'=>'left', 'valign'=>'middle'), null, 'printCreatedOn()'));
		$dg->addColumn(new Structures_DataGrid_Column(null, null, null, array('width'=>'15%', 'align'=>'left', 'valign'=>'middle'), null, 'printCreatedBy()'));
		$dg->addColumn(new Structures_DataGrid_Column(null, null, null, array('width'=>'10%', 'align'=>'left', 'valign'=>'middle'), null, 'printStatementNotes()'));
		$dg->addColumn(new Structures_DataGrid_Column(null, null, null, array('align'=>'right', 'valign'=>'top'), null, 'printAction()'));
		# Define the Look and Feel
		$dg->renderer->setUseHeader(false);
		#$dg->renderer->setTableHeaderAttributes(array('bgcolor'=>'#FFCCFF'));
		$dg->renderer->setTableEvenRowAttributes(array('bgcolor'=>'#FFFFFF', 'valign'=>'top'));
		$dg->renderer->setTableOddRowAttributes(array('bgcolor'=>'#EEEEEE', 'valign'=>'top'));
		$dg->renderer->setTableAttribute('width', '100%');
		$dg->renderer->setTableAttribute('align', 'left');
		$dg->renderer->setTableAttribute('valign', 'top');
		$dg->renderer->setTableAttribute('border', '0px');
		$dg->renderer->setTableAttribute('cellspacing', '0');
		$dg->renderer->setTableAttribute('cellpadding', '2');
		$dg->renderer->setTableAttribute('class', 'datagrid');
		#$dg->renderer->setOddRowAttribute('valign', 'top');
		#$dg->renderer->setEvenRowAttribute('valign', 'top');
		$dg->renderer->sortIconASC = '&uarr;';
		$dg->renderer->sortIconDESC = '&darr;';
		$htmloutput =  $dg->render();
		#$htmloutput .= $dg->renderer->getPaging();
		return $htmloutput;
	}
	
	function printAction($params, $acl, $resource_acl) {
		global $resource_info, $project_info;
		extract($params);
		$rule = get_rule($record['rule_id']);	
		#$acl = find_user_acl($_SESSION['user']['account_id'], $_REQUEST['project_id']);
		if($acl =='3' ||$acl =='2' ||$record['created_by']==$_SESSION['user']['account_id']) {
			if($rule['verb'] == 'has UID' && $rule['object'] == 'UID') {
				$result ='';
				//$result = printDeleteStatementLink($params);
			}
			#else
			$result = printEditStatementLink($params). '<br>'. printDeleteStatementLink($params);
		} else {
			$result ='No Permission';
		}
		return $result; 
	}
	
	function printAddButton($params) {
		extract($params);
		if ($resource_acl!=-1) {
			$res = '<input type="button" value="Add"  onClick="window.open(\'insertstatement.php{get_proj_id}{get_res_id}&resource_id='.$inserting_resource['resource_id'].'&rule_id='.$rule_id.'\', \'_blank\', \'width=600, height=500, location=no, titlebar=no, scrollbars=yes, resizable=yes\')">';
		}
		return $res;
	}

	function printActionLink($statement_id, $acl, $owner) {	
		if($acl ==2 || $acl==3 || $owner == $_SESSION['user']['account_id']) {
			$res = '<a href="#" onClick="window.open(\'editstatement.php{get_proj_id}{get_res_id}&statement_id='.$statement_id.'\', \'editstatement_'.$statement_id.'\', \'width=600, height=600, location=no, titlebar=no, scrollbars=yes, resizable=yes\')" title="Edit statement '.$statement_id.'">Edit</a>';
			$res .='<br>';
			$res .= '<a href="#" onClick="window.open(\'deletestatement.php{get_proj_id}{get_res_id}&statement_id='.$statement_id.'\', \'deletestatement_'.$statement_id.'\', \'width=600, height=600, location=no, titlebar=no, scrollbars=yes, resizable=yes\')" title="Delete statement '.$statement_id.'">Delete</a>';
		}
		return $res;	
	}
	
	function printActionLink1($statement_id, $acl, $owner) {
		if($acl=='2' || $acl=='3') {
			$res = '<a href="#" onClick="window.open(\'editstatement4resource.php{get_proj_id}{get_res_id}&statement_id='.$statement_id.'\', \'editstatement_'.$statement_id.'\', \'width=600, height=600, location=no, titlebar=no, scrollbars=yes, resizable=yes\')" title="Edit statement '.$statement_id.'">Edit</a>';
			$res .='<br>';
			$res .= '<a href="#" onClick="window.open(\'deletestatement.php{get_proj_id}{get_res_id}&statement_id='.$statement_id.'\', \'deletestatement_'.$statement_id.'\', \'width=600, height=600, location=no, titlebar=no, scrollbars=yes, resizable=yes\')" title="Delete statement '.$statement_id.'">Delete</a>';
		}
		return $res;	
	}

	function printEditStatementLink($params) {
		extract($params);
		$res = '<a href="#" onClick="window.open(\'editstatement.php{get_proj_id}{get_res_id}&statement_id='.$record['statement_id'].'\', \'editstatement_'.$record['statement_id'].'\', \'width=600, height=600, location=no, titlebar=no, scrollbars=yes, resizable=yes\')" title="Edit statement '.$record['statement_id'].'">Edit</a>';
		return $res;
	}

	function printDeleteStatementLink($params) {
		extract($params);
		$res = '<a href="#" onClick="window.open(\'deletestatement.php{get_proj_id}{get_res_id}&statement_id='.$record['statement_id'].'\', \'deletestatement_'.$record['statement_id'].'\', \'width=600, height=600, location=no, titlebar=no, scrollbars=yes, resizable=yes\')" title="Delete statement '.$record['statement_id'].'">Delete</a>';
		return $res;
	}
	
	function printValue($params) {
		extract($params);
		if(object_is_resource(array('project_id'=>$record['project_id'], 'rule_id'=>$record['rule_id'], 'db'=>$_SESSION['db']))) {
			$resource_id = urldecode($record['value']);
		} else {
			$resource_id = '';
		}
		if($resource_id !='') {
			return '<input type="button" size="10" value="'.str_pad($resource_id, 6, '0', STR_PAD_LEFT).'" onClick="window.open(\'index.php{get_proj_id}{get_res_id}&resource_id='.$resource_id.'\', \'_blank\', \'width=700, height=600, location=no, titlebar=no, scrollbars=yes, resizable=yes\')">'; 
		} else {
			return "<font color=dodgerblue size=3><b>".urldecode($record['value'])."</b></font>";
		}
	}
	
	#function printCreatedBy($params) {
	#	extract($params);
	#	return find_user_loginID($record['created_by']);
	#}

	function printStatementNotes($params) {
		extract($params);
		return $record['notes'];
	}
?>
