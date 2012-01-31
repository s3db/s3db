<?php
/* vim: set expandtab tabstop=4 shiftwidth=4: */
// +----------------------------------------------------------------------+
// | PHP version 4                                                        |
// +----------------------------------------------------------------------+
// | Copyright (c) 1997-2005 The PHP Group                                |
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
// +----------------------------------------------------------------------+
//
// $Id: Record.php,v 1.3 2005/01/10 15:15:48 asnagy Exp $

/**
 * Structures_DataGrid_Record Class
 *
 * This class represents one record for the DataGrid.  All data is stored
 * as an Associative Array with the keys as the Column Name and the value
 * as the cell value.  Other data types can be added by the extended classes, 
 * such as a DB_Result or a DB_DataObject.
 *
 * @version  $Revision: 1.3 $
 * @author   Andrew S. Nagy <asnagy@webitecture.org>
 * @access   public
 * @package  Structures_DataGrid
 * @category Structures
 */
class Structures_DataGrid_Record
{
    var $_record = array();

    /**
     * Constructor
     *
     * Builds the record.  Accepts the data as an Array.
     *
     * @access  public
     * @todo    Allow more data types.
     */
    function Structures_DataGrid_Record($data = null)
    {
        if ($data != null) {
            $this->setRecord($data);
        }
    }

    /**
     * Get Value
     *
     * Retrieves the value for the column name specified
     *
     * @access  public
     * @param   string     $key    The name of the column to retrieve the value
     *                             for.
     * @return  void
     */
    function getValue($key)
    {
        return $this->_record[$key];
    }

    /**
     * Set Value
     *
     * Sets the value for the column name specified
     *
     * @access  public
     * @param   string     $key    The name of the column to work on.
     * @param   string     $data   The data to set the specific cell to.
     * @return  void
     */
    function setValue($key, $data)
    {
        $this->_record[$key] = $data;
    }

    /**
     * Get Record
     *
     * Retrieves the current record as an array.
     *
     * @access  public
     * @return  array            The current record as an array.
     */    
    function getRecord()
    {
        return $this->_record;
    }

    /**
     * Set Record
     *
     * Sets the current record.  The record must be defined as an array.
     *
     * @access  public
     * @return  mixed              Returns true if successful, otherwise a
     *                             PEAR_Error object is returned.
     */
    function setRecord($data)
    {
        if (is_array($data)) {
            $this->_record = $data;
            return true;
        } else {
            return new PEAR_Error('Invalid data type.  Must be an array');
        }
    }
}

?>
