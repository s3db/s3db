<?php


include_once(S3DB_SERVER_ROOT.'/s3dbcore/datamatrix.php');
include_once(S3DB_SERVER_ROOT.'/s3dbcore/callback.php');


function s3db_display($datasource, $columns)
{
	if($_GET['num_per_page']=='') $how_many = 50;
	else
		$how_many = $_GET['num_per_page'];

	if(is_array($datasource)) $totalElements = count($datasource);
	else $totalElements = '1';

	if(intVal($how_many) < $totalElements)
			$dg =& new Structures_DataGrid($how_many);
	else
		   $dg =& new Structures_DataGrid(50);	
	$dg->bind($datasource);
       // Define DataGrid's columns  
	  
	   foreach ($columns as $col_name) {
		$dg->addColumn(new Structures_DataGrid_Column($col_name, null, null, array('width'=>(100/count($columns)).'%', 'align'=>'left'), null, 'printCol(colname='.$col_name.')'));
		
		#$dg->addColumn(new Structures_DataGrid_Column($col_name, null, null, array('width'=>'10%', 'align'=>'left'), null, 'printCol(colname='.$col_name.')'));
	   }
	  
	// Define the Look and Feel
		
		$dg->renderer->setTableAttribute('border', '1px');	
		
		$dg->renderer->setTableHeaderAttributes(array('bgcolor'=>'lightyellow'));		
		$dg->renderer->setTableEvenRowAttributes(array('bgcolor'=>'#FFFFFF'));		
		$dg->renderer->setTableOddRowAttributes(array('bgcolor'=>'#EEEEEE'));		
		$dg->renderer->setTableAttribute('width', '100%');		
		$dg->renderer->setTableAttribute('align', 'center');		
		$dg->renderer->setTableAttribute('cellspacing', '0');		
		$dg->renderer->setTableAttribute('cellpadding', '4');		
		$dg->renderer->setTableAttribute('class', 'datagrid');		
		$dg->renderer->sortIconASC = '&uarr;';		
		$dg->renderer->sortIconDESC = '&darr;';	

		$htmloutput =  $dg->render();
		$htmloutput .= $dg->renderer->getPaging();

		return $htmloutput;
}

function printCol($params)
{extract($params);
return ($record[$params['colname']]);
	
}

function render_elements($datasource, $acl, $columns, $table, $new=0, $uid='', $how_many=50)
        {
			#echo '<pre>';print_r($datasource);
            
          	
			$_SESSION['num_rules'] = count($datasource);
            #Set the colors for the diff vers/objects
			$_SESSION['current_color']='0';
			$_SESSION['previous_verb']='';
			$user_id = 	$_SESSION['user']['account_id'];
		// Create the DataGrid, bind it's Data Source
			
				if($_GET['num_per_page']!='') 
					$how_many = $_GET['num_per_page'];
				#else
					#$how_many = $_GET['num_per_page'];
               
				if(is_array($datasource)) $totalElements = count($datasource);
				else $totalElements = '1';
				
				
				if(intVal($how_many) <= $totalElements)
                        $dg =& new Structures_DataGrid($how_many);
                else
                       $dg =& new Structures_DataGrid(50);
			  	
				 
                // Create the DataGrid, bind it's Data Source
              #  $dg =& new Structures_DataGrid(100); // Display 100 per page
              
			 #  echo '<pre>';print_r($columns);
			   
				$dg->bind($datasource);
                // Define DataGrid's columns            
                
				#cols for users
				if(in_array('User ID', $columns))
				$dg->addColumn(new Structures_DataGrid_Column('User ID', null, (($_REQUEST['class_id']!='' || $_REQUEST['rule_id']!='' || $_REQUEST['project_id']!='')?null:'account_id'), array('width'=>'10%', 'align'=>'left'), null, 'printUserID()'));
				
                if(in_array('User Name', $columns))
				$dg->addColumn(new Structures_DataGrid_Column('User Name', null, (($_REQUEST['class_id']!='' || $_REQUEST['rule_id']!='' || $_REQUEST['project_id']!='')?null:'account_uname'), array('width'=>'20%', 'align'=>'left'), null, 'printAccountUserName()'));
				if(in_array('Login', $columns))
				$dg->addColumn(new Structures_DataGrid_Column('Login', null, (($_REQUEST['class_id']!='' || $_REQUEST['rule_id']!='' || $_REQUEST['project_id']!='')?null:'account_lid'), array('width'=>'15%', 'align'=>'left'), null, 'printAccountLID()'));

				if(in_array('Account Status', $columns))
				$dg->addColumn(new Structures_DataGrid_Column('Account Status', null, 'account_status', array('width'=>'20%', 'align'=>'left'), null, 'printAccountStatus()'));

				#cols for projects
				if(in_array('Project ID', $columns))
				$dg->addColumn(new Structures_DataGrid_Column('Project ID', null, 'project_id', array('align'=>'left'), null, 'printProjectID()'));
				if(in_array('Project Name', $columns))
				$dg->addColumn(new Structures_DataGrid_Column('Project Name', null, 'project_name', array('align'=>'left'), null, 'printProjectName()'));
				if(in_array('Project Description', $columns))
				$dg->addColumn(new Structures_DataGrid_Column('Project Description', null, 'project_description', array('align'=>'left'), null, 'printProjectDescription()'));
				if(in_array('Created Date', $columns))
				$dg->addColumn(new Structures_DataGrid_Column('Created Date', null, 'created_on', array('align'=>'left'), null, 'printCreatedOn()'));
								
				
				
				
				
				#cols for groups
				if(in_array('Group ID', $columns))
				$dg->addColumn(new Structures_DataGrid_Column('Group ID', null, 'account_lid', array('width'=>'15%', 'align'=>'left'), null, 'printUserID()'));
				
				if(in_array('Group Name', $columns))
				$dg->addColumn(new Structures_DataGrid_Column('Group Name', null, 'account_lid', array('width'=>'60%', 'align'=>'left'), null, 'printLoginID()'));
				if(in_array('Login ID', $columns))
				$dg->addColumn(new Structures_DataGrid_Column('Login ID', null, 'login_id', array('width'=>'15%', 'align'=>'left'), null, 'printLoginID()'));
				
				#cols for access log
				if(in_array('Login Time', $columns))
				$dg->addColumn(new Structures_DataGrid_Column('Login Time', null, 'login_timestamp',  array('width'=>'20%', 'align'=>'left'), null, 'printLoginTime()'));
				
				if(in_array('Login From', $columns))
				$dg->addColumn(new Structures_DataGrid_Column('Login From', null, 'ip', array('width'=>'20%', 'align'=>'left'), null, 'printClientIP()'));

				#Cols for the key
				if(in_array('Key', $columns))
				$dg->addColumn(new Structures_DataGrid_Column('Key', null, 'key_id', array('width'=>'15%', 'align'=>'left'), null, 'printKey()'));
				if(in_array('Requested By', $columns))
				$dg->addColumn(new Structures_DataGrid_Column('Requested By', null, 'account_id', array('width'=>'12%', 'align'=>'left'), null, 'printAccount_uname()'));
				if(in_array('Project', $columns))
				$dg->addColumn(new Structures_DataGrid_Column('Project', null, 'account_id', array('width'=>'15%', 'align'=>'left'), null, 'printProject_name()'));
				if(in_array('Expires', $columns))
				$dg->addColumn(new Structures_DataGrid_Column('Expires', null, 'expires', array('width'=>'15%', 'align'=>'left'), null, 'printExpiration_Date()'));
				if(in_array('UID', $columns))
				$dg->addColumn(new Structures_DataGrid_Column('UID', null, 'UID', array('width'=>'15%', 'align'=>'left'), null, 'printID()'));

				
								
				#Cols for editing user in project
				
				 if(in_array('Access Control List', $columns))
                $dg->addColumn(new Structures_DataGrid_Column('Access Control List', null, null, array('align'=>'left'), null, 'printACL_options($new='.$new.', $uid='.$uid.', $user='.$user_id.')'));
				  if(in_array('Permissions', $columns))
                $dg->addColumn(new Structures_DataGrid_Column('Permissions', null, null, array('align'=>'left'), null, 'printACL()'));
				
				
				#Cols for rules table
				if(in_array('Owner', $columns))
				$dg->addColumn(new Structures_DataGrid_Column('Owner', null, 'created_by', array('width'=>'5%', 'align'=>'left', 'valign'=>'top'), null, 'printOwner()'));
				if(in_array('Rule_id', $columns))
				$dg->addColumn(new Structures_DataGrid_Column('Rule_id', null, 'rule_id', array('align'=>'left'), null, 'printrule_id()'));
				if(in_array('CreatedOn', $columns))
				$dg->addColumn(new Structures_DataGrid_Column('Created On', null, 'created_on', array('width'=>'5%', 'align'=>'left'), null, 'printCreatedOn()'));
				if(in_array('Rule', $columns))
				$dg->addColumn(new Structures_DataGrid_Column('Rule', null, 'rule_id', array('align'=>'left'), null, 'printrule()'));
				if(in_array('Subject', $columns))
				$dg->addColumn(new Structures_DataGrid_Column('Subject', null, 'subject', array('align'=>'left'), null, 'printsubject()'));
				if(in_array('SubjectAndId', $columns))
				$dg->addColumn(new Structures_DataGrid_Column('Subject', null, 'subject', array('align'=>'left'), null, 'printsubjectAndId()'));
				if(in_array('Verb', $columns))
				$dg->addColumn(new Structures_DataGrid_Column('Verb', null, 'verb', array('align'=>'left'), null, 'printverbinColor()'));
				if(in_array('VerbAndId', $columns))
				$dg->addColumn(new Structures_DataGrid_Column('Verb', null, 'verb', array('align'=>'left'), null, 'printverbAndId()'));
				if(in_array('Object', $columns))
				$dg->addColumn(new Structures_DataGrid_Column('Object', null, 'object', array('align'=>'left'), null, 'printobject()'));
				if(in_array('ObjectAndId', $columns))
				$dg->addColumn(new Structures_DataGrid_Column('Object', null, 'object', array('align'=>'left'), null, 'printobjectAndId()'));
				if(in_array('Validation', $columns))
				$dg->addColumn(new Structures_DataGrid_Column('Validation', null, 'validation', array('align'=>'left'), null, 'printvalidation()'));
				
				#Cols for queriable rules
				if(in_array('Show', $columns))
				$dg->addColumn(new Structures_DataGrid_Column('Show<br><input type=button value="Check All" 
				onClick="this.value=check()">', null, null, array('width'=>'5%', 'align'=>'left','valign'=>'top'), null, 'printShowMe()'));
				if(in_array('Value', $columns))
				$dg->addColumn(new Structures_DataGrid_Column('Value', null, null, array('width'=>'20%', 'align'=>'left','valign'=>'top'), null, 'printInputBox()'));
				if(in_array('Logic', $columns))
				#$dg->addColumn(new Structures_DataGrid_Column('Logic', null, null, array('width'=>'20%', 'align'=>'left','valign'=>'top'), null, 'printLogic()'));

				#Cols for list instances
				in_array('ResourceID', $columns);
				if(in_array('ResourceID', $columns))
				{ $dg->addColumn(new Structures_DataGrid_Column('ID', null, 'resource_id', array('width'=>'10%', 'align'=>'left', 'valign'=>'top'), null, 'printResourceID()'));}
				if(in_array('ResourceNotes', $columns))
                $dg->addColumn(new Structures_DataGrid_Column('Notes', null, 'notes', array('width'=>'13%', 'align'=>'left', 'valign'=>'top'), null, 'printResourceNotes()'));
				if(in_array('Form', $columns))
                $dg->addColumn(new Structures_DataGrid_Column('Form', null, null, array('width'=>'8%', 'align'=>'left', 'valign'=>'top'), null, 'printFormLink()'));
                if(in_array('Statements', $columns))
				$dg->addColumn(new Structures_DataGrid_Column('Statements', null, null, array('align'=>'left'), null, 'printStatements()'));
				
                
              	 if(in_array('Status', $columns))
				 $dg->addColumn(new Structures_DataGrid_Column('Status', null, null, array('align'=>'left'), null, 'printStatus()'));
				if(in_array('Requested_on', $columns))
				$dg->addColumn(new Structures_DataGrid_Column('Requested_on', null, null, array('align'=>'left'), null, 'printRequested_on()'));
				if(in_array('Notes', $columns))
				$dg->addColumn(new Structures_DataGrid_Column('Notes', null, null, array('align'=>'left'), null, 'printNotes()'));
				
				if(in_array('Actions', $columns))
			{		if ($table=='access_rules')
						$dg->addColumn(new Structures_DataGrid_Column('Actions', null, null, array('align'=>'left'), null, 'printActionLinkPermission()'));
					elseif($table=='access_keys')
						$dg->addColumn(new Structures_DataGrid_Column('Actions', null, null, array('align'=>'left'), null, 'printActionLinkKeys()'));
					elseif($table=='rule')
						$dg->addColumn(new Structures_DataGrid_Column('Actions', null, null, array('align'=>'left'), null, 'printActionLinkRules()'));
					elseif($table == 'groups')
						$dg->addColumn(new Structures_DataGrid_Column('Actions', null, null, array('align'=>'left'), null, 'printActionLinkGroup()'));
					elseif($table == 'project')
					$dg->addColumn(new Structures_DataGrid_Column('Actions', null, null, array('align'=>'left'), null, 'printActionLinkProject()'));
					elseif ($table=='users') {
					$dg->addColumn(new Structures_DataGrid_Column('Actions', null, null, array('align'=>'left'), null, 'printActionLinkUser()'));
					}
			}	


                // Define the Look and Feel
				if(ereg('(rule|groups|statements|project|users)', $table))
				$dg->renderer->setTableAttribute('border', '0px');	
				else
				$dg->renderer->setTableAttribute('border', '1px');	
				
				$dg->renderer->setTableHeaderAttributes(array('bgcolor'=>'lightyellow'));		
				$dg->renderer->setTableEvenRowAttributes(array('bgcolor'=>'#FFFFFF'));		
				$dg->renderer->setTableOddRowAttributes(array('bgcolor'=>'#EEEEEE'));		
				$dg->renderer->setTableAttribute('width', '100%');		
				$dg->renderer->setTableAttribute('align', 'center');		
				$dg->renderer->setTableAttribute('cellspacing', '0');		
				$dg->renderer->setTableAttribute('cellpadding', '4');		
				$dg->renderer->setTableAttribute('class', 'datagrid');		
				$dg->renderer->sortIconASC = '&uarr;';		
				$dg->renderer->sortIconDESC = '&darr;';	

                $htmloutput =  $dg->render();
                $htmloutput .= $dg->renderer->getPaging();

                return $htmloutput;
        }

		function printLoginTime($params)
	{
		extract($params);
		return substr($record['login_timestamp'], 0, 19);
	}	
	
		
	function printClientIP($params)
	{
		extract($params);
		return $record['ip'];
	}
		function printAccount_uname($params)
        {
                extract($params);
      			return $record['account_name'];
         

        }


        function printrule_id($params)
        {
                extract($params);
                return $record['rule_id'];
        }

		function printrule($params)
        {
                extract($params);
                #return $record['rule'];
				return $record['rule_name'];
        }
		
		function printSubject($params)
        {
                extract($params);
                return $record['subject'];
        }

		function printSubjectAndId($params)
        {
                extract($params);
                return ($record['subject'].' (C'.$record['subject_id'].')');
        }
		
		function printverb($params)
        {
                extract($params);
                return $record['verb'];
        }
		
		function printverbAndId($params)
        {
           if(is_array($params))
			   extract($params);
		   else $record['verb'] = $params;
		
			if($_SESSION['previous_verb'] =='')
			{
				   $_SESSION['previous_verb'] = $record['verb'];
				   $_SESSION['current_color'] = '0';
			}
         
                else if($_SESSION['previous_verb']!=$record['verb'])
                {
                        $_SESSION['previous_verb'] = $record['verb'];
                        $_SESSION['current_color'] = intVal($_SESSION['current_color']) + 1;
                }
                switch(intVal($_SESSION['current_color'])%3)
                {
                        case 0:
                                return '<font color="red">'.$record['verb'].'</font>';
                        case 1:
                                return '<font color="green">'.$record['verb'].'</font>';
                        case 2:
                                return '<font color="blue">'.$record['verb'].'</font>';
                }
               
        }
 
		
		function printobject($params)
        {
                extract($params);
                return $record['object'];
        }
		
		function printobjectAndId($params)
        {
                extract($params);
                return ($record['object'].(($record['object_id']!='')?(' (C'.$record['object_id'].')'):''));
        }
		
		function printvalidation($params)
        {
                extract($params);
                return $record['validation'];
        }
		
		function printKey($params)
        {
                extract($params);
				
                return $record['key_id'];
        }
		function printExpiration_Date($params)
		{		
				extract($params);
                return $record['expires'];
		
		}


	
	function printProjectName($params)
	{global $action;
		extract($params);
		if($record['project_status'] == 'A')
			return '<a href="'.$action['project'].'&project_id='.$record['project_id'].'" title="Working on resources associated with project ('.$record['project_name'].')">'.$record['project_name'].'</a>';
		else 
			return $record['project_name'];
	}	
	
	function printProjectDescription($params)
	{
		extract($params);
		return $record['project_description'];
	}
	
	 function printPermissionNotes($params)
        {
                extract($params);
                return $record['notes'];
     
        }

		 function printstatus($params)
        {
                extract($params);
				if ($record['status']=='Denied') $record['status'] = '<font color=red>'.$record['status'].'</font>';
                return $record['status'];
        }

	 function printRequested_on($params)
        {
                extract($params);
                return $record['requested_on'];
     
        }
	function printActionLinkProject($params)
        {
		extract($params);
       	$uid_info = uid($record['project_id']);
		#if($record['acl'] == '3')
		if($record['change'])
			{
			if($uid_info['Did']==$GLOBALS['Did'])
			$out =  printEditLinkProject($params).' ';
			$out .=  printDeleteLinkProject($params);
			return ($out);
			}
		else
			return "";
        }
	
	function printEditLinkProject($params)
	{
		global $action;
		extract($params);
	
			return '<a href="'.$action['editproject'].'&project_id='.$record['project_id'].'" title="Edit project ('.$record['project_name'].') information">Edit</a>';
		
	}	
	
	function printDeleteLinkProject($params)
	{	global $action;
		extract($params);
		//if($record['project_ownership'] == 'owned')
			return '<a href="'.$action['deleteproject'].'&project_id='.$record['project_id'].'" title="Delete project ('.$record['project_name'].')">Delete</a>';
			//return '<a href="deleteproject.php?id='.$record['project_id'].'">Delete</a>';
		//else
	//		return '&nbsp;';	
	}	
	
	function printActionLinkPermission($params)
        {
            
				extract($params);
		//echo $record['owner'];
		//echo $_SESSION['user']['account_lid'];
		
		#if($acl == '3')
			
			{#If this project requested the connection, he can delete it
				if($record['status']!='deleted')
					{if ($record['project_id'] == $_REQUEST['project_id'])
					
					{
						
								
							return  printDeletePermissionLink($params);
					}
					#If the entry is from ouside project to this project, you can give permissions or not
					else
							return  printAcceptPermissionLink($params). ' '.printDenyPermissionLink($params);
					}
			}
			
			
		}

		function printActionLinkKeys($params)
		{
		  extract($params);
		
		return '<a href="access_keys.php?key_id='.$record['key_id'].'&expires='.$record['expires'].'&action=delete" title="Delete Key '.$record['key_id'].'">Delete</a>';
		
		
		}
			
			
			
	function printDisconnectPermissionLink($params)		
	{
		extract($params);
		return '<a href="sharerules.php?key='.$_REQUEST['key'].'&project_id='.$_REQUEST['project_id'].'&ext_project_id='.$record['project_id'].'&delete_rule_id='.$record['rule_id'].'&action=disconnect" title="Disconnect'.$record['rule_id'].'">Disconnect</a>';
	
	}
	
	function printDeletePermissionLink($params)
	{
		extract($params);
		return '<a href="sharerules.php?key='.$_REQUEST['key'].'&project_id='.$_REQUEST['project_id'].'&delete_rule_id='.$record['rule_id'].'&action=delete" title="Delete pending permission '.$record['rule_id'].'">Delete</a>';
		
	}	

	function printAcceptPermissionLink($params)
	{
		extract($params);
		
		return '<a	href="sharerules.php?key='.$_REQUEST['key'].'&project_id='.$_REQUEST['project_id'].'&ext_project_id='.$record['project_id'].'&accept_rule_id='.$record['rule_id'].'&action=accept" title="Accept pending permission '.$record['rule_id'].'">Accept</a>';
		
	}	

	function printDenyPermissionLink($params)
	{
		extract($params);
		return '<a href="sharerules.php?key='.$_REQUEST['key'].'&project_id='.$_REQUEST['project_id'].'&ext_project_id='.$record['project_id'].'&delete_rule_id='.$record['rule_id'].'&action=deny" title="Deny pending permission '.$record['rule_id'].'">Deny</a>';
		
		
	}	


	function render_insert_statement($index, $resource_id, $rule, $owner_project_id)
	{
		
		
		 $_SESSION['current_color']='0';
                $_SESSION['previous_verb']='';
	
		 $verb = $rule['verb'];
                 $rule_notes = preg_replace('/\(.*\)/', '', $rule['notes']);

		$stats ="";
		$stat = sprintf("\n%s\n", '<table width="100%"><tr bgcolor="lightyellow"><td colspan="3">');	
		$stat .= sprintf("%s\n", ($index+1).'. [ '.printVerbStat($verb).' | '.$rule['object'].' ]</font><br />&nbsp;&nbsp;&nbsp;&nbsp;');
		$stat .= sprintf("%s\n", '<font size-=2 color="dodgerblue">'.$rule['notes'].'</font>');
		$stat .= sprintf("%s\n", '</td></tr>');
		$stat .= sprintf("%s\n", '<tr><td><b><font color="navy" size="2">&nbsp;&nbsp;Value</font></b></td><td><b><font color="navy" size="2">&nbsp;&nbsp;Notes</font></b></td><td>&nbsp;</td>');
		
		$O = array('db'=>$_SESSION['db'], 'user_id'=>$user_id, 'project_id'=>$_REQUEST['project_id'], 'object'=>$rule['object']);
		if(object_is_resource($O))
		{
			$stat .= sprintf("%s\n", '<tr><td valign="top">');
			
			$peek ='<input type="button" name="input_'.$resource_id.'_'.$rule['rule_id'].'existvalues" value="Peek" onClick="window.open(\'existuid.php{get_proj_id}{get_res_id}&rule_id='.$rule['rule_id'].'&name=input_'.$resource_id.'_'.$rule['rule_id'].'\', \'_blank\', \'width=500, height=500, location=no, titlebar=no, scrollbars=yes, resizable=yes\')"><br />';
			
			$stat .= sprintf("%s\n", '&nbsp;&nbsp;<textarea type="text" style="background: lightyellow" name="input_'.$resource_id.'_'.$rule['rule_id'].'" cols="20"></textarea>&nbsp;&nbsp;'.$peek.'</td>');
			
			$stat .= sprintf("%s\n", '<td valign="top"><textarea style="background: lightyellow" name="text_'.$resource_id.'_'.$rule['rule_id'].'" rows="2" cols="20"></textarea></td>');
			
			$stat .= sprintf("%s\n", '<td valign="top"><input type="hidden" name="resource_id" value="'.$resource_id.'"><input type="hidden" name="rule_id" value="'.$rule['rule_id'].'"><input type="submit" name="insert_'.$resource_id.'_'.$rule['rule_id'].'" value="Insert"></form>');
					
			$stat .= sprintf("%s\n", '</td></tr>');
			
			$stat .= sprintf("%s\n", '<tr><td valign="top" colspan="3">');
			
			$stat .= sprintf("%s\n", '<br />&nbsp;&nbsp;<input name="upload_input_'.$resource_id.'_'.$rule['rule_id'].'" type="file" />&nbsp;&nbsp;&nbsp;&nbsp;<input type="hidden" name="resource_id" value="'.$resource_id.'"><input type="hidden" name="rule_id" value="'.$rule['rule_id'].'"><input type="submit" name="upload_'.$resource_id.'_'.$rule['rule_id'].'" value="Upload File" /></form> ');
			
			
			$stat .= sprintf("%s\n", '</td></tr>');
		}
		else
		{
			$stat .= sprintf("%s\n", '<tr><td valign="top">');
			$stat .= sprintf("%s\n", '&nbsp;&nbsp;<textarea type="text" style="background: lightyellow" name="input_'.$resource_id.'_'.$rule['rule_id'].'" cols="20"></textarea></td>');
			$stat .= sprintf("%s\n", '<td valign="top"><textarea style="background: lightyellow" name="text_'.$resource_id.'_'.$rule['rule_id'].'" rows="2" cols="20"></textarea></td>');
			$stat .= sprintf("%s\n", '<td valign="top"><input type="hidden" name="resource_id" value="'.$resource_id.'"><input type="hidden" name="rule_id" value="'.$rule['rule_id'].'"><input type="submit" name="insert_'.$resource_id.'_'.$rule['rule_id'].'" value="Insert"></form>');
			$stat .= sprintf("%s\n", '</td></tr>');
			$stat .= sprintf("%s\n", '<tr><td valign="top" colspan="3">');
			$stat .= sprintf("%s\n", '<br />&nbsp;&nbsp;<input name="Hyperlink_ref_'.$resource_id.'_'.$rule['rule_id'].'" value = "http://" type="text" /><input type = "text" name="Hyperlink_name_'.$resource_id.'_'.$rule['rule_id'].'">&nbsp;&nbsp;&nbsp;&nbsp;<input type="hidden" name="resource_id" value="'.$resource_id.'"><input type="hidden" name="rule_id" value="'.$rule['rule_id'].'"><input type="submit" name="insert_'.$resource_id.'_'.$rule['rule_id'].'" value="Add Hyperlink"></form><BR><BR> ');
			$stat .= sprintf("%s\n", '<br />&nbsp;&nbsp;<input name="upload_input_'.$resource_id.'_'.$rule['rule_id'].'" type="file" />&nbsp;&nbsp;&nbsp;&nbsp;<input type="hidden" name="resource_id" value="'.$resource_id.'"><input type="hidden" name="rule_id" value="'.$rule['rule_id'].'"><input type="submit" name="insert_'.$resource_id.'_'.$rule['rule_id'].'" value="Upload File"></form> ');

		
			$stat .= sprintf("%s\n", '</td></tr>');

		}
		$stat .=sprintf("%s\n", '</table>');	
		$stats .= $stat;
		return $stats;
	}


	 function printVerbStat($verb)
        {
             
				if($_SESSION['previous_verb'] =='')
                        $_SESSION['previous_verb'] = $verb;
                else if($_SESSION['previous_verb']!=$verb)
                {
                        $_SESSION['previous_verb'] = $verb;
                        $_SESSION['current_color'] = intVal($_SESSION['current_color']) + 1;
                }
              
				
				switch(intVal($_SESSION['current_color'])%3)
                {
                        case 0:
                                return '<font color="red">'.$verb.'</font>';
                        case 1:
                                return '<font color="green">'.$verb.'</font>';
                        case 2:
                                return '<font color="blue">'.$verb.'</font>';
                }
        }


		#Functions to eventally be totally replaced with render_element
		function render_users($datasource, $order, $direction)
	{
		
		
		// Determine sort order and direction as well as the page to display
		$orderBy = $order;
		$dir = $direction;			
		// Create the DataGrid, bind it's Data Source
		$dg =& new Structures_DataGrid(20); // Display 20 per page

		$dg->bind($datasource);	
		// Define DataGrid's columns		
		$dg->addColumn(new Structures_DataGrid_Column('Login ID', null, 'account_lid', array('width'=>'20%', 'align'=>'left'), null, 'printLoginID()'));
	
		$dg->addColumn(new Structures_DataGrid_Column('User Name', null, 'account_uname', array('width'=>'20%', 'align'=>'left'), null, 'printUserName()'));
		$dg->addColumn(new Structures_DataGrid_Column('Created Date', null, 'created_on', array('width'=>'20%', 'align'=>'left'), null, 'printCreatedOn()'));
		$dg->addColumn(new Structures_DataGrid_Column('Action', null, null, array('align'=>'left'), null, 'printActionLinkUser()'));
		$dg->addColumn(new Structures_DataGrid_Column('Status', null, 'account_status', array('width'=>'20%', 'align'=>'left'), null, 'printAccountStatus()'));
		
		// Define the Look and Feel
		$dg->renderer->setTableHeaderAttributes(array('bgcolor'=>'lightyellow'));		
		$dg->renderer->setTableEvenRowAttributes(array('bgcolor'=>'#FFFFFF'));		
		$dg->renderer->setTableOddRowAttributes(array('bgcolor'=>'#EEEEEE'));		
		$dg->renderer->setTableAttribute('width', '100%');		
		$dg->renderer->setTableAttribute('align', 'center');		
		$dg->renderer->setTableAttribute('border', '0px');		
		$dg->renderer->setTableAttribute('cellspacing', '0');		
		$dg->renderer->setTableAttribute('cellpadding', '4');		
		$dg->renderer->setTableAttribute('class', 'datagrid');		
		$dg->renderer->sortIconASC = '&uArr;';		
		$dg->renderer->sortIconDESC = '&dArr;';	
		
		$htmloutput =  $dg->render();	
		//echo $dg->renderer->getPaging();
		$htmloutput .= $dg->renderer->getPaging();

		return $htmloutput;
	}	

	
		
	function printLoginID($params)
	{
		extract($params);
		#echo '<pre>';print_r($params);
		if($record['account_lid']!='')	$user_uname = $record['account_lid'];
		elseif($record['login_id']!='') $user_uname =$record['login_id'];
		else 
		{if(is_object($_SESSION['db']))
			$user_uname = find_user_loginID($record['account_id']);
		}
		return $user_uname;
	}	
	
	function printLastName($params)
	{
		extract($params);
		return $record['account_lastname'];
	}	

	function printUserID($params)
	{
		extract($params);
		return $record['account_id'];
	}	
	
	function printFirstName($params)
	{
		extract($params);
		return $record['account_firstname'];
	}
	
	function printFormLink($params)
	{extract($params);
		$action = $GLOBALS['action'];
		
		if($record['change']){
		if($_REQUEST['item_id'])
		return '<a href="'.str_replace('&item_id='.$_REQUEST['item_id'], '&item_id='.$record['item_id'],$action['instanceform']).'" target="_blank">Add Statements</a>';
		else {
			return '<a href="'.$action['instanceform'].'&item_id='.$record['item_id'].'" target="_blank">Add Statements</a>';	
		}
		}
		
	}
	function printUserName($params)
	{
		extract($params);
		return $record['account_uname'];
	}
	
	function printCreatedOn($params)
	{
		extract($params);
		return substr($record['created_on'], 0, 19);
	}

	function printCreatedBy($params)
        {
//                extract($params);
//                if(is_numeric($record['created_by']))
//				return find_user_loginID($record['created_by']);
//				else
				return $record['created_byID'];
        }

	
	function printActionLinkUser($params)
	{	
		extract($params);
		if($record['account_status']!='I')
		return  printEditLinkUser($params). ' '.printDeleteLinkUser($params).' '.printViewLink($params).' '.printProxyLink($params);
		else 
			return printActivateUser($params);
	}
	function printAccountStatus($params)
	{
		extract($params);
		return ($record['account_status']=='I')?'Inactive':'Active';
		#return ($record['account_status']=='A')?'Active':'Inactive';
	}

	function printActivateUser($params)
	{
	extract($params);
	$action = $GLOBALS['action'];
	
	$activate_link = '<a href="'.$action['listusers'].'&activate='.$record['account_id'].'">Activate</a>';
	return ($activate_link);
	}

	
	function printDeleteLinkUser($params)
	{
		extract($params);
		if(is_generic_admin($record['account_id']))
			return '&nbsp;';
		else
			return '<a href="deleteuser.php?id='.$record['account_id'].'" title="Delete user ('.$record['account_uname'].')">Delete</a>';
			//return '<a href="deleteuser.php?id='.$record['account_id'].'">Delete</a>';
	}	
	
	function printNothing($params)
	{
		extract($params);
		return '';
	}	
	
	function printProxyLink($params)
	{
		extract($params);
		if(is_generic_admin($record['account_id']))
			return '&nbsp;';
		else
			return '<a href="proxyuser.php?id='.$record['account_id'].'" title="Become ('.$record['account_uname'].')">Proxy</a>';
		return '';
	}	
	function printViewLink($params)
	{
		extract($params);
		return '<a href="viewuser.php?id='.$record['account_id'].'" title="View user ('.$record['account_uname'].') information">View</a>';
		//return '<a href="viewuser.php?id='.$record['account_id'].'">View</a>';
	}
	
	function is_generic_admin($account_id)
	{
		$db = $_SESSION['db'];
		$sql = "select account_lid from s3db_account where account_id='".$account_id."'";
		$db->query($sql, __LINE__, __FILE__);
		$db->next_record();
		if($db->f('account_lid') == 'Admin')
			return True;
		else	
			return False;
	}	

	function render_project_acl($datasource, $order, $direction)
        {
                #print_r($datasource);
                $orderBy = $order;
                $dir = $direction;

                // Create the DataGrid, bind it's Data Source
                $dg =& new Structures_DataGrid(20); // Display 20 per page
                $dg->bind($datasource);
                // Define DataGrid's columns            
                $dg->addColumn(new Structures_DataGrid_Column('Login', null, 'account_lid', array('width'=>'15%', 'align'=>'left'), null, 'printAccountLID()'));
                $dg->addColumn(new Structures_DataGrid_Column('User Name', null, 'account_uname', array('15%', 'align'=>'left'), null, 'printAccountUserName()'));
                $dg->addColumn(new Structures_DataGrid_Column('Permissions', null, null, array('align'=>'left'), null, 'printACL()'));

                // Define the Look and Feel
                //$dg->renderer->setTableHeaderAttributes(array('bgcolor'=>'#FFCCFF'));
                $dg->renderer->setTableHeaderAttributes(array('bgcolor'=>'lightyellow'));
                $dg->renderer->setTableEvenRowAttributes(array('bgcolor'=>'#FFFFFF'));
                $dg->renderer->setTableOddRowAttributes(array('bgcolor'=>'#EEEEEE'));
                $dg->renderer->setTableAttribute('width', '100%');
                $dg->renderer->setTableAttribute('align', 'left');
                $dg->renderer->setTableAttribute('border', '1px');
                $dg->renderer->setTableAttribute('cellspacing', '0');
                $dg->renderer->setTableAttribute('cellpadding', '4');
                $dg->renderer->setTableAttribute('class', 'datagrid');
                $dg->renderer->sortIconASC = '&uarr;';
                $dg->renderer->sortIconDESC = '&darr;';

                $htmloutput =  $dg->render();
                $htmloutput .= $dg->renderer->getPaging();

                return $htmloutput;
        }

	function printAccountLID($params)
        {
              
				extract($params);
                return $record['account_lid'];
        }

        function printAccountUserName($params)
        {
                extract($params);
                return $record['account_uname'];
        }
	 
	 function printACL($params)
        {
               
				extract($params);
				
                if($record['permissionOnResource'] =='nnn' || $record['permissionOnResource'] =='')
                        return '<B>nnn</B> - View nothing, change nothing, use nothing.';
                else if(eregi('ynn',$record['permissionOnResource']))
                        return '<B>'.$record['permissionOnResource'].'</B> - View resource, change nothing, use nothing.';
				else if(eregi('yny',$record['permissionOnResource']))
                        return '<B>'.$record['permissionOnResource'].'</B> - View resource, change nothing, use any resource.';
                else if(eregi('yss',$record['permissionOnResource']))
                        return '<B>'.$record['permissionOnResource'].'</B> - View resource, change own data, use own resources.';
                else if(eregi('ysy',$record['permissionOnResource']))
                        return '<B>'.$record['permissionOnResource'].'</B> - View resource, change own data, use any resource.';
				elseif(eregi('yyy',$record['permissionOnResource']))
						return '<B>'.$record['permissionOnResource'].'</B> - View resource, change any data, use any resource.';
				else
                return $record['permissionOnResource'];
        }

function printACL_options($params)
		
		{
			extract($params);
		   
			
			if($record['permissionOnResource'])
			{
			$localPermission = $record['permissionOnResource'];
			}
		
		 	elseif($record['assigned_permission'])
			{
			#$localPermission = has_permission(array('uid'=>$uid,'shared_with'=>'U'.$record['account_id']), $_SESSION['db']);
			$localPermission = $record['assigned_permission'];
			}
			
			if($record['account_id']==$user_id){
			$localPermission = '222';
			}
			$localPermission = str_ireplace(array('n','s','y','-'), array('0','1','2','m'), $localPermission);
			
			
			if(!$localPermission)
				$new = 1;
			else {
				$view = substr($localPermission, 0,1);
				$change = substr($localPermission, 1,1);
				$add_data = substr($localPermission, 2,1);
			   
			}
			
			
		if($new)
			{
			$checked['V']['m']='checked';
			$checked['C']['m']='checked';
			$checked['A']['m']='checked';
			}
		elseif($record['account_id']==$_SESSION['user']['account_id']){
			$checked['V']['2']='checked';
			$checked['C']['2']='checked';
			$checked['A']['2']='checked';
		}
		else {
			
			
			
			$checked = array(
					'V'=>array('0'=>(($view==''||$view=='0')?'checked':''),	
								'1'=>(($view=='1')?'checked':''), 
								'2'=>(($view=='2')?'checked':''),
								'm'=>(($view=='m')?'checked':'')),
					 'C'=>array('0'=>(($change==''||$change=='0')?'checked':''), 
								'1'=>(($change=='1')?'checked':''), 
								'2'=>(($change=='2')?'checked':''),
								'm'=>(($change=='m')?'checked':'')),
					'A'=>array('0'=>(($add_data==''||$add_data=='0')?'checked':''),
								'1'=>(($add_data=='1')?'checked':''), 
								'2'=>(($add_data=='2')?'checked':''),
								'm'=>(($add_data=='m')?'checked':''))
						);
		 }
		
		 
		 
			

			$acl.='<table class="acl">
						<tr><td>';

				$acl.='
						<table class="acl" border="2">
						<tr>
							<td><a href="#" title="Check this box if you want Global permissions">G</a> <input type="checkbox" name="global_'.$record['account_id'].'" checked></td>
							<td>View</td>
							<td>Edit</td>
							<td>Use</td>
						</tr>
						<tr>
							<td>&nbsp;&nbsp;None&nbsp;&nbsp;</td>
							<td><input type="radio" id="view_'.$record['account_id'].'_0" name ="view_'.$record['account_id'].'" value="0" '.$checked['V']['0'].'></td>
							<td><input type="radio" id="edit_'.$record['account_id'].'_0" name ="edit_'.$record['account_id'].'" value="0" '.$checked['C']['0'].'></td>
							<td><input type="radio" id ="add_data_'.$record['account_id'].'_0" name ="add_data_'.$record['account_id'].'" value="0" '.$checked['A']['0'].'></td>
						</tr>
						<tr>
							<td>&nbsp;&nbsp;Self&nbsp;<BR></td>
							<td><input type="radio" id="view_'.$record['account_id'].'_1" name ="view_'.$record['account_id'].'" value="1" '.$checked['V']['1'].'></td>
							<td><input type="radio" id="edit_'.$record['account_id'].'_1" name ="edit_'.$record['account_id'].'" value="1" '.$checked['C']['1'].'></td>
							<td><input type="radio" id="add_data_'.$record['account_id'].'_1" name ="add_data_'.$record['account_id'].'" value="1" '.$checked['A']['1'].'></td>
						</tr>
						<tr>
							<td>&nbsp;&nbsp;All&nbsp;&nbsp;</td>
							<td><input type="radio" id="view_'.$record['account_id'].'_2" name ="view_'.$record['account_id'].'" value="2" '.$checked['V']['2'].'></td>
							<td><input type="radio" id="edit_'.$record['account_id'].'_2" name ="edit_'.$record['account_id'].'" value="2" '.$checked['C']['2'].'></td>
							<td><input type="radio" id="add_data_'.$record['account_id'].'_2" name ="add_data_'.$record['account_id'].'" value="2" '.$checked['A']['2'].'></td>
						</tr>
						<tr>
							<td>Inherit</td>
							<td><input type="radio" id="view_'.$record['account_id'].'_3" name ="view_'.$record['account_id'].'" value="-" '.$checked['V']['m'].'></td>
							<td><input type="radio" id="edit_'.$record['account_id'].'_3" name ="edit_'.$record['account_id'].'" value="-" '.$checked['C']['m'].'></td>
							<td><input type="radio" id="add_data_'.$record['account_id'].'_3" name ="add_data_'.$record['account_id'].'" value="-" '.$checked['A']['m'].'></td>
						</tr>
						</table>
						';
			$acl.='</td><td>';
				
				$acl.='<table class="acl" border="0">';
				$acl.='<tr>
				<td>
				Common choices:<BR>
				<input type="radio" id="option_'.$record['account_id'].'_000" name="option_2" onclick="check_option(0,0,0,\''.$record['account_id'].'\')">View nothing, edit/add nothing (nnn)<BR>
				<input type="radio" id="option_'.$record['account_id'].'_200" name="option_2" onclick="check_option(2,0,0,\''.$record['account_id'].'\')">View all, edit/add nothing (ynn)<BR>
				<input type="radio" id="option_'.$record['account_id'].'_211" name="option_2" onclick="check_option(2,1,1,\''.$record['account_id'].'\')">View everything, edit own, add data, don\'t give permissions (yss)<BR>
				<input type="radio" id="option_'.$record['account_id'].'_212" name="option_2" onclick="check_option(2,1,2,\''.$record['account_id'].'\')">View all, edit own, add all, give permission (ysy) <BR>
				<input type="radio" id="option_'.$record['account_id'].'_222" name="option_2" onclick="check_option(2,2,2,\''.$record['account_id'].'\')">View all, edit all, add anywhere (yyy) <BR>
				<input type="radio" id="option_'.$record['account_id'].'_inherit" name="option_2" onclick="check_option(3,3,3,\''.$record['account_id'].'\')">Inherit Permission (---)<BR>
				</td>
				</tr>';
				$acl.='</table>';
			$acl.='</td></td>';
			$acl.='</table>';		
						
                return $acl;
		
		}
	   
	   function addUsers($U)
		{
		extract($U);
		

		if(is_array($users)){
			$s3ql=compact('user_id','db');
			$s3ql['insert']='user';
			$s3ql['where'][$element.'_id']=$U[$element.'_id'];
				
				#NOW ADD THE SELECTED USERS TO THE CLASS:
					#Are there users in project? They will inherit permissions directly,unless otherwise specified
					#echo '<pre>';print_r($_POST);exit;
				
					foreach ($users as $ind=>$user) {
						$account_id = str_replace('.', '_', $user['account_id']);
						$v = ($_POST['view_'.$account_id]=='m')?'-':$_POST['view_'.$account_id];
						$e = ($_POST['edit_'.$account_id]=='m')?'-':$_POST['edit_'.$account_id];
						$p = ($_POST['add_data_'.$account_id]=='m')?'-':$_POST['add_data_'.$account_id];
						
						$permLevel = $v.$e.$p;
						
						$permLevel = str_replace(array('0','1','2','m'),array('n','s','y','-'),$permLevel);

						if($_POST['global_'.$account_id]=='on'){
						   $permLevel = strtoupper($permLevel);
						}
						
						$s3ql['where']['user_id']=$user['account_id'];
						$s3ql['where']['permission_level']=$permLevel;
						
						$done=S3QLaction($s3ql);
						#echo $done;
						
						
						ereg('<error>([0-9]+)</error>.*<message>(.*)</message>', $done, $s3qlout1);
						if($s3qlout1[1]!='0')
							$message.=$s3qlout1[2];
					}
				return ($message);
		}
		
		}	
 
	function render_resources($datasource, $order, $direction)
	{
		//print_r($datasource);
		// Determine sort order and direction as well as the page to display
		$orderBy = $order;
		$dir = $direction;			
		//echo $orderBy;
		//echo $dir;
		// Create the DataGrid, bind it's Data Source
		$dg =& new Structures_DataGrid(15); // Display 20 per page
		$dg->bind($datasource);	
		// Define DataGrid's columns		
	//	$dg->addColumn(new Structures_DataGrid_Column('Project ID', null, 'project_id', array('width'=>'7%', 'align'=>'left'), null, 'printProjectID()'));
		$dg->addColumn(new Structures_DataGrid_Column('Resource ID', null, 'resource_id', array('width'=>'7%', 'align'=>'left'), null, 'printResourceID()'));
		$dg->addColumn(new Structures_DataGrid_Column('Owner', null, 'created_by', array('width'=>'5%', 'align'=>'left'), null, 'printOwner()'));
		$dg->addColumn(new Structures_DataGrid_Column('Created Date', null, 'created_on', array('width'=>'12%', 'align'=>'left'), null, 'printCreatedOn()'));
		$dg->addColumn(new Structures_DataGrid_Column('ID', null, 'uid', array('width'=>'5%', 'align'=>'left'), null, 'printID()'));
		$dg->addColumn(new Structures_DataGrid_Column('Entity', null, 'entity', array('width'=>'15%', 'align'=>'left'), null, 'printEntity()'));
		$dg->addColumn(new Structures_DataGrid_Column('Notes', null, 'notes', array('width'=>'25%', 'align'=>'left'), null, 'printNotes()'));
		$dg->addColumn(new Structures_DataGrid_Column('Clone', null, null, array('width'=>'5%', 'align'=>'left'), null, 'printCloneLink()'));
		$dg->addColumn(new Structures_DataGrid_Column('Rules', null, null, array('width'=>'5%', 'align'=>'left'), null, 'printRulesLink()'));
		$dg->addColumn(new Structures_DataGrid_Column('Statements', null, null, array('width'=>'5%', 'align'=>'left'), null, 'printStatementsLink()'));
		$dg->addColumn(new Structures_DataGrid_Column('Action', null, null, array('align'=>'left'), null, 'printActionLink()'));
		
		// Define the Look and Feel
		$dg->renderer->setTableHeaderAttributes(array('bgcolor'=>'#FFCCFF'));		
		$dg->renderer->setTableEvenRowAttributes(array('bgcolor'=>'#FFFFFF'));		
		$dg->renderer->setTableOddRowAttributes(array('bgcolor'=>'#EEEEEE'));		
		$dg->renderer->setTableAttribute('width', '100%');		
		$dg->renderer->setTableAttribute('align', 'center');		
		$dg->renderer->setTableAttribute('border', '0px');		
		$dg->renderer->setTableAttribute('cellspacing', '0');		
		$dg->renderer->setTableAttribute('cellpadding', '4');		
		$dg->renderer->setTableAttribute('class', 'datagrid');		
		$dg->renderer->sortIconASC = '&uarr;';		
		$dg->renderer->sortIconDESC = '&darr;';	
		//$dg->renderer->sortIconASC = '&#94;';		
		//$dg->renderer->sortIconDESC = '&#118;';	
		
		$htmloutput =  $dg->render();	
		//echo $dg->renderer->getPaging();
		$htmloutput .= $dg->renderer->getPaging();

		return $htmloutput;
	}	
		
	function printProjectID($params)
	{
		extract($params);
		return $record['project_id'];
	}	
	

	
	function printResourceID($params)
        {
			global $action;
            extract($params);
		
		
		$result =  '<input type="button" size="10" value="'.str_pad($record['resource_id'], 6, '0', STR_PAD_LEFT).'" onClick="window.open(\''.$action['item'] .'&item_id='.$record['resource_id'].'\', \'_blank\', \'width=700, height=600, location=no, titlebar=no, scrollbars=yes, resizable=yes\')"><br />';
		

		#$acl = find_final_acl($_SESSION['user']['account_id'], $_REQUEST['project_id'], $_SESSION['db']);
                
				
		#if($_SESSION['user']['account_id'] == $project_info['owner'] || $acl =='3' ||$record['created_by']==$_SESSION['user']['account_id'])
		#if($record['dataAcl'] =='3')
		if($record['change'])
		$result .= printEditRIDLink($params). '&nbsp;&nbsp;'. printDeleteRIDLink($params);	
		return $result;		
        }
	
	
	function printOwner($params)
	{
		extract($params);
		
		if ($record['owner']!='') $owner = $record['owner'];
		elseif ($record['created_byID']!='') $owner = $record['created_byID'];

		return $owner;
	}
	
	
	function printEntity($params)
	{
		extract($params);
		return $record['entity'];
	}
		
	function printID($params)
	{
		extract($params);
		return $record['uid'];
	}
		
	
	function printCloneLink($params)
	{
		extract($params);
			return '<a href="index.php?page='.$_SESSION['current_page'].'&action=clone" title="Clone a resource with a name '.$record['entity'].'">Clone</a>';
	}

	function printRulesLink($params)
	{
		extract($params);
			return '<a href="../rule/index.php?{get_proj_id&}resource_id='.$record['resource_id'].'" title="Rules associated with resource '.$record['resource_id'].'">Rules</a>';
	}

	
	function printStatementsLink($params)
	{
		extract($params);
			return '<a href="../statement/index.php?&resource_id='.$record['resource_id'].'" title="Statement associated with resource '.$record['resource_id'].'">Statements</a>';
	}


	function printEditLinkUser($params)
	{
		extract($params);
		if(is_generic_admin($record['account_id']))
			return '&nbsp;';
		else
		//	return '<a href="edituser.php">Edit<input type="hidden" name="account_id" value="'.$record['account_id'].'>"</a>';
			return '<a href="edituser.php?id='.$record['account_id'].'" title="Edit user ('.$record['account_uname'].') information">Edit</a>';
	}	
	
	function printNotes($params)
        {
                extract($params);
                return $record['notes'];
        }
	


	function printVerbinColor($params)
        {
           if(is_array($params))
			   extract($params);
		   else $record['verb'] = $params;
		
		if($_SESSION['previous_verb'] =='')
		{
                        $_SESSION['previous_verb'] = $record['verb'];
                        $_SESSION['current_color'] = '0';
		}
                //else if(strcasecmp($_SESSION['previous_verb'], $record['verb']) != 0)
		// This is really stupid
                else if($_SESSION['previous_verb']!=$record['verb'])
                {
                        $_SESSION['previous_verb'] = $record['verb'];
                        $_SESSION['current_color'] = intVal($_SESSION['current_color']) + 1;
                }
                switch(intVal($_SESSION['current_color'])%3)
                {
                        case 0:
                                return '<font color="red">'.$record['verb'].'</font>';
                        case 1:
                                return '<font color="green">'.$record['verb'].'</font>';
                        case 2:
                                return '<font color="blue">'.$record['verb'].'</font>';
                }
               
        }


	function printeditlinkResource($params)
	{
		extract($params);
		if($record['owner'] == $_SESSION['user']['account_lid'] ||
		get_project_owner() == $_SESSION['user']['acccount_id'] ||
		get_resource_permissions($record['project_id']) >=6)
			return '<a href="index.php?page='.$_SESSION['current_page'].'&action=edit" title="Edit resource '.$record['resource_id'].'">Edit</a>';
		else
			return '&nbsp;';
	}	

	
	function printDeleteLinkResource($params)
	{
		extract($params);
		if($record['owner'] == $_SESSION['user']['account_lid'] ||
		get_project_owner() == $_SESSION['user']['acccount_id'] ||
		get_resource_permissions($record['project_id']) == 5 || get_resource_permissions($record['project_id']) == 7)
			return '<a href="deleteresource.php?resource_id='.$record['resource_id'].'&action=delete" title="Delete resource '.$record['resource_id'].'">Delete</a>';
		else
			return '&nbsp;';
	}	
	
	function printResourceIDandLink($params)
        {
			global $acl, $project_info, $resource_info;
                extract($params);
		//echo $record['owner'].'   ';
		//echo $_SESSION['user']['account_lid'];
		#$acl = find_user_acl($_SESSION['user']['account_id'], $project_info['id']);
                $result =  '<input type="button" size="10" value="'.str_pad($record['resource_id'], 6, '0', STR_PAD_LEFT).'" onClick="window.open(\'../statement/index.php{get_proj_id}{get_res_id}&resource_id='.$record['resource_id'].'\', \'_blank\', \'width=500, height=500, location=no, titlebar=no, scrollbars=yes, resizable=yes\')"><br />';
		if($_SESSION['user']['account_id'] == $project_info['owner'] || $acl =='3' ||$record['owner']==$_SESSION['user']['account_lid'])
			$result .= printEditLinkResource($params). '&nbsp;&nbsp;'. printDeleteLinkResource($params);	
		return $result;		
        }	

	
##FUnctions specific for rendering rules

	function printRuleID($params)
	{global $resource_info, $project_info;
		extract($params);
		return $record['rule_id'];
	}	
	
function printLogic($params)
        {
                extract($params);
		$res = sprintf("%s\n", '<table>');
		
		if(intVal($_SESSION['num_rules'])  > 0)
		{
			$res .= sprintf("%s\n", '	<tr><td style="font-size: 10" valign="bottom">');
			$res .= sprintf("%s\n", '		<input type="radio" name="rule_'.$record['rule_id'].'", value="and"> AND');
			//$res .= sprintf("%s\n", '		<input type="radio" name="rule_'.$record['rule_id'].'", value="not"> NOT');
			$res .= sprintf("%s\n", '		<input type="radio" name="rule_'.$record['rule_id'].'", value="or"> OR<br />');
			$res .= sprintf("%s\n", '	</td></tr>');
			$_SESSION['num_rules'] = intVal($_SESSION['num_rules']) - 1;
		}
		$res .= sprintf("%s\n", '</table>');
                //return $record['rule_id'];
		return $res;
       }

	 function printActionLinkRules($params)
        {$action = $GLOBALS['webaction'];
          extract($params);
			#echo '<pre>';print_r($record);
			if($record['change'])
				if($record['object']!='UID')
				{
				return  printEditLinkRules($params). ' '.printDeleteLinkRules($params);
				}
				elseif($record['object']=='UID')
				return '<a href="'.$action['editclass'].'&class_id='.$record['subject_class_id'].'">Edit </a><a href="'.$action['deleteclass'].'&class_id='.$record['subject_class_id'].'">Delete </a>';
			elseif($record['project_id']!=$_REQUEST['project_id'])
				return '(shared rule)';
			else
				return '';
			
        }

	
	function printEditLinkRules($params)
	{$action=$GLOBALS['webaction'];
		extract($params);
		
		#return '<a href="index_main_page.php{get_proj_id}{get_res_id}&rule_id='.$record['rule_id'].'&action=edit" title="Edit rule '.$record['rule_id'].'">Edit</a>';
		
		return '<a href="'.$action['editrules'].'&rule_id='.$record['rule_id'].'&action=edit" title="Edit rule '.$record['rule_id'].'">Edit</a>';
		
	}	
	
	
	
	function printDeleteLinkRules($params)
	{global $action;
		extract($params);
		return '<a href="'.$action['deleterule'].'&rule_id='.$record['rule_id'].'&action=delete" title="Delete rule '.$record['rule_id'].'">Delete</a>';
		
		
	}	
	

##FUnctions specific for rendering groups
 function printActionLinkGroup($params)
        {
                extract($params);
                if($record['account_id']!='1')
				return  printEditLinkGroup($params). ' '.printDeleteLinkGroup($params);
				else
					return  printEditLinkGroup($params);
				
        }

	function printEditLinkGroup($params)
	{
		extract($params);
		return '<a href="editgroup.php?group_id='.$record['account_id'].'" title="Edit group ('.$record['account_uname'].') information">Edit</a>';

//		return '<a href="editgroup.php?id='.$record['account_id'].'">Edit</a>';
	}	
	
	function printDeleteLinkGroup($params)
	{
		extract($params);
		return '<a href="deletegroup.php?group_id='.$record['account_id'].'" title="Delete group ('.$record['account_uname'].')">Delete</a>';
	//	return '<a href="deletegroup.php?id='.$record['account_id'].'">Delete</a>';
	}	



	function printMapLink($params)
	{
		extract($params);
		if($record['project_status'] == 'A')
			return '<a href="../map/index.php{get_proj_id}{get_res_id}&id='.$record['project_id'].'">Map</a>';
		else
			return '';
	}
	
		
	
#Userd by deleteuser
function create_user_list($users)
	{

		if(!empty($users))
		{
			foreach ($users as $i=>$value)
			{
				$user_list .='<option value="'.$users[$i]['account_id'].'">'.$users[$i]['account_uname'].' ('.$users[$i]['account_lid'].')</option>';
			}
		}

		return $user_list;				
	}

	

function create_groupuser_list($users, $group)
	{
		$user_list='';
		foreach ($users as $i=>$value)
		{
			
			if(is_group_member($users[$i]['account_id'], $group))
			{
				$user_list .='<option value="'.$users[$i]['account_id'].'" selected>'.$users[$i]['account_uname'].' ('.$users[$i]['account_lid'].')</option>';
			}	
			else 
			{
				$user_list .='<option value="'.$users[$i]['account_id'].'">'.$users[$i]['account_uname'].' ('.$users[$i]['account_lid'].')</option>';
			}
		}
		//echo $user_list;
		return $user_list;				
	}
	

	function create_group_list($groups, $account_id)
	{
		$group_list='';
		if(is_array($groups))
		foreach ($groups as $i=>$value)
		{
			$group_list .='<option value="'.$groups[$i]['account_id'].'"';
			if(user_groups($groups[$i]['account_id'], $account_id))
				$group_list.=' selected> '.$groups[$i]['account_lid'].' </option>';		
			else
				$group_list.='>'.$groups[$i]['account_lid'].'</option>';		
		}
		return $group_list;		
	}

	function create_static_group_list($groups, $account_id)
	{
		
		$group_list='';
		if(is_array($groups))
		foreach ($groups as $i=>$value)
		{
			
			//$group_list .='<option value="'.$groups[$i]['account_id'].'"';
			#if(user_groups($groups[$i]['account_id'], $account_id))
				$group_list.=$groups[$i]['account_lid'].', ';		
		}
		return substr($group_list, 0, strrpos($group_list, ','));				
	}

function create_group_select_list($users)
	{
		$user_select_list='';
		foreach ($users as $i=>$value)
		{
			$user_select_list .='<option value="'.$users[$i]['account_id'].'">'.$users[$i]['account_uname'].' ('.$users[$i]['account_lid'].')</option>';
		}
		return $user_select_list;				
	}
	
//	function create_group_select_list_inputgroups($groups)
//	{
//		$group_select_list='';
//		foreach ($groups as $i=>$value)
//		{
//			$group_select_list .='<option value="'.$groups[$i]['account_id'].'" '.$groups[$i]['selected'].'>'.$groups[$i]['account_lid'].'</option>';
//		}
//		return $group_select_list;				
//	}

function create_group_selected($groups, $selected_groups)
        {
                $group_selected_list='';
                foreach($groups as $i=>$value)
                {
                        $group_selected_list .='<option value="'.$groups[$i]['account_id'].'"';
                        if(is_selected_group($groups[$i]['account_id'], $selected_groups))
                                $group_selected_list .=' selected>'.$groups[$i]['account_lid'].'</option>';
                        else
                                $group_selected_list .='>'.$groups[$i]['account_lid'].'</option>';
                }
		return $group_selected_list;				
        }



	function printInputBox($params)
        {		global $action;
               
				extract($params);
				
				
				#echo '<pre>';print_r($params);
				#print the input box without value if clen query was requested
				
				$main_resource_id = $_REQUEST['class_id'];
				
				if(!empty($_SESSION['result_list'][$main_resource_id][$record['rule_id']]) && empty ($_POST['clearquery'])) 
				$entered_value = "~".$_SESSION['result_list'][$main_resource_id][$record['rule_id']];
				elseif(!empty($record['rule_id']) && empty ($_POST['clearquery'])) 
					$entered_value = entered_before($record['rule_id']);
				elseif($_POST['clearquery'])
					$entered_value = '';
             
				
				$inputBox='<input type="text" style="background: lightyellow" size="15" name="rule_1_'.$record['rule_id'].'" value="'.$entered_value.'">&nbsp;&nbsp;';
		
				
				#Here we put a button for the remote resource, if it exists
				#array rules at this point should have gone through a function to grab object_class_id
				if($record['object_class_id']!='')
				{
				#we are jumping into another class. By defual, class_id args are sent with action. In this particular case, it shuld be replaced
				$linkButton = ereg_replace('&class_id='.$_REQUEST['class_id'], '',$action['querypage']);
				$inputBox .='<input type="button" name="rule_1_'.$record['rule_id'].'existvalues" value="Go to '.$params['record']['object'].'" onClick="window.location=\''.$linkButton.'&class_id='.$record['object_class_id'].'&main_rule='.$record['rule_id'].'&main_resID='.$main_resource_id.'\', \'_blank\', \'width=700, height=500, location=no, titlebar=no, scrollbars=yes, resizable=yes\'">';
				
				$peek = ereg_replace('&class_id=[0-9]+','&class_id='.$record['object_class_id'],$action['peek']);
				
				}
				elseif($record['object_class_id']=='')
				{
				$peek = ereg_replace('&class_id=[0-9]+','&rule_id='.$record['rule_id'], $action['peek']);
				}
				
				$inputBox .='<input type="button" name="rule_1_'.$record['rule_id'].'existvalues" value="Peek" onClick="window.open(\''.$peek.'&name=rule_1_'.$record['rule_id'].'\', \'_blank\', \'width=500, height=500, location=no, titlebar=no, scrollbars=yes, resizable=yes\')"><br />';
			
		
		return $inputBox;
        }

	function printShowMe($params)
	{
                extract($params);
		
		if(display_me($record['rule_id']))
		
		{
			//echo "show me";
                        $showMe = '<input type="checkbox" name="show_me[]" value="show_me_val_'.$record['rule_id'].'" checked>';
		}
                else
                        $showMe = '<input type="checkbox" name="show_me[]" value="show_me_val_'.$record['rule_id'].'">';
			
		
		return $showMe;	
	}

		 function display_me($rule_id)
        {
                
				#$show_me = $_SESSION['show_me'];
				$show_me = $_POST['show_me'];
              
				

				if(count($show_me) > 0)
                {
                       if(in_array("show_me_val_".$rule_id, $show_me))
								return True;
							else
									return False;

                        
				}
				else
					Return False;
						
        }
 
	function display_statements($S)
	{	$action = $GLOBALS['webaction'];
		extract($S);
		$result ='';
		$hidden = '';
		$index = 1;
		$total = 1;

		
		#Render the statement differentely according to the user request: show, search result (matched), hidden
		
		foreach($stats as $i => $value)
		{
			$display = '';
			$display .='<font size=+0>'.($index+$i).'. [ '.dispVerb($stats[$i]).' | <b>'.$stats[$i]['object'].'</b> ]<br />&nbsp;&nbsp;&nbsp;&nbsp;';
			
			if($stats[$i]['match']!='') 
				{$b = '<b>';$b1 = '</b>';}
			#This is a trick to avoid the file name to be read as a resource, but to allow file upload in resource connects
			
			#Print a button when the rule points to another resource
				#Subject is switched with object because in this case we are looking for the rule that has this object as subject
				
			
			if($stats[$i]['object_id']!='' && $stats[$i]['file_name']=='')  
				{
				$display .= '<input type="button" size="10" value="'.$stats[$i]['button_notes'].'" onClick="window.open(\''.$action['item'].'&instance_id='.$stats[$i]['value'].'\', \'_blank\', \'width=700, height=600, location=no, titlebar=no, scrollbars=yes, resizable=yes\')"></b><font size=1> (Id '.str_pad($stats[$i]['value'], 6, '0', STR_PAD_LEFT).')</font>';
				
				}
			elseif($stats[$i]['file_name']!='')
				$display .=urldecode($stats[$i]['value']).' ('.ceil($stats[$i]['file_size']/1024).' Kb)&nbsp;&nbsp;&nbsp;&nbsp;';
			else
				$display .=$b.html_entity_decode(urldecode($stats[$i]['value'])).$b1.'&nbsp;&nbsp;&nbsp;&nbsp;';
			
			$display .='<br /><br />';
			
			if($stats[$i]['rest']=='')
			$result .= $display; #call it different names according to whether it is a match/shown or rest
			else
			$hidden .= $display;
			$total++;	
			
		}
		$index = $total;
		
		
		
		$result .='<span class="hidden" id="'.$resource_id.'">'.$hidden.'</span>'; #put the hidden at the end of the output
        if($hidden!='')
        $result .='&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<a href="javascript:shownhidden(\''.$resource_id.'\')">[+/-]</a>';
     
         
                return $result;
		
	}

	function rearrange_statements($stats, $resource_id)
	{
		
		
		foreach($stats as $i => $value)
		{
			$rule_id = $stats[$i]['rule_id'];
				
			$stats[$i]['color'] = get_current_color($stats[$i]['verb']);	
			
			

			if(is_match($rule_id))
				$stats[$i]['match']=True;
			else if(display_me($rule_id))
				$stats[$i]['shown'] = True;
			else
				$stats[$i]['rest'] = True;
		}
		
		$result = display_statements(compact('matched','shown', 'rest','resource_id', 'stats'));

		return $result;
	}
	
	
	function is_match($rule_id)
	{
		$used_rule = $_SESSION['used_rule'];
		
		
		if(!empty($used_rule))
		{
		foreach($used_rule as $i=>$value)
		{
			
			if($used_rule[$i] == $rule_id)
			{
				
				return True;
			}
		}
		}
		return False;
	}
	
	    function dispVerb($stat)
        {
               
                                return '<font color="'.$stat['color'].'">'.$stat['verb'].'</font>';
        }
	
	function get_current_color($verb)
	{
                if($_SESSION['previous_verb'] =='')
                        $_SESSION['previous_verb'] = $verb;
                else if($_SESSION['previous_verb']!=$verb)
                {
                        $_SESSION['previous_verb'] = $verb;
                        $_SESSION['current_color'] = intVal($_SESSION['current_color']) + 1;
                }
                switch(intVal($_SESSION['current_color'])%3)
                {
                        case 0:
                                return "red";
                        case 1:
                                return "green";
                        case 2:
                                return "blue";
                }
	}
	
	function printResourceNotes($params)
        {
                extract($params);
                return "<font color=red size=3><b>".$record['notes']."</b></font>";
        }	

	function printStatements($params)
        {
			
        $_SESSION['previous_verb']='';
		$_SESSION['current_color'] = 0;
        extract($params);
		
		$stats = $record['stats'];
		
		$i = 1;
		if(is_array($stats))
		{
			$result = rearrange_statements($stats, $record['resource_id']);	
			return $result;	
		}
		else {
			return $stats;
		}
		
        }
	

	function printEditRIDLink($params)
	{global $action;
                extract($params);
                $res = '<a href="#" onClick="window.open(\''.$action['editinstance'].'&instance_id='.$record['resource_id'].'\', \'editresource_'.$record['resource_id'].'\',\'_blank\', \'width=300, height=300, location=no, titlebar=no, scrollbars=yes, resizable=yes\')" title="Edit resource '.$record['resource_id'].' ( '.$record['entity'].' )">Edit</a>';
              
		return $res;
	}

	function printDeleteRIDLink($params)
	{$action = $GLOBALS['action'];
                extract($params);
                $res = '<a href="#" onClick="window.open(\''.$action['deleteinstance'].'&instance_id='.$record['resource_id'].'\', \'deleteresource_'.$record['resource_id'].'\',\'_blank\', \'width=300, height=300, location=no, titlebar=no, scrollbars=yes, resizable=yes\')" title="Delete resource '.$record['resource_id'].' ( '.$record['entity'].' )">Delete</a>';
               
		return $res;
	}
	
	function getEditNewResourceLink($resource)
        {
                $res = '<a href="#" onClick="window.open(\'../resource/editresource.php{get_proj_id}{get_res_id}&resource_id='.$resource.'\', \'editresource_'.$resource.'\', \'width=600, height=600, location=no, titlebar=no, scrollbars=yes, resizable=yes\')" title="Edit resource '.$resource.' )">Edit</a>';
                return $res;
        }
                                                                                                                                           
	function getDeleteNewResourceLink($resource)
        {
		$_SESSION['new_insert_all'] = "new";
                $res = '<a href="#" onClick="window.open(\'../resource/deleteresource.php{get_proj_id}{get_res_id}&resource_id='.$resource.'\', \'deleteresource_'.$resource.'\', \'width=450, height=450, location=no, titlebar=no, scrollbars=yes, resizable=yes\')" title="Delete resource '.$resource.'">Delete</a>';
                return $res;
        }

		function show_distinct_verbs()
        {
                $show_me = $_SESSION['show_me'];
                $verbs = Array();
                foreach($show_me as $i=>$value)
                {
                        //$verb['verb'] = $show_me[$i]['verb'];
                        array_push($verbs, $show_me[$i]['verb']);
                }
                $verbs = array_unique($verbs);
                return $verbs;
        }

        function show_distinct_objects($verb)
        {
                $show_me = $_SESSION['show_me'];
                $objects = Array();
                foreach($show_me as $i=>$value)
                {
                        if($show_me[$i]['verb'] == $verb)
                        {
                                //$object['object'] = $show_me[$i]['object'];
                                array_push($objects, $show_me[$i]['object']);
                        }
                }
                $objects = array_unique($objects);
                return $objects;
        }

        function show_distinct_rules()
        {
                $show_me = $_SESSION['show_me'];
                $rules = Array();
                foreach($show_me as $i=>$value)
                {
                        //$rule['rule_id']=$show_me[$i]['rule_id'];
                        array_push($rules, $show_me[$i]['rule_id']);
                }
                $rules = array_unique($rules);
                return $rules;
        }

function break_multiple_values($values)
	{
		if(is_array($values))
		{
			$result = Array('subject'=>$values[0]['subject'], 
				'verb'=>$values[0]['verb'], 
				'object'=>$values[0]['object'],
				'values'=>array());
			$vals = array();
			foreach($values as $i =>$value)
			{
				if($values[$i]['file_name'] =='')
				{
					$select_val = $values[$i]['value'];
				}
				else 
				{
					$select_val = "File: ".$values[$i]['file_name'];
				}
				
				{
					$split_val = split(",", $select_val);
				
				
					foreach($split_val as $j =>$value)
					{
					
						array_push($vals, trim($split_val[$j]));
					}
				}			
				
			}
			$final_vals = array_unique($vals);
			sort($final_vals);
			reset($final_vals);
		}
		$result['values'] = $final_vals;
		return $result;
	}
 

function printStatementsForPeek($stats, $rule_info)
        {
		
		$i = 1;
		
		
		if(is_array($stats))
		{
			
			$result ='<b>'.$rule_info['subject'].' | '.$rule_info['verb'].' | '.$rule_info['object'].' </b><br /><br />';
			
			#$vals = $stats['values'];
			
			$i=0;
			foreach($stats as $vals)
			{
				
				$select_val = $vals;
				$select_val = str_replace("(", "\(", $select_val);
                                $select_val = str_replace(")", "\)", $select_val);

				
				{
					if(substr($select_val, 0, 5) == "File:")
					{
						#$result .=sprintf("%s\n", ($i+1).'. [<b><a href="#" onClick="SendInfo(\'File: ~*'.addslashes(trim(substr($select_val, 5))).'\')">'.$select_val.'</a></b>]<br />');
						$result .=sprintf("%s\n", ($i+1).'. [<b><a href="#" onClick="SendInfo(\'File: ~*'.addslashes(trim(substr($select_val, 5))).'\')">'.$select_val.'</a></b>]<br />');
					}
					else
					{
						#$result .=sprintf("%s\n", ($i+1).'. [<b><a href="#" onClick="SendInfo(\'~*'.addslashes($select_val).'\')">'.$select_val.'</a></b>]<br />');
						$result .=sprintf("%s\n", ($i+1).'. [<b><a href="#" onClick="SendInfo(\''.addslashes($select_val).'\')">'.$select_val.'</a></b>]<br />');
					}
				}	
			$i++;
			}
		}
		else
			$result ="No values for object ".$rule_info['object']." were found";
		return $result;	
        }

		function  printInstancesForPeek($inst, $class_info)
        {
		 $i = 0;
		
		if(is_array($inst))
		
			{
				echo "Existing statements<BR><BR>";
				foreach ($inst as $instance_info)
				{
					
					if ($instance_info['notes']!='')
					{#$result .='[<b><a href="#" onClick="SendInfo(\''.addslashes($instance_info['resource_id']).'\')">'.$instance_info['notes'].'</a></b>] <font color=navy size=1>(Id '.$instance_info['resource_id'].')</font><br /><br />';
					$result .=sprintf("%s\n", ($i+1).'. [<b><a href="#" onClick="SendInfo(\''.addslashes($instance_info['resource_id']).'\')">'.$instance_info['notes'].' </a></b><font color=navy size=1>(Id '.$instance_info['resource_id'].')</font>]<br />');
					}
					else
					{#$result .='[<b><a href="#" onClick="SendInfo(\''.addslashes($instance_info['resource_id']).'\')">'.$instance_info['resource_id'].'</a></b>] <br /><br />';
					$result .=sprintf("%s\n", ($i+1).'. [<b><a href="#" onClick="SendInfo(\''.addslashes($instance_info['resource_id']).'\')">'.$instance_info['resource_id'].'</a></b>]<br />');
					}
				$i++;
				}
			}
		
		else
			$result ="No instances were found on object ".$class_info['entity'];
		return $result;	
        }		

		function printStatementActionLink($statement_id) 
		{	$action=$GLOBALS['action'];
                
				$res = '<a href="#" onClick="window.open(\''.$action['editstatement'].'&statement_id='.$statement_id.'\', \'editstatement_'.$statement_id.'\', \'width=600, height=600, location=no, titlebar=no, scrollbars=yes, resizable=yes\')" title="Edit statement '.$statement_id.'">Edit</a>';
                $res .='<br>';
                $res .= '<a href="#" onClick="window.open(\''.$action['deletestatement'].'&statement_id='.$statement_id.'\', \'deletestatement_'.$statement_id.'\', \'width=600, height=600, location=no, titlebar=no, scrollbars=yes, resizable=yes\')" title="Delete statement '.$statement_id.'">Delete</a>';

                return $res;
        }
	
function editInputStatementValue($statement_info)
{
global $action;
if($statement_info['button_notes']!='')
	{#print a button with the instance link, a text box with the UID and a peek button for access to other instances
	$value .= '<input type="button" value="'.$statement_info['button_notes'].'" onClick="window.open(\''.$action['item'].'&instance_id='.$statement_info['value'].'\')"><br />';

	$value .= '<input type="text"  style="background: lightyellow" name="value" value="'.$statement_info['value'].'">';
						
	$action['peek'] = str_replace('&statement_id='.$statement_info['statement_id'], '&class_id='.$statement_info['object_class_id'],$action['peek']);

	$value .= '&nbsp;&nbsp;&nbsp;<input type="button" name="peek" value="peek" onClick="window.open(\''.$action['peek'].'&name=value\', \'_blank\', \'width=500, height=500, location=no, titlebar=no, scrollbars=yes, resizable=yes\')">';

	
	}
	elseif($statement_info['file_name']=='')		
	$value .=  '<textarea style="background: lightyellow" rows="4" cols="40" name="value" >'.$statement_info['value'].'</textarea>';
	else
	$value .=  $statement_info['value'];

	return $value;

}

function viewStatementValue($statement_info)

{
global $action;
if($statement_info['object_id']!='')
	{#print a button with the instance link, a text box with the UID and a peek button for access to other instances
	$value .= '<input type="button" value="'.$statement_info['button_notes'].'" onClick="window.open(\''.$action['item'].'&instance_id='.$statement_info['value'].'\')"><br />';
	
	
	}
	else
	$value .=  $statement_info['value'];

	return $value;


}

function instanceButton($instance_info)
{
$action = $GLOBALS['webaction'];

if ($instance_info['notes']=='') {
	$button_notes = $instance_info['resource_id'];
	}
	else {
		$button_notes = $instance_info['notes'];
	}

return ('<input type="button" value="'.$button_notes.'" onClick="window.open(\''.$action['item'].'&instance_id='.$instance_info['resource_id'].'\')"><font size="2" color="navy"><br />(Id '.$instance_info['resource_id'].')</font>');	
}

function render_resource_doesnot_exist($S)
{
#$error .= render_statementpage_header($S);
extract($S);
	if(is_array($where)) extract($where);
	
	if($rule_id!='') $rule_info = get_info('rule',$rule_id, $db);
$instance_id = $resource_id;

$error .= sprintf('%s', '<table width="100%"><tbody><tr><td><font color="red">Resource does not exist</font></td></tr>');
$error .= sprintf('%s', '<tr><td><ol><li><i>Resource</i><b>'.$rule_info['subject'].'</b>ID #'.$instance_id.' found</li>');
$error .= sprintf('%s', '<li><i>Rule</i><b> '.$rule_info['subject'].' + '.$rule_info['verb'].' + '.$S['rule_info']['object'].' </b>found</li>');
$error .= sprintf('%s', '<li><i><b> '.$rule_info['object'].' </b>is resource</i></li>');
$error .= sprintf('%s', '<i></i><li><i>But<i> Resource</i><b> '.$rule_info['object'].'</b>( UID:<b>'.$value.'</b>) does not exist</i></li>');
#$error .= sprintf('%s', '<i></i></ol><i></i></td></tr><tr><td>  <br><input value="Try again" onclick="window.history.go(-1)" type="button"></td></tr></tbody></table>');
$error .= sprintf('%s', '<i></i></ol><i></i></td></tr><tr><td>  <br><input value="Try again" onclick="history.go(-1)" type="button"></td></tr></tbody></table>');
$error .= sprintf('%s', '</td></tr><tr><td align="right"></td></tr></tbody></table></form></body></html>');

return $error;
}

function couldnot_insert_statement($S)
{#$S msut include rule_id/rule_info, value, resource_id and error message
	extract($S);
	
	
if($rule_id!='') $rule_info = get_info('rule',$rule_id, $db);
#$error .= render_statementpage_header($S);

$error .= sprintf('%s', '<table width="100%"><tbody><tr><td><font color="red">'.$done[2]['message'].'</font></td></tr>');

if($done[2]['error_code']=='4'){
$error .= sprintf('%s', '<tr><td><ol><li><i>Resource</i><b> '.$rule_info['subject'].'</b> (ID #'.$resource_id.' found)</li>');
$error .= sprintf('%s', '<li><i>Rule</i><b> '.$rule_info['subject'].' + '.$rule_info['verb'].' + '.$rule_info['object'].' (R#'.$rule_info['rule_id'].') </b>found</li>');
$error .= sprintf('%s', '<i></i><li><i>But<i>Statement</i><b> '.$rule_info['subject'].' + '.$rule_info['verb'].' + '.$rule_info['object'].' ['.$value.']</b>for the above resource already exists</i></li>');
}

#$error .= sprintf('%s', '<i></i></ol><i></i></td></tr><tr><td>  <br><input value="Try again" onclick="window.history.go(-1)" type="button"></td></tr></tbody></table>');
$error .= sprintf('%s', '<i></i></ol><i></i></td></tr><tr><td>  <br><input value="Try again" onclick="history.go(-1)" type="button"></td></tr></tbody></table>');
$error .= sprintf('%s', '</td></tr><tr><td align="right"></td></tr></tbody></table></form></body></html>');
return $error;
}

function render_statement_already_exists($S)
{extract($S);
	if(is_array($where)) extract($where);
	
	if($rule_id!='') $rule_info = get_info('rule',$rule_id, $db);
#$error .= render_statementpage_header($S);
$error .= sprintf('%s', '<table width="100%"><tbody><tr><td><font color="red">Statement already exist</font></td></tr>');
$error .= sprintf('%s', '<tr><td><ol><li><i>Resource</i><b> '.$rule_info['subject'].'</b> (ID #'.$resource_id.' found)</li>');
$error .= sprintf('%s', '<li><i>Rule</i><b> '.$rule_info['subject'].' + '.$rule_info['verb'].' + '.$rule_info['object'].' (R#'.$rule_info['rule_id'].') </b>found</li>');
$error .= sprintf('%s', '<i></i><li><i>But<i>Statement</i><b> '.$rule_info['subject'].' + '.$rule_info['verb'].' + '.$rule_info['object'].' ['.$value.']</b>for the above resource already exists</i></li>');
#$error .= sprintf('%s', '<i></i></ol><i></i></td></tr><tr><td>  <br><input value="Try again" onclick="window.history.go(-1)" type="button"></td></tr></tbody></table>');
$error .= sprintf('%s', '<i></i></ol><i></i></td></tr><tr><td>  <br><input value="Try again" onclick="history.go(-1)" type="button"></td></tr></tbody></table>');
$error .= sprintf('%s', '</td></tr><tr><td align="right"></td></tr></tbody></table></form></body></html>');
return $error;
}

function render_inserted($S, $statement_id)
{$action =$GLOBALS['webaction'];
extract($S);
	if(is_array($where)) extract($where);
	
	if($rule_id!='') $rule_info = s3info('rule',$rule_id, $db);
	if(!$instance_id) $instance_id = $resource_id;

$message .= sprintf("\n%s\n", '<table width="100%"><tr><td>');	
$message .= sprintf("%s\n", '	<font color="red">Statement inserted</font>');
$message .= sprintf("%s\n", '	</td></tr>');
$message .= sprintf("%s\n", '	<tr><td>');
$message .= sprintf("%s\n", '      <ol>');
$message .= sprintf("%s\n", '      	<li><i>Resource</i> <b>'.$rule_info['subject'].'</b> ID #'.str_pad($instance_id, 6, '0', STR_PAD_LEFT).' found</li>');
$message .= sprintf("%s\n", '      	<li><i>Rule</i> <b>'.$rule_info['subject'].' + '.$rule_info['verb'].' + '.$rule_info['object'].'</b> found</li>');
if(resourceObject(compact('rule_info', 'project_id', 'db')) && resource_found(compact('rule_info', 'value', 'project_id', 'db', 'user_id')))
$message .= sprintf("%s\n", '      	<li><i>Found Resource </i> <b>'.$rule_info['object'].'</b> ( UID: <b>'.$value.'</b> )</li>');

if($insert!='file') $display_value = $value;
else 
	{$display_value = '<a href='.$action['download'].'&statement_id='.$statement_id.'>'.$filename.'</a>';
	
	}
$message .= sprintf("%s\n", '      	<li><i>Statement</i> <b>'.$rule_info['subject'].' | '.$rule_info['verb'].' | '.$rule_info['object'].' [ '.urldecode($display_value).' ]</b> for the above resource inserted</li>');
$message .= sprintf("%s\n", '      	<li>Statement ID:  <b>'.$statement_id.'</b></li>');
$message .= sprintf("%s\n", '      </ol>');
$message .= sprintf("%s\n", '	<tr><td>');
#$message .= sprintf("%s\n", '		<br /><input type="button" value="Insert Another" onClick="opener.window.location.reload(); window.history.go(-1); return false;">');
#$message .= sprintf("%s\n", '		<br /><input type="button" value="Insert Another" onClick="opener.window.location.reload(); history.go(-1); return false;">');
#$message .= sprintf("%s\n", '		<br /><input type="button" value="Insert Another" onClick="window.location=\''.$action['instanceform'].'\'">');
#$message .= sprintf("%s\n", '		&nbsp;&nbsp;<input type="button" value="Close Window" onClick="opener.window.location.reload(); self.close();return false;">');
$message .= sprintf("%s\n", '	</td></tr>');
$message .= sprintf("%s\n", '	</td></tr>');
$message .= sprintf("%s\n", '	</table>');

return $message;
}

function render_value_cannot_be_null()
	{
		#$message .= render_statementpage_header($S);
		$message = sprintf("\n%s\n", '<table width="100%"><tr><td>');	
		$message .= sprintf("%s\n", '	<font color="red">Value can not be empty</font>');
		$message .= sprintf("%s\n", '	</td></tr>');
		$message .= sprintf("%s\n", '	<tr><td>');
	
		#$message .= sprintf("%s\n", '		<br /><input type="button" value="Try again" onClick="window.history.go(-1)">');
		$message .= sprintf("%s\n", '		<br /><input type="button" value="Try again" onClick="history.go(-1)">');
		$message .= sprintf("%s\n", '	</td></tr>');
		$message .= sprintf("%s\n", '	</td></tr>');
		$message .= sprintf("%s\n", '	</table>');
		
		return $message;
		
	}

	function render_empty_form($F)
	{$action = $GLOBALS['webaction'];
		extract($F);
		
		$rule_id = $rule_info['rule_id'];
		$stat = sprintf("\n%s\n", '<table width="100%">');		
		$stat .= sprintf("%s\n", '<tr bgcolor="lightyellow"><td colspan="3">');
		$stat .= sprintf("%s\n", '['.$rule_info['subject'].' | <a id="'.$rule_info['rule_id'].'">'.printVerbStat($rule_info['verb']).' | <b>'.$rule_info['object'].'</b> ] <font size=2 color=red>(R'.$rule_info['rule_id'].')</font><br />&nbsp;&nbsp;&nbsp;&nbsp;');
		$stat .= sprintf("%s\n", '<font size-=2 color=dodgerblue>'.(($rule_info['validation']!='')?'Validation: '.$rule_info['validation']:'').'</font>');
		$stat .= sprintf("%s\n", '</td></tr>');
		
		$stat .= sprintf("%s\n", '<tr><td width="33%"><b><font color="navy" size="-1">&nbsp;&nbsp;Value</font></b></td><td width="33%"><b><font color="navy" size="-1">Notes</font></b></td><td width="33%"><b><font color="navy" size="-1"><a href="'.S3DB_URI_BASE.'/statement/jumpuploader.php?rule_id='.$rule_id.'&item_id='.$instance_id.'" title="Please note that importing a file will take the place of the Value of the statement. You may add another statement to this item if you wish to keep both.">Multiple Files</a></font></b></td>');
		#$stat .= sprintf("%s\n", '<tr><td width="33%"><b><font color="navy" size="-1">&nbsp;&nbsp;Value</font></b></td><td width="33%"><b><font color="navy" size="-1">Notes</font></b></td><td width="33%"><b><font color="navy" size="-1"><a href="#" title="Please note that importing a file will take the place of the Value of the statement. You may add another statement to this item if you wish to keep both.">File (hover for instructions)</a></font></b></td>');

		$stat .= sprintf("%s\n", '<tr><td valign="top" width="33%">');
		
		
		if($rule_info['add_data']){
		if($rule_info['object_id']!='')
		{
				$select_name = 'input_'.$instance_id.'_'.$rule_id;
				$inputBox = get_rule_drop_down_menu(compact('select_name','rule_info', 'project_id', 'db', 'user_id'));
				
				
		}
		else
			{
			$inputBox = sprintf("%s\n", '<textarea style="background: lightyellow" name="input_'.$instance_id.'_'.$rule_id.'" cols="20"></textarea>');
			$peek='';
			}
		}
		else {
			$inputBox = sprintf("%s\n", '<I><font color="red">User cannot add data to this rule</font></I>');
			$peek='';
		}
		
		$stat .= $inputBox;
		$stat .= $peek."</td>";
		$stat .= sprintf("%s\n", '<td valign="top"  width="33%"><textarea style="background: lightyellow" name="text_'.$instance_id.'_'.$rule_info['rule_id'].'" rows="2" cols="20"></textarea></td>');
		$stat .= sprintf("%s\n", '<td valign="top"  width="33%"><input name="upload_input_'.$instance_id.'_'.$rule_id.'" type="file"><font style="font-size: 10"></font></td>');
		
		#$stat .= sprintf("%s\n", '<td valign="top"><input type="submit" name="insert_'.$resource_id.'_'.$rule_id.'" value="Insert">');

		
		$stat .= sprintf("\n%s\n", '<tr><td colspan="3"><hr size="1" align="center" color="blue"></hr></td></tr>');	
		$stat .=sprintf("%s\n", '</table>');
		#$stat .=sprintf("%s\n", '</form>');	
		$stats .= $stat;

		
		
	//echo " I am here";
	return $stats;
	}

	function user_drop_down_list($select_name, $db, $user_id)
	{
		$s3ql=compact('user_id','db');
		$s3ql['select']='*';
		$s3ql['from']='users';
		
		$users= S3QLaction($s3ql);

		if (is_array($users)) {
			$drop .= '<select name="'.$select_name.'" size="10" multiple>';
			
			foreach ($users as $user_info) {
				
				$drop .= '<option value="'.$user_info['account_id'].'">'.$user_info['account_uname'].'</option>';
			}
			$drop .= '</select>';
		}
		echo $drop;
	return ($drop);
		
	}

function aclGrid($Z)
{#acGid prints the grid with the permission codes
#input:$Z=compact('user_id', 'db');
	extract($Z);
	
#CREATE THE HEADER AND SET THE TPL FILE

	if(!$users)
	{
	$s3ql=compact('user_id','db');
	$s3ql['select']='*';
	$s3ql['from']='groups';
	$s3ql['where']['user_id']=$user_id;

	$done = S3QLaction($s3ql);

	$groups = $done;

	#Got the groups, now figure out the users
	$users = array();
	if(is_array($groups))
	{
	foreach ($groups as $group_info) {
		$s3ql=compact('user_id','db');
		$s3ql['select']='*';
		$s3ql['from']='users';
		$s3ql['where']['group_id']=$group_info['account_id'];
		if ($_REQUEST['orderBy']!='') {
		$s3ql['order_by'] = $_REQUEST['orderBy'].' '.$_REQUEST['direction'];
		}

		$done = S3QLaction($s3ql);
		
		
		
		if(is_array($done))
			foreach ($done as $user) {
				array_push($users, $user);
			}
			
		
	}
	}
	}
	
	#replace the 0,1,2... of the array bu the account Id for making sure we get a unique list
	if(is_array($users) && !empty($users)) {
	$how_many = count($users);
	$datagrid = render_elements($users, $acl, array('User ID', 'Login', 'User Name', 'Access Control List'),'account_acl', $new, $uid,$how_many);
	}

	return ($datagrid);
}

//function addUsers($U)
//		{
//		extract($U);
//		
//
//		if(is_array($users)){
//			$s3ql=compact('user_id','db');
//			$s3ql['insert']='user';
//			$s3ql['where'][$element.'_id']=$U[$element.'_id'];
//				
//				#NOW ADD THE SELECTED USERS TO THE CLASS:
//					#Are there users in project? They will inherit permissions directly,unless otherwise specified
//					
//				
//					foreach ($users as $ind=>$user) {
//						$account_id = str_replace('.', '_', $user['account_id']);
//						$v = ($_POST['view_'.$account_id]=='m')?'-':$_POST['view_'.$account_id];
//						$e = ($_POST['edit_'.$account_id]=='m')?'-':$_POST['edit_'.$account_id];
//						$p = ($_POST['add_data_'.$account_id]=='m')?'-':$_POST['add_data_'.$account_id];
//						
//						$permLevel = $v.$e.$p;
//						
//						if($permLevel!='---'){
//						$s3ql['where']['user_id']=$user['account_id'];
//						$s3ql['where']['permission_level']=$permLevel;
//						#echo '<pre>';print_r($s3ql);
//						$done=S3QLaction($s3ql);
//						#echo $done;
//						
//						
//						ereg('<error>([0-9]+)</error>.*<message>(.*)</message>', $done, $s3qlout1);
//						if($s3qlout1[1]!='0')
//							$message.=$s3qlout1[2];
//						}
//					}
//				return ($message);
//		}
//		}


?>