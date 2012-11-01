<?php
	##Check4updates.php is a mothership function that informs sattelite deployments about the existance of new code updates
	ini_set('display_errors',0);
	if($_REQUEST['su3d']) {
		ini_set('display_errors',1);
	}
	if(file_exists('config.inc.php')) {
		include('config.inc.php');
	} else {
		Header('Location: index.php');
		exit;
	}

	$key= $GLOBALS['update_project']['key'];
	include 'core.header.php';
	$version = $_REQUEST['version'];
	switch($version) {
		case 'beta':
			$s3ql=compact('user_id','db');
			$s3ql['select']='value,notes';
			$s3ql['from']='statements';
			$s3ql['where']['statement_id']=$GLOBALS['update_project']['version']['beta'];
			$done = S3QLaction($s3ql);
			break;
		case 'stable':
			$s3ql=compact('user_id','db');
			$s3ql['select']='value,notes';
			$s3ql['from']='statements';
			$s3ql['where']['statement_id']=$GLOBALS['update_project']['version']['stable'];
			$done = S3QLaction($s3ql);
			break;
	}
	if($_REQUEST['options']=='explain') {
		$data[0] = array('date'=>$done[0]['value'],'explain'=>$done[0]['notes']);
		$cols =  array('date','explain');
		$format = $_REQUEST['format'];
		$z = compact('data','cols','format');
		echo outputFormat($z);
	} else {
		echo($done[0]['value']);	
	}
	exit;
?>