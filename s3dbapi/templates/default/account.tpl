<!-- BEGIN account -->
<!-- BEGIN top -->
<form method="POST" action="{action_url}">
<table class="top" align="center">
	<tr><td>
		<table class="insidecontents" align="center" width="{content_width}">
			<tr><td class="message"><br />{message}</td></tr>
			<tr align="center"><td colspan="2" class="current_stage">{current_stage}</td></tr>
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
		<tr align="center"><td>
			{data_grid}
		</td></tr>
	</table>
	</td></tr>
</table>
<!-- END middle -->
<!-- BEGIN user_info_edit -->
<table class="middle" width="100%"  align="center">
	<tr><td>
		<table class="insidecontents" width="{content_width}"  align="center" border="0">
			<tr bgcolor="#80BBFF"><td colspan="4" align="center">{edit_message}</td></tr>
			<tr class="odd">
				<td class="info">Login ID<sup class="required">{loginid_required}</sup></td>
				<td class="info"><input name="account_lid" value="{account_lid}">&nbsp;<input type="hidden" name="account_id" value="{account_id}"<input type="hidden" name="account_addr_id" value="{account_addr_id}"</td>
				<td class="info">Account Status{active_required}</td>
				
				<td class="info"><input type="checkbox" name="account_status" value="{account_status}" {checked}>&nbsp;</td>
			</tr>
			<tr class="even">
				<td class="info">Real Name<sup class="required">{uname_required}</sup></td>
				<td class="info"><input name="account_uname" value="{account_uname}">&nbsp;</td>
				<td class="info">Account Type</td>
				<td ass="info">{account_type}</td>
			</tr>
			<tr class="odd">
				<td class="info">Password<sup class="required">{password_required}</sup></td>
				<td class="info"><input type="password" name="account_pwd" value="">&nbsp;</td>
				<td class="info">Re-type Password<sup class="required">{password2_required}</sup></td>
				<td class="info"><input type="password" name="account_pwd_2" value="">&nbsp;</td>
			</tr>
			<tr class="even">
				<td class="info">Groups</td>
				<td colspan="3"><select name="account_groups[]" multiple>{group_select_list}<option value="-100"></option></select></td>
			</tr>
			<tr class="odd">
				<td class="info">Address 1<sup>{addr1_warn}</sup></td>
				<td class="info"><input name="addr1" value="{addr1}">&nbsp;</td>
				<td class="info">Address 2<sup>{addr1_warn}</sup></td>
				<td class="info"><input name="addr2" value="{addr2}">&nbsp;</td>
			</tr>
			<tr class="even">
				<td class="info">City<sup>{city_warn}</sup></td>
				<td class="info"><input name="city" value="{city}">&nbsp;</td>
				<td class="info">State<sup>{state_warn}</sup></td>
				<td class="info"><input name="state" value="{state}">&nbsp;</td>
			</tr>
			<tr class="odd">
				<td class="info">Postal Code<sup>{postal_code_warn}</sup></td>
				<td class="info"><input name="postal_code" value="{postal_code}">&nbsp;</td>
				<td class="info">Country<sup>{country_warn}</sup></td>
				<td class="info"><input name="country" value="{country}">&nbsp;</td>
			</tr>
			<tr class="even">
				<td class="info">Email<sup>{email_warn}</sup></td>
				<td class="info"><input name="account_email" value="{account_email}">&nbsp;</td>
				<td class="info">Phone<sup>{phone_warn}</sup></td>
				<td class="info"><input name="account_phone" value="{account_phone}">&nbsp;</td>
			</tr>
		</table>
	</td></tr>
</table>
<!-- END user_info_edit -->
<!-- BEGIN user_info_view -->
<table class="middle" width="100%"  align="center">
	<tr><td>
		<table class="insidecontents" width="{content_width}"  align="center" border="0">
			<tr bgcolor="#80BBFF"><td colspan="4" align="center">{view_message}</td></tr>
			<tr class="odd">
				<td class="info">Login ID</td>
				<td class="account_view">{account_lid}<input type="hidden" name="account_id" value="{account_id}"<input type="hidden" name="account_addr_id" value="{account_addr_id}"</td>
				<td class="info">Account Status</td>
<!--
				<td class="info"><input type="checkbox" name="account_status" value="{account_status}" {checked}>&nbsp;</td>
-->
				<td  class="account_view">{account_status}</td>
			</tr>
			<tr class="even">
				<td class="info">Real Name</td>
				<td  class="account_view">{account_uname}</td>
				<td class="info">Account Type</td>
				<td  class="account_view">{account_type}</td>
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
	<!--
				<td width="25%" align="left">Account Type</td>
				<td width="25%" align="left">{account_type}</td>
-->
				<td class="info">Account Groups</td>
				<td  class="account_view" colspan="3" >{account_groups}</td>
			</tr>
			<tr class="even">
				<td class="info">Last Password Changed On</td>
				<td  class="account_view">{account_last_pwd_changed_on}</td>
				<td class="info">Last Password Changed By</td>
				<td  class="account_view" >{account_last_pwd_changed_by}</td>
			</tr>
			<tr class="odd">
				<td class="info">Last Login On</td>
				<td  class="account_view">{account_last_login_on}</td>
				<td class="info">Last Login From</td>
				<td class="account_view">{account_last_login_from}</td>
			</tr>
			<tr class="even">
				<td class="info" colspan="4">&nbsp;</td>
			</tr>
			<tr class="odd">
				<td class="info">Address 1</td>
				<td  class="account_view">{addr1}</td>
				<td class="info">Address 2</td>
				<td  class="account_view">{addr2}</td>
			</tr>
			<tr class="even">
				<td class="info">City</td>
				<td  class="account_view">{city}</td>
				<td class="info">State</td>
				<td  class="account_view">{state}</td>
			</tr>
			<tr class="odd">
				<td class="info">Postal Code</td>
				<td  class="account_view">{postal_code}</td>
				<td class="info">Country</td>
				<td  class="account_view">{country}</td>
			</tr>
			<tr class="even">
				<td class="info">Email</td>
				<td  class="account_view">{account_email}</td>
				<td class="info">Phone</td>
				<td  class="account_view">{account_phone}</td>
			</tr>
		</table>
	</td></tr>
</table>
<!-- END user_info_view -->
<!-- BEGIN delete_user -->
<table class="middle" width="100%"  align="center">
	<tr><td>
		<table class="insidecontents" width="{content_width}"  align="center" border="0">
			<tr bgcolor="#80BBFF"><td colspan="2" align="center">{delete_message}<input type="hidden" name="account_id" value="{account_id}"></td></tr>
			<tr class="odd">
				<td class="info">{prompt}</td>
				<td><select name="account_users" size="5"><option value="0" selected>Delete all projects</option>{user_list}</select></td>
			</tr>
		</table>
	</td></tr>
</table>
<!-- END delete_user -->
<!-- BEGIN group_info_edit -->
<table class="middle" width="100%"  align="center">
	<tr><td>
		<table class="insidecontents" width="{content_width}"  align="center" border="0">
			<tr bgcolor="#80BBFF"><td colspan="7" align="center">{edit_message}<input type="hidden" name="account_id" value="{account_id}"><input type="hidden" name="account_lid" value="{account_lid}"></td></tr>

			<tr class="odd">
				<td class="info">Group Name<sup class="required">{group_name_required}</sup></td>
				<td class="info"><input name="account_lid" value="{account_lid}">&nbsp;</td>
	
				<td class="info">Users to be added to this group</td>
				<td colspan="3"><select name="account_users[]" multiple>{user_list}<option value="-100"></option></select></td>
			</tr>
		</table>
	</td></tr>
</table>
<!-- END group_info_edit -->
<!-- BEGIN delete_group -->
<table class="middle" width="100%"  align="center">
	<tr><td>
		<table class="insidecontents" width="{content_width}"  align="center" border="0">
			<tr bgcolor="#80BBFF"><td align="center">{delete_message}<input type="hidden" name="account_id" value="{account_id}"></td></tr>
			<tr class="odd">
				<td class="info" align="center">{active_members}<br /><b>{prompt}</b><br />Do you really want to delete this group?<br /><br /></td>
			</tr>
		</table>
	</td></tr>
</table>
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
