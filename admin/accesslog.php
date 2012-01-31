<?php
	#accesslog.php list all the logins 
	#Helena F Deus (helenadeus@gmail.com)
	include('adminheader.php');
	
	$section_num = '2';
	$website_title = $GLOBALS['s3db_info']['server']['site_title'].' - access log';
	$site_intro = $GLOBALS['s3db_info']['server']['site_intro'];
	$manager= 'Access Log';
	$content_width='80%';
	
		
	$s3ql=compact('user_id','db');
	$s3ql['select']='*';
	$s3ql['from']='accesslog';
	if ($_REQUEST['orderBy']!='') {
		$s3ql['order_by']=$_REQUEST['orderBy'].' '.$_REQUEST['direction'];
	}
	else {
		$s3ql['order_by']='login_timestamp desc';
	}
	$logs = S3QLaction($s3ql);

	#echo '<pre>';print_r($logs);exit;
	if(count($logs) > 0)	
		$data_grid = render_elements($logs, '', array('Login ID', 'Login From', 'Login Time'), 'accesslog');
		 

	
	include(S3DB_SERVER_ROOT.'/s3style.php');
	include(S3DB_SERVER_ROOT.'/tabs.php');

?>
<!-- BEGIN top -->

<table class="top" align="center">
	<tr><td>
		<table class="insidecontents" align="center" width="<?php echo $content_width ?>">
			<tr><td class="message"><br /><?php echo $message ?></td></tr>
			
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
	<table class="insidecontents" width="<?php echo $content_width ?>" align="center">
		<tr bgcolor="#99CCFF"><td align="center"><?php echo $manager ?></td></tr>
		<tr align="center"><td>
			<?php echo $data_grid ?>
		</td></tr>
	</table>
	</td></tr>
</table>
<!-- END middle -->
<?php
include(S3DB_SERVER_ROOT.'/footer.php');
?>