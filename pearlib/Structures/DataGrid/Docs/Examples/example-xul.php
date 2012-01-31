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
            array('id' => 3,
                  'first_name' => 'Sally',
                  'last_name' => 'Robinson',
                  'age' => '52'),
            array('id' => 3,
                  'first_name' => 'Robert',
                  'last_name' => 'Brown',
                  'age' => '19'));

// Define New DataGrid with a limit of 3 records
$dg =& new Structures_DataGrid(3, null, DATAGRID_RENDER_XUL);

// Define columns for the DataGrid
$column = new Structures_DataGrid_Column('Name', 'first_name');
$dg->addColumn($column);

$column = new Structures_DataGrid_Column('Age', 'age');
$dg->addColumn($column);

// Bind data set
$dg->bind($rs);

// Sort the array based on the field
$dg->sortRecordSet('age', 'DESC');

// Print the DataGrid
$dg->render();
?>
