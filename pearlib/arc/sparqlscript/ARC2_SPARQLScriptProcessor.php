<?php
/*
homepage: ARC or plugin homepage
license:  http://arc.semsol.org/license

class:    ARC2 SPARQLScript Processor
author:   
version:  2008-07-15 (Addition: Placeholders, Placeholder property paths, Var assignments, FORBlock, Output templates)
*/

ARC2::inc('Class');

class ARC2_SPARQLScriptProcessor extends ARC2_Class {

  function __construct($a = '', &$caller) {
    parent::__construct($a, $caller);
  }
  
  function ARC2_SPARQLScriptProcessor ($a = '', &$caller) {
    $this->__construct($a, $caller);
  }

  function __init() {
    parent::__init();
    $this->env = array(
      'endpoint' => '',
      'vars' => array(),
      'output' => ''
    );
  }

  /*  */
  
  function processScript($s) {
    $r = array();
    $parser = $this->getParser();
    $parser->parse($s);
    $blocks = $parser->getScriptBlocks();
    if ($parser->getErrors()) return 0;
    foreach ($blocks as $block) {
      $sub_r = $this->processBlock($block);
      if ($this->getErrors()) return 0;
    }
  }

  /*  */
  
  function getParser() {
    ARC2::inc('SPARQLScriptParser');
    return new ARC2_SPARQLScriptParser($this->a, $this);
  }
  
  /*  */

  function replacePlaceholders($val) {
    while (preg_match('/\$\{([^\}]+)\}/', $val, $m)) {
      $val = str_replace('${' . $m[1] . '}', $this->getPlaceholderValue($m[1]), $val);
    }
    return $val;
  }
  
  function getPlaceholderValue($ph) {
    /* simple vars */
    if (isset($this->env['vars'][$ph])) return $this->env['vars'][$ph]['value'];
    /* GET/POST */
    if (preg_match('/^(GET|POST)\.(.*)$/i', $ph, $m) && isset(${'_' . $m[1]}[$m[2]])) return ${'_' . $m[1]}[$m[2]];
    /* NOW */
    if (preg_match('/^NOW(.*)$/', $ph, $m)) {
      $r = array(
        'y' => date('Y'),
        'mo' => date('m'),
        'd' => date('d'),
        'h' => date('H'),
        'mi' => date('i'),
        's' => date('s')
      );
      if (preg_match('/(\+|\-)\s*([0-9]+)(y|mo|d|h|mi|s)/is', trim($m[1]), $m2)) {
        eval('$r[$m2[3]] ' . $m2[1] . '= (int)' . $m2[2] . ';');
      }
      $uts = mktime($r['h'], $r['mi'], $r['s'], $r['mo'], $r['d'], $r['y']);
      $uts -= date('Z', $uts); /* timezone offset */
      return date('Y-m-d\TH:i:s\Z', $uts);
    }
    /* property */
    if (preg_match('/^([^\.]+)\.(.+)$/', $ph, $m)) {
      list($var, $path) = array($m[1], $m[2]);
      if (isset($this->env['vars'][$var])) {
        return $this->getPropertyValue($this->env['vars'][$var], $path);
      }
    }
    return '';
  }
  
  function getPropertyValue($obj, $path) {
    $val = $obj['value'];
    /* reserved */
    if ($path == 'size') {
      if ($obj['value_type'] == 'rows') return count($val);
      if ($obj['value_type'] == 'literal') return strlen($val);
    }
    /* struct */
    if (is_array($val)) {
      if (isset($val[$path])) return $val[$path];
      if (preg_match('/^([^\.]+)\.(.+)$/', $path, $m)) {
        list($var, $path) = array($m[1], $m[2]);
        if (isset($val[$var])) {
          return $this->getPropertyValue(array('value' => $val[$var]), $path);
        }
        return '';
      }
    }
    return '';
  }
  
  /*  */

  function processBlock($block) {
    $type = $block['type'];
    $m = 'process' . $this->camelCase($type) . 'Block';
    if (method_exists($this, $m)) {
      return $this->$m($block);
    }
    return $this->addError('Unsupported block type "' . $type . '"');
  }

  /*  */
  
  function processEndpointDeclBlock($block) {
    $this->env['endpoint'] = $block['endpoint'];
    return $this->env;
  }

  /*  */

  function processQueryBlock($block) {
    $ep_uri = $this->env['endpoint'];
    /* q */
    $q = 'BASE <' . $block['base']. '>';
    foreach ($block['prefixes'] as $k => $v) {
      $q .= "\n" . 'PREFIX ' . $k . ' <' . $v . '>';
    }
    $q .= "\n" . $block['query'];
    /* placeholders */
    $q = $this->replacePlaceholders($q);
    /* local store */
    if ((!$ep_uri || $ep_uri == ARC2::getScriptURI()) && ($this->v('sparqlscript_default_endpoint', '', $this->a) == 'local')) {
      $store = ARC2::getStore($this->a);/* @@todo error checking */
      return $store->query($q);
    }
    elseif ($ep_uri) {
      ARC2::inc('RemoteStore');
      $conf = array_merge($this->a, array('remote_store_endpoint' => $ep_uri));
      $store =& new ARC2_RemoteStore($conf, $this);
      return $store->query($q, '', $ep_uri);
    }
    else {
      return $this->addError("no store");
    }
  }

  /*  */

  function processAssignmentBlock($block) {
    $sub_type = $block['sub_type'];
    $m = 'process' . $this->camelCase($sub_type) . 'AssignmentBlock';
    if (!method_exists($this, $m)) return $this->addError('Unknown method "' . $m . '"');
    return $this->$m($block);
  }

  function processQueryAssignmentBlock($block) {
    $qr = $this->processQueryBlock($block['query']);
    $qt = $qr['query_type'];
    $vts = array('ask' => 'bool', 'select' => 'rows', 'desribe' => 'doc', 'construct' => 'doc');
    $r = array(
      'value_type' => isset($vts[$qt]) ? $vts[$qt] : $qt . ' result',
      'value' => ($qt == 'select') ? $qr['result']['rows'] : $qr['result'],
    );
    $this->env['vars'][$block['var']['value']] = $r;
  }
  
  function processStringAssignmentBlock($block) {
    $r = array('value_type' => 'literal', 'value' => $block['string']['value']);
    $this->env['vars'][$block['var']['value']] = $r;
  }
  
  function processVarAssignmentBlock($block) {
    if (isset($this->env['vars'][$block['var2']['value']])) {
      $this->env['vars'][$block['var']['value']] = $this->env['vars'][$block['var2']['value']];
    }
    else {
      $this->env['vars'][$block['var']['value']] = array('value_type' => 'undefined', 'value' => '');
    }
  }
  
  function processPlaceholderAssignmentBlock($block) {
    $ph_val = $this->replacePlaceholders('${' . $block['placeholder']['value'] . '}');
    $this->env['vars'][$block['var']['value']] = array('value_type' => 'undefined', 'value' => $ph_val);
  }
  
  /*  */
  
  function processIfblockBlock($block) {
    if ($this->testCondition($block['condition'])) {
      $blocks = $block['blocks'];
    }
    else {
      $blocks = $block['else_blocks'];
    }
    foreach ($blocks as $block) {
      $sub_r = $this->processBlock($block);
      if ($this->getErrors()) return 0;
    }
  }
  
  function testCondition($cond) {
    $ct = $cond['type'];
    $m = 'test' . $this->camelCase($cond['type']) . 'Condition';
    if (!method_exists($this, $m)) return $this->addError('Unknown method "' . $m . '"');
    return $this->$m($cond);
  }

  function testVarCondition($cond) {
    $r = 0;
    $vn = $cond['value'];
    if (isset($this->env['vars'][$vn])) $r = $this->env['vars'][$vn]['value'];
    $op = $this->v('operator', '', $cond);
    if ($op == '!') $r = !$r;
    return $r ? true : false;
  }
  
  function xtestExpressionCondition($cond) {
    print_r($cond);
    return false;
  }

  /*  */
  
  function processForblockBlock($block) {
    $set = $this->v($block['set'], array('value' => array()), $this->env['vars']);
    $entries = $set['value'];
    $iterator = $block['iterator'];
    $blocks = $block['blocks'];
    foreach ($entries as $entry) {
      $this->env['vars'][$iterator] = array('value' => $entry, 'value_type' => $set['value_type'] . ' entry');
      foreach ($blocks as $block) {
        $this->processBlock($block);
        if ($this->getErrors()) return 0;
      }
    }
  }
  
  /*  */

  function processLiteralBlock($block) {
    $val = $this->replacePlaceholders($block['value']);
    $this->env['output'] .= $val;
  }

  /*  */
  
}