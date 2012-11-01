<?php
	#encrypt.php acepts a string and encripts it using the s3db public key;
	ini_set('display_errors',0);
	if($_REQUEST['su3d']) {
		ini_set('display_errors',1);
	}
	if(file_exists('config.inc.php')){
		include('config.inc.php');
	} else {
		Header('Location: login.php');
		exit; 
	}
	@set_time_limit(0);
	@ini_set('memory_limit', '256M');
	@ini_set('upload_max_filesize', '128M');
	@ini_set('post_max_size', '256M');
	@ini_set('max_input_time', '-1');
	@ini_set('max_execution_time', '-1');
	@ini_set('expect.timeout', '-1');
	@ini_set('default_socket_timeout', '-1');

	$key=($_REQUEST['crypt']!="")?$_REQUEST['crypt']:$_REQUEST['key'];
	include_once('core.header.php');

	$str=$_REQUEST['string'];
	if(!$str) {
		$que = $_REQUEST['query'];
		$que = S3DB_URI_BASE.'/S3QL.php?key='.$_REQUEST['key'].'&query='.$que.'&format=php';
		$queryStr = stream_get_contents(fopen($que, 'r'));
		$queryData=unserialize($queryStr);
	}

	if(!$str && !$que) {
		echo (formatReturn(7,"Please specify what string/query you wish to decrypt.", $_REQUEST["format"]));exit;
	}
	if(empty($queryData)) {
		echo (formatReturn(7,"Query returned no results. Please check that you have permission to perform this query first.", $_REQUEST["format"]));exit;
	}

	$privateKey = $_REQUEST["private_key"];
	if(!$privateKey) {
		##Find if this user provided a key and the elements to gather a private key
		if($key) {
			$s3ql=compact('user_id','db');
			$s3ql['from']='project';
			$done = S3QLaction($s3ql);
			if(is_array($done)) {
				foreach ($done as $proj_info) {
					if($proj_info['name']=='EncriptionKeys') {
						$s3ql=compact('user_id','db');
						$s3ql['from']='rules';
						$s3ql['where']['project_id']=$proj_info['project_id'];
						$done=false;
						$done = S3QLaction($s3ql);
					
						if(is_array($done)) {
							foreach ($done as $rule_info) {
								if($rule_info['object']=='private_key') {
									$s3ql=compact('user_id','db');
									$s3ql['from']='statements';
									$s3ql['where']['rule_id'] = $rule_info['rule_id'];
									$done = S3QLaction($s3ql);
									if($done[0]['value']!='') {
										$privateKey = $done[0]['value'];
									}
								}
							}
						}
					}
				}
			}
		}
		if(!$privateKey) {
			echo (formatReturn(7,"Could not retrieve a private key. Please specify a valid private key to decript this string", $_REQUEST["format"]));
			exit;
		}
	} else {
		include_once(S3DB_SERVER_ROOT."/s3dbcore/encryption.php");
	}
	if($str) {
		$decrypted = decrypt($str, $privateKey);
		if(!$decrypted) {
			echo (formatReturn(7,"Your private key appears to be the wrong key for the string.", $_REQUEST["format"]));
			exit;
		}
		echo $decrypted;
	} else {
		if(is_array($queryData)) {
			foreach ($queryData as $d=>$data) {
				foreach ($data as $e=>$data_value) {
					$decrypted = decrypt($data_value, $privateKey);
					if($decrypted) {
						$queryData[$d][$e] = $decrypted;
					}
				}
			}
		}
		$data = $queryData;
		$cols = array_keys($queryData[0]);
		$format=($_REQUEST['format']!="")?$_REQUEST['format']:'html';
		echo outputFormat(compact('data','cols', 'format'));
		exit;
	}
?>