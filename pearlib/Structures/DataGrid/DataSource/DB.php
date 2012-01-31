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
// | Author: Andrew Nagy <asnagy@webitecture.org>                         |
// |         Olivier Guilyardi <olivier@samalyse.com>                     |
// +----------------------------------------------------------------------+
//
// $Id $

require_once 'Structures/DataGrid/DataSource/Array.php';

/**
 * PEAR::DB Data Source Driver
 *
 * This class is a data source driver for the PEAR::DB::DB_Result object
 *
 * @version  $Revision: 1.11 $
 * @author   Andrew S. Nagy <asnagy@webitecture.org>
 * @author   Olivier Guilyardi <olivier@samalyse.com> 
 * @access   public
 * @package  Structures_DataGrid
 * @category Structures
 */
class Structures_DataGrid_DataSource_DB extends Structures_DataGrid_DataSource
{   
    /**
     * Reference to the DB_Result object
     *
     * @var object DB_Result
     * @access private
     */
    var $_result;

    /**
     * Constructor
     *
     * @access public
     */
    function Structures_DataGrid_DataSource_DB()
    {
        parent::Structures_DataGrid_DataSource();
    }
  
    /**
     * Bind
     *
     * @param   object DB_Result    The result object to bind
     * @access  public
     * @return  mixed               True on success, PEAR_Error on failure
     */
    function bind(&$result, $options=array())
    {
        if ($options) {
            $this->setOptions($options); 
        }
        
        if (strtolower(get_class($result)) == 'db_result') { 
            $this->_result =& $result;
            return true;
        } else {
            return new PEAR_Error('The provided source must be a DB_Result');
        }
    }

    /**
     * Fetch
     *
     * @param   integer $offset     Offset (starting from 0)
     * @param   integer $limit      Limit
     * @param   string  $sortField  Field to sort by
     * @param   string  $sortDir    Sort direction : 'ASC' or 'DES     
     * @access  public
     * @return  array       The 2D Array of the records
     */
    function &fetch($offset=0, $limit=null, $sortField=null, $sortDir='ASC')
    {
        $recordSet = array();

        // Fetch the Data
        if ($numRows = $this->_result->numRows()) {
            while ($record = $this->_result->fetchRow(DB_FETCHMODE_ASSOC)) {
                $recordSet[] = $record;
            }
        }

        // Determine fields to render
        if (!$this->_options['fields']) {
            $this->setOptions(array('fields' => array_keys($recordSet[0])));
        }                
        
        // Limit and Sort the Data
        $recordSet =& Structures_DataGrid_DataSource_Array::staticFetch(
                          $recordSet, $this->_options['fields'], $offset, 
                          $limit, $sortField, $sortDir);

        return $recordSet;
    }

    /**
     * Count
     *
     * @access  public
     * @return  int         The number or records
     */
    function count()
    {
        return $this->_result->numRows();
    }
    
    /**
     * This should not be used due to performance issues, but is available for
     * compatability.
     * 
     * @access  public
     * @param   string  $sortField  Field to sort by
     * @param   string  $sortDir    Sort direction : 'ASC' or 'DESC'
     */
    function sort($sortField, $sortDir)
    {
        return new PEAR_Error('Cannot sort a DB_Result Object');
    }


}
?>
