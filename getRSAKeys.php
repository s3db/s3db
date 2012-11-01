<?php
	#RSA Keys is a very simple function that relies on an php RSA library to generate a pair of public/private key given an authentication mechanism (a key). Output formats match those of s3db
	if(file_exists('config.inc.php')) {
		include('config.inc.php');
	} else {
		exit;
	}
	#Get the key, send it to check validity
	#When the key goes in the header URL, no need to read the xml, go directly to the file
	$key= $_REQUEST['key'];
	include_once('core.header.php');

	#RSA library
	include_once(S3DB_SERVER_ROOT.'/pearlib/RSACrypt/RSA.php');	
	#include_once('html2cell.php');
	#Profilling... 
	if(is_file(S3DB_SERVER_ROOT.'/pearlib/Benchmark/Timer.php')) {
		require_once S3DB_SERVER_ROOT.'/pearlib/Benchmark/Timer.php';
		$timer = new Benchmark_Timer();
		$timer->start();
	}

	$RSAkeys = generate_key_pair();
	if($_REQUEST['action']=='save' || $_REQUEST['action']=='view') {
		list($created, $encryption)=createEncryptionProject($user_id,$db,$RSAkeys);
		if($created && $_REQUEST['action']=='save') {
			echo formatReturn("0","RSA Key pair saved. Use an s3db key from the current user to decrypt messages using decrypt.php", $_REQUEST['format']);
			exit;
		} elseif($created && $_REQUEST['action']=='view') {
			$RSAKeys=array();
			$data[0]['public_key'] = $encryption['public_key'];
			$data[0]['private_key'] = $encryption['private_key'];
		} else {
			echo formatReturn("1","Could not save the key pair.", $_REQUEST['format']);	
			exit;
		}
	} else {
		$data =  array(0=>array('public_key'=>$RSAkeys['public'], 'private_key'=>$RSAkeys['private']));
	}

	$cols = array('public_key', 'private_key');
	$format = ($_REQUEST['format']!="")?$_REQUEST['format']:'html';
	$z = compact('data','cols', 'format');
	echo outputFormat($z);
	exit;

	function generate_key_pair() {
	    $key_length = '64';
	    $key_pair = new Crypt_RSA_KeyPair($key_length);
	    check_error_pair($key_pair);
	    $public_key = $key_pair->getPublicKey();
	    $private_key = $key_pair->getPrivateKey();
	    $keys = array('public'=>$public_key->toString(), 'private'=>$private_key->toString());
		return ($keys);
	}

	// error handler
	function check_error_pair(&$obj) {
		if($obj->isError()) {
			$error = $obj->getLastError();
			switch ($error->getCode()) {
				case CRYPT_RSA_ERROR_WRONG_TAIL :
					// nothing to do
					break;
				default:
					// echo error message and exit
					echo 'error: ', $error->getMessage();
					exit;
	        }
	    }
	}

	function createEncryptionProject($user_id,$db,$RSAkeys) {
		$encryption['project_name'] = 'EncriptionKeys';
		$encryption['project_description'] = 'Project with the purpose of storing a public/private key pair, making in accessible through an s3db key.';
		$encryption['collection_name'] = 'Keys';
		$encryption['rule'][0] = array('Keys','RSA', 'public_key');
		$encryption['rule'][1] = array('Keys','RSA', 'private_key');

		##does this user have a project for private keys? If so, he cannot create another
		$s3ql=compact('user_id','db');
		$s3ql['from']='project';
		$s3ql['where']['name']=$encryption['project_name'];
		$done = S3QLaction($s3ql);
	
		if(is_array($done) &&!empty($done)) {
			$encryption['project_id'] =$done[0]['project_id'];
		} else {
			$s3ql=compact('user_id','db');
			$s3ql['insert']='project';
			$s3ql['where']['name']=$encryption['project_name'];
			$s3ql['where']['description']=$encryption['project_description'];
			$s3ql['format']='php';
			$done = S3QLaction($s3ql);
			$msg=unserialize($done);$msg = $msg[0];
			if($msg['project_id']) { $encryption['project_id'] = $msg['project_id']; }
		}

		if(!$encryption['project_id']) {
			return (false);
		}
	
		$s3ql=compact('user_id','db');
		$s3ql['from']='collection';
		$s3ql['where']['project_id']=$encryption['project_id'];
		$s3ql['where']['name']=$encryption['collection_name'];
		$done = S3QLaction($s3ql);
		
		if(is_array($done) &&!empty($done)) {
			$encryption['collection_id'] =$done[0]['collection_id'];
		} else {
			$s3ql=compact('user_id','db');
			$s3ql['insert']='collection';
			$s3ql['where']['project_id']=$encryption['project_id'];
			$s3ql['where']['name']=$encryption['collection_name'];
			$s3ql['format']='php';
			$done = S3QLaction($s3ql);
			$msg=unserialize($done);$msg = $msg[0];
			if($msg['collection_id']) { $encryption['collection_id'] =$msg['collection_id']; }
		}

		if(!$encryption['collection_id']) {
			return (false);
		}

		$s3ql=compact('user_id','db');
		$s3ql['from']='rules';
		$s3ql['where']['project_id']=$encryption['project_id'];
		$s3ql['where']['subject_id']=$encryption['collection_id'];
		$s3ql['where']['object']=$encryption['rule'][0][2];
		$done = S3QLaction($s3ql);
		
		if(is_array($done) &&!empty($done)) {
			$encryption['rule'][0]['rule_id'] =$done[0]['rule_id'];
		} else {
			$s3ql=compact('user_id','db');
			$s3ql['insert']='rule';
			$s3ql['where']['project_id']=$encryption['project_id'];
			$s3ql['where']['subject_id']=$encryption['collection_id'];
			$s3ql['where']['verb']=$encryption['rule'][0][1];
			$s3ql['where']['object']=$encryption['rule'][0][2];
			$s3ql['format']='php';
			$done = S3QLaction($s3ql);$msg=unserialize($done);$msg = $msg[0];
			if($msg['rule_id']) { $encryption['rule'][0]['rule_id'] =$msg['rule_id']; }
		}

		if (!$encryption['rule'][0]['rule_id']) {
			return (false);	
		}

		$s3ql=compact('user_id','db');
		$s3ql['from']='rules';
		$s3ql['where']['project_id']=$encryption['project_id'];
		$s3ql['where']['subject_id']=$encryption['collection_id'];
		$s3ql['where']['object']=$encryption['rule'][1][2];
		$done = S3QLaction($s3ql);
		
		if(is_array($done) &&!empty($done)){
			$encryption['rule'][1]['rule_id'] =$done[0]['rule_id'];
		} else {
			$s3ql=compact('user_id','db');
			$s3ql['insert']='rule';
			$s3ql['where']['project_id']=$encryption['project_id'];
			$s3ql['where']['subject_id']=$encryption['collection_id'];
			$s3ql['where']['verb']=$encryption['rule'][1][1];
			$s3ql['where']['object']=$encryption['rule'][1][2];
			$s3ql['format']='php';
			$done = S3QLaction($s3ql);$msg=unserialize($done);$msg = $msg[0];
			if($msg['rule_id']) { $encryption['rule'][1]['rule_id'] =$msg['rule_id']; }
		}

		if (!$encryption['rule'][1]['rule_id']) {
			return (false);	
		}
		
		$s3ql=compact('user_id','db');
		$s3ql['from']='item';
		$s3ql['where']['collection_id']=$encryption['collection_id'];
		$s3ql['where']['notes']='KeyPair';
		$done = S3QLaction($s3ql);
		
		if(is_array($done) && !empty($done)){
			$encryption['item_id'] =$done[0]['item_id'];
		} else {
			$s3ql=compact('user_id','db');
			$s3ql['insert']='item';
			$s3ql['where']['collection_id']=$encryption['collection_id'];
			$s3ql['where']['notes']='KeyPair';
			$s3ql['format']='php';
			$done = S3QLaction($s3ql);$msg=unserialize($done);$msg = $msg[0];
			if($msg['item_id']) { $encryption['item_id'] =$msg['item_id']; }
		}

		if(!$encryption['item_id']) {
			return (false);	
		}
		
		$s3ql=compact('user_id','db');
		$s3ql['from']='statement';
		$s3ql['where']['item_id']=$encryption['item_id'];
		$s3ql['where']['rule_id']=$encryption['rule'][0]['rule_id'];
		$done = S3QLaction($s3ql);
		
		if(is_array($done) && !empty($done)) {
			$encryption['public_key'] =$done[0]['value'];
		}

		$s3ql=compact('user_id','db');
		$s3ql['from']='statement';
		$s3ql['where']['item_id']=$encryption['item_id'];
		$s3ql['where']['rule_id']=$encryption['rule'][1]['rule_id'];
		$done = S3QLaction($s3ql);
		if(is_array($done) && !empty($done)){
			$encryption['private_key'] =$done[0]['value'];
		}

		if(!$encryption['public_key'] || !$encryption['private_key']) {
			##pub and private key must be created simultaneaouly
			$s3ql=compact('user_id','db');
			$s3ql['insert']='statement';
			$s3ql['where']['item_id']=$encryption['item_id'];
			$s3ql['where']['rule_id']=$encryption['rule'][0]['rule_id'];
			$s3ql['where']['value']=$RSAkeys['public'];
			$s3ql['format']='php';
			$done = S3QLaction($s3ql);$msg=unserialize($done);$msg = $msg[0];
			if($msg['statement_id']) { $encryption['public_key'] =$RSAkeys['public']; }

			$s3ql=compact('user_id','db');
			$s3ql['insert']='statement';
			$s3ql['where']['item_id']=$encryption['item_id'];
			$s3ql['where']['rule_id']=$encryption['rule'][1]['rule_id'];
			$s3ql['where']['value']=$RSAkeys['private'];
			$s3ql['format']='php';
			$done = S3QLaction($s3ql);$msg=unserialize($done);$msg = $msg[0];
			if($msg['statement_id']) { $encryption['private_key'] =$RSAkeys['private']; }
		}
		
		if (!$encryption['public_key'] || !$encryption['private_key']) {
			return (false);	
		}

		return array(true,$encryption);
	}
?>