<body>
<table>

 <?php 
    ini_set('display_errors',0);
	if($_REQUEST['su3d'])
	ini_set('display_errors',1);
	$action = $GLOBALS['action'];
	
	if($class_info=='')
	 $class_info = $resource_info;
	if($class_info=='')
		$class_info=URIinfo('C'.$class_id, $user_id, $key, $db);
	 

	 $headerLinks = array($action['insertinstance'].'&action=add'=>'Add '.$class_info['entity'], $action['querypage'].'&listall=yes'=>'List all  '.$class_info['entity'], $action['querypage'].'&listall=no'=>'Query  '.$class_info['entity'], $action['ruletemplate'] =>'Rule Template');

	 if(!$_SESSION['db'])
	  {
	  $headerLinks = array();
	  }
	 if(!$class_info['propagate'])
	 {
	 $headerLinks = array();
	 }
	  
	 if(!$class_info['add_data'])
	 $headerLinks = array_filter(array_diff_key($headerLinks, array($action['insertinstance'].'&action=add'=>'', $action['ruletemplate']=>'')));
	 
	 
	 #echo '<pre>';print_r($headerLinks);
	 
	 foreach($headerLinks as $link=>$desc)
	  {
		 if(!ereg('&class_id='.$class_info['resource_id'], $link))
			 $link = $link.'&class_id='.$class_info['resource_id'];
		 echo '<a style= "border-right: 1px solid dodgerblue;	color : navy; font-size : smaller; font-weight : bold;	padding : 5px 10px 2px 10px; text-decoration : none;" href="'.$link.'">'.$desc.'</a>';
	  }
      ?>

	
</table>