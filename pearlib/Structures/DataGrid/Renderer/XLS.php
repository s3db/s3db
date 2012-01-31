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
// $Id: XLS.php,v 1.16 2005/01/10 15:15:49 asnagy Exp $

require_once 'Spreadsheet/Excel/Writer.php';

/**
 * Structures_DataGrid_Renderer_XLS Class
 *
 * @version  $Revision: 1.16 $
 * @author   Andrew S. Nagy <asnagy@webitecture.org>
 * @access   public
 * @package  Structures_DataGrid
 * @category Structures
 */
class Structures_DataGrid_Renderer_XLS
{
    /**
     * The Datagrid object to render
     * @var object Structures_DataGrid
     */
    var $_dg;
    
    /**
     * The spreadsheet object
     * @var object Spreadsheet_Excel_Writer
     */
    var $_workbook;
    
    /**
     * The worksheet object
     * @var object Spreadsheet_Excel_Writer
     */
    var $_worksheet;
    
    /**
     * The filename of the spreadsheet
     * @var string
     */
    var $_filename = 'spreadsheet.xls';

    /**
     * A switch to determine to use the header
     * @var bool
     */
    var $header = true;    
        
    /**
     * A switch to determine the state of the spreadsheet
     * @var bool
     */
    var $_rendered = false;    

    /**
     * Constructor
     *
     * Build default values
     *
     * @param   object Structures_DataGrid  $dg     The datagrid to render.
     * @access public
     */
    function Structures_DataGrid_Renderer_XLS(&$dg)
    {
        $this->_dg =& $dg;
        
        $this->_workbook = new Spreadsheet_Excel_Writer();
        $this->setFilename();
        $this->_worksheet =& $this->_workbook->addWorksheet();
    }

    /**
     * Sets the name of the file to create
     *
     * @param  string   $filename   The name of the file
     * @access public
     */
    function setFilename($filename = 'spreadsheet.xls')
    {
        $this->_filename = $filename;
        $this->_workbook->send($filename);
    }

    /**
     * Determines whether or not to use the header
     *
     * @access  public
     * @param   bool    $bool   value to determine to use the header or not.
     */
    function useHeader($bool)
    {
        $this->header = (bool)$bool;
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
     * Force download the spreadsheet
     *
     * @access  public
     */
    function render()
    {
        $this->getSpreadsheet();
       
        $this->_workbook->close();
    }

    /**
     * Get the spreadsheet object
     *
     * @access  public
     */
    function &getSpreadsheet()
    {
        $dg =& $this->_dg;

        if (!$this->_rendered) {        
            // Get the data to be rendered
            $dg->fetchDataSource();
            
            // Check to see if column headers exist, if not create them
            // This must follow after any fetch method call
            $dg->_setDefaultHeaders();
            
            if ($this->header) {
                $this->_buildHeader();
            }
            $this->_buildBody();
            $this->_rendered = true;
        }
        
        return $this->_workbook;
    }
    
    /**
     * Handles building the header of the DataGrid
     *
     * @access  private
     * @return  void
     */
    function _buildHeader()
    {
        $cnt = 0;
        foreach ($this->_dg->columnSet as $column) {
            //Define Content
            $str = $column->columnName;
            $this->_worksheet->write(0, $cnt, $str);
            $cnt++;
        }
    }

    /**
     * Handles building the body of the DataGrid
     *
     * @access  private
     * @return  void
     */
    function _buildBody()
    {
        if (count($this->_dg->recordSet)) {

            // Determine looping values
            if ($this->_dg->page > 1) {
                $begin = ($this->_dg->page - 1) * $this->_dg->rowLimit;
                $limit = $this->_dg->page * $this->_dg->rowLimit;
            } else {
                $begin = 0;
                if ($this->_dg->rowLimit == null) {
                    $limit = count($this->_dg->recordSet);
                } else {
                    $limit = $this->_dg->rowLimit;
                }
            }

            // Begin loop
            for ($i = $begin; $i < $limit; $i++) {
                $cnt = 0;
                $row = $this->_dg->recordSet[$i];
                foreach ($this->_dg->columnSet as $column) {
                    $rowCnt = ($i-$begin)+1;

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

                    $this->_worksheet->write($rowCnt, $cnt, $content);

                    $cnt++;
                }
            }
        }
    }

}

?>
