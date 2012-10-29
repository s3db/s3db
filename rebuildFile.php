<?php
	#break file fits the purpose of breaking large rdf files into smaller ones such that they can be imported without the process being killed by php
	ini_set('display_errors',0);
	if($_REQUEST['su3d']) {
		ini_set('display_errors',1);
	}
	$file= ($_REQUEST['file']!='')?$_REQUEST['file']:$argv[2];
	if($file=='' || !is_file($file)) {
		echo "Please specify the rdf/n3 file to restore";
		exit;
	}
	rebuildFile($file);

	function rebuildFile($file) {
		#find out in how many fractions this file was broken
		$parts = file_get_contents($file.'_parts');
		echo "Rebuilding file from fragments".chr(10);
		for ($i=1; $i <= $parts; $i++) {
			$data .= file_get_contents($file.'_'.$i.'clean');
			$file2unlink = $file.'_'.$i.'clean';
			echo unlink($file2unlink);
			echo unlink($file.'_'.$i);
		}
		file_put_contents($file.'_clean', $data);
	}
?>

