<?php
#generate a pair of public/private keys for authentication. The public key will be stored in s3db.org/central, along with the url for this implementation

#require_once 'pearlib/RSACrypt/RSA.php';


/***********************************************************/

function generate_key_pair()
{
    set_include_path(get_include_path() . PATH_SEPARATOR . S3DB_SERVER_ROOT.'/pearlib/phpseclib');

	if(is_file(S3DB_SERVER_ROOT.'/pearlib/phpseclib/Crypt/RSA.php')){
	include(S3DB_SERVER_ROOT.'/pearlib/phpseclib/Crypt/RSA.php');
	define('CRYPT_RSA_SMALLEST_PRIME', 1000);
	
	$rsa = new Crypt_RSA();
	$createKey = $rsa->createKey(10 * 64);
	
	$keys = array('public'=>base64_encode($createKey['publickey']), 'private'=>$createKey['privatekey']);
	}

	else {
	require_once S3DB_SERVER_ROOT.'/pearlib/RSACrypt/RSA.php';	
	$key_length = '64';

    $key_pair = new Crypt_RSA_KeyPair($key_length);
    
	check_error($key_pair);

    $public_key = $key_pair->getPublicKey();
    $private_key = $key_pair->getPrivateKey();
    $keys = array('public'=>$public_key->toString(), 'private'=>$private_key->toString());
		
	}
	return ($keys);
}

// error handler
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