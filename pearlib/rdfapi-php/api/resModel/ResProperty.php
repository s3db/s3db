<?php
// ----------------------------------------------------------------------------------
// Class: ResProperty
// ----------------------------------------------------------------------------------

/**
* An RDF Property.
*
*
* @version  $Id: ResProperty.php,v 1.7 2006/11/21 09:38:50 tgauss Exp $
* @author Daniel Westphal <mail at d-westphal dot de>
*
*
* @package resModel
* @access	public
**/
class ResProperty extends ResResource  
{
	
	/**
    * Constructor
	* You can supply a URI
    *
    * @param string $uri 
	* @access	public
    */	
	function ResProperty($uri)
	{
		parent::ResResource($uri);
	}
} 
?>