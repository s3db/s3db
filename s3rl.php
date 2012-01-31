<?php
#this script accepts inputs of url,public key and, in case an update is attemtped, an encripted message as well
#where should information on url and public key go? Special project where only admin can access.

 ini_set('display_errors',0);
	if($_REQUEST['su3d'])
	ini_set('display_errors',1);
if($_REQUEST['url']!='' && $_REQUEST['publicKey']!='')
{$case = 'registernewURL';
}
elseif(($_REQUEST['Did']!='' || $_REQUEST['url']!='') && !in_array('newUrl', array_keys($_REQUEST)))
$case = 'findURL';
elseif($_REQUEST['Did']!='' && in_array('newUrl', array_keys($_REQUEST)))
$case = 'updateURL';
else {
	echo 's3rl.php is a function to retrieve the url of a specific Did. For documentation and usage please refer to  <a href="http://s3db.org/">s3db.org</a>';

}


$url =$_REQUEST['url'];
$publicKey = urldecode($_REQUEST['publicKey']);
$Did = ($_REQUEST['s3rl']!='')?$_REQUEST['s3rl']:$_REQUEST['Did'];
$newUrl = $_REQUEST['newUrl'];
$message = $_REQUEST['message'];
$name = $_REQUEST['name'];
$_REQUEST['description'] = is_base64_encoded($_REQUEST['description'])?base64_decode($_REQUEST['description']):$_REQUEST['description'];
 $description=$_REQUEST['description'];
$format=$_REQUEST['format'];



list($regValid, $msg) = registerURL(compact('url', 'publicKey', 'Did', 's3rl', 'newUrl', 'case', 'message','name','format'));

if($regValid)
{	$Did = $msg['deployment_id'];
	$finName = $msg['name'];
		
if($case == 'registernewURL')
	{echo formatReturn('0','URL registered', $_REQUEST['format'], array('deployment_id'=>$Did, 'name'=>$finName));
	}
elseif($case == 'findURL')
	{
	
	$cols = array_keys($regs[1]);
	$data[0] = $regs[1];
	
	$format =($_REQUEST['format']=='')?'html':$_REQUEST['format'];
	$z = compact('data','cols', 'format');
	
	echo outputFormat($z);

		
	}
elseif($case == 'updateURL')
echo formatReturn($GLOBALS['error_codes']['success'],$msg,$format,array('newUrl'=>$newUrl));
exit;
}
else {
	echo formatReturn($GLOBALS['error_codes']['wrong_input'],$msg,$format,'');
	exit;
}

function registerURL($U)
{#this function call the db but it should NOT leave this one function
extract($U);


include_once('config.inc.php');
$key=$GLOBALS['deployment_project']['key'];
include 'core.header.php';
#include (S3DB_SERVER_ROOT.'/webActions.php');
#require_once(S3DB_SERVER_ROOT.'/s3dbcore/class.db.inc.php');
#include_once(S3DB_SERVER_ROOT.'/s3dbcore/common_functions.inc.php');
#include_once(S3DB_SERVER_ROOT.'/s3dbcore/callback.php');
#Generate Did, and,  since we're on it, name if empty
if(!$Did && $case=='registernewURL')
$Did = s3id();
if(!$name && $case=='registernewURL'){
$name = 'D'.$Did;
$U['name']=$name;
}

$db = CreateObject('s3dbapi.db');
$db->Halt_On_Error = 'no';
$db->Host     = $GLOBALS['s3db_info']['server']['db']['db_host'];
$db->Type     = $GLOBALS['s3db_info']['server']['db']['db_type'];
$db->Database = $GLOBALS['s3db_info']['server']['db']['db_name'];
$db->User     = $GLOBALS['s3db_info']['server']['db']['db_user'];
$db->Password = $GLOBALS['s3db_info']['server']['db']['db_pass'];
$db->connect();


$U['db']=$db;
list($inputValid, $errMessage)=validate_register_inputs($U);

if($inputValid){

switch ($case) {
	case 'registernewURL':{
			$protocol = ($_SERVER['HTTPS']!='')?'https://':'http://';
			$url =ereg('localhost', $url)?$protocol.getClientIP().str_replace($protocol.'localhost', '', $url):$url;
			
			
			#echo $sql;exit;
			#echo $s3rl;exit;
			
			if($Did!=''){
			$sql = "select * from s3db_deployment where deployment_id = '".$Did."'";
			$db->query($sql, __LINE__, __FILE__);

			if($db->next_record())
					{
					return array(False, 'Did already exists. Please provide another one or leave that field blank for an arbitrary value');
					}
				
			}
			
			#if($Did!=''){
			$sql = "insert into s3db_deployment (deployment_id, url, publickey, message, created_on) values ('".$Did."', '".$url."', '".$publicKey."', '".random_string(20)."', now())";
			#echo $sql;
			$db->query($sql, __LINE__, __FILE__);
			$s3rl = $Did;
				
			
			#}
			#else{
			#include('s3id.php');
			
			
			#$sql = "insert into s3db_deployment (deployment_id, url, publickey, message, created_on) values ('".$Did."', '".$url."', '".$publicKey."', '".random_string(20)."', now())";
			
			#echo $sql;exit;
			#$db->query($sql, __LINE__, __FILE__);
			
			
			if($db->Errno==0){
				
				
				##Now create an entry in the project of deployemnts;
				#create a remote user to access this entry
				$s3ql=compact('user_id','db');
				$s3ql['insert']='user';
				$s3ql['where']['user_id']=$url.((substr($url, strlen($url)-1,1)=='/')?'':'/').'U1';
				$s3ql['where']['permission_level']='111';
				$s3ql['format']='php';
				$done = unserialize(S3QLaction($s3ql));
				
				//$msg=html2cell($done);
				$msg=$done[0];
				
				if(ereg('^(4|9|0)$', $msg['error_code'])){
				$remoteUser = $s3ql['where']['user_id'];
				$user_id = '1';
				$s3ql=compact('user_id','db');
				$s3ql['insert']='item';
				$s3ql['where']['collection_id']=$GLOBALS['deployment_project']['collection_id'];
				$s3ql['where']['item_id']=$Did;
				$s3ql['where']['notes']=($name=='')?urlencode('Deployment '.$Did):$name;
				$s3ql['format']='php';
				
				$done = unserialize(S3QLaction($s3ql));
				$msg=$done[0];
				
				
				
				if($msg['error_code']=='0' || $msg['error_code']=='4')
					{
					$s3ql=compact('user_id','db');
					$s3ql['insert']='user';
					$s3ql['where']['item_id']=$msg['item_id'];
					$s3ql['where']['user_id']=$remoteUser;
					$s3ql['where']['permission_level']='222';
					$done = S3QLaction($s3ql);
					$item_id = $msg['item_id'];
					$msg=html2cell($done);$msg=$msg[2];

					#find the collectiont rules
					$s3ql=compact('user_id','db');
					$s3ql['from']='rules';
					$s3ql['where']['subject_id']=$GLOBALS['deployment_project']['collection_id'];
					$rules = S3QLaction($s3ql);

					if(!empty($rules)){
						$s3ql=compact('user_id','db');
						$s3ql['insert']='statement';
						$s3ql['where']['item_id']=$item_id;
						
						
						foreach ($rules as $key=>$rule_info) {
							if(in_array($rule_info['object'], array_keys($_GET))){
								$s3ql['where']['rule_id']=$rule_info['rule_id'];
								
								if($rule_info['object']=='keywords'){
								$keywords = explode(',', $_GET['keywords']);
								
								foreach ($keywords as $word) {
								
								if(is_base64_encoded($word)) $word = base64_decode($word);
								
								$s3ql['where']['value']=$word;
								
								$done = S3QLaction($s3ql);
								
								}
								}
								elseif($rule_info['object']=='url'){
								$s3ql['where']['value']=$url;
								$done = S3QLaction($s3ql);
								}
								elseif($rule_info['object']=='description'){
								$v=$_GET[$rule_info['object']];
								
								if(is_base64_encoded($v)) $v=base64_decode($v);
								$s3ql['where']['value']=$v;
								$done = S3QLaction($s3ql);
								}
								elseif($rule_info['object']=='name'){
								$s3ql['where']['value']=$name;
								$done = S3QLaction($s3ql);
								}
								else{
								$v=$_GET[$rule_info['object']];
								
								$s3ql['where']['value']=$v;
								$done = S3QLaction($s3ql);
								
								}
							}
							
						}
						
						}


					}
				
				
				}
			}
			
			#After registering url, return Did
			if($Did!='')
			return array(True, array('deployment_id'=>$Did,'name'=>$name));
			else {
				return array(False, '');
			}
			
		 break;
	}


	case 'findURL':{
		##Let's first check if there is already a deployment in this URL
		if($url!=''){
		$sql = "select * from s3db_deployment where url = '".$url."'";
		$db->query($sql, __LINE__, __FILE__);

		if($db->next_record())
		{
			$reg = array('deployment_id'=>$db->f('deployment_id'), 'url'=>$db->f('url'), 'modified_on'=>$db->f('modified_on'));
			return (array(True, $reg));
		}
		}
		elseif($Did!='') {
			

		$sql = "select * from s3db_deployment where deployment_id = '".str_replace('D', '', $Did)."'";
		$db->query($sql, __LINE__, __FILE__);

		if($db->next_record())
				{
				$reg = array('url'=>$db->f('url'),
								'publicKey'=>$db->f('publickey'),
								'modified_on'=>$db->f('modified_on'));
				return array(True, $reg);
				}
				else {
					return array(False, 'Did not found');
				}

		}
		break;
	}

case 'updateURL':{
#did the user send the decripted message already? Validate and change the message

if($message!='') #check if it matches whatever is in store for this Did
	{
	$sql = "select * from s3db_deployment where deployment_id = '".str_replace('D', '', $Did)."'";
	
	$db->query($sql, __LINE__, __FILE__);

	if($db->next_record())
		{
		$oldUrl= $db->f('url');
		$storedMessage= $db->f('message');
		
		#do messages match?
		if($storedMessage!=$message)
			{
			#$sql = "update s3db_register set message= '".random_string(20)."' where deployment_id = '".str_replace('D', '', $Did)."'";
			
			return array(False, 'Error code:<error>3</error><description> Decripted Message does not match the request</description>');
			}
		else {
			#echo 'ahaa, you found it :-)';
			#update key
			$sql = "update s3db_deployment set url = '".$newUrl."', message= '".random_string(20)."', modified_on = now() where deployment_id = '".str_replace('D', '', $Did)."'";
			
			$db->query($sql, __LINE__, __FILE__);
			
			##Now update the entry on s3db
			$sql = "update s3db_statement set url='".$newUrl."' where rule_id='' and item_id=''";
			
			
			$dbdata = get_object_vars($db);
			if($dbdata['Errno']=='0')
				return array(True, 'Error code:<error>0</error><description>  URL updated</description>');
			else {
				return array(False, 'Error code:<error>1</error><description>  Could not update URL</description>');
			}
			
			}
			


		}
	
		else {
			return array(False, 'Error code:<error>2</error> Could not find Did');
		}
	
	}

	else{
		#send a message to the url to make sure he is who he says he is
		$sql = "select * from s3db_deployment where deployment_id = '".ereg_replace('^D', '', $Did)."'";
		
		$db->query($sql);
		if($db->next_record())
				{
				$publicKey= $db->f('publickey');
				$message= $db->f('message');
				}
				else {
					return array(False, 'Could not find URL');
				}
	  
		#encript it
		require_once 'pearlib/RSACrypt/RSA.php';	
		
		$encripted = encrypt($message, $publicKey);
		
		if($encripted=='')
		$encripted = encrypt($message, urlencode($publicKey));
	  
		$ErrMessage = "For authentication, please decript this message using your private key: <message>".rawurlencode($encripted)."</message><br />(Note: you might need to remove url encoding that your browser might have added before decoding. Plase refer to http://www.asciitable.com/ for the correct characters.)";
		
		echo formatReturn($GLOBALS['error_codes']['success'],$ErrMessage, $format, array('encripted'=>$encripted));
		exit;
		#echo "For authentication, please decript this message using your private key: <message>".rawurlencode($encripted)."</message>";
		#echo "<br />(Note: you might need to remove url encoding that your browser might have added before decoding. Plase refer to http://www.asciitable.com/ for the correct characters.)";
		#	exit;
	}

}
}
}
else {
	echo formatReturn($GLOBALS['error_codes']['wrong_input'],$errMessage, $format, '');
		
}
}

function encrypt($message, $publicKey)
{
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



function getClientIP() {
$ip;
if (getenv("HTTP_CLIENT_IP"))
$ip = getenv("HTTP_CLIENT_IP");
else if(getenv("HTTP_X_FORWARDED_FOR"))
$ip = getenv("HTTP_X_FORWARDED_FOR");
else if(getenv("REMOTE_ADDR"))
$ip = getenv("REMOTE_ADDR");
else
$ip = "UNKNOWN";
return $ip;

}

function is_base64_encoded($data)
    {
        if (preg_match('%^[a-zA-Z0-9/+]*={0,2}$%', $data)) {
            return TRUE;
        } else {
            return FALSE;
        }
    };
function validate_register_inputs($U)
{
	extract($U);
	#Things to check: name; Did
	
	#Check name: name cannot be equal to a Did and must be unique
	if($name){
		$sql = "select * from s3db_deployment where deployment_id = '".ereg_replace('^D','',$name)."'";

		$db->query($sql);
		
		if($db->next_record()){
			return (array(false, "Deployment names cannot be equal to an existing deployment UID."));
		}
		else {
		#Ok, all clear, now let's check the uniqueness of the name	
		$sql="select * from s3db_statement where rule_id = '".$GLOBALS['deployment_project']['name']['rule_id']."' and value = '".$U['name']."'";

		$db->query($sql);
		
		if($db->next_record()){
		return (array(false, "Deployment names must be unique. You may leave deployment name empty and a unique name will be generated."));
		}
		
		}
		return (array(true,"Name successfully validated."));
		
	}
	
	return (array(true));
}
?>