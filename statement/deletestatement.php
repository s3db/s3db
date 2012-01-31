<?php
#deletestatement.php is the interface for deleting statements.
	
   #Helena F Deus (helenadeus@gmail.com)
	ini_set('display_errors',0);
	if($_REQUEST['su3d'])
	ini_set('display_errors',1);
	
	if($_SERVER['HTTP_X_FORWARDED_HOST']!='')
			$def = $_SERVER['HTTP_X_FORWARDED_HOST'];
	else 
			$def = $_SERVER['HTTP_HOST'];
	
	if(file_exists('../config.inc.php'))
	{
		include('../config.inc.php');
	}
	else
	{
		Header('Location: http://'.$def.'/s3db/');
		exit;
	}
	$key = $_GET['key'];
	#Get the key, send it to check validity

include_once('../core.header.php');

if($key) $user_id = get_entry('access_keys', 'account_id', 'key_id', $key, $db);
	else $user_id = $_SESSION['user']['account_id'];

$statement_id = $_REQUEST['statement_id'];
$statement_info = URIinfo('S'.$statement_id, $user_id, $key, $db);

if(!$statement_info['delete'])
{
echo "User cannot delete this statement";
exit;
}
else
{
	$project_info = get_info('project', $statement_info['project_id'], $db);
	$instance_info = get_info('instance', $statement_info['resource_id'], $db);
	$statements[0] = $statement_info;
	$statements = include_rule_info($statements, $project_id, $db);
	$statements = include_button_notes($statements, $project_id, $db);
	$statements = Values2Links($statements);
	$statement_info = $statements[0];
	
	#echo '<pre>';print_r($statement_info);
	if($_POST['delete_statement'] !='')
	{
		
		$s3ql = compact('db', 'user_id');
		$s3ql['delete'] = 'statement';
		$s3ql['where']['statement_id'] = $statement_id;
		$s3ql['flag']='all';
		#$s3ql['format']='html';
		#$s3ql['where']['project_id'] = $project_id;
		#$s3ql['where']['confirm'] = 'yes';

		$done = S3QLaction($s3ql);
		$done=html2cell($done);
		#echo '<pre>';print_r($done);
		#ereg('<error>([0-9]+)</error>.*<message>(.*)</message>', $done, $s3qlout);
		if($done[2]['error_code']=='0')
		{
						$js = sprintf("%s\n", '<script type="text/javascript">');
                        $js .= sprintf("%s\n", 'function kill_me()');
                        $js .= sprintf("%s\n", '{');
                        $js .= sprintf("%s\n", '        opener.window.location.reload(); self.close(); return false;');
                        $js .= sprintf("%s\n", '}');
                        $js .= sprintf("%s\n", '</script>');
                       echo  $js;
		}
		else
			echo '<font color="red">'.$done[2]['message'].'</font>';
	}
				
	
?>
<body onload="kill_me()"> 
<?php
echo '<form action="'.$action['deletestatement'].'" method="post" autocomplete="on">
<table border="0">
        <tr>
                <td>Deleting statement #'.$statement_info['statement_id'].'</td><td align="right"><font color="red"><b>'.$instance_info['notes'].'</b></font></td>
        </tr>

</table>
<table>
        <tr>
                <td colspan="2"><hr color="navy" size="2"></hr></td>
        </tr>
        <tr>
                <td style="color: red" colspan="2"><br />Do you really want to delete the following statement?<br /><br /></td>';

		$displayInfo = array('Project:'=>$project_info['project_name'],
					'ID:'=>$statement_info['resource_id'],
					'Subject'=>$statement_info['subject'],
					'Verb'=>$statement_info['verb'],
					'Object'=>$statement_info['object'],
					'Value'=>viewStatementValue($statement_info),
					'Notes'=>$statement_info['notes'],
					'Created On:'=>$statement_info['created_on'],
					'Created By:'=>find_user_loginID(array('account_id'=>$statement_info['created_by'], 'db'=>$db)),
					'Modified By:'=>find_user_loginID(array('account_id'=>$statement_info['modified_by'], 'db'=>$db)),
					'Modified On:'=>$statement_info['modified_on']);
		
		foreach($displayInfo as $title=>$something)
		{
		echo '<tr>';
		echo '<td>'.$title.'</td>';
		echo '<td>'.$something.'</td>';
		echo '</tr>';
		
		}
		
        echo '<tr><td><input type="submit" name="delete_statement" value="&nbsp;&nbsp;Delete&nbsp;&nbsp;"></td></tr>
</table>
</form>
</body>';
}
?>