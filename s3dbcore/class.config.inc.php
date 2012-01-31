<?php
	/**
	* Application configuration in a centralized location
	* @author Joseph Engo <jengo@phpgroupware.org>
	* @copyright Copyright (C) 2000-2004 Free Software Foundation, Inc. http://www.fsf.org/
	* @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
	* @package phpgwapi
	* @subpackage application
	* @version $Id: class.config.inc.php,v 1.7.2.1.2.3 2004/02/10 13:51:17 ceb Exp $
	*/

	/**
	* Application configuration in a centralized location
	*
	* @package phpgwapi
	* @subpackage application
	*/
	class config
	{
		var $db;
		var $appname;
		var $config_data;

		function config($appname = '')
		{
			if (! $appname)
			{
				$appname = $GLOBALS['phpgw_info']['flags']['currentapp'];
			}

			$this->db      = $GLOBALS['phpgw']->db;
			$this->appname = $appname;
		}

		function read_repository()
		{
			$this->db->query("select * from phpgw_config where config_app='" . $this->appname . "'",__LINE__,__FILE__);
			while ($this->db->next_record())
			{
				$test = @unserialize($this->db->f('config_value'));
				if($test)
				{
					$this->config_data[$this->db->f('config_name')] = $test;
				}
				else
				{
					$this->config_data[$this->db->f('config_name')] = $this->db->f('config_value');
				}
			}
		}

		function save_repository()
		{
			$config_data = $this->config_data;

			if ($config_data)
			{
				$this->db->lock(array('phpgw_config','phpgw_app_sessions'));
				$this->db->query("delete from phpgw_config where config_app='" . $this->appname . "'",__LINE__,__FILE__);
				if($this->appname == 'phpgwapi')
				{
					$this->db->query("delete from phpgw_app_sessions where sessionid = '0' and loginid = '0' and app = '".$this->appname."' and location = 'config'",__LINE__,__FILE__);
				}
				while (list($name,$value) = each($config_data))
				{
					if(is_array($value))
					{
						$value = serialize($value);
					}
					$name  = addslashes($name);
					$value = addslashes($value);
					$this->db->query("delete from phpgw_config where config_name='" . $name . "'",__LINE__,__FILE__);
					$query = "insert into phpgw_config (config_app,config_name,config_value) "
						. "values ('" . $this->appname . "','" . $name . "','" . $value . "')";
					$this->db->query($query,__LINE__,__FILE__);
				}
				$this->db->unlock();
			}
		}

		function delete_repository()
		{
			$this->db->query("delete from phpgw_config where config_app='" . $this->appname . "'",__LINE__,__FILE__);
		}

		function value($variable_name,$variable_data)
		{
			$this->config_data[$variable_name] = $variable_data;
		}
	}
?>
