<?php
	#editstatement.php is the interface for editing statements.
	#Helena F Deus (helenadeus@gmail.com)
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

	if($key) { 
		$user_id = get_entry('access_keys', 'account_id', 'key_id', $key, $db);
	} else {
		$user_id = $_SESSION['user']['account_id'];
	}

	$statement_id = $_REQUEST['statement_id'];
	$statement_info = URIinfo('S'.$statement_id, $user_id, $key, $db);
	#$statementAcl = statementAcl(compact('user_id', 'db', 'statement_id'));

	$project_id = $_REQUEST['project_id'];

	#relevant extra arguments
	#$args = '?key='.$_REQUEST['key'].'&project_id='.$_REQUEST['project_id'].'&statement_id='.$_REQUEST['statement_id'];
	#include('../webActions.php');

	if(!$statement_info['change']) {
		echo "User cannot edit statements in this rule";
		exit;
	} else {
		$project_info = get_info('project', $statement_info['project_id'], $db);
		$instance_info = get_info('instance', $statement_info['resource_id'], $db);
		$statements[0] = $statement_info;
		$statements = include_rule_info($statements, $project_id, $db);
		$statements = include_button_notes($statements, $project_id, $db);
		$statements = Values2Links($statements);
		$statement_info = $statements[0];
      
		if($_POST['edit_statement'] !='') {
			$value = $_POST['value'];
			if (ereg('^http://', $value)) {
				$value = urlencode($value);
			}
			$notes = $_POST['notes'];
			
			$s3ql = compact('db', 'user_id');
			$s3ql['edit'] = 'statement';
			$s3ql['where']['statement_id'] = $statement_id;
			#$s3ql['where']['project_id'] = $project_id;
			if($statement_info['file_name']=='') {
				$s3ql['set']['value'] = $value;
			}
			$s3ql['set']['notes'] = $notes;
			$done = S3QLaction($s3ql);
			$done=unserialize($done);
		
			if($done[0]['error_code']=='0') {
				$js = sprintf("%s\n", '<script type="text/javascript">');
				$js .= sprintf("%s\n", 'function kill_me()');
				$js .= sprintf("%s\n", '{');
				$js .= sprintf("%s\n", '	opener.window.location.reload(); self.close(); return false;');
				$js .= sprintf("%s\n", '}');
				$js .= sprintf("%s\n", '</script>');
				echo $js;
			} else {
				echo '<font color="red">'.$done.'</font>';
			}
		}
?>
<body onload="<?php if($_REQUEST['close_me']) { echo "opener.window.location.reload(); self.close(); return false;"; } ?>">
<?php
		echo '
	<form action="'.$action['editstatement'].'&close_me=1" method="post" autocomplete="on" name="insertstatement">
		<table border="0">
			<tr>
				<td>Editing statement #'.$statement_id.'</td><td align="right"><font color="red"><b>'.$instance_info['notes'].'</b></font></td>
			</tr>
		</table>
		<table>
			<tr>
				<td colspan="2"><hr color="navy" size="2"></hr></td>
			</tr>
			<tr>
				<td style="color: red" colspan="2"><br /></td>
			</tr>';
		$displayInfo = array(
						'Project:'=>$project_info['project_name'],
						'ID:'=>$statement_info['resource_id'],
						'Subject'=>$statement_info['subject'],
						'Verb'=>$statement_info['verb'],
						'Object'=>$statement_info['object'],
						'Value'=>editInputStatementValue($statement_info, $action),
						'Notes'=>'<textarea  style="background: lightyellow" rows="2" cols="40" name="notes" >'.$statement_info['notes'].'</textarea>',
						'Created On:'=>$statement_info['created_on'],
						'Created By:'=>find_user_loginID(array('account_id'=>$statement_info['created_by'], 'db'=>$db)),
						'Modified By:'=>find_user_loginID(array('account_id'=>$statement_info['modified_by'], 'db'=>$db)),
						'Modified On:'=>$statement_info['modified_on']
					);
		foreach($displayInfo as $title=>$something) {
			echo "
			<tr>
				<td>$title</td>
				<td>$something</td>
			</tr>";
		}
		echo '
			<tr>
				<td colspan="2"><br /> </td>
			</tr>
			<tr>
				<td><input type="submit" name="edit_statement" value="&nbsp;&nbsp;Update&nbsp;&nbsp;"></td>
			</tr>
		</table>
	</form>';
	}
?>
</body>