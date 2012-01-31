<?php
/* vim: set expandtab tabstop=4 shiftwidth=4: */
// +----------------------------------------------------------------------+
// | PHP version 4                                                        |
// +----------------------------------------------------------------------+
// | Copyright (c) 1997-2004 The PHP Group                                |
// +----------------------------------------------------------------------+
// | This source file is subject to version 2.0 of the PHP license,       |
// | that is bundled with this package in the file LICENSE, and is        |
// | available through the world-wide-web at                              |
// | http://www.php.net/license/2_02.txt.                                 |
// | If you did not receive a copy of the PHP license and are unable to   |
// | obtain it through the world-wide-web, please send a note to          |
// | license@php.net so we can mail you a copy immediately.               |
// +----------------------------------------------------------------------+
// | Author: Olivier Guilyardi <olivier@samalyse.com>                     |
// |         Andrew Nagy <asnagy@webitecture.org>                         |
// +----------------------------------------------------------------------+
//
// $Id: DataObject.php,v 1.18 2005/01/24 15:55:19 asnagy Exp $

require_once 'Structures/DataGrid/DataSource.php';

/**
 * PEAR::DB_DataObject Data Source Driver
 *
 * This class is a data source driver for a PEAR::DB::DB_DataObject object
 *
 * Recognized options :
 *
 * <b>"labels_property" : </b> The name of a property that you can set
 * within your DataObject. This property is expected to contain the
 * same kind of information as the "labels" options. If the "labels" 
 * option is set, this one will not be used. Default : "fb_fieldsLabels".
 *
 * <b>"fields_property" : </b> The name of a property that you can set
 * within your DataObject. This property is expected to contain the
 * same kind of information as the "fields" options. If the "fields"
 * option is set, this one will not be used. Default : "fb_fieldsToRender".
 * 
 * @version  $Revision: 1.18 $
 * @author   Olivier Guilyardi <olivier@samalyse.com>
 * @author   Andrew Nagy <asnagy@webitecture.org>
 * @access   public
 * @package  Structures_DataGrid
 * @category Structures
 */
class Structures_DataGrid_DataSource_DataObject
    extends Structures_DataGrid_DataSource
{   
    /**
     * Reference to the DataObject
     *
     * @var object DB_DataObject
     * @access private
     */
    var $_dataobject;
    
    /**
     * Total number of rows 
     * 
     * This property caches the result of DataObject::count(), that 
     * can't be called after DataObject::fetch() (DataObject bug ?).
     *
     * @var int
     * @access private
     */
     var $_rowNum = null;    
    
    /**
     * Constructor
     *
     * @param object DB_DataObject
     * @access public
     */
    function Structures_DataGrid_DataSource_DataObject()
    {
        parent::Structures_DataGrid_DataSource();
        $this->_addDefaultOptions(array(
                    'labels_property' => 'fb_fieldLabels',
                    'fields_property' => 'fb_fieldsToRender',
                    'sort_property' => 'fb_linkOrderFields',
                    'formbuilder_integration' => false));
    }
  
    /**
     * Bind
     *
     * @param   object DB_DataObject    $dataobject     The DB_DataObject object
     *                                                  to bind
     * @param   array                   $options        Associative array of 
     *                                                  options.
     * @access  public
     * @return  mixed   True on success, PEAR_Error on failure
     */
    function bind(&$dataobject, $options=array())
    {
        if ($options) {
            $this->setOptions($options); 
        }

        if (is_subclass_of($dataobject, 'DB_DataObject')) {
            $this->_dataobject =& $dataobject;

            $mergeOptions = array();
            
            // Merging the fields and fields_property options
            if (!$this->_options['fields']) {
                if ($fieldsVar = $this->_options['fields_property']
                    and isset($this->_dataobject->$fieldsVar)) {
                    $mergeOptions['fields'] = $this->_dataobject->$fieldsVar;
                    if ($this->_options['formbuilder_integration']) {
                        if (isset($this->_dataobject->fb_preDefOrder)) {
                            $ordered = array();
                            foreach ($this->_dataobject->fb_preDefOrder as
                                     $orderField) {
                                if (in_array($orderField,
                                             $mergeOptions['fields'])) {
                                    $ordered[] = $orderField;
                                }
                            }
                            $mergeOptions['fields'] =
                                array_merge($ordered,
                                            array_diff($mergeOptions['fields'],
                                                       $ordered));
                        }
                        foreach ($mergeOptions['fields'] as $num => $field) {
                            if (strstr($field, '__tripleLink_') ||
                                strstr($field, '__crossLink_') || 
                                strstr($field, '__reverseLink_')) {
                                unset($mergeOptions['fields'][$num]);
                            }
                        }
                    }
                }
            }

            // Merging the labels and labels_property options
            if (!$this->_options['labels'] 
                and $labelsVar = $this->_options['labels_property']
                and isset($this->_dataobject->$labelsVar)) {
                
                $mergeOptions['labels'] = $this->_dataobject->$labelsVar;

            }

            if ($mergeOptions) {
                $this->setOptions($mergeOptions);
            }
                
            return true;
        } else {
            return new PEAR_Error('The provided source must be a DB_DataObject');
        }
    }

    /**
     * Fetch
     *
     * @param   integer $offset     Limit offset (starting from 0)
     * @param   integer $len        Limit length
     * @param   string  $sortField  Field to sort by
     * @param   string  $sortDir    Sort direction : 'ASC' or 'DESC'
     * @access  public
     * @return  array   The 2D Array of the records
     */    
    function &fetch($offset=0, $len=null, $sortField=null, $sortDir='ASC')
    {
        // Check to see if Query has already been submitted
        if ($this->_dataobject->_DB_resultid != '') {
            $this->_rowNum = $this->_dataobject->N;
        } else {
            // Caching the number of rows
            if (PEAR::isError($count = $this->count())) {
                return $count;
            } else {
                $this->_rowNum = $count;
            }
                    
            // Sorting
            if ($sortField) {
                $this->sort($sortField, $sortDir);
            } elseif (($sortProperty = $this->_options['sort_property'])
                      && isset($this->_dataobject->$sortProperty)) {
                foreach ($this->_dataobject->$sortProperty as $sort) {
                    $this->sort($sort);
                }
            }
            
            // Limiting
            if ($offset) {
                $this->_dataobject->limit($offset, $len);
            } elseif ($len) {
                $this->_dataobject->limit($len);
            }
            
            $result = $this->_dataobject->find();
        }
        
        // Retrieving data
        $records = array();
        if ($this->_rowNum) {
            if ($this->_options['formbuilder_integration']) {
                require_once('DB/DataObject/FormBuilder.php');
                $links = $this->_dataobject->links();
            }            
            while ($this->_dataobject->fetch()) {
                // Determine Fields
                if (!$this->_options['fields']) {
                    $this->_options['fields'] =
                        array_keys($this->_dataobject->toArray());
                    //$this->_options['fields'] = array_filter(array_keys(get_object_vars($this->_dataobject)), array(&$this, '_fieldsFilter'));
                }
                $fieldList = $this->_options['fields'];
                // Build DataSet
                $rec = array();
                foreach ($fieldList as $fName) {
                    $getMethod = 'get'.$fName;
                    if (method_exists($this->_dataobject, $getMethod)) {
                        //$rec[$fName] = $this->_dataobject->$getMethod(&$this);
                        $rec[$fName] = $this->_dataobject->$getMethod;
                    } elseif (isset($this->_dataobject->$fName)) {                        
                        $rec[$fName] = $this->_dataobject->$fName;
                    } else {
                        $rec[$fName] = null;
                    }
                }
                
                // Get Linked FormBuilder Fields
                if ($this->_options['formbuilder_integration']) {
                    foreach (array_keys($rec) as $field) {
                        if (isset($links[$field]) &&
                            $linkedDo =& $this->_dataobject->getLink($field)) {
                            $rec[$field] = DB_DataObject_FormBuilder::getDataObjectString($linkedDo);
                        }
                    }
                }
                                
                $records[] = $rec;
            }
        }
       
        return $records;
    }

    /**
     * Count
     *
     * @access  public
     * @return  int         The number of records or a PEAR_Error
     */    
    function count()
    {
        if ($this->_rowNum == null) {
            if ($this->_dataobject->N) {
                $this->_rowNum = $this->_dataobject->N;
            } else {
                $test = $this->_dataobject->count();
                if ($test === false) {
                    return new PEAR_Error ('Can\'t count the number of rows');
                }
                $this->_rowNum = $test;
            }
        }
        
        return $this->_rowNum;
    }
    
    /**
     * Sorts the dataobject.  This MUST be called before fetch.
     * 
     * @access  public
     * @param   string  $sortField  Field to sort by
     * @param   string  $sortDir    Sort direction : 'ASC' or 'DESC'
     */
    function sort($sortField, $sortDir = null)
    {
        if ($sortDir === null) {
            $this->_dataobject->orderBy($sortField);
        } else {
            $this->_dataobject->orderBy($sortField . ' ' . $sortDir);
        }
    }
    
    // This function is temporary until DB_DO bug #1315 is fixed
    // This removeds and variables from the DataObject that begins with _ or fb_
    function _fieldsFilter($value)
    {
        if (substr($value, 0, 1) == '_') {
            return false;
        } else if (substr($value, 0, 3) == 'fb_') {
            return false;
        } else if ($value == 'N') {
            return false;
        } else {
            return true;
        }
        
    }

}
?>