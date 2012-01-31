<?php 
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

#Universal variables
$sortorder = $_REQUEST['orderBy'];
$direction = $_REQUEST['direction'];
$project_id = $_REQUEST['project_id'];
#$acl = find_final_acl($user_id, $project_id, $db);
$uni = compact('db', 'acl','user_id','key', 'project_id', 'dbstruct', 'sortorder', 'direction');
$project_info = URI('P'.$project_id, $user_id, $db);
$acl = $project_info['acl'];

#relevant extra arguments
$args = '?key='.$_REQUEST['key'].'&project_id='.$_REQUEST['project_id'];

#Define the page actions
include('../webActions.php'); #include the specification of the link map. Must be put in here becuase arguments vary.

if($_REQUEST['project_id']!='') echo '<FRAMESET ROWS="50%,50%" Border="2"><FRAME SRC="'.$action['projectstree'].'" NAME=""><FRAME SRC="'.$action['map'].'&project_id='.$project_id.'" NAME=""  MARGINWIDTH="1px" MARGINHEIGHT="1px">'; 
	else echo '<FRAMESET><FRAME SRC="'.$action['projectstree'].'" MARGINWIDTH="1px" MARGINHEIGHT="1px">'; 
	?>

</FRAMESET>
</HEAD>

<BODY>

</BODY>
</HTML>
