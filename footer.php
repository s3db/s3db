<!-- BEGIN footer -->
<TABLE class="footer" cellspacing="0" cellpadding="0" width="100%">
	<TR bgcolor="dodgerblue"></TR>
	<TR>
		<TD align="left">&nbsp;Copyright &copy; 2008 <a href="mailto:hdeus@s3db.org">Helena F. Deus</a> & <a href="mailto:jalmeida@mathbiol.org">Jonas Almeida</a> All rights reserved<br /></TD>
		<TD align="center">Updated: <?php echo $t=(($release!='')?date("l, dS F, Y @ h:ia", strtotime($release)):date("l, dS F, Y @ h:ia",filemtime(S3DB_SERVER_ROOT.'/s3dbupdates.rdf'))); ?><br /></TD>
		<TD align="right">Powered by <a href="http://www.s3db.org" target="_blank">S<sup>3</sup>DB</a> &nbsp;<br></TD>
	</TR>
</TABLE>
<!-- END footer -->
