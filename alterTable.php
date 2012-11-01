<?php
	function updateTables($db) {
		$sql = "ALTER TABLE s3db_permission ADD COLUMN PL_view integer;";
		$db->query($sql, __FILE__, __LINE__);
	
		$sql = "ALTER TABLE s3db_permission ADD COLUMN PL_change integer";
		$db->query($sql, __FILE__, __LINE__);
	
		$sql = "ALTER TABLE s3db_permission ADD COLUMN PL_use integer;";
		$db->query($sql, __FILE__, __LINE__);
	
		$sql = "ALTER TABLE s3db_permission ADD COLUMN id_num text;";
		$db->query($sql, __FILE__, __LINE__);
	
		$sql = "ALTER TABLE s3db_permission ADD COLUMN id_code text;";
		$db->query($sql, __FILE__, __LINE__);
	
		$sql = "ALTER TABLE s3db_permission ADD COLUMN shared_with_num text;";
		$db->query($sql, __FILE__, __LINE__);
	
		$sql = "ALTER TABLE s3db_permission ADD COLUMN shared_with_code text;";
		$db->query($sql, __FILE__, __LINE__);
	 }
?>