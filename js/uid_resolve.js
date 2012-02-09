function uid_resolve(uid){
	// //#Valid UID rules
	//# 1.  Individual non-D entities must be preceded by a D entity 
	//# 2. D entities must always resolve to a URL
	//# 3. Entities are separated by #
	//# 4. Entities start with D U P C I R S
	//# 5. Entities must appear according to their order in the core model:
	//# a. P cannot be preceded by C,R,I,S
	//# b. C cannot be preceded by I,R
	//# c. R cannot be preceded by S
	//# d. I cannot be preceded by S
	// A uid may start with a URL, but subsequent Did in the uid string must correspond to the alphanumeric version of the Did uri (why? is this rule really necessary)?
	
		//find all the D
		 var uid_info = {};
		 var tmp1 = uid.match(/([D|(http)][^D]+)/g); // find all D followed by non-D
		 var tmp2 = uid.match(/([D|U|P|C|R|S|I])(\d+)$/);
		
		 var dUIDS = [];
		 //the did is the last one found, if any; no mothership unless there is a Did
		 if(tmp1!==null && typeof(tmp1[1])!=='undefined'){
			tmp4 = tmp1[1].match(/(D\d+|http[^D|P|U|C|R|I|S]+)/);
			uid_info['did'] = tmp4[1];//now find the LAST of the uid, if there are many
			var tmp3 = uid.match(/^(http.+)\/|^(D\d+)/);
		 }
		 else {
			uid_info['did'] = 'local';
		 }
		if(typeof(tmp3)!=='undefined' && tmp3!==null){
			 uid_info['ms'] = tmp3[0];
		}
		else {
			 uid_info['ms'] = 'http://root.s3db.org/';
		}
		 
		 //now resolve only the uid part
		 if(tmp2[1] && tmp2[2]){
			uid_info['origin'] = uid;
			uid_info['letter'] = tmp2[1];
			uid_info['uid'] = tmp2[0];
			uid_info['s3_id'] = tmp2[2];
		 }
		
			
		 
		 
	return uid_info;
}
