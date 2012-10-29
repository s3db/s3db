<?php
	#rss_generator creates an index of files in the local deplooyment of s3db, which include all files (and subdirectories) 
	#and dates of midification and writes this to an RDF that will be made available on the mothership

	##Start the rdf library
	##
	ini_set('display_errors',0);
	if($_REQUEST['su3d']) {
		ini_set('display_errors',1);
	}
	include('rdfheader.inc.php');
	$model = ModelFactory::getDefaultModel();

	##
	#Read all the file from the current directory
	$cwd=getcwd();
	$rootname=dirname($cwd).'/'.basename($cwd);

	$model = buildDirModel($cwd, $model, $rootname);
	$model->saveAs("updates.rdf", "rdf");
	Header("Location: updates.rdf");
	exit;
	#$model->writeAsHtml(); 

	function buildDirModel($dir, $model, $rootname) {
		##Remove from $dir to output the part until s3db root;
		$dirFiles=scandir($dir);
		foreach ($dirFiles as $ind) {
			if(is_file($dir.'/'.$ind) && !ereg('^(s3id|config.inc.php|treeitem.*.js)', $ind)) {
				$fstat=lstat($dir.'/'.$ind);
				$lastModified = date('Y-m-d H:i:s', $fstat['mtime']);
				$path = str_replace($rootname,'',$dir);
				$path = ($path=='')?$ind:substr($path,1,strlen($path)).'/'.$ind;
				$subjResources = new Resource('http://www.s3db.org/central/s3dbfiles.php?file='.$path);
				$statement = new Statement($subjResources, new Resource('http://purl.org/dc/elements/1.1/date'), new Literal($lastModified));
				$path = new Statement($subjResources, new Resource('http://s3db.org/scripts'), new Literal($path));

				$model->add($statement); 
				$model->add($path); 
			} elseif(is_dir($dir.'/'.$ind) && !ereg('^(.|..|extras)$', $ind)) {
				$newDir = $dir.'/'.$ind;
				$submodel = ModelFactory::getDefaultModel();
				$submodel = buildDirModel($newDir, $submodel, $rootname);
				$model->addModel($submodel);
			}
		}
		return ($model);
	}
?>