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
	
	
	$args = '?key='.$_REQUEST['key'];
	include '../webActions.php';

	echo '<FRAMESET COLS="20%, 80%" Border="2">';
	echo '<FRAME SRC="'.$action['projectstree'].'" NAME="ProjectsFrames" >';
	echo '<FRAME SRC="'.$action['listprojects'].'" NAME="main_page">';
	
?>
	
</FRAMESET>

</HEAD>

<BODY>

</BODY>
</HTML>