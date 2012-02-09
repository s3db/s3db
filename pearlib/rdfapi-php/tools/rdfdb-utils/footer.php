<?php

// ----------------------------------------------------------------------------------
// RDFDBUtils : Footer 
// ----------------------------------------------------------------------------------

/** 
 * At the bottom of each page
 * 
 * @version $Id: footer.php,v 1.6 2006/05/15 05:24:37 tgauss Exp $
 * @author   Gunnar AAstrand Grimnes <ggrimnes@csd.abdn.ac.uk>
 *
 **/


if (isset($db)) { $db->close(); } 


?>

<div class="footer">
<hr> 
RDF DB Utils - a part of <a href="http://www.wiwiss.fu-berlin.de/suhl/bizer/rdfapi/index.html">RAP</a><br/>
Written by <a href="http://www.csd.abdn.ac.uk/~ggrimnes/">Gunnar Grimnes</a>
</div>

</body>
</html>

