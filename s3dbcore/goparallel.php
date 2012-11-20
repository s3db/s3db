<?php
	##goparallel runs multiple S3QL queries simulatenously using curl library. 
	#Part of S3DB (http://s3db.org)
	#Helena Deus (helenadeus@gmail.com), 2009-08-10
	if(is_file('../pearlib/PEAR.php')) {
		require_once '../pearlib/PEAR.php';
		require_once '../pearlib/Benchmark/Timer.php';
		$timer = new Benchmark_Timer();
		$timer->start();
	}
	$S3QL = array(
			'http://ibl.mdanderson.org/edu/S3QL.php?key=xxxxx&query=<S3QL><from>items</from><where><collection_id>26969</collection_id></where></S3QL>',
			'http://ibl.mdanderson.org/edu/S3QL.php?key=xxxxx&query=<S3QL><from>items</from><where><collection_id>26971</collection_id></where></S3QL>',
			'http://ibl.mdanderson.org/edu/S3QL.php?key=xxxxx&query=<S3QL><from>items</from><where><collection_id>26973</collection_id></where></S3QL>',
			'http://ibl.mdanderson.org/edu/S3QL.php?key=xxxxx&query=<S3QL><from>items</from><where><collection_id>26975</collection_id></where></S3QL>',
		);
	$results = goparallel($S3QL,1);
	echo "Your queries results:<BR>";
	echo '<pre>';print_r($results);
	$timer->display();

	function goparallel($S3QL,$goparallel=true) {
		global $timer;
		if(extension_loaded ('curl') && $goparallel) {
			// Create cURL handlers
			if($timer) { $timer->setMarker('Starting queries from group '.$it); }
			foreach ($S3QL as $k=>$url) {
				$qURL = $url.'&format=php';
				$ch[$k] = curl_init();
				// Set options 
				curl_setopt($ch[$k], CURLOPT_URL, $qURL);
				curl_setopt($ch[$k], CURLOPT_RETURNTRANSFER, 1);
			}
			$mh = curl_multi_init();
			foreach ($S3QL as $k=>$url) {
				curl_multi_add_handle($mh,$ch[$k]);
			}
			$running=null;
			do {
				$test=curl_multi_exec($mh,$running);
				if($timer) { $timer->setMarker('Query '.$k.' of group '.$it.' executed'); }
			} while($running > 0);
			$answer[$k] = "";
			foreach ($S3QL as $k=>$url) {
				$answer[$k]  = curl_multi_getcontent($ch[$k]);
				if(!empty($answer[$k])) {
					#@fwrite($a, $answer[$k]);
					##This is what takes the longest after the query, can it be replaced?
					$ans=array();
					$ans = unserialize($answer[$k]);
					#$letter =  $queried_elements[$r][0];
					//$letter =  $queried_elements[$k];
					
					if(empty($ans)) {
						##is this query part is not optional, then the result will be null
						##TO BE DEVELOPED SOON
					} else {
						$results[$k] = $ans;		
					}
					$r++;
			
					##Add the triples to already existing triples
					#Line up the answer with the model
					if($timer) { $timer->setMarker('Query '.$it.'=>'.$k.' converted to php '); }
				}
			}
			curl_multi_close($mh);
		} else {
			foreach ($S3QL as $k=>$url) {
				if($timer) { $timer->setMarker('Query '.$k.' executed'); }
				$url .= '&format=php';
				$a = @fopen($url,'r'); 
				if($a) {
					$ans=array();
					$tmp = stream_get_contents($a);
					$ans = unserialize($tmp);
					$results[$k] = $ans;
					fclose($a);
				}
			}
		}
		return ($results);
	}
?>