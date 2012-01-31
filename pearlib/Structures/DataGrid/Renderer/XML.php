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
// $Id: XML.php,v 1.16 2005/01/10 15:15:49 asnagy Exp $

require_once 'XML/Util.php';

/**
 * Structures_DataGrid_Renderer_XML Class
 *
 * @version  $Revision: 1.16 $
 * @author   Andrew S. Nagy <asnagy@webitecture.org>
 * @access   public
 * @package  Structures_DataGrid
 * @category Structures
 */
class Structures_DataGrid_Renderer_XML
{
    /**
     * The Datagrid object to render
     * @var object Structures_DataGrid
     */
    var $_dg;

    /**
     * Constructor
     *
     * Build default values
     *
     * @param   object Structures_DataGrid  $dg     The datagrid to render.
     * @access public
     */
    function Structures_DataGrid_Renderer_XML($dg)
    {
        $this->_dg =& $dg;
    }

    /**
     * Generates the XML for the DataGrid
     *
     * @access  public
     * @return  string      The XML of the DataGrid
     */
    function render()
    {
        header('Content-type: text/xml');
        
        echo $this->toXML();
    }
       
    /**
     * Generates the XML for the DataGrid
     *
     * @access  public
     * @return  string      The XML of the DataGrid
     */
    function toXML()
    {
        $dg =& $this->_dg;

        // Get the data to be rendered
        $dg->fetchDataSource();
                
        // Check to see if column headers exist, if not create them
        // This must follow after any fetch method call
        $dg->_setDefaultHeaders();
        
        $xml = XML_Util::getXMLDeclaration() . "\n";

        $xml .= "<DataGrid>\n";
        foreach ($this->_dg->recordSet as $row) {
            $xml .= "  <Row>\n";

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

                $xml .= '    ' .
                        XML_Util::createTag($column->columnName,
                                         null, $content) . "\n";
            }

            $xml .= "  </Row>\n";
        }
        $xml .= "</DataGrid>\n";

        return $xml;
    }

}

?>