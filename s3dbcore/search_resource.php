<?php
	#function search_resource builds the query to send to S3DB
	#input: x must contain at least $rules, rule/value pairs and db
	#output: returns all the resource_instances that match the search criteria
	#Helena F Deus, 13 November 2006
	function query_statements($q) {
		extract($q);
		list($resource_ids, $rule_values) = query_result($q);
		for($i=0;$i<count($resource_ids);$i++) {
			$items[] = array('item_id'=>$resource_ids[$i], 'notes'=>get_notes($resource_ids[$i],$db));
		}
		return $items;
	}

	function query_result($q) {
		$db = $q['db'];
		#Build and execute the query
		$sql = buildQueryString($q);
		$sql = "select statement_id, rule_id, resource_id, value from s3db_statement where resource_id in (".$sql.")";
		$db->query($sql, __LINE__, __FILE__);
		while($db->next_record()) {
			$resource_ids[] = $db->f('resource_id');
			$rule_values[$db->f('statement_id')] = array('rule_id'=>$db->f('rule_id'), 'value'=>$db->f('value'));
		}
		return (array($resource_ids, $rule_values));
	}

	function buildQueryString($q) {
		#Build the query for a simple intersection
		$i=0;
		foreach($q['RuleValuePair'] as $rule_id=>$value) {
			#Sintax for "AND", empty logical means "AND" as well, by default
			if(eregi('and',$q['logical'][$i]) ||  $q['logical'][$i]=='') {
				$AND[$i] = TRUE;
			} elseif(eregi('or',$q['logical'][$i])) {
				$OR[$i] = TRUE;
			} elseif(eregi('not', $q['logical'][$i])) {
				$NOT[$i] = TRUE;
			}
			#There is a small difference in MySQL and Postgres
			$regex = $GLOBALS['regexp'];

			#Basic query string
			$sql[$i] = "select resource_id from s3db_statement where rule_id='".$rule_id."' and value ".$logical.$regex."'".$value."'";
	
			#Increment the queries
			#Case when only one thing is queried
			if(count($q['RuleValuePair'])==1) {
				$sqlComplete .= "".$sql[$i]."";
			} else {
				if($i==0) {
					if($AND[$i]) { $sqlComplete = "".$sql[0].""; }
					elseif($NOT[$i]) { $sqlComplete = "".$sql[0].""; }
					elseif($OR[$i]) { $sqlComplete = "(".$sql[0].")"; }
				} else {
					if($AND[$i-1]) { $sqlComplete = "".$sql[$i]." and resource_id in (".$sqlComplete.")"; }
					elseif($OR[$i-1]) { $sqlComplete = "(".$sql[$i].") union ".$sqlComplete.""; }
					elseif($NOT[$i-1]) { $sqlComplete = "".$sql[$i]." and resource_id not in (".$sqlComplete.")"; }
				}
			}
			$i++;
		}
		return $sqlComplete;
	}

	function search_resource1($Q) {
		extract($Q);
		if($GLOBALS['s3db_info']['server']['db']['db_type']=='mysql') {
			$begin = '';
			$intersect = 'and resource_id in';
			$union = ') or resource_id in';
			$end=')';
			$extra =  '';
		} else {
			$begin = '(';
			$intersect = ') intersect';
			$union = ') union';
			$end= '';
			$extra = ')';
		}
					
		$final_query = 'select distinct resource_id, entity, notes, created_by, created_on from s3db_resource where resource_id in '.$begin;	
		$display_query = 'select distinct resource_id, entity, notes, created_by, created_on from s3db_resource where resource_id in '.$begin;
		$_SESSION['used_rule'] = '';
		//$_SESSION['rule_value_pairs'] = array();
		if(is_array($rules)) {
			$query_rule = Array();
			$used_rule = Array();
			$query ='';
			//$found = False;
			foreach($rules as $rule_info) { 		#build the individual queries (one per rule)
				$sqlquery = construct_query($rule_info);
				if($sqlquery != '') {
					$not_and_or = 'rule_'.$rule_info['rule_id'];
					if($_POST[$not_and_or] =='and' || $_POST[$not_and_or] =='') {
						$query.= construct_query($rule_info).' '.$intersect.' ';
					} elseif($_POST[$not_and_or] == 'or') {
						$query.= construct_query($rule_info).' '.$union.' ';
					}
					#else
					#$query.= construct_query($rule_info).' intersect ';
					#$query.= construct_query($queriable_rule[$i]);
					array_push($used_rule, $rule_info['rule_id']);
					array_push($query_rule, $rule_info);
				}
			}
			if($query !='') {
				$query = trim($query);
				if(strrpos($query, " ".$intersect) && substr($query, strrpos($query, " ".$intersect)) ==' '.$intersect) {
					$query = substr($query, 0, strrpos($query, " ".$intersect)).')'.$extra;
				} elseif(strrpos($query, " ".$union) && substr($query, strrpos($query, " ".$union)) ==' '.$union) {
					$query = substr($query, 0, strrpos($query, " ".$union)).')'.$extra;
				} else {
					$query .=  ')';
				}
				#$query .= ')';
				$final_query .= $query;
				$display_query .= $query;
			} else {
				$final_query = 'You need to specify some search criteria.';
				//$display_query = 'All instance of resource <b>'.$queriable_rule[0]['subject'].'</b>';	
				$display_query = $final_query;	
			}
		}
		$_SESSION['displayquery'] = $display_query;
		$db->query($final_query, __LINE__, __FILE__);
		while($db->next_record()) {
			#$found_resources[] = Array('resource_id'=>$db->f('resource_id'));
			$found_resources[] = Array(
									'resource_id'=>$db->f('resource_id'),
									'entity'=>$db->f('entity'),
									'created_by'=>$db->f('created_by'),
									'created_on'=>$db->f('created_on'),
									'notes'=>$db->f('notes')
								);
		}
		#print_r($found_resources);
		#$found_instances = get_found_resources($found_resources);
		$found_instances = $found_resources;
		$found_instances['sqlquery'] = $display_query;
		$_SESSION['used_rule'] = $used_rule;
		$_SESSION['query_rule'] = $query_rule;
		return $found_instances;									
	}
	
	function get_found_resources($found_resources) {
		global $project_info, $resource_info;
		if(count($found_resources) > 0) {
			$db = $_SESSION['db'];
			foreach($found_resources as $i=>$value) {
				#$sql = "select resource_id, owner, entity, notes, created_on  from s3db_resource where resource_id='".$found_resources[$i]['resource_id']."' and project_id='".$_SESSION['working_project']['id']."' order by created_on desc";
				#$sql = "select resource_id, entity, notes, created_on, created_by  from s3db_resource where resource_id='".$found_resources[$i]['resource_id']."' and project_id='".$project_info['id']."' and iid != 0 order by created_on desc";
				$sql = "select resource_id, entity, notes, created_on, created_by  from s3db_resource where resource_id='".$found_resources[$i]['resource_id']."' and iid != 0 order by created_on desc";
				$db->query($sql, __LINE__, __FILE__);
				if($db->next_record()) {
					$resources[] = Array(
										'resource_id'=>$db->f('resource_id'),
										#'owner'=>$db->f('owner'),
										'entity'=>$db->f('entity'),
										'notes'=>$db->f('notes'),
										'created_by'=>$db->f('created_by'),
										'created_on'=>$db->f('created_on')
									);
				}
			}
		}
		return $resources;
	}

	function search_resource($Q) {
		extract($Q);
		if($GLOBALS['s3db_info']['server']['db']['db_type']=='mysql') {
			$begin = '';
			$intersect = 'and resource_id in';
			$union = ') or resource_id in';
			$end=')';
			$extra =  '';
		} else {
			$begin = '(';
			$intersect = ') intersect';
			$union = ') union';
			$end= '';
			$extra = ')';
		}
		$final_query = 'select distinct resource_id, resource_class_id, entity, notes, created_by, created_on from s3db_resource where resource_id in '.$begin;	
		$display_query = 'select distinct resource_id, resource_class_id, entity, notes, created_by, created_on from s3db_resource where resource_id in '.$begin;
		$_SESSION['used_rule'] = '';
		#$_SESSION['rule_value_pairs'] = array();
		if(is_array($rules)) {
			$query_rule = Array();
			$used_rule = Array();
			$query ='';
			//$found = False;
			
			foreach($rules as $rule_id=>$rule_info) {		#build the individual queries (one per rule)
				$sqlquery = construct_query(compact('rule_info', 'rule_value_pairs', 'db'));
				if($sqlquery != '') {
					$not_and_or = 'rule_'.$rule_info['rule_id'];
					if($_POST[$not_and_or] =='and' || $_POST[$not_and_or] =='') {
						$query.= construct_query(compact('rule_info', 'rule_value_pairs', 'db')).' '.$intersect.' ';
					} elseif($_POST[$not_and_or] == 'or') {
						$query.= construct_query(compact('rule_info', 'rule_value_pairs', 'db')).' '.$union.' ';
					}
					#else
					#$query.= construct_query($rule_info).' intersect ';
					#$query.= construct_query($queriable_rule[$i]);
					array_push($used_rule, $rule_info['rule_id']);
					array_push($query_rule, $rule_info);
				}
			}
			if($query !='') {
				$query = trim($query);
				if(strrpos($query, " ".$intersect) && substr($query, strrpos($query, " ".$intersect)) ==' '.$intersect) {
					$query = substr($query, 0, strrpos($query, " ".$intersect)).')'.$extra;
				} elseif(strrpos($query, " ".$union) && substr($query, strrpos($query, " ".$union)) ==' '.$union) {
					$query = substr($query, 0, strrpos($query, " ".$union)).')'.$extra;
				} else {
					$query .=  ')';
				}
				#$query .= ')';
				if($query!='' && trim($orderBy)!='') {
					$query .= ' order by '.$orderBy;
				}
				$final_query .= $query;
				$display_query .= $query;
			} else {
				$final_query = 'You need to specify some search criteria.';
				#$display_query = 'All instance of resource <b>'.$queriable_rule[0]['subject'].'</b>';	
				$display_query = $final_query;	
			}
		}
		$_SESSION['displayquery'] = $display_query;
		$db->query($final_query, __LINE__, __FILE__);
		while($db->next_record()) {
			#$found_resources[] = Array('resource_id'=>$db->f('resource_id'));
			$found_resources[] = Array(
									'resource_id'=>$db->f('resource_id'),
									'entity'=>$db->f('entity'),
									'created_by'=>$db->f('created_by'),
									'created_on'=>$db->f('created_on'),
									'resource_class_id'=>$db->f('resource_class_id'),
									'notes'=>$db->f('notes')
								);
		}
		#$found_instances = get_found_resources($found_resources);
		$found_instances = $found_resources;
		$found_instances['sqlquery'] = $display_query;
		$_SESSION['used_rule'] = $used_rule;
		$_SESSION['query_rule'] = $query_rule;
		return $found_instances;									
	}	
	
	function construct_query($Q) {
		extract($Q);
		if($_SESSION['rule_value_pairs']=='') {
			$rule_value_pairs = array();
		} else {
			$rule_value_pairs = $_SESSION['rule_value_pairs'];
		}
		$rule_1 = 'rule_1_'.$rule_info['rule_id'];
		$rule_2 = 'rule_2_'.$rule_info['rule_id'];
		$not_and_or = 'rule_'.$rule_info['rule_id'];
		$subject = $rule_info['subject'];	
		$verb = $rule_info['verb'];	
		$object = $rule_info['object'];	
		$sql='';
		$value_pairs = array();
		#$common = "select distinct resource_id from s3db_statement where subject='".$subject."' and verb='".$verb."' and object='".$object."' and ";
		$common = "select distinct resource_id from s3db_statement where rule_id='".$rule_info['rule_id']."' and ";
		if($rule_value_pairs[$rule_1] != '') {
			$sql1 = translate_query($rule_value_pairs[$rule_1], 0);
			$rule_value = array(
							'rule_id'=>$rule_info['rule_id'], 
							'value'=>$rule_value_pairs[$rule_1]
						);
			array_push($value_pairs, $rule_value);
		}
		if($rule_value_pairs[$rule_2] != '' && $rule_value_pairs[$rule_2] !='') {
			$sql2 = translate_query($_POST[$rule_2], 0);
			$rule_value = array(
							'rule_id'=>$rule['rule_id'], 
							'value'=>$rule_value_pairs[$rule_2]
						);
			array_push($value_pairs, $rule_value);
		}
		if($sql1 !='' && $sql2 !='') {
			if($rule_value_pairs[$not_and_or] =='and') {
				if(valid_numeric($rule_value_pairs[$rule_1]) && valid_numeric($rule_value_pairs[$rule_2])) {
					$sql = '('.$common.$sql1.' and '.$sql2.')';
				} else {
					$sql = '('.$common.$sql1.') intersect ('.$common.$sql2.')';
				}
			}
			#if($rule_value_pairs[$not_and_or] =='not') {
			#	#nothing
			#}
			if($rule_value_pairs[$not_and_or] =='or') {
				$sql = '('.$common.'(('.$sql1.' or '.$sql2.'))';
			}
		} elseif($sql1 !='') {
			$sql = '('.$common.$sql1;
		} elseif($sql2 !='') {
			$sql = '('.$common.$sql2.')';
		}
		if($sql !='') {
			#$used_rule  = $_SESSION['used_rule'];
			#if(substr($used_rule, strrpos($used_rule,",")+1) != $rule['rule_id'])
			#$_SESSION['used_rule'] = $used_rule.','.$rule['rule_id'];
		}	
		return $sql;
	}

	function translate_query($query, $flag) {
		if($GLOBALS['s3db_info']['server']['db']['db_type']=='mysql') {
			$regexp = 'regexp';
		} else {
			$regexp = '~';
		}
		$is_file = False; 
		if(substr($query, 0, 5) == 'File:') {
			$is_file = True;
			$query = trim(substr($query, 5)); 
		}	
		$query = stripslashes($query);
		$possible_start_str = Array('~*', '~', '!~*', '!~', '>=', '>', '<=','<');
		foreach($possible_start_str as $i) {
			if(substr($query, 0, strlen($i)) == $i) {
				$rest = substr($query, strlen($i));
				$rest = htmlspecialchars(trim($rest));
				#$rest = str_replace("(", "\(", $rest);	
				#$rest = str_replace(")", "\)", $rest);	
				$rest = str_replace("+", "\\+", $rest);	
				$rest = str_replace("?", "\\?", $rest);	
				#$rest = str_replace("*", "\\*", $rest);	
				#$rest = addslashes(preg_quote($rest));
				$rest = addslashes($rest);
				if(substr($rest, 0, 1) == "'") {
					if($is_file) {
						return 'file_name '.$regexp.' %'.$rest.'%';
					} else {
						return 'value '.$regexp.' '.$rest.'';
					}
					#} else {
					#	if($is_file)
					#		return 'file_name '.$i.' '.$rest;
					#	else	
					#		return 'value '.$i.' '.$rest;
					#}
				} else {
					if($is_file) {
						return "file_name ".$regexp." '%".$rest."%'";
					} else {
						return "value ".$regexp." '".$rest."'";
					}
				}
			}
		}
		if(substr($query, 0) == "'") {
			if($is_file) {
				return 'lower(file_name) ='.htmlspecialchars(strtolower($query));
			} else {
				return 'lower(value) '.$regexp.' '.htmlspecialchars(strtolower($query));
			}
		} else {
			if($is_file) {
				return "lower(file_name) ='".htmlspecialchars(strtolower($query))."'";
			} else {
				return "lower(value) ".$regexp." '".htmlspecialchars(strtolower($query))."'";
			}
		}
	}
?>