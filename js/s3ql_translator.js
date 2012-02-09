var S3QLtranslator = function (query, core) {
	if(typeof(core)=='undefined'){
		//alert('You need to define a variable named "core" as the name for your included core structure');
		var s3db = {
			name : 's3db',
			entities : ["deployment", "user", "project", "collection", "rule","item","statement"],
			entity_ids : {D:'deployment_id',U:'user_id',P:'project_id',C:'collection_id',R:'rule_id',I:'item_id',S:'statement_id'},
				
			relationships : {
				DP : {domain : 'deployment', range : 'project' },
				PC : {domain : 'project', range : 'collection' },
				PR : {domain : 'project', range : 'rule' },
				CI : {domain : 'collection', range : 'item' },
				Rsubject : {domain : 'collection', range : 'rule' },
				Robject : {domain : 'collection', range : 'rule' },
				Rpredicate : {domain : 'item', range : 'rule' },
				Ssubject : {domain : 'item', range : 'statement' },
				Sobject : {domain : 'item', range : 'statement' },
				Spredicate : {domain : 'rule', range : 'statement' },
				DU : {domain : 'deployment', range : 'user' },
				DU : {domain : 'user', range : 'user' }		
			},

			
			actions : ["select", "insert", "update", "delete" ],
			entitySymbols : { D :"deployment",  U :"user",  P:"project", C:"collection",R:"rule", I:"item", S:"statement"},
			globalAttributes : ["id", "label", "description", "created", "creator"],
			
			specificAttributes: {
				deployment: [],
				user : ['username','email'],
				project: [],
				collection: ["project_id"],
				rule: ["project_id", "subject_id", "verb_id", "object_id"],
				item: ["collection_id"],
				statement : ["item_id", "rule_id", "value"]
			
			}
		};
		core = s3db;
	}
	//start of by reading all that is before the first |
	//var entityNames = {"S":"statement", "R": "rule","C":"collection","I":"item","P":"project","U":"user","D":"deployment"};
	if(typeof(core.entities)!=='undefined' && typeof(core.entities)=='object'){
		var entityNames = core.entities;
	}
	else {
		var entityNames = [];
	}
	
	var s3ql_query = "";
	
	//Detect any operation specification; separate components so that each can be trimmed
	var actions = ''; 
	$.each(core.actions, function(index, value) {  
		if(index!==0)  actions += '|'; actions += value; 
	});
	var op = query.trim().match(actions);
	if(op) { 
	op = op[0].trim(); 
	var targetAndParams = query.replace(op,"").trim().match(/\((.*)\)/);
	
	if(!targetAndParams){
		console.log('invalid query - parameters are required to be inside parenthesis');
		return false;
		}
	targetAndParams = targetAndParams[1];
	}
	else {
		op = "select";
		var targetAndParams = query.trim();
	}
	
	var symbols = '';var ind=0; 
	$.each(core.entitySymbols, function(index, value) { 
		//if(ind!==0) symbols += '|'; 
		symbols += index; ind++;
	} ) ;

	var target = targetAndParams.trim().match('^['+symbols+']');
	
	if(!target){
		console.log('invalid query - one of '+symbols+' is required to initialize the query');
		return false;
	}
	target = target[0];
	
	//var template = targetAndParams.trim().match(target+'\\.[^|, ]+','g');
	var t = targetAndParams.trim().match('[^|]*|');
	var template = t[0].split(',');
	var search = '';
	if(template){
		for (var i=0; i<template.length; i++) {
			if(template[i]!=='' && template[i].trim()!==target){
				search += template[i].trim().replace(target+'\.','');
				if(i!==template.length-1){
					search += ',';
				}

			}
		}
	}

	var params = targetAndParams.trim().match(/\|(.*)/);
	

	//Detect if there is more than 1 paramenter
	var s3ql_params = "";
	if(params){
		s3ql_params += "<where>";
		params = params[1].trim();
		var p = params.split(",");
		for (var i=0; i<p.length; i++) {
			 var pi = p[i].trim();
			 var attrValue = pi.match(/(.*)=(.*)/);
			 if(attrValue){
				var attr = attrValue[1].trim();
				var value = attrValue[2].trim();
				if(attr && value){
					if(core.entitySymbols[attr]) {attr =core.entity_ids[attr];}
					
				}
				
			 }
			 else if (pi.match('['+symbols+'](.*)')) {
					
					letterSymbol = pi.match('('+symbols+')([0-9]+| (.+))');
					if(typeof(core.name)!=='undefined' && core.name=='s3db'){
						var uid_info = uid_resolve(pi);
					}
					else {
						uid_info = {'letter' : letterSymbol[1], 's3_id': letterSymbol[2] }
					}
					
					attr = core.entity_ids[uid_info['letter']];
					value = uid_info['origin'];
					
			}
			s3ql_params += "<"+attr+">"+value+"</"+attr+">";
		}
		s3ql_params += "</where>";
	}
	
	//Now build the s3ql query
	if(op=="select"){
		if(typeof(search)==='undefined' || search ==''){
			search = '*';
		}
		s3ql_query += "<S3QL><select>"+search+"</select><from>"+core.entitySymbols[target]+"</from>";
	}
	else {
		s3ql_query += "<S3QL><"+op+">"+core.entitySymbols[target]+"</"+op+">";
	}
	s3ql_query += s3ql_params;
	s3ql_query += "</S3QL>";
	return s3ql_query;
	
}

String.prototype.trim = function () {
    return this.replace(/^\s*/, "").replace(/\s*$/, "");
}

