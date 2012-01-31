<!-- BEGIN adminacct -->
<table border="0" align="center" width="70%">
   <tr bgcolor="{account_bg}">
    <td colspan="2" align="center"><font color="{account_text}"><b>{account_title}</b></font></td>
   </tr>
   <tr bgcolor="{account_action_bg}">
	<td align="center" width="20%"><img src="{account_action_img}"></td>
    	<td>
		<table>
	 	<tr><td>{account_action_text}</td></tr>
			<tr><td align="center">{account_action}
				<table>
					<tr><td><b>{account_name}</b></td><td>{account_msg}</td></tr>
					<tr><td align="right">Login:</td><td>Admin</td>
					<tr><td align="right">Password:</td><td><input type="password" name="{passname}" value=""></td>
					<tr><td align="right">Re-Type Password:</td><td><input type="password" name="{passcheckname}" value=""></td>
					<tr><td>&nbsp;</td><td><input name="{submitname}" value="{submitvalue}" type="submit"></td>
				</table>
			</td></tr>
		</table>
        </td>
   </tr>
</table>
<!-- END adminacct -->
