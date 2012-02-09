<?php
// ----------------------------------------------------------------------------------
// RDFDBUtils : Logout
// ----------------------------------------------------------------------------------

/** 
 * Destroys all session data
 * 
 * @version $Id: logout.php,v 1.6 2006/05/15 05:24:37 tgauss Exp $
 * @author   Gunnar AAstrand Grimnes <ggrimnes@csd.abdn.ac.uk>
 *
 **/

session_start();
session_unset();
session_destroy();
//session_write_close();
header("Location: index.php");
?>