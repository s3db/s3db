<!-- BEGIN login_form -->
<body onload="if (self != top) top.location = self.location">

<table border ="0" align="center">

	<tr align="center">
		<td><br /><br /><br /><br /><br />
		</td>
	</tr>	

</table>
<TABLE border="0" cellpadding="0" cellspacing="0" width="40%" align="center">
 <TR>
  <TD>
   <TABLE border="0" width="100%" bgcolor="#486591" cellpadding="2" cellspacing="1">
    <TR bgcolor="#e6e6e6">
     <TD valign="BASELINE">

		<FORM id ="login_form" name="login" method="post" action="{login_url}" {autocomplete}>
		<input type="hidden" name="passwd_type" value="text">
			<TABLE border="0" align="CENTER"  width="100%" cellpadding="0" cellspacing="0">
				<TR align="center">
					<td colspan="2">
      						<img src="images/login_top.gif" WIDTH="100%" HEIGHT="46" BORDER="0" ALT="">
						
					</td>
				</TR>
				<TR bgcolor="#e6e6e6">
					<TD colspan="2" align="CENTER"><br /><font color="red">&nbsp;{error}&nbsp;</font><br /><br /></TD>
				</TR>
				<TR bgcolor="#e6e6e6">
					{authorities}
				

				</TR>
				<TR bgcolor="#e6e6e6">
					<TD align="RIGHT" width="40%"><font color="#000000"><i><a href="#" title="If you have an S3DB Id in another deployment, just login with your remote UID or your email">{username}</a></i>:&nbsp;</font></TD>
					 <TD><nobr>{input_login}</nobr></TD>
				

				</TR>
				
				<TR bgcolor="#e6e6e6">
					<TD align="RIGHT" width="40%"><font color="#000000"><i>{password}</i>:&nbsp;</font></TD>
					<TD><input name="passwd" id="password" type="password" value="">{lost_my_password}</TD>
				
				</TR>
				
				<tr bgcolor="#e6e6e6">
					<td width="40%">&nbsp;</td>
					<td align="left"><input type="submit" value="{login}" name="submit">&nbsp;&nbsp;&nbsp;&nbsp;</td>
				</tr>
				<tr bgcolor="#e6e6e6">
				{different_user_link}
				</tr>
				</FORM>
				
				
				<!-- <TR bgcolor="#e6e6e6">
					<form method="get" action="openID/try_auth.php">
					<input type="hidden" name="action" value="verify" />
					<TD align="RIGHT" width="40%"><font color="#000000"><i>OpenID login</i>:&nbsp;</font></TD>
					 <TD>{input_openID}</TD>

				

				</TR>
				<TR bgcolor="#e6e6e6">
					<td width="40%">&nbsp;</td>
					<TD align="left"><input type="submit" value="Verify" />
					</form>
					</TD>
				</TR> -->
				<TR bgcolor="#e6e6e6">
					<TD colspan="1" align="left"><a href=http://www.s3db.org><font color=#000000 size="3">www.s3db.org</font></a><BR>
					<a href=http://code.google.com/p/s3db/issues/>Click here to report bugs</a>
					</TD><TD colspan="1" align="right"><font color="#000000" size="2"><br/><i>S<sup>3</sup>DB&nbsp;&nbsp; {version}</i></font></TD>
					
				</TR> 
				<TR>
					<TD></TD>
					<TD colspan="1" align="right"><font color="#000000" size="2"><i>{Did}</i></font></TD>
				</TR>
			</TABLE>
		
      
     </TD>
    </TR>
   
   </TABLE>
  </TD>
  
 
 </TR>
 
<TR>
		<TD><i>Note: There is a public login in this S3DB. To login as public, please use "public" as username and "public" as password.</i></TD>
 </TR>
 <TR>
		<TD><BR>{license}</TD>
		
 </TR>
</TABLE>

<br><br><br>
<table border="0" width="50%" align="center">
<TR>
<TD></TD>
</TR>

</table>
<script type="text/javascript">
	 	function reset_password_redirect()
		{
		var username = document.forms.login_form.login.value;
		var reset_password = "reset_password.php?login=" +	username;
		window.location=(reset_password);
		}
		
</script>
<script src="urchin.js" type="text/javascript">
</script>
<script type="text/javascript">
_uacct = "UA-324999-9";
urchinTracker();
</script>

</body>
</HTML>

<!-- END login_form -->
