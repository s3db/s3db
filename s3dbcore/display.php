<?php
/**
	
	* @author Helena F Deus <helenadeus@gmail.com>
	* @license http://www.gnu.org/copyleft/gpl.html GNU General Public License
	* @package S3DB http://www.s3db.org
*/

#display.php contains functions that accept array from queries and parses the results in several formats


function display($x)
#x is the data to be displayed and the format
{
#echo '<pre>';print_r($x['columns']);
	#Format must be: <TR><TD>value1</TD><TD>value2</TD><TD>values3</TD></TR> => This means the last value will not hold the same thing as the other two!
	
	#echo '<pre>';print_r($x['columns']);
	$display .= $x['format']['begin_table'].$x['format']['tr'].$x['format']['td'];
	$display .= $x['header'].$x['format']['end_td'].$x['format']['end_tr'];
		if (is_array($x['data']))
			foreach ($x['data'] as $datum) #datum: quantum units of data :-)
				{
				
				$display .= $x['format']['tr'].$x['format']['td'];
				if (is_array($x['columns']))
					#for ($i=0; $i<count($x['columns']); $i++)
					$c=0;
					foreach($x['columns'] as $i=>$name)
					{
					if ($c<count($x['columns'])-1)
					$display .= $datum[$x['columns'][$i]].$x['format']['middle'];
					else
					$display .= $datum[$x['columns'][$i]]. $x['format']['end_td'].$x['format']['end_tr'];
					$c++;
					}
				}
	$display .= $x['format']['end_table'];
	
	return $display;

}

function S3QLoutput($D)
	{
	extract($D);

	$data = listS3DB($D);
		
		if(is_array($data))
		$data = array_map('ValuesToFileLinks', $data);
		
		

	#echo '<pre>';print_r($data);
	#After the query, resume the cols that are supposed to be displayed
	if($D['out']!='' && $D['out']!='*') 
		{
		if ($SQLfun == 'distinct')
		$D['out'] = str_replace(array($SQLfun, "(", ")"), "", $D['out']);	
		
		$cols = array_map('trimmit', explode(',', $D['out']));
		}


	for($c=0;$c<count($cols);$c++)
			{$pCol = $cols[$c];
			if($c==count($cols)-1)
			$header .= trim($pCol);
			else
			$header .= trim($pCol).$format['middle'];
			}
	$x = array('data'=>$data, 'format'=>$format, 'header'=>$header, 'columns'=>$cols);
				
		if($data!='')
		echo display($x);
		else echo "<report>Your query returned no results</report>";


	}

function list_S3QL($element, $D)
	{
	extract($D);


	if($element== 'projects')
		{
		$data = list_projects($D);

		}

	if ($element=='resources' || $element=='classes')
		{
			
			$data = list_classes($D);
			
			#$data = list_shared_resources ($D);
			#if($data=='')
			#$data = list_project_resources ($D);
		
		}
	if($element == 'users')
		{
			
			#$data = list_users($D);
			$data = list_all_users($D);

		}
	if($element == 'keys')
		{
		
			$data = list_keys($D);

		}
	if($element == 'accesslog')
		{
			$data = list_logs($D);

		}
	if($element == 'rules')
		{
			
			$data = list_rules($D);
			
		}
	if ($element == 'rulelog')

		{
		$data = list_rules_log($D);
		
		}
	if($element == 'resource instances')
		{
		
			$data = list_all_instances($D);
			

		}
	if($element == 'statements')
		{

		$data = list_statements($D);
		
		
		if(is_array($data))
		$data = array_map('ValuesToFileLinks', $data);
		
		}

	#echo '<pre>';print_r($data);
	#After the query, resume the cols that are supposed to be displayed
	if($D['out']!='' && $D['out']!='*') 
		{
		if ($SQLfun == 'distinct')
		$D['out'] = str_replace(array($SQLfun, "(", ")"), "", $D['out']);	
		
		$cols = array_map('trimmit', explode(',', $D['out']));
		}


	for($c=0;$c<count($cols);$c++)
			{$pCol = $cols[$c];
			if($c==count($cols)-1)
			$header .= trim($pCol);
			else
			$header .= trim($pCol).$format['middle'];
			}
	$x = array('data'=>$data, 'format'=>$format, 'header'=>$header, 'columns'=>$cols);
				
		if($data!='')
		echo display($x);
		else echo "<report>Your query returned no results</report>";


	}

function completeDisplay($pack)
	{
	global $timer;
	#$pack = compact('t', 'data', 'format') 
	extract($pack);	
	
	$data = ($s3qlOut=='')?$data:$s3qlOut;
	$Outputs = columnsToDisplay($letter,$returnFields=array(),$data);
			##Disctionary output
			
			if($complete){
			#Find what other cols should be in the output based on dictionary
			
			$linkCols = array();
				
				foreach ($data as $d=>$data_info) {
					if($data[$d]['links']!=""){
						 foreach ($data[$d]['links'] as $lName=>$lVal) {
							$data[$d][$lName] = $lVal;
							 
							 if(!in_array($lName, $Outputs)){	
								array_push($Outputs, $lName);
								}
						 }
					}
					
				}
					
			
			}

			$cols = $Outputs;
		
		#map some cols
		if($data['class_id']!='')
				$data['resource_class_id'] =$data['class_id'];
			if($s3ql['from'] =='users' && $s3ql['where']['project_id']!='') 
				{$s3ql['from'] = 'project_acl';
				#$cols = array_merge($cols, array('permissionOnResource'));
				foreach($data as $out=>$val)
					{
					$data[$out]['project_id'] = $data[$out]['acl_project_id'];
					$data[$out]['user_id'] = $data[$out]['acl_account'];
					$data[$out]['permission_level'] = $data[$out]['acl_rights'];
					}
				}
		
			if(is_array($data))
					{
					$data = array_map('ValuesToFileLinks', $data);#on statements, return links each time there is a file
					

					}
			if(is_array($cols))
				{
				if($user_id!='1')
					$cols = array_diff($cols, array('session_id', 'account_type','account_status', 'iid','project_status'));
					
					$cols= array_diff($cols, array('account_pwd'));
				}
			
			
		
		
		#if(ereg('html|tab', $format) ||  $format=='')
		
		
		if(!ereg('json|php|xml|rdf|n3|sif|turtle', $format)) 
			{
			
			if($format=='html.pretty'){
				$format='html';
				
				echo '<script type="text/javascript">
				<!--
				function paintRows(){
				lines = document.getElementsByTagName(\'tr\');
				for (i=0; i<lines.length; i=i+2) {lines[i].style.backgroundColor = \'#BBFFFF\'}
				for (i=1; i<lines.length; i=i+2) 
				{lines[i].style.backgroundColor = \'lightyellow\'};
				}
				//-->
				</script>';
				echo '<body onload = paintRows()>';
			}
			elseif(ereg('html.(.*)', $format, $css))
			{
				$format='html';
				$style = @stream_get_contents(@fopen($css[1], 'r'));
				echo '<style type="text/css">';
				echo $style;
				echo '</style>';
			}
			
			$format = get_parser_characters($format);
			
			#Fetch the cols of what is to be returned
			if($s3ql['select']!='')
			{
			$P['out'] = urldecode($s3ql['select']);
			$P['SQLfun'] = ereg_replace("\(.*\)", "",$P['out']);
			if($P['out']==$P['SQLfun']) $P['SQLfun']='';
			}
			
			
			
			#After the query, resume the cols that are supposed to be displayed. Remove the sensitivy cols that should not be displayed
			if($P['out']!='' && $P['out']!='*')
				{if($P['SQLfun'] == 'distinct')
					$P['out'] = str_replace(array($P['SQLfun'], "(", ")"), "", $P['out']);
				
				$cols = array_map('trimmit', explode(',', $P['out']));
				}
			#echo '<pre>';print_r($cols);exit;
			$c=0;
			
			
			foreach($cols as $i=>$name)
			{

				$pCol = $name;
				#if($c==count($cols)-1)
				if ($c<count($cols)-1)
				$header .= trim($pCol).$format['middle'];
				else
				$header .= trim($pCol);
				$c++;
			}
			
			$x = array('data'=>$data, 'format'=>$format, 'header'=>$header, 'columns'=>$cols);
			
			return display($x);
			#exit;
			}
			
		else 
			{
			
				#filter data by selected
					
					if($s3ql['select']!='*'){
					$s3ql_out=ereg_replace(' ', '', $s3ql['select']);#take out all the spaces
					$selectFields = explode(',', $s3ql_out);
					}
					
				#clean up the non display field first
					if(is_array($data))
					foreach ($data as $kd=>$value) {
						if(!empty($selectFields[0])){
							foreach ($selectFields as $colname) {
							$data2display[$kd][$colname] = $value[$colname];
							}
						}
						else {
							//echo '<pre>';print_r($value);
							foreach ($cols as $colname) {
							#if($value[$colname]!='')
							$data2display[$kd][$colname] = $value[$colname];	
							}
						}
					}
				#if dictionary is requested, get the namespaces 
				if($complete){
				$s3qlN=compact('user_id','db');
				$s3qlN['from']='namespaces';
				$formatN = 'array';
				$namespaces = query_user_dictionaries($s3qlN,$db, $user_id,$formatN);
				if($timer) $timer->setMarker('Namespaces retrieved');
				}
				
				$data = is_array($data2display)?$data2display:$data;
				
			
				if($_REQUEST['out']=='header' || ($format=='json' && $_SERVER['HTTPS']))
					{
					header("Pragma: public");
					header("Expires: 0"); // set expiration time
					header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
					header("Content-Type: application/force-download");
					header("Content-Type: application/octet-stream");
					header("Content-Type: application/download");
					#header("Content-Type: ".$ext."");

					// use the Content-Disposition header to supply a recommended filename and
					// force the browser to display the save dialog.
					header("Content-Disposition: attachment; filename=s3db.".$format."");
					header("Content-Transfer-Encoding: binary");
					}
			
			if($format=='json')
				{
				$callback = ($_REQUEST['jsonp']=='')?(($_REQUEST['callback']=='')?'s3db_json':$_REQUEST['callback']):$_REQUEST['jsonp'];
				$onLoad = ($_REQUEST['onload']=='')?'':'; '.stripslashes($_REQUEST['onload']).((ereg('\(.*\)',$_REQUEST['onload'])?'':'()'));
				$jsonpp = ($_REQUEST['jsonpp']=='')?'':', "'.$_REQUEST['jsonpp'].'"';
				
				return ($callback.'('.json_encode($data).$jsonpp.')'.$onLoad);
				exit;
				}
			elseif($format=='php')
				{
				return serialize($data);
				#echo '$data = ';
				#print_r($data);
				exit;
				}
			elseif($format=='xml')
				{
				$xmlData = xml_encode($data, $letter,$root,$namespaces);
				
				return ($xmlData);
				exit;
				}
			elseif(ereg('rdf-json|rdf|n3|turtle',$format))
				{
				if(!in_array(S3DB_SERVER_ROOT.'/rdfheader.inc.php', get_included_files())) include_once(S3DB_SERVER_ROOT.'/rdfheader.inc.php');
				
				return rdf_encode($data, $letter, $format, $db,$namespaces);
				exit;
				}
			elseif($format=='sif'){
				return tab_encode($data, $returnFields);
				exit;
				}
			
			}
			
				
		
	
	
		
		
		if (is_array($s3qlOut) && !empty($s3qlOut)) #this menas data was removed
			
			return formatReturn($GLOBALS['error_codes']['no_permission_message'],"User does not have permission to access resource(s)", $s3ql['format']);
			
		
		
	}

function outputFormat($z)
{##$z = compact('data','cols', 'format');
extract($z);
	if(!ereg('json|php|xml|rdf|n3|sif|turtle', $format))
			{
				

				if($format=='html.pretty'){
				
				$format='html';
				
				echo '<script type="text/javascript">
				<!--
				function paintRows(){
				lines = document.getElementsByTagName(\'tr\');
				lines[0].setAttribute("style", "font-weight: bold");
				for (i=0; i<lines.length; i=i+2) {lines[i].style.backgroundColor = \'#CCFFFF\'}
				for (i=1; i<lines.length; i=i+2) 
				{lines[i].style.backgroundColor = \'#FFFFFF\'};
				}
				//-->
				</script>';
				echo '<body onload = paintRows()>';
				}
				elseif(ereg('html.(.*)', $format, $css))
				{
				$format='html';
				$style = @stream_get_contents(@fopen($css[1], 'r'));
				echo '<style type="text/css">';
				echo $style;
				echo '</style>';
				}
				
				$format = get_parser_characters($format);
				
				#Fetch the cols of what is to be returned
				if($s3ql['select']!='')
				{
				$P['out'] = urldecode($s3ql['select']);
				$P['SQLfun'] = ereg_replace("\(.*\)", "",$P['out']);
				if($P['out']==$P['SQLfun']) $P['SQLfun']='';
				}
				#else {
				#	$P['out'] = implode(',',array_keys($data[0]));
				#}
				
				
				
				#After the query, resume the cols that are supposed to be displayed. Remove the sensitivy cols that should not be displayed
				if($P['out']!='' && $P['out']!='*')
					{if($P['SQLfun'] == 'distinct')
						$P['out'] = str_replace(array($P['SQLfun'], "(", ")"), "", $P['out']);
					

					$cols = array_map('trimmit', explode(',', $P['out']));

					}
				
				
				
				
				
				#echo '<pre>';print_r($cols);exit;
				$c=0;
				
				
				foreach($cols as $i=>$name)
				{
				
				$pCol = $name;
				#if($c==count($cols)-1)
				if ($c<count($cols)-1)
				$header .= trim($pCol).$format['middle'];
				else
				$header .= trim($pCol);
				$c++;
				}
				
				$x = array('data'=>$data, 'format'=>$format, 'header'=>$header, 'columns'=>$cols);

				
				return display($x);
				#exit;
			}
			else {
		
			#if dictionary is requested, get the namespaces 
				if($namespaces_needed){
				$s3qlN=compact('user_id','db');
				$s3qlN['from']='namespaces';
				$formatN = 'array';
				
				$namespaces = query_user_dictionaries($s3qlN,$db, $user_id,$formatN);
				if($timer) $timer->setMarker('Namespaces retrieved');
				}
			
			if($_REQUEST['out']=='header' || ($format=='json' && $_SERVER['HTTPS']))
					{
					header("Pragma: public");
					header("Expires: 0"); // set expiration time
					header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
					header("Content-Type: application/force-download");
					header("Content-Type: application/octet-stream");
					header("Content-Type: application/download");
					#header("Content-Type: ".$ext."");

					// use the Content-Disposition header to supply a recommended filename and
					// force the browser to display the save dialog.
					header("Content-Disposition: attachment; filename=s3db.".$format."");
					header("Content-Transfer-Encoding: binary");
					}
			
			if($format=='json')
				{
				#$callback = ($_REQUEST['jsonp']=='')?'s3db_json':$_REQUEST['jsonp'];
				$callback = ($_REQUEST['jsonp']=='')?(($_REQUEST['callback']=='')?'s3db_json':$_REQUEST['callback']):$_REQUEST['jsonp'];
				$onLoad = ($_REQUEST['onload']=='')?'':'; '.stripslashes($_REQUEST['onload']).((ereg('\(.*\)',$_REQUEST['onload'])?'':'()'));
				$jsonpp = ($_REQUEST['jsonpp']=='')?'':', "'.$_REQUEST['jsonpp'].'"';
				
				return ($callback.'('.json_encode($data).$jsonpp.')'.$onLoad);
				exit;
				}
			elseif($format=='php')
				{
				return serialize($data);
				#echo '$data = ';
				#print_r($data);
				exit;
				}
			elseif($format=='xml')
				{
				#header("Content-type: application/xml"); 
				return xml_encode($data, $letter, $root,$namespaces);
				exit;
				}
			elseif(ereg('rdf|n3|turtle',$format))
				{
				
				if(!in_array(S3DB_SERVER_ROOT.'/rdfheader.inc.php', get_included_files())) include_once(S3DB_SERVER_ROOT.'/rdfheader.inc.php');
				return rdf_encode($data, $letter, $format, $db, $namespaces);
				exit;
				}
			elseif($format=='sif'){
				return tab_encode($data, $returnFields);
				exit;
				}
			
			}
			
				
		
	
	
		if (is_array($s3qlOut) && !empty($s3qlOut)) #this menas data was removed
			return formatReturn($GLOBALS['error_codes']['no_permission_message'],"User does not have permission to access resource(s)", $s3ql['format']);
			

}

function array2str($D)
{#$D = compact('data','format', 'select','returnFields', 'letter','cols', 'db')
	extract($D);
	$select = ($s3ql['select']!='')?$s3ql['select']:$select;
	
	if(!ereg('json|php|xml|rdf|n3|sif', $format))
			{
				

				if($format=='html.pretty'){
				
				$format='html';
				
				echo '<script type="text/javascript">
				<!--
				function paintRows(){
				lines = document.getElementsByTagName(\'tr\');
				for (i=0; i<lines.length; i=i+2) {lines[i].style.backgroundColor = \'#BBFFFF\'}
				for (i=1; i<lines.length; i=i+2) 
				{lines[i].style.backgroundColor = \'lightyellow\'};
				}
				//-->
				</script>';
				echo '<body onload = paintRows()>';
				}
				elseif(ereg('html.(.*)', $format, $css))
				{
				$format='html';
				$style = @stream_get_contents(@fopen($css[1], 'r'));
				echo '<style type="text/css">';
				echo $style;
				echo '</style>';
				}
				
				$format = get_parser_characters($format);
				
				#Fetch the cols of what is to be returned
				if($select!='')
				{
				$P['out'] = urldecode($select);
				$P['SQLfun'] = ereg_replace("\(.*\)", "",$P['out']);
				if($P['out']==$P['SQLfun']) $P['SQLfun']='';
				}
				
				
				
				#After the query, resume the cols that are supposed to be displayed. Remove the sensitivy cols that should not be displayed
				if($P['out']!='' && $P['out']!='*')
					{if($P['SQLfun'] == 'distinct')
						$P['out'] = str_replace(array($P['SQLfun'], "(", ")"), "", $P['out']);
					

					$cols = array_map('trimmit', explode(',', $P['out']));

					}
				
				
				
				
				
				#echo '<pre>';print_r($cols);
				$c=0;
				
				
				foreach($cols as $i=>$name)
				{
				
				$pCol = $name;
				#if($c==count($cols)-1)
				if ($c<count($cols)-1)
				$header .= trim($pCol).$format['middle'];
				else
				$header .= trim($pCol);
				$c++;
				}
				
				$x = array('data'=>$data, 'format'=>$format, 'header'=>$header, 'columns'=>$cols);

				
				return display($x);
				#exit;
			}
			else {
				
				#filter data by selected
					
					if($s3ql['select']!='*'){
					#$t=$GLOBALS['s3codes'][$letter];
					#$t=$GLOBALS['plurals'][$t];
					#$toreplace = array_keys($GLOBALS['s3map'][$t]);
					#$replacements = array_values($GLOBALS['s3map'][$t]);
					#$s3ql['select'] = str_replace($toreplace, $replacements, $s3ql['select']);
					$s3ql_out=ereg_replace(' ', '', $s3ql['select']);#take out all the spaces
					$selectFields = explode(',', $s3ql_out);
					}
					
				#clean up the non display field first
				#echo '<pre>';print_r($data);
				#echo '<pre>';print_r($selectFields);
					foreach ($data as $key=>$value) {
						if(!empty($selectFields[0])){
							foreach ($selectFields as $colname) {
							$data2display[$key][$colname] = $value[$colname];
							}
						}
						else {
							//echo '<pre>';print_r($value);
							foreach ($cols as $colname) {
							$data2display[$key][$colname] = $value[$colname];	
							}
						}
					}
					
				$data = $data2display;
			
			if($_REQUEST['out']=='header' || ($format=='json' && $_SERVER['HTTPS']))
					{
					header("Pragma: public");
					header("Expires: 0"); // set expiration time
					header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
					header("Content-Type: application/force-download");
					header("Content-Type: application/octet-stream");
					header("Content-Type: application/download");
					#header("Content-Type: ".$ext."");

					// use the Content-Disposition header to supply a recommended filename and
					// force the browser to display the save dialog.
					header("Content-Disposition: attachment; filename=s3db.".$format."");
					header("Content-Transfer-Encoding: binary");
					}
			
			if($format=='json')
				{
				
				return ('s3db_json('.json_encode($data).')');
				exit;
				}
			elseif($format=='php')
				{
				return serialize($data);
				#echo '$data = ';
				#print_r($data);
				exit;
				}
			elseif($format=='xml')
				{
				#header("Content-type: application/xml"); 
				return xml_encode($data, $letter);
				exit;
				}
			elseif($format=='rdf' || $format=='n3')
				{
				include('rdfheader.inc.php');
				return rdf_encode($data, $letter, $format, $db);
				exit;
				}
			elseif($format=='sif'){
				return tab_encode($data, $returnFields);
				exit;
				}
			
			}
		
	}

function columnsToDisplay($letter,$returnFields=array(),$data=array())
{
	if($letter!='E'){
	$element = $GLOBALS['s3codes'][$letter];
	
	$cols = (empty($returnFields))?array_merge($GLOBALS['dbstruct'][$element], $returnFields):$GLOBALS['dbstruct'][$element];
	$outputNames = $GLOBALS['dbstruct'][$element];
	$outputNames = array_merge($GLOBALS['common_attr'], $outputNames);
	}
	else {
		$outputNames = array();
	}
	$lessOutputs = array();
			
			switch ($letter) {
				
				case 'D':
					$moreOutputs = array('mothership', 'message','self');
				break;
				case 'G':
					$moreOutputs = array('group_id', 'groupname', 'name','change', 'add_data', 'uid','uri');
					
				break;
				case 'U':
				#inthis case there are some things that need to come out of the output	
				#if ($user_id!='1') {
				
				$lessOutputs = array('account_pwd', 'account_group', 'account_addr_id', 'addr1','addr2','acl');
				#}
				
				$moreOutputs = array('user_id', 'username', 'address', 'login', 'change', 'add_data', 'permissionOnResource','assigned_permission','effective_permission','filter', 'uid','uri', 'assigned_permissionOnEntity','effective_permissionOnEntity');
				
				break;
				case 'P':
				$moreOutputs = array('name', 'permission_level', 'assigned_permission','effective_permission', 'uid','uri');
				$lessOutputs = array('project_folder');
				
				break;

				case 'C':
				$moreOutputs = array('class_id', 'collection_id', 'name','description','permission_level','assigned_permission','effective_permission', 'uid','uri');
				
				break;
				
				case 'I':
				$moreOutputs = array('collection_id', 'class_id', 'instance_id', 'item_id', 'description','permission_level','assigned_permission','effective_permission',  'uid','uri');
				$lessOutputs = array();
				break;

				case 'S':
				$moreOutputs = array('subject', 'subject_id','verb','verb_id','object','object_id','description','instance_id', 'item_id', 'permission_level','assigned_permission','effective_permission', 'change', 'add_data', 'uid','uri');
				$lessOutputs = array('status');
				break;

				case 'R':
				
				$moreOutputs = array('permission_level','assigned_permission','effective_permission','description', 'change', 'add_data', 'uid','uri');
				$lessOutputs = array('permission');
				break;
				
				case 'E':
				$outputNames = array_keys($data[0]);
				$cols = array_keys($data[0]);
				$lessOutputs = array();
				$moreOutputs = array();
				break;

				default :
				$Outputs = $pack['cols'];
				
			}
			$Outputs = array_diff($outputNames, $lessOutputs);
			$Outputs = array_merge($Outputs, $moreOutputs);
			
			#$data = $s3qlOut;
			$cols = $Outputs;
return ($cols);		
}

?>