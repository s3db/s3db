// for documentation and rationale see http://sites.google.com/a/s3db.org/s3db/documentation/mis/json-jsonp-jsonpp

function remove_element_by_id (id) {
	var e = document.getElementById(id);
	e.parentNode.removeChild(e);
	return false;
}

function s3dbcall (src,next_eval) {
	call = "call_"+Math.random().toString().replace(/\./g,"");
	// using padded, parameterized jason
	//src=src+"&format=json&jsonp=s3db_jsonpp&jsonpp="+next_eval;
	src=src+"&format=json&callback=s3db_jsonpp&jsonpp="+next_eval;
	var headID = document.getElementsByTagName("head")[0];
	var script = document.createElement('script');
	script.type = 'text/javascript';
	script.src = src ;
	script.id = call;
	headID.appendChild(script);// retrieve answer
	setTimeout("remove_element_by_id('"+script.id+"')",3000); // wait 1 sec and remove the script that asked the question (IE needs it there for a moment, Firefox is ok with immediate deletion)
	}
function s3db_jsonpp (ans,jsonpp) {
	eval(jsonpp);
	return ans}