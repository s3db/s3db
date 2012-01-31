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
// $Id: DataObject.php,v 1.10 2005/01/10 15:17:34 asnagy Exp $

require_once 'Structures/DataGrid/Record.php';

/**
 * Structures_DataGrid_Record_DataObject Class
 *
 * @version  $Revision: 1.10 $
 * @author   Andrew S. Nagy <asnagy@webitecture.org>
 * @access   public
 * @package  Structures_DataGrid
 * @category Structures
 */
class Structures_DataGrid_Record_DataObject extends Structures_DataGrid_Record
{
    /**
     * Constructor
     *
     * Builds the record if specified. The data must be of type DB_DataObject.
     *
     * @access  public
     * @see DB::DB_DataObject
     */
    function Structures_DataGrid_Record_DataObject($data = null)
    {
        $result = $this->setRecord($data);
        if (PEAR::isError($result)) {
            PEAR::raiseError($result->toString());
        }
    }

    function setRecord($data)
    {
        if (strtolower(is_subclass_of($data, 'db_dataobject'))) {
            parent::setRecord($data->toArray());
        } else {
            return new PEAR_Error('Invalid data type. ' . 
                                  'Data must be of type DB_DataObject');
        }
    }
}

?>
