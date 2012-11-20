<?php
	/**
	 * include_once('../config.inc.php');
	 * include '../core.header.php';
	 * ini_set('include_path',  ini_get('include_path') . PATH_SEPARATOR . S3DB_SERVER_ROOT);
	 * require_once 'RSACrypt/RSA.php';
	 * 	
	 * #now decript
	 * if($_REQUEST['decrypt']) {
	 * 		echo $e=decrypt($_REQUEST['decrypt'], $GLOBALS['s3db_info']['deployment']['private_key']);
	 * }
	 * 
	 * if($_REQUEST['encrypt']) {
	 * 		echo $e=encrypt($_REQUEST['encrypt'], $GLOBALS['s3db_info']['deployment']['public_key']);
	 * }
	 */

	function encrypt($message, $publicKey) {
	    ini_set('include_path',  ini_get('include_path') . PATH_SEPARATOR . S3DB_SERVER_ROOT);
		require_once 'pearlib/RSACrypt/RSA.php';
		$plain_text = $message;
	    $public_key = $publicKey;
	    
	    $key = Crypt_RSA_Key::fromString($public_key);
	    check_error($key);
	    $rsa_obj = new Crypt_RSA;
	    check_error($rsa_obj);
	    $enc_text = $rsa_obj->encrypt($plain_text, $key);
	    check_error($rsa_obj);
		return ($enc_text);
	}

	function decrypt($encriptedMessage, $privateKey) {
		ini_set('include_path',  ini_get('include_path') . PATH_SEPARATOR . S3DB_SERVER_ROOT);
		require_once 'pearlib/RSACrypt/RSA.php';
		$enc_text = $encriptedMessage;
		$private_key =$privateKey;
	
		$key = Crypt_RSA_Key::fromString($private_key);
		check_error($key);
		$rsa_obj = new Crypt_RSA;
		check_error($rsa_obj);
		$rsa_obj->setParams(array('dec_key' => $key));
		check_error($rsa_obj);
		$plain_text = $rsa_obj->decrypt($enc_text);
		check_error($rsa_obj);
		return ($plain_text);
	}

	function check_error(&$obj) {
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
?>