<?php
	#instance.php displays all statements in a certain instance and links to create more
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
	if(file_exists('config.inc.php')) {
		include('config.inc.php');
	} else {
		Header('Location: http://'.$def.'/s3db/');
		exit;
	}
	
	#just to know where we are...
	$thisScript = end(explode('/', $_SERVER['SCRIPT_FILENAME'])).'?'.$_SERVER['argv'][0];
	$key = $_GET['key'];

	#Get the key, send it to check validity
	include_once('core.header.php');

	if($key) {
		$user_id = get_entry('access_keys', 'account_id', 'key_id', $key, $db);
	} else {
		$user_id = $_SESSION['user']['account_id'];
	}

	#Universal variables
	$instance_id = ($_REQUEST['item_id']!='')?$_REQUEST['item_id']:$_REQUEST['instance_id'];
	if($instance_id) {
		$instance_info = URIinfo('I'.$instance_id, $user_id, $key, $db);
	}
	$class_id = ($_REQUEST['class_id']!='')?$_REQUEST['class_id']:(($_REQUEST['collection_id']!='')?$_REQUEST['collection_id']:$instance_info['class_id']);

	if($instance_id=='') {
		echo "Please provide an item_id";
		exit;
	} elseif(!$instance_info['view']) {
		echo ('User does not have permission in this instance');
		exit;
	} else {
		include('action.header.php'); #add the instance header and the insertall
		echo '<table width="100%">';
		echo '<tr><td class="nav_menu">';
		if($instance_info['add_data']) {
			echo '<br /><br />[ <a href="#" onClick="window.location=\''.$action['instanceform'].'\'"> Add Statements </a>]';
		}
	}
	#include the header for the instance
	include('resource/instance.header.php');
	
	#if there are any rules, print a grid with the rules header and the button to add a statement
	#if (ereg('(1|2|3)', $projectAcl)) {#users that do not have access on the project and do not provide a project_id cannot see rules. Otherwise, there is no way of knowing which rules they were given access to
	$s3ql=compact('user_id','db');
	$s3ql['from'] = 'rules';
	$s3ql['where']['subject_id'] = $instance_info['class_id'];
	#$s3ql['where']['object'] = "!='UID'";
	$rules = S3QLaction($s3ql);
	$I = compact('instance_info', 'db', 'user_id', 'rules','project_id');
	#}
	
	if(is_array($rules)) {
		echo render_statements($I);	#print the statements together with the rules.
	}			

	function render_statements($I) {
		$action = $GLOBALS['action']; #all the possible links were separated ina script that gets always included
		extract($I);
		$_SESSION['current_color']='0';
        $_SESSION['previous_verb']='';

		#display all the rules in this class where the user has permission
		$s3ql=compact('user_id','db');
		$s3ql['select']='*';
		$s3ql['from']='statements';
		$s3ql['where']['instance_id']=$instance_info['instance_id'];
		$statements = S3QLaction($s3ql);

		#divide them by rules
		if(is_array($statements)) {
			foreach ($statements as $stat_info) {
				$stats_per_rule[$stat_info['rule_id']][$stat_info['statement_id']] = $stat_info;
			}
			if(is_array($rules)) {
				$rule_ids = array_map('grab_rule_id', $rules);
				$tRules = array_combine($rule_ids, $rules);
			}

			if(is_array($stats_per_rule) && is_array($tRules)) {
				$stats ='';
				$index = 1;
				foreach($stats_per_rule as $rule_id=>$exist_stats) {
					if($tRules[$rule_id]['object']!='UID' && $tRules[$rule_id]['verb']!='has UID') {
						$subject = $tRules[$rule_id]['subject'];
						$verb = $tRules[$rule_id]['verb'];
						$object = $tRules[$rule_id]['object'];
						$rule_id = $tRules[$rule_id]['rule_id'];
						#$rule_notes = preg_replace('/\(.*\)/', '', $rules[$i]['notes']);
						$rule_notes = $tRules[$rule_id]['notes'];
				
						$stats .= sprintf("\n%s\n", '<table width="100%" border="0"><tr bgcolor="lightyellow"><td colspan="2">');	
						$stats .= sprintf("%s\n", ($index++).'. '.printVerbinColor($verb).' | <font size=4><b>'.$object.'</b></font> (R'.$rule_id.') </td></tr><tr><td>&nbsp;&nbsp;<font size-=2>'.$rule_notes.'</font></td><td align="right">');
						if($tRules[$rule_id]['add_data']) {
							$stats .= sprintf("%s\n",'<input type="button" value="Add"  onClick="window.open(\''.$action['insertstatement'].'&rule_id='.$rule_id.'\', \'_blank\', \'width=600, height=500, location=no, titlebar=no, scrollbars=yes, resizable=yes\')">');
						}
						$stats .= sprintf("%s\n", '	</td></tr>');

						$stat ='';
						$stat .= sprintf("%s\n", '	<tr><td colspan="2" style="color: navy; font-size: normal">');
						if(is_array($exist_stats)) {
							$stat .= render_substatements_without_grid($exist_stats, $db);
							//$stat .= render_substatements($exist_stats, 'value', 'DESC'); 
						}
						$stat .= sprintf("%s\n", '	</td></tr>');
				
						$stats .= $stat;
						$stats .= sprintf("%s\n", '     <tr><td colspan="2"><br>');
		                $stats .= sprintf("%s\n", '     </td></tr>');
						$stats .= sprintf("%s\n", '</table>');	
					}
				}
			}
		}
		return $stats;	
	}
	
	function render_substatements_without_grid($exist_stats, $db) {
		$action=$GLOBALS['action'];
		$substats = '<table width="100%" border="0">';
		if(is_array($exist_stats)) {
			foreach($exist_stats as $i => $value) {
				#if(object_is_resource())
				if($exist_stats[$i]['file_name']=='') {
					if($exist_stats[$i]['object_id']!='') {		#if the value is not a file, put a button
						$substats .= '<tr><td colspan="6"><input type="button" size="10" value="'.$exist_stats[$i]['object_notes'].'" onClick="window.open(\''.$action['item'].'&item_id='.$exist_stats[$i]['value'].'\', \'_blank\', \'width=700, height=600, location=no, titlebar=no, scrollbars=yes, resizable=yes\')">&nbsp;&nbsp;<font size=1 color=navy>  (Id '.str_pad($exist_stats[$i]['value'], 6, '0', STR_PAD_LEFT).')</font></td></tr>';
					} else {
						$substats .= "<tr><td colspan=6><font color=dodgerblue size=3><b>".html_entity_decode($exist_stats[$i]['value'])."</b></font></td></tr>";
					}
				} else {
					$substats .= "<tr><td colspan=6><font color=dodgerblue size=3><b>File: <a href=".$action['download']."&statement_id=".$exist_stats[$i]['statement_id'].">".$exist_stats[$i]['file_name'].(($exist_stats[$i]['file_size']!='')?'('.ceil($exist_stats[$i]['file_size']/1024).' kb)':'')."<a/></b></font></td></tr>";
				}
				$substats .= "<tr><td width=50%>&nbsp;</td><td width=15%><font color=gray size=1>".substr($exist_stats[$i]['created_on'], 0, 19)."</font></td><td width=15%>";
				if($exist_stats[$i]['change']) {
					$action_link =  printStatementActionLink($exist_stats[$i]['statement_id']);
					$substats .= "<font color=gray size=1>".find_user_loginID(array('account_id'=>$exist_stats[$i]['created_by'], 'db'=>$db))."</font></td><td width=10%><font color=gray size=1>".$exist_stats[$i]['notes']."</font></td><td width=10% align=right>".$action_link."</td></tr>";
				}
			}
			$substats .= '</table>';
			return $substats;
		}
	}
?>
