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
			if(isset($_REQUEST['collection_id'])) {
				$result = array();
				$s3ql=compact('user_id','db');
				$s3ql['select']='*';
				$s3ql['from']='items';
				$s3ql['where']['collection_id']=$_REQUEST['collection_id'];
				$s3ql['format']=$_REQUEST['format'];
				$res = S3QLaction($s3ql);
				
				$count = count($res);
				if($count > 0) {
					for($i = 0; $i < $count; $i++) {
						$s3ql=compact('user_id','db');
						$s3ql['select']='*';
						$s3ql['from']='statements';
						$s3ql['where']['item_id']=$res[$i]['item_id'];
						$s3ql['format']=$_REQUEST['format'];
						$res2 = S3QLaction($s3ql);
						
						$result = array_merge($result,$res2);
					}
				}
				//add apikey entry
				array_walk($result,create_function('&$item','$item["apikey"] = '.$key.';'));

				if(strtolower($format) == 'json') {
					echo json_remove_callback(formatReturn(0,compact('result'),$format,''));
				} else {
					echo formatReturn(0,compact('result'),$format,'');
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
							//if($_REQUEST['item_id'] && $_REQUEST['rule_id']){
							if($_REQUEST['collection_id'] && $_REQUEST['rule_id']){
								$itemid = 0;
								//create new item for file
								$s3ql=compact('user_id','db');
								$s3ql['insert']='item';
								$s3ql['where']['collection_id']=$_REQUEST['collection_id'];
								$s3ql['where']['notes']=$filename;
								$s3ql['format']='json';
								$res = S3QLaction($s3ql);
								
								//extract item ID
								preg_match( '/(?:\"item_id\"|\'item_id\') *:* *(?:\"|\')* *([a-zA-Z0-9]+) *(?:\"|\')*/i', $res, $matches);
								if(is_array($matches) && count($matches) > 1) {
									$itemid = trim($matches[1]);
									
									$s3ql=compact('user_id','db');
									$s3ql['insert']='file';
									$s3ql['where']['item_id']=$itemid;
									$s3ql['where']['rule_id']=$_REQUEST['rule_id'];
									$s3ql['where']['filekey']=$filekey;
									$s3ql['format']=$_REQUEST['format'];
									$done = S3QLaction($s3ql);

									$msg .=$done;
									if(strtolower($format) == 'json') {
										// file_size file_name download_url statement_id
										preg_match('/(?<=\"file_id\"\:)\d+/',$msg,$statementid);
										$statementid = $statementid[0];
										$tmpMsg = ',"file_name":"'.$filename.'","file_size":"'.$filesize.'","statement_id":"'.$statementid.'","apikey":"'.$key.'","download_url":"'.S3DB_URI_BASE.'/download.php?key='.$key.'&statement_id='.$statementid.'"';
										$tmpPos = strpos($msg, ',');
										if($tmpPos !== false) {
											if($tmpPos > 0) {
												$msg = substr($msg, 0, $tmpPos).$tmpMsg.substr($msg, $tmpPos);
											}
										}
									}
								} else {
									$msg .= formatReturn('3',"File did not save properly, please try again.",$format,'');
								}
							} else {
								$msg .= formatReturn('3',"You did not provide a Collection and Rule ID",$format,'');
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
		} else {		//if key is empty, check for filekey
			$filekey = $xml->filekey;
			if ($filekey == '') $filekey = $_REQUEST['filekey'];
			if ($filekey!='') {
				include_once('core.filekeyheader.php');
	
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
				
				//check if the statement ID is the only one that belongs to its item
				$s3ql = compact('user_id','db');
				$s3ql['select']='*';
				$s3ql['from']='statements';
				$s3ql['where']['statement_id']=$statementid;
				$res = S3QLaction($s3ql);
				
				if(isset($res[0]['item_id']) && intval($res[0]['item_id']) > 0) {
					$itemid = $res[0]['item_id'];
					$s3ql = compact('user_id','db');
					$s3ql['select']='*';
					$s3ql['from']='statements';
					$s3ql['where']['item_id']=$itemid;
					$res = S3QLaction($s3ql);
					
					//delete the statement
					$s3ql=compact('user_id','db');
					$s3ql['delete']='statement';
					$s3ql['where']['statement_id']=$statementid;
					$s3ql['format']=$_REQUEST['format'];
					$done = S3QLaction($s3ql);
					
					//determine whether to delete item
					if(count($res) == 1) {
						$s3ql=compact('user_id','db');
						$s3ql['delete']='item';
						$s3ql['where']['item_id']=$itemid;
						$done2 = S3QLaction($s3ql);
					}
					
					if(strtolower($format) == 'json') {
						echo json_remove_callback(formatReturn(0,compact('done'),$format,''));
					} else {
						echo formatReturn(0,compact('done'),$format,'');
					}
				} else {
					echo formatReturn('3',"Could not find the item the statement belongs to.",$format,''); exit;
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
                         	