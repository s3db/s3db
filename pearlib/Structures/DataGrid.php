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
// $Id: DataGrid.php,v 1.16 2005/01/26 21:25:47 asnagy Exp $

require_once 'PEAR.php';

require_once 'Structures/DataGrid/Renderer.php';

// Renderer Drivers
define ('DATAGRID_RENDER_TABLE',    'HTMLTable');
define ('DATAGRID_RENDER_SMARTY',   'Smarty');
define ('DATAGRID_RENDER_XML',      'XML');
define ('DATAGRID_RENDER_XLS',      'XLS');
define ('DATAGRID_RENDER_XUL',      'XUL');
define ('DATAGRID_RENDER_CSV',      'CSV');
define ('DATAGRID_RENDER_CONSOLE',  'Console');

/**
 * Structures_DataGrid Class
 *
 * A PHP class to implement the functionality provided by the .NET Framework's
 * DataGrid control.  This class can produce a data driven list in many formats
 * based on a defined record set.  Commonly, this is used for outputting an HTML
 * table based on a record set from a database or an XML document.  It allows
 * for the output to be published in many ways, including an HTML table,
 * an HTML Template, an Excel spreadsheet, an XML document.  The data can
 * be sorted and paged, each cell can have custom output, and the table can be
 * custom designed with alternating color rows.
 *
 * Quick Example:
 * <code>
 * <?php
 * require('Structures/DataGrid.php');
 * $dg = new Structures_DataGrid();
 * $result = mysql_query('SELECT * FROM users');
 * while ($rs = mysql_fetch_assoc($result)) {
 *     $dataSet[] = $rs;
 * }
 * $dg->bind($dataSet);
 * echo $dg->render();
 * ?>
 * </code>
 *
 * @version  $Revision: 1.16 $
 * @author   Andrew S. Nagy <asnagy@webitecture.org>
 * @access   public
 * @package  Structures_DataGrid
 * @category Structures
 */
class Structures_DataGrid extends Structures_DataGrid_Renderer
{
    /**
     * Constructor
     *
     * Acts somewhat as a factory and instantiates the Renderer and the Core
     *
     * @param  string   $limit      The row limit per page.
     * @param  string   $page       The current page viewed.
     * @param  string   $renderer   The renderer to use.
     * @return void
     * @access public
     */
    function Structures_DataGrid($limit = null, $page = 1,
                                 $renderer = DATAGRID_RENDER_TABLE)
    {
        parent::Structures_DataGrid_Renderer($renderer, $limit, $page);
    }

    /**
     * Method used for debuging purposes only.  Displays a dump of the DataGrid
     * object.
     *
     * @access  public
     * @return  void
     */
    function dump()
    {
        echo '<pre>';
        print_r($this);
        echo '</pre>';
    }
    
    /**
     * Returns the current version of the package according to the CVS Tag
     *
     * @access  public
     * @return  string      CVS Tag Version Number
     */
    function apiVersion()
    {
        return '$Name:  $';
    }

}

?>