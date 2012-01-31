<?php
#build resources handles for the core
$resources['cD']=new Resource("http://www.s3db.org/core#s3dbDeployment");
$resources['cP']=new Resource("http://www.s3db.org/core#s3dbProject");
$resources['cC']=new Resource("http://www.s3db.org/core#s3dbCollection");
$resources['cR']=new Resource("http://www.s3db.org/core#s3dbRule");
$resources['cI']=new Resource("http://www.s3db.org/core#s3dbItem");
$resources['cS']=new Resource("http://www.s3db.org/core#s3dbStatement");

#build resource handles for the RDF relations
$resources['rType']=new Resource("http://www.w3.org/1999/02/22-rdf-syntax-ns#type");
$resources['rLabel']=new Resource("http://www.w3.org/2000/01/rdf-schema#label");
$resources['rDescription']=new Resource("http://purl.org/dc/terms/description");
$resources['rSubClassOf'] = new Resource('http://www.w3.org/2000/01/rdf-schema#subClassOf');
$resources['rSubject'] = new Resource('http://www.w3.org/1999/02/22-rdf-syntax-ns#subject');
$resources['rPredicate'] = new Resource('http://www.w3.org/1999/02/22-rdf-syntax-ns#predicate');
$resources['rObject'] = new Resource('http://www.w3.org/1999/02/22-rdf-syntax-ns#object');
?>