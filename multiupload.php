<?php
/*
 * Uploads accepts file fragments, identified by a file_id and inserts the file in an appropriate location where it can be retrieved by s3db and linked in a statement
 * 
 * Helena F Deus, Dec 1, 2006
 * Bade Iriabho, June 28, 2012
 * 
 */
	if(file_exists('config.inc.php')) {
		include('config.inc.php');
	} else {
		Header('Location: index.php');
		exit;
	}
	header('Pragma: no-cache');
	header('Cache-Control: no-store, no-cache, must-revalidate');
	header('X-Content-Type-Options: nosniff');
	header('Access-Control-Allow-Origin: *');
	header('Access-Control-Allow-Methods: OPTIONS, HEAD, GET, POST, PUT, DELETE');
	header('Access-Control-Allow-Headers: X-File-Name, X-File-Type, X-File-Size');
	
//	===========LOG Stuff===========
/*
	function blog($str='') {
		$myFile = "s3dbuserlog.txt";
		$fh = fopen($myFile, 'a') or die("can't open file");
		fwrite($fh, date('n-j-Y H:i:s :::').$str."\r\n");
		fclose($fh);
	}
	blog(print_r($_REQUEST,true));
	blog(print_r($_FILES,true));
*/
	switch ($_SERVER['REQUEST_METHOD']) {
		case 'OPTIONS':
			break;
		case 'HEAD':
		case 'GET':
			get_action();
			break;
		case 'POST':
			if (isset($_REQUEST['_method']) && $_REQUEST['_method'] === 'DELETE') {
				delete_action();
			} else {
				post_action();
			}
			break;
		case 'DELETE':
			delete_action();
			break;
		default:
			header('HTTP/1.1 405 Method Not Allowed');
	}
	
	function get_action() {
		$xml = $_REQUEST['file'];
		$format=trim($_REQUEST['format']);
		$msg = '';
		
		//Determine if XML is a URL or a string
		if (stripos($xml,'http://') === true || stripos($xml,'https://') === true) {
			$handle = fopen ($xml, 'rb');
			$xml = stream_get_contents($handle);
			fclose($handle);
		}
		$xml = simplexml_load_string($xml);  //this will read the xml and output the result on an array
	
		//Get the key, send it to check validity, if key is missing check for filekey
		$key = $xml->key;
		if (!isset($key) || trim($key) == '') { $key = (isset($_REQUEST['key']))?trim($_REQUEST['key']):''; }
		
		if($key != '') {
			//Check for filename and generate the filekey
			include_once('core.header.php');
			if(isset($_REQUEST['item_id'])) {
				$s3ql=compact('user_id','db');
				$s3ql['select']='*';
				$s3ql['from']='statements';
				$s3ql['where']['item_id']=$_REQUEST['item_id'];
				$s3ql['format']=$_REQUEST['format'];
				
				$done = S3QLaction($s3ql);
				if(strtolower($format) == 'json') {
					echo json_remove_callback(formatReturn(0,compact('done'),$format,''));
				} else {
					echo formatReturn(0,compact('done'),$format,'');
				}
			}
		} else {
			if(strtolower($format) == 'json') {
				echo json_remove_callback(formatReturn('3',"Key is missing.",$format,'')); exit;
			} else {
				echo formatReturn('3',"Key is missing.",$format,''); exit;
			}
		}
	}
	
	function post_action() {
		$xml = $_REQUEST['file'];
		$format=trim($_REQUEST['format']);
		
		//Determine if XML is a URL or a string
		if (stripos($xml,'http://') === true || stripos($xml,'https://') === true) {
			$handle = fopen ($xml, 'rb');
			$xml = stream_get_contents($handle);
			fclose($handle);
		}
		$xml = simplexml_load_string($xml);  //this will read the xml and output the result on an array
	
		//Get the key, send it to check validity, if key is missing check for filekey
		$key = $xml->key;
		if ($key == '') $key = $_REQUEST['key'];
	
		if ($key != '') {
	        //Check for filename and generate the filekey
	        //include_once('core.keyheader.php');
	        include_once('core.header.php');
	        //include_once('s3dbcore/transferFile.php');
	
	        if (!$_FILES) {
	        	echo formatReturn('3','No file to upload.', $_REQUEST['format'],'');
				exit;
	        } elseif($file_id == '') {		//If a file_id is not provided, create one
				if(is_array($_FILES)){
					$folder = $GLOBALS['s3db_info']['server']['db']['uploads_folder'].$GLOBALS['s3db_info']['server']['db']['uploads_file'].'/tmps3db/';
				
					$filekey=''; $filename=''; $msg='';
					foreach ($_FILES as $inputName=>$fileData) {
						$filename = (is_array($_FILES[$inputName]['name']))?$_FILES[$inputName]['name'][0]:$_FILES[$inputName]['name'];
						$filesize = (is_array($_FILES[$inputName]['size']))?$_FILES[$inputName]['size'][0]:$_FILES[$inputName]['size'];
						$filetemp = (is_array($_FILES[$inputName]['tmp_name']))?$_FILES[$inputName]['tmp_name'][0]:$_FILES[$inputName]['tmp_name'];
												
						preg_match('|([^\.]+)$|', $filename, $ext);
						$ext = $ext[1];
						
						$filekey = generateAFilekey(compact('filename', 'filesize', 'db', 'user_id'));
						
						//Retrieve the file_id from the filekey
						$tmp= get_filekey_data($filekey, $db); $file_id = $tmp['file_id'];
						
						//Now upload the file
						if(strlen(trim($ext)) > 0) {
							$final = $folder.$file_id.'.'.$ext;
						} else {
							$final = $folder.$file_id;
						}
						$moved = move_uploaded_file($filetemp, $final);
										                         
						if ($moved) {
							//Was a rule_id and item_id provided? If yes, insert it using s3ql, if not, provide the filekey
							if($_REQUEST['item_id'] && $_REQUEST['rule_id']){
								$s3ql=compact('user_id','db');
								$s3ql['insert']='file';
								$s3ql['where']['item_id']=$_REQUEST['item_id'];
								$s3ql['where']['rule_id']=$_REQUEST['rule_id'];
								$s3ql['where']['filekey']=$filekey;
				
								$s3ql['format']=$_REQUEST['format'];
				
								$done = S3QLaction($s3ql);
								$msg .=$done;
								if(strtolower($format) == 'json') {
									// file_size file_name download_url statement_id
									preg_match('/(?<=\"file_id\"\:)\d+/',$msg,$statementid);
									$statementid = $statementid[0];
									$tmpMsg = ',"file_name":"'.$filename.'","file_size":"'.$filesize.'","statement_id":"'.$statementid.'","download_url":"'.S3DB_URI_BASE.'/download.php?key='.$key.'&statement_id='.$statementid.'"';
									$tmpPos = strpos($msg, ',');
									if($tmpPos !== false) {
										if($tmpPos > 0) {
											$msg = substr($msg, 0, $tmpPos).$tmpMsg.substr($msg, $tmpPos);
										}
									}
								}
							} else {
								$msg .= formatReturn('0',"This filekey is to be used instead of key for file transfer, it will expire in 24h. Break the file in base64 encoded fragments, replacing the character '+' with it's URL equivalent '%2b'",$format,array('filekey'=>$filekey));
							}
						} else {
							$msg .= formatReturn('2',"Failed to import file ".$filename,$format,'');
						}
					}
					if(strtolower($format) == 'json') {
						echo json_remove_callback($msg);
					} else {
						echo $msg;
					}
				}
			}
			//if there is no data besides the key, ask for a filename and filesize
	
			//if($filename == '') $filename = $xml -> filename;
			//$filesize = $_REQUEST['filesize'];
			//if($filesize == '') $filesize = $xml -> filesize;
					
			//Copy file from Php tmp directory
		} else {		//if key is empty, check for filekey
			$filekey = $xml->filekey;
			if ($filekey == '') $filekey = $_REQUEST['filekey'];
			if ($filekey!='') {
				include_once('core.filekeyheader.php');
	
				//add a form to the page such that it accepts both POST and GET
				//echo '<form name="file" method="POST">';
				//echo '<input type="hidden" name="query"> <!-- this form is for programming environments that support sending POST -->';
				//echo '</form>';
	
				//echo receiveFileFragments(compact('filekey', 'db'));
				if(strtolower($format) == 'json') {
					echo json_remove_callback(formatReturn('0',receiveFileFragments(compact('filekey', 'db')), $format,''));
				} else {
					echo formatReturn('0',receiveFileFragments(compact('filekey', 'db')), $format,'');
				}
				exit;
			} else {
				include_once (S3DB_SERVER_ROOT.'/s3dbcore/callback.php');
				include_once (S3DB_SERVER_ROOT.'/s3dbcore/display.php');
				if(strtolower($format) == 'json') {
					echo json_remove_callback(formatReturn('3',"Filekey is missing.",$format,'')); exit;
				} else {
					echo formatReturn('3',"Filekey is missing.",$format,''); exit;
				}
			}
		}
	}
	
	function delete_action(){
		$xml = $_REQUEST['file'];
		$format=trim($_REQUEST['format']);
		$msg = '';
		
		//Determine if XML is a URL or a string
		if (stripos($xml,'http://') === true || stripos($xml,'https://') === true) {
			$handle = fopen ($xml, 'rb');
			$xml = stream_get_contents($handle);
			fclose($handle);
		}
		$xml = simplexml_load_string($xml);  //this will read the xml and output the result on an array
		
		//Get the key, send it to check validity, if key is missing check for filekey
		$key = $xml->key;
		if (!isset($key) || trim($key) == '') {
			$key = (isset($_REQUEST['key']))?trim($_REQUEST['key']):'';
		}
		
		if($key != '') {
			//Check for filename and generate the filekey
			include_once('core.header.php');
			if(isset($_REQUEST['statement_id'])) {
				preg_match('/\d+/', $_REQUEST['statement_id'], $statementid);
				$statementid = $statementid[0];
				$s3ql=compact('user_id','db');
				$s3ql['delete']='statement';
				$s3ql['where']['statement_id']=$_REQUEST['statement_id'];
				$s3ql['format']=$_REQUEST['format'];
		
				$done = S3QLaction($s3ql);
				if(strtolower($format) == 'json') {
					echo json_remove_callback(formatReturn(0,compact('done'),$format,''));
				} else {
					echo formatReturn(0,compact('done'),$format,'');
				}
			}
		} else {
			if(strtolower($format) == 'json') {
				echo json_remove_callback(formatReturn('3',"Key is missing.",$format,'')); exit;
			} else {
				echo formatReturn('3',"Key is missing.",$format,''); exit;
			}
		}
		
	}
	
	function json_remove_callback($str) {
		$val = stripos($str, '(');
		if($val !== false) {
			if($val >= 0) {
				$str = substr($str,$val+1);
				if(substr($str,-1)==';') {
					$str = substr($str, 0, (strlen($str) - 2));
				} else {
					$str = substr($str, 0, (strlen($str) - 1));
				}
			}
		}
		return $str;
	}
?>
                         	