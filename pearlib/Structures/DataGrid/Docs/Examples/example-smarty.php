<?php
require_once('Structures/DataGrid.php');

// Data to be printed by DataGrid
$rs = array(array('id' => 1,
                  'first_name' => 'Bob',
                  'last_name' => 'Smith',
                  'age' => '37'),
            array('id' => 2,
                  'first_name' => 'John',
                  'last_name' => 'Doe',
                  'age' => '23'),
            array('id' => 3,
                  'first_name' => 'Fred',
                  'last_name' => 'Thompson',
                  'age' => '58'),
            array('id' => 4,
                  'first_name' => 'Sally',
                  'last_name' => 'Robinson',
                  'age' => '52'),
            array('id' => 5,
                  'first_name' => 'Robert',
                  'last_name' => 'Brown',
                  'age' => '19'));

// Define New DataGrid with a limit of 3 records
$dg =& new Structures_DataGrid(3, null, DATAGRID_RENDER_SMARTY);

// Define columns for the DataGrid
$column = new Structures_DataGrid_Column('Name', 'first_name', 'first_name', array('width' => '75%'));
$dg->addColumn($column);
$column = new Structures_DataGrid_Column('Age', 'age', 'age', array('width' => '25%'));
$dg->addColumn($column);
$column = new Structures_DataGrid_Column('Edit', null, null, array('align' => 'center'));
$dg->addColumn($column);
$column = new Structures_DataGrid_Column('Delete', null, null, array('align' => 'center'), 'delete');
$dg->addColumn($column);

$dg->bind($rs);

// Print the DataGrid
$result = $dg->renderer->setTemplate('example.tpl');
if (PEAR::isError($result)) {
    echo $result->getMessage();
} else {
    $dg->render();
}

?>
