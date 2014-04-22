<?php
    ini_set('display_errors',0);
    if($_REQUEST['su3d']) {
        ini_set('display_errors',1);
    }
    if(file_exists('config.inc.php')) {
        include('config.inc.php');
    } else {
        Header('Location: login.php?error=7');
        exit;
    }

    $db_engine = $GLOBALS['s3db_info']['server']['db']['db_type'];
    $hostname  = $GLOBALS['s3db_info']['server']['db']['db_host'];
    $dbname    = $GLOBALS['s3db_info']['server']['db']['db_name'];
    $user      = $GLOBALS['s3db_info']['server']['db']['db_user'];
    $pass      = $GLOBALS['s3db_info']['server']['db']['db_pass'];

    if($db_engine == 'mysql') {	
        $dbconn = mysqli_connect($hostname, 'root', '');
        if(!$dbconn) {
            echo "Failed to connect to database (" . mysqli_connect_errno() . "): " . mysqli_connect_error();
            exit;
        }
         
        $dbcsql  = "CREATE DATABASE $dbname; ";
        $dbcsql .= "GRANT ALL PRIVILEGES ON {$db_engine}.* TO '$user'@'localhost' IDENTIFIED BY '$pass';";
        if(mysqli_multi_query($dbconn,$dbcsql)) {
            //Intentionally not echoing anything
            //echo "Database $dbname created successfully.";
        } else {
            echo "Error creating database: " . mysqli_error($dbconn);
            exit;
        }
        mysqli_close($dbconn);
    }
?>