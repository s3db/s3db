<?php
#File2folder will accept the file and transfer it to its appropriate location in the  
function tmpfile2folder($F)
{
	extract($F);
		
		#in order to chose a folder, will need to verify s3db deployment of the project. If the project is not on this deployment, most likely will need to create a new one. 
		#tmpfile2folder takes inputs in and returns built inputs
			$maindir = $GLOBALS['s3db_info']['server']['db']['uploads_folder'].$GLOBALS['s3db_info']['server']['db']['uploads_file'];
			
			if($inputs['project_id'] =='')
				return array(False, 'error_code'=>$GLOBALS['error_codes']['something_went_wrong'], 'message'=>'Need a valid project_id to insert file');
				#find which of the user projects can insert instances in this class.
				
				$folder = project_folder_name($inputs['project_id'], $db);
				
				if($folder=='' || !is_dir($maindir.'/'.$folder)) {
				
					$newProj=random_string(15).'.project'.urlencode($inputs['project_id']);
					$project_folder = $maindir.'/'.$newProj;
					if(mkdir($project_folder))
						$folder=$newProj;
						chmod($project_folder, 0775);
					}
				
			
			if($folder=='') 
				return array(False, 'error_code'=>$GLOBALS['error_codes']['something_went_wrong'], 'message'=>'Failed to find a project_in for this item');
			else{
					$file_found = fileFoundInfo(array('filekey'=>$inputs['filekey'], 'db'=>$db, 'user_id'=>$user_id, 'rule_id'=>$inputs['rule_id']));
					
					#echo '<pre>';print_r($file_found);
					
					#if(!$file_found[0])
					#	return array(False, $file_found[1]);
					#else {
					#	$old_file = $file_found['old_file'];
					#}
					#echo '<pre>';print_r($file_found);
					$old_file = $file_found['old_file'];
					$inputs['value'] = $folder;
					$inputs['file_name'] = $file_found['file_name'];
					$inputs['file_size'] = $file_found['file_size'];
					
					
					
					#find the extentsion and the filename.
					$tmp=fileNameAndExtension($file_found['file_name']);
					extract($tmp);
					#$tmp=explode(".", $inputs['file_name']);	
					
					//echo '<pre>';print_r($tmp);exit;
					

					#$extension= end($tmp);
					#$tmp = array_diff($tmp, array($extension));
					#$name = implode('.', $tmp);

					$inputs['mime_type'] = $extension;
					
					$fileFinName =$maindir.'/'.$folder.'/'.urlencode($name.'_'.$inputs['project_id'].'_'.$inputs['resource_id'].'_'.$inputs['rule_id'].'_'.$inputs['statement_id'].'.'.$extension);

					$fileFinName = ereg_replace('/.$','',$fileFinName);
					
					#echo $old_file.'<br />';
					#echo $fileFinName;exit;

					if(!copy($old_file, $fileFinName))
						return array(False,'error_code'=>$GLOBALS['error_codes']['no_permission_message'], 'message'=>'Could not move file');
					else {
						return array(True,$inputs);
					}
					
			}
}

function movefile2folder($F)

{

extract($F);

#Maindir might vary, it will depend on where the users will want their files
#$maindir = S3DB_SERVER_ROOT.'/extras/'.$GLOBALS['s3db_info']['server']['db']['uploads_file'];
#if(is_dir($GLOBALS['s3db_info']['server']['db']['uploads_folder']))
$maindir = $GLOBALS['s3db_info']['server']['db']['uploads_folder'].$GLOBALS['s3db_info']['server']['db']['uploads_file'];

$folder_code_name = $F['value'];
list($name, $extension) = explode(".", $filename);
#here end the decision as to where the foldr will be and starts the process of renaming and moving the file
		
$file_in_folder =$folder_code_name.'/'.$name.'_'.$project_id.'_'.$resource_id.'_'.$rule_id.'_'.strval($statement_id).'.'.$extension;
		
		
		$file_destination = $maindir."/".$file_in_folder;
		#echo $file_destination;exit;
		#echo '<BR>2'.$uploadedfile;
		
		if(move_uploaded_file($uploadedfile, $file_destination)) ##if not uploaded, try copy
			{return TRUE;
			exit;
			}
		elseif(copy($uploadedfile, $file_destination))
			{
			chmod($file_destination, 0700);
			#rename($uploadedfile, $uploadedfile.'_moved');
			return TRUE;
			exit;
			}
		else{
			return FALSE;
			}
			
		
		
}

function move_uploadedfile($resource_info, $project_info)
	{


		$destDir = $GLOBALS['s3db_info']['server']['db']['uploads_folder'].$GLOBALS['s3db_info']['server']['db']['uploads_file'].'/tmps3db/';
		
			
			$file_to_read = $destDir.'xlsimport_P'.$project_info['project_id'].'_C'.$resource_info['resource_id'].'.txt';
			
			if($_FILES['import']['tmp_name']!='')
			$uploaded_file = $_FILES['import']['tmp_name'];
			#is there a tab in the first 100 chars? If not, it probably is not a text file
			$h=@fread(@fopen($uploaded_file, 'r'), '1000');
			
			if(strpos($h, '	')=='')
			{
			echo '<font color="red"><B>Please make sure the file is tab delimited.</B></font>';
			}
			#list ($filename, $extension) = explode (".", $_FILES['import']['name']);
			if(!is_file($uploaded_file))
			{echo '<font color="red"><B>Please make sure the file was uploaded.</B></font>';
			}

			//return file error when move file doesn't work, but stop showing after other forms are submitted
			
			if (!move_uploaded_file($uploaded_file, $file_to_read))
			{
			return (False);
			}
			else {
			return $file_to_read;
			}
	} //this ends function movefile 


function project_folder_name ($project_id, $db)

	{

		$maindir = $GLOBALS['s3db_info']['server']['db']['uploads_folder'].$GLOBALS['s3db_info']['server']['db']['uploads_file'];
		
		$sql = "select project_folder from s3db_project where project_id = '".$project_id."'";
		#echo $sql;
		$db->query($sql, __LINE__, __FILE__);

			if($db->next_record())
			{
				 return $project_folder = $db->f('project_folder');
			}
			else {
				
				$folders = scandir($maindir);
				if(!is_array($folders))
					return (False);
				else {
					
					foreach ($folders as $key=>$foldername) {
						if(ereg('(.*).project'.urlencode($project_id), $foldername))
							$matches[]=$foldername;

				}
				
				if(count($matches)==1)
					return ($matches[0]);
				else {
					return (False);
				}
				}
				
			}
	}


function MoveFile($F)
{#function MoveFile(compact('filekey','db', 'file'))
#takes any file and moves it to s3db temporeary file directory with a file_id as name.
extract($F);
	#if(is_dir($GLOBALS['s3db_info']['server']['db']['uploads_folder']))
		$maindir = $GLOBALS['s3db_info']['server']['db']['uploads_folder'].$GLOBALS['s3db_info']['server']['db']['uploads_file'];
		
		$tmpdir = $maindir.'/tmps3db';

	#now find the name that the file will need to have in order to be found by the API
	 $sql = "select * from s3db_file_transfer where filekey = '".$filekey."'";

	 $db->query($sql, __LINE__, __FILE__);

	  if($db->next_record())
	{    
	    $file_id = $db->f('file_id');
		$filename = $db->f('filename');
	}
	else {
		return (False);
	}
	
	list($name, $ext) = explode('.', $filename);
	$destination = $tmpdir.'/'.$file_id.'.'.$ext;
	
	 if(!copy($file, $destination))
	{	
	 if(!move_uploaded_file($file,  $destination))
		 return (False);
		 else 
			return (True);
	}
	else {
		return (True);
	}
		 
	 

}
function findDestFile($F)
{#function MoveFile(compact('filekey','db', 'file'))
#takes any file and moves it to s3db temporeary file directory with a file_id as name.
extract($F);
	#if(is_dir($GLOBALS['s3db_info']['server']['db']['uploads_folder']))
		$maindir = $GLOBALS['s3db_info']['server']['db']['uploads_folder'].$GLOBALS['s3db_info']['server']['db']['uploads_file'];
		
		$tmpdir = $maindir.'/tmps3db';

	#now find the name that the file will need to have in order to be found by the API
	 $sql = "select * from s3db_file_transfer where filekey = '".$filekey."'";

	 $db->query($sql, __LINE__, __FILE__);

	  if($db->next_record())
	{    
	    $file_id = $db->f('file_id');
		$filename = $db->f('filename');
	}
	else {
		return (False);
	}
	
	#list($name, $ext) = explode('.', $filename);
	$tmp = fileNameAndExtension($filename);
	$name = $tmp['name'];
	$ext= $tmp['extension'];
	$destination = $tmpdir.'/'.$file_id.'.'.$ext;
	
	
	 return ($destination);
	

}
function fileLocation($statement_info, $db)
	{
		$maindir = $GLOBALS['s3db_info']['server']['db']['uploads_folder'].$GLOBALS['s3db_info']['server']['db']['uploads_file'];
		
		#retrieve the project information
		
		$folder_code_name = project_folder_name($statement_info['project_id'], $db);
		$file_name = $statement_info['file_name'];
		#echo '<pre>';print_r($statement_info);
		#list ($realname, $extension) = explode('.', $file_name);
		$tmp = fileNameAndExtension($file_name);
		$realname = $tmp['name'];
		$extension = $tmp['extension'];


		$file_new_name =  $realname.'_'.urlencode($statement_info['project_id'].'_'.$statement_info['resource_id'].'_'.$statement_info['rule_id'].'_'.$statement_info['statement_id']).'.'.$extension;
		

		
		$file_location = $maindir."/".$folder_code_name."/".$file_new_name;
		return ($file_location);
	}
?>