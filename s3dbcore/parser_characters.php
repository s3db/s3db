<?php

function get_parser_characters($format)

{
	if ($format=='tab') 
	{
	$middle = '	';
	$end_tr = '<BR>';

	}
	elseif($format=='html' || $format=='') 
	{
	$begin_table = '<TABLE>';
	$end_table = '</TABLE>';
	$tr = '<TR>';
	$end_tr = '</TR>';
	$td = '<TD>';
	$end_td = '</TD>';

	$middle = $end_td.$td;
	
	
	}
	elseif(ereg('html:', $format)) 
	{
	list($style, $choice) = explode(':', $format);
	$begin_table = '<TABLE class = "$choice">';
	$end_table = '</TABLE>';
	$tr = '<TR>';
	$end_tr = '</TR>';
	$td = '<TD>';
	$end_td = '</TD>';

	$middle = $end_td.$td;
	
	
	}
	
	return $parser_char = array ('middle'=> $middle, 'begin_table'=>$begin_table, 'tr'=>$tr, 'end_tr'=>$end_tr,'td'=>$td, 'end_td'=>$end_td, 'middle'=>$middle, 'end_table'=>$end_table);
}



?>