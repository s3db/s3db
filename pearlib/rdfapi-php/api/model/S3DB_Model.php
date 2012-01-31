<?php

// ----------------------------------------------------------------------------------
// Class: S3DB_Model
// ----------------------------------------------------------------------------------

/**
 * Abstract superclass of Model. A S3DB_Model is a programming interface to an RDF graph annotated to the S3DB Core.
 * An RDF graph is a directed labeled graph, as described in http://www.w3.org/TR/rdf-mt/.
 * It can be defined as a set of <S, P, O> triples, where P is a uriref, S is either
 * a uriref or a blank node, and O is either a uriref, a blank node, or a literal.
 *
 *
 * @version  $Id: S3DB_Model.php,v 1.00 2008/06/24
 * @author Helena Deus <helenadeus@gmail.com>
 *
 * @package model
 * @access	public
 */
class S3DB_Model extends Model
{

}
?>