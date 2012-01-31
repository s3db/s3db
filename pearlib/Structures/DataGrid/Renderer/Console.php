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
// $Id: Console.php,v 1.2 2005/01/10 15:15:49 asnagy Exp $

require_once 'Console/Table.php';

/**
 * Structures_DataGrid_Renderer_Console Class
 *
 * @version  $Revision: 1.2 $
 * @author   Andrew S. Nagy <asnagy@webitecture.org>
 * @access   public
 * @package  Structures_DataGrid
 * @category Structures
 */
class Structures_DataGrid_Renderer_Console
{
    /**
     * The Datagrid object to render
     * @var object Structures_DataGrid
     */
    var $_dg;
    
    /**
     * Use the table header
     * @var bool
     */
    var $header = true;

    /**
     * The console_table object
     * @var object Console_Table
     */
    var $_table;

    /**
     * A switch to determine the state of the table
     * @var bool
     */
    var $_rendered = false;
    
    /**
     * Constructor
     *
     * Build default values
     *
     * @param   object Structures_DataGrid  $dg     The datagrid to render.
     * @access  public
     */
    function Structures_DataGrid_Renderer_Console(&$dg)
    {
        $this->_dg =& $dg;
        $this->_table = new Console_Table();
    }

    /**
     * Determines whether or not to use the Header
     *
     * @access  public
     * @param   bool    $bool   value to determine to use the header or not.
     */
    function useHeader($bool)
    {
        $this->header = (bool)$bool;
    }
    
    /**
     * Prints the console text for the DataGrid
     *
     * @access  public
     */
    function render()
    {
        echo $this->toAscii();
    }

    function toAscii()
    {
        $table = $this->getTable();
        return $table->getTable();
    }
    
    /**
     * Gets the Console_Table ascii text for the DataGrid
     *
     * @access  public
     * @return  string      The console table ascii text for the DataGrid
     */
    function getTable()
    {
        $dg =& $this->_dg;

        if (!$this->_rendered) {
            // Get the data to be rendered
            $dg->fetchDataSource();

            // Check to see if column headers exist, if not create them
            // This must follow after any fetch method call
            $dg->_setDefaultHeaders();
                        
            // Define Table Header
            if ($this->header) {
                $this->_buildTableHeader();
            }
    
            // Build Table Data
            $this->_buildTableBody();
    
            $this->_rendered = true;
        }
        
        return $this->_table;
    }   

    /**
     * Sets the rendered status.  This can be used to "flush the cache" in case
     * you need to render the datagrid twice with the second time having changes
     *
     * @access  public
     * @params  bool        $status     The rendered status of the DataGrid
     */
    function setRendered($status)
    {
        $this->_rendered = (bool)$status;
    }   
        
    /**
     * Handles building the header of the table
     *
     * @access  private
     * @return  void
     */
    function _buildTableHeader()
    {
        $columnList = array();
        foreach ($this->_dg->columnSet as $column) {
            $columnList[] = $column->columnName;
        }
        
        $this->_table->setHeaders($columnList);
    }

    /**
     * Handles building the body of the table
     *
     * @access  private
     * @return  void
     */
    function _buildTableBody()
    {
        if ($this->_dg->recordSet) {
            if (!isset($this->_dg->rowLimit)) {
                $this->_dg->rowLimit = count($this->_dg->recordSet);
            }
            
            // Begin loop
            for ($i = 0; $i < $this->_dg->rowLimit; $i++) {
                if (isset($this->_dg->recordSet[$i])) {
                    $cellList = array();
                    $row = $this->_dg->recordSet[$i];
                    foreach ($this->_dg->columnSet as $column) {
                        $rowCnt = $i+1;

                        // Build Content
                        if (isset($column->formatter)) {
                            //Use Formatter                            
                            $content = $column->formatter($row); 
                        } elseif (!isset($column->fieldName)) {
                            if ($column->autoFillValue != '') {
                                // Use AutoFill                                
                                $content = $column->autoFillValue; 
                            } else {
                                // Use Column Name                                
                                $content = $column->columnName;
                            }
                        } else {
                            // Use Record Data
                            $content = $row[$column->fieldName];
                        }
                        
                        $cellList[] = $content;
                    }
                    $this->_table->addRow($cellList);
                }
            }
        }
    }


}

?>