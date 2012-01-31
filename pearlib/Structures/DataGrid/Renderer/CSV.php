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
// $Id: CSV.php,v 1.5 2005/01/10 15:15:49 asnagy Exp $

/**
 * Structures_DataGrid_Renderer_CSV Class
 *
 * @version  $Revision: 1.5 $
 * @author   Andrew S. Nagy <asnagy@webitecture.org>
 * @access   public
 * @package  Structures_DataGrid
 * @category Structures
 */
class Structures_DataGrid_Renderer_CSV
{
    /**
     * The Datagrid object to render
     * @var object Structures_DataGrid
     */
    var $_dg;

    /**
     * The Delimiter to use to seperate the values
     * @var string
     */
    var $delimiter = ',';
    
    /**
     * Whether or not to encapuslate the values with quotes
     * @var bool
     */
    var $useQuotes = false;
    
    /**
     * Constructor
     *
     * Build default values
     *
     * @param   object Structures_DataGrid  $dg     The datagrid to render.
     * @access public
     */
    function Structures_DataGrid_Renderer_CSV(&$dg)
    {
        $this->_dg =& $dg;
    }

    /**
     * Generates CSV data representing the DataGrid
     *
     * @access  public
     * @return  string      The CSV data of the DataGrid
     */
    function render()
    {
        header('Content-type: text/csv');
        
        echo $this->toCSV();
    }
    
    /**
     * Define the CSV delimiter
     *
     * @access  public
     * @return  string      The CSV delimiter to use
     */
    function setDelimiter($delimiter)
    {
        $this->delimiter = $delimiter;
    }

    /**
     * Set the switch to encapsulate the values with quotes
     *
     * @access  public
     * @return  boolean     The boolean value to determine whether or not to
     *                      wrap values with quotes
     */
    function setUseQuotes($bool)
    {
        $this->useQuotes = (bool)$bool;
    }
           
    /**
     * Generates the CSV format for the DataGrid
     *
     * @access  public
     * @return  string      The CSV of the DataGrid
     */
    function toCSV()
    {
        $dg =& $this->_dg;

        // Get the data to be rendered
        $dg->fetchDataSource();
                
        // Check to see if column headers exist, if not create them
        // This must follow after any fetch method call
        $dg->_setDefaultHeaders();
        
        $i = 0;
        foreach ($this->_dg->columnSet as $column) {
            if ($i > 0) {
                $csv .= $this->delimiter . ' ';
            }
            $csv .= $column->columnName;
            $i++;
        }
        $csv .= "\n";
        
        foreach ($this->_dg->recordSet as $row) {
            $i = 0;
            foreach ($this->_dg->columnSet as $column) {
                // Build Content
                if ($column->formatter != null) {
                    $content = $column->formatter($row);
                } elseif ($column->fieldName == null) {
                    if ($column->autoFillValue != null) {
                        $content = $column->autoFillValue;
                    } else {
                        $content = $column->columnName;
                    }
                } else {
                    $content = $row[$column->fieldName];
                }

                // Implement Delimiter
                if ($i > 0) {
                    $csv .= $this->delimiter . ' ';
                }
                
                // Add content to CSV
                if ($this->useQuotes) {
                    $csv .= '"' . $content . '"';
                } else {
                    $csv .= $content;
                }
                $i++;
            }

            $csv .= "\n";
        }

        return $csv;
    }

}

?>