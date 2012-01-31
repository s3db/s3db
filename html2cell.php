<?php
#html2cell is a function designed to parse html into arrays;


function html2cell($html)
{
	#parse what's inside the table
	if (eregi('<TABLE>(.*)</TABLE>', $html, $table_contents)) {
		#parse teh lines
		
		if (eregi('<TR>(.*)</TR>',$table_contents[1], $row_contents)) {
			
			
			
			#$row = explode('<TR>', $row_contents[0]);
			$row = spliti('<TR>', $row_contents[0]);
			
			$row = array_filter($row);#explode has this annoying habit of adding empty values
			
			
			
			foreach ($row as $rowi=>$a_row) {
			#remove </tr>
			
			$a_row = str_ireplace('</TR>', '', $a_row);
			
			
			#parse teh cells
			if (eregi('<TD>(.*)</TD>', $a_row, $cell_contents)) {
				
				
				$cells[$rowi] = spliti('<TD>', $cell_contents[1]);
				
				#remove emptyes
				$cells[$rowi] = array_filter($cells[$rowi]);

				
				#remove the /td
				$cells[$rowi] = array_filter($cells[$rowi]);
				
				foreach ($cells[$rowi] as $col=>$a_cell) {
					
					$a_cell = str_ireplace('</TD>', '', $a_cell);
					$cells[$rowi][$col] = $a_cell;
					if ($rowi>1) {
						$cells[$rowi][trim($cells[1][$col])] = $a_cell;
					}
				}
				
				
			}
			else {
				return ('No cells');
			}
			}
			
		}
		else {
				return ('No rows');
			}
	}
	else {
		'No html tables found';
	}

	
	return ($cells);
}

function html2array($html)
{
	#parse what's inside the table
	if (eregi('<TABLE>(.*)</TABLE>', $html, $table_contents)) {
		#parse teh lines
		
		if (eregi('<TR>(.*)</TR>',$table_contents[1], $row_contents)) {
			
			
			
			#$row = explode('<TR>', $row_contents[0]);
			$row = spliti('<TR>', $row_contents[0]);
			
			$row = array_filter($row);#explode has this annoying habit of adding empty values
			
			
			
			foreach ($row as $rowi=>$a_row) {
			#remove </tr>
			
			$a_row = str_ireplace('</TR>', '', $a_row);
			
			
			#parse teh cells
			if (eregi('<TD>(.*)</TD>', $a_row, $cell_contents)) {
				
				
				$cells[$rowi] = spliti('<TD>', $cell_contents[1]);
				
				#remove emptyes
				$cells[$rowi] = array_filter($cells[$rowi]);

				
				#remove the /td
				$cells[$rowi] = array_filter($cells[$rowi]);
				
				foreach ($cells[$rowi] as $col=>$a_cell) {
					
					$a_cell = str_ireplace('</TD>', '', $a_cell);
					$cells[$rowi][$col] = $a_cell;
					if ($rowi>1) {
						$cells[$rowi][trim($cells[1][$col])] = $a_cell;
					}
				}
				
				
			}
			else {
				return ('No cells');
			}
			}
			
		}
		else {
				return ('No rows');
			}
	}
	else {
		'No html tables found';
	}

	
	return ($cells[2]);
}

?>