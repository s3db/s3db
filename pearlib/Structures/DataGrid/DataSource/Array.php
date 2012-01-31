<?php
/* vim: set expandtab tabstop=4 shiftwidth=4: */
// +----------------------------------------------------------------------+
// | PHP version 4                                                        |
// +----------------------------------------------------------------------+
// | Copyright (c) 1997-2004 The PHP Group                                |
// +----------------------------------------------------------------------+
// | This source file is subject to version 2.0 of the PHP license,       |
// | that is bundled with this package in the file LICENSE, and is        |
// | available through the world-wide-web at                              |
// | http://www.php.net/license/2_02.txt.                                 |
// | If you did not receive a copy of the PHP license and are unable to   |
// | obtain it through the world-wide-web, please send a note to          |
// | license@php.net so we can mail you a copy immediately.               |
// +----------------------------------------------------------------------+
// | Author: Olivier Guilyardi <olivier@samalyse.com>                     |
// |         Andrew Nagy <asnagy@webitecture.org>                         |
// +----------------------------------------------------------------------+
//
// $Id: Array.php,v 1.10 2005/01/05 16:12:05 asnagy Exp $

require_once 'Structures/DataGrid/DataSource.php';

/**
 * Array Data Source Driver
 *
 * This class is a data source driver for a 2D Array
 *
 * @version  $Revision: 1.10 $
 * @author   Olivier Guilyardi <olivier@samalyse.com>
 * @author   Andrew Nagy <asnagy@webitecture.org>
 * @access   public
 * @package  Structures_DataGrid
 * @category Structures
 */
class Structures_DataGrid_DataSource_Array extends Structures_DataGrid_DataSource
{
    /**
     * The array
     *
     * @var array
     * @access private
     */
    var $_ar;
     
    function Structures_DataGrid_DataSource_Array()
    {
        parent::Structures_DataGrid_DataSource();
    }

    /**
     * Bind
     *
     * @param   array $ar       The result object to bind
     * @access  public
     * @return  mixed           True on success, PEAR_Error on failure
     */    
    function bind($ar, $options=array())
    {
        if ($options) {
            $this->setOptions($options); 
        } 
               
        if (is_array($ar)) {
            $this->_ar = $ar;
            return true;
        } else {
            return new PEAR_Error('The provided source must be an array');
        }
    }

    /**
     * Count
     *
     * @access  public
     * @return  int         The number or records
     */
    function count()
    {
        return count($this->_ar);
    }

    /**
     * Fetch
     *
     * @param   integer $offset     Limit offset (starting from 0)
     * @param   integer $len        Limit length
     * @param   string  $sortField  Field to sort by
     * @param   string  $sortDir    Sort direction : 'ASC' or 'DESC'     
     * @access  public
     * @return  array       The 2D Array of the records
     */
    function &fetch($offset=0, $len=null, $sortField='', $sortDir='ASC')
    {
        if ($this->_ar && !$this->_options['fields']) {
            $this->setOptions(array('fields' => array_keys($this->_ar[0])));
        }
        $records =& $this->staticFetch($this->_ar, $this->_options['fields'],
                                       $offset, $len, $sortField, $sortDir);
        return $records;
    }
    
    /**
     * Reusable static fetch method
     * 
     * Since many drivers end up needing this array driver's features,
     * the following method is provided in order to avoid subclassing
     * this class.
     * 
     * @param   integer $offset     Limit offset (starting from 0)
     * @param   integer $len        Limit length
     * @param   string  $sortField  Field to sort by
     * @param   string  $sortDir    Sort direction : 'ASC' or 'DESC'
     * @static
     */
    function &staticFetch($ar, $fieldList, $offset=0, $len=null, 
                          $sortField=null, $sortDir='ASC')
    {
        // sorting
        if ($sortField) {
            Structures_DataGrid_DataSource_Array::sort($sortField, $sortDir,
                                                       $ar);
        }
        
        // slicing
        if (is_null($len)) {
            $slice = array_slice($ar, $offset);
        } else {
            $slice = array_slice($ar, $offset, $len);
        }

        // Filter out fields that are to not be rendered
        //
        // With the new array_intersect_key() the following would be :
        // $records = array_intersect_key($slice, array_flip ($fieldList));
        // One line... And faster... But this function is cvs-only.
        $records = array();
        foreach ($slice as $rec) {
            $buf = array();
            foreach ($rec as $key => $val) {
                if (in_array($key, $fieldList)) {
                    $buf[$key] = $val;
                }
            }
            $records[] = $buf;
        }
        
        return $records;
    }
    
    /**
     * Sorts the array.  Can be called statically if the ar parameter is
     * specified
     * 
     * @access  public
     * @param   string  $sortField  Field to sort by
     * @param   string  $sortDir    Sort direction : 'ASC' or 'DESC'
     * @param   array   $ar         The array to sort (Used for static calls)
     */
    function sort($sortField, $sortDir, $ar = null)
    {
        if ($ar == null) {
            $ar = $this->_ar;
        }
        
        $numRows = count($ar);
        $sortAr = array();
        for ($i = 0; $i < $numRows; $i++) {
            $sortAr[$i] = $ar[$i][$sortField];
        }
        $sortDir = strtoupper($sortDir) == 'ASC' ? SORT_ASC : SORT_DESC;
        array_multisort($sortAr, $sortDir, $ar);
    }
}

?>