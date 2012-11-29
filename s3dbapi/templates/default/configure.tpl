<!-- BEGIN header -->
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN">
<HTML>
<HEAD>
<META http-equiv="Content-Type" content="text/html; charset={charset}">
<META name="AUTHOR" content="S3DB http://www.s3db.org">
<META NAME="description" CONTENT="S3DB">
<META NAME="keywords" CONTENT="S3DB">
<meta name="robots" content="none">
<LINK REL="ICON" href="{img_icon}" type="image/x-ico">
<LINK REL="SHORTCUT ICON" href="{img_shortcut}">
<link rel="stylesheet" type="text/css" href="css/tab.css">
<TITLE>{website_title}</TITLE>
<script language="javascript" src="js/jquery.js"></script>
<script>

function hideAndShow() {
    
	if($('.advanced').length==0){
	$('.advanced_show').removeClass('advanced_show').addClass('advanced');
	$('#showLink').html('Show Advanced Options')
	}
	else {
	$('.advanced').removeClass('advanced').addClass('advanced_show');
	$('#showLink').html('Hide Advanced Options');
	}
}


</script>
<style type="text/css" media="screen">


.advanced {
	display: none;
	
}
.advanced_show {
	display: inline;
	font-style: italic;
	color: #0033FF;
}
.message {
	font-style: bold;
	color: #FF0000
}
</style>

</HEAD>
<body>
<!-- END header -->
<!-- BEGIN site_configure -->
<table border ="0" align="center" width="80%">
        <tr align="center">
                <td>
                        <a href="http://www.s3db.org"><img src="images/logo.png" alt="S3DB" title="S3DB" border="0"></a>
                </td>
        </tr>
</table>

<form method="POST" action="{action_url}">
<table border="0" align="center" width="80%">
   <th colspan="2" bgcolor="{th_bg}">
	<td></td>
	<td></td>
   </th>

   <tr bgcolor="royalblue">
    <td align="center" colspan="2"><font color="white"><b>S3DB Site Configuration</b></font></td>
	<td><a href="javascript:hideAndShow()" style="color:white; font-style: italic;" id="showLink">Show Advanced Options</a></td>
	<input type="hidden" name="ip" value=REMOTE_ADDR>
   </tr>
   
   <tr>
     <td colspan="2" align="left"><font color="red">{error}</font></td>
   </tr>
   <tr>
   <!--  <td colspan="2"><br /><br /></td> --> 
   </tr>
   <tr>
     <td width="30%" align="right"><div class="advanced"><sup><font color="red">{server_root_required}</font></sup>Server Root</div></td>
     <td><div class="advanced"><input name="server_root" type="text" style="background: lightyellow" size="30" value="{server_root}"></div></td>
	 <td><div class="advanced"><font color="navy" size="2">Server Root is the path to the s3db folder.</font></div></td>
   </tr>
   <tr>
     <td align="right"><div class="advanced"><sup><font color="red">{uri_base_required}</font></sup>URI Base</div></td>
     <td><div class="advanced"><input name="uri_base" type="text" style="background: lightyellow" size="30" value="{uri_base}"></div></td>
	 <td><div class="advanced"><font color="navy" size="2">URI Base is the URL through which you will be accessing S3DB. If you are using a re-direct, please indicate it here.</font></div></td>
   </tr>
   <tr>
     <td align="right"><div class="advanced"><sup><font color="red">{site_logo_required}</font></sup>Site Logo</div></td>
     <td><div class="advanced"><input name="site_logo" type="text" style="background: lightyellow" size="30" value="{site_logo}"></div></td>
   </tr>
   <tr>
     <td align="right"><sup><font color="red">{site_title_required}</font></sup>Name</td>
     <td><input name="site_title" type="text"  style="background: lightyellow" size="30" value="{site_title}"></td>
	 <td><font color="navy" size="2">This name will be used to register this S3DB. <b>Please chose a descriptive name, as names must be unique</b>, or leave this field empty and a name will automatically be generated.</font></td>
   </tr>
   <tr>
     <td align="right"><sup><font color="red">{site_intro_required}</font></sup>Deployment Description</td>
     <td><textarea name="site_intro" style="background: lightyellow" cols="40" rows="5">{site_intro}</textarea></td>
    <td><font color="navy" size="2">Use the Deployment Information</a> as a starting introduction to make your Deployment "discoverable" among the S3DB community.</font></td>
	</tr>
    
	<tr>
		<td align="right">Deployment Keywords</td>
		<td><input name="deployment_keywords" type="text"  style="background: lightyellow" size="30" value="{deployment_keywords}"></td>
		<td><font color="navy" size="2">You can add a few descriptive keywords about your deployment. Please use comma-separated values.</font></td>
		
	</tr>
	<tr>
		<td align="right">Your Name and Email</td>
		<td><input name="userName" type="text"  style="background: lightyellow" size="30" value="{userName}"><br /><input name="email" type="text"  style="background: lightyellow" size="30" value="{email}"></td>
		<td><font color="navy" size="2">If you like our work, let us know who you are and how we can contact you about news in the S3DB community. <b>Your email will not be made public nor used for any other purposes other than issues related to S3DB.</b></font></td>
	</tr>
   <tr>
    <td>
	</td>
	
	<td><input type="hidden" name="site_config_admin_pass" style="background: lightyellow" size="30" value="{site_config_admin_pass}">
	</td>
   </tr>
   <tr>
     <!-- <td align="right"><sup><font color="red">{site_config_admin_required}</font></sup>Site Configuration Admin</td> -->
     <td><input type="hidden" name="site_config_admin" style="background: lightyellow" size="30" value="{site_config_admin}"></td>
   </tr>
  
   
   <tr>
     <td><br /></td>
   </tr>
   <tr>
     <td align="right"><div class="advanced"><sup><font color="red">{db_type_required}</font></sup>Database Type</div></td>
     <td><div class="advanced"><select name="db_type">
	{db_options}
	</select></div></td>
	 <td>
	<font color="navy" size="2">{database_message}</font>
	</td>
   </tr>
   <tr>
     <td align="right"><div class="advanced"><sup><font color="red">{db_host_required}</font></sup>Database Host</div></td>
     <td><div class="advanced"><input type="text" name="db_host" style="background: lightyellow" size="30" value={db_host_default}></input></div></td>
	 <td>
	<font color="navy" size="2">{db_host_message}</font>
	</td>
   </tr>
<!-- 
   <tr>
     <td align="right"><sup><font color="red">{db_host_required}</font></sup>Host Name</td>
     <td><div class="advanced"><input type="hidden" name="db_host" style="background: lightyellow" size="30" value="{db_host}"></div></td>
   </tr>
-->   
   <tr>
     <td align="right"><div class="advanced"><sup><font color="red">{db_name_required}</font></sup>Database Name</div></td>
     <td><div class="advanced"><input name="db_name" style="background: lightyellow" size="30" value="{db_name}"></div></td>
	
   </tr>
   <tr>
    
	 <td align="right"><div class="advanced"><sup><font color="red">{db_user_required}</font></sup>Database User</div></td>
     <td><div class="advanced"><input name="db_user" style="background: lightyellow" size="30" value="{db_user}"></div></td>
	 
   </tr>
	
   <tr>
     
	 <td align="right"><div class="advanced"><sup><font color="red">{db_pass_required}</font></sup>Database Password</div></td>
     <td><div class="advanced"><input type="password" name="db_pass" style="background: lightyellow" size="30" value="{db_pass}"></div></td>
   
   </tr>
   
   <tr>
     <td align="right"><div class="advanced">Uploads Folder</div></td>
     <td><div class="advanced"><input type="text" name="uploads_folder" style="background: lightyellow" size="30" value="{server_root}/extras/"></div>
	 </td>
	 <td>
	 <div class="advanced"><font color="navy" size="2">Note: uploads folder will be where uplodaded files will be stored. The avoid making them accessible on the browser, select a folder that cannot be accessed on the browser.</font></div>
	 </td>
	</tr>
	
<tr>
     <td width="30%" align="right"><div class="advanced"><sup><font color="red"></font></sup>Email Host</div></td>
     <td><div class="advanced"><input name="email_host" style="background: lightyellow" size="30" value="{email_host}"></div></td>
   </tr>
   <tr>
     <!-- <td width="30%" align="right"><sup><font color="red"></font></sup>Central Host (mothership)</td> -->
     <td><input type="hidden" name="mothership" style="background: lightyellow" size="30" value="{mothership}"></td>
   </tr>
   <tr>
   <tr>
     
     <td width="30%" align="right">{mothership_intro}</td>
     <td>{mothership_text}</td>
	 <td><font color="navy" size="2">To Register in another S3DB, please use the <a href="javascript:hideAndShow()">Advanced</a> options</font></td>
	 <br>{uncheck}
	</td>
	<tr>
	<!-- <td width="30%" align="right"><div class="advanced">Choose a different Registry URL</div></td>
	<td><div class="advanced"><input type="text" name = "mothership_new"></div></td> -->
	<td><div class="message">{mothership_error}</div></td>
	</tr>
   
   <tr>
     <td><br /></td>
   </tr>
   <tr>
	<td>&nbsp;</td>
	<td><input type="submit" name="{save_create}_configuration" value="{save_create} Configuration">&nbsp;&nbsp;&nbsp;&nbsp;{db_config}&nbsp;&nbsp;&nbsp;&nbsp;{log_out}</td> 
	
	
   </tr>
</table>   
</body>
</html>
<!-- END site_configure -->