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
// | Authors: Olivier Guilyardi <olivier@samalyse.com>                    |
// |          Andrew Nagy <asnagy@webitecture.org>                        |
// +----------------------------------------------------------------------+
//
// $Id: DataSource.php,v 1.2 2005/01/10 15:15:48 asnagy Exp $


// Data Source Drivers
define('DATAGRID_SOURCE_ARRAY',     'Array');
define('DATAGRID_SOURCE_DATAOBJECT','DataObject');
define('DATAGRID_SOURCE_DB',        'DB');
define('DATAGRID_SOURCE_XML',       'XML');
define('DATAGRID_SOURCE_RSS',       'RSS');
define('DATAGRID_SOURCE_CSV',       'CSV');

/**
* Base abstract class for data source drivers
* 
* <b>Recognized options (valid for all drivers) :</b>
*
* <b>"generate_columns" : </b> Generate Structures_DataGrid_Column objects 
* with labels. (default : true) 
* 
* <b>"labels" : </b> How to translate the field names to column labels. 
* Only used when "generate_columns" is true. Default : array().
* This is an associative array of the form :
* <code> 
* array ("fieldName" => "fieldLabel", ...) 
* </code>
* 
* <b>"fields" : </b> Which fields should be rendered (Only used when
* "generate_columns" is true. The default is an empty array : all of
* the DataObject's fields will be rendered.
* This is an array of the form :
* <code>
* array ("fieldName1", "fieldName2", ...)
* </code>
* 
* Users may want to see the create() factory method
*
* Developers :
*
* <b>HOWTO develop a new source driver</b>
*
* Subclass this Structures_DataGrid_DataSource class :
* <code>
* class Structures_DataGrid_DataSource_Foo extends Structures_DataGrid_DataSource
* </code>
*
* In the constructor, initialize default options . These defaults will be
* used to validate user provided options, so you need to set all possible
* ones
* <code>
*     function Structures_DataGrid_DataSource_Foo()
*     {
*         parent::Structures_DataGrid_DataSource(); // required
*         $this->_addDefaultOptions(array( 'bar' => true));
*     }
* </code>
*
* Expose the fetch(), count() and bind() methods, overloading 
* the provided skeleton. See the corresponding prototypes
* for more information on how to do this.
*
* Eventually, use the dump() debugging method to test your brand new
* driver
*
* @author   Olivier Guilyardi <olivier@samalyse.com>
* @author   Andrew Nagy <asnagy@webitecture.org>
* @package  Structures_DataGrid
* @category Structures
* @version  $Revision $
*/
class Structures_DataGrid_DataSource
{
    /**
     * Common and driver-specific options
     *
     * @var array
     * @access protected
     * @see Structures_DataGrid_DataSource::_setOption()
     * @see Structures_DataGrid_DataSource::addDefaultOptions()
     */
    var $_options = array();

    /**
     * Constructor
     *
     */
    function Structures_DataGrid_DataSource()
    {
        $this->_options = array('generate_columns' => null,
                                'labels'           => array(),
                                'fields'           => array());
    }

    /**
     * Adds some default options.
     *
     * This method is meant to be called by drivers. It allows adding some
     * default options. Additionally to setting default values the options
     * names (keys) are used by setOptions() to validate its input.
     *
     * @access protected
     * @param array $options An associative array of the from:
     *                       array(optionName => optionValue, ...)
     * @return void
     * @see Structures_DataGrid_DataSource::_setOption
     */
    function _addDefaultOptions($options)
    {
        $this->_options = array_merge($this->_options, $options);
    }
   
    /**
     * Driver Factory
     *
     * A clever method which loads and instantiate data source drivers.
     *
     * Can be called in various ways :
     *
     * Detect the source type and load the appropriate driver with default
     * options :
     * <code>
     * $driver =& Structures_DataGrid_DataSource::create($source);
     * </code>
     *
     * Detect the source type and load the appropriate driver with custom
     * options :
     * <code>
     * $driver =& Structures_DataGrid_DataSource::create($source, $options);
     * </code>
     *
     * Load a driver for an explicit type (faster, bypasses detection routine) :
     * <code>
     * $driver =& Structures_DataGrid_DataSource::create($source, $type, $options);
     * </code>
     *
     * @access  public
     * @param   mixed   $source     The data source respective to the driver
     * @param   array   $options    An associative array of the form :
     *                              array(optionName => optionValue, ...)
     * @param   string  $type       The data source type constant (of the form 
     *                              DATAGRID_DATASOURCE_*)  
     * @uses    Structures_DataGrid_DataSource::_detectSourceType()     
     * @return  mixed               Returns the source driver object or 
     *                              PEAR_Error on failure
     * @static
     */
    function &create($source, $options=array(), $type=null)
    {
        if (is_null($type) &&
            !($type = Structures_DataGrid_DataSource::_detectSourceType($source))) {
            return new PEAR_Error('Unable to determine the data source type. '.
                                  'You may want to explicitly specify it.');
        }

        if (!@include_once "Structures/DataGrid/DataSource/$type.php") {
            return new PEAR_Error("No such data source driver: '$type'");
        }
        
        $classname = "Structures_DataGrid_DataSource_$type";
        $driver = new $classname();
        $driver->bind($source, $options);
       
        return $driver;
    }
    

    /**
     * Set options
     *
     * @param   mixed   $options    An associative array of the form :
     *                              array("option_name" => "option_value",...)
     * @access  protected
     */
    function setOptions($options)
    {
        $this->_options = array_merge($this->_options, $options);
    }

    /**
     * Generate columns if options are properly set
     *
     * Note : must be called after fetch()
     * 
     * @access public
     * @return array Array of Column objects. Empty array if irrelevant.
     */
    function getColumns()
    {
        $columns = array();
        if ($this->_options['generate_columns'] 
            and $fieldList = $this->_options['fields']) {
                             
            include_once('Structures/DataGrid/Column.php');
            
            foreach ($fieldList as $field) {
                $label = strtr($field, $this->_options['labels']);
                $col = new Structures_DataGrid_Column($label, $field, $field);
                $columns[] = $col;
            }
        }
        
        return $columns;
    }
    
    
    // Begin driver method prototypes DocBook template
     
    /**#@+
     *
     * This method is public, but please note that it is not intended to be called by
     * user-space code. It is meant to be called by the main Structures_DataGrid
     * container.
     *
     * It is intended to be overloaded by drivers.
     */
   
    /**
     * Fetching method prototype
     *
     * When overloaded, should either return a PEAR_Error or a <b>reference</b>
     * to an array of the form :
     *    array("Columns" => $columns, "Records" => $records)
     * where $columns is an array of Structures_DataGrid_Column objects and
     * $records an assoc array of rows
     *
     * @param   integer $offset     Limit offset (starting from 0)
     * @param   integer $len        Limit length
     * @param   string  $sortField  Field to sort by
     * @param   string  $sortDir    Sort direction : 'ASC' or 'DESC'
     * @return  object PEAR_Error   An error with message
     *                              'No data source driver loaded'
     * @access  public                          
     */
    function &fetch($offset=0, $len=null, $sortField=null, $sortDir='ASC')
    {
        $err = new PEAR_Error("No data source driver loaded");
        return $err;
    }

    /**
     * Counting method prototype
     *
     * Note : must be called before fetch() 
     * 
     * When overloaded, should either return a numeric value indicating the
     * total number of rows in the data source, or a PEAR_Error object
     *
     * @return  object PEAR_Error       An error with message
     *                                  'No data source driver loaded'
     * @access  public                          
     */
    function count()
    {
        return PEAR_Error("No data source driver loaded");
    }
    
    /**
     * Sorting method prototype
     *
     * Note : must be called before fetch() 
     * 
     * @return  object PEAR_Error       An error with message
     *                                  'No data source driver loaded'
     * @access  public                          
     */
    function sort()
    {
        return PEAR_Error("No data source driver loaded");
    }    
  
    /**
     * Datasource binding method prototype
     *
     * When overloaded, should either return true or a PEAR_Error object
     *
     * @return  object PEAR_Error   An error with message
     *                              'No data source driver loaded'
     * @access  public                          
     */
    
    function bind()
    {
        return PEAR_Error("No data source driver loaded");
    }
  
    /**#@-*/

    // End DocBook template
   
    /**
     * Dump the data as returned by fetch().
     *
     * This method is meant for debugging purposes. It returns what fetch()
     * would return to its DataGrid host as a nicely formatted console-style
     * table.
     *
     * @param   integer $offset     Limit offset (starting from 0)
     * @param   integer $len        Limit length
     * @param   string  $sortField  Field to sort by
     * @param   string  $sortDir    Sort direction : 'ASC' or 'DESC'
     * @return  string              The table string, ready to be printed
     * @uses    Structures_DataGrid_DataSource::fetch()
     * @access  public
     */
    function dump($offset=0, $len=null, $sortField=null, $sortDir='ASC')
    {
        $records =& $this->fetch($offset, $len, $sortField, $sortDir);
        $columns = $this->getColumns();

        if (!$columns and !$records) {
            return "<Empty set>\n";
        }
        
        include_once 'Console/Table.php';
        $table = new Console_Table();
        
        $headers = array();
        if ($columns) {
            foreach ($columns as $col) {
                $headers[] = is_null($col->fieldName)
                            ? $col->columnName
                            : "{$col->columnName} ({$col->fieldName})";
            }
        } else {
            $headers = array_keys($records[0]);
        }

        $table->setHeaders($headers);
        
        foreach ($records as $rec) {
            $table->addRow($rec);
        }
       
        return $table->getTable();
    }
   
    /**
     * Detect source type
     *
     * @param   mixed   $source     Some kind of source
     * @return  string              The type constant of this source or null if
     *                              it couldn't be detected
     * @access  private
     */
    function _detectSourceType($source)
    {
        switch($source) {
            // DB_DataObject
            case (is_subclass_of($source, 'db_dataobject')):
                return DATAGRID_SOURCE_DATAOBJECT;
                break;

            // DB_Result
            case (strtolower(get_class($source)) == 'db_result'):
                return DATAGRID_SOURCE_DB;
                break;
                
            // Array
            case (is_array($source)):
                return DATAGRID_SOURCE_ARRAY;
                break;

            // RSS
            case (is_string($source) and stristr('<rdf:RDF', $source)):
                return DATAGRID_SOURCE_RSS;
                break;

            // XML
            case (is_string($source) and ereg('^ *<\?xml', $source)):
                return DATAGRID_SOURCE_XML;
                break;

            // CSV
            //case (is_string($source)):
            //    return DATAGRID_SOURCE_CSV;
            //    break;
                
            default:
                return null;
                break;
        }
    }
}
?>