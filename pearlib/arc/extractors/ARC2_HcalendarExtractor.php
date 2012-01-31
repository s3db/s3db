<?php
/*
homepage: http://arc.semsol.org/
license:  http://arc.semsol.org/license

class:    ARC2 hCalendar Extractor
author:   Benjamin Nowack
version:  2008-05-27
*/

ARC2::inc('MicroformatsExtractor');

class ARC2_HcalendarExtractor extends ARC2_MicroformatsExtractor {

  function __construct($a = '', &$caller) {
    parent::__construct($a, $caller);
  }
  
  function ARC2_HcalendarExtractor($a = '', &$caller) {
    $this->__construct($a, $caller);
  }

  function __init() {
    parent::__init();
    $this->terms = array(
      /* root  */
      'vevent',
      /* skipped */
      '#class', '#uid',
      /* props */
      'category', 'description', 'dtend', 'dtstamp', 'dtstart', 'duration', 'location', 'status', 
      'summary', 'url', 'last-modified',
    );
    $this->a['ns']['vcard'] = 'http://www.w3.org/2001/vcard-rdf/3.0#';
    $this->a['ns']['dct'] = 'http://purl.org/dc/terms/';
    $this->a['ns']['dc'] = 'http://purl.org/dc/elements/1.1/';
  }

  /*  */
  
  function extractRDF() {
    foreach ($this->nodes as $n) {
      if (!$vals = $this->v('class m', array(), $n['a'])) continue;
      if (!in_array('vevent', $vals)) continue;
      /* vevent  */
      $t_vals = array(
        's' => $this->getResID($n, 'vevent') . '_event',
        's_type' => $this->a['ns']['cal'].'Vevent',
      );
      $t = ' ?s a ?s_type . ';
      /* properties */
      foreach ($this->terms as $term) {
        $m = 'extract' . $this->camelCase($term);
        if (method_exists($this, $m)) {
          list ($t_vals, $t) = $this->$m($n, $t_vals, $t);
        }
      }
      /* result */
      $doc = $this->getFilledTemplate($t, $t_vals, $n['doc_base']);
      $this->addTs(ARC2::getTriplesFromIndex($doc));
    }
  }
  
  /*  */
  
  function extractSimple($n, $t_vals, $t, $cls, $prop = '') {
    if ($sub_ns = $this->getSubNodesByClass($n, $cls)) {
      $tc = 0;
      $prop = $prop ? $prop : 'cal:' . $cls;
      foreach ($sub_ns as $sub_n) {
        $var = $this->normalize($cls) . '_'. $tc;
        if ($t_vals[$var] = $this->getNodeContent($sub_n)) {
          $t .= '?s ' . $prop . ' ?' . $var . ' . ';
          $tc++;
        }
      }
    }
    return array($t_vals, $t);
  }

  /*  */

  function extractSummary($n, $t_vals, $t) {
    return $this->extractSimple($n, $t_vals, $t, 'summary');
  }
  
  /*  */

  function extractCategory($n, $t_vals, $t) {
    if ($sub_ns = $this->getSubNodesByClass($n, 'category')) {
      $tc = 0;
      foreach ($sub_ns as $sub_n) {
        list ($sub_t_vals, $sub_t) = $this->extractRelTagCategory($sub_n, $t_vals, $t, $tc);
        if ($sub_t != $t) {
          list ($t_vals, $t) = array($sub_t_vals, $sub_t);
        }
        else {
          list ($t_vals, $t) = $this->extractPlainCategory($sub_n, $t_vals, $t, $tc);
        }
        $tc++;
      }
    }
    return array($t_vals, $t);
  }

  function extractRelTagCategory($n, $t_vals, $t, $tc) {
    $href = $this->v('href uri', '', $n['a']);
    $rels = $this->v('rel m', array(), $n['a']);
    if ($href && in_array('tag', $rels)) {
      $parts = preg_match('/^(.*\/)([^\/]+)\/?$/', $href, $m) ? array('space' => $m[1], 'tag' => rawurldecode($m[2])) : array('space' => '', 'tag' => '');
      if ($tag = $parts['tag']) {
        $t_vals['cat_' . $tc] = $tag;
        $t .= '?s dc:subject ?cat_' . $tc . ' . ';
        //$t .= '?s vcard:CATEGORIES ?tag_' . $tc . ' . ';
      }
    }
    return array($t_vals, $t);
  }
  
  function extractPlainCategory($n, $t_vals, $t, $tc) {
    if ($tag = $this->getNodeContent($n)) {
      $t_vals['cat_' . $tc] = $tag;
      $t .= '?s dc:subject ?cat_' . $tc . ' . ';
      //$t .= '?s vcard:CATEGORIES ?tag_' . $tc . ' . ';
    }
    return array($t_vals, $t);
  }
  
  /*  */
  
  function extractDescription($n, $t_vals, $t) {
    return $this->extractSimple($n, $t_vals, $t, 'description');
  }

  /*  */

  function extractTz($n, $t_vals, $t) {/* e.g. -05:00 */
    return $this->extractSimple($n, $t_vals, $t, 'tz');
  }

  /*  */

  function extractUid($n, $t_vals, $t) {
    return $this->extractSimple($n, $t_vals, $t, 'uid');
  }

  /*  */

  function extractUrl($n, $t_vals, $t) {
    if ($sub_n = $this->getSubNodeByClass($n, 'url')) {
      if ($t_vals['url'] = $this->v('href uri', '', $sub_n['a'])) {
        $t .= '?s cal:url ?url . ';
      }
    }
    return array($t_vals, $t);
  }
  
  /*  */
  
}
