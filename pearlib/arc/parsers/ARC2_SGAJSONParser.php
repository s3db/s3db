<?php
/*
homepage: http://arc.semsol.org/
license:  http://arc.semsol.org/license

class:    ARC2 SG API JSON Parser
author:   Benjamin Nowack
version:  2008-07-15
*/

ARC2::inc('RDFParser');

class ARC2_SGAJSONParser extends ARC2_RDFParser {

  function __construct($a = '', &$caller) {
    parent::__construct($a, $caller);
  }
  
  function ARC2_SGAJSONParser($a = '', &$caller) {
    $this->__construct($a, $caller);
  }

  function __init() {/* reader */
    parent::__init();
    $this->rdf = 'http://www.w3.org/1999/02/22-rdf-syntax-ns#';
    $this->nsp = array($this->rdf => 'rdf');
  }
  
  /*  */

  function x($re, $v, $options = 'si') {
    while (preg_match('/^\s*(\/\*.*\*\/)(.*)$/Usi', $v, $m)) {/* comment removal */
      $v = $m[2];
    }
    $this->unparsed_code = (strlen($this->unparsed_code) > strlen($v)) ? $v : $this->unparsed_code;
    return ARC2::x($re, $v, $options);
  }

  function parse($path, $data = '') {
    $this->state = 0;
    /* reader */
    if (!$this->v('reader')) {
      ARC2::inc('Reader');
      $this->reader = & new ARC2_Reader($this->a, $this);
    }
    $this->reader->setAcceptHeader('Accept: application/json; q=0.9, */*; q=0.1');
    $this->reader->activate($path, $data);
    $this->x_base = isset($this->a['base']) && $this->a['base'] ? $this->a['base'] : $this->reader->base;
    /* parse */
    $doc = '';
    while ($d = $this->reader->readStream()) {
      $doc .= $d;
    }
    $this->reader->closeStream();
    $doc = preg_replace('/^[^\{]*(.*\})[^\}]*$/is', '\\1', $doc);
    $this->unparsed_code = $doc;
    list($this->struct, $rest) = $this->extractObject($doc);
    return $this->done();
  }
  
  /*  */
  
  function extractObject($v) {
    if (function_exists('json_decode')) return array(json_decode($v, 1), '');
    $r = array();
    if ($sub_r = $this->x('\{', $v)) {
      $v = $sub_r[1];
      while ((list($sub_r, $v) = $this->extractEntry($v)) && $sub_r) {
        $r[$sub_r['key']] = $sub_r['value'];
      }
      if ($sub_r = $this->x('\}', $v)) $v = $sub_r[1];
    }
    elseif ($sub_r = $this->x('\[', $v)) {
      $v = $sub_r[1];
      while ((list($sub_r, $v) = $this->extractValue($v)) && $sub_r) {
        $r[] = $sub_r;
      }
      if ($sub_r = $this->x('\]', $v)) $v = $sub_r[1];
    }
    elseif ((list($sub_r, $v) = $this->extractValue($v)) && $sub_r) {
      $r = $sub_r;
    }
    return array($r, $v);
  }
  
  function extractEntry($v) {
    if ($r = $this->x('\,', $v)) $v = $r[1];
    /* k */
    if ($r = $this->x('\"([^\"]+)\"\s*\:', $v)) {
      $k = $r[1];
      $sub_v = $r[2];
      if (list($sub_r, $sub_v) = $this->extractObject($sub_v)) {
        return array(
          array('key' => $k, 'value' => $sub_r),
          $sub_v
        );
      }
    }
    return array(0, $v);
  }
  
  function extractValue($v) {
    if ($r = $this->x('\,', $v)) $v = $r[1];
    if ($sub_r = $this->x('\"', $v)) {
      $rest = $sub_r[1];
      if (preg_match('/^([^\x5c]*|.*[^\x5c]|.*\x5c{2})\"(.*)$/sU', $rest, $m)) {
        return array($m[1], $m[2]);
      }
    }
    return array(0, $v);
  }
  
  /*  */
  
  function done() {
    $this->extractRDF();
  }
  
  function extractRDF() {
    $s = $this->getContext();
    $os = $this->getURLs($this->struct);
    foreach ($os as $o) {
      if ($o != $s) $this->addT($s, 'http://www.w3.org/2000/01/rdf-schema#seeAlso', $o, 'uri', 'uri');
    }
  }
  
  function getContext() {
    if (!isset($this->struct['canonical_mapping'])) return '';
    foreach ($this->struct['canonical_mapping'] as $k => $v) return $v;
  }
  
  function getURLs($struct) {
    $r =array();
    if (is_array($struct)) {
      foreach ($struct as $k => $v) {
        if (preg_match('/^http:\/\//', $k) && !in_array($k, $r)) $r[] = $k;
        $sub_r = $this->getURLs($v);
        foreach ($sub_r as $sub_v) {
          if (!in_array($sub_v, $r)) $r[] = $sub_v;
        }
      }
    }
    elseif (preg_match('/^http:\/\//', $struct) && !in_array($struct, $r)) {
      $r[] = $struct;
    }
    return $r;
  }
  
  /*  */

  function getTriples() {
    return $this->v('triples', array());
  }
  
  function countTriples() {
    return $this->t_count;
  }

  function addT($s = '', $p = '', $o = '', $s_type = '', $o_type = '', $o_dt = '', $o_lang = '') {
    if (!($s && $p && $o)) return 0;
    //echo "-----\nadding $s / $p / $o\n-----\n";
    $t = array('s' => $s, 'p' => $p, 'o' => $o, 's_type' => $s_type, 'o_type' => $o_type, 'o_datatype' => $o_dt, 'o_lang' => $o_lang);
    if ($this->skip_dupes) {
      $h = md5(print_r($t, 1));
      if (!isset($this->added_triples[$h])) {
        $this->triples[$this->t_count] = $t;
        $this->t_count++;
        $this->added_triples[$h] = true;
      }
    }
    else {
      $this->triples[$this->t_count] = $t;
      $this->t_count++;
    }
  }

  /*  */
  
  
  

  /*  */
  
}
