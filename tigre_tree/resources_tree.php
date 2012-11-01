<html>
	<head>
		<title>JavaScript Tree Menu</title>
		<style>
			a, A:link, a:visited, a:active
			{
				color: #0000aa; 
				text-decoration: none; 
				font-family: Tahoma, Verdana; 
				font-size: 16px
			}
			A:hover
			{
				color: #ff0000; 
				text-decoration: none; 
				font-family: Tahoma, Verdana; 
				font-size: 16px
			}
			p, tr, td, ul, li
			{
				color: #000000; 
				font-family: Tahoma, Verdana; 
				font-size: 11px
			}
			.header1, h1
			{
				color: #ffffff; 
				background: #4682B4; 
				font-weight: bold; 
				font-family: Tahoma, Verdana; 
				font-size: 18px; 
				margin: 0px; 
				padding: 2px;
			}
			.header2, h2
			{
				color: #000000; 
				background: #DBEAF5; 
				font-weight: bold; 
				font-family: Tahoma, Verdana; 
				font-size: 17px;
			}
			.intd
			{
				color: #000000; 
				font-family: Tahoma, Verdana; 
				font-size: 11px; 
				padding-left: 15px; 
				padding-right: 20px;
			}
			.ctrl
			{
				font-family: Tahoma, Verdana, sans-serif; 
				font-size: 17px; 
				width: 100%; 
			}
			form
			{ 
				margin: 2px;
			}
		</style>
	</head>
	<body bgcolor=white bottommargin="15" topmargin="15" leftmargin="15" rightmargin="15" marginheight="15" marginwidth="15">
		<script language="JavaScript" src="tree.js"></script>
		<script language="JavaScript" src= <?php echo $tree_items_file; ?> ></script>
		<script language="JavaScript" src="tree_tpl.js"></script>
		<!-- Sample -->
		<script language="JavaScript">
			<!--
			new tree (TREE_ITEMS, tree_tpl);
			//-->
		</script>
		<br />
		<input type="button" value="Refresh" onClick="window.location.reload()" />
	</body>
</html>