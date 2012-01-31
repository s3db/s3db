<?php
#s3encrition contains functions tha use RSA algorithm
function encrypt1($message, $publicKey)
{	#because there is already an encrypt,this one is encrypt1
    ini_set('include_path',  ini_get('include_path') . PATH_SEPARATOR . S3DB_SERVER_ROOT);
	require_once 'pearlib/RSACrypt/RSA.php';
	$plain_text = $message;
    $public_key = $publicKey;
    
    $key = Crypt_RSA_Key::fromString($public_key);
   
	
	if($key->isError()) 
		{return("");}
	
	check_error($key);
    $rsa_obj = new Crypt_RSA;
    
	check_error($rsa_obj);
	
    
    $enc_text = $rsa_obj->encrypt($plain_text, $key);
    check_error($rsa_obj);
    
	return ($enc_text);
}

function decrypt($encriptedMessage, $privateKey)
{
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


function check_error(&$obj)
{
    if ($obj->isError()) {
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