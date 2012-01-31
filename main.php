<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.0 Transitional//EN">
<HTML>
<HEAD>
<TITLE> S3DB  </TITLE>
<?php
ini_set('display_errors',0);
	if($_REQUEST['su3d'])
	ini_set('display_errors',1);
if(file_exists('config.inc.php'))
	{
		include('config.inc.php');
	}
	else
	{
		echo '<META HTTP-EQUIV="Refresh" Content= "0; target="_parent" URL="login.php?error=2">';
		exit;
	}
	
		
	
	#$args ='?key='.$_REQUEST['key'].'&url='.$_REQUEST['url'].'&project_id='.$_REQUEST['project_id'].'&resource_id='.$_REQUEST['resource_id'];
	
	
	include 'webActions.php';

	echo '<FRAMESET  ROWS="20%,80%" Border="2">';
		
	echo '<FRAME SRC="'.$action['header'].'" NAME="header">';
	 
	echo '<FRAME SRC="'.$action['projectFrames'].'" NAME="rest_of_frames" Border="1">'; 
?>
</FRAMESET>

</HEAD>

<BODY>

</BODY>
</HTML>