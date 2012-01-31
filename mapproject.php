<?php
	#map/index displays the map with input from the core
	#Includes links to resource pages, xml and rdf export 
	#Helena F Deus (helenadeus@gmailo.com)
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

		#echo '<pre>';print_r($_GET);
		#Get the key, send it to check validity

		include_once('core.header.php');
		
		#if($key)
		#	$user_id = get_entry('access_keys', 'account_id', 'key_id', $key, $db);
		#	else
		#	$user_id = $_SESSION['user']['account_id'];

		#Universal variables
		$project_id = $_REQUEST['project_id'];
		$project_info = URIinfo('P'.$project_id, $user_id, $key, $db);
		#$acl = find_final_acl($user_id, $project_id, $db);
		
		
		##Solving Encoding probles
		$toReplace = array_values(get_html_translation_table(HTML_ENTITIES));

		foreach ($toReplace as $a=>$b) {#The advantage of being portuguese... 
			if(eregi('&([a-z]{1})(tilde|acute|grave|circ|uml|uml|elig|cedil|cedil|slash);', $b,$c)){
				$toReplace[$a] = str_replace('&','&amp;',$toReplace[$a]);
				$replace[] = $c[1];
			}
			else {
				$replace[] = $b;
			}
		}
		
		array_push($toReplace, chr(10), '<br/>');
		array_push($replace, '', '');
		
		
		#$toReplace = array('&amp;ecirc;','&amp;acirc;',chr(10),'<br/>');
		#$replace = array('e','a','','');
		
		$uni = compact('db', 'user_id','key', 'project_id', 'dbstruct', 'regexp','toReplace','replace');
			

		#$args = '?key='.$_REQUEST['key'].'&amp;project_id='.$_REQUEST['project_id'];
		#include('webActions.php'); #include the specification of the link map. Must be put in here becuase arguments vary.


		if ($project_id=='')
			{
				echo "Please specify a project_id";
				exit;
			}
		elseif(!$project_info['view'])
		{		echo "User does not have access in this project.";
				exit;
		}
		else
		{
	
		#xml file MUST be accessible on the web for java to get to it. So the way to hide it is giving it a random name
		
		
		$filename = 'map/zzzproject'.$_REQUEST['project_id'].random_string(10).'.xml';
		#$filename = 'map/project'.$_REQUEST['project_id'].'.xml';
	
		
		#delete the old file, create a new one;
		#if(is_file($filename))
		#unlink($filename);

		#create the xml file for display
		#open the file and print the data in it
		if (!$handle = fopen($filename, 'w')) {
        	 	echo "Cannot open file ($filename)";
        		 exit;
   		}

   		#echo create_graph_string($uni);exit;
		if (fwrite($handle, create_graph_string($uni)) === FALSE) 
		{
       			echo "Cannot write to file ($filename)";
       			exit;
   		}
		
		chmod($filename, 0777);
		fclose($handle);

	
	#display it
	#echo $filename;
	#echo $filename = 'map/InitialXML.xml';
	#chdir(S3DB_SERVER_ROOT.'/map');
	#echo getcwd();
	echo '<APPLET  CODE = "com.touchgraph.linkbrowser.LinkBrowserApplet.class" ARCHIVE = "map/TGLinkBrowser.jar, map/nanoxml-2.1.1.jar, map/BrowserLauncher.jar" WIDTH = 100% HEIGHT = 90%></XMP><PARAM NAME = CODE VALUE = "com.touchgraph.linkbrowser.LinkBrowserApplet.class" ><PARAM NAME = ARCHIVE VALUE = "map/TGLinkBrowser.jar, map/nanoxml-2.1.1.jar, map/BrowserLauncher.jar" ><PARAM NAME="type" VALUE="application/x-java-applet;version=1.3"><PARAM NAME="scriptable" VALUE="false"><PARAM NAME = "browser" VALUE ="yes"><param name=targetFrame value= "main_page"><param name=externalFrame value= "externalFrame"><param name=initialXmlFile value="'.$filename.'"><param name=xmlStr value=""></APPLET>';
	
	
}
	

	
	

	
	function create_graph_string($O)
	{
		$filename = S3DB_SERVER_ROOT.'/map/Graph.xml';
		$chunksize = 1024;
		$fh = fopen($filename, 'r');
		if($fh == false)
		{
			return '';
		}
		$xmlfileStr = '';
		while(!feof($fh))
		{
			$xmlfileStr .= fread($fh, $chunksize);
		}
		fclose($fh);
		$xmlfileStr .= sprintf("%s\n", '<TOUCHGRAPH_LB version="1.20">');
		$xmlfileStr .= create_node_set($O);	
		$xmlfileStr .= create_edge_set($O);	
		//echo $xmlfileStr;
		$xmlfileStr .= create_params($O);	
		$xmlfileStr .= sprintf("%s\n", '</TOUCHGRAPH_LB>');
		//echo $xmlfileStr;
		return $xmlfileStr;
		
	}
	
	
	function create_node_set($O)
	{
		$node_set_str = sprintf("\t%s\n", '<NODESET>');
		
		$node_set_str .= create_nodes($O);
		$node_set_str .= sprintf("\t%s\n", '</NODESET>');
		//echo $node_set_str;	
		return $node_set_str;	
	}

	
	
	function create_nodes($O)
	{$action=$GLOBALS['action'];
		extract($O);
		
		
		$project_info = s3info('project', $project_id, $db);
		$project_node .= sprintf("\t\t%s\n",'<NODE nodeID="P'.$project_info['project_id'].'">');
		$project_node .= sprintf("\t\t\t%s\n", '<NODE_LOCATION x="0" y="0" visible="true"/>');
		$project_node .= sprintf("\t\t\t%s\n",'<NODE_HINT hint="'.str_replace("\"", "", htmlentities($project_info['project_description'])).'" width="300" height="-1" isHTML="false"/>');
		$project_node .= sprintf("\t\t\t%s\n", '<NODE_LABEL label="'.$project_info['project_name'].'" shape="3" backColor="0000FF" textColor="FFFF00" fontSize="12"/>');
		$project_node .= sprintf("\t\t\t%s\n", '<NODE_URL url="'.htmlentities($action['project']).'" urlIsLocal="true" urlIsXML="false"/>');
		#$project_node.= sprintf("\t\t\t%s\n",'<NODE_URL url="../project/project.php?project_id='.$_REQUEST['project_id'].'" urlIsLocal="true" urlIsXML="false"/>');
		$project_node .= sprintf("\t\t%s\n", '</NODE>');
		
		$s3ql['db'] = $db;
		$s3ql['user_id'] = $user_id;
		$s3ql['select']='*';
		$s3ql['from']='rules';
		$s3ql['where']['project_id'] = $_REQUEST['project_id'];
		$s3ql['where']['object']="!=UID";
		#$rules = s3list($s3ql);
		$rules = S3QLaction($s3ql);
		$GLOBALS['rules']=$rules;

		#Create the node for the regular resources
		#List all classes in project
		$s3ql=compact('user_id','db');
		$s3ql['select']='*';
		$s3ql['from']= 'classes';
		$s3ql['where']['project_id']=$_REQUEST['project_id'];
		#$resources = s3list($s3ql);
		$resources = S3QLaction($s3ql);
		$GLOBALS['collections']=$resources;
		
					
		if (is_array($resources))
		{
		$C = grab_id('collection', $resources); #=>these are the ids of all allowed nodes
		
		foreach($resources as $resource_info)
		{
			
			//Lena -created this session because map doesn't allow more than 1 get, but for queryresource to run properly we need at least 2 get's
			
			$rule_id = get_rule_id_by_entity_id($resource_info['resource_id'],  $resource_info['project_id'], $db);
			$subject =  str_replace($toReplace,$replace,htmlentities($resource_info['entity']));
			$notes = str_replace($toReplace,$replace,htmlentities($resource_info['notes']));
			
			
			if( $resource_info['project_id']==$_REQUEST['project_id'])
				$color = 'FF0000';
			else
				$color = 'FF6600';
			
			$resource_id =  $resource_info['resource_id'];
		
			$subject_node .= sprintf("\t\t%s\n", '<NODE nodeID="C'.$resource_id.'">');
			
			$subject_node .= sprintf("\t\t\t%s\n", '<NODE_LOCATION x="0" y="0" visible="false"/>');
			
			$subject_node .= sprintf("\t\t\t%s\n", '<NODE_HINT hint="'.$notes.'" width="300" height="-1" isHTML="true"/>');
			
			$subject_node .= sprintf("\t\t\t%s\n", '<NODE_LABEL label="'.$subject.'" shape="2" backColor="'.$color.'" textColor="FFFFFF" fontSize="14"/>');
			$subject_node .= sprintf("\t\t\t%s\n", '<NODE_URL url="'.htmlentities($action['resource']).'&amp;class_id='.$resource_info['resource_id'].'" urlIsLocal="true" urlIsXML="false"/>');
			
			$subject_node .= sprintf("\t\t%s\n", '</NODE>');
		}
		
		#and finally... the nodes for the objects...
		
		if(is_array($rules))
		#foreach($objects as $object)
		foreach($rules as $rule_info)
		{
			$subject = 	str_replace($toReplace,$replace,htmlentities($rule_info['subject']));
			$verb = str_replace($toReplace,$replace,htmlentities($rule_info['verb']));
			$object = str_replace($toReplace,$replace,htmlentities($rule_info['object']));
			$notes = str_replace($toReplace,$replace,htmlentities($rule_info['notes']));
			#is the rule from this project? if not, print a different color
			if($rule_info['project_id']==$_REQUEST['project_id'])
			{$objcolor = '336600';$classColor= 'FF0000';}
			else
				{$objcolor = '009900';$classColor='FF6600';}
			if($rule_info['object']!='UID' || $rule_info['verb']!='has UID')
			{$object_node .= sprintf("\t\t%s\n", '<NODE nodeID="R'.$rule_info['rule_id'].'">');
			$object_node .= sprintf("\t\t\t%s\n", '<NODE_LOCATION x="0" y="0" visible="false"/>');
			
			if($rule_info['object_id']==''){
			$object_node .= sprintf("\t\t\t%s\n", '<NODE_LABEL label="'.$object.'" shape="1" backColor="'.$objcolor.'" textColor="FFFF00" fontSize="12"/>');
			$object_node .= sprintf("\t\t\t%s\n", '<NODE_URL url="'.htmlentities($action['rule']).'&amp;class_id='.$rule_info['subject_id'].'&amp;rule_id='.$rule_info['rule_id'].'" urlIsLocal="true" urlIsXML="false"/>');
			$object_node .= sprintf("\t\t\t%s\n", '<NODE_HINT hint="'.$notes.'" width="300" height="-1" isHTML="false"/>');
			}
			elseif(in_array($rule_info['object_id'], $C)) {
			##Collection must either exist already as a visible node or the map will crash
				
			$object_node .= sprintf("\t\t\t%s\n", '<NODE_LABEL label="'.$object.'" shape="1" backColor="'.$classColor.'" textColor="FFFFFF" fontSize="12"/>');
			$object_node .= sprintf("\t\t\t%s\n", '<NODE_URL url="'.htmlentities($action['resource']).'&amp;class_id='.$rule_info['object_id'].'" urlIsLocal="true" urlIsXML="false"/>');
			$object_node .= sprintf("\t\t\t%s\n", '<NODE_HINT hint="'.$notes.'" width="300" height="-1" isHTML="false"/>');	
			}
			else {
				$object_node .= sprintf("\t\t\t%s\n", '<NODE_LABEL label="'.$object.'" shape="1" backColor="#EFEFEF" textColor="FFFF00" fontSize="12"/>');
				$object_node .= sprintf("\t\t\t%s\n", '<NODE_HINT hint="User is not allowed in this Collection" width="300" height="-1" isHTML="false"/>');
			}
			
			$object_node .= sprintf("\t\t%s\n", '</NODE>');
			}
			
			
		}

		#find all projects involved in the rules of this project
		#find out how many project_ids there are
		if(is_array($rules))
		{$extraproject_ids = array_map('grab_project_id', $rules);
		$extraproject_ids  = array_diff(array_unique($extraproject_ids), array($_REQUEST['project_id']));
		
		#echo '<pre>';print_r($extraproject_ids);
		
		#Create extra projects nodes
		if(is_array($extraproject_ids))
		foreach($extraproject_ids as $extra_project_id)
		{
		$project_info = get_info('project', $extra_project_id, $db);
		$extra_project_node .= sprintf("\t\t%s\n",'<NODE nodeID="P'.$extra_project_id.'">');
		$extra_project_node .= sprintf("\t\t\t%s\n", '<NODE_LOCATION x="1" y="1" visible="true"/>');
		$extra_project_node .= sprintf("\t\t\t%s\n",'<NODE_HINT hint="'.str_replace("\"", "", $project_info['project_description']).'" width="300" height="-1" isHTML="false"/>');
		$extra_project_node .= sprintf("\t\t\t%s\n", '<NODE_LABEL label="'.$project_info['project_name'].'" shape="3" backColor="406AFD" textColor="FFFF00" fontSize="12"/>');
		#$extra_project_node .= sprintf("\t\t\t%s\n", '<NODE_URL url="'.$action['project'].'" urlIsLocal="true" urlIsXML="false"/>');
		#$project_node.= sprintf("\t\t\t%s\n",'<NODE_URL url="../project/project.php?project_id='.$_REQUEST['project_id'].'" urlIsLocal="true" urlIsXML="false"/>');
		$extra_project_node .= sprintf("\t\t%s\n", '</NODE>');}
		}
		
			
		return $project_node.$extra_project_node.$subject_node.$object_node;
	

		}
	}
	
	
	function create_uid_node()
	{
		$uid_node .= sprintf("\t\t%s\n",'<NODE nodeID="UID">');
		$uid_node .= sprintf("\t\t\t%s\n", '<NODE_LOCATION x="0" y="0" visible="true"/>');
		$uid_node .= sprintf("\t\t\t%s\n",'<NODE_HINT hint="Unique Identifier" width="300" height="-1" isHTML="false"/>');
		$uid_node .= sprintf("\t\t\t%s\n", '<NODE_LABEL label="UID" shape="2" backColor="0000FF" textColor="FFFF00" fontSize="12"/>');
		$uid_node.= sprintf("\t\t\t%s\n",'<NODE_URL url="" urlIsLocal="false" urlIsXML="false"/>');
		$uid_node .= sprintf("\t\t%s\n", '</NODE>');
		return $uid_node;
	}

	function create_params()
	{
		 $params = sprintf("\t%s\n", '<PARAMETERS>');
		 $params .= sprintf("\t\t%s\n", '<PARAM name="offsetX" value=""/>');
		 $params .=sprintf("\t\t%s\n", '<PARAM name="rotateSB" value="0"/>');
		 $params .= sprintf("\t\t%s\n", '<PARAM name="zoomSB" value="-7"/>');
		 $params .=sprintf("\t\t%s\n", '<PARAM name="offsetY" value=""/>');
		 $params .=sprintf("\t%s\n", '</PARAMETERS>');
		return $params;
	}
	
	function create_edge_set($O)
	{
		extract($O);
		
		#Create edges for regular resources

		$edge_set_str = sprintf("\t%s\n", '<EDGESET>');
		
		
		#List all classes in project
//		$s3ql['db'] = $db;
//		$s3ql['user_id'] = $user_id;
//		$s3ql['select']='*';
//		$s3ql['from']='classes';
//		$s3ql['where']['project_id'] = $_REQUEST['project_id'];
//		#$resources = s3list($s3ql);
//		$resources = S3QLaction($s3ql); This was replaced by holding urles and collections in globals
		
		$resources = $GLOBALS['collections'];
		$C = grab_id('collection', $resources);
		
		$project_name = str_replace($toReplace, $replace, htmlentities($project_info['project_name']));
		if(!empty($resources))
		{
			if (is_array($resources))
			foreach($resources as $resource_info)
			{
				
				$project_info =s3info('project', $_REQUEST['project_id'], $db);
				$projectNode = 'P'.$project_info['project_id'];
				$classNode = 'C'.$resource_info['resource_id'];
				$subject = str_replace($toReplace, $replace, htmlentities($resource_info['entity']));

				if($resource_info['project_id'] == $_REQUEST['project_id'])
					$color = 'A0A0A0';
				else
					$color = 'E9E9E9';

				$edge_set_str .= sprintf("\t\t%s\n", '<EDGE fromID="'.$projectNode.'" toID="'.$classNode.'" label="[Project '.$project_name. '] has resource ['.$subject.']" type="1" length="20" visible="false" color="'.$color.'"/>');

				#create the edges between remote classes/rules and project_id
				if($resource_info['project_id']!=$_REQUEST['project_id'])
				$edge_set_str .= sprintf("\t\t%s\n", '<EDGE fromID="'.$classNode.'" toID="P'.$resource_info['project_id'].'" label="[Project '.$project_name. '] has resource ['.$subject.']" type="1" length="20" visible="false" color="'.$color.'"/>');


				#build an array with class name as keys and class_id as values for use in the rules
				$classes[$resource_info['entity']] = $resource_info['resource_id'];
				
			}		
		}
		
		$rules =  $GLOBALS['rules'];
		#$rules = include_all_class_id(compact('rules', 'project_id', 'user_id','db'));
		
		#echo '<pre>';print_r($rules);
		#for($i= 0; $i< count($rules); $i++)
		if(is_array($rules))
		foreach($rules as $rule_info)
		{
			
			#echo $classes[$rules[$i]['subject']];
			//echo $rules[$i]['subject'];
			
				if($rule_info['project_id'] == $_REQUEST['project_id'])
					$color = 'A0A0A0';
				else
					$color = 'E9E9E9';

				if($rule_info['object_id']!='')
				{	if(in_array($rule_info['object_id'], $C))
						$toID = 'C'.$rule_info['object_id'];
					else
						$toID = 'R'.$rule_info['rule_id'];	
				}
					else
						$toID = 'R'.$rule_info['rule_id'];
				
				#find the class_id where the rule will connect
				if(in_array($rule_info['subject_id'], $C)){
				if($rule_info['object']!='UID')
				#$edge_set_str .= sprintf("\t\t%s\n", '<EDGE fromID="C'.$rule_info['subject_id'].'" toID="'.$toID.'" label="Rule: [('.$rule_info['subject'].') '.$rule_info['verb'].' ('.$rule_info['object'].')] was created_on '.substr($rule_info['created_on'], 0, 19).' by '.find_user_loginID(array('account_id'=>$rule_info['created_by'], 'db'=>$O['db'])).'" type="1" length="40" visible="true" color="'.$color.'"/>');
				$edge_set_str .= sprintf("\t\t%s\n", '<EDGE fromID="C'.$rule_info['subject_id'].'" toID="'.$toID.'" label="R'.$rule_info['rule_id'].' was created_on '.substr($rule_info['created_on'], 0, 19).' by '.find_user_loginID(array('account_id'=>$rule_info['created_by'], 'db'=>$O['db'])).'" type="1" length="40" visible="true" color="'.$color.'"/>');
				}

				
			
		}

		
		
	
		$edge_set_str .= sprintf("\t%s\n", '</EDGESET>');

		
		
		return $edge_set_str;
	}

	
	

	 
?>
