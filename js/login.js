function load() {
	
slot = document.getElementById('load');
var table=document.createElement('table');
table.setAttribute('style','background: #DDF0FF; font-style: bold;color: #333300');
table.width="100%"

slot.appendChild(table);

var tr=document.createElement('tr')
table.appendChild(tr);

var td=document.createElement('td');
td.innerHTML='<div style="text-decoration: underline; font-style: italic" onmouseover="Tip(\'For users in other s3db deployments, please pick protocol s3db\')" onmouseout="UnTip()">Protocol</div>';
tr.appendChild(td);

var td=document.createElement('td');
td.innerHTML='<div style="text-decoration: underline; font-style: italic" onmouseover="javascript:if(document.getElementById(\'protocol\').value==\'s3db\'){Tip(\'Authority is the name/deployment_id of the S3DB where user has an account\')} else { Tip(\'Authority is the server \(ftp/ldap\) or the domain, for example, google\')}" onmouseout="UnTip()">Authority</div>';
tr.appendChild(td);

var td=document.createElement('td');
td.innerHTML='Email or Username';
tr.appendChild(td);

var tr = document.createElement('tr');
table.appendChild(tr);

var td=document.createElement('td');
tr.appendChild(td);
var sel = document.createElement('select');
td.appendChild(sel);

sel.id='protocol';
sel.name='protocol';
sel.setAttribute('onchange','trim_authority()');
var options = ['s3db','http','ftp']; //'ldap',,'smtp','svn' to come

for (var i=0; i<options.length; i++) {
var opt = document.createElement('option');
opt.value=options[i];
opt.innerHTML = options[i];
if(document.getElementById('protocol_php') && document.getElementById('protocol_php').value==opt.value){opt.setAttribute('selected','on');}	
sel.appendChild(opt);
}

var td=document.createElement('td');
td.id='authority_slot';
tr.appendChild(td);

var td=document.createElement('td');
td.id='username_slot';
tr.appendChild(td);

var tr = document.createElement('tr');
table.appendChild(tr);

var tab1 = document.createElement('table');
var tr1 = document.createElement('tr');
tab1.appendChild(tr1);
slot.appendChild(tab1);
var td1=document.createElement('td');
td1.id='user_uri_slot';

if(document.getElementById('user_uri_php')){
	td1.innerHTML= "User URI: "+document.getElementById('user_uri_php').value;
	 }
tr1.appendChild(td1);

trim_authority();
}

function trim_authority() {
var slot=document.getElementById('authority_slot');
if(slot.childNodes.length>0){
	slot.removeChild(document.getElementById('authority'));
}
var sel=document.createElement('select');
slot.appendChild(sel);
sel.name='authority';
sel.id='authority';
sel.setAttribute('onChange','display_username_password()');
slot.appendChild(sel);
t={'ldap':['eApps', 'mdanderson' , 'New ldap server...'],'http':[ 'google','New REST ...'],'s3db':['RPPA','TCGA','New s3db ...'],'ftp':['caftps.nci.nih.gov','ftp1.nci.nih.gov','New ftp server...']};
options = t[document.getElementById('protocol').value];
	for (var i=0; i<options.length; i++) {
		var opt = document.createElement('option');
		opt.value=options[i];
		opt.innerHTML = options[i];
		if(document.getElementById('authority_php') && document.getElementById('authority_php').value==opt.value){opt.setAttribute('selected','on');}
		sel.appendChild(opt);
	}
display_username_password();
}


function display_username_password() {
	 var slot=document.getElementById('username_slot');
	 if(!document.getElementById('email'))
	 {
	 var text = document.createElement('input');
	 text.type='text';
	 text.id='email';
	 text.name='email';
	 text.setAttribute('onkeyup','login(event)');
	 if(document.getElementById('email_php') && document.getElementById('email_php').value){
	 text.value = document.getElementById('email_php').value;
	 }
	 slot.appendChild(text);
	 }
	 
	 //now a little trick that i learned with s3db
	 authval=document.getElementById('authority').value;
	 if(authval.match(/^New/)){
	 var slot1 = document.getElementById('authority_slot');
	 slot1.removeChild(document.getElementById('authority'));
	 var text = document.createElement('input');
	 text.type='text';
	 text.id='authority';
	 text.name='authority';
	 //if( authval=='S3DB'){ text.value='http://ibl.mdanderson.org/TCGA/';}
	 text.size='33';
	 slot1.appendChild(text);
	 }
	 
}

function login(ev) {
	if(ev.keyCode==13){
		document.getElementById('form').submit();
	}
}
