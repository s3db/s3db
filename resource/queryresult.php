<?php
	#queryresource.php is a query interface for the rules of a specific class. To be adapted to queries on any class. If called with "listall", it will return all the instances of a class
	#Includes links to edit and delete resource, as well as edit rules
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

	#Universal variables
	$class_id = ($_REQUEST['collection_id']!='')?$_REQUEST['collection_id']:$_REQUEST['class_id'];
 
	if($class_id) {
		$collection_info = URIinfo('C'.$class_id, $user_id, $key, $db);
		if(!$collection_info['view']) {
			echo "User does not have access to view or query this collection";
			exit;
		}
	}

	#$class_info = URI('C'.$class_id, $user_id,$db);
	$uid = 'C'.$class_id;
	$uni = compact('db', 'user_id','key', 'SQLextra', 'class_info', 'dbstruct');

	#include all the javascript functions for the menus...
	include('../s3style.php');

	#and the short menu for the resource script
	include('../action.header.php');
	
	#define a few usefull html vars
	if($_GET['page']!='' ) {
		$current_page = $_GET['page'];
	} else {
		$current_page = 1;
	}

	#by default, display 50
	if($_GET['num_per_page']=='') { 
		$num_per_page = '50'; 
	} else {
		$num_per_page = $_GET['num_per_page'];
	}
	$per_page = array('50', '100', '200', '400', '600', '1000');
	$selected[$num_per_page] = 'selected';

	#find the rules in the class, these will be usefull for the query
	if($_SESSION[$uid]['rules']=='') {
		$s3ql=compact('user_id','db');
		$s3ql['from'] = 'rules';
		if($class_id!='') {
			$s3ql['where']['subject_id'] = $class_id;
		}
		#if($_REQUEST['project_id']!='')
		#$s3ql['where']['project_id'] = $_REQUEST['project_id'];
		$s3ql['where']['object']='!="UID"';
	
		if($_REQUEST['orderBy']) {
			$s3ql['order_by'] = $_REQUEST['orderBy'].' '.$_REQUEST['direction'];
		}
		$rules = S3QLaction($s3ql);
	} else {
		$rules = $_SESSION[$uid]['rules'];
	}
	
	if(is_array($rules)) {
		#find out whter the object of this rule is a class and retrieve the rule_id in that case
		#$rules = include_all_class_id(compact('rules', 'project_id', 'db'));
		#make rule_id the index for the array
		$rule_ids = grab_id('rule', $rules);
		$rules = array_combine($rule_ids, array_values($rules));
	}

	#action on buttons
	if($_REQUEST['listall']=='yes') {
		#find all instances in this class
		$limit = ($_REQUEST['num_per_page']!='')?$_REQUEST['num_per_page']:'50';
		$page = ($_REQUEST['page']=='')?'1':$_REQUEST['page'];
		$offset = ($page-1)*$limit;
		
		$s3ql=compact('user_id','db');
		$s3ql['from'] = 'items';
		$s3ql['where']['collection_id'] = $class_id;
		#$s3ql['where']['project_id'] = $project_id;
		if($_REQUEST['orderBy']!='')
		$s3ql['order_by'] = $_REQUEST['orderBy'].' '.$_REQUEST['direction'];
		#$s3ql['limit']=$limit;
		#$s3ql['offset']=$offset;
		$instances =S3QLaction($s3ql);
	} else {
		#find the rules in the class, these will be usefull for the query
		$S = compact('db', 'project_id', 'rule_id', 'resource_info', 'project_info', 'user_id');
		$orderBy = $_REQUEST['orderBy'].' '.$_REQUEST['direction'];	
		$instances = search_resource(compact('rules', 'db', 'orderBy'));
		$sqlquery = $instances['sqlquery'];
		if(is_array($instances)) {
			$instances = array_diff_key($instances, array('sqlquery'=>''));
			$instances = array_filter($instances);
		}
	}
	
	if(is_array($instances) && !empty($instances)) {
		$_SESSION['queryresult']='';
		#interpret user_acl on each instance
		$instances = replace_created_by($instances, $db);
			
		#now find the list of rules, make rule_id the key and append the rule info into the statement info
		if($_REQUEST['statements']!='' || count($instances)<=50) {
			$instances = include_statements(compact('rules', 'instances', 'user_id', 'db', 'project_id'));
		} else { 
			$instances = includeStatementLink($instances);
		}

		#$instances = include_data_acl(compact('instances', 'user_id', 'db'));
		#$instances = include_statements(compact('db', 'user_id', 'rules', 'instances'));
		$_SESSION[$user_id]['instances'][$class_id] = $instances;
		$_SESSION['queryresult'] =  $instances;
		$datagrid = render_elements($instances, $acl, array('ResourceID', 'ResourceNotes', 'Form', 'Statements', 'CreatedOn','Owner'), 'statements');
	} else {
		 $message_report .= 'Your query returned no results.';
	}

	#print the form
	#include('queryResultForm.php');
	#exit;

	if($_REQUEST['main_resID']!='') {
		$main_class_info = s3info('class', $_REQUEST['main_resID'], $db);
		$action['query_page'] = str_replace('&class_id='.$_REQUEST['class_id'],'&class_id='.$_REQUEST['main_resID'],$action['query_page']);
		echo '<input type="button" value="Send result to '.$main_class_info['entity'].'" onclick="window.location=\''.$action['query_page'].'\'">';
	}

	if($_SESSION['query_result']!='' && $_GET['action'] !='listall') {
		$entity = $class_info['entity'];
		#Do a little trick to order the array by the intended order, by naming the key the same as the element by which we are to sort and then sorting by key
		if($sortorder!='') {
			foreach($_SESSION['query_result'] as $key => $instance) {
				$unsortedinstances[strtolower($instance[$sortorder]).$key] = $instance;
			}
			if($direction=='ASC' && is_array($unsortedinstances)) {
				ksort($unsortedinstances);
			} elseif($direction=='DESC' && is_array($unsortedinstances)) {
				krsort($unsortedinstances);
			}
			if(is_array($unsortedinstances)) {
				foreach($unsortedinstances as $key => $instance) {
					$sortedinstances[] = $instance; 
				}
			}
			$instances = $sortedinstances;
		} else {
			$instances = $_SESSION['query_result'];
		}
		if($_SESSION['sqlquery'] == '') {
			$_SESSION['sqlquery'] = array_pop($instances);
		}
		$_SESSION['for_summary'] =$instances; 
		$tpl->set_var('back', '[<a href="javascript:history.go(-1)"><b> Back </b></a>]');
		if(count($instances) > 0) {
			$instances = include_data_acl(compact('instances', 'user_id', 'db'));
			#if($_REQUEST['listall']!='yes')
			$instances = include_statements($instances, $user_id, $db, $project_id);
			
			$tpl->set_var('data_grid_instances', render_elements($instances, $acl, array('ResourceID', 'ResourceNotes', 'Statements', 'CreatedOn','Owner'), 'statements'));
			
			// find the name associated with main entity (only in cases where send result button is active)
			$db = $_SESSION['db'];
			$sql = "select entity from s3db_resource where resource_id='".$class_info['main_resID']."' and project_id='".$_REQUEST['project_id']."' and iid ='0'";
			$db->query($sql, __LINE__, __FILE__);
			while($db->next_record()) {
				$main_entity = Array('entity'=>$db->f('entity'));
			}
			
			//Lena's - create the send result button, which will close the window and reload the main table UID's to the main_table
			//	print_r ($main_entity);
			if(!empty($class_info['main_resID'])) {
				$tpl->set_var('send_query_button', '<input type="button" value="Send result to '.$main_entity['entity'].'" onclick="window.location=\'queryresource_main_page.php{get_proj_id}&entity_id='.$class_info['main_resID'].'\'">');

				##This will create a session that will hold the result from the present query, in case the user is coming from another resource
				$entity_id = $_REQUEST['entity_id'];
				$_SESSION['result_list'][$entity_id]='';
				$main_resource_id = $class_info['main_resID'];
				$main_rule = $resource_info['main_rule'];
			
				for($result=0; $result<count($_SESSION['query_result']); $result++) {
					$result_UID_list .= $_SESSION['query_result'][$result]['resource_id'];
					if ($_SESSION['query_result'][$result]['resource_id'] !='' && $_SESSION['query_result'][$result+1]['resource_id'] !='') { $result_UID_list.="|"; }
				}
				$_SESSION['result_list'][$main_resource_id][$main_rule] = $result_UID_list;
			}
		} else {
			$query_result='No match found';
		}
		$_SESSION['query_result'] = $instances;
		//Header('Location: queryresource.php');					
	}
	
	function includeStatementLink($instances) {
		for ($i=0; $i < count($instances); $i++) {
			$instances[$i]['stats'] = '<a href="'.$GLOBALS['action']['item'].'&item_id='.$instances[$i]['item_id'].'" target="_blank">Open Statements</a>';
		} 
		return ($instances);
	}
?>
<body onload="kill_me()">
	<table class="resource_list" width="100%" align="center" border="0">
		<tr>
			<td>
				<table class="query_resource" width="100%" border="0">
					<tr>
						<td class="" colspan="2">
							[<a href="javascript:history.go(-1)"><i>Back</i></a>]&nbsp;&nbsp;&nbsp;
<?php
	echo '<input type="button" name="update" value="Update from downloaded File" onClick="window.open(\''.$action['excelimport'].'\', \'_blank\', \'width=700, height=700, location=no, titlebar=no, scrollbars=yes, resizable=yes\')" onMouseOver="popup(\'You may update data in batched by downloading a version of this data as Excel and Reimporting it using this Tool.\', \'yellow\')" onmouseout="kill()">&nbsp;&nbsp;&nbsp;';
	echo '<input type="button" name="export" value="Export these '.((count($instances)<$limit)?count($instances):$limit).' results as Excel" onClick="window.location=\''.$action['excelexport'].'&num_per_page='.$num_per_page.'&current_page='.$current_page.'\'">&nbsp;&nbsp;&nbsp;';
	echo '<input type="button" name="view" value="View these '.((count($instances)<$limit)?count($instances):$limit).' results as Table" onClick="window.open(\''.$action['view'].'&color=on&format=html&listall='.$_REQUEST['listall'].'&num_per_page='.$num_per_page.'&current_page='.$current_page.'\', \'_blank\', \'width=700, height=700, location=no, titlebar=no, scrollbars=yes, resizable=yes\')">';
?>
							<br />
						</td>
						<td style="font-size: smaller" align="right">
							Number of Results Per Page
							<select name="num_per_page" onChange="window.location=this.options[this.selectedIndex].value">
<?php
	foreach($per_page as $num) {
		echo '<option value="'.$action['queryresult'].'&listall=yes&num_per_page='.$num.'" '.$selected[$num].'>'.$num.'</option>';
	}
?>
							</select>
						</td>
					</tr>
					<tr>
						<td class="nav_menu" colspan="3"><hr size="2" align="center" color="dodgerblue"></hr></td>
					</tr>
					<tr>
						<td colspan="2"><b>Total Matched Records: </b><?php echo count($instances)?></td>
						<td align="right"></td>
					</tr>
					<tr>
						<td class="nav_menu" colspan="3"><hr size="2" align="center" color="dodgerblue"></hr></td>
					</tr>
					<tr align="center">
						<td colspan="3">
							<?php echo $datagrid; ?>
						</td>
					</tr>
					<tr align="center">
						<td colspan="3">
							<?php echo $message_report; ?>
						</td>
					</tr>
				</table>
			</td>
		</tr>
	</table>
</body>
