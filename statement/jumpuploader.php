<?php
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
if(!$key) $key=get_user_key($user_id, $db);
$item_id = $_REQUEST['item_id'];
$rule_id = $_REQUEST['rule_id'];
if(!$item_id || !$rule_id){
	echo formatReturn('3',"Please specify item_id and rule_id", $_REQUEST['format'],'');
	exit;
}
$url = $GLOBALS['URI']."/multi_upload.php?key=".$key."&rule_id=".$rule_id."&item_id=".$item_id;

?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
 <head>
  <title> header </title>
  
 </head>

 <body>
  <applet name="jumpLoaderApplet"
		code="jmaster.jumploader.app.JumpLoaderApplet.class"
		archive="jumploader_z.jar"
		width="600"
		height="400" 
		mayscript>
	<param name="uc_uploadUrl" value="<?php echo $url; ?>"/>
</applet>
 </body>

</html>
