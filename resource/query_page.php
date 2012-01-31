<?php

#querypage.php is a query interface for the rules of a specific class. Its purpose is to send the queried information into queryresult
	#Includes links to edit and delete resource, as well as edit rules
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
	
#just to know where we are...
$thisScript = end(explode('/', $_SERVER['SCRIPT_FILENAME'])).'?'.$_SERVER['argv'][0];

$key = $_GET['key'];

#echo '<pre>';print_r($_GET);
#Get the key, send it to check validity

include_once('../core.header.php');

#if($key) $user_id = get_entry('access_keys', 'account_id', 'key_id', $key, $db);
#	else $user_id = $_SESSION['user']['account_id'];
 
#Universal variables
$project_id = $_REQUEST['project_id'];

$rule_id = $_REQUEST['rule_id'];
$class_id = ($_REQUEST['collection_id']!='')?$_REQUEST['collection_id']:$_REQUEST['class_id'];
$uid = 'C'.$class_id;
if($rule_id=='' && $class_id=='')
{
echo "Please specify a class_id or a rule_id".
exit;
}

if($rule_id!='')
{
$rule_info = URIinfo('R'.$rule_id, $user_id, $key, $db);
if(!$rule_info['view'])
	{echo "User does not have access to this rule";
		exit;
	}
}

if($class_id)
{
$collection_info = URIinfo('C'.$class_id, $user_id, $key, $db);

#$pl = permission4Resource(array('uid'=>'C'.$class_id, 'shared_with'=>'U'.$user_id, 'db'=>$db, 'user_id'=>$user_id));
#$info['C'.$class_id] = URIinfo('C'.$class_id, $user_id, $key, $db);

#$pl = permission_level($pl,'C'.$class_id, $user_id, $db);

if(!$collection_info['view'])
	{echo "User does not have access to this class";
		exit;
	}
}


#
#define a few usefull html vars

	if($_GET['page']!='' )
                $_SESSION['current_page'] = $_GET['page'];
	else
     $_SESSION['current_page'] = 1; 
     $_SESSION['sqlquery'] = '';
     $_SESSION['query_result'] = '';
     $_SESSION['used_rule'] = '';
	 $_SESSION['previous_verb'] ='';
	 $_SESSION['current_color']='0';
	
	
	$entity = $class_info['entity'];
		
		
	$s3ql=compact('user_id','db');
	$s3ql['from'] = 'rules';
	if($rule_id)
	$s3ql['where']['rule_id'] = $rule_id;
	elseif($class_id){
	$s3ql['where']['subject_id'] = $class_id;
	$s3ql['where']['object'] = "!=UID";
	}
	if($_REQUEST['project_id'])
	$s3ql['where']['project_id']=$_REQUEST['project_id'];
	if($_REQUEST['orderBy'])
	$s3ql['order_by'] = $_REQUEST['orderBy'].' '.$_REQUEST['direction'];
	
	#echo '<pre>';print_r($s3ql);
	$rules = S3QLaction($s3ql);

#echo '<pre>';print_r($rules);exit;
	
#	if(is_array($rules))
#	{
#	#find out whter the object of this rule is a class and retrieve the rule_id in that case
#	$rules = include_all_class_id(compact('rules', 'project_id', 'db'));
	
#	}

//actions when the buttons on list all, search or clear query are clicked
	
#echo '<pre>';print_r($_SESSION);
		if($_POST['search']!='')
		{
			
			$S = compact('db', 'project_id', 'rule_id', 'resource_info', 'project_info', 'user_id');
			#echo '<pre>';print_r($S);exit;
			$_SESSION['show_me'] = get_show_me($resource_info, $rules, $db);
			$_SESSION['rule_value_pairs'] = get_rule_value_pairs($resource_info, $rules, $db);
			$_SESSION[$uid]['rules'] = $rules;
			$_SESSION[$uid]['info'] = $resource_info;
				#echo '<pre>';print_r($_SESSION);exit;
			$_SESSION['list_all']='';
			
			Header('Location:'.$action['queryresult'].'&listall=no&page=1&orderBy=created_on&direction=desc');
			exit;
			
			
			
		}
		
		elseif($_REQUEST['listall'] =='yes')
		{
			
			Header('Location:'.$action['queryresult'].'&listall=yes&page=1&orderBy=created_on&direction=desc'); #go directly do result
			exit;
		
				
		}
		else

		//if the buttons aren't clicked each reload of the page should run the following code
		{
			#include all the javascript functions for the menus...
			include('../S3DBjavascript.php');

			
			#and the short menu for the resource script
			include('../action.header.php');

			if(is_array($rules) && !empty($rules))
			$datagrid =  render_elements($rules, $acl, array('Rule_id', 'Subject', 'Verb', 'Object', 'Show', 'Value', 'Notes', 'Logic'), 'rule');
			else
			{if($classAcl == '3')
				$datagrid = 'Before query, please create rules.';
				else
				$datagrid = 'No rules have been specified to be queried. The owner of the project or a level 3 permission user can create and edit rules.';
			}

			
			
			
			
		}
			
			


echo '<form name="queryresource" method="POST" action="'.$action['querypage'].'&main_resID='.$_REQUEST['main_resID'].'&main_rule='.$_REQUEST['main_rule'].'" autocomplete="on">
<td class="message" colspan="9"></td></tr>';
?>	
</table>

<td class="message" colspan="9"></td></tr>
			<table class="resource_list" width="100%" align="center" border="0">
				<tr>
					<td>
						<table class="query_resource" width="100%" border="0">

							<tr><td class="nav_menu" colspan="9"></td></tr>
							<tr><td class="nav_menu" colspan="9"><hr size="2" align="center" color="dodgerblue"></hr></td></tr>
							<tr><td class="message" colspan="9"></td></tr>
							<tr><td class="nav_menu" colspan="9" align="left">
							<input type="submit" name="search" value="Search <?php echo $class_info['entity'] ?>">&nbsp;&nbsp;&nbsp;
							<?php
							
							echo '<input type="button" name="listall" value="List all '.$class_info['entity'].'" onClick="window.location=\''.$action['querypage'].'&listall=yes&main_resID='.$_REQUEST['main_resID'].'&main_rule='.$_REQUEST['main_rule'].'\'">&nbsp;&nbsp;&nbsp;';
							echo '<input type="button" name="clearquery" value="Clear Query" onClick="window.location=\''.$action['querypage'].'\'"><br /><br /><b /></td></tr>';
							?>

							<tr><td colspan="9">Note: Regular Expression can be used in the specification of query. See examples <a href="#" onClick="window.open('../docs/regularExpressionExample.html', 'help', 'width=450, height=600, location=no, titlebar=no, scrollbars=yes, resizable=yes')" title="Examples of Regular Expression">here</a></td></tr>
							<tr><td colspan="6">Available Rules: <?php echo count($rules)?></td>
							<td align="right">Number of Results Per Page
							<select name="num_per_page" onChange="window.location=this.options[this.selectedIndex].value">

							<?php
				
							$num_per_page = array('10','50', '100', '200', '400', '600', '1000');
							$selected[$_REQUEST['num_per_page']] = 'selected';
				
							foreach($num_per_page as $num)
							{
							
							echo '<option value="'.$action['querypage'].'&num_per_page='.$num.'" '.$selected[$num].'>'.$num.'</option>';
							}
							echo '</select></tr>';
											
				
							?>
                                        

	
	<?php
	echo $datagrid;
	?>
</form>
</table>

