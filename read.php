<?php
	#acecpts an S3QL query to download the queried file
	#Helena F Deus, March 18, 2009
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

	include_once(S3DB_SERVER_ROOT.'/dbstruct.php');
	include_once(S3DB_SERVER_ROOT.'/s3dbcore/authentication.php');
	include_once(S3DB_SERVER_ROOT.'/s3dbcore/display.php');
	include_once(S3DB_SERVER_ROOT.'/s3dbcore/callback.php');
	include_once (S3DB_SERVER_ROOT.'/s3dbcore/element_info.php');
	include_once (S3DB_SERVER_ROOT.'/s3dbcore/validation_engine.php');
	include_once (S3DB_SERVER_ROOT.'/s3dbcore/insert_entries.php');
	include_once (S3DB_SERVER_ROOT.'/s3dbcore/file2folder.php');
	include_once (S3DB_SERVER_ROOT.'/s3dbcore/update_entries.php');
	include_once (S3DB_SERVER_ROOT.'/s3dbcore/delete_entries.php');
	include_once (S3DB_SERVER_ROOT.'/s3dbcore/datamatrix.php');
	include_once (S3DB_SERVER_ROOT.'/s3dbcore/create.php');
	include_once (S3DB_SERVER_ROOT.'/s3dbcore/permission.php');
	include_once (S3DB_SERVER_ROOT.'/s3dbcore/list.php');
	include_once (S3DB_SERVER_ROOT.'/s3dbcore/S3QLRestWrapper.php');
	include_once (S3DB_SERVER_ROOT.'/s3dbcore/SQL.php');
	include_once (S3DB_SERVER_ROOT.'/s3dbcore/S3QLaction.php');
	include_once (S3DB_SERVER_ROOT.'/s3dbcore/htmlgen.php');
	include_once (S3DB_SERVER_ROOT.'/s3dbcore/acceptFile.php');
	include_once (S3DB_SERVER_ROOT.'/s3dbcore/URIaction.php');
	include_once(S3DB_SERVER_ROOT.'/s3dbcore/common_functions.inc.php');
	
	$format = $_REQUEST['format'];
	if($format=='') { $format='html'; }

	#if a key has been provided, validate the key
	$key=$_REQUEST['key'];
	include_once('core.header.php');
	
	$query=($_REQUEST['query']!="")?$_REQUEST['query']:$_REQUEST['q'];
	if($query=='') { 
		echo formatReturn('3','Please input an S3QL query.',$format,'');
		exit;
	}
	$q=compact('query','format','key','user_id','db');
	$s3ql=parse_xml_query($q);
	$s3ql['db']=$db;
	$s3ql['user_id']=$user_id;

	$data = S3QLaction($s3ql);
	if(count($data)>1) {
		$s3ql['order_by']='created_on desc';
		$s3ql['limit']='1';
		$data = S3QLaction($s3ql);
	}
	if($data[0]['file_name']=='') {
		echo $data[0]['value'];
	} else {
		$statement_info = $data[0];
		pushDownload2Header(compact('statement_info', 'db', 'user_id', 'format'));
	}
?>