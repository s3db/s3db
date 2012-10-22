<?php
	 /**
        * Setup process
        * @author Miles Lott <milosch@phpgroupware.org>
        * @copyright Portions Copyright (C) 2004 Free Software Foundation, Inc. http://www.fsf.org/
        * @license http://www.fsf.org/licenses/gpl.html GNU General Public License
        * @package phpgwapi
        * @subpackage application
        * @version $Id: class.setup_process.inc.php,v 1.7.2.4 2004/02/10 13:51:18 ceb Exp $
        */

	/***************************************************************************\
        * S3DB                                                                     *
        * http://www.s3db.org                                                      *
        * Modified by Chuming Chen <chumingchen@gmail.com>                         *
        \**************************************************************************/

	/**
	* Setup
	* 
	* @package s3dbapi
	* @subpackage dbsetup 
	*/
	class dbsetup
	{
		var $db;
		var $oProc;
		var $translation;

		var $detection = '';
		var $process = '';
		var $lang = '';
		var $html = '';
		var $appreg = '';
		
		/* table name vars */
		var $tbl_apps;
		var $tbl_config;
		var $tbl_hooks;
		
		/* message */
		var $msg = '';

		function dbsetup()
		{
			$this->db	  = CreateObject('s3dbapi.db');
			$this->db->Halt_On_Error = 'no';
			$this->db->Host     = $GLOBALS['s3db_info']['server']['db']['db_host'];
			$this->db->Type     = $GLOBALS['s3db_info']['server']['db']['db_type'];
			$this->db->Database = $GLOBALS['s3db_info']['server']['db']['db_name'];
			$this->db->User     = $GLOBALS['s3db_info']['server']['db']['db_user'];
			$this->db->Password = $GLOBALS['s3db_info']['server']['db']['db_pass'];

			
			$this->translation = CreateObject('s3dbapi.setup_translation');
			$this->oProc	  = CreateObject('s3dbapi.schema_proc', $GLOBALS['s3db_info']['server']['db']['db_type']);
			$this->oProc->m_odb = $this->db;
		}
		
		/**
		 * function detet database setup detectdb
		 */	
		function detectdb() 
		{
			$names = array();
			$this->db->Halt_On_Error = 'no';
			$tables = $this->db->table_names();
			
			foreach ($tables as $tab_info) {
				$names[] = $tab_info['table_name'];
			}
					
			
			if(is_array($names) && in_array('s3db_account', $names))
				return True;
			else
				return False;

		}
			
		/*!
		@function loaddb
		@abstract include api db class for connect to the db
		*/
		function loaddb()
		{
			//$this->db =CreateObject('s3dbapi.db_'.$GLOBALS['s3db_info']['server']['db']['db_type']);	
			return $this->db->connect();
		}
		
		function init_schema_proc()
		{
			$this->translation = CreateObject('s3dbapi.setup_translation');
			$this->oProc	  = CreateObject('s3dbapi.schema_proc', $GLOBALS['s3db_info']['server']['db']['db_type']);
			$this->oProc->m_odb = $GLOBALS['dbsetup']->db;
			//echo $this->oProc->m_odb;
		/*	$this->oProc->m_odb->Host = $this->db->Host;
			$this->oProc->m_odb->Database = $this->db->Database;
			$this->oProc->m_odb->User = $this->db->User;
			$this->oProc->m_odb->Password = $this->db->Password;
			$this->oProc->m_odb->connect();
			*/
			$this->oProc->m_odb->Halt_On_Error = 'report'; 
		}	
		
		function create_tables($DEBUG=False)
		{
			if(!$this->oProc)
			{
				if($DEBUG)
				{
					echo 'init schema processing';
				}	
				$this->init_schema_proc();	
			}
			//echo "hey".$this->oProc->m_odb->User;
			$tables = S3DB_SERVER_ROOT.'/s3dbapi/setup/tables.inc.php';
			//echo $tables;
			if(file_exists($tables))
			{
				if($DEBUG)
				{
					echo 'Including '.$tables;
				}
				include ($tables);
				$ret = $this->create_table_process($s3db_tables, False);
				if($ret)
				{
					if($DEBUG)
					{
						echo 'S3DB database tables have been geneated';
					}
					return True;
				}
				else
				{
					if($DEBUG)
					{
						echo 'S3DB database tables failed to be generated';
					}
					return False;

				}	
			}	
		}
	
		function drop_tables($DEBUG=False)
		{
			if(!$this->oProc)
			{
				if($DEBUG)
				{
					echo 'init schema processing';
				}	
				$this->init_schema_proc();	
			}
			$tables = S3DB_SERVER_ROOT.'/s3dbapi/setup/tables.inc.php';
			//echo $tables;
			if(file_exists($tables))
			{
				include ($tables);
				$this->oProc->DropAllTables($s3db_tables, $DEBUG);			
			}
		}

		 function create_table_process($tables,$DEBUG=False)
                {
                        if(!$tables)
                        {
                                return False;
                        }

                        $ret = $this->oProc->GenerateScripts($tables,$DEBUG);
                        if($ret)
                        {
                                $sret = $this->oProc->ExecuteScripts($tables,$DEBUG);
                                if($sret)
                                {
                                        return True;
                                }
                                else
                                {
                                        return False;
                                }
                        }
		}
	}	
?>
