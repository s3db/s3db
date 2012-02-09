var GET = [];
function get() {
	var query = unescape(window.location.search.replace("?",""));
	if(query){
		//Separate the parameters of the query
		
		var splitQuery = query.split("&");
		for (var i=0; i<splitQuery.length; i++) {
			if(typeof(splitQuery[i])==='string'){
				var tmp = splitQuery[i].match(/([A-Za-z0-9_]+)=(.*)/);
				if(tmp){
					GET[tmp[1]] = tmp[2];
				}
			}

		}

	
	}
}