<?php
#generate a pair of public/private keys for authentication. The public key will be stored in s3db.org/central, along with the url for this implementation

require_once 'Crypt/RSA.php';

$keys = generate_key_pair(); 

/***********************************************************/
function generate_key_pair()
{
    $key_length = '64';

    $key_pair = new Crypt_RSA_KeyPair($key_length);
    check_error($key_pair);

    $public_key = $key_pair->getPublicKey();
    $private_key = $key_pair->getPrivateKey();
    $keys = array('public'=>$public_key->toString(), 'private'=>$private_key->toString());
	
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