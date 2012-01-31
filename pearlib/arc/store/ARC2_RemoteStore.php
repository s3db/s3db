<?php
/*
homepage: http://arc.semsol.org/
license:  http://arc.semsol.org/license

class:    ARC2 Remote RDF Store
author:   Benjamin Nowack
version:  2008-07-14 (Tweak: added "query_type" info to query result)
*/

ARC2::inc('Class');

class ARC2_RemoteStore extends ARC2_Class {

  function __construct($a = '', &$caller) {
    parent::__construct($a, $caller);
  }
  
  function ARC2_RemoteStore($a = '', &$caller) {
    $this->__construct($a, $caller);
  }

  function __init() {
    parent::__init();
  }

  /*  */

  function isSetUp() {
    return 1;
  }
  
  function setUp() {}
  
  /*  */
  
  function reset() {}
  
  function drop() {}
  
  function insert($doc, $g, $keep_bnode_ids = 0) {
    $doc = is_array($doc) ? $this->toTurtle($doc) : $doc;
    return $this->query('INSERT INTO <' . $g . '> { ' . $doc . ' }');
  }
  
  function delete($doc, $g) {
    if (!$doc) {
      return $this->query('DELETE FROM <' . $g . '>');
    }
    else {
      $doc = is_array($doc) ? $this->toTurtle($doc) : $doc;
      return $this->query('DELETE FROM <' . $g . '> { ' . $doc . ' }');
    }
  }
  
  function replace($doc, $g, $doc_2) {
    return array($this->delete($doc, $g), $this->insert($doc_2, $g));
  }
  
  /*  */
  
  function query($q, $result_format = '', $src = '', $keep_bnode_ids = 0, $log_query = 0) {
    if ($log_query) $this->logQuery($q);
    ARC2::inc('SPARQLPlusParser');
    $p = & new ARC2_SPARQLPlusParser($this->a, $this);
    $p->parse($q, $src);
    $infos = $p->getQueryInfos();
    $qt = $infos['query']['type'];
    $t1 = ARC2::mtime();
    $r = array('query_type' => $qt, 'result' => $this->runQuery($q, $qt));
    $t2 = ARC2::mtime();
    $r['query_time'] = $t2 - $t1;
    /* query result */
    if ($result_format == 'raw') {
      return $r['result'];
    }
    if ($result_format == 'rows') {
      return $r['result']['rows'] ? $r['result']['rows'] : array();
    }
    if ($result_format == 'row') {
      return $r['result']['rows'] ? $r['result']['rows'][0] : array();
    }
    return $r;
  }

  function runQuery($q, $qt = '') {
    /* ep */
    $ep = $this->v('remote_store_endpoint', 0, $this->a);
    if (!$ep) return false;
    /* http verb */
    $mthd = in_array($qt, array('load', 'insert', 'delete')) ? 'POST' : 'GET';
    /* reader */
    ARC2::inc('Reader');
    $reader =& new ARC2_Reader($this->a, $this);
    $reader->setAcceptHeader('Accept: */*');
    if ($mthd == 'GET') {
      $url = $ep;
      $url .= strpos($ep, '?') ? '&' : '?';
      $url .= 'query=' . urlencode($q);
    }
    else {
      $url = $ep;
      $reader->setHTTPMethod($mthd);
      $reader->setCustomHeaders("Content-Type: application/x-www-form-urlencoded");
      $reader->setMessageBody('query=' . rawurlencode($q));
    }
    $reader->activate($url);
    $format = $reader->getFormat();
    $resp = '';
    while ($d = $reader->readStream()) {
      $resp .= $d;
    }
    $reader->closeStream();
    if ($ers = $reader->getErrors()) return array('errors' => $ers);
		$mappings = array('rdfxml' => 'RDFXML', 'sparqlxml' => 'SPARQLXMLResult', 'turtle' => 'Turtle');
    if (!$format || !isset($mappings[$format])) {
      return $resp;
      //return $this->addError('No parser available for "' . $format . '" SPARQL result');
    }
    /* format parser */
    $suffix = $mappings[$format] . 'Parser';
    ARC2::inc($suffix);
    $cls = 'ARC2_' . $suffix;
    $parser =& new $cls($this->a, $this);
    $parser->parse($ep, $resp);
    /* ask */
    if ($qt == 'ask') return $parser->getBoolean();
    /* select */
    if ($qt == 'select') return array('rows' => $parser->getRows(), 'variables' => $parser->getVariables());
    /* any other */
    return $parser->getSimpleIndex(0);
  }
  
  /*  */
  
  function optimizeTables() {}
  
}
