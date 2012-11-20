<?php
	#datamatrix is a generic file of functions that generates the table of statements for several outputs
	function create_datamatrix_header($head) {
		##Head is an array that must contain verbs and  object from the rules to be displayed in the table. If format is empty, will consider it to be tab delimited
		extract($head);
		$color = $_REQUEST['color'];
		$format = $_REQUEST['format'];
		
		#If verbs and objects are not sent in the array, it's easy to find them		
		if(is_array($rules)) {
			if($verbs=='') { $verbs = array_map('grab_verb', $rules); }
			if($objects=='') { $objects = array_map('grab_object', $rules, $verbs); }
		}
		$previous_verb ='';
        $current_color = 0;
        $matrix_header='';
		$parser = get_parser_characters($format);
		#First ROW, first and second COLS

		if($color=='on') {
			$matrix_header .= sprintf("%s\n", '<table width=100% border=1 style="border-collapse: collapse;">');
		} else {
			$matrix_header .= sprintf($parser['begin_table']);
		}
		$matrix_header .= sprintf($parser['tr']);
		$matrix_header .= sprintf("%s", $parser['td']);
		if($type!='template') {
			$matrix_header .= sprintf("%s", $class_info['entity'].$parser['middle']);
		}
		$matrix_header .= sprintf("%s", $class_info['entity'].$parser['end_td']);
		
		#Remaining COLS
		if(is_array($verbs)) {
			foreach($verbs as $i => $value) {
				$verb = $verbs[$i];
				##Print different verbs in different colors (for table view, mostly, yet for others there is no harm in this being here) 
				if($previous_verb =='') {
					$previous_verb = $verb;
				} elseif($previous_verb!=$verb) {
					$previous_verb = $verb;
					$current_color = $current_color + 1;
				}
				$my_color='';
				switch($current_color%3) {
					case 0:
						$my_color='red';
						break;
					case 1:
						$my_color='green';
						break;
					case 2:
						$my_color='blue';
						break;
				}
				if($format=='html' && $color=="on") {
					$colored = '<font color="'.$my_color.'">';$endcolored='</font>';
				}
				$matrix_header .=sprintf("%s", $parser['td'].$colored.$verb.$endcolored.$parser['end_td']);
			}
		}
		#Second ROW, first and second COLS
		$matrix_header .= sprintf("%s\n", $parser['end_tr']);
		if($color=='on') { 
			$matrix_header .= sprintf("%s\n", '	<tr bgcolor="lightblue">');
		} else {
			$matrix_header .= sprintf("%s", $parser['tr']);
		}
		$matrix_header .=sprintf($parser['td']);
		if($type!='template') {
			$matrix_header .=sprintf("UID".$parser['middle']);
		}
		$matrix_header .=sprintf("Notes".$parser['end_td']);
	
		#Second row, rest of COLS
		if (is_array($verbs)) {
			foreach($verbs as $i => $value) {
				$matrix_header .= sprintf("%s", $parser['td'].$objects[$i].$parser['end_td']);
			}
		}
		$matrix_header .= sprintf("%s", $parser['end_tr']);	
		return $matrix_header;
	}

	function render_datamatrix_values($vals) {
		$action =$GLOBALS['webaction'];
		extract($vals);
		$color = $_REQUEST['color'];
		$format = $_REQUEST['format'];
		$parser = get_parser_characters($format);
		$rows='';
		$items = $instances;
		
		#if($_REQUEST['num_per_page']!='' && $_REQUEST['current_page']!='') {
		#	$start = (($_REQUEST['current_page']-1)*$_REQUEST['num_per_page']);
		#	$end=($_REQUEST['num_per_page']*$_REQUEST['current_page']);
		#} else {
		#	$start = 0;
		#	$end= count($items);
		#}
		
		#if(is_array($matched_resource))
		for($i=$start; $i<$end; $i++) {
			$item_id = $items[$i]['item_id'];
			if(!is_array($items[$i]['stats'])) {
				$row ='';
				$s3ql = compact('db', 'user_id');
				$s3ql['select'] = '*';
				$s3ql['from'] = 'statements';
				$s3ql['where']['item_id'] =$item_id;
				$all_values = S3QLaction($s3ql);
			} else {
				$all_values = $items[$i]['stats'];	
			}
			if(is_array($all_values) && !empty($all_values)) {
				#replace values with filelinks and find the notes for the buttons
				$all_values = include_button_notes($all_values, $project_id, $db);
				$all_values = Values2Links($all_values);
			}
			if(count($all_values) =='0') {
				if($color == 'on') {		#This is to print each line in a different color - if line is multiple of 2, print it in AliceBlue
					if($total%2==0) {
						$row =sprintf("%s\n", '		<tr bgcolor="AliceBlue">');
					} else {
						$row =sprintf("%s\n", $parser['tr']);
					}
					#$all_values = include_button_notes($all_values, $project_id, $db);
					#In case color is on, it means we are trying to achieve one of the interactive, user-friendly interfaces. Therefore, resource should come in the format of a button. Need to be dealt with care in case 
					$resource_id_button = '<input type="button" size="10" value="'.str_pad($item_id, 6, '0', STR_PAD_LEFT).'" onClick="window.open(\''.$action['item'].'&item_id='.$item_id.'\', \'_blank\', \'width=700, height=600, location=no, titlebar=no, scrollbars=yes, resizable=yes\')">';
				} else {
					$resource_id_button = $item_id;
				}
				$subrow ='';
				$subrow .= sprintf("%s", $parser['td'].$resource_id_button.$parser['end_td']);
				$subrow .= sprintf("%s", $parser['td']. $items[$i]['notes'].$parser['end_td']);
                                
				#Moving on to the data on the rules, since there are no values on this row, print only opening and ending the cell
				if (is_array($rules)) {
					foreach($rules as $j=>$value) {
						$subrow .= sprintf("%s", $parser['td'].$parser['end_td']);
					}
				}
				$subrow .=sprintf("%s", $parser['end_tr']);
				$row .=$subrow;
				$total +=1;
			} else { 		#Where there are actual values on the row
				$n=(get_max_num_values($all_values, $rules)==0)?1:get_max_num_values($all_values, $rules);
				#$n will determine the number of lines for this item
				$total=0;
				$row =''; 
				for($m=0; $m<$n; $m++) {
					//$row ='';
					if($_REQUEST['color']=='on') {
						#if($total%2==0)
						#$row .=sprintf("%s\n", '<tr bgcolor="AliceBlue">');}
						#else
						#$row .=sprintf("%s\n", $parser['tr']);
						$resource_id_button = '<input type="button" size="10" value="'.str_pad($item_id, 6, '0', STR_PAD_LEFT).'" onClick="window.open(\''.$action['item'].'&item_id='.$item_id.'\', \'_blank\', \'width=700, height=600, location=no, titlebar=no, scrollbars=yes, resizable=yes\')">';
					} else {
						$resource_id_button =$item_id;	 
					}
					$row .=sprintf("%s", $parser['tr']);	

					$subrow ='';	
					$subrow .= sprintf("%s", $parser['td'].$resource_id_button.$parser['end_td']);
					$subrow .= sprintf("%s", $parser['td'].$items[$i]['notes'].$parser['end_td']);
					#if($item_id=='3783'){ echo $row ;  exit;}
					
					if(is_array($rules)) {
						foreach($rules as $j=>$value) {
							$rule_id = $rules[$j]['rule_id'];
							$values = get_value_by_rule($all_values, $rule_id);
							if($format=='html' && $color=='on') {
								$value = viewStatementValue($values[$m]);
							} else {
								$value = $values[$m]['value'];
							}
							if($value!='') {
								$subrow .= sprintf("%s", $parser['td'].$value.$parser['end_td']);
							} else {
								$subrow .= sprintf("%s", $parser['td'].$parser['end_td']);
							}
						}
					}
					$subrow .=sprintf($parser['end_tr']);
					$row .=$subrow;
				}
				$rows .= $row;	
			}
		}
		$rows .=sprintf($parser['end_table']);
		return $rows;
	}

	function get_max_num_values($all_values, $rules) {
		$num_values = array();
		if (is_array($rules)) {
			foreach($rules as $i => $value) {
				array_push($num_values, count(get_value_by_rule($all_values, $rules[$i]['rule_id'])));
			}
		}
		if (count($num_values)>=1) {
			return max($num_values);
		}
	}
	
	function get_value_by_rule($all_values, $rule_id) {
		$values = Array();
		if (is_array($all_values)) {
			foreach($all_values as $i=>$value) {
				if($all_values[$i]['rule_id'] == $rule_id) {
					array_push($values, $all_values[$i]);
				}
			}
		}
		return $values;
	}
?>