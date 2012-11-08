<?php
	#project_page displays general information on the project;
	#Includes links to resource pages, xml and rdf export 
	#Helena F Deus (helenadeus@gmail.com)
	ini_set('display_errors',0);
	if($_REQUEST['su3d']) {
		ini_set('display_errors',1);
	}
	if($_SERVER['HTTP_X_FORWARDED_HOST']!='') {
		$def = $_SERVER['HTTP_X_FORWARDED_HOST'];
	} else {
		$def = $_SERVER['HTTP_HOST'];
	}
	if(file_exists('../config.inc.php')) {
		include('../config.inc.php');
	} else {
		Header('Location: http://'.$def.'/s3db/');
		exit;
	}
	
	#just to know where we are...
	$thisScript = end(explode('/', $_SERVER['SCRIPT_FILENAME'])).'?'.$_SERVER['argv'][0];

	$key = $_GET['key'];
	#Get the key, send it to check validity
	include_once('../core.header.php');

	if($key_valid!='0' && $_SESSION['db']=='') {
		exit;
	}

	if($key) {
		$user_id = get_entry('access_keys', 'account_id', 'key_id', $key, $db);
	} else {
		$user_id = $_SESSION['user']['account_id'];
	}
	$deployment_info = URI('D'.$GLOBALS['Did'], $user_id,  $db);

	#Universal variables
	$sortorder = $_REQUEST['orderBy'];
	$direction = $_REQUEST['direction'];
	$project_id = $_REQUEST['project_id'];
	#$acl = find_final_acl($user_id, $project_id, $db);
	$uni = compact('db', 'acl','user_id','key', 'project_id', 'dbstruct');
	
	#relevant extra arguments
	$args ='?key='.$_REQUEST['key'];

	include('../webActions.php'); #include the specification of the link map. Must be put in here becuase arguments vary.
	
	#Find all the projects for this user
	$s3ql=compact('user_id','db');
	$s3ql['from'] = 'projects';
	if($sortorder!='')
	$s3ql['order_by'] = $sortorder.' '.$direction;
	$projects = S3QLaction($s3ql);
	
	$_SESSION[$user_id]['projects'] = $projects;
	
	#this is the directory where upload of xml or n3 will go before i start using it for building projects
	#$totaldirname = S3DB_SERVER_ROOT.$GLOBALS['s3db_info']['server']['db']['uploads_file'].'/schemas';	
	$totaldirname = $GLOBALS['s3db_info']['server']['db']['uploads_folder'].$GLOBALS['s3db_info']['server']['db']['uploads_file'].'/tmps3db';	
	if($_POST['submitschema']) {
		if(!file_exists($totaldirname)) {
			mkdir($totaldirname, 0777);
		}
		$indexfile = $totaldirname.'/index.php';
		if(file_exists($totaldirname)) {
			file_put_contents ($indexfile , 'This folder cannot be accessed');
		}
		$uploadfile = $totaldirname.'/xmlschema.s3db';
		list ($filename, $extension) = explode (".", $_FILES['schema']['name']);
			
		#read the first 100 lines or so and chech if it is XML
		#prevent the user from uploading malicious code in php; this may not solve  the problem, butfor now it will have to do
		if (move_uploaded_file($_FILES['schema']['tmp_name'], $uploadfile)) {
			header ('Location: '.$action['xmlimport']);
			#create the values that will go into the statements table
		} else {
			echo 'Upload unsuccessfull';
		}
	} elseif($_POST['submitN3']) {		#Action to be taken when an N3 file is submitted
		if(!file_exists($totaldirname)) {
			mkdir($totaldirname, 0777);
		}
		$indexfile = $totaldirname.'/index.php';
		if (file_exists($totaldirname)) {
			file_put_contents ($indexfile , 'This folder cannot be accessed');
		}
		
		$uploadfile = $GLOBALS['uploads'].'tmps3db/'.random_string(3).date('Ymd').'.n3';

		#list ($filename, $extension) = explode (".", $_FILES['schema']['name']);
		#prevent the user from uploading malicious code in php; this may not solve  the problem, butfor now it will have to do
		#if ($extension=='n3' || $extension=='txt') {
		#	echo '<pre>';print_r($_FILES);exit;
				
		if (move_uploaded_file($_FILES['schema']['tmp_name'], $uploadfile)) {
			header ('Location: '.$action['rdfimport'].'&file='.$uploadfile.'&load');
			exit;
		} elseif(copy($_FILES['schema']['tmp_name'], $uploadfile)) {
			header ('Location: '.$action['rdfimport'].'&file='.$uploadfile.'&load');
			exit;
		} else {
			##Does apache have write permissions?
			if(!is_writable($GLOBALS['uploads'].'tmps3db/')) {
				$message = "Upload unsuccessfull, please contact your systems administrator. User apache should have write permission in folder 'tmps3db' within the uploads directory.";
			} else {
			$message = "Upload unsuccessfull, please try again.";	
			}
		}
		#create the values that will go into the statements table
		#}
		#else 
	} elseif($_REQUEST['remoteproject_id']!='') {		 #this is for retrieving information on a project outside this s3db
		$key = get_entry('access_keys', 'key_id', 'account_id', $user_id, $db);
		$s3ql['key'] = $key;
		$s3ql['select'] = '*';
		$s3ql['from'] = 'projects';
		$s3ql['where']['project_id'] = $_REQUEST['remoteproject_id'];
		$s3ql['url'] = 'http://s3db.virtual.vps-host.net/central/';
		$s3ql['format'] ='php';
		$S3QL_string = S3QLquery($s3ql);

		$handle = fopen ($S3QL_string, 'r');
		$contents = stream_get_contents($handle);
		parse_str($contents, $projects);
		
		#Check if project_id is repeated
		for($i=0;$i<count($projects);$i++) 
		if ($projects[$i]['project_id'] == $_POST['remote_project_id'] && $projects[$i]['URI'] == $_POST['URI'])
		$found_project .= True;
		
		if($found_project) {
			echo 'This project is being accessed.';
		}
		#else {
		#	#When the creator is not revealed, use the local user as the owner of the project
		#	if ($creator=='' || $creator = 'anonymous') $creator = $_SESSION['user']['account_id'];
		#	$newproject = array ('project_id'=>$_POST['remote_project_id'], 'project_name'=>$title, 'project_description'=>$description,'created_on'=>$date, 'created_by'=>$date, 'key'=>$_POST['key'], 'URI'=>$_POST['URI'], 'project_status' =>$status);
		#	$inserted_project = insert_project($newproject);
		#}
	}
	
	if(is_array($projects) && !empty($projects)) {
		$projects = replace_created_by($projects, $db);
		#$projects = include_acl($projects, $user_id, $db);
		
		echo '<table class="insidecontents" width="80%" align="center">
		<tr><td class="message">'.$message.'</td></tr>
		<tr bgcolor="#99CCFF"><td align="center">Project Manager</td></tr><tr><td>';
		echo render_elements($projects, $acl, array('Project ID', 'Project Name', 'Project Description', 'Owner', 'Actions'), 'project');	
		echo '</table>';
	}
	include('../S3DBjavascript.php');
?>
<table width="80%" align="center">
	<tr>
		<td>
			<table class="insidecontents" width="80%" align="center">
		</td>
	</tr>
	<tr>
		<td align="left">
<?php
	if($user_info['account_type']=='p') {
		$disabled=" disabled";
	}
	echo '<input type="button" name="newproject" value="New Project" onclick="window.location=\''.$action['createproject'].'\'"'.$disabled.'>';
	echo '&nbsp;&nbsp;&nbsp;<input type="button" name="newproject" value="Remote Project" onclick="window.location=\''.$action['remoteproject'].'\'"'.$disabled.'><br><br>';
?>   
		</td>
	</tr>
</table>
<!-- Other options -->
<table class="insidecontents" width="80%" align="center">
	<tr bgcolor="#99CCFF">
		<td align="center">Import Project</td>
	</tr>
	<tr>
		<td align="left"></td>
	</tr>
	<tr>
		<td>
			<form enctype="multipart/form-data" name="importschema" action="<?php echo $action['listprojects']; ?>" method="POST">
				<input type="hidden" name="MAX_FILE_SIZE" value="20000000">
				<input type="file" name="schema">
				<input type="submit" name="submitschema" value="Import from XML" <?php echo $disabled; ?>>
				<font size="2">(valid for XML files created by S3DB or complying with the <a href="../docs/s3db.xsd" target='_blank'> S3 schema </a>)</font>
			</form>
			<br><br>
		</td>
	</tr>
	<tr>
		<td align="left"></td>
	</tr>
	<tr>
		<td>
			<form enctype="multipart/form-data" name="importN3" action="<?php echo $action['listprojects']; ?>" method="POST">
				<input type="hidden" name="MAX_FILE_SIZE" value="20000000">
				<input type="file" name="schema">
				<input type="submit" name="submitN3" value="Import/Update from N3/RDF"  <?php echo $disabled ?>>
				<font size="2">(valid only for N3/RDF files annotated to <a href="../docs/s3dbCore.n3" target="_blank">S3DB core</a>). <br />Please note that RDF uploads through the interface only accepts small files (<10000 lines). For larger files, please use API function: rdfRestore.php with argument "file". For Documentation see http://s3db.org/documentation/api-functions</font>
			</form>
			<br><br>
		</td>
	</tr>
	<tr>
		<td align="left"><br><br></td>
	</tr>
</table>
<?php
	#include('../footer.php');
?>