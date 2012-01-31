<html>
<head>
  <title>DataGrid: Example</title>
  <style type="text/css">
    table.datagrid {font-family: Verdana, Arial; font-size: x-small; background-color: #333333;}
  </style>
</head>
<body>

<?php
require_once('Structures/DataGrid.php');

class Printer {
    function printLink($params)
    {
        extract($params);
        $id = $record['id'];

        return "<a href=\"edit_user.php?id=$id\">$label</a>";
    }
}

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
$dg =& new Structures_DataGrid(4);

// Define DataGrid Color Attributes
$dg->renderer->setTableHeaderAttributes(array('bgcolor' => '#3399FF'));
$dg->renderer->setTableOddRowAttributes(array('bgcolor' => '#CCCCCC'));
$dg->renderer->setTableEvenRowAttributes(array('bgcolor' => '#EEEEEE'));

// Define DataGrid Table Attributes
$dg->renderer->setTableAttribute('width', '50%');
$dg->renderer->setTableAttribute('cellspacing', '1');
$dg->renderer->setTableAttribute('cellpadding', '4');
$dg->renderer->setTableAttribute('class', 'datagrid');

$dg->renderer->sortIconASC = "&uArr;";
$dg->renderer->sortIconDESC = "&dArr;";

// Set empty row table attributes
$dg->renderer->allowEmptyRows(true, array('bgcolor' => '#FFFFFF'));

// Define columns for the DataGrid
$column = new Structures_DataGrid_Column('Name', 'first_name', 'first_name', array('width' => '75%'));
$dg->addColumn($column);
$column = new Structures_DataGrid_Column('Age', 'age', 'age', array('width' => '25%'));
$dg->addColumn($column);
$column = new Structures_DataGrid_Column('Edit', null, null, array('align' => 'center'), null, 'Printer::printLink($label=Edit)');
$dg->addColumn($column);
$column = new Structures_DataGrid_Column('Delete', null, null, array('align' => 'center'));
$dg->addColumn($column);

/* Add rows to the DataGrid
Option #1
foreach ($rs as $row) {
    $row = new Structures_DataGrid_Record($row);
    $result = $dg->addRecord($row);
    if (PEAR::isError($result)) {
        echo $result->getMessage();
    }
}
*/

/* Bind a datasource object
Option #2
require_once('Structures/DataGrid/DataSource.php');
$source = Structures_DataGrid_DataSource::create($rs);
$dg->bindDataSource($source);
*/

// Option #3 Bind directly to any data type
$dg->bind($rs);

// Print the DataGrid
$dg->render();
echo $dg->renderer->getPaging();
?>

</body>
</html>
