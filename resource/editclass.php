<?php
#editrule.php is a form for changingrules that are classes
	#Includes links to edit and delete resource, as well as edit rules
	#Helena F Deus (helenadeus@gmail.com)

ini_set('display_errors',0);
if($_REQUEST['su3d'])
ini_set('display_errors',1);
	
include('classheader.php');

if(!$class_info['change'])
	{#check projectAcl
		{echo "User does not have access to change this resource";
		exit;
		}
	}
else
{

if($_POST['editresource'])
	{
	#echo '<pre>';print_r($_POST);
	#this is the structure that the query to edit a class must have
	$s3ql = compact('user_id', 'db');
	$s3ql['edit'] = 'collection';
	$s3ql['where']['collection_id'] = $class_id;
	$s3ql['where']['entity'] = $_REQUEST['new_entity'];
	$s3ql['where']['notes'] = $_REQUEST['new_notes'];
	
	#echo '<pre>';print_r($s3ql);exit;
	$done = S3QLaction($s3ql);
	
	ereg('<error>(.*)</error>.*<message>(.*)</message>', $done, $s3qlout);
	
	if($s3qlout[1]!='0' && $s3qlout[1]!='3')#no need for the nochange message in this case
		$message .=substr($s3qlout[2], 0, strpos($s3qlout[2], '.'));
	
	
	#now add the users
		$message .= addUsers(compact('users','user_id', 'db', 'class_id', 'collection_id','element'));
#echo $message;exit;
	if($message=='')
			{
			Header('Location:'.$action['class']);
			exit;
			}
		else {
			$message .= '<input type="button" value="Return to class" onclick="window.location=\''.$action['class'].'\'">';
			}
		
	}
	
	#echo "<font color = 'red'>".$done."</font>";
	
	
#include the form
#include all the javascript functions for the menus...
include('../S3DBjavascript.php');

#and the short menu for the resource script
include('../action.header.php');
echo "<br /><br />";	

#echo '<pre>';print_r($_REQUEST);
$new = 0;
$aclGrid = aclGrid(compact('user_id', 'db', 'users','new','uid'));



?>

<table class="edit_rule" width="90%"  align="center" border="0">
	<?php
	echo '<tr><td class="message" colspan="9">'.$done.'<br /></td></tr>';
	?>
	<tr bgcolor="#80BBFF"><td colspan="9" align="center">Edit resource</td></tr>
	<tr class="odd" align="center">
		<td  width="10%">Owner</td>
		<td  width="10%">Created On</td>
		<td width="10%">Entity</td>
		<td width="10%">Notes</td>
		<td  width="10%">Action</td>
	</tr>

	<tr valign="top" class="entry">
		<?php
		#echo '<pre>';print_r($resource_info);
		echo '<td  width="10%">'.find_user_loginID(array('account_id'=>$resource_info['created_by'], 'db'=>$db)).'</td>';
		echo '<td  width="10%">'.$resource_info['created_on'].'</td>';
		echo '<form name="insertAcl" method="POST" action="'.$action['editclass'].'">';
		echo "<td  width='10%'><input type='text' name='new_entity' style='background: lightyellow' rows='2' cols='20' value='".$resource_info['entity']."'></td>";
		echo "<td  width='10%'><textarea name='new_notes' style='background: lightyellow' rows='2' cols='20'>".$resource_info['notes']."</textarea></td>";
		echo "<td width='10%' align='center'>";
		echo "<input type='submit' name='editresource' value='Update'><br />";
		echo "<input type='button' value='Delete' onClick=\"window.location = '".$action['deleteclass']."'\"><br />";
		echo "<input type='button' id='cancel' name='cancel' value='Cancel' onclick=\"window.location = '".$action['resource']."'\"</td>";
		?>

</tr>
<tr><td colspan="9" align="center"><BR><BR></td></tr>
<tr bgcolor="#80BBFF"><td colspan="9" align="center">Manage user permissions</td></tr>
<tr><td colspan="9" align="center"><?php echo $aclGrid;
}
?></td></tr></form>
</table>
