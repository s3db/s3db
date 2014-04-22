<?php
    /**
     * Database class for MySQL using mysqli_ instead of mysql_ (preferred for PHP projects using >= PHP 5.0 and >= MySQL 4.1)
     * @author Bade Iriabho
     * @copyright Copyright (C) 2011-2014 Bade Iriabho
     * @copyright Portions Copyright (C) 2001-2004 Free Software Foundation, Inc. http://www.fsf.org/
     * @license http://www.fsf.org/licenses/lgpl.html GNU Lesser General Public License
     * @link see class.db.inc.php
     * @package phpgwapi
     * @subpackage database
     * @version $Id: class.db_mysql.inc.php,v 1.00.0.0.0.1 2012/12/20 13:51:18 ceb Exp $
     */

/**
 * Database class for MySQL
*
* @package phpgwapi
* @subpackage database
*/
class db extends db_
{
    /* public: this is an api revision, not a CVS revision. */
    var $type     = 'mysql';
    var $revision = '1.4';

    function db($query = '') {
        $this->db_($query);
    }

    /* public: connection management */
    function connect($Database = '', $Host = '', $User = '', $Password = '') {
        /* Handle defaults */
        if ($Database == '') { $Database = $this->Database; }
        if ($Host == '') 	 { $Host     = $this->Host; }
        if ($User == '')	 { $User     = $this->User; }
        if ($Password == '') { $Password = $this->Password; }

        /* establish connection, select database */
        if (!isset($this->Link_ID) || $this->Link_ID == 0) {
            if ($GLOBALS['phpgw_info']['server']['db_persistent']) {
                $this->Link_ID=mysqli_connect("p:$Host", $User, $Password, $Database);
            } else {
                $this->Link_ID=mysqli_connect($Host, $User, $Password, $Database);
            }

            /* check connection */
            if (!$this->Link_ID) {
                $this->halt(($GLOBALS['phpgw_info']['server']['db_persistent']?'p':'')."connect($Host, $User, \$Password) failed. Connect Error[".mysqli_connect_errno()."] ".mysqli_connect_error());
                $this->Errno=2;
                return 0;
            }
        }
        return $this->Link_ID;
    }

    /* This only affects systems not using persistant connections */
    function disconnect() {
        if($this->Link_ID) {
            @mysqli_close($this->Link_ID);
            $this->Link_ID = 0;
            return 1;
        } else {
            return 0;
        }
    }

    function to_timestamp($epoch) {
        return date('Y-m-d H:i:s',$epoch);
    }

    function from_timestamp($timestamp) {
        preg_match('/([0-9]{4})-([0-9]{2})-([0-9]{2}) ([0-9]{2}):([0-9]{2}):([0-9]{2})/',$timestamp,$parts);
        return mktime($parts[4],$parts[5],$parts[6],$parts[2],$parts[3],$parts[1]);
    }

    /* public: discard the query result */
    function free() {
        if(isset($this->Query_ID) && !is_null($this->Query_ID)) {
            @mysqli_free_result($this->Query_ID);
        }
        $this->Query_ID = NULL;
    }

    /* line number and file name functionality are added so as to help with better error reporting */
    function query($Query_String, $line = '', $file = '') {
        /* No empty queries, please, since PHP4 chokes on them. */
        /* The empty query string is passed on from the constructor,
         * when calling the class without a query, e.g. in situations
        * like these: '$db = new db_Subclass;'
        */
        if ($Query_String == '') {
            return 0;
        }
        if (!$this->connect()) {
            $this->Errno = @mysqli_connect_errno();
            $this->Error = @mysqli_connect_error();
            return 0; /* we already complained in connect() about that. */
        }

        # New query, discard previous result.
        if ($this->Query_ID) {
            $this->free();
        }
    
        if ($this->Debug) {
            printf("Debug: query = %s<br>\n", $Query_String);
        }
    
        $this->Query_ID = @mysqli_query($this->Link_ID, $Query_String);
        $this->Row   = 0;
        $this->Errno = mysqli_errno($this->Link_ID);
        $this->Error = mysqli_error($this->Link_ID);
        if (!$this->Query_ID) {
            $this->halt("Invalid SQL: ".$Query_String, $line, $file);
        }
    
        # Will return nada if it fails. That's fine.
        return $this->Query_ID;
    }
    
    // public: perform a query with limited result set
    function limit_query($Query_String, $offset, $line = '', $file = '', $num_rows = 0) {
        $offset		= intval($offset);
        $num_rows	= intval($num_rows);
    
        if ($num_rows == 0) {
            $maxmatches = $GLOBALS['phpgw_info']['user']['preferences']['common']['maxmatchs'];
            $num_rows = (isset($maxmatches)?intval($maxmatches):15);
        }
    
        if ($offset == 0) {
            $Query_String .= " LIMIT $num_rows";
        } else {
            $Query_String .= " LIMIT {$offset},{$num_rows}";
        }

        if ($this->Debug) {
        	printf("Debug: limit_query = %s<br>offset=%d, num_rows=%d<br>\n", $Query_String, $offset, $num_rows);
        }
        
        return $this->query($Query_String, $line, $file);
    }
    
    /* public: walk result set */
    function next_record() {
        if (!$this->Query_ID) {
            $this->halt('next_record called with no query pending.');
            return 0;
        }
    
        $this->Record = @mysqli_fetch_array($this->Query_ID);
        $this->Row   += 1;
        $this->Errno  = mysqli_errno($this->Link_ID);
        $this->Error  = mysqli_error($this->Link_ID);
        
        $stat = is_array($this->Record);
        if (!$stat && $this->Auto_Free) {
            $this->free();
        }
        return $stat;
    }
    
    /* public: position in result set */
    function seek($pos = 0) {
        $status = @mysqli_data_seek($this->Query_ID, $pos);
        if ($status) {
            $this->Row = $pos;
        } else {
            $this->halt("seek($pos) failed: result has ".$this->num_rows()." rows");
            /* half assed attempt to save the day,
             * but do not consider this documented or even
             * desireable behaviour.
             */
            @mysqli_data_seek($this->Query_ID, $this->num_rows());
            $this->Row = $this->num_rows;
            return 0;
        }
        return 1;
    }
    
    function get_last_insert_id($table, $field) {
        /* This will get the last insert ID created on the current connection.  Should only be called
         * after an insert query is run on a table that has an auto incrementing field.  $table and
         * $field are required, but unused here since it's unnecessary for mysql.  For compatibility
         * with pgsql, the params must be supplied.
         */
        if (!isset($table) || $table == '' || !isset($field) || $field == '') {
            return -1;
        }
        return @mysqli_insert_id($this->Link_ID);
    }
    
    function last_resource_id() {
        $db = $_SESSION['db'];
        $sql = 'SELECT MAX(resource_id) FROM s3db_resource';
        $db->query($sql, __LINE__, __FILE__);
        if($db->next_record()) {
            $last = Array('resource_id'=>$db->f('max(resource_id)'));
        }
        return $last;
    }
    
    /* public: table locking */
    function lock($table, $mode='write') {
        $this->connect();
        $query = "LOCK TABLES ";
        if (is_array($table)) {
            while (list($key,$value)=each($table)) {
                if ($key == "read" && $key!=0) {
                    $query .= "$value read, ";
                } else {
                    $query .= "$value $mode, ";
                }
            }
            $query = substr($query,0,-2);
        } else {
            $query .= "$table $mode";
        }
        $res = @mysqli_query($this->Link_ID,$query);
        if(!$res) {
            if(is_array($tables)) {
                $this->halt("lock(array(".implode(',',$table)."), $mode) failed.");
            } else {
                $this->halt("lock($table, $mode) failed.");
            }
            return 0;
        }
        return $res;
    }
    
    function unlock() {
        $this->connect();
        $res = @mysqli_query("UNLOCK TABLES");
        if (!$res) {
            $this->halt("unlock() failed.");
            return 0;
        }
        return $res;
    }
    
    /* public: evaluate the result (size, width) */
    function affected_rows() {
        return @mysqli_affected_rows($this->Link_ID);
    }
    
    function num_rows() {
        return @mysqli_num_rows($this->Query_ID);
    }
    
    function num_fields() {
        return @mysqli_field_count($this->Link_ID);
    }
    
    /* public: sequence numbers */
    function nextid($seq_name) {
        $this->connect();
        if ($this->lock($this->Seq_Table)) {
            /* get sequence number (locked) and increment */
            $q   = sprintf("SELECT nextid FROM %s WHERE seq_name = '%s'", $this->Seq_Table, $seq_name);
            $id  = @mysqli_query($this->Link_ID, $q);
            $res = @mysqli_fetch_array($id);
    
            /* No current value, make one */
            if (!is_array($res)) {
                $currentid = 0;
                $q = sprintf("INSERT INTO %s VALUES('%s', %s)", $this->Seq_Table, $seq_name, $currentid);
                $id = @mysqli_query($this->Link_ID, $q);
            } else {
                $currentid = $res["nextid"];
            }
            $nextid = $currentid + 1;
            $q = sprintf("UPDATE %s SET nextid = '%s' WHERE seq_name = '%s'", $this->Seq_Table, $nextid, $seq_name);
            $id = @mysqli_query($this->Link_ID, $q);
            $this->unlock();
        } else {
            $this->halt("cannot lock {$this->Seq_Table} - has it been created?");
            return 0;
        }
        return $nextid;
    }
    
    /* public: return table metadata */
    function metadata($table='',$full=false) {
        $count = 0;
        $id    = 0;
        $res   = array();
    
        /* if no $table specified, assume that we are working with a query */
        /* result */
        if(strlen(trim($table)) > 0) {
            $sql = "SELECT * FROM $table LIMIT 1";
            $this->query($sql, __LINE__, __FILE__);
        }
        $id = $this->Query_ID;
        if (!$id) {
            $this->halt("No query specified.");
        }
    
        $count = @mysqli_field_count($this->Link_ID);

        /* made this IF due to performance (one if is faster than $count if's) */
        if (!$full) {
            for ($i=0; $i<$count; $i++) {
                $finfo = mysqli_fetch_field_direct($this->Query_ID, $i);
                $res[$i]['table'] = $finfo->table;
                $res[$i]['name']  = $finfo->name;
                $res[$i]['type']  = $finfo->type;
                $res[$i]['len']   = $finfo->length;
                $res[$i]['flags'] = $finfo->flags;
            }
        } else {
            /* full */
            $res["num_fields"]= $count;
            for ($i=0; $i<$count; $i++) {
                $finfo = mysqli_fetch_field_direct($this->Query_ID, $i);
                $res[$i]['table'] = $finfo->table;
                $res[$i]['name']  = $finfo->name;
                $res[$i]['type']  = $finfo->type;
                $res[$i]['len']   = $finfo->length;
                $res[$i]['flags'] = $finfo->flags;
                $res['meta'][$res[$i]['name']] = $i;
            }
        }
        
        /* free the result only if we were called on a table */
        if ($table) {
            @mysqli_free_result($id);
        }
        return $res;
    }
    
    /* private: error handling */
    function halt($msg, $line = '', $file = '') {
        $this->Error = @mysqli_error($this->Link_ID);	// need to be BEFORE unlock,
        $this->Errno = @mysqli_errno($this->Link_ID);	// else we get its error or none
        	
        if ($this->Link_ID)	{	// only if we have a link, else infinite loop
            $this->unlock();	/* Just in case there is a table currently locked */
        }
        if ($this->Halt_On_Error == "no") {
            return;
        }
        $this->haltmsg($msg);
        if ($file) {
            printf("<br><b>File:</b> %s",$file);
        }
        if ($line) {
            printf("<br><b>Line:</b> %s",$line);
        }
        if ($this->Halt_On_Error != "report") {
            echo "<p><b>Session halted.</b>";
            $GLOBALS['phpgw']->common->phpgw_exit(True);
        }
    }
    
    function haltmsg($msg) {
        printf("<b>Database error:</b> %s<br>\n", $msg);
        if ($this->Errno != "0" && $this->Error != "()") {
            printf("<b>MySQL Error</b>: %s (%s)<br>\n",$this->Errno,$this->Error);
        }
    }
    
    function table_names() {
        if (!$this->Link_ID) {
            $this->connect();
        }
        if (!$this->Link_ID) {
            return array();
        }
        $return = Array();
        $this->query("SHOW TABLES");
        $i=0;
        while ($info=@mysqli_fetch_row($this->Query_ID)) {
            $return[$i]['table_name'] = $info[0];
            $return[$i]['tablespace_name'] = $this->Database;
            $return[$i]['database'] = $this->Database;
            $i++;
        }
        return $return;
    }

    function create_database($adminname = '', $adminpasswd = '') {
        $currentUser = $this->User;
        $currentPassword = $this->Password;
        $currentDatabase = $this->Database;

        if($adminname != '') {
            $this->User = $adminname;
            $this->Password = $adminpasswd;
            $this->Database = "mysql";
        }
        $this->disconnect();
        $this->query("CREATE DATABASE IF NOT EXISTS $currentDatabase");
        $this->query("GRANT ALL ON $currentDatabase.* TO '$currentUser'@'localhost' IDENTIFIED BY '$currentPassword'");
        $this->disconnect();
        
        $this->User = $currentUser;
        $this->Password = $currentPassword;
        $this->Database = $currentDatabase;
        $this->connect();
        /*return $return; */
    }
}
?>
