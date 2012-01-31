<!-- BEGIN account -->
<!-- BEGIN top -->
<form method="POST" action="{action_url}" autocomplete="on">
<table class="top" align="center">
	<tr><td>
		<table class="insidecontents" align="center" width="{content_width}">
			<tr><td class="message"><br />{message}</td></tr>
		</table>
	</td></tr>
</table>
<!-- END top -->
<!-- BEGIN middle -->
<!--
<div id="contents">
-->
<table class="middle">
	<tr><td>
	<table class="insidecontents" width="{content_width}" align="center">
	<tr bgcolor="#99CCFF"><td align="center">{manager}</td></tr>
	<tr align="center"><td>{data_grid}</td></tr>
	</table>
	</td></tr>
</table>
<!-- END middle -->
<!-- BEGIN profile_edit -->
<table class="middle" width="100%"  align="center">
	<tr><td>
		<table class="insidecontents" width="{content_width}"  align="center" border="0">
			<tr bgcolor="#80BBFF"><td colspan="4" align="center">{edit_message}</td></tr>
			<tr class="odd">
				<td class="info">Login ID</td>
				<td class="account_view">{account_lid}<input type="hidden" name="account_id" value="{account_id}"<input type="hidden" name="account_addr_id" value="{account_addr_id}"</td>
				<td class="info">Account active</td>
				<td class="account_view">{account_status}</td>
			</tr>
<!--
			<tr class="even">
				<td class="info">First Name<sup class="required">{firstname_required}</sup></td>
				<td class="account_view"><input name="account_firstname" value="{account_firstname}">&nbsp;</td>
				<td class="info">Last Name<sup class="required">{lastname_required}</sup></td>
				<td ass="account_view"><input name="account_lastname" value="{account_lastname}">&nbsp;</td>
			</tr>
-->
			<tr class="even">
				<td class="info">User Name</td>
				<td class="account_view">{account_uname}</td>
				<td class="info">Account Type</td>
				<td class="account_view">{account_type}</td>
			</tr>
			<tr class="odd">
				<td class="info">Password<sup class="required">{password_required}</sup></td>
				<td class="account_view"><input type="password" name="account_pwd" value="">&nbsp;</td>
				<td class="info">Re-type Password<sup class="required">{password2_required}</sup></td>
				<td class="account_view"><input type="password" name="account_pwd_2" value="">&nbsp;</td>
			</tr>
			<tr class="even">
				<td class="info">Account Groups</td>
				<td class="account_view" colspan="3">{group_list}</td>
			</tr>
			 <tr class="odd">
                                <td class="info">Created On</td>
                                <td class="account_view">{created_on}</td>
                                <td class="info">Created By</td>
                                <td class="account_view">{created_by}</td>
                        </tr>
                        <tr class="even">
                                <td class="info">Last Modified On</td>
                                <td class="account_view">{modified_on}</td>
                                <td class="info">Last Modified By</td>
                                <td class="account_view">{modified_by}</td>
                        </tr>
			 <tr class="odd">
                                <td class="info">Last Password Changed On</td>
                                <td class="account_view">{account_last_pwd_changed_on}</td>
                                <td class="info">Last Password Changed By</td>
                                <td  class="account_view" >{account_last_pwd_changed_by}</td>
                        </tr>
                        <tr class="even">
                                <td class="info">Last Login On</td>
                                <td  class="account_view">{account_last_login_on}</td>
                                <td class="info">Last Login From</td>
                                <td class="account_view">{account_last_login_from}</td>
                        </tr>
			<tr class="odd">
                                <td class="info" colspan="4">&nbsp;</td>
                        </tr>
			<tr class="even">
				<td class="info">Address 1<sup>{addr1_warn}</sup></td>
				<td class="info"><input name="addr1" value="{addr1}">&nbsp;</td>
				<td class="info">Address 2<sup>{addr1_warn}</sup></td>
				<td class="info"><input name="addr2" value="{addr2}">&nbsp;</td>
			</tr>
			<tr class="odd">
				<td class="info">City<sup>{city_warn}</sup></td>
				<td class="info"><input name="city" value="{city}">&nbsp;</td>
				<td class="info">State<sup>{state_warn}</sup></td>
				<td class="info"><input name="state" value="{state}">&nbsp;</td>
			</tr>
			<tr class="even">
				<td class="info">Postal Code<sup>{postal_code_warn}</sup></td>
				<td class="info"><input name="postal_code" value="{postal_code}">&nbsp;</td>
				<td class="info">Country<sup>{country_warn}</sup></td>
				<td class="info"><input name="country" value="{country}">&nbsp;</td>
			</tr>
			<tr class="odd">
				<td class="info">Email<sup>{email_warn}</sup></td>
				<td class="info"><input name="account_email" value="{account_email}">&nbsp;</td>
				<td class="info">Phone<sup>{phone_warn}</sup></td>
				<td class="info"><input name="account_phone" value="{account_phone}">&nbsp;</td>
			</tr>
		</table>
	</td></tr>
</table>
<!-- END profile_edit -->
<!-- END delete_group -->
<!-- BEGIN bottom -->
<table class="bottom" width="100%"  align="center">
	<tr><td>
	<table class="insidecontents" width="{content_width}"  align="center">
	<tr><td align="left">{action}<br /><br /></td></tr>
	</table>
	</td></tr>
</form>
</table>
<!-- END bottom -->
<!--
</div>
-->
<!-- END account -->
