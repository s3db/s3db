<?php
	##msredirect.php is a function that redirects a url to a given deployment given some attribute of that url
	include('config.inc.php');
	include('core.header.php');
	$ms = ($_REQUEST['ms']!='')?$_REQUEST['ms']:S3DB_URI_BASE;
	if(substr($ms, strlen($ms),1)!='/') { $ms .= '/'; }

	if($_REQUEST['deployment_id']!='') {
		$rule_query=compact('user_id','db');
		$rule_query['url'] =$ms;
		$rule_query['select'] = '*';
		$rule_query['from'] = 'statements';
		$rule_query['where']['item_id'] =  $_REQUEST['deployment_id'];
		$stats = Query($rule_query);
		if(is_array($stats)) {
			foreach ($stats as $statement) {
				if($statement['object']=='url') {
					$URL[] = $statement['value'];
				}
			}
		}
	} else {
		foreach ($_GET as $attribute=>$value) {
			if($value && !ereg('su3d|args|ms|deployment_id|format|func', $attribute)) {
				$objects[] = $attribute;
				$values[] = $value;
				$dep_project=$GLOBALS['deployment_project']['project_id'];
				
				$rule_query=compact('user_id','db');
				$rule_query['url'] =$ms;
				$rule_query['select'] = '*';
				$rule_query['from'] = 'rules';
				$rule_query['where']['object']=$attribute;
				$rule_query['where']['project_id']=$dep_project;
				$data = Query($rule_query);
				
				if(is_array($data)) {
					$rule_id[] = $data[0]['rule_id'];
				}
			} elseif($attribute=='name') {
				#find the items that match this name. Just to make the wholeprocess faster
				$rule_id = array($GLOBALS['deployment_project']['name']['rule_id']);
				$values = array($value);
			}
		}
		if(is_array($rule_id)) {
			foreach ($rule_id as $i=>$Rid) {
				$s3ql=compact('user_id','db');
				$s3ql['url'] =$ms;
				$s3ql['select']='*';
				$s3ql['from']='statements';
				$s3ql['where']['rule_id']=$Rid;
				$s3ql['where']['value']=$values[$i];
			    $items = Query($s3ql);
				
				if(is_array($items)) {
					foreach ($items as $item) {
						$s3ql=compact('user_id','db');
						$s3ql['url'] =$ms;
						$s3ql['select']='*';
						$s3ql['from']='statements';
						$s3ql['where']['item_id'] = $item['item_id'];
						##If the user does no want redirect, or there is + 1 item, query all rules. Otherwise, find the url directly
						if($_GET['redirect']!='0' || count($items)>1) {
							$s3ql['where']['rule_id'] = $GLOBALS['deployment_project']['url']['rule_id'];
						}
						$stats = Query($s3ql);

						if(is_array($stats)) {
							foreach ($stats as $statement) {
								if($statement['object']=='url') {
									$URL[] = $statement['value'];
								}
							}
						}
					}
				}
			}
		}
	}

	if($_REQUEST['args']!='') {
		$args=$_REQUEST['args'];
	} else {
		ereg('(\?.*)$',$_SERVER['REQUEST_URI'],$q);	
		if($q[1]) { $args = $q[1]; }
	}

	if($_REQUEST['func']!='') {
		if(!ereg('\.php$',$_REQUEST['func'])) { 
			$func = $_REQUEST['func'].'.php'; 
		} else { 
			$func = $_REQUEST['func'];
		}
	}

	if($args) { $more = '/'.$func.$args; }
	if($_GET['redirect']!='0' && count($URL)==1) {
		Header("Location:".$URL[0].$more);
		exit;
	} elseif (count($URL)>=1) {
		$URL = array_unique($URL);
		if(count($URL)==1 && $_REQUEST['redirect']!='0') {
			Header("Location:".$URL[0].$more);
			exit;
		} else {
			$data = $stats;
			$cols = array_keys($stats[0]);
			$format = ($_REQUEST['format']=='')?'html':$_REQUEST['format'];
			$z = compact('data','cols', 'format');
			echo  outputFormat($z);
			#echo "More than 1 URL was found";
		}
		//$letter = 'E';
		//$data = $URL;
		//
		//
		//
		//$pack= compact('data','letter', 'format', 'db');
		//exit;
	}
	
	function Query($s3ql) {
		if(!ereg('^'.S3DB_URI_BASE, $s3ql['url'])) {
			$s3ql=array_filter(array_diff_key($s3ql, array('db'=>'', 'user_id'=>'')));
			$rule_query = S3QLquery($s3ql);
			$result = html2cell(stream_get_contents(fopen($rule_query, 'r')));
			return ($result);
		} else {
			$s3ql=array_filter(array_diff_key($s3ql, array('url'=>'', 'key'=>'')));
			$result = S3QLaction($s3ql);
			return ($result);
		}
	}
?>