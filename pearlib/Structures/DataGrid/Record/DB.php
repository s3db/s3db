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
// $Id: DB.php,v 1.8 2005/01/10 15:15:49 asnagy Exp $

require_once 'Structures/DataGrid/Record.php';

/**
 * Structures_DataGrid_Record_DB Class
 *
 * @version  $Revision: 1.8 $
 * @author   Andrew S. Nagy <asnagy@webitecture.org>
 * @access   public
 * @package  Structures_DataGrid
 * @category Structures
 */
class Structures_DataGrid_Record_DB extends Structures_DataGrid_Record
{
    /**
     * Constructor
     *
     * Builds the record.  Data must be of type DB::DB_Result.
     *
     * @access  public
     * @param   object DB_Result    $data   The DB_Result object. Optional.
     */
    function Structures_DataGrid_Record_DB($data = null)
    {
        $result = $this->setRecord($data);
        if (PEAR::isError($result)) {
            PEAR::raiseError($result->toString());
        }
    }

    /**
     * Set Record
     *
     * Converts the DB_Record object into a format that the DataGrid object
     * understands.
     *
     * @access  public
     * @param   object DB_Result    $data   The DB_Result object.
     */
    function setRecord($data)
    {
        if (strtolower(get_class($data)) == 'db_result') {
            parent::setRecord($data->fetchRow(DB_FETCHMODE_ASSOC));
        } else {
            return new PEAR_Error('Invalid data type. Data must be a DB_Result record');
        }
    }
}

?>
