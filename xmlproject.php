<?php
	#xmlimport recreates a project form the XML
	#Includes links to project page
		#Helena F Deus (helenadeus@gmail.com)

	ini_set('display_errors',0);
	if($_REQUEST['su3d'])
	ini_set('display_errors',1);

if($_SERVER['HTTP_X_FORWARDED_HOST']!='')
			$def = $_SERVER['HTTP_X_FORWARDED_HOST'];
	else 
			$def = $_SERVER['HTTP_HOST'];
	
	if(file_exists('config.inc.php'))
	{
		include('config.inc.php');
	}
	else
	{
	
		Header('Location: http://'.$def.'/s3db/');
		exit;
	}
	


#just to know where we are...
$thisScript = end(explode('/', $_SERVER['SCRIPT_FILENAME'])).'?'.$_SERVER['argv'][0];

$key = $_GET['key'];


#Get the key, send it to check validity

include_once('core.header.php');

if($key)
	$user_id = get_entry('access_keys', 'account_id', 'key_id', $key, $db);
	else
	$user_id = $_SESSION['user']['account_id'];

#Universal variables
$project_id = $_REQUEST['project_id'];
$project_info = URIinfo('P'.$project_id, $user_id, $key,$db);
#$acl = find_final_acl($user_id, $project_id, $db);
$uni = compact('db', 'acl','user_id','key', 'project_id', 'dbstruct');

if ($project_id=='')
	{
		echo "Please specify a project_id";
		exit;
	}
elseif(!$project_info['view'])
{		echo "User cannot access this project.";
		exit;
}
else
{
	

		$P = compact('db', 'user_id', 'project_id');
		
		
		#if the user request for download, send the file
		
		$file = $project_info['project_name'].'.s3db.xml';
		$filename=$GLOBALS['s3db_info']['server']['db']['uploads_folder'].$GLOBALS['s3db_info']['server']['db']['uploads_file'].'/'.$file;
		
   		if (!$handle = fopen($filename, 'w')) {
        	 	echo "Cannot open file ($filename)";
        		 exit;
   		}
		

		$xmlfileStr .= sprintf("%s\n", '<?xml version="1.0" encoding="UTF-8"?>');
		$xmlfileStr .= create_project_set($P);

   		if (fwrite($handle, $xmlfileStr) === FALSE) {
       			echo "Cannot write to file ($filename)";
       			exit;
   		}
   
   		fclose($handle);

		##download the file
		
		if($_REQUEST['link']=='0' || $_REQUEST['link']=='')
		{
		$file_handle = fopen($filename, "r");
		
		header("Pragma: public");
        header("Expires: 0"); // set expiration time
        header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
        // browser must download file from server instead of cache

        // force download dialog
		 header("Content-Type: application/force-download");
		 header("Content-Type: application/octet-stream");
		 header("Content-Type: application/download");

        // use the Content-Disposition header to supply a recommended filename and
        // force the browser to display the save dialog.
        header("Content-Disposition: attachment; filename=".urlencode($file)."");
        header("Content-Transfer-Encoding: binary");
	
		echo $file_contents = fread($file_handle, filesize($filename));
		fclose($file_handle);
		}
		else
		{
		$linkname =random_string(10).'.s3db.xml';
		copy($filename,  S3DB_SERVER_ROOT.'/'.$linkname);
		Header('Location: '.S3DB_URI_BASE.'/'.$linkname);
		exit;
		}
		#echo $filename;exit;
		#echo $xmlfileStr;	
		#Header('Location: '.$filename);
		#exit;
		
		#echo urldecode(create_xml_string($P));
	
}

	function create_project_set($P)
	{
		extract($P);
		
		$Puid = 'P'.$project_id;
		$project_data = URIinfo($Puid, $user_id, '', $db);

		
		$node_set_str .= sprintf("\t%s\n", '<PROJECT>');
		$node_set_str .= sprintf("\t%s\n", '<ID>'.urlencode($project_data['project_id']).'</ID>');
		$node_set_str .= sprintf("\t%s\n", '<NAME>'.urlencode($project_data['project_name']).'</NAME>');
		$node_set_str .= sprintf("\t%s\n", '<DESCRIPTION>'.urlencode($project_data['project_description']).'</DESCRIPTION>');
		#$node_set_str .= sprintf("\t%s\n", '<TOTAL_RESOURCES>'.$nr_of_resources.'</TOTAL_RESOURCES>');
		$node_set_str .= create_resource_nodes($P);

		$node_set_str .= sprintf("\t%s\n", '</PROJECT>');
		
		
		return $node_set_str;	
	}

	function create_resource_nodes($R)
	{
		extract($R);
		
		#get classes
		$s3ql['db'] = $db;
		$s3ql['user_id'] = $user_id;
		$s3ql['select'] = '*';
		$s3ql['from'] = 'collections';
		$s3ql['where']['project_id'] = $project_id;

		$resources = S3QLaction($s3ql);
	
		if (is_array($resources))
		{foreach($resources as $node)
		{
			
			#$x = array('subject'=>$node['entity'], 'project_id'=>$project_id, 'db'=>$db);
			#find all the rules
			#get classes
			$s3ql['from'] = 'rules';
			$s3ql['where']['subject_id'] = $node['collection_id'];
			$s3ql['where']['project_id'] = $project_id;
			
			
			$rules = S3QLaction($s3ql);
			
			
			#$rule_triplets = list_shared_rules($x);
			$nr_of_rules = count($rules);
			#echo '<pre>';print_r($rules);
			$resource_node .= sprintf("\t\t%s\n", '<RESOURCE>');
			$resource_node .= sprintf("\t\t%s\n", '<ID>'.urlencode($node['resource_id']).'</ID>');
			$resource_node .= sprintf("\t\t%s\n", '<ENTITY>'.urlencode($node['entity']).'</ENTITY>');
			$resource_node .= sprintf("\t\t%s\n", '<NOTES>'.urlencode($node['notes']).'</NOTES>');
			#$resource_node .= sprintf("\t\t%s\n", '<TOTAL_RULES>'.$nr_of_rules.'</TOTAL_RULES>');
			


		#create the verbs and objects
			
			
			if (is_array($rules))
			{
			foreach($rules as $node)
			{$node['created_by'] = get_info('account', $node['created_by'], $db);
			$node['modified_by'] = get_info('account', $node['modified_by'], $db);
	
			$resource_node .= sprintf("\t\t\t%s\n", '<RULE>');
			$resource_node .= sprintf("\t\t\t%s\n", '<ID>'.urlencode($node['rule_id']).'</ID>');
			$resource_node .= sprintf("\t\t\t%s\n", '<SUBJECT>'.urlencode($node['subject']).'</SUBJECT>');
			$resource_node .= sprintf("\t\t\t%s\n", '<VERB>'.urlencode($node['verb']).'</VERB>');
			$resource_node .= sprintf("\t\t\t%s\n", '<OBJECT>'.urlencode($node['object']).'</OBJECT>');
			$resource_node .= sprintf("\t\t\t%s\n", '<NOTES>'.urlencode($node['notes']).'</NOTES>');
			$resource_node .= sprintf("\t\t\t%s\n\n", '</RULE>');
			}
			}
			$resource_node .= sprintf("\t\t%s\n", '</RESOURCE>');
		
		}
		return $resource_node;		
		}
	}
	
	

	
	 

?>
