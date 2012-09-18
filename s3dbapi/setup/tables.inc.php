<?php
	$s3db_tables = array(
		's3db_project' => array(
			'fd' => array(
				'project_id' => array('type' => 'varchar','precision' => '255','nullable' => False),
				'project_name' => array('type' => 'varchar','precision' => '255','nullable' => False),
				'project_folder' => array('type' => 'varchar','precision' => '255'),
				'uri' => array('type' => 'varchar','precision' => '255','nullable' => True),
				'project_owner' => array('type' => 'varchar','precision' => '255','nullable' => False),
				'project_description' => array('type' => 'varchar','precision' => '255'),
				'project_status' => array('type' => 'char','precision' => '1','nullable' => False,'default' => 'A'),
				'status' => array('type' => 'varchar','precision' => '255','nullable' => True,'default' => 'A'),
				'created_on' => array('type' => 'timestamp','nullable' => False),
				'created_by' => array('type' => 'varchar','precision' => '255','nullable' => False),
				'modified_on' => array('type' => 'timestamp','nullable' => True),
				'modified_by' => array('type' => 'varchar','precision' => '255','nullable' => True)
			),
			'pk' => array('project_id'),
			'fk' => array(),
			'ix' => array(),
			'uc' => array()
		),
		's3db_deployment' => array(
			'fd' => array(
				'deployment_id' => array('type' => 'varchar','precision' => '255','nullable' => False),
				'url' => array('type' => 'varchar','precision' => '255','nullable' => True),
				'publickey' => array('type' => 'varchar','precision' => '255','nullable' => False),
				'checked_on' => array('type' => 'timestamp', 'nullable' => True),
				'checked_valid' => array('type' => 'timestamp','nullable' => True),
				'message' => array('type' => 'varchar','precision' => '255'),
				'created_on' => array('type' => 'timestamp','nullable' => True),
				'modified_on' => array('type' => 'timestamp','nullable' => True),
			),
			'pk' => array('deployment_id'),
			'fk' => array(),
			'ix' => array(),
			'uc' => array()
		),
		's3db_permission' => array(
			'fd' => array(
				'uid' => array('type' => 'varchar','precision' => '255', 'nullable' => False),
				'shared_with' => array('type' => 'varchar','precision' => '255'),
				'permission_level' => array('type' => 'varchar','precision' => '255'),
				'pl_view' => array('type' => 'char','precision' => '1', 'nullable' => True),
				'pl_change' => array('type' => 'char','precision' => '1', 'nullable' => True),
				'pl_use' => array('type' => 'char','precision' => '1', 'nullable' => True),
				'id_num' => array('type' => 'varchar','precision' => '255', 'nullable' => True),
				'id_code' => array('type' => 'varchar','precision' => '1', 'nullable' => True),
				'shared_with_num' => array('type' => 'varchar','precision' => '255', 'nullable' => True),
				'shared_with_code' => array('type' => 'varchar','precision' => '1', 'nullable' => True),
				'id' => array('type' => 'varchar','precision' => '255'),
				'created_by' => array('type' => 'varchar','precision' => '255'),
				'created_on' => array('type' => 'timestamp')
			),
			'pk' => array('uid, shared_with'),
			'ix' => array(),
			'fk' => array(),
			'uc' => array()
		),

		's3db_shared' => array(
			'fd' => array(
			'uid_code'=>array('type' => 'varchar','precision' => '1', 'nullable' => False),
			'uid_num'=>array('type' => 'varchar','precision' => '255', 'nullable' => False),
			'relation'=>array('type' => 'varchar','precision' => '3', 'nullable' => False),
			'shared_with_code'=>array('type' => 'varchar','precision' => '1', 'nullable' => False),
			'shared_with_num'=>array('type' => 'varchar','precision' => '255', 'nullable' => False),
			'created_by' => array('type' => 'varchar','precision' => '255', 'nullable' => False),
			'created_on' => array('type' => 'timestamp', 'nullable' => False)
			),
			'pk' => array('uid_code,uid_num,shared_with_code,shared_with_num'),
		
		),

		's3db_file_transfer' => array(
			'fd' => array(
				'file_id' => array('type' => 'varchar','precision' => '255', 'nullable' => False),
				'filename' => array('type' => 'varchar','precision' => '255'),
				'filesize' => array('type' => 'varchar','precision' => '255'),
				'status' => array('type' => 'varchar','precision' => '255'),
				'expires' => array('type' => 'timestamp', 'nullable' => False),
				'filekey' => array('type' => 'varchar','precision' => '255', 'nullable' => False),
				'created_by' => array('type' => 'varchar','precision' => '255')
			),
			'pk' => array('file_id'),
			'ix' => array(),
			'fk' => array(),
			'uc' => array()
		),
		's3db_access_keys' => array(
			'fd' => array(
				'key_id' => array('type' => 'varchar','precision' => '255'),
				'account_id' => array('type' => 'varchar','precision' => '255'),
				'expires' => array('type' => 'timestamp','precision' => '255'),
				'notes' => array('type' => 'blob','precision' => '4'),
				'uid' => array('type' => 'varchar','precision' => '255')

						),
			'pk' => array('key_id'),
			'ix' => array(),
			'fk' => array(),
			'uc' => array()
		),

		's3db_access_rules' => array(
			'fd' => array(
				'project_id' => array('type' => 'varchar','precision' => '255'),
				'rule_id' => array('type' => 'varchar','precision' => '255'),
				'account_id' => array('type' => 'varchar','precision' => '255'),
				'notes' => array('type' => 'blob','precision' => '4'),
				'status' => array('type' => 'blob','precision' => '4'),
				'requested_on' => array('type' => 'timestamp','precision' => '4'),
				'uri' => array('type' => 'blob','precision' => '4')

						),
			'pk' => array('project_id, rule_id'),
			'ix' => array(),
			'fk' => array(),
			'uc' => array()
		),
		's3db_account' => array(
			'fd' => array(
				'account_id' => array('type' => 'varchar','precision' => '255','nullable' => False),
				'account_lid' => array('type' => 'varchar','precision' => '255','nullable' => False),
				'account_pwd' => array('type' => 'varchar','precision' => '255','nullable' => False),
				'account_uname' => array('type' => 'varchar','precision' => '50','nullable' =>False),
				'account_email' => array('type' => 'varchar','precision' => '50','nullable' => True),
				'account_phone' => array('type' => 'varchar','precision' => '50','nullable' => True),
				'account_addr_id' => array('type' => 'varchar','precision' => '255','nullable' => True),
				'account_group' => array('type' => 'char','precision' => '1','nullable' => True),
				'account_last_login_on' => array('type' => 'timestamp','nullable' => True),
				'account_last_login_from' => array('type' => 'varchar','precision' => '25','nullable' => True),
				'account_last_pwd_changed_on' => array('type' => 'timestamp','nullable' => True),
				'account_last_pwd_changed_by' => array('type' => 'varchar','precision' => '255','nullable' => True),
				'account_status' => array('type' => 'char','precision' => '1','nullable' => False,'default' => 'A'),
				'account_type' => array('type' => 'char','precision' => '1','nullable' => True),
				'created_on' => array('type' => 'timestamp','nullable' => False),
				'created_by' => array('type' => 'varchar','precision' => '255','nullable' => False),
				'modified_on' => array('type' => 'timestamp','nullable' => True),
				'modified_by' => array('type' => 'varchar','precision' => '255','nullable' => True)
			),
			'pk' => array('account_id'),
			'fk' => array(),
			'ix' => array(),
			'uc' => array('account_lid')
		),
		's3db_access_log' => array(
			'fd' => array(
				'session_id' => array('type' => 'char','precision' => '32','nullable' => False),
				'login_timestamp' => array('type' => 'timestamp','nullable' => False),
				'login_id' => array('type' => 'varchar','precision' => '30','nullable' => False),
				'ip' => array('type' => 'varchar','precision' => '30','nullable' => False)
			),
			'pk' => array(),
			'fk' => array(),
			'ix' => array(),
			'uc' => array()
		),
		's3db_rule_change_log' => array(
			'fd' => array(
				'project_id' => array('type' => 'varchar','precision' => '255','nullable' => False),
				'rule_id' => array('type' => 'varchar','precision' => '255','nullable' => False),
				'action' => array('type' => 'char','precision' => '10','nullable' => False),
				'action_by' => array('type' => 'varchar','precision' => '30','nullable' => False),
				'action_timestamp' => array('type' => 'timestamp','nullable' => False),
				'new_subject' => array('type' => 'varchar','precision' => '50','nullable' => True),
				'new_verb' => array('type' => 'varchar','precision' => '50','nullable' => True),
				'new_object' => array('type' => 'varchar','precision' => '50','nullable' => True),
				'new_subject_id' => array('type' => 'varchar','precision' => '50','nullable' => True),
				'new_verb_id' => array('type' => 'varchar','precision' => '50','nullable' => True),
				'new_object_id' => array('type' => 'varchar','precision' => '50','nullable' => True),
				'new_notes' => array('type' => 'blob','nullable' => True),
				'old_subject' => array('type' => 'varchar','precision' => '50','nullable' => True),
				'old_verb' => array('type' => 'varchar','precision' => '50','nullable' => True),
				'old_object' => array('type' => 'varchar','precision' => '50','nullable' => True),
				'old_subject_id' => array('type' => 'varchar','precision' => '50','nullable' => True),
				'old_verb_id' => array('type' => 'varchar','precision' => '50','nullable' => True),
				'old_object_id' => array('type' => 'varchar','precision' => '50','nullable' => True),
				'old_notes' => array('type' => 'blob','nullable' => True),
			),
			'pk' => array(),
			'fk' => array(),
			'ix' => array(),
			'uc' => array()
		),
		's3db_statement_log' => array(
			'fd' => array(
				'statement_log_id' => array('type' => 'auto','precision' => '255', 'nullable' => False),
				'statement_id' => array('type' => 'varchar','precision' => '255'),
				'old_rule_id' => array('type' => 'varchar','precision' => '255'),
				'old_resource_id' => array('type' => 'varchar','precision' => '255'),
				'old_project_id' => array('type' => 'varchar','precision' => '255'),
				'old_value' => array('type' => 'text'),
				'old_notes' => array('type' => 'varchar','precision' => '255'),
				'action' => array('type' => 'varchar','precision' => '255'),
				'modified_by' => array('type' => 'varchar','precision' => '255'),
				'modified_on' => array('type' => 'timestamp','nullable' => False),
				'created_by' => array('type' => 'varchar','precision' => '255'),
				'created_on' => array('type' => 'timestamp','nullable' => False),
			),
			'pk' => array('statement_log_id'),
			'fk' => array(),
			'ix' => array(),
			'uc' => array()
		),
		's3db_addr' => array(
			'fd' => array(
				'addr_id' => array('type' => 'auto','nullable' => False),
				'addr1' => array('type' => 'varchar','precision' => '128','nullable' => True),
				'addr2' => array('type' => 'varchar','precision' => '128','nullable' => True),
				'city' => array('type' => 'varchar','precision' => '64','nullable' => True),
				'state' => array('type' => 'varchar','precision' => '64','nullable' => True),
				'postal_code' => array('type' => 'varchar','precision' => '64','nullable' => True),
				'country' => array('type' => 'varchar','precision' => '64','nullable' => True)
			),
			'pk' => array('addr_id'),
			'fk' => array(),
			'ix' => array(),
			'uc' => array()
		),
		's3db_resource' => array(
			'fd' => array(
				'resource_id' => array('type' => 'varchar','precision' => '255','nullable' => False),
				'project_id' => array('type' => 'varchar','precision' => '255','nullable' => False),
				'iid' => array('type' => 'varchar','precision' => '255','nullable' => False),
				'entity' => array('type' => 'varchar','precision' => '50','nullable' => True),
				'notes' => array('type' => 'blob','nullable' => True),
				'created_on' => array('type' => 'timestamp','nullable' => False),
				'created_by' => array('type' => 'varchar','precision' => '255','nullable' => False),
				'modified_on' => array('type' => 'timestamp','nullable' =>True),
				'modified_by' => array('type' => 'varchar','precision' => '255','nullable' => True),
				'permission' => array('type' => 'varchar','precision' => '50','nullable' => True),
				'status' => array('type' => 'varchar','precision' => '255','nullable' => True,'default' => 'A'),
				'resource_class_id' => array('type' => 'varchar','precision' => '50','nullable' => True)
			),
			'pk' => array('resource_id'),
			'ix' => array(),
			'fk' => array(),
			'uc' => array()
		),
		's3db_rule' => array(
			'fd' => array(
				'rule_id' => array('type' => 'varchar','precision' => '255','nullable' => False),
			
				'project_id' => array('type' => 'varchar','precision' => '255','nullable' => False),
				'subject' => array('type' => 'varchar','precision' => '255','nullable' => True),
				'verb' => array('type' => 'varchar','precision' => '255','nullable' => True),
				'object' => array('type' => 'varchar','precision' => '255','nullable' => True),
				'notes' => array('type' => 'blob','nullable' => True),
				'created_on' => array('type' => 'timestamp','nullable' => False),
				'created_by' => array('type' => 'varchar','precision' => '255','nullable' => False),
				'modified_on' => array('type' => 'timestamp','nullable' => True),
				'modified_by' => array('type' => 'varchar','precision' => '255','nullable' => True),
				'permission' => array('type' => 'varchar','precision' => '255','nullable' => True),
				'validation' => array('type' => 'text','nullable' => True),
				'status' => array('type' => 'varchar','precision' => '255','nullable' => True,'default' => 'A'),
				'subject_id' => array('type' => 'varchar','precision' => '255','nullable' => True),
				'verb_id' => array('type' => 'varchar','precision' => '255','nullable' => True),
				'object_id' => array('type' => 'varchar','precision' => '255','nullable' => True)
			),
			'pk' => array('rule_id'),
			'ix' => array(),
			'fk' => array(),
			'uc' => array()
		),
		's3db_statement' => array(
			'fd' => array(
				'statement_id' => array('type' => 'varchar','precision' => '255','nullable' => False),
				'project_id' => array('type' => 'varchar','precision' => '255','nullable' => False),
				'resource_id' => array('type' => 'varchar','precision' => '255','nullable' => False),
				'rule_id' => array('type' => 'varchar','precision' => '255','nullable' => False),
				'value' => array('type' => 'text','nullable' => True),
				'notes' => array('type' => 'text','nullable' => True),
				'status' => array('type' => 'varchar','precision' => '255','nullable' => True,'default' => 'A'),
				'created_on' => array('type' => 'timestamp','nullable' => False),
				'created_by' => array('type' => 'varchar','precision' => '255','nullable' => False),
				'modified_on' => array('type' => 'timestamp','nullable' => True),
				'modified_by' => array('type' => 'varchar','precision' => '255','nullable' => True),
				'mime_type' => array('type' => 'varchar','precision' => '255','nullable' => True),
				'file_name' => array('type' => 'varchar','precision' => '255','nullable' => True),
				'file_size' => array('type' => 'varchar','precision' => '255','nullable' => True),
				'permission' => array('type' => 'varchar','precision' => '255','nullable' => True)
			),
			'pk' => array('statement_id'),
			'ix' => array(),
			'fk' => array(),
			'uc' => array()
		),
		's3db_config' => array(
			'fd' => array(
				'config_id' => array('type' => 'auto','nullable' => False),
				'config_name' => array('type' => 'varchar','precision' => '255','nullable' => False),
				'config_value' => array('type' => 'varchar','precision' => '255','nullable' => False),
				'config_type' => array('type' => 'varchar','precision' => '3','default' => 'str','nullable' => False),		//[int, num, str]
				'config_note' => array('type' => 'varchar','precision' => '255','nullable' => True),
				'created_on' => array('type' => 'timestamp','nullable' => False),
				'modified_on' => array('type' => 'timestamp','nullable' => True),
				'modified_by' => array('type' => 'int','nullable' => True),
			),
			'pk' => array('config_id'),
			'fk' => array(),
			'ix' => array(),
			'uc' => array()
		)
	);
	#echo '<pre>';print_R($s3db_tables);
?>
