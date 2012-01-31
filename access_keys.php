<?php 
	#access_keys is the interface for keys to access s3db. Includes links to add keys and delete keys.
	ini_set('display_errors',0);
	if($_REQUEST['su3d'])
	ini_set('display_errors',1);

	if(file_exists('config.inc.php'))
	{
		include('config.inc.php');
	}
	else
	{
		Header('Location: index.php');
		exit;
	}
	
  
	$section_num = '3';
	$website_title = $GLOBALS['s3db_info']['server']['site_title'].' - access log';
	$site_intro = $GLOBALS['s3db_info']['server']['site_intro'];
	$manager= 'Access Log';
	$content_width='80%';

	
	include(S3DB_SERVER_ROOT.'/core.header.php');
	include(S3DB_SERVER_ROOT.'/webActions.php');
	
	
	
	#Action on submit
	if ($_REQUEST['Submit_key']!='')
	{
	
	
	$s3ql=compact('user_id','db');
	$s3ql['insert']='key';
	$s3ql['where']['key_id']=$_REQUEST['input_key'];
	$s3ql['where']['expires']=$_REQUEST['date'];
	$s3ql['where']['notes']=$_REQUEST['notes'];
	$s3ql['where']['UID']=strtoupper(substr($_REQUEST['resource'],0, 1).$_REQUEST['ID']);
	if ($_REQUEST['orderBy']!='') {
		$s3ql['order_by']=$_REQUEST['orderBy'].' '.$_REQUEST['direction'];
	}	
	
	$done = S3QLaction($s3ql);
	$done = html2cell($done);
	
	#ereg('<error>([0-9]+)</error>(.*)<message>(.*)</message>', $done, $s3qlout);
	
	$message = $done[2]['message'];
	}

	
	elseif ($_REQUEST['action']=='delete')
	{
	
	
	$match_values = array('key_id'=>$_REQUEST['key_id'], 'expires'=>$_REQUEST['expires']);
	delete_entry('access_keys', $match_values, $db);

	
	
	}
	
	#Delete expired keys each time script is accessed
	#$delete_expired = delete_expired_keys(date('Y-m-d'), $db);

	if ($_REQUEST['random']!='') $random = random_string('15');
	else $random = '';

	$s3resources = array('', 'project', 'class', 'rule', 'instance' ,'statement');
	#Print a line for addition of an access key
	$create_key_line .= '<form name="key" method="POST" action=access_keys.php>';
	$create_key_line .= '<table width="100%"  align="center" border="0">';
	$create_key_line .= '<tr bgcolor="#99CCFF"><td colspan="9" align="center">Create Key</td></tr>';
	$create_key_line .= '<tr bgcolor="#FFFFCC"><td  width="10%">Key</td>';
	$create_key_line .= '<td  width="10%">Expires</td>';
	$create_key_line .= '<td  width="10%">Notes</td>';
	#$create_key_line .= '<td  width="10%">Resource</td>';
	#$create_key_line .= '<td  width="10%">ID</td>';
	$create_key_line .= '<td  width="10%">Action</td></tr>';
	$create_key_line .= '<tr><td  width="10%"><input type="text" style="background: lightyellow" name="input_key" value="'.$random.'"><BR><a href="access_keys.php?random=yes'.$extra_vars.'">Generate random key</a></td>';
	$create_key_line .= '<td  width="10%"><INPUT style="background: lightyellow" TYPE="text" NAME="date" VALUE="'.date('Y-m-d', time()+(1 * 24 * 60 * 60)).'" SIZE=25>';
	$create_key_line .= '<td  width="10%"><textarea name="notes"  style="background: lightyellow" rows="2"cols="20" ></textarea></td>';
	#$create_key_line .= '<td  width="10%"><select name="resource">';
	#foreach ($s3resources as $s3) {
	#	$create_key_line .= '<option value="'.$s3.'">'.$s3.'</option>';
	#}
	$create_key_line .= '</select></td>';
	#$create_key_line .= '<td  width="10%"><input type="text" name="ID"></td>';
	$create_key_line .= '<td  width="10%"><input type ="submit" name="Submit_key" value="New Key"></td></tr>';


include(S3DB_SERVER_ROOT.'/s3style.php');
include(S3DB_SERVER_ROOT.'/tabs.php');
#Find existing keys
$s3ql=compact('user_id','db');
$s3ql['select']='*';
$s3ql['from']='keys';
if ($_REQUEST['orderBy']!='') {
		$s3ql['order_by']=$_REQUEST['orderBy'].' '.$_REQUEST['direction'];
	};


#echo '<pre>';print_r($s3ql);
$user_keys = S3QLaction($s3ql);
#echo '<pre>';print_r($user_keys);exit;
#Create the table with exsiting keys
#Parse to the template
$add_key_form= $create_key_line;
if (is_array($user_keys) && !empty($user_keys))
{
$existing_keys_header = "<tr bgcolor='#80BBFF'><td colspan='9' align='center'>Existing Keys</td></b></tr><td><BR></td>";

$columns = array('Key', 'Requested By', 'Expires', 'Notes', 'Actions');
#echo '<pre>';print_r($user_keys);	
$user_keys = replace_created_by($user_keys, $db);

$keys_table = render_elements($user_keys, $acl, $columns, 'access_keys');



}
        
		
?>
<table class="contents">
	<table class="top" align="center">
	<tr><td>
		<table class="insidecontents" align="center" width="<?php echo $content_width ?>">
			<tr><td class="message"><br /><?php echo $message ?></td></tr>
			
		</table>
	</td></tr>
</table>

	<?php echo $add_key_form ?>
	
	
	<tr><td><BR><BR></td></tr>
	<?php echo $existing_keys_header ?>
	
	<?php echo $keys_table ?>
	
	<tr><td><br /><br /><br /></td></tr>
</table>