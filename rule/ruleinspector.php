<?php
#ruleinspector.php is a form for changing, deleting and generally visualizing rules
	#Includes links to edit and delete rule, as well as resource header
	
   #Helena F Deus (helenadeus@gmail.com)
	ini_set('display_errors',0);
	if($_REQUEST['su3d'])
	ini_set('display_errors',1);
include('ruleheader.php');

$tpl = CreateObject('s3dbapi.Template', $GLOBALS['s3db_info']['server']['template_dir']);

#Universal variables
$uni = array('db'=>$db, 'user_id'=>$user_id, 'key'=>$key);

$edited_rule = $rule_info;
$element='rule';


#is project_id empty? get it form the class
	if($project_id=='')
		if ($resource_info!='')
		{
		$project_id = $resource_info['project_id'];
		}
		elseif($rule_info!='')
		{
			$project_id = $rule_info['project_id'];
		}
	

	#is project_id still empty?
if($project_id=='' || $project_id=='0')
{
	echo "Please specify a valid project_id";
	exit;
}
elseif(!$project_info['add_data'])
{
	echo 'You are not allowed on this project.';
		exit;
}
else
	{
	
	if($_REQUEST['class_id']=='' || $_REQUEST['any_subject_class']==1)
		{
		#find all the classes in the project ro create "select" for forms
		$s3ql=compact('user_id','db');
		$s3ql['from']='collections';
		$s3ql['where']['project_id']=$project_id;
		$classes = S3QLaction($s3ql);
	
		}
		else {
			$classes[0]=$class_info;
		}

#echo '<pre>';print_r($_POST);exit;
	#Still here? ok, we can continue the script
	foreach ($_POST as $request_key=>$request_value) {
		$_POST[$request_key]=str_replace('#', '', $request_value);
	}
	
	if($_GET['action'] == 'edit')
		{
			$rule = URIinfo('R'.$_GET['rule_id'], $user_id, $key, $db);
			
			$rule_id=$rule['rule_id'];
			$action_name='editrule'; 
			$action_value='Update'; 
			$subject_input=$rule['subject']; 
			
			if($rule['verb_id']=='' || $_REQUEST['literal_verb'])
			$verb_input='<input name="verb" style="background: lightyellow" value="'.$rule['verb'].'" size="10">';
			else {
				$selected = array('instance_id'=>$rule['verb_id'], 'notes'=>$rule['verb']);
				$verb_input.=verbInputSelect(compact('classes', 'db', 'user_id', 'selected', 'project_id'));
			}
			#echo '<pre>';print_r($rule);
			if($rule['object_id']=='' || $_REQUEST['literal_object'])
			{
				$object_input='<input name="object" style="background: lightyellow" value="'.$rule['object'].'" size="10">'; 
			}
			else {
				$selected = array('class_id'=>$rule['object_id'], 'entity'=>$rule['object']);
				$object_input.=objectInputSelect(compact('classes', 'db', 'user_id', 'selected'));
			}
			if($rule['verb'] == 'has UID')
				$verb_input='has UID'; 
			if($rule['object'] == 'UID')
				$object_input='UID'; 
			
			$a = array_merge($uni, array('account_id'=>$rule['created_by']));
			$notes_input=$rule['notes']; 
			
			
			if(in_array('literal_object', array_keys($_REQUEST)))
			{$validation_input=$rule['validation']; 
			
			}
			else {
			$validation_input='UID';
			$validation_disabled = " disabled";
			}
			
			
			$owner=find_user_loginID($a); 
			$created_on= $rule['created_on']; 
			$displayed_rule_id= $rule['rule_id']; 
			$displayed_resource_id= $rule['resource_id']; 
			$edit_message='Update Rule';
			
			#query users of this rule
			$s3ql=compact('user_id','db');
			$s3ql['from']='users';
			$s3ql['where']['rule_id']=$rule_id;
			
			$users=S3QLaction($s3ql);
			$aclGrid = aclGrid(compact('user_id', 'db', 'users'));
			

		}
	else if($_POST['editrule'])
		{
			#echo '<pre>';print_r($_POST);
				$s3ql=compact('user_id','db');
				$s3ql['edit'] = 'rule';
				$s3ql['where']['rule_id'] = $_REQUEST['rule_id'];
				if($_POST['subject_id']!='')
				$s3ql['set']['subject_id'] = $_POST['subject_id'];
				if($_POST['subject']!='')
				$s3ql['set']['subject'] = $_POST['subject'];
				if($_POST['verb']!='')
				$s3ql['set']['verb'] = $_POST['verb'];
				if($_POST['verb_id']!='')
				$s3ql['set']['verb_id'] = $_POST['verb_id'];
				if($_POST['object']!='')
				$s3ql['set']['object'] = $_POST['object'];
				if($_POST['object_id']!='')
				$s3ql['set']['object_id'] = $_POST['object_id'];
				if($_POST['notes']!='')
				$s3ql['set']['notes'] = nl2br($_POST['notes']);
				else {
					$s3ql['set']['notes']='';
				}
				if($_POST['validation']!='')
				$s3ql['set']['validation'] = nl2br($_POST['validation']);
				else {
					$s3ql['set']['validation']='';
				}
				$s3ql['format']='html';
				
				$done = S3QLaction($s3ql);
				$msg = html2cell($done);
				
				//create biportal link
				//do we have the verb_id? probably not since it was not created now;
				$rule_info = get_rule_info($_REQUEST['rule_id']);
				if ($_REQUEST['verb_bioportal_concept_id']) {
					createBioportalLink('verb', 'I', $rule_info['verb_id'], $db, $user_id);
				}
				if ($_REQUEST['object_bioportal_concept_id']) {
					createBioportalLink('object', 'C', $_REQUEST['object_id'], $db, $user_id);
				}

				if($msg[2]['error_code']=='0')
					{
						
						$action_message .= addUsers(compact('users','user_id', 'db', 'rule_id', 'element'));
						
				
					}
				else
				{$action_message .=$msg[2]['message'];
				}
			
			if($action_message==''){
						Header('Location: '.$action['editrules']); 
						exit;
			}
						
			$owner=$edited_rule['owner'];
			$action_name='editrule'; 
			$action_value='Update'; 
			$edit_message='Update Rule';
			if($resource_info=='')
			$subject_input=$edited_rule['subject'];
			else
			$subject_input='<input name="subject" style="background: lightyellow" value="'.$edited_rule['subject'].'" size="10">';
			$verb_input='<input name="verb" style="background: lightyellow" value="'.$edited_rule['verb'].'" size="10">'; 
			
			#$object_input .='<input name="object" style="background: lightyellow" value="'.$edited_rule['object'].'" size="10">'; 
			#$object_input .='<br />';
			#$object_input .='<input type="button" value="Choose a class" size="10" onClick="window.location=\''.$action['ruleinspector'].'\'">'; 

			$notes_input=$edited_rule['notes'];
			
			if(in_array('literal_object', array_keys($_REQUEST)))
			{$validation_input=$edited_rule['validation'];
			
			}
			else {
			$validation_input='UID';
			$validation_disabled = " disabled";
			}
			
			
			$created_on=$edited_rule['created_on']; 
			$displayed_rule_id=$edited_rule['rule_id']; 
			$displayed_resource_id=$resource_info['id']; 
			$aclGrid = aclGrid(compact('user_id', 'db'));
		}
	else if($_POST['newrule']) 
		{
				
				$subject_id = ($_POST['subject_id']!='')?$_POST['subject_id']:$resource_info['class_id'];
								
				$s3ql=compact('user_id','db');
				$s3ql['insert'] = 'rule';
				$s3ql['where']['project_id'] = $project_id;
				$s3ql['where']['subject_id'] = $subject_id;
				if($_POST['verb'])
				$s3ql['where']['verb'] = $_POST['verb'];
				if($_POST['verb_id'])
				$s3ql['where']['verb_id'] = $_POST['verb_id'];	
				
				if($_POST['object_id']!='')
				$s3ql['where']['object_id'] = $_POST['object_id'];
				else 
				$s3ql['where']['object'] = $_POST['object'];
				
				$s3ql['where']['validation'] = $_POST['validation'];
				$s3ql['where']['notes'] = nl2br($_POST['notes']);
				$s3ql['format']='php';
				#echo '<pre>';print_r($s3ql);
				$done = S3QLaction($s3ql);
				$msg = unserialize($done);
				
				if($msg[0]['error_code']=='0')
				{	
					$rule_id = $msg[0]['rule_id'];
					#echo '<pre>';print_r($users);exit;
					$message .= addUsers(compact('users','user_id', 'db', 'rule_id', 'element'));

					//create bioportal link
					//do we have the verb_id? probably not since it was not created now;
					$rule_info = get_rule_info($rule_id);
					if ($_REQUEST['verb_bioportal_concept_id']) {
						
						createBioportalLink('verb', 'I', $rule_info['verb_id'], $db, $user_id);
						createBioportalLink('object', 'C', $_REQUEST['object_id'], $db, $user_id);
					}
					if ($_REQUEST['object_bioportal_concept_id']) {
						createBioportalLink('object', 'C', $_REQUEST['object_id'], $db, $user_id);
					}	
						
				}
				else
				{
					
					$message .= $msg[0]['message'];
				}	
				if($message==''){
				Header('Location: '.$action['editrules']); 
				exit;
				}

			$owner= find_user_loginID(array('account_id'=>$user_id, 'db'=>$db));
			$action_name='newrule'; 
			$action_value='Create'; 
			
			if($resource_info!='')
			$subject_input= $newrule['subject'];
			else
			$subject_input='<input name="subject" style="background: lightyellow" value="'.$subject.'" size="10">'; 
			
			$verb_input='<input name="verb" style="background: lightyellow" value="" size="10">'; 
			$object_input='<input name="object" class="bp_form_complete-all-name ac_input" style="background: lightyellow" value="" size="10">'; 
			$notes_input= $newrule['notes'];
			
			
			if(in_array('literal_object', array_keys($_REQUEST)))
			{$validation_input=$newrule['validation'];
			
			}
			else {
			$validation_input='UID';
			$validation_disabled = " disabled";
			}
			
			
			$displayed_rule_id='New'; 
			$displayed_resource_id= $resource_info['id']; 
			
			$edit_message='Create New Rule';
	}
	else
		{
			
			$action_message='* required';	
			$subject_required='*';
			$object_required='*';
			$verb_required='*';
			$owner= find_user_loginID(array('account_id'=>$user_id, 'db'=>$db));
			$action_name='newrule'; 
			$action_value='Create'; 
		 	if($resource_info!='')
			$subject_input=$resource_info['entity'];
			else
			$subject_input='<input name="subject" style="background: lightyellow" value="" size="10">'; 
			
			if(in_array('literal_object', array_keys($_REQUEST)))
			{$validation_input="";
			
			}
			else {
			$validation_input='UID';
			$validation_disabled = " disabled";
			}

			$verb_input_name = 'Verb';
			$verb_input='<input name="verb" style="background: lightyellow" value="" size="10">'; 
			$verb_browser='<input type="button" name="verbBrowser" style="background: lightyellow" onClick = "window.open(\''.$action['verbpeek'].'\')" value="'.$rule['verb'].'" size="10">'; 
			$object_input='<input name="object" class="bp_form_complete-all-name ac_input" style="background: lightyellow" value="" size="10">'; 
			$displayed_rule_id='New'; 
			$displayed_resource_id=$resource_info['id']; 

			$edit_message='Create New Rule';
			
		}

#include all the javascript functions for the menus...
	include('../S3DBjavascript.php');

	#and the short menu for the resource script
	
	if(is_array($resource_info) && !empty($resource_info))
	include('../action.header.php');

	#Make a good looking heather

	#GET A LIST OF ALL THE RULES

		$s3ql = compact('db', 'user_id');
		$s3ql['select'] = '*';
		$s3ql['from'] = 'rules';
		if($project_id!='')
		$s3ql['where']['project_id'] = $project_id;
		
		if($class_id!='') 
		{
		$s3ql['where']['subject_id'] = $class_id;
		$s3ql['where']['object'] = "!=UID";
		#$s3ql['where']['object'] = "[^(UID)]";
		}

		if($_REQUEST['orderBy']!='')
			$s3ql['order_by']=$_REQUEST['orderBy'].' '.$_REQUEST['direction']; 
		
		
		#echo '<pre>';print_r($s3ql);exit;
		$rules = S3QLaction($s3ql);
		#echo '<pre>';print_r($rules);		exit;
		if(is_array($rules) && !empty($rules))
		{	
			#Set the cols to show up in the html table
				
				$columns = array('Rule_id', 'Owner', 'CreatedOn', 'SubjectAndId', 'VerbAndId', 'ObjectAndId', 'Validation', 'Notes', 'Actions');
				
				
				#replace all created_by with login_id
				$rules = replace_created_by($rules, $db);
				$rules = include_class_id($rules, $db);
				$elements = $rules;
				$rules = grab_acl(compact('user_id', 'elements', 'db'));
				if($_REQUEST['action']=='edit') $new = 0;
				else
					$new=1;
				
				$data_grid = render_elements($rules, $acl, $columns, 'rule',$new, 'R'.$_REQUEST['rule_id']);

			

			#show the table header
			$num_per_page = $_REQUEST['num_per_page'];
			$select[$num_per_page]='selected';
			
			$perPage = array('10', '50', '100', '150', '200', '250', '300', '350', '400', '450', '500');
			echo '<table class="middle" width="100%"  align="center"></a>
				<tr><td>
				<table class="insidecontents" width="100%" align="center">
				<tr bgcolor="#99CCFF"><td colspan="3" align="center">Rule Inspector</td></tr>
				<tr><td></td><td>Available Rules: '.count($rules).'</td><td align="right">Number of Results Per Page
				<select name="num_per_page" onChange="window.location=this.options[this.selectedIndex].value">';
				foreach($perPage as $n)
				echo '<option value="'.$thisScript.'&num_per_page='.$n.'" '.$select[$n].'>'.$n.'</option>';
				
				#Finally show the list of rules
				echo '</td></tr><tr><td colspan="3">'.$data_grid.'</td></tr></table>';
		}
         else 
		{
			if($project_info['add_data'])
			{
				$message ='You do not have any rule yet. Please create a new rule first.';	
			}
			else
				$message = 'You do not have any rule yet. You also do not have permission to create rule.';	
		}
		
		

		
		#echo '<pre>';print_r($classes);
		$class_select .= '<select id="subject_id" name = "subject_id" onChange="window.location=this.options[this.selectedIndex].value">';
		if(is_array($classes))
		{if($_REQUEST['rule_id']!='')
			{$rule_info = URIinfo('R'.$_REQUEST['rule_id'], $user_id, $key, $db);
			$selected = array('subject_id'=>$rule_info['subject_id'], 'subject'=>$rule_info['subject']);
			$class_select .= '<option value="#'.$selected['subject_id'].'" selected>'.$selected['subject'].' (C'.$selected['subject_id'].')</option>';
			}
			foreach ($classes as $class_info) {
			if($class_id==''){
				if($class_info['class_id']!=$selected['subject_id'])
				$class_select .= '<option value="#'.$class_info['class_id'].'">'.$class_info['entity'].' (C'.$class_info['class_id'].')</option>';
			}
			elseif($class_info['class_id']==$class_id) {#for class centered rule inspector show only this class
				
				$class_select .= '<option value="#'.$class_info['class_id'].'">'.$class_info['entity'].'</option>';
				$class_select .= '<option value="'.str_replace('&class_id='.$_REQUEST['class_id'],'', $action['inspectrules']).'">(Choose from all collections)</option>';
			}
		}
		$class_select .= '</select>';
		
		
		}
		
		#verb action. Default is display instances from s3dbVerb.
		if($_REQUEST['literal_verb'] || ($_REQUEST['action']=='edit' && ($_REQUEST['literal_verb'])))

		{
			$verb_select .= $verb_input;
			$verb_select .= '<br />';
			$verb_select .= '<input type="button" name="edit_item_verb" value="Choose from Items" onClick="window.location=\''.str_replace(array('literal_verb=1', 'item_verb=0'), array('', ''), $action['inspectrules']).'\'">';
		
		}

		elseif($_REQUEST['any_item'] || $_REQUEST['edit_item_verb'])
		{
				$verb_input_name = 'Verb_id';
				$verb_select .= str_replace('name="verb"', 'name="verb_id"', $verb_input);
				$verb_select .= '<input type="button" name="edit_item_verb" value="Choose from Verb Class" onClick="window.location=\''.str_replace(array('literal_verb=1', 'item_verb=0'), array('', ''), $action['inspectrules']).'\'">';
				
				
		
		}
		else {
			
			if($_REQUEST['rule_id'])
			{$rule_info = URIinfo('R'.$_REQUEST['rule_id'], $user_id, $key, $db);
			$selected = array('instance_id'=>$rule_info['verb_id'], 'notes'=>$rule_info['verb']);
			}
			$verb_select .= verbInputSelect(compact('classes', 'user_id', 'db', 'selected', 'project_id'));
			#echo '<pre>';print_r($verb_select);exit;
		}
	
		

		if($_REQUEST['literal_object'] || ($_REQUEST['action']=='edit' && $rule_info['object_id']==''))
			{
			$validation_input=$rule['validation']; 
			$validation_disabled = ""; 
			$object_select .= $object_input;
			$object_select .= '<br />';
			$object_select .= '<input type="button" name="edit_class_object" value="Choose from Collections" onClick="window.location=\''.str_replace(array('literal_object=1'), array(''), $action['inspectrules']).'&class_object=1\'">';
			}
		
		elseif($_REQUEST['any_class'] || $_REQUEST['edit_class_object'])
			{		
			$s3ql=compact('user_id','db');
			$s3ql['from']='collections';
			$s3ql['order_by']='entity';
			$allclasses = S3QLaction($s3ql);
			
			$object_select .= '<select name = "object_id" onChange="window.location=this.options[this.selectedIndex].value">';
			if(is_array($classes))
			{foreach ($allclasses as $class_info) {
				$object_select .= '<option value="#'.$class_info['class_id'].'">'.$class_info['entity'].' (C'.$class_info['class_id'].')</option>';
			}
			}
			$object_select .= '<option value="'.str_replace(array('literal_object=0', 'class_object=1'), array('', ''), $action['inspectrules']).'&literal_object=1">(New)</option>';
			$object_select .= '<option value="'.str_replace(array('literal_object=1'), array(''), $action['inspectrules']).'&any_class=1">(View all collections)</option>';
			$object_select .= '<option value="'.str_replace(array('&any_class=1'), array('&any_class=0'), $action['inspectrules']).'">(View only project collections)</option>';
			$object_select .= '</select>';
		}
		
		else {
			if($_REQUEST['rule_id'])
			{$rule_info = URIinfo('R'.$_REQUEST['rule_id'], $user_id, $key, $db);
			$selected = array('class_id'=>$rule_info['object_id'], 'entity'=>$rule_info['object']);
			}
			
			$object_select .= objectInputSelect(compact('classes', 'user_id', 'db', 'selected'));
			

		}
		?>
		<script type="text/javascript">
		function  verbSelected() {
		var verb_id = document.getElementById('verb_id');
		
		var selected = verb_id.options[verb_id.selectedIndex].value;
		if (selected=='new') {
			
			document.getElementById('verb_holder').innerHTML = '<input type="text" name="verb" id="verb"><input type="button" name="edit_item_verb" value="Choose from Items" onClick="window.location=window.location.href.replace(\'literal_verb=1\',\'\').replace(\'item_verb=0\',\'\')">';

		}
		else if(selected=='other'){
			window.location = window.location.href.replace('literal_verb=1','').replace('item_verb=0','') +'&any_item=1';
		}
		else if(selected=='ontology'){
			
			document.getElementById('verb_holder').innerHTML = '<input type="text" name="verb" id="verb" class="bp_form_complete-all-name" size="30">Type 3 or more letters<input type="button" name="edit_item_verb" value="Choose from Items" onClick="window.location=window.location.href.replace(\'literal_verb=1\',\'\').replace(\'item_verb=0\',\'\')">';

			// Grab the specific scripts we need and fires it start event
			jQuery.getScript("http://bioportal.bioontology.org/javascripts/JqueryPlugins/autocomplete/crossdomain_autocomplete.js", function(){
				formComplete_setup_functions();
			});
			
			
			
		}
		}

		function objectSelected()
		{
		var obj_id = document.getElementById('object_id');
		
		var selected = obj_id.options[obj_id.selectedIndex].value;
		if (selected=='new') {
			
			document.getElementById('object_holder').innerHTML = '<input type="text" name="object" id="object" class="bp_form_complete-all-name ac_input" ><input type="button" name="edit_item_verb" value="Choose from Collections" onClick="window.location=window.location.href.replace(\'literal_object=1\',\'\')">';
			//now for validation field, remove "UID" and allow any validation
			document.getElementById('validation').value = '';
			document.getElementById('validation').disabled = false;
			
			// Grab the specific scripts we need and fires it start event
			jQuery.getScript("http://bioportal.bioontology.org/javascripts/JqueryPlugins/autocomplete/crossdomain_autocomplete.js", function(){
				formComplete_setup_functions();
			});

		}
		else if(selected=='all'){
			window.location = window.location.href +'&any_class=1';
			//now for validation field
			document.getElementById('validation').value = 'UID';
			document.getElementById('validation').disabled = true;
		}
		else if(selected=='project_col') {
			
			window.location = window.location.href.replace('&any_class=1','&any_class=0');
			//now for validation field, remove "UID" and allow any validation
			document.getElementById('validation').value = 'UID';
			document.getElementById('validation').disabled = true;
		}
		}
		</script>

		<?php

		echo '<form method="POST" name="insertAcl" action="ruleinspector.php?key='.$key.'&project_id='.$project_id.'&class_id='.$class_id.'" autocomplete="on">';
		echo '<table class="edit_rule" width="100%"  align="center" border="0">
				<tr><td class="message" colspan="9"><br />'.$action_message.'</td></tr>
				<tr bgcolor="#80BBFF"><td colspan="9" align="center">'.$edit_message.'<input type="hidden" name="rule_id" value="'.$rule_id.'"><input type="hidden" name="project_id" value="'.$project_id.'"></td></tr>
				<tr class="odd" align="center">
				<td  width="10%">Rule_id</td>
						<td  width="10%">Created On</td>
						<td width="10%">Subject<sup class="required">'.$subject_required.'</sup></td>
						<td  width="10%">'.$verb_input_name.'<sup class="required">'.$verb_required.'</sup></td>
						<td  width="10%">Object<sup class="required">'.$object_required.'</sup></td>
						<td  width="10%">Validation</td>
						<td  width="15%">Notes</td>
						<td  width="10%">Action</td>
					</tr>
					<tr valign="top" class="entry">

						<td  width="10%">'.$rule_id.'</td>
						<td  width="10%">'.$created_on.'&nbsp;</td>';
#						<td  width="10%">'.$subject_input.'</td> 
			echo		'<td  width="10%">'.$class_select.'</td> ';
			#echo		'<td  width="10%">'.$verb_input.'<a href="'.$action['peekverb'].'" target="_blank"><I>Select an instance</I></a></td>';
			echo 	'<td  width="10%" id="verb_holder">'.$verb_select.'</td>';
			echo '	<td  width="10%" id="object_holder">'.$object_select.'</td>
	
						<td  width="15%"><input type="text" name="validation" id="validation" style="background: lightyellow" rows="2"cols="20" value="'.$validation_input.'" '.$validation_disabled.'></td>
						<td  width="15%"><textarea name="notes" style="background: lightyellow" rows="2"cols="20">'.$notes_input.'</textarea></td>
						<td width="10%" align="center">
						
						<input type="submit" name="'.$action_name.'" value="'.$action_value.'"></td>
					</tr>
					<tr>
					<td><input type="submit" name="'.$action_name.'" value="'.$action_value.'"></td><td><input type="button" value="Add Remote Rule" size="20" onClick="window.location=\''.$action['remoterule'].'\'"></td>
					</tr>
					<tr><td  colspan="9"><br /><br />
					'.$aclGrid.'
					</td></tr>																								  
				</table></form>';

	}

	
	   

function verbInputSelect($V)
	{extract($V);

		
		$action = $GLOBALS['action'];
		$verb_select .= '<select name = "verb_id" id="verb_id" onChange="verbSelected()">';

		
		if($selected['notes']!='' && $selected['notes']!='0')
		{$verb_select .= '<option value="#'.$selected['instance_id'].'" selected>'.$selected['notes'].' (I'.$selected['instance_id'].')</option>';
		}

		if(!is_array($classes) || $_REQUEST['class_id']!='')
		{
		$s3ql=compact('user_id','db');
		$s3ql['from']='collections';
		$s3ql['where']['project_id']=$project_id;
		$classes = S3QLaction($s3ql);
		
		}
		
		if(is_array($classes))
				foreach ($classes as $class_info) {
					
					if($class_info['entity']=='s3dbVerb' && $class_info['project_id'] == $_REQUEST['project_id'])
						{
						
						$s3ql=compact('user_id','db');
						$s3ql['from']='items';
						$s3ql['where']['collection_id']=$class_info['collection_id'];
						$s3ql['order_by']='notes asc';
						$verbs = S3QLaction($s3ql);
						#echo '<pre>';print_r($s3ql);
						#echo '<pre>';print_r($verbs);
						#exit;
						#echo '<pre>';print_r($selected);
						#echo '<pre>';print_r($verbs);
						if(is_array($verbs))
							foreach ($verbs as $verbItem) {
							
							#if($verbItem['instance_id']!=$selected['instance_id']){
							$verb_select .= '<option value="#'.$verbItem['instance_id'].'">'.(($verbItem['notes']!='')?($verbItem['notes'].' (I'.$verbItem['instance_id'].')'):'(I'.$verbItem['instance_id'].')').'</option>';	
							#}
							}
					
						}
						
				}
		if($verbs=='')
		$verb_select .= '<option value="#"></option>';
		#$verb_select .= '<option value="'.str_replace(array('literal_verb=0', 'item_verb=1'), array('', ''), $action['inspectrules']).'&literal_verb=1">(New)</option>';
		$verb_select .= '<option value="new">(New)</option>';
		$verb_select .= '<option value="ontology">(Select from a Bio-Ontology!)</option>';
		$verb_select .= '<option value="other">(Item_id from another collection)</option>';
		$verb_select .= '</select>';

	return ($verb_select);
	}

function objectInputSelect($O)
{extract($O);
$action = $GLOBALS['action'];	
	$object_select .= '<select name = "object_id" id="object_id" onChange="objectSelected()">';

	if($selected['class_id']!='')
	$object_select .= '<option value="#'.$selected['class_id'].'" selected>'.$selected['entity'].'</option>';

	#echo ($_REQUEST['project_id']!='' && !$_REQUEST['any_class']);
	if($_REQUEST['project_id']!='' && !$_REQUEST['any_class'])
	{
	$s3ql=compact('user_id','db');
	$s3ql['from']='collections';
	$s3ql['where']['project_id']=$_REQUEST['project_id'];
	$classes = S3QLaction($s3ql);
	
	}
	
	if(is_array($classes))
			{foreach ($classes as $class_info) {
				if($class_info['class_id']!=$selected['class_id'])
				$object_select .= '<option value="#'.$class_info['class_id'].'">'.$class_info['entity'].' (C'.$class_info['class_id'].')</option>';
			}
			}
			#$object_select .= '<option value="'.str_replace(array('literal_object=0'), array('literal_object=1'), $action['inspectrules']).'&literal_object=1">(New)</option>';
			$object_select .= '<option value="new">(New)</option>';
			$object_select .= '<option value="all">(View all collections)</option>';
			$object_select .= '<option value="project_col">(View only project collections)</option>';
			$object_select .= '</select>';
return ($object_select);
}
		

?>