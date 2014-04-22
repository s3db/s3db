<?
    $db_engine  = $GLOBALS['s3db_info']['server']['db']['db_type'];
    $hostname 	= $GLOBALS['s3db_info']['server']['db']['db_host'];
    $dbname 	= $GLOBALS['s3db_info']['server']['db']['db_name'];
    $user 		= $GLOBALS['s3db_info']['server']['db']['db_user'];
    $pass 		= $GLOBALS['s3db_info']['server']['db']['db_pass'];
    
    if($db_engine == 'mysql') {
        $dbconn = mysqli_connect($hostname, $user, $pass);
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
    } else {
        #creating a db in postgres without root user
        $dbconn = pg_connect("host=$hostname user=$user password=$pass dbname=template1");
        if(!$dbconn) {
            echo "An error occured in connecting to pgSQL database.";
            exit;
        }
        $query = pg_query($dbconn, "CREATE DATABASE $dbname WITH OWNER $user");
        if(!$query) {
            echo "An error occured creating the database.";
            exit;
        }
        pg_close($dbconn);
    }
?>