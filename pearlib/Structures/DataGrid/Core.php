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
// $Id: Core.php,v 1.26 2005/01/10 15:15:48 asnagy Exp $

require_once 'Structures/DataGrid/Column.php';
require_once 'Structures/DataGrid/Record.php';

/**
 * Structures_DataGrid_Core Class
 *
 * The Core class implements the Core functionality of the DataGrid.
 * It offers the paging and sorting methods as well as the record and column
 * management methods.
 *
 * @version  $Revision: 1.26 $
 * @author   Andrew S. Nagy <asnagy@webitecture.org>
 * @access   public
 * @package  Structures_DataGrid
 * @category Structures
 */
class Structures_DataGrid_Core
{
    /**
     * Array of columns.  Columns are defined as a DataGridColumn object.
     * @var array
     */
    var $columnSet = array();

    /**
     * Array of records.  Records are defined as a DataGridRecord object.
     * @var array
     */
    var $recordSet = array();

    /**
     * The Data Source Driver object
     * @var object Structures_DataGrid_DataSource
     */
    var $_dataSource;    
    
    /**
     * An array of fields to sort by.  Each field is an array of the field name
     * and the direction, either ASC or DESC.
     * @var array
     */
    var $sortArray;

    /**
     * Limit of records to show per page.
     * @var string
     */
    var $rowLimit;

    /**
     * The current page to show.
     * @var string
     */
    var $page;

    /**
     * GET/POST/Cookie parameters prefix
     * @var string
     */
     var $requestPrefix;    

    /**
     * Constructor
     *
     * Creates default table style settings
     *
     * @param  string   $limit  The row limit per page.
     * @param  string   $page   The current page viewed.
     * @access public
     */
    function Structures_DataGrid_Core($limit = null, $page = 1)
    {
        $this->rowLimit = $limit;
        $this->page = $page;
        
        // Automatic handling of GET/POST/COOKIE variables
        $this->_parseHttpRequest();
    }

    /**
     * Retrieves the current page number when paging is implemented
     *
     * @return string    the current page number
     * @access public
     */
    function getCurrentPage()
    {
        return $this->page;
    }

    /**
     * Define the current page number.  This is used when paging is implemented
     *
     * @access public
     * @param  string    $page       The current page number.
     */
    function setCurrentPage($page)
    {
        $this->page = $page;
    }

    /**
     * If you need to change the request variables, you can define a prefix.
     * This is extra useful when using multiple datagrids.
     *
     * @access  public
     * @param   string $prefix      The prefix to use on request variables;
     */
    function setRequestPrefix($prefix)
    {
        $this->requestPrefix = $prefix;
        
        // Automatic handling of GET/POST/COOKIE variables
        $this->_parseHttpRequest();
    }    
    
    /**
     * Adds a DataGridColumn object to this DataGrid object
     *
     * @access  public
     * @param   object Structures_DataGrid_Column   $column     The column
     *          object to add. This object should be a
     *          Structures_DataGrid_Column object.
     * @return  bool    True if successful, otherwise false.
     */
    function addColumn($column)
    {
        if (is_a($column, 'structures_datagrid_column')) {
            $this->columnSet = array_merge($this->columnSet, array($column));
            return true;
        } else {
            return false;
        }
    }

    /**
     * A simple way to add a recod set to the datagrid
     *
     * @access  public
     * @param   mixed   $rs         The record set in any of the supported data
     *                              source types
     * @param   array   $options    Optional. The options to be used for the
     *                              data source
     * @param   string  $type       Optional. The data source type
     * @return  bool                True if successful, otherwise PEAR_Error.
     */
    function bind($rs, $options = array(), $type = null)
    {
        require_once 'Structures/DataGrid/DataSource.php';
        
        $source =& Structures_DataGrid_DataSource::create($rs, $options, $type);
        if (!PEAR::isError($source)) {
            return $this->bindDataSource($source);
        } else {
            return $source;
        }
    }

    /**
     * Allows binding to a data source driver.
     *
     * @access  public
     * @param   mixed   $source     The data source driver object
     * @return  mixed               True if successful, otherwise PEAR_Error
     */
    function bindDataSource(&$source)
    {
        if (is_subclass_of($source, 'structures_datagrid_datasource')) {
            $this->_dataSource =& $source;
        } else {
            return new PEAR_Error('Invalid data source type, ' . 
                                  'must be a valid data source driver class');
        }
        
        return true;
    }
    
    /**
     * Adds a DataGrid_Record object to this DataGrid object
     *
     * @access  public
     * @param   object Structures_DataGrid_Record   $record     The record
     *          object to add. This object must be a Structures_DataGrid_Record
     *          object.
     * @return  bool            True if successful, otherwise false.
     */
    function addRecord($record)
    {
        if (is_a($record, 'structures_datagrid_record')) {
            $this->recordSet = array_merge($this->recordSet,
                                           array($record->getRecord()));
            return true;
        } else {
            return new PEAR_Error('Not a valid DataGrid Record');
        }
    }

    /**
     * Drops a DataGrid_Record object from this DataGrid object
     *
     * @access  public
     * @param   object Structures_DataGrid_Record   $record     The record
     *          object to drop. This object must be a Structures_DataGrid_Record
     *          object.
     * @return void
     */
    function dropRecord($record)
    {
        unset($this->recordSet[$record->getRecord()]);
    }

    /**
     * Sorts the records by the defined field.
     * Do not use this method if data is coming from a database as sorting
     * is much faster coming directly from the database itself.
     *
     * @access  public
     * @param   string $sortBy      The field to sort the record set by.
     * @param   string $direction   The sort direction, either ASC or DESC.
     * @return  void
     */
    function sortRecordSet($sortBy, $direction = 'ASC')
    {
        if ($this->_dataSource) {
            $this->_dataSource->sort($sortBy, $direction);
        } else {
            usort($this->recordSet, array($this, '_sort'));
        }
        $this->sortArray = array($sortBy, $direction);
    }
    
    function _sort($a, $b, $i = 0)
    {
        //$bool = strnatcmp($a[$this->sortArray[0]], $b[$this->sortArray[0]]);
        $bool = strnatcasecmp($a[$this->sortArray[0]], $b[$this->sortArray[0]]);
        
        if ($this->sortArray[1] == 'DESC') {
            $bool = $bool * -1;
        }
        
        return $bool;
    }
    
    /**
     * Parse HTTP Request parameters
     *
     * @access  private
     * @return  array      Associative array of parsed arguments, each of these 
     *                     defaulting to null if not found. 
     */
    function _parseHttpRequest()
    {
        // Determine parameter prefix
        if ((isset($this->requestPrefix)) && ($this->requestPrefix != '')) {
            $prefix = $this->requestPrefix;
        } else {
            $prefix = null;
        }
        
        // Add values to arguments
        if (isset($_REQUEST[$prefix . 'page'])) {
            $this->page = $_REQUEST[$prefix . 'page'];
        }
        
        if (isset($_REQUEST[$prefix . 'orderBy'])) {
            $this->sortArray[0] = $_REQUEST[$prefix . 'orderBy'];
        }
        
        if (isset($_REQUEST[$prefix . 'direction'])) {
            $this->sortArray[1] = $_REQUEST[$prefix . 'direction'];
        }
    }     
}

?>
