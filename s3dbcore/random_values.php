<?php

function random_string($length)
	{
	  $acceptedChars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYS0123456789';
  $max = strlen($acceptedChars)-1;
  $random = null;
  for($i=0; $i < $length; $i++) {
   $random .= $acceptedChars{mt_rand(0, $max)};
  }
  return $random;
	
	}

?>