<?php
	$a = array(
			0=>array(7=>1),
			1=>array(1=>1),
			3=>array(5=>1),
			4=>array(8=>1),
			5=>array(7=>1),
			8=>array(3=>1)
		);
	$s = array(5=>array(0=>1));
	$r = multiplyByVector($a, $s);
	echo '<pre>';print_r($r);

	function multiplyByVector($matrix, $vector) {
		##Multiply a matrix by a vector; $matrix can be a sparse matrix; $m is the number of cols, $n is the number of rows
		$n = max(array_keys($matrix));
		foreach($matrix as $row=>$cols) {
			$multiply=array();
			foreach ($cols as $col=>$value) {
				##sum the result of the value in the col multiplied by the value in the vector on the corresponding row
				$multiply[] = $value*$vector[$col][0];
			}
			$r[$row] = array_sum($multiply);
			$tmp = array_keys($cols);
			$tmpM[] = max($tmp);
		}
		$m = max($tmpM);
	
		#if(max(array_keys($vector))!=$m)
		#	return ("Vector size is different from the number of columns in the matrix. Could not multiply.");
		#else
		return ($r);
	}
?>