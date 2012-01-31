<?php
	/**
        * Has a few functions, but primary role is to load the phpgwapi
        * @author Dan Kuykendall <seek3r@phpgroupware.org>
        * @author Joseph Engo <jengo@phpgroupware.org>
        * @copyright Copyright (C) 2000-2004 Free Software Foundation, Inc. http://www.fsf.org/
        * @license http://www.fsf.org/licenses/lgpl.html GNU Lesser General Public License
        * @package phpgwapi
        * @subpackage utilities
        * @version $Id: common_functions.inc.php,v 1.16.2.10 2004/02/26 21:49:22 skwashd Exp $

	/***************************************************************************\
        * S3DB                                                                     *
        * http://www.s3db.org                                                      *
        * Modified by Helena Deus<helenadeus@gmail.com>                          *
        \**************************************************************************/

	 $cwd = dirname($_SERVER['SCRIPT_FILENAME']);
	 //echo $cwd;
	 $GLOBALS['s3db_info']['server']['template_dir'] = S3DB_SERVER_ROOT . '/s3dbapi/templates/default';
	 //$GLOBALS['s3db_info']['server']['template_dir'] = $cwd;


	function safe_args($expected, $recieved, $line='??', $file='??')
	{
		/* This array will contain all the required fields */
		$required = Array();

		/* This array will contain all types for sanatization checking */
		/* only used when an array is passed as the first arg          */
		$types = Array();
		
		/* start by looping thru the expected list and set params with */
		/* the default values                                          */
		$num = count($expected);
    for ($i = 0; $i < $num; $i++)
		{
			$args[$expected[$i]['name']] = $expected[$i]['default'];
			if ($expected[$i]['default'] === '##REQUIRED##')
			{
				$required[$expected[$i]['name']] = True;
			}
			$types[$expected[$i]['name']] = $expected[$i]['type']; 
 		}
		
		/* Make sure they passed at least one param */
		if(count($recieved) != 0)
		{
			/* if used as standard function we loop thru and set by position */
			if(!is_array($recieved[0]))
			{
		    for ($i = 0; $i < $num; $i++)
				{
					if(isset($recieved[$i]) && $recieved[$i] !== '##DEFAULT##')
					{
						if(sanitize($recieved[$i],$expected[$i]['type']))
						{
							$args[$expected[$i]['name']] = $recieved[$i];
							unset($required[$expected[$i]['name']]);
						}
						else
						{
							echo 'Fatal Error: Invalid paramater type for '.$expected[$i]['name'].' on line '.$line.' of '.$file.'<br>';
							exit;
						}
					}
   			}
			}
			/* if used as standard function we loop thru and set by position */
			else
			{
		    for ($i = 0; $i < $num; $i++)
				{
					$types[$expected[$i]['name']] = $expected[$i]['type']; 
 				}
				while(list($key,$val) = each($recieved[0]))
				{
					if($val !== '##DEFAULT##')
					{
						if(sanitize($val,$types[$key]) == True)
						{
							$args[$key] = $val;
							unset($required[$key]);
						}
						else
						{
							echo 'Fatal Error: Invalid paramater type for '.$key.' on line '.$line.' of '.$file.'<br>';
							exit;
						}
					}
				}
			}
		}
		if(count($required) != 0)
		{
			while (list($key) = each($required))
			{
				echo 'Fatal Error: Missing required paramater '.$key.' on line '.$line.' of '.$file.'<br>';
			}
			exit;
		}
		return $args;
	}

	/*!
	 @function sanitize
	 @abstract Validate data.
	 @author seek3r
	 @discussion This function is used to validate input data. 
	 @syntax sanitize('type', 'match string');
	 @example sanitize('number',$somestring);
	*/

	/*
	$GLOBALS['phpgw_info']['server']['sanitize_types']['number'] = Array('type' => 'preg_match', 'string' => '/^[0-9]+$/i');
	*/

	function sanitize($string,$type)
	{
		switch ($type)
		{
			case 'bool':
				if ($string == 1 || $string == 0)
				{
					return True;
				}
				break;
			case 'isprint':
				$length = strlen($string);
				$position = 0;
				while ($length > $position)
				{
					$char = substr($string, $position, 1);
					if ($char < ' ' || $char > '~')
					{
						return False;
					}
					$position = $position + 1;
				}
				return True;
				break;
			case 'alpha':
				if (preg_match("/^[a-z]+$/i", $string))
				{
					return True;
				}
				break;
			case 'number':
				if (preg_match("/^[0-9]+$/i", $string))
				{
					return True;
				}
				break;
			case 'alphanumeric':
				if (preg_match("/^[a-z0-9 -._]+$/i", $string))
				{
					return True;
				}
				break;
			case 'string':
				if (preg_match("/^[a-z]+$/i", $string))
				{
					return True;
				}
				break;
			case 'ip':
				if (eregi("^[0-9]{1,3}(\.[0-9]{1,3}){3}$",$string))
				{
					$octets = split('\.',$string);
					for ($i=0; $i != count($octets); $i++)
					{
						if ($octets[$i] < 0 || $octets[$i] > 255)
						{
							return False;
						}
					}
					return True;
				}
				return False;
				break;
			case 'file':
				if (preg_match("/^[a-z0-9_]+\.+[a-z]+$/i", $string))
				{
					return True;
				}
				break;
			case 'email':
				if (eregi("^([[:alnum:]_%+=.-]+)@([[:alnum:]_.-]+)\.([a-z]{2,3}|[0-9]{1,3})$",$string))
				{
					return True;
				}
				break;
			case 'password':
				$password_length = strlen($string);
				$password_numbers = Array('0','1','2','3','4','5','6','7','8','9');
				$password_special_chars = Array(' ','~','`','!','@','#','$','%','^','&','*','(',')','_','+','-','=','{','}','|','[',']',"\\",':','"',';',"'",'<','>','?',',','.','/');

				if(@isset($GLOBALS['phpgw_info']['server']['pass_min_length']) && is_int($GLOBALS['phpgw_info']['server']['pass_min_length']) && $GLOBALS['phpgw_info']['server']['pass_min_length'] > 1)
				{
					$min_length = $GLOBALS['phpgw_info']['server']['pass_min_length'];
				}
				else
				{
					$min_length = 1;
				}

				if(@isset($GLOBALS['phpgw_info']['server']['pass_require_non_alpha']) && $GLOBALS['phpgw_info']['server']['pass_require_non_alpha'] == True)
				{
					$pass_verify_non_alpha = False;
				}
				else
				{
					$pass_verify_non_alpha = True;
				}
				
				if(@isset($GLOBALS['phpgw_info']['server']['pass_require_numbers']) && $GLOBALS['phpgw_info']['server']['pass_require_numbers'] == True)
				{
					$pass_verify_num = False;
				}
				else
				{
					$pass_verify_num = True;
				}

				if(@isset($GLOBALS['phpgw_info']['server']['pass_require_special_char']) && $GLOBALS['phpgw_info']['server']['pass_require_special_char'] == True)
				{
					$pass_verify_special_char = False;
				}
				else
				{
					$pass_verify_special_char = True;
				}
				
				if ($password_length >= $min_length)
				{
					for ($i=0; $i != $password_length; $i++)
					{
						$cur_test_string = substr($string, $i, 1);
						if (in_array($cur_test_string, $password_numbers) || in_array($cur_test_string, $password_special_chars))
						{
							$pass_verify_non_alpha = True;
							if (in_array($cur_test_string, $password_numbers))
							{
								$pass_verify_num = True;
							}
							elseif (in_array($cur_test_string, $password_special_chars))
							{
								$pass_verify_special_char = True;
							}
						}
					}

					if ($pass_verify_num == False)
					{
						$GLOBALS['phpgw_info']['flags']['msgbox_data']['Password requires at least one non-alpha character']=False;
					}

					if ($pass_verify_num == False)
					{
						$GLOBALS['phpgw_info']['flags']['msgbox_data']['Password requires at least one numeric character']=False;
					}

					if ($pass_verify_special_char == False)
					{
						$GLOBALS['phpgw_info']['flags']['msgbox_data']['Password requires at least one special character (non-letter and non-number)']=False;
					}
					
					if ($pass_verify_num == True && $pass_verify_special_char == True)
					{
						return True;
					}
					return False;
				}
				$GLOBALS['phpgw_info']['flags']['msgbox_data']['Password must be at least '.$min_length.' characters']=False;
				return False;
				break;
			case 'any':
				return True;
				break;
			default :
				if (isset($GLOBALS['phpgw_info']['server']['sanitize_types'][$type]['type']))
				{
					if ($GLOBALS['phpgw_info']['server']['sanitize_types'][$type]['type']($GLOBALS['phpgw_info']['server']['sanitize_types'][$type]['string'], $string))
					{
						return True;
					}
				}
				return False;
		}
	}

	function reg_var($varname, $method = 'any', $valuetype = 'alphanumeric',$default_value='',$register=True)
	{
		if($method == 'any')
		{
			$method = Array('POST','GET','COOKIE','SERVER','GLOBAL','DEFAULT');
		}
		elseif(!is_array($method))
		{
			$method = Array($method);
		}
		$cnt = count($method);
		for($i=0;$i<$cnt;$i++)
		{
			switch(strtoupper($method[$i]))
			{
				case 'DEFAULT':
					if($default_value)
					{
						$value = $default_value;
						$i = $cnt+1; /* Found what we were looking for, now we end the loop */
					}
					break;
				case 'GLOBAL':
					if(@isset($GLOBALS[$varname]))
					{
						$value = $GLOBALS[$varname];
						$i = $cnt+1;
					}
					break;
				case 'POST':
				case 'GET':
				case 'COOKIE':
				case 'SERVER':
					$meth = '_'.strtoupper($method[$i]);
					if(@isset($GLOBALS[$meth][$varname]))
					{
						$value = $GLOBALS[$meth][$varname];
						$i = $cnt+1;
					}
					break;
				default:
					if(@isset($GLOBALS[strtoupper($method[$i])][$varname]))
					{
						$value = $GLOBALS[strtoupper($method[$i])][$varname];
						$i = $cnt+1;
					}
			}
		}

		if (!@isset($value))
		{
			$value = $default_value;
		}

		if (!@is_array($value))
		{
			if ($value == '')
			{
				$result = $value;
			}
			else
			{
				if (sanitize($value,$valuetype) == 1)
				{
					$result = $value;
				}
				else
				{
					$result = $default_value;
				}
			}
		}
		else
		{
			reset($value);
			while(list($k, $v) = each($value))
			{
				if ($v == '')
				{
					$result[$k] = $v;
				}
				else
				{
					if (is_array($valuetype))
					{
						$vt = $valuetype[$k];
					}
					else
					{
						$vt = $valuetype;
					}

					if (sanitize($v,$vt) == 1)
					{
						$result[$k] = $v;
					}
					else
					{
						if (is_array($default_value))
						{
							$result[$k] = $default_value[$k];
						}
						else
						{
							$result[$k] = $default_value;
						}
					}
				}
			}
		}
		if($register)
		{
			$GLOBALS['phpgw_info'][$GLOBALS['phpgw_info']['flags']['currentapp']][$varname] = $result;
		}
		return $result;
	}

	/*!
	 @function get_var
	 @abstract retrieve a value from either a POST, GET, COOKIE, SERVER or from a class variable.
	 @author skeeter
	 @discussion This function is used to retrieve a value from a user defined order of methods. 
	 @syntax get_var('id',array('POST','GET','COOKIE','GLOBAL','DEFAULT'));
	 @example $this->id = get_var('id',array('POST','GET','COOKIE','GLOBAL','DEFAULT'));
	 @param $variable name
	 @param $method ordered array of methods to search for supplied variable
	 @param $default_value (optional)
	*/
	function get_var($variable,$method='any',$default_value='')
	{
		return reg_var($variable,$method,'any',$default_value,False);
	}

	/*!
	 @function include_class
	 @abstract This will include the class once and guarantee that it is loaded only once.  Similar to CreateObject, but does not instantiate the class.
	 @author skeeter
	 @discussion This will include the API class once and guarantee that it is loaded only once.  Similar to CreateObject, but does not instantiate the class.
	 @syntax include_class('setup');
	 @example include_class('setup');
	 @param $included_class API class to load
	*/
	function include_class($included_class)
	{
		if (!isset($GLOBALS['phpgw_info']['flags']['included_classes'][$included_class]) ||
			!$GLOBALS['phpgw_info']['flags']['included_classes'][$included_class])
		{
			$GLOBALS['phpgw_info']['flags']['included_classes'][$included_class] = True;   
			include_once(PHPGW_SERVER_ROOT.'/phpgwapi/inc/class.'.$included_class.'.inc.php');
		}
	}

	/*!
	 @function CreateObject
	 @abstract Load a class and include the class file if not done so already.
	 @author mdean
	 @author milosch
	 @author (thanks to jengo and ralf)
	 @discussion This function is used to create an instance of a class, and if the class file has not been included it will do so. 
	 @syntax CreateObject('app.class', 'constructor_params');
	 @example $phpgw->acl = CreateObject('phpgwapi.acl');
	 @param $classname name of class
	 @param $p1-$p16 class parameters (all optional)
	*/
	function CreateObject($class,
		$p1='_UNDEF_',$p2='_UNDEF_',$p3='_UNDEF_',$p4='_UNDEF_',
		$p5='_UNDEF_',$p6='_UNDEF_',$p7='_UNDEF_',$p8='_UNDEF_',
		$p9='_UNDEF_',$p10='_UNDEF_',$p11='_UNDEF_',$p12='_UNDEF_',
		$p13='_UNDEF_',$p14='_UNDEF_',$p15='_UNDEF_',$p16='_UNDEF_')
	{
		global $phpgw_info, $phpgw;

		if (is_object(@$GLOBALS['phpgw']->log) && $class != 'phpgwapi.error' && $class != 'phpgwapi.errorlog')
		{
			//$GLOBALS['phpgw']->log->write(array('text'=>'D-Debug, dbg: %1','p1'=>'This class was run: '.$class,'file'=>__FILE__,'line'=>__LINE__));
		}

		/* error_reporting(0); */
		list($appname,$classname) = explode('.', $class);

		//$filename = $GLOBALS['server_root'].'/inc/class.'.$classname.'.inc.php';
		//echo $filename;
		$filename = S3DB_SERVER_ROOT.'/s3dbcore/class.'.$classname.'.inc.php';
		$included_files = get_included_files();

		if (!isset($included_files[$filename]))
		{
			if(@file_exists($filename))
			{
				include_once($filename);
				$is_included = True;
			}
			else
			{
				$is_included = False;
			}
		}
		else
		{
			$is_included = True;
		}
		
		if($is_included)
		{
			if ($p1 == '_UNDEF_' && $p1 != 1)
			{
				$obj = new $classname;
			}
			else
			{
				$input = array($p1,$p2,$p3,$p4,$p5,$p6,$p7,$p8,$p9,$p10,$p11,$p12,$p13,$p14,$p15,$p16);
				$i = 1;
				$code = '$obj = new ' . $classname . '(';
				while (list($x,$test) = each($input))
				{
					if (($test == '_UNDEF_' && $test != 1 ) || $i == 17)
					{
						break;
					}
					else
					{
						$code .= '$p' . $i . ',';
					}
					$i++;
				}
				$code = substr($code,0,-1) . ');';
				eval($code);
			}
			/* error_reporting(E_ERROR | E_WARNING | E_PARSE); */
			return $obj;
		}
	}

	/*!
	 @function ExecMethod
	 @abstract Execute a function, and load a class and include the class file if not done so already.
	 @author seek3r
	 @discussion This function is used to create an instance of a class, and if the class file has not been included it will do so.
	 @syntax ExecObject('app.class', 'constructor_params');
	 @param $method to execute
	 @param $functionparams function param should be an array
	 @param $loglevel developers choice of logging level
	 @param $classparams params to be sent to the contructor
	 @example ExecObject('phpgwapi.acl.read');
	*/
	function ExecMethod($method, $functionparams = '_UNDEF_', $loglevel = 3, $classparams = '_UNDEF_')
	{
		/* Need to make sure this is working against a single dimensional object */
		$partscount = count(explode('.',$method)) - 1;
		if ($partscount == 2)
		{
			list($appname,$classname,$functionname) = explode(".", $method);
			if (!is_object($GLOBALS[$classname]))
			{
				if ($classparams != '_UNDEF_' && ($classparams || $classparams != 'True'))
				{
					$GLOBALS[$classname] = CreateObject($appname.'.'.$classname, $classparams);
				}
				else
				{
					$GLOBALS[$classname] = CreateObject($appname.'.'.$classname);
				}
			}

			if ((is_array($functionparams) || $functionparams != '_UNDEF_') && ($functionparams || $functionparams != 'True'))
			{
				return $GLOBALS[$classname]->$functionname($functionparams);
			}
			else
			{
				return $GLOBALS[$classname]->$functionname();
			}
		}
		/* if the $method includes a parent class (multi-dimensional) then we have to work from it */
		elseif ($partscount >= 3)
		{
			$GLOBALS['methodparts'] = explode(".", $method);
			$classpartnum = $partscount - 1;
			$appname = $GLOBALS['methodparts'][0];
			$classname = $GLOBALS['methodparts'][$classpartnum];
			$functionname = $GLOBALS['methodparts'][$partscount];
			/* Now I clear these out of the array so that I can do a proper */
			/* loop and build the $parentobject */
			unset ($GLOBALS['methodparts'][0]);
			unset ($GLOBALS['methodparts'][$classpartnum]);
			unset ($GLOBALS['methodparts'][$partscount]);
			reset ($GLOBALS['methodparts']);
			$firstparent = 'True';
			while (list ($key, $val) = each ($GLOBALS['methodparts']))
			{
				if ($firstparent == 'True')
				{
					$parentobject = '$GLOBALS["'.$val.'"]';
					$firstparent = False;
				}
				else
				{
					$parentobject .= '->'.$val;
				}
			}
			unset($GLOBALS['methodparts']);
			$code = '$isobject = is_object('.$parentobject.'->'.$classname.');';
			eval ($code);
			if (!$isobject)
			{
				if ($classparams != '_UNDEF_' && ($classparams || $classparams != 'True'))
				{
					if (is_string($classparams))
					{
						eval($parentobject.'->'.$classname.' = CreateObject("'.$appname.'.'.$classname.'", "'.$classparams.'");');
					}
					else
					{
						eval($parentobject.'->'.$classname.' = CreateObject("'.$appname.'.'.$classname.'", '.$classparams.');');
					}
				}
				else
				{
					eval($parentobject.'->'.$classname.' = CreateObject("'.$appname.'.'.$classname.'");');
				}
			}

			if ($functionparams != '_UNDEF_' && ($functionparams || $functionparams != 'True'))
			{
				eval('$returnval = '.$parentobject.'->'.$classname.'->'.$functionname.'('.$functionparams.');');
				return $returnval;
			}
			else
			{
				eval('$returnval = '.$parentobject.'->'.$classname.'->'.$functionname.'();');
				return $returnval;
			}
		}
		else
		{
			return 'error in parts';
		}
	}

function update_url_registry($U)
{
#update an existing url in the mothership
#syntax: update_url_registry(compact('mothership', 'newUrl', 'publicKey', 'Did'));
extract($U);

$mothership = $mothership.'s3rl.php?Did='.$Did.'&newUrl='.$newUrl.'&format=php';

$fid = fopen($mothership,'r');
$resp = stream_get_contents($fid);

if(!$resp){
	return array(False, 'Mothership is not responding');
}

$resp = unserialize($resp);

#if the request was successfull, a message is returned to be decripted
#if(!ereg('<message>(.*)</message>', $resp, $s3qlout))
if($resp[0]['error_code']!='0'){
	return array(False, $resp[0]['message']);
}
else {
	$message=$resp[0]['encripted'];
	#$message = $s3qlout[1];
	$privateKey = $GLOBALS['s3db_info']['deployment']['private_key'];
	$publicKey = $GLOBALS['s3db_info']['deployment']['public_key'];
	
	if(preg_match("/BEGIN PUBLIC KEY/",base64_decode($publicKey))){
			
			set_include_path(get_include_path() . PATH_SEPARATOR . S3DB_SERVER_ROOT.'/pearlib/phpseclib');
			
			if(is_file(S3DB_SERVER_ROOT.'/pearlib/phpseclib/Crypt/RSA.php')){
			include(S3DB_SERVER_ROOT.'/pearlib/phpseclib/Crypt/RSA.php');
			define('CRYPT_RSA_SMALLEST_PRIME', 1000);
			$rsa = new Crypt_RSA();
			$rsa->setEncryptionMode(CRYPT_RSA_ENCRYPTION_OAEP);
			$rsa->loadKey($privateKey);
			$decriptedMessage = $rsa->decrypt($message);
			
			}
	}
	else {
		require_once 'pearlib/RSACrypt/RSA.php';		
		$decriptedMessage = decrypt($message, $privateKey);
		if($decriptedMessage=='') 
			$decriptedMessage = decrypt(rawurldecode($message), $privateKey);
	}
	
	
	if($decriptedMessage=='') 
		{return array(False,'Could not decript Message.');}

	
	$urlToSend = $mothership.'&message='.rawurlencode($decriptedMessage).'&format=php';
	
	#$resp2 = do_post_request($urlToSend, compact('Did', 'newUrl'), $optional_headers = null);
	$resp2 = fread(fopen($urlToSend, 'r'), '1000000');
	$resp2 = unserialize($resp2);
	#ereg('<error>([0-9]+)</error><description>(.*)</description>', $resp2, $s3qlout2);
	$resp2 = $resp2[0];
	
		if($resp2['error_code']=='0')
			return array(true, $resp2['message']);
		else {
			return array(False, $resp2['message']);
		}
		}

}



function do_post_request($url, $data, $optional_headers = null)
{
#send data by cookie
$head .= "Cookie: ";
foreach ($data as $key=>$value) {
	$head .= $key.'='.$value."\r\n ";
}

$params = array('http' => array(
			'method' =>"post",
			#'content'=>"public_key=".$data['publicKey']."",
			'header'=>$head));
				#"Cookie: publicKey=".$data['publicKey']."\r\n ".
					#		"message=".$data['message']."\r\n ",
						#	"POST: public_key=".$data['publicKey']."\r\n ",
			#'public_key' => $data['publicKey']));
					#'public_key' => $data['public_key']
#
#echo '<pre>';print_r($params);exit;
#echo $url;exit;
if ($optional_headers!== null) {
$params['http']['header'] = $optional_headers;
}
$ctx = @stream_context_create($params);
$fp = @fopen($url, 'rb', false, $ctx);
if (!$fp) {
return (false);
#throw new Exception("Problem with $url, $php_errormsg");
}
$response = @stream_get_contents($fp);
if ($response === false) {
return (false);
#throw new Exception("Problem reading data from $url, $php_errormsg");
}
#echo '<pre>';print_r($response);exit;
return $response;
}

function getHostIP() {
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

function http_test_existance($url) {

 return (($fp = @fopen($url, 'r')) === false) ? false : @fclose($fp);
}
	
function captureIp()
{#finds the IP address on the system call ipconfig
	exec('ipconfig', $output);

$ip = trim($ip);

$prot=($_SERVER['HTTPS']!='')?"https://":"http://";
$myLocal = $prot.$myIp."/".strtok($_SERVER['PHP_SELF'], '/');
if(ereg('localhost|127.0.0.1', $_SERVER['SERVER_ADDR']) && $_SERVER['SERVER_ADDR']!=$myIp && $myIp!='')#is ip is valid and we are not there yet and this is a windows server..
{
header('Location: '.$myLocal, 1);exit;
}

	
	if(is_array($output))
	foreach ($output as $value) {
		$value = trim($value);
		
		if(ereg('(IP Address)(.+[0-9])|(IPv[0-9] Address)(.+[0-9])', $value, $ipdata))
		{
		
		$dots = trim(substr($ipdata[0], strpos($ipdata[0], ':')+1, strlen($ipdata[0])-strpos($ipdata[0], ':')));
		$parts = explode('.', $dots);
		
		if(count($parts)==4 && ereg('^([0-9]+).([0-9]+).([0-9]+).([0-9]+)$', $dots))
			{$ip[] = $dots;
			$size[]=strlen($dots);
			}
		}
	}
	#echo '<pre>';print_r($ip);
	#there may be + 1 IP. Return the longest...Lucky guess...
	if(is_array($size))
	$shortIp =  $ip[array_search(max($size), $size)];
	else {
		$shortIp = $ip[0];
	}
	
	return ($shortIp);
}

//function S3QLSyntax($s, $q)
//{
//	
//	if(!is_array($s))
//		return ("input should be an array");
//	
//	if($q=='') 
//	{$startQ = $s['url'].'S3QL.php?key='.$s['key'].'&query=<S3QL>';
//	$s = array_filter(array_diff_key($s, array('url'=>'', 'key'=>'')));
//	$endQ = '</S3QL>';
//	}
//
//	foreach ($s as $key=>$value) {
//		if(!is_array($value))
//			{
//			$Q .='<'.$key.'>'.$value.'</'.$key.'>';
//		
//			}
//		else {
//			$tmpQ = '<'.$key.'>'.S3QLSyntax($value, $Q).'</'.$key.'>';
//			$Q .= $tmpQ;
//			
//		}
//	}
//	$q=$startQ.$Q.$endQ;
//	return ($q);
//}
?>
