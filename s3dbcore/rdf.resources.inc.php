<?php
#build resources handles for the core
$resources['cD']=new Resource("http://www.s3db.org/core#s3dbDeployment");
$resources['cU']=new Resource("http://www.s3db.org/core#s3dbUser");
$resources['cG']=new Resource("http://www.s3db.org/core#s3dbGroup");
$resources['cP']=new Resource("http://www.s3db.org/core#s3dbProject");
$resources['cC']=new Resource("http://www.s3db.org/core#s3dbCollection");
$resources['cR']=new Resource("http://www.s3db.org/core#s3dbRule");
$resources['cI']=new Resource("http://www.s3db.org/core#s3dbItem");
$resources['cS']=new Resource("http://www.s3db.org/core#s3dbStatement");

#build resource handles for the RDF relations
$resources['rType']=new Resource("http://www.w3.org/1999/02/22-rdf-syntax-ns#type");
$resources['rLabel']=new Resource("http://www.w3.org/2000/01/rdf-schema#label");
$resources['rDescription']=new Resource("http://www.w3.org/2000/01/rdf-schema#comment");
$resources['rSubClassOf'] = new Resource('http://www.w3.org/2000/01/rdf-schema#subClassOf');
$resources['rSubject'] = new Resource('http://www.w3.org/1999/02/22-rdf-syntax-ns#subject');
$resources['rPredicate'] = new Resource('http://www.w3.org/1999/02/22-rdf-syntax-ns#predicate');
$resources['rObject'] = new Resource('http://www.w3.org/1999/02/22-rdf-syntax-ns#object');
$resources['rCreatedBy'] =  new Resource('http://purl.org/dc/terms/creator');
$resources['rCreatedOn'] =  new Resource('http://purl.org/dc/terms/created');
$resources['rEmail'] =  new Resource('http://xmlns.com/foaf/0.1/mbox');
$resources['rName'] =  new Resource('http://xmlns.com/foaf/0.1/name');

?>