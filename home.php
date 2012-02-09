<?php
#home.php is the initial s3db interface - displays the purpose of the s3db innitiative.
#Helena F Deus (helenadeus@gmail.com)
ini_set('display_errors',0);
	if($_REQUEST['su3d'])
	ini_set('display_errors',1); 
	if(file_exists('config.inc.php'))
	{
		include('config.inc.php');
	}
	else
	{
		Header('Location: index.php');
		exit;
	}
	
 
	include(S3DB_SERVER_ROOT.'/core.header.php');
	include 'webActions.php';
	
	
	
	$image_path = '.';
	$section_num = '1';
	$website_title = $GLOBALS['s3db_info']['server']['site_title'].' - Home';
	$site_intro = $GLOBALS['s3db_info']['server']['site_intro'];
	
	
include	's3style.php';
include 'tabs.php';
?>
<!-- BEGIN contents -->
<body onload="if (self != top) top.location = self.location">
<table class="contents">
	<tr><td class="message"><br /><?php echo $message ?><br /><br /></td></tr>
	<tr><td><h2>S<sup>3</sup>DB Initiative</h2></td></tr>
	<tr><td>
	<?php echo $site_intro ?>
	</td></tr>
	<tr><td><BR><BR>
	</td></tr>
	<tr><td>
	<?php 
	if($user_id=='1')
	{echo '<input type="button" name="config" value="Change Configuration" onClick = "window.location=\''.$action['dbconfig'].'\'">';
	
	##For motherships, scan code updates
	if($GLOBALS['update_project']['file']['project_id']!=''){
	echo '&nbsp;&nbsp;&nbsp;&nbsp;<input type="button" name="scanUpdates" value="Scan File Changes" onClick = "window.location=\'scanModified.php\'">';
	}
	
	$BupdatesDt = check4updates('beta');
	
	#was it modified more thatn 1 hour ago?
	if(is_file('s3dbupdates.rdf'))
		{
		$release = date("Ymd Hi", filemtime('s3dbupdates.rdf'));
		
		}
	else { #This code will be as outdated as the last full version that was uploaded. Let's ask the mothership when was the			last version released
			$release = file_get_contents('release.txt');
		}
	
	if(strtotime($BupdatesDt)-strtotime($release)>=60*60) $Bdisabled = ' enabled';
	else $Bdisabled = ' disabled';
	
	
	echo '&nbsp;&nbsp;&nbsp;&nbsp;<input type="button" name="checkupdates" value="Update Beta Code" onClick = "window.location=\''.$action['selfupdate'].'&date='.date("d-m-Y", strtotime($release)).'\'"'.$Bdisabled.'>';

	$SupdatesDt = check4updates('stable');
	
	if(strtotime($SupdatesDt)>strtotime($release)) $Sdisabled = ' enabled';
	else $Sdisabled = ' disabled';

	echo '&nbsp;&nbsp;&nbsp;&nbsp;<input type="button" name="checkupdates" value="Update Stable Code" onClick = "window.location=\''.$action['selfupdate'].'&date='.date("d-m-Y", strtotime($release)).'\'"'.$Sdisabled .'>';
	#echo '&nbsp;&nbsp;&nbsp;&nbsp;<input type="button" name="checkupdates" value="Update Code" onClick = "window.location=\''.$action['selfupdate'].'\'">';
	
	echo '<div class="message">Last code update was on '. date("l, dS F, Y @ h:ia", strtotime($release)).'. Updating only affects minor bugs, it will not affect major functionality. Before reporting a bug, please update your code, the bug might have been fixed.</div>';
	$Did=(!ereg('^D',$GLOBALS['s3db_info']['deployment']['Did']))?'D'.$GLOBALS['s3db_info']['deployment']['Did']:$GLOBALS['s3db_info']['deployment']['Did'];
	echo '<div class="message">This is deployment '.$Did.'</div>';
	###
	#Retrieve the new updates.rdf from the mothership
	include('rdfheader.inc.php');
	if(!empty($GLOBALS['s3db_info']['deployment']['mothership'])) {
	
	
	
	}
	}
	?>
	</td></tr>
	
	<tr><td><br /></td></tr>
</table>
<!-- END contents -->

<?php
include 'footer.php';

function check4updates($version)
{
	##Ckeck for updates from mothership
	$codeSource = ($GLOBALS['s3db_info']['deployment']['code_source']!='')?$GLOBALS['s3db_info']['deployment']['code_source']:$GLOBALS['s3db_info']['deployment']['mothership'];

	#$ms = $GLOBALS['s3db_info']['deployment']['mothership'];
	$url2call = $codeSource.'check4updates.php?version='.$version;
	#echo $url2call;exit;
	$fid=@fopen($url2call, 'r');
	if($fid)
	{$updatesAvailable = stream_get_contents($fid);
	
	return ($updatesAvailable);
	}
	else
	return (false);
}
?>