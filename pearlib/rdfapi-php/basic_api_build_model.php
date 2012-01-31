<?php
define("RDFAPI_INCLUDE_DIR", "C:\wamp\www\\rdfapi-php\api\\");
include(RDFAPI_INCLUDE_DIR . "RdfAPI.php");

$someDoc = new Resource ("http://www.example.org/someDocument.html");
$creator = new Resource ("http://www.purl.org/dc/elements/1.1/creator");
$statement1 = new Statement ($someDoc, $creator, new Literal ("Radoslaw Oldakowski"));

#create a model
$model1 = ModelFactory::getDefaultModel();
$model1->add($statement1);

#create a new model
$model2 = ModelFactory::getDefaultModel();
$model2->add(new Statement($someDoc, new Resource("http://www.example.org/myVocabulary/title"), new Literal("RAP tutorial")));
$model2->add(new Statement($someDoc, new Resource("http://www.example.org/myVocabulary/language"), new Literal("English")));

#and put them together
$model1->addModel($model2); 
echo "\$model1 contains " .$model1->size() ." statements";

// Output $model1 as HTML table
echo "<b>Output the MemModel as HTML table: </b><p>";
$model1->writeAsHtmlTable();

// Output the string serialization of $model1
echo "<b>Output the plain text serialization of the MemModel: </b><p>";
echo $model1->toStringIncludingTriples();

// Output the RDF/XML serialization of $model1
echo "<b>Output the RDF/XML serialization of the MemModel: </b><p>";
echo $model1->writeAsHtml();


##We can then save the model in RDF or N3
$model1->saveAs("modelLixo.rdf", "rdf");
$model1->saveAs("modelLixo.n3", "n3");

##Now let's reify the model
$it = $model2->getStatementIterator();
while ($it->hasNext()) {
      $statement = $it->next();
      echo "Statement number: " . $it->getCurrentPosition() . "<BR>";
      echo "Subject: " . $statement->getLabelSubject() . "<BR>";
      echo "Predicate: " . $statement->getLabelPredicate() . "<BR>";
      echo "Object: " . $statement->getLabelObject() . "<P>";
}

#This outputes on ly the result of the query as an html table
$result = $model1->find($someDoc, NULL, NULL);
$result->writeAsHtmlTable();


echo '<b>There is an easy way to reify the model as well!</b>';
$reified = $model2->reify();
$reified->writeAsHtmlTable();
$model1->close();
$model2->close();
$result->close();
$reified->close();
?>

