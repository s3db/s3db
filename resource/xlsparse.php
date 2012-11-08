<?php
	#xlsparse.php accepts a tab delimited file formatted according to a rule template and writes the retreived statements and instances to s3db
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
	$a = set_time_limit(0);
	ini_set('memory_limit','3000M');

	$key = $_GET['key'];
	include_once('../core.header.php');
	#Universal variables
	$class_id = $_REQUEST['class_id'];
	$resource_info = URIinfo('C'.$class_id, $user_id, $key, $db);
	$uni = compact('db', 'user_id');

	if($class_id=='' || !is_array($resource_info)) {
		echo "Please specify a valid class_id";
		exit;
	} elseif(!$resource_info['add_data']) {
		echo "User does not have permisson on this class!";
		exit;
	} else {
		$project_id = ($_REQUEST['project_id']!='')?$_REQUEST['project_id']:$resource_info['project_id'];

		//run the form
		#include all the javascript functions for the menus...
		include('../S3DBjavascript.php');
	
		#and the short menu for the resource script
		include('../action.header.php');

		echo '
			<br /><br />
			<table width="100%" border="0">
				<tr bgcolor="#80BBFF">
					<td colspan="9" align="center">Import '.$resource_info['entity'].' from file</td>
				</tr>
				<tr>
					<td><BR></td>
				</tr>
				<tr>
					<td bgcolor="#FFFFCC">1. Select file to import</td>
				</tr>
				<tr>
					<td>
						<form enctype="multipart/form-data" name="importform" action="'.$action['excelimport'].'" method="POST">
							<input type="hidden" name="MAX_FILE_SIZE" value="2000000">
							<input name="import" type="file"><BR><BR>	
							<input type="submit" name="submitfile" value="Submit File">
					</td>
				</tr>
				<tr>
					<td><br /></td>
				</tr>
				<tr>
					<td>
						<p><font color="#FF3300">Please Note</font>: 
						<ul>
							<li>Import from excel works with <B>ONE COLLECTION</B> at a time - use the rule template to indicate the rules where data should be imported. Rules should all have the same subject.</li>
							<li>Importing will only work with <b>tab separated files</b>. The values on the first column must be <i><b>UID of the item</b></i>. PLEASE LEAVE THESE EMPTY if you are inserting new data.<br /></li>
							<li>Values on the second columns will be used as the <b>Label</b> of the item. <br /></li>
							<li>The <b>Header must contain 3 lines</b>, data will go on the forth line. The 3 lines of the header must contain: Subject of the Rule, Verb of the Rule and Object of the Rule. You can download the <a href="'.$action['ruletemplate'].'">template</a> file with the headers for this collection.</li>
							<li>Mac users should save the file as <b>Text (Windows)</b>.</li>
						</ul>
						</p>
					</td>
				</tr>
			</table>';

		if($_POST['submitfile']) {
			$_SESSION['cells'] = '';
			$file = move_uploadedfile($resource_info, $project_info);
			if(is_file($file)) {
				echo "<table width=100%><td><BR><tr><td bgcolor=\"lightyellow\"><p>2. File is valid and was successfully uploaded. Name of the file: ".$_FILES['import']['name']."\n </p></td>";
				echo '<form name = "importform" action = "'.$action['excelimport'].'" method="POST">';
				echo '<input type="hidden" name="file" value="'.$file.'">';
				echo '<tr><td><BR></tr></td><tr><td>';
				echo '<input type="submit" name="gototable" value="Show data before updating '.$resource_info['entity'].'">';
				echo "</tr></td></table>";
			} else {
				echo "<table width=100%><td><BR><tr><td bgcolor=\"lightyellow\"><p>2. File COULD NOT be uploaded. Please try again, if this still doens't work please contact your system administrator (must have permission to write)\n </p></td>";
			}
		}						//run the functions where upload file will occur

		if($_POST['gototable']) {
			$file = $_POST['file'];
			if(is_array($_SESSION['cells'])) {
				$cells = $_SESSION['cells'];
			} else {
				$cells = separate_cells(compact('action','project_id', 'resource_info','db','user_id', 'file'));
				$_SESSION['cells'] = $cells; #until i figure out a way to keep this data in the client... sessions will have to be the way to go
			}
			echo display_option(compact('cells', 'db', 'user_id', 'project_id', 'resource_info'));
		}
		if($_POST['InsertinDB']) {
			$cells = $_SESSION['cells'];
			$cells['data'] = updateS3DB(compact('cells', 'project_id', 'class_id', 'db', 'user_id', 'resource_info'));
			echo '<input type="button" onClick="window.location=\''.$action['listall'].'\'" value="List all '.$resource_info['entity'].'">';
		}
		echo '</form>';
	}

	function separate_cells($F) {
		extract($F);
		$regexp = $GLOBALS['regexp'];

		#1.Create $lines by separating each line of the uploaded file
		$lines = file($file);
		$lines = array_filter($lines, 'remove_empty_lines');
		
		for($i=0;$i<count($lines);$i++) {
			$lines[$i] = rtrim($lines[$i]);	//this is a fix to remove the pragraph at the end of the line
		}
		
		#2.Separate EACH cell from the first line in the uploaded file - These will give the program the field  names and verbs
		$subjects = explode("	", $lines[0]);
		$verbs = explode("	", $lines[1]);
		$objects = explode("	", $lines[2]);
		
		#eliminate the trailing blanks
		$verbs[count($verbs)-1] = trim($verbs[count($verbs)-1]);
		$objects[count($verbs)-1] = trim($objects[count($verbs)-1]);
		
		#remove parenthesis
		$verbs = array_map('clean_inputs', $verbs);
		$objects = array_map('clean_inputs', $objects);

		#4.now for the rule info
		for($col=0;$col<count($objects);$col++) {
			$cells['rules'][$col]['subject'] = $subjects[$col];
			$cells['rules'][$col]['verb'] = $verbs[$col];
			$cells['rules'][$col]['object'] = $objects[$col];
			#now check to see if this rule was found(and user has permissions on it)
			
			$s3ql = compact('db', 'user_id');
			$s3ql['select'] = '*';
			$s3ql['from'] = 'rules';
			$s3ql['where']['subject'] = $subjects[$col];
			$s3ql['where']['verb'] = $verbs[$col];
			#$s3ql['where']['object'] = $regexp."'^".addslashes($objects[$col])."$'";
			$s3ql['where']['object'] = addslashes($objects[$col]);
			$s3ql['where']['project_id'] = $project_id;
			$s3ql['format']='html';
			$output = S3QLaction($s3ql);
			
			if(is_array($output)) {
				$cells['rules'][$col]['rule_id'] = $output[0]['rule_id'];
				$cells['rules'][$col]['rule_info'] = $output[0]; #get the rule_id from the query on rules
				$rules[0] = $cells['rules'][$col]['rule_info'];
				$cells['rules'][$col]['rule_info'] = $rules[0];
			} else {
				$cells['rules'][$col]['rule_info'] = $output;
			}
		}

		#now go for the instances, start collecting them from rows
		for($row=3;$row<count($lines);$row++) { #row 1 is for verbs, 2 for objects
			$cells['data'][$row] = explode ("	", $lines[$row]); #booom
			$cells['data'][$row] = array_map('trim_quotes', $cells['data'][$row]); #clean the quotes we needed to add for excel
			$cells['data'][$row]['UID'] = $cells['data'][$row][0];
			$instance_info = URIinfo('I'.$cells['data'][$row]['UID'], $user_id, $key, $db);
			
			#if a uid was found, we can start running the statements
			if(is_array($instance_info)) {
				$cells['data'][$row]['instance_info'] = $instance_info;
			} else {
				$cells['data'][$row]['instance_info'] = '';
			}
			#new loop, this time to get the statement info
			for($col=2;$col<count($objects);$col++) {	
				#read the existing statements
				$rule_info = $cells['rules'][$col]['rule_info'];
				$rule_id = $rule_info['rule_id'];
				#instance here must be something valid, found in the validating instance process
				$instance_id = $instance_info['resource_id'];
						
				$cells['data'][$row][$col] = array('newvalue' => $cells['data'][$row][$col]);
				if ($rule_id!='' && is_array($instance_info)) {		#find statement info associated with a determinate rule_id and UID (still to solve the problem of multiple statements)
					#$statement_id = find_statement_id($rule_id, $UID, $_REQUEST['project_id']);
					$s3ql= compact('db', 'user_id');
					$s3ql['select'] = '*';
					$s3ql['from'] = 'statements';
					$s3ql['where']['rule_id'] = $rule_id;
					$s3ql['where']['resource_id'] = $instance_id;
					$s3ql['where']['project_id'] = $project_id;
					$s3ql['format']='html';
					$statements = S3QLaction($s3ql);
								
					$cells['data'][$row][$col]['statements'] = $statements;
					if(count($statements)==1) {
						$cells['data'][$row][$col]['statement_info'] = $statements[0];
					}
					if(is_array($statements)) { 		#only one statement_id, we're safe
						if($rule_info['object_id']!='') {
							$statements = include_button($statements, $user_id, $db);
						}
						$statement_info = $statements[0];
						$statement_info['value'] = urldecode($statement_info['value']); #this is to turn the encoded html into normal text again; if it is not in html, it should do nothing
						$cells['statements'][$row][$col][0] = $statement_info;
						$cells['data'][$row][$col]['statement_info'] = $statement_info;
					} else {
						$cells['data'][$row][$col]['statement_info'] = '';
						$cells['statements'][$row][$col][0] = '';
					}
				}
			}
		}
		return ($cells);
	}

	function display_option($C) {
		extract($C);
		$action = $GLOBALS['webaction'];
	
		$tablecells .=  '';
		$tablecells .= '<form name = "importform" action = "'.$action['excelimport'].'" method="POST">';
		$tablecells .=  "<table width='100%'><tr bgcolor=lightyellow><td>3. Select fields to be updated</td></tr></table>";
		$tablecells .= "<TABLE border=1>";
		$tablecells .= "<TR>"; #start the row where the rule are displayed

		foreach($cells['rules'] as $col=>$rules) {
			if($col==0) { 		#0 contains UID, 1 contains Notes
				$tablecells .= '<TD><BR>UID<BR><br>';
				$tablecells .= '<input type="button" value="Check all" name="fieldcheck'.$col.'" onClick="this.value=check_rule('.$col.')" checked><BR></TD>';
			} elseif($col==1) {
				$tablecells .= '<TD>';
				$tablecells .= 'Notes';
				$tablecells .= '<BR><br><br><input type="button"  value="Check all" "fieldcheck'.$col.'" onClick="this.value=check_rule('.$col.')" checked><BR>';
				$tablecells .= '</TD>';
			} elseif($col>=2) {
				if (is_array($rules['rule_info'])) {
					$tablecells .= '<TD>'.$rules['rule_info']['verb'].'<BR><font color=blue>'.$rules['rule_info']['object'].'<BR>(rule id '.$rules['rule_info']['rule_id'].')</font><BR><input type="button"  value="Check all" name="fieldcheck'.$col.'" onClick="this.value=check_rule('.$col.')"  checked></TD>';
				} else {
					$tablecells .= '<TD><BR>'.$rules['verb'].'<BR><font color=red>'.$rules['object'].'<BR>(rule id NOT FOUND)</font>';
					$tablecells .= '<br>';
					$tablecells .= 'create rule?';
					$tablecells .= '<input type="checkbox" name="newrule_0_'.$col.'" checked>';
					$tablecells .= '<input type="button"  value="Check all" name="fieldcheck'.$col.'" onClick="this.value=check_rule('.$col.')" checked></TD>';
				}
			}
		}
		$tablecells .= "</TR>";

		foreach($cells['data'] as $row=>$row_data) {
			$tablecells .= '<TR>';
			for($col=0;$col<count($cells['rules']);$col++) {
				if($col==0) {
					$tablecells .= '<TD>';
					if($row_data['UID']=='') {
						$tablecells .= '(no UID)';
						$tablecells .= '<input type="checkbox" name="newinstance_'.$row.'" id="confirm_me'.$col.'[]" checked>';
					} elseif($row_data['UID']!='' && !is_array($row_data['instance_info'])) {		#so, the instance was not found?
						$tablecells .= '<font color=red>';
						$tablecells .= '(UID not found)';
						$tablecells .= '</font>';
						$tablecells .= '<br>new?';
						$tablecells .= '<input type="checkbox" name="newinstance_'.$row.'" id="confirm_me'.$col.'[]" checked>';
					} else {
						$tablecells .= instanceButton($row_data['instance_info']);
						$tablecells .= '<input type="checkbox" name="confirminstance_'.$row.'" id="confirm_me'.$col.'[]" checked>';	
					}
					$tablecells .= '</TD>';
				} elseif($col==1) {		#a is new, b is old
					$tablecells .= '<TD>';
					if(!is_array($row_data['instance_info']) && $row_data[1]=='') {		#no a ,no b
						$tablecells .='(notes empty)';
					} elseif($row_data[1]!='' && !is_array($row_data['instance_info'])) {		#a but no b
						$tablecells .= $row_data[1];
						$tablecells .= '<input type="checkbox" name="confirmnotes_'.$row.'" id="confirm_me'.$col.'[]" checked>';
					} elseif($row_data[1]=='' && is_array($row_data['instance_info']) && $row_data['instance_info']['notes']!='') {		#b but no a
						$tablecells .= 'old:'.$row_data['instance_info']['notes'].'<br>';
						$tablecells .= 'new: (empty)<br>';
						if($row_data['instance_info']['add_data']) {
							$tablecells .= '<font color=red>';
							$tablecells .= 'delete?';
							$tablecells .= '</font>';
							$tablecells .= '<input type="checkbox" name="confirmnotes_'.$row.'" value="editnotes_'.$row_data['instance_info']['resource_id'].'" id="confirm_me'.$col.'[]" checked>';
						} else {		#uh ho, you're not allowed!
							$tablecells .= '<font color=red>';
							$tablecells .= 'User is not allowed to change instance_id '.$row_data['instance_info']['resource_id'];
							$tablecells .= '</font>';
						}
					} elseif($row_data[1]!='' && is_array($row_data['instance_info']) && $row_data['instance_info']['notes']==$row_data[1]) {		#a==b
						#this is an intance where notes were NOT modified
						$tablecells .= $row_data[1];
						$tablecells .= '<br>';
						$tablecells .= '<font color=navy>';
						$tablecells .= '(no change)';
						$tablecells .= '</font>';
					} elseif($row_data[1]!='' && is_array($row_data['instance_info']) && $row_data[1]!=$row_data['instance_info']['notes']) {		#a!=b this is an intance where notes were modified
						$tablecells .= 'old:'.$row_data['instance_info']['notes'].'<br>';
						if($row_data['instance_info']['add_data']) {
							$tablecells .= '<font color=DarkGreen>';
							$tablecells .= 'new:'.$row_data[1].'';
							$tablecells .= '</font>';
							$tablecells .= '<br>edit?';
							$tablecells .= '<input type="checkbox" name="confirmnotes_'.$row.'" value="editnotes_'.$row_data['instance_info']['resource_id'].'" id="confirm_me'.$col.'[]" checked>';
						} else {		#uh ho, you're not allowed!
							$tablecells .= '<font color=red>';
							$tablecells .= 'User is not allowed to change instance_id '.$row_data['instance_info']['resource_id'];
							$tablecells .= '</font>';
						}
					}
					$tablecells .= '</TD>';
				} elseif($col>=2) {
					$tablecells .= '<TD>';
					$statement_info = $row_data[$col]['statement_info'];

					#if($rules['rule_info']['rule_id']=='') {		#no rule
					#		$tablecells .='(Data will NOT be imported)';
					#} else
					
					if($statement_info['value']=='' && $row_data[$col]['newvalue']=='') {		#print nothing no a and no b
						$tablecells .= '';
					} elseif($row_data[$col]['newvalue']!='' && empty($statement_info['value'])) {		#a but not b this means nothing was there, no checking required except for rule
						if(!is_array($cells['rules'][$col]['rule_info'])) {
							$tablecells .= $row_data[$col]['newvalue'];
						} elseif($cells['rules'][$col]['rule_info']['object_id']=='') {		#normal
							$tablecells .= $row_data[$col]['newvalue'];
						} else {
							$IN_instance_info = s3info('instance', $row_data[$col]['newvalue'], $db);
							if (!is_array($IN_instance_info)) {
								$tablecells .= '<font color=red>';
								$tablecells .= '(UID '.$row_data[$col]['newvalue'].' not found)';
								$tablecells .= '<br>';
								$tablecells .= 'Please insert a valid resource';
								$tablecells .= '</font>';
								$tablecells .= get_rule_drop_down_menu(array('select_name'=>'selectstatement_'.$row.'_'.$col, 'rule_info'=>$cells['rules'][$col]['rule_info'], 'db'=>$db,'user_id'=>$user_id,'project_id'=>$project_id,'instance_id'=>$row_data['instance_info']['resource_id']));
							} else {		#object is a resource and instance was found
								$tablecells .= instanceButton($IN_instance_info);
							}
						}
						$tablecells .= '<br>';
						$tablecells .= '<input type="checkbox" name="insertstatement_'.$row.'_'.$col.'" value="insertstatement_'.$row_data['instance_info']['resource_id'].'_'.$rule_id.'" id="confirm_me'.$col.'[]" checked>';
					} elseif($row_data[$col]['newvalue']=='' && is_array($statement_info) && $statement_info['value']!='') {		#b but not a #here is something being deleted
						if($statement_info['change']) {
							if($statement_info['file_name']!='') {
								$tablecells .= '<font color=red>';
								$tablecells .= '(statement contains a file, please change it in the interface)<br /><a href=# onClick="window.open(\''.$action['instance'].'&instance_id='.$statement_info['resource_id'].'\')">Edit</a>';
								$tablecells .= '</font>';
							} else {
								if($cells['rules'][$col]['rule_info']['object_id']=='') {
									$tablecells .= 'old: '.$statement_info['value'];
								} else {
									$OUT_instance_info = get_info('instance', $statement_info['value'], $db); 
									$tablecells .=  'old: '.instanceButton($OUT_instance_info);
								}
								$tablecells .= '<br>';
								$tablecells .= '<font color=red>';
								$tablecells .= 'new: (empty)<br>';
								$tablecells .= 'delete?';
								$tablecells .= '</font>';
								$tablecells .= '<input type="checkbox" name="deletestatement_'.$row.'_'.$col.'" value="deletestatement_'.$row_data['instance_info']['resource_id'].'_'.$rule_id.'" id="confirm_me'.$col.'[]">';
							}
						} else {
							$tablecells .= 'old: '.$statement_info['value'];
							$tablecells .= '<font color=red>';
							$tablecells .= 'User does not have permission to delete statement_id '.$statement_info['statement_id'].'!!';
							$tablecells .= '</font>';
						}
					} elseif($row_data[$col]['newvalue']!='' && is_array($statement_info) && $statement_info['value']==$row_data[$col]['newvalue']) {		#a==b nothing to change
						if($cells['rules'][$col]['rule_info']['object_id']=='') {
							$tablecells .= $statement_info['value'];
						} else {
							$OUT_instance_info = get_info('instance', $statement_info['value'], $db); 
							$tablecells .=  instanceButton($OUT_instance_info);
						}
						$tablecells .= '<font color=navy>';
						$tablecells .= '<br>';
						$tablecells .= '(no change)';
						$tablecells .= '</font>';
					} elseif($row_data[$col]['newvalue']!='' && is_array($statement_info) && $statement_info['value']!='' && $statement_info['value']!=$row_data[$col]['newvalue']) {		#a!=b
						if($statement_info['change']) {
							if($statement_info['file_name']!='') {
								$tablecells .= '<font color=red>';
								$tablecells .= '(statement contains a file, please change it in the interface)<br /><a href=# onClick="window.open(\''.$action['instance'].'&instance_id='.$statement_info['resource_id'].'\')">Edit</a>';
								$tablecells .= '</font>';
							} else {
								if($cells['rules'][$col]['rule_info']['object_id']=='') {
									$tablecells .= 'old: '.$statement_info['value'];
									$tablecells .= '<br>';
									$tablecells .= 'new: '.$row_data[$col]['newvalue'];
								} else {
									$OLD_instance_info = URIinfo('I'.$statement_info['value'], $user_id, $key, $db);
									$NEW_instance_info = URIinfo('I'.$row_data[$col]['newvalue'], $user_id, $key, $db);
									$tablecells .=  'old: '.instanceButton($OLD_instance_info);
									$tablecells .= '<br>';
									if (!is_array($NEW_instance_info)) {
										$tablecells .= '<font color=red>';
										$tablecells .= '(UID '.$row_data[$col]['newvalue'].' not found)';
										$tablecells .= '<br>';
										$tablecells .= 'Please insert a valid resource';
										$tablecells .= '</font>';
										$tablecells .= get_rule_drop_down_menu(array('select_name'=>'selectstatement_'.$row.'_'.$col, 'rule_info'=>$cells['rules'][$col]['rule_info'], 'db'=>$db,'user_id'=>$user_id,'project_id'=>$project_id,'instance_id'=>$row_data['instance_info']['resource_id']));
									} else {
										$NEW_instance_info = URIinfo('I'.$row_data[$col]['newvalue'], $user_id, $key, $db);
										$tablecells .= 'new: '.instanceButton($NEW_instance_info);
									}
								}
								$tablecells .= '<br>';
								$tablecells .= '<font color=DarkGreen>';
								$tablecells .= 'edit?';
								$tablecells .= '</font>';
								$tablecells .= '<input type="checkbox" name="editstatement_'.$row.'_'.$col.'" value="editstatement_'.$row_data['instance_info']['resource_id'].'_'.$rule_id.'" id="confirm_me'.$col.'[]" checked>';
							}
						} else {
							$tablecells .= 'old: '.$statement_info['value'];
							$tablecells .= '<font color=red>';
							$tablecells .= 'User does not have permission to delete statement_id '.$statement_info['statement_id'].'!!';
							$tablecells .= '</font>';
						}
					}
					$tablecells .= '</TD>';
				}
			}
			$tablecells .= '</TR>';
		}
		$tablecells .= '<input type="submit" name="InsertinDB" value="Import '.$resource_info['entity'].'"><BR>';
		$tablecells .= '</table>';
		return ($tablecells);
	}

	function updateS3DB($U) {
		extract($U);
		$report .='<table>';
		$posted_data = array_diff_key($_POST, array('MAX_FILE_SIZE'=>'', 'InsertinDB'=>''));
		foreach($posted_data as $button_coord=>$S3DBaction) {
			list($confirm, $row, $col) = explode('_', $button_coord);
			$instance_notes = $cells['data'][$row][1];
		
			if($confirm =='newrule') { 		#CREATE rule
				$s3ql=compact('user_id','db');
				$s3ql['insert']='rule';
				$s3ql['where']['project_id']=$project_id;
				$s3ql['where']['subject_id']=$_REQUEST['class_id'];
				$s3ql['where']['verb']=$cells['rules'][$col]['verb'];
				$s3ql['where']['object']=$cells['rules'][$col]['object'];
				$s3ql['format']='php';
				$done = S3QLaction($s3ql);
				$msg = unserialize($done);
				$msg=$msg[0];

				$report .='<tr><td>';
				#ereg('<error>([0-9]+)</error>(.*)<(message|rule_id)>(.*)</(message|rule_id)>', $done, $s3qlout);
				if($msg['error_code']=='0') {
					$rule_id = $msg['rule_id'];
					$cells['rules'][$col]['rule_id'] = $rule_id;
					$report .= 'Rule '.$s3ql['where']['subject'].'|'.$s3ql['where']['verb'].'|'.$s3ql['where']['object'].' created';
				} else {
					$cells['rules']['error'][$row][$col] = $msg['message'];
					$report .= '<font color="red">Rule '.$s3ql['where']['subject'].'|'.$s3ql['where']['verb'].'|'.$s3ql['where']['object'].' could not be created</font>';
				}
				$report .='</td></tr>';
			} elseif($confirm=='newinstance' && $row!='') {		#CREATE instance
				##remove the confirm notes of this instance after it is created, or it will read in the wrong row for update
				$s3ql=compact('user_id','db');
				$s3ql['insert']='item';
				$s3ql['where']['collection_id']=$class_id;
				#$s3ql['where']['project_id']=$project_id;
				if ($_POST['confirmnotes_'.$row]!='') {
					$s3ql['where']['notes']=$instance_notes;
				}
				$s3ql['format']='php';
				$done = S3QLaction($s3ql);
				$msg =unserialize($done);
				$msg=$msg[0];
			
				#$msg = html2cell($done);
				$report .='<tr><td>';
				#ereg('<error>([0-9]+)</error>.*<(message|item_id)>(.*)</(message|item_id)>', $done, $s3qlout);
				if($msg['error_code']=='0') {
					$instance_id=$msg['item_id'];
					$report .= 'I'.$instance_id.' with notes <font color="blue">'.$instance_notes.'</font> was created';
					$cells['data'][$row]['UID'] = $instance_id;
					$posted_data['confirmnotes_'.$row] = '';
				} else {
					$cells['data']['error'][$row][$col] = $msg['message'];
					$report .= '<font size="4" color="red">Instance with notes '.$instance_notes.' was NOT created. Reason: '.$msg['message'].'</font>';
					#$report .= 'Instance '.$instance_id[0].' with notes '.$instance_notes.' created<br />';
				}
				$report .='</td></tr>';
			} elseif($posted_data['confirminstance_'.$row]=='on' || $posted_data['newinstance_'.$row]=='on') {
				if ($confirm =='confirmnotes' && $posted_data['confirmnotes_'.$row]!='') {		#EDIT instance
					$s3ql=compact('user_id','db');
					$s3ql['edit']='item';
					$s3ql['where']['item_id']=$cells['data'][$row]['UID'];
					#$s3ql['where']['project_id']=$project_id;
					$s3ql['set']['notes']=$instance_notes;
					$s3ql['format']='php';
					$done = S3QLaction($s3ql);
					$msg = unserialize($done);
					$msg=$msg[0];

					$report .='<tr><td>';
					#ereg('<error>(.*)</error>.*<(message|item_id)>(.*)</(message|item_id)>', $done, $s3qlout);
					if($msg['error_code']=='0') {
						$report .='I'.$s3ql['where']['item_id'].' updated with notes <font color="blue">'.$s3ql['set']['notes'].'</font>';
					}
					else {
						$report .='<font color="red">I'.$s3ql['where']['item_id'].'WAS NOT updated with notes '.$s3ql['set']['notes'].'. Reason: '.$msg['message'].'</font>';
					}
					$report .='</td></tr>';
					$cells['data']['error'][$row][$col]= $done;
					#$report .= 'Instance '.$instance_id[0].' with notes '.$instance_notes.' created<br />';
				} elseif($confirm=='insertstatement') { 		#CREATE statement
					if($cells['rules'][$col]['rule_info']['object_id']!='' && $_POST['selectstatement_'.$row.'_'.$col]!='') {
						$cells['data'][$row][$col]['newvalue'] = $_POST['selectstatement_'.$row.'_'.$col];
					}
					if ($cells['data'][$row]['UID']!='' && $cells['rules'][$col]['rule_id']!='') {
						##is this a file?
						$s3ql=compact('user_id','db');
						$s3ql['insert']='statement';
						$s3ql['where']['item_id']=$cells['data'][$row]['UID'];
						$s3ql['where']['rule_id']=$cells['rules'][$col]['rule_id'];
						#$s3ql['where']['project_id']=$project_id;
						$s3ql['where']['value']=$cells['data'][$row][$col]['newvalue'];
						$s3ql['format']='php';
						$done = S3QLaction($s3ql);
						$msg = unserialize($done);
						
						$msg=$msg[0];
						$report .='<tr><td>';
				
						#ereg('<error>(.*)</error>.*<(statement_id|message)>(.*)</(statement_id|message)>', $done, $s3qlout);
						if($msg['error_code']=='0') {
							$statement_id=$msg['statement_id'];
							$cells['data'][$row][$col]['statement_id'] = $statement_id;
							$report .= 'Statement of <#I'.$s3ql['where']['item_id'].'><#R'.$s3ql['where']['rule_id'].'>'.$s3ql['where']['value'].' inserted.';
						} else {
							$report .= '<font color="red">Statement of <#I'.$s3ql['where']['item_id'].'><#R'.$s3ql['where']['rule_id'].'>'.$s3ql['where']['value'].' WAS NOT inserted. Reason: '.$msg['message'].'</font>';
						}
					}
					$report .='</td></tr>';
				} elseif($confirm == 'editstatement') { 		#EDIT statement
					if($cells['rules'][$row]['rule_info']['object_id']!='' && $_POST['selectstatement_'.$row.'_'.$col]!='') { 
						$value = $_POST['selectstatement_'.$row.'_'.$col];
					} else {
						$value = $cells['data'][$row][$col]['newvalue'];
					}
					$s3ql=compact('user_id','db');
					$s3ql['edit']='statement';
					$s3ql['where']['statement_id']=$cells['data'][$row][$col]['statement_info']['statement_id'];
					#$s3ql['where']['project_id'] = $project_id;
					$s3ql['set']['value']=$cells['data'][$row][$col]['newvalue'];
					$s3ql['format']='php';
					$done = S3QLaction($s3ql);
					$msg = unserialize($done);
					$msg=$msg[0];
				
					$report .='<tr><td>';
					#ereg('<error>([0-9]+)</error>.*<message>(.*)</message>', $done, $s3qlout);
					if ($msg['error_code']=='0') {
						$report .='S'.$s3ql['where']['statement_id'].' updated with value <font color="blue">'.$s3ql['set']['value'].'</font>';
					} else {
						$report .='<font color="red">S'.$s3ql['where']['statement_id'].' WAS NOT updated. Reason: '.$msg['message'].'</font>';
					}
					$report .='</td></tr>';
				} elseif($confirm =='deletestatement') { 		#DELETE statement
					$s3ql=compact('user_id','db');
					$s3ql['delete']='statement';
					$s3ql['where']['statement_id']=$cells['data'][$row][$col]['statement_info']['statement_id'];
					$s3ql['where']['project_id']= $project_id;
					//$s3ql['where']['confirm']='yes';
					$s3ql['format']='php';
					$done = S3QLaction($s3ql);
					$msg = unserialize($done);
					$msg=$msg[0];
			
					$report .='<tr><td>';
					$report .=$msg['message'];
					$report .='</td></tr>';
				}
				$cells['data']['error'][$row][$col] = $done;
			}
		}
		$report .='</table>';
		echo $report;
		return ($cells['data']);
	}
?>