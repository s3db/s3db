<!-- BEGIN dbconfig -->
<body>
<table border ="0" align="center" width="70%">
        <tr align="center">
                <td>
                        <a href="http://www.s3db.org"><img src="images/s3db.png" alt="S3DB logo" title="S3DB" border="0"></a>
                </td>
        </tr>
</table>

<form method="POST" action="{action_url}">
<table border="0" align="center" width="70%">
   <th colspan="2" bgcolor="{th_bg}" align="right">
    <font color="{th_text}">&nbsp;<b>{title}</b></font><br />
	{logout_button}<!-- <input type="submit" name="logout" value="Log Out"> -->
   </th>
   <tr bgcolor="{db_bg}">
    <td colspan="2" align="center"><font color="{db_text}"><b>{db_title}</b></font></td>
   </tr>
   <tr bgcolor="{db_action_bg}">
	<td align="center" width="20%"><img src="{db_action_img}"></td>
    	<td>
		<table>
			<tr><td>{db_action_text}<br /></td></tr>
			<tr><td>{db_action_config}<br /></td></tr>
			<tr><td align="left">{db_action}</td></tr>
		</table>
	</td>
   </tr>
</table>   
<!-- END dbconfig -->

