<?php
	function put_the_frag_on_frag_file($F) {
		extract($F);
		#Create the next var to compare with the nr of fragments that have been inserted
		$next = find_next_frag($F);
		if($next == $thisfrag) {
			if (fwrite($fid, $fileStr)) {
				file_put_contents($indname, $next+1);
				chmod($indname, 0777);
				$res .= 'Frag '.$thisfrag.' inserted. ';
				#If this was the last fragment, and it was inserted correctly, delete the index and decode the whole file
				if($thisfrag == $totalfrag) {
					unlink($indname);
				}
			} else {
				$res .= '</report>Frag '.$thisfrag.' failed. Error writting to file</report>';
			}
		} else {
			echo "</report>Waiting for frag ".$next."</report>";
		}
		return $res;
	}

	function check_filekey_validity($filekey, $db) {
		#$db = $_SESSION['db'];
		#$sql = "select * from s3db_file_transfer where filekey='".$filekey."' and expires>='".date('Y-m-d G:i:s')."'";
		$sql = "select file_id from s3db_file_transfer where filekey='".$filekey."'";
		$db->query($sql, __LINE__, __FILE__);
		if($db->next_record()) {
			return TRUE;
		} else {
			return FALSE;
		}
	}

	function put_the_frag_on_file($F) {
		extract($F);
		#Create the next var to compare with the nr of fragments that have been inserted
		$next = find_next_frag($F);
		if($next == $thisfrag) {
			if(fwrite($fid, $fileStr)) {
				file_put_contents($indname, $next+1);
				chmod($indname, 0777);
				$msg = 'Frag '.$thisfrag.' inserted. ';	

				#If this was the last fragment, and it was inserted correctly, delete the index and decode the whole file
				if($thisfrag == $totalfrag) {
					unlink($indname);
				}
			} else {
				$msg = 'Frag '.$thisfrag.' failed. Error writting to file';
			}
		} else {
			$msg= "Waiting for frag ".$next."";
		}
		return $msg;
	}

	function find_next_frag($F) {
		extract($F);
		if (!is_file($indname)) {
			$indname = file_put_contents($indname, '0');
			$next = '1';
		} else {
			$next = file_get_contents($indname);
		}
		return $next;
	}

	function acceptFrag($F) {
		extract($F);
		$next = file_get_contents($ind);	
		$fin = fopen($filelocation, 'a+');
		echo base64_decode($fileStr);
		#Decide which fragment will go next
		if($next!='') {
			if($next+1!=$thisfrag) {
				echo "Waiting for fragment ".($next+1)."<BR>";
			} else {		#Means index file corresponds to correct fragment
				if(fwrite($fin, base64_decode($fileStr))) {
					$next=$next + 1; #do the ind file couting independently from the fragNr
					file_put_contents($ind, $next); #increment the fragment on the index file
					$percent = $thisfrag*100/$lastfrag;
					#$progress = '<IMG SRC="progBar.jpg" WIDTH="'.$next*$FragWidth.'0" HEIGHT="20" BORDER="0" ALT="">'.intval($percent).'%';
					echo "Fragment ".$thisfrag." inserted";
					echo intval($percent).'%';
					#fclose($fin);
					return TRUE;
				} else {
					echo "Could not write frament ".$thisfrag." to file.";
				}
			}
		}	
	}

	function receiveFileFragments($F) {
		extract($F);
		$filekey_valid = check_filekey_validity($filekey, $db);
		if ($filekey_valid) {
			#Get the information about the file from the table
			$file_id = get_entry("file_transfer", "file_id", "filekey", $filekey, $db);
			$filesize = get_entry("file_transfer", "filesize", "filekey", $filekey, $db);
			$originalname = get_entry("file_transfer", "filename", "filekey", $filekey, $db);
	
			#list($name, $extension) = explode('.', $originalname);
			ereg('([A-Za-z0-9]+)\.*([A-Za-z0-9]*)$',$originalname, $tokens);
			$name = $tokens[1];
			$extension= $tokens[2];
	
			#$name = ereg_replace('.([A-Za-z0-9]*)$', '', $originalname);
			if($fragNr=='') {
				$fragNr =  $_REQUEST['fragNr'];
			}
			list($thisfrag, $totalfrag) = explode('/', $fragNr);
		
			#Define the folder where these files will be stored
			$folder = $GLOBALS['s3db_info']['server']['db']['uploads_folder'].$GLOBALS['s3db_info']['server']['db']['uploads_file'].'/tmps3db/';
			$filename = $folder.$file_id.'_'.$thisfrag.'.tmp';
			$final = $folder.$file_id.'.'.$extension;
			if($fileStr=='') {
				$fileStr = $_REQUEST['fileStr'];
			}
			#decode the fragment right after receiving them if they were encoded one at a time
			if($_REQUEST['encode']=='2') {
				$fileStr = base64_decode($fileStr);
			}
			$indname = $folder.'ind'.$file_id.'.txt';
			$fid = fopen($filename, 'a+');
			chmod($filename, 0777);
	
			if($fragNr=='' || $fileStr=='') { 
				$msg= "Syntax: uploads.php?filekey=[filekey]&fragNr=x/Total&fileStr=[encoded as base 64 fragment string]&encode=1<BR>";
				#echo "&lt;filekey&gt;...&lt;/filekey&gt;<BR>";
				#echo "&lt;fragNr&gt;[this frag]/[total nr of frags]&lt;/fragNr&gt;<BR>";
				#echo "&lt;fileStr&gt;(hexadecimal encoded fragment string)&lt;/fileStr&gt;<BR>";
				#echo "</report>";
			} else {
				if($filesize!='' && filesize($filename)==$filesize) {
					$msg.="This file was already uploaded";
				} elseif ($thisfrag > $totalfrag) {
					$msg= "Too many fragments";
				} else {
					$F = compact('thisfrag', 'fileStr', 'totalfrag', 'fid', 'filename', 'indname', 'final');
					$msg=put_the_frag_on_file($F);

					#When the last fragment is in, decode the entire file
					if($thisfrag == $totalfrag) {
						#find all the fragment files in the folder,  write them in the final file
						for($i=1;$i<=$totalfrag;$i++) {
							$fragment_file_name = $folder.$file_id.'_'.$i.'.tmp';
							if(is_file($fragment_file_name)) {
								if(file_put_contents($folder.$file_id.'.tmp', file_get_contents($fragment_file_name),FILE_APPEND)) {
									unlink($fragment_file_name);
								}
							} else {
								return $msg= "Fragment ".$i." is missing, please upload it again.";
							}
						}
						#decode the file in the end, if the user requested it
						if($_REQUEST['encode']!='2') {		#if encoding is to be done after getting the file.Empty is default to be backward compatible; other wise, the data on the tmp file is already decoded
							$fullStr =  file_get_contents($folder.$file_id.'.tmp');
							$decodedStr = base64_decode($fullStr);
							if(!file_put_contents($folder.$file_id.'.tmp', $decodedStr)) {
								return $msg = "Failed accepting the file. Please try again or encode file one fragment at a time before sending.";
							}
						}
						copy($folder.$file_id.'.tmp', $final);
						chmod($final, 0777);
						if(is_file($folder.$file_id.'.tmp')) {		#delete the temporary key
							unlink($folder.$file_id.'.tmp');	
						}
						return $msg."Upload Complete";
					} else {
						fclose($fid);
					}
				}
			}
		}
		return ($msg);
	}

	function generateAFilekey($F) {
		#this function accepts user input with key and filename and puts an entry on the file_transfer table 
		extract($F);
		$filekey = random_string("12");
		$file_id = str_replace (array('.', ' '),'', microtime());

		#Insert the entry on file_trasnfer table
		$day = date('d')+1;
		$expires = date('Y-m-d G:i:s', mktime(date('G'),date('i'),date('s'), date('m'), $day, date('Y')));
		$file = array('file_id'=>$file_id, 'filename'=>$filename, 'filesize'=>$filesize, 'expires'=>$expires, 'filekey'=> $filekey, 'status'=>'empty');
		list($name, $extension) = explode('.', $filename);
	
		#Insert the file_id in the file_transfer_table to be associated with the output file
		$transfer = insert_file_for_transfer($file, $user_id, $db);
		if($transfer) {
			return $filekey;
		}
	}
?>