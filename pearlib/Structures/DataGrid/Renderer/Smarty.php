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
// $Id: Smarty.php,v 1.10 2005/01/18 19:45:41 asnagy Exp $

require_once 'Smarty/Smarty.class.php';

/**
 * Structures_DataGrid_Renderer_Smarty Class
 *
 * @version  $Revision: 1.10 $
 * @author   Andrew S. Nagy <asnagy@webitecture.org>
 * @access   public
 * @package  Structures_DataGrid
 * @category Structures
 */
class Structures_DataGrid_Renderer_Smarty
{
    var $_dg;

    var $_smarty;

    var $_tpl;

    /**
     * Constructor
     *
     * Build default values
     *
     * @param   object Structures_DataGrid  $dg     The datagrid to render.
     * @access public
     */
    function Structures_DataGrid_Renderer_Smarty(&$dg)
    {
        $this->_dg =& $dg;
        
        $this->_smarty = new Smarty();
        $this->_smarty->template_dir = dirname($_SERVER['SCRIPT_FILENAME']);
        $this->_smarty->compile_dir = dirname($_SERVER['SCRIPT_FILENAME']) . '/compile';
    }

    function setSmarty(&$smarty)
    {
        $this->_smarty = &$smarty;
    }

    function setTemplate($tpl)
    {
        if (is_file($this->_smarty->template_dir . '/' . $tpl)) {
            $this->_tpl = $tpl;
        } else {
            return new PEAR_Error('Error: Unable to find ' .
                                  $this->_smarty->template_dir . '/' . $tpl);
        }
    }

    function getSmarty()
    {
        // Get the data to be rendered
        $this->_dg->fetchDataSource();
        
        // Check to see if column headers exist, if not create them
        // This must follow after any fetch method call
        $this->_dg->_setDefaultHeaders();
                
        if ($this->_tpl != '') {
            $this->_smarty->assign('recordSet',   $this->_dg->recordSet);
            $this->_smarty->assign('columnSet',   $this->_dg->columnSet);
            $this->_smarty->assign('recordLimit', $this->_dg->rowLimit);
            $this->_smarty->assign('currentPage', $this->_dg->page);

            return $smarty;
        } else {
            return new PEAR_Error('Error: No template defined');
        }
    }
    
    function render()
    {
        $smarty = $this->getSmarty();
        
        if (PEAR::isError($smarty)) {
            return $smarty;
        } else {
            $smarty->display($this->_tpl);
        }
    }

}

?>