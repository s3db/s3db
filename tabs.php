<?php
	#tabs.php is the file with the links for project, my account, etx. It should not be used stand alone but included in a stricpt that has S3DBjavascript included as well
	ini_set('display_errors',0);
	if($_REQUEST['su3d']) {
		ini_set('display_errors',1);
	}
	if(!in_array('s3style.php', get_included_files())) {
		include_once('s3style.php');
	}

	#echo $show_name = substr($user_info['account_lid'], str_searchint start, [int length])('http://[a-zA-Z0-9]+#', '', );exit;
	$show_name = ereg_replace("(http://.*)#", "", $user_info['account_lid']);
?>
<!-- BEGIN header -->
<body class="section-<?php echo $section_num ?>">
<table class="head">
	<tr>
		<td align="left" valign="middle">
			<a href="<?php echo $action['home']?>">
				<img src="<?php echo S3DB_URI_BASE.'/images/logo.png'?>" border ="0" height="40" alt="S3DB">
			</a>
		</td>
		<td align="right" valign="bottom">Login: <b><?php echo $show_name.' (User '.$user_info['account_id'].')'; ?></b><br/><?php echo date("D M j, G:i:s T Y") ?></td>
	</tr>
</table>
<ul id="menu">
	<li id="nav-1">
		<a href="<?php echo $action['home']?>">Home</a>
	</li>
<?php
	if (user_is_admin($user_id, $db)) {
		echo '
	<li id="nav-2">
		<a href="'.$action['admin'].'" target="_parent">Admin</a>
		<ul id="subnav-2">';
		if($user_id!='1'){
			echo '<li><a href="'.$action['edituser'].(($_REQUEST['id']=='')?'&id='.$user_id.'':'').'" target="_parent">My Account</a></li>';
		}
		echo '
			<li><a href="'.$action['listusers'].'"  target="_parent">User Manager</a></li>
			<li><a href="'.$action['listgroups'].'"  target="_parent">Group Manager</a></li>
			<li><a href="'.$action['accesslog'].'"  target="_parent">View Access Log</a></li>
			<li><a href="'.$action['listkeys'] .'"  target="_parent">Access Keys</a></li>
		</ul>
	</li>';
	} else {
		if (!ereg('p|r', $user_info['account_type'])) {
			echo '
	<li id="nav-2">
		<a href="'.$action['edituser'].'&id='.$user_id.'" target="_parent">My Account</a>
		<ul id="subnav-2">
		</ul>
	</li>';
		}
	}
?>
	<li id="nav-3">
		<a href="<?php echo $action['listkeys']?>" target="_parent">Keys</a>
	</li>
	<li id="nav-4">
		<a href="<?php echo $action['main']?>"  target="_parent">Project</a>
	</li>
	<li id="nav-5">
	 	<a href="<?php echo $action['websparql']?>" target="_parent">SPARQL</a>
	</li>
	<li id="nav-6">
		<a href="<?php echo $action['logout']?>" target="_parent">Logout</a>
	</li>
</ul>
<!-- END header -->
