<?php
#instance.header.php displays a header for every item being opened
#Helena F Deus (helenadeus@gmail.com)
ini_set('display_errors',0);
if($_REQUEST['su3d'])
ini_set('display_errors',1);

?>
<table width="100%">
	
	
	<tr><td>
	<hr size="2" align="center" color="dodgerblue"></hr>
	</td></tr>
    <tr><td>
		<table width="100%">
			<tr style="color: navy; font-weight:bold">
				<td width="30%">ID</td>
				<td width="20%">Entity</td>
				<td width="15%">Created On</td>
				<td width="15%">Created By</td>
				<td width="10%">Notes</td>
				<td>&nbsp;</td> 
			</tr>
			<tr>
				<?php
				echo '<td>'.$instance_info['resource_id'];
				if($instance_info['change'])
				{echo '<br /><a href="#" onclick="window.open(\''.$action['editinstance'].'\', \'editresource_'.$instance_id.'\', \'width=600, height=600, location=no, titlebar=no, scrollbars=yes, resizable=yes\')" title="Edit resource '.$instance_id.' )">Edit</a>';
				echo '&nbsp;&nbsp;&nbsp;<a href="#" onclick="window.open(\''.$action['deleteinstance'].'\', \'width=600, height=600, location=no, titlebar=no, scrollbars=yes, resizable=yes\')">Delete</a></td>';
				}
				echo '</td>';
				echo '<td><b>'.$instance_info['entity'].'</b></td>';
				echo '<td>'.$instance_info['created_on'].'</td>';
				echo '<td>'.find_user_loginID(array('account_id'=>$instance_info['created_by'], 'db'=>$db)).'</td>';
				echo '<td><font color=red><b>'.$instance_info['notes'].'</b></td>';
				?>
				<td>&nbsp;</td> 
			</tr>
		</table>

	</td></tr>
	<tr><td>
	<hr size="2" align="center" color="dodgerblue"></hr>
	</td></tr>
</table>