<?php
	/***************************************************************************\
        * S3DB                                                                     *
        * http://www.s3db.org                                                      *
        * Written by Chuming Chen <chumingchen@gmail.com>                          *
        * ------------------------------------------------------------------------ *
        * This program is free software; you can redistribute it and/or modify it  *
        * under the terms of the GNU General Public License as published by the    *
        * Free Software Foundation; either version 2 of the License, or (at your   *
        * option) any later version.                                               *
        * See http://www.gnu.org/copyleft/gpl.html for detail                      *
        \**************************************************************************/
	/*ob_start();
	$_SESSION = array();	
	session_destroy();
	Header('Location: login.php?msg=1');
	*/
	
	session_start();
	$_SESSION['db'] ='';
	Header('Location: login.php?error=1');
?>
