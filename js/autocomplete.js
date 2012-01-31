// ===================================================================
// Author: Matt Kruse <matt@mattkruse.com>
// WWW: http://www.mattkruse.com/
//
// NOTICE: You may use this code for any purpose, commercial or
// private, without any further permission from the author. You may
// remove this notice from your final code if you wish, however it is
// appreciated by the author if at least my web site address is kept.
//
// You may *NOT* re-distribute this code in any way except through its
// use. That means, you can include it in your product, or your web
// site, or any other form where the code is actually being used. You
// may not put the plain javascript up on your site for download or
// include it in your javascript libraries for download. 
// If you wish to share this code with others, please just point them
// to the URL instead.
// Please DO NOT link directly to my .js files from your site. Copy
// the files to your server and use them there. Thank you.
// ===================================================================

//function autoComplete(field, select, forcematch){var found = false;for(var i = 0;i < select.options.length;i++){if(select.options[i][text].toUpperCase().indexOf(field.value.toUpperCase()) == 0){found=true;break;}}if(found){select.selectedIndex = i;}else{select.selectedIndex = -1;}if(field.createTextRange){if(forcematch && !found){field.value=field.value.substring(0,field.value.length-1);return;}var cursorKeys ="8;46;37;38;39;40;33;34;35;36;45;";if(cursorKeys.indexOf(event.keyCode+";") == -1){var r1 = field.createTextRange();var oldValue = r1.text;var newValue = found ? select.options[i][text] : oldValue;if(newValue != field.value){field.value = newValue;var rNew = field.createTextRange();rNew.moveStart('character', oldValue.length) ;rNew.select();}}}}

function autoComplete(field, select, property, forcematch){var found = false;for(var i = 0;i < select.options.length;i++){if(select.options[i][property].toUpperCase().indexOf(field.value.toUpperCase()) == 0){found=true;break;}}if(found){select.selectedIndex = i;}else{select.selectedIndex = -1;}if(field.createTextRange){if(forcematch && !found){field.value=field.value.substring(0,field.value.length-1);return;}var cursorKeys ="8;46;37;38;39;40;33;34;35;36;45;";if(cursorKeys.indexOf(event.keyCode+";") == -1){var r1 = field.createTextRange();var oldValue = r1.text;var newValue = found ? select.options[i][property] : oldValue;if(newValue != field.value){field.value = newValue;var rNew = field.createTextRange();rNew.moveStart('character', oldValue.length) ;rNew.select();}}}}

