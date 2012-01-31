<?php

$a=fopen($argv[1],'r');
$fcg = stream_get_contents($a);
file_put_contents('/home/mhdeus/.public_html/lixo/'.md5($argv[1]), $fcg);
print($argv[1].' done');
?>
