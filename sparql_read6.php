<?php
/**
	
	* @author Helena F Deus <helenadeus@gmail.com>
	* @license http://www.gnu.org/copyleft/gpl.html GNU General Public License
	* @package S3DB http://www.s3db.org
*/
########################################################
##Debug box
#echo '<pre>';print_r($outputData);exit;
#$timer ->display();
#$timer->stop();$profiling = $timer->getProfiling(); 
#echo $profiling[count($profiling)-1]['total'].' sec';exit;
########################################################
	

function sparql($I)
{  
	
	##Parse the query and build the dataset
	#global $timer;
	if(is_file(S3DB_SERVER_ROOT.'/pearlib/Benchmark/Timer.php')){
	require_once S3DB_SERVER_ROOT.'/pearlib/Benchmark/Timer.php';
	$timer = new Benchmark_Timer();
	$timer->start();
	}

	extract($I);

	##To use SPARQL with ARC library, we will need it to work with a remote endpoint. That means that we do not want to configure ARC as a datastore, but rather to retrieve the data from s3db deployments, convert it to RDF and then use ARC to run the query on it
	/* ARC2 static class inclusion */ 
	ini_set("include_path", S3DB_SERVER_ROOT."/pearlib/arc". PATH_SEPARATOR. ini_get("include_path"));
	include_once("ARC2.php");

	$s3ql['url'] = ($in['url']!='')?$in['url']:$default_uri;
	$s3ql['key'] = ($in['key']!='')?$in['key']:get_user_key($user_id, $db);
	$q = $in['query'];
	 
	list($query, $triples, $prefixes) = parse_sparql_query($q, $s3ql); 
	
	#Altered 4/23/2010
	#from this moment on, we need first to SELECT even if that is not the query form; the query form will matter when we finally return the data
	$query_form = ($query['select']!='')?'select':(($query['ask']!='')?'ask': (($query['describe']!='')?'describe':(($query['construct']!='')?'construct':'??')));
	if($query_form=='describe'){
		$format = 'n3';
	}
	
	$bq .= "PREFIX ".implode("\n PREFIX ", $query['prefix'])."\n ";
	$bq .= "SELECT ".$query[$query_form][0]."\n ";
	$bq .= "FROM".implode(" FROM ", $query['from'])."\n ";
	$bq .= "WHERE ".$query['where'][0]."\n ";

	/*
	$bq .= "PREFIX ".implode("\n PREFIX ", $query['prefix'])."\n ";
	$bq .= "SELECT ".$query['select'][0]."\n ";
	$bq .= "FROM".implode(" FROM ", $query['from'])."\n ";
	$bq .= "WHERE ".$query['where'][0]."\n ";
	*/
	preg_match_all('(\?[A-Za-z0-9_]+) ', $bq, $vars);
	if($vars[0]) {
	$vars = array_unique($vars[0]);
	$sparql_vars = implode(" ",$vars);
	}
	if($query['select'][0]!="" && $query['select'][0]!="*"){
		$outputCols = explode(" ", trim($query['select'][0]));
		$outputCols = array_filter($outputCols);
		$outputCols = array_intersect($vars, $outputCols);
	}
	
	$sparql=ereg_replace("FROM(.*)WHERE", "WHERE",$bq);
	
	#Validate the query first
	include_once(RDFAPI_INCLUDE_DIR."sparql/SparqlParser.php");
	
	try {
		$parser = new SparqlParser();
		$parsed = $parser->parse($sparql);		
	} 
	catch (Exception $e) {
		echo formatReturn('1', 'Parse error: '.$e->getMessage(), $_REQUEST['format'],'');
		exit;
	}

	#lets preprocess the order by which the must be queries must be performed to optimize speedness
	$filename = S3DB_SERVER_ROOT.'/tmp/'.md5($sparql.$user_id);
	
	if($clean && is_file($filename)){
	unlink($filename);
	}
	
	if(!is_file($filename)) {
	
	list($iterations, $scrambled) = iterationOrder($triples,$prefixes, true);
	
	##$rdf_results will contain the totality of triples retrieved from s3db;
	##Start a rdf-api model
	$iterations = array_values($iterations);
	
	
	$rdf = S3DB_URI_BASE.'/s3dbcore/model.n3';#base s3db rdf model
	$rdffilename = md5($rdf);
	$file_place = $GLOBALS['uploads'].'/';
	
	#$queryModel = rdf2php($rdf);
	#$data = $queryModel->sparqlQuery($sparql);
	#echo '<pre>';print_r($data);exit;
	if($timer) $timer->setMarker('Core model read into results');
	
   	
	$rdf_results = array();
	
	# add the dictionary data 
		if($complete){

			$dicfile = S3DB_SERVER_ROOT.'/tmp/'.md5('query_dictionary'); 
			if($clean && is_file($dicfile) && $_REQUEST['clean']=='dic'){
				unlink($dicfile);
			}
			
			if(!is_file($dicfile)){
				include_once(S3DB_SERVER_ROOT.'/s3dbcore/dictionary.php');
				
				$s3qlN=compact('user_id','db');
				$s3qlN['from']='link';
				$s3qlN['format'] = 'php';
				
				$links = query_user_dictionaries($s3qlN,$db,$user_id);
				file_put_contents($dicfile, $links);
				$links = unserialize($links);
				
			}
			else {
				$links = unserialize(file_get_contents($dicfile));
			}
								
			$rdf_results['E'][0] = $links;

			$nsfile =  S3DB_SERVER_ROOT.'/tmp/'.md5('query_ns'); 
			if($clean && is_file($nsfile) && $_REQUEST['clean']=='ns'){
				unlink($nsfile);
			}
			if(!is_file($nsfile)){
				include_once(S3DB_SERVER_ROOT.'/s3dbcore/dictionary.php');
				
				$s3qlN=compact('user_id','db');
				$s3qlN['from']='namespaces';
				$s3qlN['format'] = 'php';
				$ns = query_user_dictionaries($s3qlN,$db,$user_id);
				file_put_contents($nsfile, $ns);
				$ns = unserialize($ns);
				
			}
			else {
				
				$ns = unserialize(file_get_contents($nsfile));
			}



			if($timer) $timer->setMarker('Dictionary links retrieved');
			##Because dictionary queries are too generic, variables need to be bound before writting S3QL
			$tmp_triples = rdf_encode($links,"E", 'array',  $s3ql['db'],$ns);
			$file=make_rdf_model($tmp_triples);
			$queryModel = rdf2php($file);
			#Query needs to be parcial, triple by triple...
			foreach ($triples as $g=>$gPat) {
			$tmp  = explode(" ",$gPat);
			foreach ($tmp as $t) { 
				
				if(ereg("http", $t)) { $t = "<".$t.">";}
				$tgPat .= $t." ";
			}
			$part .= "PREFIX ".implode("\n PREFIX ", $query['prefix'])."\n ";
			$part .= "SELECT ".$query['select'][0]."\n ";
			$part .= "FROM".implode(" FROM ", $query['from'])."\n ";
			$part .= "WHERE {".$tgPat." . }";

			$dic_bound_data = $queryModel->sparqlQuery($part);


			if($dic_bound_data){
				foreach ($dic_bound_data as $l=>$varsvals) {

					if($vars){
						foreach ($vars as $v) {
							if($varsvals[$v]->uri){
								
								if(!is_array($discovered[$v]) || !in_array($varsvals[$v]->uri, $discovered[$v]))
								$discovered[$v][] = $varsvals[$v]->uri;
							}
							elseif ($varsvals[$v]->literal) {
								if(!is_array($discovered[$v]) || !in_array($varsvals[$v]->literal, $discovered[$v]))
								$discovered[$v][] = $varsvals[$v]->literal;
							}
						}
					}
					
				}
			}

			}

		}
	
	$performedQueries = array();
	$r=0;
	foreach ($iterations as $it=>$triples2query) {
		$S3QL=array();
		$S3QLfinal = array();
		$queried_elements = array();
		$lQueries = array();
		$rQueries = array();
		foreach ($triples2query as $i=>$tripleInd)	{
			
			$tripleString = $tripleInd;
			
			$tmp = explode(" ", trim($tripleString));
			$subject =$tmp[0];
			
			//once the subjec is known, predicate may be found
			ereg("^\\".$subject.'(.*)', trim($tripleString), $tmp1);
			$tmp2 = explode(" ", trim($tmp1[1]));
			$predicate = $tmp2[0];
			
			//With known subject and predicate, object will be the string with that portion truncated
			ereg("^\\".$predicate.'(.*)', trim($tmp1[1]), $tmp3);
			$object = trim($tmp3[1]);
			
			#list($subject, $predicate) = explode(' ',trim($tripleString));
			#$object = trim(str_replace(array($subject, $predicate), array("", ""), $tripleString));
			//Method 1 to breaking the triple
			/*$tmp = explode(' ',trim($tripleString));
			$subject = $tmp[0];
			$predicate = $tmp[1];
			if(count($tmp)>3){
				for ($t=2; $t <count($tmp) ; $t++) {
					if($object!='') $object .= " ";
					$object .=  $tmp[$t];
				}
			}
			*/
			$subject = ereg_replace('^<|>$','',$subject);
			$predicate = ereg_replace('^<|>$','',$predicate);
			$object = ereg_replace('^<|>$','',$object);
			$triple = compact('subject','predicate','object');
		   
			#sparql triple is used to calculate the values of the variables in the triple
			#$sparql_triple = $sparql_prefixes_default.' SELECT * WHERE { '.ltrim($tripleString).' . }';
			
			#now lets interpret the triple to explore the space of possible queries on S3QL
			$pack = compact('triple', 's3ql','user_id', 'db','prefixes','varType','discoveredData','discovered','it','varTypeWhere','collected_data','performedQueries');
			
			

			$sp = sparql_navigator($pack);
			extract($sp);
			# if($timer) $timer->setMarker('Built query '.$i);
			
			##Remove queries that were already performed
			
			 if($S3QL[0]){
			 foreach ($S3QL as $s=>$q) {
				if(!in_array($q, $S3QLfinal)){
				$S3QLfinal[] =$q;
				$queried_elements[] = $element[$s];
				$lQueries[] = $localQueries[$s];
				$rQueries[] =  $remoteQueries[$s];
				}
			 }
			 #$S3QLfinal[] =$S3QL[0];
			 #$queried_elements[] = $element;
			 #$localQueries[$tripleString] = $localQueries[0];
			 #$remoteQueries[$tripleString] = $remoteQueries[0];
			 #$localQueries = array_filter($localQueries);
			 #$remoteQueries = array_filter($remoteQueries);
			 }
		}
			$S3QL = $S3QLfinal;
			$localQueries = $lQueries;
			$remoteQueries =  $rQueries;
			
			
			##Remove repeated queries
			#$S3QL=array_unique($S3QL);
			#$S3QL = array_values($S3QL);
			
			#if only the s3ql is requested, we can return it now
			if($in['output']=='S3QL')
				{
				foreach ($localQueries as $sparqlVersion=>$s3qlVersion) {
				$Q[]['S3QL'] = S3QLQuery($s3qlVersion);	
				}
				foreach ($remoteQueries as $rq) {
				$Q[]['S3QL'] = $rq;	
				}

				$root = 's3ql';#root is just the word that xml should parse as the root for each entry
				$data = $Q;
				$cols = array('S3QL');
				$format = ($in['format']=='')?'html':$in['format'];
				$z = compact('data','cols','format','root');
				
				$out=outputFormat($z);
				return array(true,$out);
				
				}
			
			
			#If paralel library is activated, use it for the data. Otherwise use the custom version
			#$query_answers_file = 'sparql_query_ans'.rand(100,200);	$a=fopen($query_answers_file, 'a');
			
			if(!empty($S3QL)){
			if(extension_loaded ('curl') && $goparallel){
				// Create cURL handlers
				if($timer) $timer->setMarker('Starting queries from group '.$it);
				
				foreach ($S3QL as $k=>$url) {
					$qURL = $url;
					
					$ch[$k] = curl_init();
					// Set options 
					curl_setopt($ch[$k], CURLOPT_URL, $qURL.'&format=php');
					curl_setopt($ch[$k], CURLOPT_RETURNTRANSFER, 1);
				}
				$mh = curl_multi_init();
				foreach ($S3QL as $k=>$url) {
					curl_multi_add_handle($mh,$ch[$k]);
				}

				$running=null;
				do {
					curl_multi_exec($mh,$running);
					if($timer) $timer->setMarker('Query '.$k.' of group '.$it.' executed');
				
				} while ($running > 0);

				foreach ($S3QL as $k=>$url) {
				$answer[$k]  = curl_multi_getcontent($ch[$k]);
				
				if(!empty($answer[$k]))
					{
					#@fwrite($a, $answer[$k]);
					
					##This is what takes the longest after the query, can it be replaced?
					$ans = unserialize($answer[$k]);
					#$letter =  $queried_elements[$r][0];
					$letter =  $queried_elements[$k];
					
					if(empty($ans)){
					##is this query part is not optional, then the result will be null
					##TO BE DEVELOPED SOON
					}
					else {
					$rdf_results[$letter][] = $ans;		
					}
					$r++;		
					

					##Add the triples to already existing triples
					#Line up the answer with the model
					
					
					
					if($timer) $timer->setMarker('Query '.$it.'=>'.$k.' converted to php ');
					
					
									
					}
				}
				
				curl_multi_close($mh);
				
				####Time count
				#$time_end = microtime(true);
				#$time = $time_end - $time_start;
				#echo "Query took ".$time." seconds\n";exit;
				###
				
			
			}
			else 
			{

				#Now solve the remaining triples with the constants found in this one

				if(is_array($localQueries) && !empty($localQueries)) {
					
					foreach ($localQueries as $s=>$locals3ql) {
						$locals3ql = array_filter(array_diff_key($locals3ql,array('url'=>'')));
						$tmpqueryfile = S3DB_SERVER_ROOT.'/tmp/'.md5(S3QLQuery($locals3ql));
						
						##Has this query been performed?
						if($clean=='2'  && is_file($tmpqueryfile)){
							unlink($tmpqueryfile);
						}
						#If query results are stored in cache, use them!
						if (is_file($tmpqueryfile)) {
							
							$answer = unserialize(file_get_contents($tmpqueryfile));
						}
						else {
							
							$answer = S3QLAction($locals3ql);
							file_put_contents($tmpqueryfile, serialize($answer));
						}
						
						//$answer = S3QLAction($locals3ql);
						
						if(!empty($answer))
							{
							$letter =  letter($locals3ql['from']);
							$rdf_results[$letter][] = $answer;		
							}
					}
				}
				if(is_array($remoteQueries) && !empty($remoteQueries[0])) {
					$k=0;
					foreach ($remoteQueries as $remoteQuery) {
						$answer = remoteQ($remoteQuery);
						if(!empty($answer))
							{
							$letter = $queried_elements[$k];
							$rdf_results[$letter][] = $answer;
							$k++;
							#$rdfanswer = rdf2php($answer);
							#Line up the answer with the model
							#$queryModel->addModel($rdfanswer);
							
							#Now perform the query on the small model to find a constant for the remaining queries
							#list($data,$discovered, $discoveredData,$queryModel) = executeQuery($queryModel,$sparql_triple,$discovered,$format);
							}
					}
				}

			}
			}
			
		}

	##Get the data from the file
	
	
	##Convert the result into an RDF file
		$data_triples = array();
		if(is_array($rdf_results)){
			foreach ($rdf_results as $letter=>$results2rdfize) {
				$dont_skip_core_name = false;
				$dont_skip_serialized=true;
				if(ereg('S', $letter)) $dont_skip_serialized=false;
				if(ereg('C|R|P', $letter)) $dont_skip_core_name = true;

				foreach ($results2rdfize as $k=>$data) {
					
					$tmp_triples = rdf_encode($data,$letter, 'array',  $s3ql['db'],$ns,$collected_data,$dont_skip_serialized,$dont_skip_core_name);
					
					
					if(is_array($tmp_triples))
					$data_triples=array_merge($data_triples, $tmp_triples);
					
				
				}
			}
		}
		
		
		 if(is_array($outputCols) && !empty($outputCols)){
					##only this one are to be shown in the final result
					$vars = $outputCols;
				}
				$cleanCols = array();
				
				foreach ($vars as $varname) {
					
					$cleanCols[] = ereg_replace('^\?','', $varname);
				}
				
		
		if(empty($data_triples)){
		##still return the headers 
		$cols = $cleanCols;
		$format = ($_REQUEST['format']!="")?$_REQUEST['format']:'html';
		$data = array();
		$z = compact('data','cols','format','root');
		$out=outputFormat($z);
		return (array(1,$out));#return true because query was valid... just did not return any results
		
		}
		#echo $filename;exit;
		$tmp['ns'] = $prefixes;
			/*
			#this one for turtle
			$parser = ARC2::getComponent('TurtleParser', $a);
			$index = ARC2::getSimpleIndex($triples, false) ; # false -> non-flat version 
			$rdf_doc = $parser->toTurtle($index,$prefixes);
			*/
			$parser = ARC2::getComponent('RDFXMLParser');
			$index = ARC2::getSimpleIndex($data_triples, false) ; /* false -> non-flat version */
			$rdf_doc = $parser->toRDFXML($index);
			#$filename = S3DB_SERVER_ROOT.'/tmp/'.md5($sparql.date('d'));
			$rr= fopen($filename, 'a+');
			fwrite($rr, $rdf_doc);
			fclose($rr);
			if($timer) $timer->setMarker(count($data_triples).' triples written to file '.$filename);
			
			##The better strategy would be to let the client cpu resolve the query; return the graphs with the rdf so that a sparql on the client can handle it
		}
		
		
		if($_REQUEST['filename']){
			$url2search = str_replace(S3DB_SERVER_ROOT, S3DB_URI_BASE, $filename);
			if(filesize($filename)>0){
			return (array(true,$url2search));
			}
			else {
			return (array(false));	
			}
		exit;

		}
		
		
		if($redirect){
			##And now use an external service ( I gave up with ARC) to parse the query
			$url2search = str_replace(S3DB_SERVER_ROOT, S3DB_URI_BASE, $filename);
			$bq = stripslashes($bq);
			
			##Giving up on ARC, surrender to sparql.com
			$remote_endpoint = "http://sparql.org/sparql?query=";
			
			$bq=ereg_replace("FROM <.*>", "FROM <".$url2search.">", $bq);
			#$sparql=ereg_replace("FROM <.*>", "FROM <".$url2search.">", $sparql);
			
			$bq = urlencode($bq);
			$remote_endpoint .= $bq.'&default-graph-uri=&stylesheet=/xml-to-html.xsl';
			
			#$remote_endpoint .= $sparql.'&default-graph-uri=&stylesheet=/xml-to-html.xsl';
			
			
			return (array(true, $remote_endpoint));
		}
		
		
		
		#And finally perform the query on the model.
		$queryModel = rdf2php($filename);
		$format = ($in['format']!='')?$in['format']:'html';
		
		if($timer) $timer->setMarker('Data converted to a model the rdf-api can query');
		
		if(eregi('^(sparql-xml|sparql-html)$', $format)){
			switch ($format) {
				case 'sparql-xml':
					 $result = $queryModel->sparqlQuery($sparql, 'XML');
				
				break;
				case 'sparql-html': 
					
					$result = $queryModel->sparqlQuery($sparql, 'HTML');
					
					if($_REQUEST['su3d']){
					$timer->stop();$profiling = $timer->getProfiling(); 
					echo "Query took ".$profiling[count($profiling)-1]['total'].' sec';
					}
				break;
		}
		if($result){
		return array(true,$result);
		}
		else {
			return (false);
		}

		}
		elseif($format=='html.form'){
				$form .= '
				<html>
				<head>

				</head><body>
				<form method="GET" action="sparql.php" id="sparqlform">
				<h5>Target Deployment(s)</h5>
				<input type="hidden" name="key" value="'.$s3ql['key'].'"/>
				<input type="hidden" name="format" value="'.$_REQUEST['format'].'"/>
				<input type = "text" id="url" size = "100%" value="'.$GLOBALS['url'].'" name="url">
				<h5>SPARQL  <a href="http://www.w3.org/TR/rdf-sparql-query/" target="_blank">(help!!)</a></h5>
				<br />

				<textarea cols="100" id="sparql" rows="10" name = "query">'.stripslashes($sparql).'</textarea><br />
				<input type="submit" value="SPARQL this!" id="submitsparql"></body>
				</form>
				';
				$form .= '<br />'.count($data)." rows";
				$form .= '<br />Query took '.(strtotime(date('His'))-$start).' sec';
				if(count($data)>0){
				return (array(true, $form));
				}
				else {
					return (array(false));
				}
		
		}
		else {
			
			#and output the result according to requested format
	
				$queryResultCache = S3DB_SERVER_ROOT.'/tmp/'.md5($sparql.$user_id.'result');
				if($clean && is_file($queryResultCache)){
					unlink($queryResultCache);
				}
				
				
				$sparql = stripslashes($sparql);
				
				if(!is_file($queryResultCache))	{
					
					$data = $queryModel->sparqlQuery($sparql);
					file_put_contents($queryResultCache, serialize($data));
					chmod($queryResultCache, 0777);
					if($timer) $timer->setMarker('Query on SPARQL data executed by rdf-api.');
				}
				else {
					$data = unserialize(file_get_contents($queryResultCache));
				}
				
				if(is_array($outputCols) && !empty($outputCols)){
					##only this one are to be shown in the final result
					$vars = $outputCols;
				}
				$cleanCols = array();
				
				foreach ($vars as $varname) {	
					$cleanCols[] = ereg_replace('^\?','', $varname);
				}
				
				
				$outputData = array();
				if(is_array($data))
				foreach ($data as $s=>$sparql_line) {
					foreach ($sparql_line as $sparql_var=>$sparql_var_value) {
							
						if($sparql_var_value->uri!=''){
							
							$outputData[$s][ereg_replace('^\?','', $sparql_var)] = $sparql_var_value->uri;
							

						}
						elseif($sparql_var_value->label!='') {
							$outputData[$s][ereg_replace('^\?','', $sparql_var)] = $sparql_var_value->label;
						}
						else {
							 $outputData[$s][ereg_replace('^\?','', $sparql_var)] = "";
						}
					}
				}
			  
			 if($timer) $timer->setMarker('Data converted in a format that fun outputformat can read');
			#$timer ->display();
			
			
			#root is just the word that xml should parse as the root for each entry
			$root = 'sparql';
			
			if($timer) $timer->setMarker('All variables fitted into their places to represent in the final output');
			$data = $outputData;
			$cols = $cleanCols;
			
			
			if($_REQUEST['su3d']){
			if($timer) {$timer->stop();$profiling = $timer->getProfiling();} 
			echo "Query took ".$profiling[count($profiling)-1]['total'].' sec<br>';
			}
			
			$z = compact('data','cols','format','root');
			$out=outputFormat($z);
			#if($_REQUEST['su3d']){
			# echo $out;exit;
			#}
			
			#if(count($data)>0){
			return (array(true, $out));
			#}
			#else {
			#	return (array(false));
			#}
			
			}
		
		
		
		#else {
		#$out= formatReturn($GLOBALS['error_codes']['no_results'], 'Your query did not return any results.', $format,'');
		#}

		
}


function isSPARQLVar($e){
	if($e->uri!='')
		return (False);
	elseif($e->label!='')
		return (False);
	elseif (ereg('^\?', $e)) {
	return (true);	
	}
	else {
		return (false);
	}
}

function isS3DBCore($e, $call=false,$format='rdf')
{

##First slip into url + finalization; check if url is s3db's
	if(!ereg('^(http.*)/(D|G|U|P|C|R|I|S)([0-9]+)$', $e, $uri_out))
			return (False);
		else {
			
			$s3dbquery = $uri_out[1].'/URI.php?format='.$format.'&uid='.$uri_out[2].$uri_out[3].'&key='.$GLOBALS['key'];
			
			if($call){
			$uri_dat = stream_get_contents(fopen($s3dbquery,'r'));
			
			if($format=='rdf')
				{$model[$e] = rdf2php($uri_dat);  }
			else {
				if($format=='php')
				{	
					$model[$e] = unserialize($uri_dat);
					
				}
			}
			}
			
			#$msg=html2cell($uri_dat);$msg = $msg[2];
			#if(is_array($model[$e]->triples))
			#{
			#echo $uri_out[2]; echo '<pre>';print_r($GLOBALS['s3dbCore'][$uri_out[2]]);
			#$next=(!is_array($GLOBALS['s3dbCore'][$uri_out[2]]))?0:count($GLOBALS['s3dbCore'][$uri_out[2]]+1);
			#$GLOBALS['s3dbQueries'][count($GLOBALS['s3dbQueries'])+1] = $s3dbquery;
			#$GLOBALS['s3dbCore'][$uri_out[2]][$next] = $uri_out[2].$uri_out[3];
			#$GLOBALS['s3dbURI'][count($GLOBALS['s3dbURI'])+1]=$uri_out[2].$uri_out[3];
			return (array('query'=>$s3dbquery, 'url'=>$uri_out[1],'letter'=>$uri_out[2], 'value'=>$uri_out[3], 'data'=>$model[$e]));
			}
			#else {
			#	return (False);
			#}
			
		#}
}

function switchFromCore($E)
{
	return ($GLOBALS['s3codes'][$E]);
}

function switchToCore($E)
{
	return ($GLOBALS['s3codesInv'][$E]);
}

function sparql_navigator($c)
	{global $timer;
	extract($c);
	
	
	##
	#React to the triples individually.
	#
	
	$crew = array('subject','predicate','object');
	#
	#no answer just yet 
	#
	$ans=array();
	$triple_vars = array();
	$q='';
	
	

	$fromSpace = array_map('switchToCore', array_keys($GLOBALS['COREids']));
	$whereSpace = array_combine(array('D','G','U','P','C','R','I','S'), $GLOBALS['queriable']);

	$selectSpace = $GLOBALS['queriable'];
	
	#
	#first we'll try to answer the question with the captain himself - the subject has the most chances of winning the game; the subject can answer the question totally or partially. In case it is partially, predicate and object will complete it. 
	#
	
	$from = $fromSpace;
	
	foreach ($crew as $crew_member) {
	##if any of the triples is just 'a', replace by rdf:type
	if($triple[$crew_member]=='a'){
			$triple[$crew_member] = 'http://www.w3.org/1999/02/22-rdf-syntax-ns#type';
	}

	$isCore=false;
	$isCore =isS3DBCore($triple[$crew_member],true,'php');
	
	if($isCore){
		$collected_data[$isCore['letter'].$isCore['value']] = $isCore['data']; ##save it for later
	}
	
	switch ($crew_member) {
			
		case 'subject':
				#
				#subject can tells us for sure where the query should target; relationships associated with this core member					can be retrieved. 
				#
				if($isCore)
				{
					#
					#Because the core allows only collections and items as subjects
					#
					list($from, $where) = trimBasedOnSub(compact('from','isCore','where','triple','fromSpace'));
					
					##Where cannot be determined yet, but we can constrain the where space
					$whereSpace = array_intersect_key($whereSpace, array_flip(array_map('switchToCore',$from)));
					
				}
				elseif(isSPARQLVar($triple[$crew_member]))#is subj var?
				{
	
					#If ?var was not found already, assign it to empty vars
					array_push($triple_vars, $triple[$crew_member]);

					##Build the from and the where; if these were already performed, they will not be performed again
					#11TCGA
					if($varType[$triple[$crew_member]]!='')
					{$from = array_intersect($from, $varType[$triple[$crew_member]]);
						if($varTypeWhere[$triple[$crew_member]]!=''){
						$where[letter($varType[$triple[$crew_member]])] =  $varTypeWhere[$triple[$crew_member]];
						}
					
					}

					
					

				}
				
				elseif($triple[$crew_member]) {
					if (isCoreOntology($triple[$crew_member])) {
					#The query is to be oriented towards the core. Since the core is already part of the model.n3, we need						to leave the form and where empty. Model reamians as was an query is porformed on top of it.
					$from = array();
					$where = array();

					}	
				}
				else {
					#the only time subject is not in the core is if the rdf comes from external sources. These will be left						to the sparql enggine
				}
				
				##P and O can be used to trim the answer if they are constants; otherwise they can be dicovered
				if($timer) $timer->setMarker('subject '.$triple[$crew_member].' interpreted');
				break;
		case 'predicate':	
			#Which in the core? Predicate can now choose between rule or item, otherwise it does not make sense within the core
				
				
				if($isCore)
				{
				
				list($from, $where, $varType, $varTypeWhere) = trimBasedOnPred(compact('isCore','where', 'triple','varType','varTypeWhere'));
				
				}
				
				elseif(isSPARQLVar($triple[$crew_member]))#is pred var?
				{
					array_push($triple_vars, $triple[$crew_member]);
				}
				elseif(ereg('^http',$triple[$crew_member])) {
					 
					#When the predicate is a known property, "from" can be trimmed to involve those that do have that property.
					#try to translate which property if being requested via uri:
					$tmp = $triple[$crew_member];
					
					if ($tmp=='http://www.w3.org/1999/02/22-rdf-syntax-ns#type') {##When predicate is "type" something, query can be either on core or on a particular collections
							  
								$objectIsCore =isS3DBCore($triple['object']);
								
								if($objectIsCore)
								switch ($objectIsCore['letter']) {
									case 'C':
										$from = array_intersect($from, array('I'));
										##Where will be resolved when we look at the object
										#if(!is_array($where['I'])) $where['I'] = array();
										#array_push($where['I'], array('collection_id'=>$objectIsCore['value']));
										$varType[$triple['subject']][] = 'I';
										$varTypeWhere[$triple['subject']][] = 'C'.$objectIsCore['value'];
										break;
									case 'P':
										$varType[$triple['subject']][] = 'P';
										$varTypeWhere[$triple['subject']][] = 'P'.$objectIsCore['value'];
										break;

									
								}

								$objectIsEntity =isCoreOntology($triple['object']);
								#10TCGA
								if($objectIsEntity){
									$varType[$triple['subject']][] = letter($objectIsEntity);	
								}
								##Added 131109
								#Object has been discovered?
								$objectDiscovered = WasDiscovered($triple['object'],$discovered);
								
								if($objectDiscovered){
									##Find the possibilities
									foreach ($objectDiscovered as $pObj) {
										$objectIsDiscoveredCore =isS3DBCore($pObj);
										switch ($objectIsDiscoveredCore['letter']) {
											case 'C':
												$from = array_intersect($from, array('I'));
												if(!$where['I']){
													$where['I'] = array();
												}
												#$varType[$triple['subject']][] = 'I';
												#$varTypeWhere[$objectIsDiscoveredCore['letter']][] = 'C'.$objectIsDiscoveredCore['value'];
												$where['I'][] = array('collection_id'=>$objectIsDiscoveredCore['value']);

											break;
										}
									}
								}
								
								
						}
					#elseif($tmp==rdfs.'subClassOf'){
					elseif($tmp=='http://www.w3.org/2000/01/rdf-schema#subClassOf'){
					  $from = array_intersect($from, array('C','R','P','U','G'));
					  $objectIsCore =isS3DBCore($triple['object']);
					  $objectDiscovered = WasDiscovered($triple['object'],$varType);
					  $subjectType = WasDiscovered($triple['subject'],$varType);
						
							if($objectIsCore)
								{switch ($objectIsCore['letter']) {
									case 'P':
										$from = array_intersect($from, array('C','R'));
										$varTypeWhere[$triple['subject']][] = 'P'.$objectIsCore['value'];
										#if(!is_array($where['I'])) $where['I'] = array();
										#array_push($where['I'], array('collection_id'=>$objectIsCore['value']));
										
										break;
									case 'D':
										$from = array_intersect($from, array('P','U', 'G'));
									break;
									case 'G':
										$from = array_intersect($from, array('U'));
									break;

								}
								  }
							elseif($objectType) {
							 							
								foreach ($objectType as $gold) {
									 $isObjectCore = isS3DBCore($gold);
										if($isObjectCore){
										list($from, $where) = trimBasedOnObj(array('from'=>$from,'isCore'=>$isObjectCore,'where'=>$where));
										}
										

									 
								}

							}
							

						
					}
					#elseif($tmp==rdfs.'label' || $tmp==rdfs.'comment'){
					elseif(in_array($tmp, $GLOBALS['not_uid_specific'])){
						 

						#is subject found?
					  
						$subjDiscovered = WasDiscovered($triple['subject'],$varType, $varTypeWhere);
						
						#how about object?
						$objDiscovered = WasDiscovered($triple['object'],$varType,$varTypeWhere);
						
						#$subjDataDiscovered = $discoveredData[$triple['subject']]; 
							if($subjDiscovered)
							{
							
							
							#echo 'ola';exit;	
								#$from = array();
								
								$where = array();
								foreach ($subjDiscovered as $g=>$gold) {
									
									 $isSubjectCore = isS3DBCore($gold);
									
									 if($isSubjectCore){
										list($from, $where) = trimBasedOnSub(array('fromSpace'=>$fromSpace,'from'=>$from,'isCore'=>$isSubjectCore,'where'=>$where));
									}
									elseif(in_array($gold, array('P','C','R','I','S') ))
									{
									$from = array_intersect($from, array($gold));
										if($varTypeWhere[$triple['subject']][$g]){
											if(!is_array($where[$gold])) $where[$gold] = array();
											$eid = $GLOBALS['COREletterInv'][letter($varTypeWhere[$triple['subject']][$g])];
											array_push($where[$gold], array($eid=>substr($varTypeWhere[$triple['subject']][$g], 1,strlen($varTypeWhere[$triple['subject']][$g]))));
											
										}
									
									
									 
									}
								}
							
							#echo '<pre>';print_r($from);
							#echo '<pre>';print_r($where);
							#exit;
							}
							
							#echo '<pre>';print_r($from);echo '<pre>';print_r($where);exit;
						}
					else 
						{
												

						foreach ($from as $E) {
							
							if(in_array($tmp, $GLOBALS['propertyURI'][$E]))
							{
								$fromSubSpace[] = $E;
								
								#
								#The object's help here will matter, as it will encapsulate the value to be read into the query
								#
								
								$objectIsCore =isS3DBCore($triple['object']);

								if(!is_array($where[$E]))  $where[$E] = array();
								
								if($objectIsCore) {
									array_push($where[$E], array(array_search($tmp,$GLOBALS['propertyURI'][$E]) => $objectIsCore['value']));
								}
								elseif($triple['object']) {
									array_push($where[$E], array(array_search($tmp,$GLOBALS['propertyURI'][$E]) => $triple['object'])); 
								}
							}
							
							

						#$from = array_intersect($from,$fromSubSpace); 
						}
						#Added 111109
						if(is_array($from) && is_array($fromSubSpace))
						$from = array_intersect($from,$fromSubSpace); 
						
						if(count($from)==8 || empty($where)) ##all entities will be queried, not a good move; this can be a query for the dictionary
							{$from=array();
							
							}
						}
					
					#echo '<pre>';print_r($from);
					#echo '<pre>';print_r($where);exit;
				}
				elseif($triple[$crew_member])  {
				
				}
				else {
					
				}
				break;
				
		case 'object':
				
				#echo '<pre>';print_r($where);exit;

				if($isCore)	{
					
					##Who can be connected to an element of the core? The object can eliminate some "from" options by discarding those that,according to the core, cannot be connected to this property as object
					
					
					
					#attr is always the same: it i sthe ID of the core element specified. For example, if it is rule, then attr is rule_id, etc.
					##Where can finally be retrieved; 
					
					switch ($isCore['letter']){
						case 'D':
							$subSpace = array('P','U','G','D');
							$from = array_intersect($from, $subSpace);
							break;	
						case 'P':
							#$subSpace = array('R','C','U','G','P');
							$subSpace = array('R','C','P');
							$from = array_intersect($from, $subSpace);
							foreach ($from as $e) {
								if(!is_array($where[$e])) $where[$e]=array();
								array_push($where[$e], array('project_id' => $isCore['value']));
							}
							break;
						case 'R':
							#$subSpace = array('U','G','R');
							$subSpace = array('R');
							$from = array_intersect($from, $subSpace);
							foreach ($from as $e) {
								array_push($where[$e], array('rule_id' => $isCore['value']));
							}
							break;
						case 'C':
							#$subSpace =array('I','R','U','G','C');
							$subSpace =array('I','R','C');
							
							$from = array_intersect($from, $subSpace);
							foreach ($from as $e) {
								switch ($e) {
									case 'R':
										if(!is_array($where[$e])) $where[$e]=array();
										array_push($where[$e], array('object_id' => $isCore['value']));	
										#$where['R'][end+1]['object_id'] = $isCore['value'];
									break;
									default:
										if(!is_array($where[$e])) $where[$e]=array();
										array_push($where[$e], array('collection_id' => $isCore['value']));	
									break;
									

								}
								#if(!is_array($where[$e])) $where[$e]=array();
								#array_push($where[$e], array('collection_id' => $isCore['value']));	
							}
							
							break;
						case 'I':
							#$subSpace=array('S','R','U','G','I');
							$subSpace=array('S','R','I');
							$from = array_intersect($from, $subSpace);
							foreach ($from as $e) {
								switch ($e) {
									case 'S':
										#16022010; avoided splitting the query
										#this is one of the few cases when we do want the object to be inthe same query as that for the predicate
										if(!is_array($where[$e])) $where[$e] =array('value'=>$isCore['value']);
										else {
											$where[$e][max(array_keys($where[$e]))]['value']=$isCore['value'];
										}
										#array_push($where['S'], array('value' => $isCore['value']));
									break;
									case 'R':
										array_push($where['R'], array('verb_id' => $isCore['value']));
									break;
									default :
										array_push($where[$e], array('item_id' => $isCore['value']));
									break;

								}
							}
							break;
						case 'S':
							#$subSpace=array('S','U','G');
							$subSpace=array('S');
							$from = array_intersect($from, $subSpace);
							foreach ($from as $e) {
								array_push($where[$e], array('statement_id' => $isCore['value']));
							}
							break;
						
					
					}
					
					#$from = array_intersect($from, $subSpace);
					
							
				}
				elseif(isSPARQLVar($triple[$crew_member]))#is subj var?
				{	
					array_push($triple_vars, $triple[$crew_member]);
				}

				elseif(ereg('^http',$triple[$crew_member])) {
					
					#Is this an element of the CoreOntology
					$isOnt = isCoreOntology($triple[$crew_member]);
					
					if($isOnt)
					{
					
					$from = array($GLOBALS['s3codesInv'][strtolower($isOnt)]);
					
					$where[$GLOBALS['s3codesInv'][strtolower($isOnt)]]=array();
					}
					else {
						#to be parsed by SPARQL algebra;
					}
					
				}
				elseif(!ereg('^http',$triple[$crew_member]))  {
						$ob = $triple[$crew_member];
						ereg('"(.*)"', $ob, $ob_parts);
						
						if($ob_parts) $ob=$ob_parts[1];
						
						foreach ($from as $e) {
							switch ($e) {
								case 'S':
								
								if(!is_array($where[$e])) $where[$e] =array('value'=>$ob);
								else {
									$where[$e][max(array_keys($where[$e]))]['value']=$ob;
								}
								
								#this is one of the few cases when we do want the			object to be inthe same query as that for the predicate
								break;
								case 'R':
									#$where[$e][end]['object']=$triple[$crew_member]->label;
								break;
							}
							
						}
						

				}
				
		break;
		}
		
		
	}
	
  
	##Once we go through all the triples, we should have reached a from and a where space; It's time to build the queries necessary for assigning values to variables; constraining the query space
	
	
	#fisrt thing first: let's think about efficiency? Is it the local deployment that is being queries? if so, let's call S3QLaction right here.
	
	if($s3ql['url']==S3DB_URI_BASE || $s3ql['url']==S3DB_URI_BASE.'/')
		{
			$s3ql['user_id']=$user_id;
			$s3ql['db']=$db;
			$remote=0;
		}
	else {
		
		$s3ql['user_id']=S3DB_URI_BASE.'/U'.$user_id;
		$remote=1;
	}
	
	$bQ=buildQuery(compact('s3ql','from','where','remote','performedQueries','varType','varTypeWhere','it'));
	extract($bQ);
	
	return(compact('remoteQueries','localQueries','S3QL','varType','varTypeWhere', 'element','collected_data','performedQueries'));
	
		
}


function buildQuery($bQ)
	{
	
	extract($bQ);
	global $timer;
	
	$element=array();
	$select_fields = array('P'=>'name', 'C'=>'collection_id,project_id,name,notes','R'=>'rule_id,project_id,subject_id,verb_id,object_id,object,notes', 'I'=>'item_id,collection_id,notes','S'=>'statement_id,rule_id,item_id,value');
	foreach ($from as $e) {
		
		
			$tri_s3ql = $s3ql;
			##Let's only select a few fields, as the more triples there are, the more has to be outputed
			$tri_s3ql['select']=$select_fields[$e];
			
			$tri_s3ql['from']=switchFromCore($e);
			array_push($element, $e);
			
			if(!is_array($where[$e]) || empty($where[$e])) {
				##this queries are useless unless they are on project
				#12TCGA
				if($e!='P')
					{
					$tri_s3ql=array();
					}
				else{
					
					if(!$remote)
						$tri_s3ql = array_filter(array_diff_key($tri_s3ql, array('user_id'=>'')));
					$query = S3QLQuery($tri_s3ql);
					$S3QL[] =  $query;
					if($remote){
						$tri_s3ql['format'] = 'php';
						
						$remoteQueries[] = $query;
						
						
						}
						else {
							
							$start = strtotime('His');
							$localQueries[] = $tri_s3ql;
						
						}
					
					  
						array_push($performedQueries, $e);
					}
			}
		   	else {
			
				
				for ($i=0; $i < count($where[$e]); $i++) {
				$tmp = 	$where[$e][$i];
				
				$ind= $i;
				if($tmp)
					$tri_s3ql['where']=$tmp;
					
							
				#now stringize the query such that we can check if it has been built
				#12TCGA
				$stringized_query="";
				foreach ($tri_s3ql['where'] as $w_name=>$w_value) {
					if($stringized_query!="") $stringized_query .="&&";
					else $stringized_query .="(";
					$stringized_query .= $w_name.'='.$w_value;
				}
				if($stringized_query!="") $stringized_query .=")";
				
				
				if(in_array($e.$stringized_query, $performedQueries)){
					##Do NOT perform this query again, it was already seen
					$repeated = true;
				}
				else {
					array_push($performedQueries, $e.$stringized_query);	
					##TCGA change - due to localQueries, moved this section over here
					#again... efficiency
						if($remote){
						$tri_s3ql['format'] = 'php';
						$query = S3QLQuery($tri_s3ql);
						$remoteQueries[] = $query;
						
						}
						else {
							#$tri_s3ql = array_filter(array_diff_key($tri_s3ql, array('user_id'=>'')));
							$localQueries[] = $tri_s3ql;
							$query = S3QLQuery($tri_s3ql);			
							
						}
					$S3QL[] =  $query;
					
				}
				
				
				 
				}
				
				
			}
			
	
	}
  	
	return (compact('remoteQueries','localQueries', 'S3QL', 'element','performedQueries'));	
	}
function buildAndExecuteQ($b)
{
	extract($b);	
	
	$q = array();
	$ans = array();
	$queryModel = $model;
	

	foreach ($from as $e) {
		
		
			$tri_s3ql = $s3ql;
			$tri_s3ql['select']='*';
			$tri_s3ql['from']=switchFromCore($e);
			
			
			if(!is_array($where[$e]) || empty($where[$e])) {
			
			if($tri_s3ql['url']){
				$tri_s3ql['format'] = 'rdf';
				$query = S3QLQuery($tri_s3ql);
				$rQ[] = $query;
				
				
				}
				else {
					$start = strtotime('His');
					$lQ[] = $tri_s3ql;
				
				}
			
			
			}
			else {
				for ($i=0; $i < count($where[$e]); $i++) {
				$tmp = 	$where[$e][$i];
				
				$ind= $i;
				if($tmp)
				$tri_s3ql['where']=$tmp;
				
				
				#again... efficiency
				
				
				if($tri_s3ql['url']){
				$tri_s3ql['format'] = 'rdf';
				$query = S3QLQuery($tri_s3ql);
				$rQ[] = $query;
				
				}
				else {
					$start = strtotime('His');
					$lQ[] = $tri_s3ql;
								
				}
				}
			}
	}
  
   
   
   if(is_array($lQ))
	
    foreach ($lQ as $localQuery) {
		$answer = localQ($tri_s3ql);
		if(!empty($answer))
			{
			$rdfanswer = rdf2php($answer);
			#Line up the answer with the model
			$queryModel->addModel($rdfanswer);
			}
	}
	if(is_array($rQ))
	foreach ($rQ as $remoteQuery) {
		$answer = remoteQ($q);
	    if(!empty($answer))
			{
			$rdfanswer = rdf2php($answer);
			#Line up the answer with the model
			$queryModel->addModel($rdfanswer);
			}
	}
	

	return $queryModel;#$t is the array with the vars that were discovered in this triple
}

function scrubSPARQLVar($a,$b)
{
	$c = ($a[$b]->uri!='')?$a[$b]->uri:$a[$b]->label;
	return ($c);

}

function isDiscovered($v, $ans)
{
	if(is_array($ans) && in_array($v, array_keys($ans)))
		return ($ans[$v]);
	else {
		return (False);
	}
}
function trimBasedOnObj($z)
{extract($z);
switch ($isCore['letter']) {
	case 'P':
		$from = array_intersect($from, array('C','R'));
		foreach ($from as $e) {
			 if(!is_array($where[$e])) $where[$e]=array();
			 array_push($where[$e], array('project_id'=> $isCore['value']));
		}
				
		break;
	case 'D':
		$from = array_intersect($from, array('P','U', 'G'));
		foreach ($from as $e) {
			 if(!is_array($where[$e])) $where[$e]=array();
			 array_push($where[$e], array('deployment_id'=> $isCore['value']));
		}
		
	break;
	case 'G':
		$from = array_intersect($from, array('U'));
		foreach ($from as $e) {
			 if(!is_array($where[$e])) $where[$e]=array();
			 array_push($where[$e], array('group_id'=> $isCore['value']));
		}
	break;

}


return (array($from, $where));
}

function trimBasedOnPred($z)
{extract($z);
switch ($isCore['letter']){
	case 'R':
		#echo '<pre>';print_r($isCore['data']);exit;
		$from=array('S');
		
		if(!is_array($where['S'])) $where['S']=array();
		
		array_push($where['S'], array('rule_id'=>$isCore['value']));
		if($triple['object']->label!='')
			{
			$where['S'][max(array_keys($where['S']))]['value']=$triple['object']->label;#this is one of the few cases when we want the predicate and the object to work together int he same query
			
			}
		
		##We can infer the "type" of subject to use in further queries by looking at the "from" part of the query
		$varType[$triple['subject']][] = 'I';
		$varType[$triple['predicate']][] = 'R';
		$varType[$triple['object']][] = 'I';
		
		##in some cases, we can even know which collection/rule the item/statement belongs to
		if($isCore['data'][0]['subject_id']){
		$varTypeWhere[$triple['subject']][] = 'C'.$isCore['data'][0]['subject_id'];
		}
		if($isCore['data'][0]['object_id']){
		$varTypeWhere[$triple['object']][] = 'C'.$isCore['data'][0]['object_id'];
		}
		
		

		break;
	case 'I':
		$from=array('I');
		if(!is_array($where['R'])) $where['R']=array();
		
		array_push($where['R'], array('verb_id'=>$isCore['value']));
		
		##We can infer the "type" of subject to use in further queries by looking at the "from" part of the query
		$varType[$triple['subject']][] = 'C';
		$varType[$triple['predicate']][] = 'I';
		$varType[$triple['object']][] = 'C';
		
		break;

	
}
return (array($from, $where,$varType,$varTypeWhere));
}

function trimBasedOnSub($s)
{extract($s);
		$from = array_intersect($fromSpace, array($isCore['letter']));
		if(!is_array($where[$isCore['letter']]))
		$where[$isCore['letter']] = array();
		
		
		switch ($isCore['letter']) {
			case 'P':
			   array_push($where['P'], array('project_id' => $isCore['value']));
				break;
			case 'C':
				#collection cen be the subject of a rule
				array_push($from, 'R');
				array_push($where['R'], array('subject_id' => $isCore['value']));
				break;
			case 'I':
				#item can be the subject of a statemnet
				array_push($from, 'S');
				if(!is_array($where['S']))
				$where['S'] = array();
				
				array_push($where['S'], array('item_id'=>$isCore['value']));
				array_push($where['I'], array('item_id'=>$isCore['value']));
				break;
			
			break;
			
		}
		return (array($from, $where));
}
function isCoreOntology($uri)
{
	if(ereg('^http://www.s3db.org/core.owl#s3db(.*)', $uri,$ont))
	{return ($ont[1]);
	}
	else {
		return False;
	}

	
}

function hasNotation($uri,$qname,$prefixes)
{	$url = $prefixes[$qname];
	if(ereg('<'.$url.'(.*)>', $uri,$ont))
	{return ($ont[1]);
	}
	else {
		return False;
	}

	
}

function executeQuery($queryModel,$sparql_triple,$discovered,$format)
	{global $timer;
		

			$tripleData = $queryModel->sparqlQuery($sparql_triple);
			
			
					if(!empty($tripleData)){
					foreach ($tripleData as $datakey=>$datavar) {
						
						foreach ($datavar as $valName=>$varVal) {
							
							if(is_object($datavar[$valName]))
							{
							

							if($format!='xml')
							{
								$tripleData[$datakey][$valName] = (($tripleData[$datakey][$valName]->uri!='')?$tripleData[$datakey][$valName]->uri:$tripleData[$datakey][$valName]->label);
								$discoveredData[$valName][]	= $queryModel;
								$discovered[$valName][] = $tripleData[$datakey][$valName]; 
								

							}
							else
								{$newVarName =  ereg_replace('^\?', '', $valName);$oldVarName = $valName;
								$tripleData[$datakey][$newVarName]=($tripleData[$datakey][$valName]->uri!='')?$tripleData[$datakey][$valName]->uri:$data[$datakey][$valName]->label;	
								$tripleData[$datakey][$oldVarName]='';
								$tripleData[$datakey]=array_filter($tripleData[$datakey]);
								$discovered[$valName][] = $tripleData[$datakey];
								$discoveredData[$valName][]	= $tripleData;
								}
							}
						}
						
					}
					$data[] = $tripleData;
					}
				
			return (array($data,$discovered, $discoveredData,$queryModel));
	}

function remoteQ($q){
	$b = strtotime(date('His'));
	$c = fopen($q, 'r');			
	$answer = stream_get_contents($c);
	return ($answer);
}

function localQ($tri_s3ql){
	$query = S3QLAction($tri_s3ql);
	##Now force the RDF output
	$format=$tri_s3ql['format'];
	$data = $query;
	$db=$tri_s3ql['db'];
	if(is_array($query[0])){
	$cols = array_keys($query[0]);
	$letter = letter($tri_s3ql['from']);
	$z = compact('data','cols','format', 'db','letter');

	$answer = outputFormat($z);
	}
	else {
	 $answer = array();
	}		
	return ($answer);
}

function iterationOrder($triples,$pref=array(),$return_order=false)
	{
	 /**
	
	* @author Helena F Deus <helenadeus@gmail.com>
	* @license http://www.gnu.org/copyleft/gpl.html GNU General Public License
	* @package S3DB http://www.s3db.org
	*/
	
	#Find triple order is based on the premisse that the more contsnts the system has, the more likely it is to trim down the query. So this simple function scores the number of constants in each triple and re-sorts them as each triple is being solved and providing constants for the remaining triples
	
	#break and reorder the triples; retaining the original order will be important
	
	if(count($triples)>=1){
	
	for ($i=0;$i<count($triples);$i++) {
		
		#before multisored
		$unscrambled[$triples[$i]]=$i;
		
		$triple = trim($triples[$i]);
		$solver =  explode(' ',$triple);
		list($s,$p,$o) = $solver;
		$puzzle = array(!ereg('^\?',$s),!ereg('^\?',$p),!ereg('^\?',$o));
		
		
		#If all are constant, then it is not an S3QL query
		if(array_sum($puzzle)==3){
		#$triples[$i]='';
		#$s="";$p="";$o="";#delete also s, p, o to use next
		$score[$i] = 0;
		$s="";$p="";$o="";
		}
		else{
		$score[$i] = array_sum($puzzle);
		}

		##Queries on items of collections that do not have results make it unnecessary to query the attributes of those. Score higher those that query collections
		#Find if the obj is collection
		$ob	="";$pr="";
		ereg("<(.*)>",$o,$ob);
		if($ob[1]) $o=$ob[1];
		$obj = isS3DBCore($o, false);
		#$obj = isS3DBCore($ob[1], false);

		#Find if predicate is type
		$Pterm=hasNotation($p,'rdf',$pref);
		
		##triples that tell us if ths object is of a particular kind go first
		if($Pterm=='type' && ereg('I|C|S|R|P',$obj['letter'])){
						
			$score[$i] = $score[$i]+1; ##Queries get 1 point for being faster
			
		}

		##triples that are a subclass of something are faster, get 1 extra point
		#if($p=='http://www.w3.org/2000/01/rdf-schema#subClassOf' && ereg('P',$obj['letter'])){
		#	$score[$i] = $score[$i]+1;
		#}
		
		
		
		#Some predicates, such as label comment, etc, appear in any entity, therefore they are non specific and do not help in building a query  - lose 1 point
		$pr="";
		ereg("<(.*)>",$p,$pr);
		if($pr) $p=$pr[1];

		
		if(in_array($p,$GLOBALS['not_uid_specific']) && $score[$i]==1){#the constant part is not specific and there is only this one constant
			$score[$i]=$score[$i]-1;
			
		
		}
		
	}
	
	##This will basically assign the triple to a subgroup of queries to be performed simulataneously, according to its order
	array_multisort($score, SORT_NUMERIC, SORT_DESC,$triples); ##Because I don't want to lose the index relationship between the ttriples and the order
	
	##Now separate the triples into groups
	$groups = array();
	
	foreach ($score as $i=>$s) {
		$j=max($score)-$s;
		if(!is_array($groups[$j])) $groups[$j] = array();
		array_push($groups[$j], $triples[$i]);
	}
	
		#$order = findTripleOrder($triples,array(),0,$pref);
		
		#if(is_array($order))
		#foreach ($order as $tripleInd=>$iteration) {
		
		#$createThis[$iteration][]=$tripleInd;
	#}
	}
	if(!$return_order)
	return ($groups);
	else {
		return (array($groups, $unscrambled));
	}
	
	}

function findTripleOrder($triples, $firsts=array(),$or=0,$pref=array())
	{global $timer;
	/**
	
	* @author Helena F Deus <helenadeus@gmail.com>
	* @license http://www.gnu.org/copyleft/gpl.html GNU General Public License
	* @package S3DB http://www.s3db.org
	*/
	
	#Find triple order is based on the premisse that the more contsnts the system has, the more likely it is to trim down the query. So this simple function scores the number of constants in each triple and re-sorts them as each triple is being solved and providing constants for the remaining triples
	
	#break the triples
	if(count($triples)>1){
	
	for ($i=0;$i<count($triples);$i++) {
		$triple = trim($triples[$i]);
		$solver =  explode(' ',$triple);
		list($s,$p,$o) = $solver;
		$puzzle = array(!ereg('^\?',$s),!ereg('^\?',$p),!ereg('^\?',$o));
		
		
		#If all are constant, then it is not an S3QL query
		if(array_sum($puzzle)==3){
		#$triples[$i]='';
		#$s="";$p="";$o="";#delete also s, p, o to use next
		$score[$i] = 0;
		$s="";$p="";$o="";
		}
		else{
		$score[$i] = array_sum($puzzle);
		}

		##Queries on items of collections that do not have results make it unnecessary to query the attributes of those. Score higher those that query collections
		#Find if the obj is collection
		$ob	="";$pr="";
		ereg("<(.*)>",$o,$ob);
		$obj = isS3DBCore($ob[1], false);

		#Find if predicate is type
		$Pterm=hasNotation($p,'rdf',$pref);
		
		if($Pterm=='type' && ereg('I|C|S|R|P',$obj['letter'])){
						
			$score[$i] = $score[$i]+1; ##Queries get 1 point for being faster
			
		}

		#Some predicates, such as label comment, etc, appear in any entity, therefore they are non specific and do not help in building a query  - lose 1 point
		$pr="";
		ereg("<(.*)>",$p,$pr);
		if(in_array($pr[1],$GLOBALS['not_uid_specific']) && $score[$i]==1){#the constant part is not specific and there is only this one constant
			$score[$i]=$score[$i]-1;
			
		
		}
		
	}
	
	##This will basically assign the triple to a subgroup of queries to be performed simulataneously, according to its order
	array_multisort($score, SORT_NUMERIC, SORT_DESC,$triples); ##Because I don't want to lose the index relationship between the ttriples and the order
	
	##Now separate the triples into groups
	$groups = array();
	
	foreach ($score as $i=>$s) {
		$j=max($score)-$s;
		if(!is_array($groups[$j])) $groups[$j] = array();
		array_push($groups[$j], $triples[$i]);
	}
	
	/*for ($j=0; $j < count($score) ; $j++) {
		
		#now, the first line to solve will be the one socres the highest but not equal to 3
		#if($score[$j]==2)
		if($score[$j]==max($score))
		{
			$firsts[$j] = $or;
			
			#now eliminate the solved triple from the other triples
			#$tick = array_search(0, $puzzle);
			
			
			$tmp=split(' ',$triples[$j]);
			$tick='';
			foreach ($tmp as $t) {
				if(ereg('^\?',$t))
					$tick = $t;
			}
			
			#if(ereg('(\?[A-Za-z0-9_]) ', $triples[$j],$tmp)){
			if($tick!=''){
			
			
			$triplesSolved=array();
			foreach ($triples as $tmp) {
				$triplesSolved[] = str_replace($tick,substr($tick, 1, strlen($tick)), $tmp);
				
			}
			$triples = $triplesSolved;
			
			}
			#$score[$j]=0;##since it was already added, we want a new score max
			
		}
		else {
			$firsts[$j]=0;
		}

	  
		

	}
	
	*/
	#ok, we're done, let's go back to the beginning
	/*if($triplesSolved)
		{
		$triplesSolved = array_filter($triplesSolved);
		$or++;
		$firsts = findTripleOrder($triples,$firsts,$or,$pred);
	
		}
	}
	else {
		
		$firsts = array(0=>0);
	}
	*/
	
		}
		return ($groups);
	}
function WasDiscovered($object,$discovered, $varTypeWhere=array())
{

if(is_array($discovered))
if(in_array($object, array_keys($discovered)))
{
  
  $objectType =  $discovered[$object];
  
	return ($objectType);
}
else {
	return (False);
}

}

function microtime_float()
{
    list($usec, $sec) = explode(" ", microtime());
    return ((float)$usec + (float)$sec);
}

function interpret_literal_object($object)
	{
										
	if(!eregi("REGEX", $object))#Boa, we have something to put in the "value" part
	{	
		$whereToQuery = array('value'=>$object);
		
	}
	else {
		##to be completed
	}
	return ($whereToQuery);
	}

function parse_sparql_query($q, $s3ql)
{
	$q=ereg_replace('\.\?', '. ?',$q);
	##Does this sparql have the "Select" and "prefix" part or is it just the triple patenrs
	#preg_match_all("(PREFIX|SELECT|FROM|WHERE)", $q,$tmp);
	#Altered 4/23/2010; see explanation in lenalabbook, 14April2010
	preg_match_all("(PREFIX|SELECT|DESCRIBE|CONSTRUCT|ASK|FROM|WHERE)", $q,$tmp);

	$sp = array();
	if(is_array($tmp)){
	$tmp=$tmp[0];
	$rest = $q;

	foreach ($tmp as $k=>$sp_part) {
		 ##ALL LOWERCASE
		 $sp_part = strtolower($sp_part);
		 
		 ##Find the next part
		 $pos=stripos($rest, $sp_part);
		
		 $this_till_end = substr($rest, $pos+strlen($sp_part), strlen($rest)-$pos);
		 if($tmp[$k+1]){
		 $next = stripos($this_till_end, $tmp[$k+1]);
		 
		 $this_portion = substr($rest, $pos+strlen($sp_part),$next);
		 $rest = substr($rest, $next, strlen($rest));
		 }
		 else {
		 $this_portion = substr($rest, $pos+strlen($sp_part),strlen($rest));	
		 $rest = substr($rest, $pos+strlen($sp_part), strlen($rest));
		 }
		 
		 
		 
		 if(!$sp[$sp_part]) $sp[$sp_part] = array();
		 array_push($sp[$sp_part], $this_portion);
		 
		
		 
	}
	}

	if(!in_array("prefix", array_keys($sp))){
		$sp['prefix'] = array("rdfs: <http://www.w3.org/2000/01/rdf-schema#>", "rdf: <http://www.w3.org/1999/02/22-rdf-syntax-ns#>", "s3db: <http://www.s3db.org/core.owl#>", ': <'.$s3ql['url'].((substr($s3ql['url'], strlen($s3ql['url'])-1, 1)=='/')?'':'/').'>');
		}
	##Now fix the prefixes such that they are in the format qname=>url
	$qnames = array();$url_qnames = array();
	foreach ($sp['prefix'] as $pref) {
		eregi('(.*:) *<(.*)>', $pref, $x);
		if($x)
		{array_push($qnames, " ".trim($x[1]));
		array_push($url_qnames, " ".trim($x[2]));
		}
	}

	#Altered 4/23/2010
	if(!in_array("select", array_keys($sp)) && !in_array("describe", array_keys($sp)) && !in_array("construct", array_keys($sp)) && !in_array("ask", array_keys($sp))){
		$sp['select'] = array("distinct *");
		}
	/*
	if(!in_array("select", array_keys($sp))){
		$sp['select'] = array("distinct *");
		}
	*/

	if(!in_array("from", array_keys($sp))){
		$sp['from'] = array(" <".$s3ql['url'].">");
		}

	if(!in_array("where", array_keys($sp))){
		$sp['where'] = array(" { ".$q." } ");
		}

	##Now that the query is parsed, based on the "where" portion of the query, interpred the S3QL needed to obtain the data requested on the triples
	$where = $sp['where'][0];
	$where = ereg_replace("^{|}$","", trim($where));

	##Find all triples, regardless of there being optionals or not
	#preg_match_all("(.*\.)", $where,$tmp1);
	$tmp1[0]=array_filter(explode(chr(32).".", trim($where)));
    #$where = str_replace(". ", ".",trim($where)); 
	#$tmp1[0]=array_filter(explode(".", $where));



	foreach ($tmp1[0] as $triple) {
		$Queried = array();
		$score = 0;
		$rawtriple = str_ireplace(array("OPTIONAL {", "}"), array("", ""), trim($triple));
		##Now for each triple, find the elements of teh core that are being seeked.
		$rawtriple = " ".trim($rawtriple);##ading a space in the begg to prevent replaceing qnames twice;
		
		##Now replace with the qnames ... so we know which is core, which is not
		$prefixed_triple = str_replace($qnames, $url_qnames, $rawtriple); 
		 
		
		$S3QL[] = $Queried;
		$triples[] = $prefixed_triple;
		$triple_score[] = $score;
	}
	$triples = array_unique($triples);
	if($timer) $timer->setMarker('String triples discovered from the parsed query.');
	return (array($sp, $triples, array_combine($qnames, $url_qnames)));
}

function make_rdf_model($triples)
{
	$parser = ARC2::getComponent('RDFXMLParser');
	$index = ARC2::getSimpleIndex($triples, false) ; /* false -> non-flat version */
	$rdf_doc = $parser->toRDFXML($index);
	$filename = S3DB_SERVER_ROOT.'/tmp/'.md5(rand(10000, 200000));
	$rr= fopen($filename, 'a+');
	fwrite($rr, $rdf_doc);
	fclose($rr);	
	return ($filename);
}
?>