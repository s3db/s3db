<?php
	#insertinstance.php is a form for adding instances
	#Includes links to instance page, as well as import from excel
	#Helena F Deus (helenadeus@gmail.com), June 28 2007
	#Modified by Bade Iriabho, August 20, 2012
	ini_set('display_errors',0);
	if($_REQUEST['su3d']) { ini_set('display_errors',1); }
	include('instanceheader.php');
	#relevant extra arguments
	#$args = '?key='.$_REQUEST['key'].'&project_id='.$_REQUEST['project_id'].'&class_id='.$_REQUEST['class_id'];
	
	#define actions for the page
	#include('../webActions.php');
	$class_id = ($_REQUEST['collection_id']!='')?$_REQUEST['collection_id']:$_REQUEST['class_id'];
	$resource_info = URIinfo('C'.$class_id, $user_id, $key, $db);
	
	if($class_id=='' && $resource_info=='') {
		echo "Please specify a valid collection_id";
		exit;
	} else {
		if(!$resource_info['add_data']) {
			echo "User cannnot add items in this collection";
			exit;
		} else {
			#form actions - have to com ebefore the rest of the scirpt bacuse of the headers...
			if($_POST['add_resource']) {
				$s3ql=compact('user_id','db');
				$s3ql['insert'] = 'item';
				$s3ql['where']['collection_id'] = $class_id;
								
				if($_POST['notes']!='') {
					$s3ql['where']['notes'] = nl2br($_POST['notes']);	
				}
				
				$s3ql['format']='html';
				$done = S3QLaction($s3ql);
				$msg = html2cell($done);
	
				#ereg('<item_id>([0-9]+)</item_id>', $done, $s3qlout);
				$instance_id = $msg[2]['item_id'];
				#preg_match('/[0-9]+/', $done, $instance_id);
				$item_id = $instance_id;
					
				if($instance_id!='') {
					#now add the users
					$element = 'item';
					$message .= addUsers(compact('users','user_id', 'db', 'class_id', 'collection_id','element','item_id'));
					Header('Location: '.$action['instanceform'].'&item_id='.$instance_id);
					exit;
				} else {
					echo $msg[2]['message'];
				}
			}
			#include all the javascript functions for the menus...
			include('../S3DBjavascript.php');
			#and the short menu for the resource script
			include('../action.header.php');
			#add the form for inserting instances
		}
	}//closes no permission
	$new=1;
	$aclGrid = aclGrid(compact('user_id', 'db', 'users','new'));
?>
<table class="create_resource" width="70%" border="0">
	<tr>
		<td class="message" colspan="9"></td>
	</tr>
	<tr>
		<td></td>
	</tr>
	<tr bgcolor="#FF9900">
		<td colspan="9" align="center">
		<?php
			echo "Add Several <b>".$resource_info['entity']."</b> at a time";
		?>
		</td>
	</tr>
	<tr>
		<td colspan="9">
			<center>
				<br />
				<input type="hidden" name="entity" value="Dogs" />
				<?php
					echo '<input type="button" name="takemetoupload" value="Import '.$resource_info['entity'].' from File" onclick="window.location=\''.$action['excelimport'].'\'"><br />Note: Only tab separated files are valid';
				?>
				<br /><br />
			</center>
		</td>
	</tr>
	<?php
		echo '<form name="insertAcl" method="POST" action="'.$action['insertinstance'].'" autocomplete="on">';
		echo '<tr bgcolor="#FF9900"><td colspan="9" align="center">Add One <b>'.$resource_info['entity'].'</b> at a time</td></tr>';
	?>
	<tr class="odd" align="center">
		<td width="10%">Owner</td>
		<td width="10%">Resource<sup class="required"></sup></td>
		<td width="20%">Label</td>
		<td width="10%">Action</td>
	</tr>
	<tr valign="top" align="center">
	<?php
		echo '<td width="10%">'.find_user_loginID(array('db'=>$db, 'account_id'=>$user_id)).'</td>';
		echo '<td width="15%">'.$resource_info['entity'].'</td>';
	?>
		<td width="30%">
			<input type="text" name="notes" size="70" class="bp_form_complete-all-name ac_input">
			<input type="hidden" id="notes_bioportal_ontology_id">
			<input type="hidden" id="notes_bioportal_full_id">
		</td>
		<td width="10%" align="center">
			<script type="text/javascript">
				// Grab the specific scripts we need and fires it start event
				jQuery.getScript("https://bioportal.bioontology.org/javascripts/JqueryPlugins/autocomplete/crossdomain_autocomplete.js", function(){
				formComplete_setup_functions();
				});
			</script>
		<?php
			echo '<input type="submit" name="add_resource" value="Add '.$resource_info['entity'].'">';
		?>
		</td>
	</tr>
	<tr>
		<td colspan="9" align="center"><br /><br /></td>
	</tr>
	<tr bgcolor="#FF9900">
		<td colspan="9" align="center">Users</td>
	</tr>
	<?php echo $aclGrid; ?>
	</tr>
	<?php
		echo '</form>';
	?>
</table>