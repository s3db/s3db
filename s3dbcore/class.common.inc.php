<?php
	/**
	 * Commononly used functions
	 * @author Dan Kuykendall <seek3r@phpgroupware.org>
	 * @author Joseph Engo <jengo@phpgroupware.org>
	 * @author Mark Peters <skeeter@phpgroupware.org>
	 * @copyright Copyright (C) 2000-2004 Free Software Foundation, Inc http://www.fsf.org/
	 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
	 * @package phpgwapi
	 * @subpackage utilities
	 * @version $Id: class.common.inc.php,v 1.123.2.9.2.28 2004/03/02 22:25:04 ceb Exp $
	 */

	$d1 = strtolower(@substr(PHPGW_API_INC,0,3));
	$d2 = strtolower(@substr(PHPGW_SERVER_ROOT,0,3));
	$d3 = strtolower(@substr(PHPGW_APP_INC,0,3));
	if($d1 == 'htt' || $d1 == 'ftp' || $d2 == 'htt' || $d2 == 'ftp' || $d3 == 'htt' || $d3 == 'ftp') {
		echo 'Failed attempt to break in via an old Security Hole!<br>'."\n";
		exit;
	}
	unset($d1);unset($d2);unset($d3);

	/**
	 * Commononly used functions
	 *
	 * @package phpgwapi
	 * @subpackage utilities
	 */
	class common
	{
		/**
		* An array with debugging info from the API
		* @var array Debugging info from the API
		*/
		var $debug_info;
		var $found_files;

		/**
		* This function compares for major versions only
		*
		* @param string $str1 Version string 1
		* @param string $str2 Version string 2
		* @param boolean $debug Debug flag
		* @return integer 1 when str2 is newest (bigger version number) than str1
		*/
		function cmp_version($str1,$str2,$debug=False) {
			ereg("([0-9]+)\.([0-9]+)\.([0-9]+)[a-zA-Z]*([0-9]*)",$str1,$regs);
			ereg("([0-9]+)\.([0-9]+)\.([0-9]+)[a-zA-Z]*([0-9]*)",$str2,$regs2);
			if($debug) { echo "<br />$regs[0] - $regs2[0]"; }

			for($i=1;$i<5;++$i) {
				if($debug) { echo "<br />$i: $regs[$i] - $regs2[$i]"; }
				if($regs2[$i] == $regs[$i]) {
					continue;
				}
				if($regs2[$i] > $regs[$i]) {
					return 1;
				} elseif($regs2[$i] < $regs[$i]) {
					return 0;
				}
			}
		}

		/**
		* This function compares for major and minor versions
		*
		* @param string $str1 Version string 1
		* @param string $str2 Version string 2
		* @param boolean $debug Debug flag
		* @return integer 1 when str2 is newest (bigger version number) than str1
		*/
		function cmp_version_long($str1,$str2,$debug=False) {
			ereg("([0-9]+)\.([0-9]+)\.([0-9]+)[a-zA-Z]*([0-9]*)\.([0-9]*)",$str1,$regs);
			ereg("([0-9]+)\.([0-9]+)\.([0-9]+)[a-zA-Z]*([0-9]*)\.([0-9]*)",$str2,$regs2);
			if($debug) { echo "<br />$regs[0] - $regs2[0]"; }

			for($i=1;$i<6;++$i) {
				if($debug) { echo "<br />$i: $regs[$i] - $regs2[$i]"; }

				if($regs2[$i] == $regs[$i]) {
					if($debug) { echo ' are equal...'; }
					continue;
				}
				if($regs2[$i] > $regs[$i]) {
					if($debug) { echo ', and a > b'; }
					return 1;
				} elseif($regs2[$i] < $regs[$i]) {
					if($debug) { echo ', and a < b'; }
					return 0;
				}
			}
			if($debug) { echo ' - all equal.'; }
		}

		/**
		* Convert an array into the format needed for the access column
		*
		* @param string $access Could be 'group', 'public', 'none'
		* @param array $array Access information
		* @return Comma separated list
		* @deprecated Use ACL instead
		*/
		function array_to_string($access,$array) {
			$this->debug_info[] = 'array_to_string() is a depreciated function - use ACL instead';
			$s = '';
			if ($access == 'group' || $access == 'public' || $access == 'none') {
				if (count($array)) {
					while ($t = each($array)) {
						$s .= ',' . $t[1];
					}
					$s .= ',';
				}
				if (! count($array) && $access == 'none') {
					$s = '';
				}
			}
			return $s;
		}

		/**
		* This function is used for searching the access fields
		*
		* @param string $table Table name
		* @param integer $owner User ID
		* @return string SQL where clause
		* @deprecated Use ACL instead
		*/
		function sql_search($table,$owner=0) {
			$this->debug_info[] = 'sql_search() is a deprecated function - use ACL instead';
			$s = '';
			if (!$owner) {
				$owner = $GLOBALS['phpgw_info']['user']['account_id'];
			}
			$groups = $GLOBALS['phpgw']->accounts->membership(intval($owner));
			if (gettype($groups) == 'array') {
				while ($group = each($groups)) {
					$s .= " or $table like '%," . $group[2] . ",%'";
				}
			}
			return $s;
		}

		/**
		* Get list of installed languages
		*
		* @return array List of installed languages
		*/
		function getInstalledLanguages() {
			$GLOBALS['phpgw']->db->query('select distinct lang from phpgw_lang');
			while (@$GLOBALS['phpgw']->db->next_record()) {
				$installedLanguages[$GLOBALS['phpgw']->db->f('lang')] = $GLOBALS['phpgw']->db->f('lang');
			}
			return $installedLanguages;
		}

		/**
		* Get preferred language of the users
		*
		* Uses HTTP_ACCEPT_LANGUAGE (from the users browser) to find out which languages are installed
		* @return string Users preferred language (two character ISO code)
		*/
		function getPreferredLanguage() {
			// create a array of languages the user is accepting
			$userLanguages = explode(',',$_SERVER['HTTP_ACCEPT_LANGUAGE']);
			$supportedLanguages = $this->getInstalledLanguages();

			// find usersupported language
			while (list($key,$value) = each($userLanguages)) {
				// remove everything behind '-' example: de-de
				$value = trim($value);
				$pieces = explode('-', $value);
				$value = $pieces[0];
				# print 'current lang $value<br>';
				if ($supportedLanguages[$value]) {
					$retValue=$value;
					break;
				}
			}

			// no usersupported language found -> return english
			if (empty($retValue)) {
				$retValue='en';
			}

			return $retValue;
		}

		/**
		* Connect to the ldap server and return a handle
		*
		* @param string $host LDAP host name
		* @param string $dn LDAP distinguised name
		* @param string $passwd LDAP password
		* @return resource LDAP link identifier
		*/
		function ldapConnect($host = '', $dn = '', $passwd = '') {
			if (! $host) {
				$host = $GLOBALS['phpgw_info']['server']['ldap_host'];
			}

			if (! $dn) {
				$dn = $GLOBALS['phpgw_info']['server']['ldap_root_dn'];
			}

			if (! $passwd) {
				$passwd = $GLOBALS['phpgw_info']['server']['ldap_root_pw'];
			}

			// connect to ldap server
			if (! $ds = ldap_connect($host)) {
				/* log does not exist in setup(, yet) */
				if(is_object($GLOBALS['phpgw']->log)) {
					$GLOBALS['phpgw']->log->message('F-Abort, Failed connecting to LDAP server');
					$GLOBALS['phpgw']->log->commit();
				}

				printf("<b>Error: Can't connect to LDAP server %s!</b><br>",$host);
				return False;
			}
			if(! @ldap_set_option($ds, LDAP_OPT_PROTOCOL_VERSION, 3)) {		//LDAP protocol v3 support
				if(is_object($GLOBALS['phpgw']->log)) {
					//$GLOBALS['phpgw']->log->message('set_option(protocol v3) failed using v2');
					//$GLOBALS['phpgw']->log->commit();
				}
			} else {
				if(is_object($GLOBALS['phpgw']->log)) {
					//$GLOBALS['phpgw']->log->message('set_option(protocol v3) succeded using v3');
					//$GLOBALS['phpgw']->log->commit();
				}
			}
			// bind as admin, we not to able to do everything
			if (! ldap_bind($ds,$dn,$passwd)) {
				if(is_object($GLOBALS['phpgw']->log)) {
					$GLOBALS['phpgw']->log->message('F-Abort, Failed binding to LDAP server');
					$GLOBALS['phpgw']->log->commit();
				}
				printf("<b>Error: Can't bind to LDAP server: %s!</b><br>",$dn);
				return False;
			}
			return $ds;
		}

		/**
		* Function to stop running an application
		*
		* Used to stop running an application in the middle of execution
		* @internal There may need to be some cleanup before hand
		* @param boolean $call_footer When true then call footer else exit
		*/
		function phpgw_exit($call_footer = False) {
			if (!defined('PHPGW_EXIT')) {
				define('PHPGW_EXIT',True);

				if ($call_footer) {
					$this->phpgw_footer();
				}
			}
			exit;
		}

		function phpgw_final() {
			if (!defined('PHPGW_FINAL')) {
				define('PHPGW_FINAL',True);

				// call the asyncservice check_run function if it is not explicitly set to cron-only
				//
				if (!$GLOBALS['phpgw_info']['server']['asyncservice']) {		// is default
					ExecMethod('phpgwapi.asyncservice.check_run','fallback');
				}
				/* Clean up mcrypt */
				if (@is_object($GLOBALS['phpgw']->crypto)) {
					$GLOBALS['phpgw']->crypto->cleanup();
					unset($GLOBALS['phpgw']->crypto);
				}
				$GLOBALS['phpgw']->db->disconnect();
			}
		}

		/**
		* Get random string of size $size
		*
		* @param integer $size Size of random string to return
		* @return string STring with random generated characters and numbers
		*/
		function randomstring($size) {
			$s = '';
			srand((double)microtime()*1000000);
			$random_char = array('0','1','2','3','4','5','6','7','8','9','a','b','c','d','e','f',
				'g','h','i','j','k','l','m','n','o','p','q','r','s','t','u','v',
				'w','x','y','z','A','B','C','D','E','F','G','H','I','J','K','L',
				'M','N','O','P','Q','R','S','T','U','V','W','X','Y','Z');

			for ($i=0; $i<$size; ++$i) {
				$s .= $random_char[rand(1,61)];
			}
			return $s;
		}

		function filesystem_separator() {
			return filesystem_separator();
		}

		/**
		* This is used for reporting errors in a nice format
		*
		* @param array $error List of errors
		* @param string $text Heading error text
		* @return boolean|string HTML table with error messages or false 
		*/
		function error_list($errors,$text='Error') {
			if (! is_array($errors)) {
				return False;
			}

			$html_error = '<table border="0" width="100%"><tr><td align="right"><strong>' . lang($text)
				. '</strong>: </td><td align="left">' . $errors[0] . '</td></tr>';
			for ($i=1; $i<count($errors); ++$i) {
				$html_error .= '<tr><td>&nbsp;</td><td align="left">' . $errors[$i] . '</td></tr>';
			}
			return $html_error . '</table>';
		}

		/**
		* Create a link when user is the owner otherwise &nbsp;
		*
		* @param integer $record User or account id
		* @param string $link URL
		* @param string $label Link name
		* @param array $extravars URL parameter
		* @deprecated use ACL instead
		*/
		function check_owner($record,$link,$label,$extravars = '') {
			$this->debug_info[] = 'check_owner() is a depreciated function - use ACL instead';
			/*
			$s = '<a href="' . $GLOBALS['phpgw']->link($link,$extravars) . '"> ' . lang($label) . ' </a>';
			if (ereg('^[0-9]+$',$record))
			{
				if ($record != $GLOBALS['phpgw_info']['user']['account_id'])
				{
					$s = '&nbsp;';
				}
			}
			else
			{
				if ($record != $GLOBALS['phpgw_info']['user']['userid'])
				{
					$s = '&nbsp;';
				}
			}

			return $s;
			*/
		}

		/**
		* Get fullname of a user
		*
		* @param string $lid Account login id
		* @param string $firstname Firstname
		* @param string $lastname Lastname
		* @return Fullname
		*/
		function display_fullname($lid = '', $firstname = '', $lastname = '') {
			if (! $lid && ! $firstname && ! $lastname) {
				$lid       = $GLOBALS['phpgw_info']['user']['account_lid'];
				$firstname = $GLOBALS['phpgw_info']['user']['firstname'];
				$lastname  = $GLOBALS['phpgw_info']['user']['lastname'];
			}

			$display = $GLOBALS['phpgw_info']['user']['preferences']['common']['account_display'];

			if (!$firstname && !$lastname || $display == 'username') {
				return $lid;
			}
			if ($lastname) {
				$a[] = $lastname;
			}
			if ($firstname) {
				$a[] = $firstname;
			}
			$name = '';
			switch($display) {
				case 'all':
					if ($lid) {
						$name = '['.$lid.'] ';
					}
					// fall-through
				case 'lastname':
					$name .= implode(', ',$a);
					break;
				case 'firstall':
					if ($lid) {
						$name = ' ['.$lid.']';
					}
					// fall-through
				case 'firstname':
				default:
					$name = $firstname . ' ' . $lastname . $name;
			}
			return $name;
		}

		/**
		* Grab the owner name
		*
		* @param integer $accountid Account id
		* @return string Users fullname
		*/
		function grab_owner_name($accountid = '') {
			$GLOBALS['phpgw']->accounts->get_account_name($accountid,$lid,$fname,$lname);
			return $this->display_fullname($lid,$fname,$lname);
		}

		/**
		* Create tabs
		*
		* @param array $tabs With ($id,$tab) pairs
		* @param integer $selected Id of selected tab
		* @param string $fontsize Optional font size
		* @param boolean $lang When true use translation otherwise use given label
		* @param boolean $no_image Do not use an image for the tabs
		* @return string HTML output string
		*/
		function create_tabs($tabs, $selected, $fontsize = '', $lang = False, $no_image = True) {
			if($no_image) {
				$output_text = "<table style=\"{padding: 0px; border-collapse: collapse; width: 100%;}\">\n\t<tr>\n";
//				$output_text = "<table style=\"{padding: 0px; width: 100%;}\">\n\t<tr>\n";
				foreach($tabs as $id => $tab) {
					$output_text .= "\t\t" . '<th class="';
					$output_text .= ($id != $selected ? 'in' : '');
					$output_text .= 'activetab">';
					$output_text .= '<a href="' . $tab['link'] . '">';
					$output_text .= ($lang ? lang($tab['label']) : $tab['label']);
					$output_text .= "</a></th>\n";
//					$output_text .= "<th style=\"border-bottom: 1px solid #000000; \">&nbsp;</th>\n";
				}
				$output_text .= "\t\t" . '<th class="tablast">&nbsp;</th>' . "\n"; 
				$output_text .= "\t</tr>\n</table>\n";
				return $output_text;
			}
			$output_text = '<table border="0" cellspacing="0" cellpadding="0"><tr>';

			/* This is a php3 workaround */
			if(PHPGW_IMAGES_DIR == 'PHPGW_IMAGES_DIR') {
				$ir = ExecMethod('phpgwapi.phpgw.common.get_image_path', 'phpgwapi');
			} else {
				$ir = PHPGW_IMAGES_DIR;
			}

			if($fontsize) {
				$fs  = '<font size="' . $fontsize . '">';
				$fse = '</font>';
			}

			$i = 1;
			while ($tab = each($tabs)) {
				if ($tab[0] == $selected) {
					if ($i == 1) {
						$output_text .= '<td align="right"><img src="' . $ir . '/tabs-start1.gif" /></td>';
					}
					$output_text .= '<td nowrap="nowrap" align="left" background="' . $ir . '/tabs-bg1.gif">&nbsp;<strong><a href="'
						. $tab[1]['link'] . '" class="tablink">' . $fs . $tab[1]['label']
						. $fse . '</a></strong>&nbsp;</td>';
					if ($i == count($tabs)) {
						$output_text .= '<td align="left"><img src="' . $ir . '/tabs-end1.gif" /></td>';
					} else {
						$output_text .= '<td align="left"><img src="' . $ir . '/tabs-sepr.gif" /></td>';
					}
				} else {
					if ($i == 1) {
						$output_text .= '<td align="right"><img src="' . $ir . '/tabs-start0.gif" /></td>';
					}
					$output_text .= '<td nowrap="nowrap" align="left" background="' . $ir . '/tabs-bg0.gif">&nbsp;<strong><a href="'
						. $tab[1]['link'] . '" class="tablink">' . $fs . $tab[1]['label'] . $fse
						. '</a></strong>&nbsp;</td>';
					if (($i + 1) == $selected) {
						$output_text .= '<td align="left"><img src="' . $ir . '/tabs-sepl.gif" /></td>';
					} elseif ($i == $selected || $i != count($tabs)) {
						$output_text .= '<td align="left"><img src="' . $ir . '/tabs-sepm.gif" /></td>';
					} elseif ($i == count($tabs)) {
						if ($i == $selected) {
							$output_text .= '<td align="left"><img src="' . $ir . '/tabs-end1.gif" /></td>';
						} else {
							$output_text .= '<td align="left"><img src="' . $ir . '/tabs-end0.gif" /></td>';
						}
					} else {
						if ($i != count($tabs)) {
							$output_text .= '<td align="left"><img src="' . $ir . '/tabs-sepr.gif" /></td>';
						}
					}
				}
				++$i;
				$output_text .= "\n";
			}
			$output_text .= "</table>\n";
			return $output_text;
		}

		/**
		* Get directory of application
		*
		* @param string $appname Name of application defaults to $phpgw_info['flags']['currentapp']
		* @return string|boolean Application directory or false
		*/
		function get_app_dir($appname = '') {
			if ($appname == '') {
				$appname = $GLOBALS['phpgw_info']['flags']['currentapp'];
			}
			if ($appname == 'home' || $appname == 'logout' || $appname == 'login') {
				$appname = 'phpgwapi';
			}

			$appdir         = PHPGW_INCLUDE_ROOT . '/'.$appname;
			$appdir_default = PHPGW_SERVER_ROOT . '/'.$appname;

			if (@is_dir ($appdir)) {
				return $appdir;
			} elseif (@is_dir ($appdir_default)) {
				return $appdir_default;
			} else {
				return False;
			}
		}

		/**
		* Get include directory of application
		*
		* @param string $appname Name of application, defaults to $phpgw_info['flags']['currentapp']
		* @return string|boolean Include directory or false
		*/
		function get_inc_dir($appname = '') {
			if (! $appname) {
				$appname = $GLOBALS['phpgw_info']['flags']['currentapp'];
			}
			if ($appname == 'home' || $appname == 'logout' || $appname == 'login') {
				$appname = 'phpgwapi';
			}
 
			$incdir         = PHPGW_INCLUDE_ROOT . '/' . $appname . '/inc';
			$incdir_default = PHPGW_SERVER_ROOT . '/' . $appname . '/inc';
 
			if (@is_dir ($incdir)) {
				return $incdir;
			} elseif (@is_dir ($incdir_default)) {
				return $incdir_default; 
			} else {
				return False;
			}
		}

		/**
		* List themes available
		*
		* Themes can either be css files like in HEAD (if the template has a css-directory) or ordinary .14 themes-files
		* @return array List with available themes
		*/
		function list_themes() {
			$tpl_dir = $this->get_tpl_dir('phpgwapi');

			if ($dh = @opendir($tpl_dir . SEP . 'css')) {
				while ($file = readdir($dh)) {
					if (eregi("\.css$", $file) && $file != 'phpgw.css') {
						$list[] = substr($file,0,strpos($file,'.'));
					}
				}
			} else {
				$dh = opendir(PHPGW_SERVER_ROOT . '/phpgwapi/themes');
				while ($file = readdir($dh)) {
					if (eregi("\.theme$", $file)) {
						$list[] = substr($file,0,strpos($file,'.'));
					}
				}
			}
			closedir($dh);
			reset ($list);
			return $list;
		}

		/**
		* List available templates
		*
		* @return array Alphabetically sorted list of available templates
		*/
		function list_templates() {
			$d = dir(PHPGW_SERVER_ROOT . '/phpgwapi/templates');
			while ($entry=$d->read()) {
				if ($entry != 'CVS' && $entry != '.' && $entry != '..' 
					&& $entry != 'phpgw_website' 
					&& is_dir(PHPGW_SERVER_ROOT . '/phpgwapi/templates/' . $entry))
				{
					$list[$entry]['name'] = $entry;
					$f = PHPGW_SERVER_ROOT . '/phpgwapi/templates/' . $entry . '/details.inc.php';
					if (file_exists ($f)) {
						include($f);
						$list[$entry]['title'] = 'Use '.$GLOBALS['phpgw_info']['template'][$entry]['title'].'interface';
					} else {
						$list[$entry]['title'] = $entry;
					}
				}
			}
			$d->close();
			ksort($list);
			return $list;
		}

		/**
		* Get template directory of an application
		*
		* @param string $appname Application name, defaults to $phpgw_info['flags']['currentapp']
		* @return string Template directory of given application
		*/
		function get_tpl_dir($appname = '') {
			if (! $appname) {
				$appname = $GLOBALS['phpgw_info']['flags']['currentapp'];
			}
			if ($appname == 'home' || $appname == 'logout' || $appname == 'login') {
				$appname = 'phpgwapi';
			}

			if (!isset($GLOBALS['phpgw_info']['server']['template_set']) && isset($GLOBALS['phpgw_info']['user']['preferences']['common']['template_set'])) {
				$GLOBALS['phpgw_info']['server']['template_set'] = $GLOBALS['phpgw_info']['user']['preferences']['common']['template_set'];
			}

			// Setting this for display of template choices in user preferences
			if ($GLOBALS['phpgw_info']['server']['template_set'] == 'user_choice') {
				$GLOBALS['phpgw_info']['server']['usrtplchoice'] = 'user_choice';
			}

			if (($GLOBALS['phpgw_info']['server']['template_set'] == 'user_choice' || !isset($GLOBALS['phpgw_info']['server']['template_set'])) && isset($GLOBALS['phpgw_info']['user']['preferences']['common']['template_set'])) {
				$GLOBALS['phpgw_info']['server']['template_set'] = $GLOBALS['phpgw_info']['user']['preferences']['common']['template_set'];
			} elseif ($GLOBALS['phpgw_info']['server']['template_set'] == 'user_choice' || !isset($GLOBALS['phpgw_info']['server']['template_set'])) {
				$GLOBALS['phpgw_info']['server']['template_set'] = 'default';
			}

			$tpldir         = PHPGW_SERVER_ROOT . '/' . $appname . '/templates/' . $GLOBALS['phpgw_info']['server']['template_set'];
			$tpldir_default = PHPGW_SERVER_ROOT . '/' . $appname . '/templates/default';

			if (@is_dir($tpldir)) {
				return $tpldir;
			} elseif (@is_dir($tpldir_default)) {
				return $tpldir_default;
			} else {
				return False;
			}
		}

		/**
		* Test if image directory exists and has more than just a navbar-icon
		*
		* @param string $dir Image directory
		* @return boolean True when it is an image directory, otherwise false.
		* @internal This is just a workaround for idots, better to use find_image, which has a fallback on a per image basis to the default dir
		*/
		function is_image_dir($dir) {
			if (!@is_dir($dir)) {
				return False;
			}
			if ($d = opendir($dir)) {
				while ($f = readdir($d)) {
					$ext = strtolower(strrchr($f,'.'));
					if (($ext == '.gif' || $ext == '.png') && strstr($f,'navbar') === False) {
						return True;
					}
				}
			}
			return False;
		}

		/**
		* Get image directory of an application
		*
		* @param string $appname Application name, defaults to $phpgw_info['flags']['currentapp']
		* @return string|boolean Image directory of given application or false
		*/
		function get_image_dir($appname = '') {
			if ($appname == '') {
				$appname = $GLOBALS['phpgw_info']['flags']['currentapp'];
			}
			if (empty($GLOBALS['phpgw_info']['server']['template_set'])) {
				$GLOBALS['phpgw_info']['server']['template_set'] = 'default';
			}

			$imagedir            = PHPGW_SERVER_ROOT . '/' . $appname . '/templates/' . $GLOBALS['phpgw_info']['server']['template_set'] . '/images';
			$imagedir_default    = PHPGW_SERVER_ROOT . '/' . $appname . '/templates/default/images';
			$imagedir_olddefault = PHPGW_SERVER_ROOT . '/' . $appname . '/images';

			if ($this->is_image_dir ($imagedir)) {
				return $imagedir;
			} elseif ($this->is_image_dir ($imagedir_default)) {
				return $imagedir_default;
			} elseif ($this->is_image_dir ($imagedir_olddefault)) {
				return $imagedir_olddefault;
			} else {
				return False;
			}
		}

		/**
		* Get image path of an application
		*
		* @param string $appname Appication name, defaults to $phpgw_info['flags']['currentapp']
		* @return string|boolean Image directory path of given application or false
		*/
		function get_image_path($appname = '') {
			if ($appname == '') {
				$appname = $GLOBALS['phpgw_info']['flags']['currentapp'];
			}

			if (empty($GLOBALS['phpgw_info']['server']['template_set'])) {
				$GLOBALS['phpgw_info']['server']['template_set'] = 'default';
			}

			$imagedir            = PHPGW_SERVER_ROOT . '/'.$appname.'/templates/'.$GLOBALS['phpgw_info']['server']['template_set'].'/images';
			$imagedir_default    = PHPGW_SERVER_ROOT . '/'.$appname.'/templates/default/images';
			$imagedir_olddefault = PHPGW_SERVER_ROOT . '/'.$appname.'/images';

			if ($this->is_image_dir ($imagedir)) {
				return $GLOBALS['phpgw_info']['server']['webserver_url'].'/'.$appname.'/templates/'.$GLOBALS['phpgw_info']['server']['template_set'].'/images';
			} elseif ($this->is_image_dir ($imagedir_default)) {
				return $GLOBALS['phpgw_info']['server']['webserver_url'].'/'.$appname.'/templates/default/images';
			} elseif ($this->is_image_dir ($imagedir_olddefault)) {
				return $GLOBALS['phpgw_info']['server']['webserver_url'].'/'.$appname.'/images';
			} else {
				return False;
			}
		}

		function find_image($appname,$image) {
			if (!@is_array($this->found_files[$appname])) {
				$imagedir_olddefault = '/'.$appname.'/images';
				$imagedir_default    = '/'.$appname.'/templates/default/images';
				$imagedir = '/'.$appname.'/templates/'.$GLOBALS['phpgw_info']['server']['template_set'].'/images';

				if (@is_dir(PHPGW_INCLUDE_ROOT.$imagedir_olddefault)) {
					$d = dir(PHPGW_INCLUDE_ROOT.$imagedir_olddefault);
					while (false != ($entry = $d->read())) {
						if ($entry != '.' && $entry != '..') {
							$this->found_files[$appname][$entry] = $imagedir_olddefault;
						}
					}
					$d->close();
				}

				if (@is_dir(PHPGW_INCLUDE_ROOT.$imagedir_default)) {
					$d = dir(PHPGW_INCLUDE_ROOT.$imagedir_default);
					while (false != ($entry = $d->read())) {
						if ($entry != '.' && $entry != '..') {
							$this->found_files[$appname][$entry] = $imagedir_default;
						}
					}
					$d->close();
				}

				if (@is_dir(PHPGW_INCLUDE_ROOT.$imagedir)) {
					$d = dir(PHPGW_INCLUDE_ROOT.$imagedir);
					while (false != ($entry = $d->read())) {
						if ($entry != '.' && $entry != '..') {
							$this->found_files[$appname][$entry] = $imagedir;
						}
					}
					$d->close();
				}
			}

			if(isset($this->found_files[$appname][$image.'.png'])) {
				$imgfile = $GLOBALS['phpgw_info']['server']['webserver_url'].$this->found_files[$appname][$image.'.png'].'/'.$image.'.png';
			} elseif(isset($this->found_files[$appname][$image.'.jpg'])) {
				$imgfile = $GLOBALS['phpgw_info']['server']['webserver_url'].$this->found_files[$appname][$image.'.jpg'].'/'.$image.'.jpg';
			} elseif(isset($this->found_files[$appname][$image.'.gif'])) {
				$imgfile = $GLOBALS['phpgw_info']['server']['webserver_url'].$this->found_files[$appname][$image.'.gif'].'/'.$image.'.gif';
			} elseif(isset($this->found_files[$appname][$image])) {
				$imgfile = $GLOBALS['phpgw_info']['server']['webserver_url'].$this->found_files[$appname][$image].'/'.$image;
			} elseif(isset($this->found_files['phpgwapi'][$image.'.png'])) {
				$imgfile = $GLOBALS['phpgw_info']['server']['webserver_url'].$this->found_files['phpgwapi'][$image.'.png'].'/'.$image.'.png';
			} elseif(isset($this->found_files['phpgwapi'][$image.'.jpg'])) {
				$imgfile = $GLOBALS['phpgw_info']['server']['webserver_url'].$this->found_files['phpgwapi'][$image.'.jpg'].'/'.$image.'.jpg';
			} elseif(isset($this->found_files['phpgwapi'][$image.'.gif'])) {
				$imgfile = $GLOBALS['phpgw_info']['server']['webserver_url'].$this->found_files['phpgwapi'][$image.'.gif'].'/'.$image.'.gif';
			} elseif(isset($this->found_files['phpgwapi'][$image])) {
				$imgfile = $GLOBALS['phpgw_info']['server']['webserver_url'].$this->found_files['phpgwapi'][$image].'/'.$image;
			} else {
				$imgfile = '';
			}
			return $imgfile;
		}

		function image($appname,$image='',$ext='',$use_lang=True) {
			if (!is_array($image)) {
				if (empty($image)) {
					return '';
				}
				$image = array($image);
			}
			if ($use_lang) {
				while (list(,$img) = each($image)) {
					$lang_images[] = $img . '_' . $GLOBALS['phpgw_info']['user']['preferences']['common']['lang'];
					$lang_images[] = $img;
				}
				$image = $lang_images;
			}
			while (!$image_found && (list(,$img) = each($image))) {
				if(isset($this->found_files[$appname][$img.$ext])) {
					$image_found = $GLOBALS['phpgw_info']['server']['webserver_url'].$this->found_files[$appname][$img.$ext].'/'.$img.$ext;
				} else {
					$image_found = $this->find_image($appname,$img.$ext);
				}
			}
			return $image_found;
		}

		function image_on($appname,$image,$extension='_on') {
			$with_extension = $this->image($appname,$image,$extension);
			$without_extension = $this->image($appname,$image);
			if($with_extension != '') {
				return $with_extension;
			} elseif($without_extension != '') {
				return $without_extension;
			} else {
				return '';
			}
		}

		function navbar() {
			$GLOBALS['phpgw_info']['navbar']['home']['title'] = 'Home';
			$GLOBALS['phpgw_info']['navbar']['home']['url']   = $GLOBALS['phpgw']->link('/home.php');
			$GLOBALS['phpgw_info']['navbar']['home']['icon']  = $this->image('phpgwapi',Array('home','nonav'));
			$GLOBALS['phpgw_info']['navbar']['home']['icon_hover']  = $this->image_on('phpgwapi',Array('home','nonav'),'-over');

			list($first) = each($GLOBALS['phpgw_info']['user']['apps']);
			if(is_array($GLOBALS['phpgw_info']['user']['apps']['admin']) && $first != 'admin') {
				$newarray['admin'] = $GLOBALS['phpgw_info']['user']['apps']['admin'];
				foreach($GLOBALS['phpgw_info']['user']['apps'] as $index => $value) {
					if($index != 'admin') {
						$newarray[$index] = $value;
					}
				}
				$GLOBALS['phpgw_info']['user']['apps'] = $newarray;
				reset($GLOBALS['phpgw_info']['user']['apps']);
			}
			unset($index);
			unset($value);
			unset($newarray);
			
			foreach($GLOBALS['phpgw_info']['user']['apps'] as $app => $data) {
				if (is_long($app)) {
					continue;
				}

				if ($app == 'preferences' || $GLOBALS['phpgw_info']['apps'][$app]['status'] != 2 && $GLOBALS['phpgw_info']['apps'][$app]['status'] != 3) {
					$GLOBALS['phpgw_info']['navbar'][$app]['title'] = $GLOBALS['phpgw_info']['apps'][$app]['title'];
					$GLOBALS['phpgw_info']['navbar'][$app]['url']   = $GLOBALS['phpgw']->link('/' . $app . '/index.php');
					$GLOBALS['phpgw_info']['navbar'][$app]['name']  = $app;

					if ($app != $GLOBALS['phpgw_info']['flags']['currentapp']) {
						$GLOBALS['phpgw_info']['navbar'][$app]['icon']  = $this->image($app,Array('navbar','nonav'));
						$GLOBALS['phpgw_info']['navbar'][$app]['icon_hover']  = $this->image_on($app,Array('navbar','nonav'),'-over');
					} else {
						$GLOBALS['phpgw_info']['navbar'][$app]['icon']  = $this->image_on($app,Array('navbar','nonav'),'-over');
						$GLOBALS['phpgw_info']['navbar'][$app]['icon_hover']  = $this->image($app,Array('navbar','nonav'));
					}

//					if($GLOBALS['phpgw_info']['navbar'][$app]['icon'] == '')
//					{
//						$GLOBALS['phpgw_info']['navbar'][$app]['icon']  = $this->image('phpgwapi','nonav');
//					}
				}
			}
			if ($GLOBALS['phpgw_info']['flags']['currentapp'] == 'home' || $GLOBALS['phpgw_info']['flags']['currentapp'] == 'preferences' || $GLOBALS['phpgw_info']['flags']['currentapp'] == 'about') {
				$app = $app_title = 'phpGroupWare';
			} else {
				$app = $GLOBALS['phpgw_info']['flags']['currentapp'];
				$app_title = $GLOBALS['phpgw_info']['apps'][$app]['title'];
			}

			if ($GLOBALS['phpgw_info']['user']['apps']['preferences']) {			// preferences last 
				$prefs = $GLOBALS['phpgw_info']['navbar']['preferences'];
				unset($GLOBALS['phpgw_info']['navbar']['preferences']);
				$GLOBALS['phpgw_info']['navbar']['preferences'] = $prefs;
			}

			// We handle this here becuase its special
			$GLOBALS['phpgw_info']['navbar']['about']['title'] = lang('About %1',$app_title);

			$GLOBALS['phpgw_info']['navbar']['about']['url']   = $GLOBALS['phpgw']->link('/about.php','app='.$app);
			$GLOBALS['phpgw_info']['navbar']['about']['icon']  = $this->image('phpgwapi',Array('about','nonav'));
			$GLOBALS['phpgw_info']['navbar']['about']['icon_hover']  = $this->image_on('phpgwapi',Array('about','nonav'),'-over');

			$GLOBALS['phpgw_info']['navbar']['logout']['title'] = 'Logout';
			$GLOBALS['phpgw_info']['navbar']['logout']['url']   = $GLOBALS['phpgw']->link('/logout.php');
			$GLOBALS['phpgw_info']['navbar']['logout']['icon']  = $this->image('phpgwapi',Array('logout','nonav'));
			$GLOBALS['phpgw_info']['navbar']['logout']['icon_hover']  = $this->image_on('phpgwapi',Array('logout','nonav'),'-over');
		}

		/**
		* Load header.inc.php for an application
		*/
		function app_header() {
			if (file_exists(PHPGW_APP_INC . '/header.inc.php')) {
				include(PHPGW_APP_INC . '/header.inc.php');
			}
		}

		/**
		* Load the phpgw header
		*/
		function phpgw_header() {
			include(PHPGW_INCLUDE_ROOT . '/phpgwapi/templates/' . $GLOBALS['phpgw_info']['server']['template_set']
				. '/head.inc.php');
			$this->navbar(False);
			include(PHPGW_INCLUDE_ROOT . '/phpgwapi/templates/' . $GLOBALS['phpgw_info']['server']['template_set']
				. '/navbar.inc.php');
			if (!@$GLOBALS['phpgw_info']['flags']['nonavbar'] && !@$GLOBALS['phpgw_info']['flags']['navbar_target']) {
				echo parse_navbar();
			}
		}

		function phpgw_footer() {
			if (!defined('PHPGW_FOOTER')) {
				define('PHPGW_FOOTER',True);
				if (!isset($GLOBALS['phpgw_info']['flags']['nofooter']) || !$GLOBALS['phpgw_info']['flags']['nofooter']) {
					include(PHPGW_API_INC . '/footer.inc.php');
				}
			}
		}

		/**
		* Include CSS in template header
		*
		* This first loads up the basic global CSS definitions, which support
		* the selected user theme colors. Next we load up the app CSS. This is
		* all merged into the selected theme's css.tpl file.
		*
		* @author Dave Hall (*based* on verdilak? css inclusion code)
		* @return string Template including CSS definitions
		*/
		function get_css() {
			$tpl = createObject('phpgwapi.Template', $this->get_tpl_dir('phpgwapi'));
			$tpl->set_file('css', 'css.tpl');
			$tpl->set_var($GLOBALS['phpgw_info']['theme']);
			$app_css = '';
		    	if(@isset($GLOBALS['phpgw_info']['menuaction'])) {
	    			list($app,$class,$method) = explode('.',$GLOBALS['phpgw_info']['menuaction']);
    				if(is_array($GLOBALS[$class]->public_functions) && $GLOBALS[$class]->public_functions['css']) {
    					$app_css .= $GLOBALS[$class]->css();
    				}
    			}
    			if (isset($GLOBALS['phpgw_info']['flags']['css'])) {
    				$app_css .= $GLOBALS['phpgw_info']['flags']['css'];
    			}
			$tpl->set_var('app_css', $app_css);
			return $tpl->subst('css');			
		}

		/**
		* Include JavaScript in template header
		*
		* The method is included here to make it easier to change the js support
		* in phpgw. One change then all templates will support it (as long as they 
		* include a call to this method).
		*
		* @author Dave Hall (*vaguely based* on verdilak? css inclusion code)
		* @return string The JavaScript code to include
		*/
		function get_java_script() {
			$java_script = '';
			if(@is_object($GLOBALS['phpgw']->js)) {
				$java_script .= $GLOBALS['phpgw']->js->get_script_links();
			}
			
			if(@isset($GLOBALS['phpgw_info']['menuaction'])) {
				list($app,$class,$method) = explode('.',$GLOBALS['phpgw_info']['menuaction']);
				if(is_array($GLOBALS[$class]->public_functions) && $GLOBALS[$class]->public_functions['java_script']) {
					$java_script .= $GLOBALS[$class]->java_script();
				}
			}
			//you never know - best to protect the stupid ;)
			if (isset($GLOBALS['phpgw_info']['flags']['java_script'])) {
				$java_script .= $GLOBALS['phpgw_info']['flags']['java_script'] . "\n";
			}
			return $java_script;
		}

		/**
		* Get on(un)load attributes from javascript class
		*
		* @author Dave Hall <skwashd@phpgroupware.org>
		* @return string Body attributes or empty
		*/
		function get_body_attribs() {
			if(@is_object($GLOBALS['phpgw']->js)) {
				return $GLOBALS['phpgw']->js->get_body_attribs();
			} else {
				return '';
			}
		}

		function hex2bin($data) {
			$len = strlen($data);
			return @pack('H' . $len, $data);
		}

		/**
		* Encrypt data
		*
		* @param string $data Data to be encrypted
		* @return string Encrypted data
		*/
		function encrypt($data) {
			return $GLOBALS['phpgw']->crypto->encrypt($data);
		}

		/**
		* Decrypt data
		* @param string $data Data to be decrypted
		* @return string Decrypted data
		*/
		function decrypt($data) {
			return $GLOBALS['phpgw']->crypto->decrypt($data);
		}

		/**
		* DES encrypt a password
		*
		* @param string $userpass User password
		* @param string $random Random seed
		* @return string DES encrypted password
		*/
		function des_cryptpasswd($userpass, $random) {
			$lcrypt = '{crypt}';
			$password = crypt($userpass, $random);
			$ldappassword = sprintf('%s%s', $lcrypt, $password);
			return $ldappassword;
		}

		/**
		* MD5 encrypt password
		*
		* @param string $userpass User password
		* @param string $random Random seed
		* @return string MD5 encrypted password
		*/ 
		function md5_cryptpasswd($userpass, $random) {
			$bsalt = '$1$';
			$esalt = '$';
			$lcrypt = '{crypt}';
			$modsalt = sprintf('%s%s%s', $bsalt, $random, $esalt);
			$password = crypt($userpass, $modsalt);
			$ldappassword = sprintf('%s%s', $lcrypt, $password);
			return $ldappassword;
		}

		/**
		* Encrypt password based on encryption type set in setup
		*
		* @param string $password Password to encrypt
		* @return Encrypted password or false
		*/
		function encrypt_password($password) {
			if (strtolower($GLOBALS['phpgw_info']['server']['ldap_encryption_type']) == 'des') {
				$salt       = $this->randomstring(2);
				$e_password = $this->des_cryptpasswd($password, $salt);
				return $e_password;
			} elseif (strtolower($GLOBALS['phpgw_info']['server']['ldap_encryption_type']) == 'md5') {
				$salt       = $this->randomstring(8);
				$e_password = $this->md5_cryptpasswd($password, $salt);
				return $e_password;
			}
			return false;
		}

		/**
		* Find the current position of the application in the users portal_order preference
		*
		* @param integer $app Application id to find current position
		* @return integer Applications position or -1
		*/
		function find_portal_order($app) {
			if(!is_array($GLOBALS['phpgw_info']['user']['preferences']['portal_order'])) {
				return -1;
			}
			@reset($GLOBALS['phpgw_info']['user']['preferences']['portal_order']);
			while(list($seq,$appid) = each($GLOBALS['phpgw_info']['user']['preferences']['portal_order'])) {
				if($appid == $app) {
					@reset($GLOBALS['phpgw_info']['user']['preferences']['portal_order']);
					return $seq;
				}
			}
			@reset($GLOBALS['phpgw_info']['user']['preferences']['portal_order']);
			return -1;
		}

		/**
		* Wrapper to new hooks class
		*
		* @param string $location Location name
		* @param string $appname Application name
		* @param boolean $no_permission_check Do not use permission check when set to true
		* @return mixed Result from $GLOBALS['phpgw']->hooks->process()
		* @deprecated
		*/
		function hook($location, $appname = '', $no_permission_check = False) {
			echo '$'."GLOBALS['phpgw']common->hook()".' has been replaced. Please change to the new $'."GLOBALS['phpgw']hooks->process()".'. For now this will act as a wrapper<br>';
			return $GLOBALS['phpgw']->hooks->process($location, $order, $no_permission_check);
		}

		/**
		* Wrapper to new hooks class
		*
		* @param string $location Location name
		* @param string $appname Application name
		* @param boolean $no_permission_check Do not use permission check when set to true
		* @return mixed Result from $GLOBALS['phpgw']->hooks->single()
		* @deprecated
		* @internal $no_permission_check should *ONLY* be used when it *HAS* to be. (jengo)
		*/
		function hook_single($location, $appname = '', $no_permission_check = False) {
			echo '$'."GLOBALS['phpgw']common->hook_single()".' has been replaced. Please change to the new $'."GLOBALS['phpgw']hooks->single()".'. For now this will act as a wrapper<br>';
			return $GLOBALS['phpgw']->hooks->single($location, $order, $no_permission_check);
		}

		/**
		* Wrapper to new hooks class
		*
		* @param string $location Location name
		* @return mixed Result from $GLOBALS['phpgw']->hooks->count()
		* @deprecated
		*/
		function hook_count($location) {
			echo '$'."GLOBALS['phpgw']common->hook_count()".' has been replaced. Please change to the new $'."GLOBALS['phpgw']hooks->count()".'. For now this will act as a wrapper<br>';
			return $GLOBALS['phpgw']->hooks->count($location);
		}

		/**
		* Wrapper to the session->appsession()
		*
		* @param string $data Data
		* @return mixed Result of $GLOBALS['phpgw']->session->appsession()
		* @deprecated
		*/
		function appsession($data = '##NOTHING##') {
			$this->debug_info[] = '$phpgw->common->appsession() is a depreciated function' . ' - use $phpgw->session->appsession() instead';
			return $GLOBALS['phpgw']->session->appsession('default','',$data);
		}

		/**
		* Show current date
		*
		* @param integer $t Time, defaults to user preferences
		* @param string $format Date format, defaults to user preferences
		* @return string Formated date
		*/
		function show_date($t = '', $format = '') {
			if(!is_object($GLOBALS['phpgw']->datetime)) {
				$GLOBALS['phpgw']->datetime = createobject('phpgwapi.datetime');
			}
			if (!$t || intval($t) <= 0) {
				$t = $GLOBALS['phpgw']->datetime->gmtnow;
			}

			//  + (date('I') == 1?3600:0)
			$t += $GLOBALS['phpgw']->datetime->tz_offset;
			
			if (! $format) {
				$format = $GLOBALS['phpgw_info']['user']['preferences']['common']['dateformat'] . ' - ';
				if ($GLOBALS['phpgw_info']['user']['preferences']['common']['timeformat'] == '12') {
					$format .= 'h:i a';
				} else {
					$format .= 'H:i';
				}
			}
			return date($format,$t);
		}

		/**
		*
		* @param string $yearstr Year
		* @param string $monthstr Month
		* @param string $day Day
		* @param boolean $add_seperator Use separator, defaults to space
		* @return string Formatted date
		*/
		function dateformatorder($yearstr,$monthstr,$daystr,$add_seperator = False) {
			$dateformat = strtolower($GLOBALS['phpgw_info']['user']['preferences']['common']['dateformat']);
			$sep = substr($GLOBALS['phpgw_info']['user']['preferences']['common']['dateformat'],1,1);

			$dlarr[strpos($dateformat,'y')] = $yearstr;
			$dlarr[strpos($dateformat,'m')] = $monthstr;
			$dlarr[strpos($dateformat,'d')] = $daystr;
			ksort($dlarr);

			if ($add_seperator) {
				return (implode($sep,$dlarr));
			} else {
				return (implode(' ',$dlarr));
			}
		} 

		/**
		* Format the time takes settings from user preferences
		*
		* @param integer $hour Hour
		* @param integer $min Minute
		* @param integer $sec Second
		* @return string Time formatted as hhmmss with am/pm
		*/
		function formattime($hour,$min,$sec='') {
			$h12 = $hour;
			if ($GLOBALS['phpgw_info']['user']['preferences']['common']['timeformat'] == '12') {
				if ($hour >= 12) {
					$ampm = ' pm';
				} else {
					$ampm = ' am';
				}

				$h12 %= 12;

				if ($h12 == 0 && $hour) {
					$h12 = 12;
				}
				if ($h12 == 0 && !$hour) {
					$h12 = 0;
				}
			} else {
				$h12 = $hour;
			}
			if ($sec) {
				$sec = ":$sec";
			}
			return "$h12:$min$sec$ampm";
		}

		/**
		* Uses code in class email.msg to obtain the appropriate password for email
		*
		* @return string EMail password
		* @deprecated
		* @internal This is not the best place for it, but it needs to be shared bewteen Aeromail and SM
		*/
		/*
		function get_email_passwd_ex() {
			// ----  Create the email Message Class  if needed  -----
			if (is_object($GLOBALS['phpgw']->msg)) {
				$do_free_me = False;
			} else {
				$GLOBALS['phpgw']->msg = CreateObject('email.mail_msg');
				$do_free_me = True;
			}
			// use the Msg class to obtain the appropriate password
			$tmp_prefs = $GLOBALS['phpgw']->preferences->read();
			if (!isset($tmp_prefs['email']['passwd'])) {
				$email_passwd = $GLOBALS['phpgw_info']['user']['passwd'];
			} else {
				$email_passwd = $GLOBALS['phpgw']->msg->decrypt_email_passwd($tmp_prefs['email']['passwd']);
			}
			// cleanup and return
			if ($do_free_me) {
				unset ($GLOBALS['phpgw']->msg);
			}
			return $email_passwd;
		}
		*/

		/**
		* Create email preferences
		*
		* @param mixed $prefs Unused
		* @param integer $account_id Account id, defaults to phpgw_info['user']['account_id']
		* @internal This is not the best place for it, but it needs to be shared between Aeromail and SM
		*/
		function create_emailpreferences($prefs='',$accountid='') {
			return $GLOBALS['phpgw']->preferences->create_email_preferences($accountid);
			// Create the email Message Class if needed
			if (is_object($GLOBALS['phpgw']->msg)) {
				$do_free_me = False;
			} else {
				$GLOBALS['phpgw']->msg = CreateObject('email.mail_msg');
				$do_free_me = True;
			}

			// this sets the preferences into the phpgw_info structure
			$GLOBALS['phpgw']->msg->create_email_preferences();

			// cleanup and return
			if ($do_free_me) {
				unset ($GLOBALS['phpgw']->msg);
			}
		}

		/*
		function create_emailpreferences($prefs,$accountid='') {
			$account_id = get_account_id($accountid);
			
			// NEW EMAIL PASSWD METHOD (shared between SM and aeromail)
			$prefs['email']['passwd'] = $this->get_email_passwd_ex();
			
			// Add default preferences info
			if (!isset($prefs['email']['userid'])) {
				if ($GLOBALS['phpgw_info']['server']['mail_login_type'] == 'vmailmgr') {
					$prefs['email']['userid'] = $GLOBALS['phpgw']->accounts->id2name($account_id)
						. '@' . $GLOBALS['phpgw_info']['server']['mail_suffix'];
				} else {
					$prefs['email']['userid'] = $GLOBALS['phpgw']->accounts->id2name($account_id);
				}
			}
			// Set Server Mail Type if not defined
			if (empty($GLOBALS['phpgw_info']['server']['mail_server_type'])) {
				$GLOBALS['phpgw_info']['server']['mail_server_type'] = 'imap';
			}
			
			// OLD EMAIL PASSWD METHOD
			if (!isset($prefs['email']['passwd'])) {
				$prefs['email']['passwd'] = $GLOBALS['phpgw_info']['user']['passwd'];
			} else {
				$prefs['email']['passwd'] = $this->decrypt($prefs['email']['passwd']);
			}
			// NEW EMAIL PASSWD METHOD Located at the begining of this function
			
			if (!isset($prefs['email']['address'])) {
				$prefs['email']['address'] = $GLOBALS['phpgw']->accounts->id2name($account_id)
					. '@' . $GLOBALS['phpgw_info']['server']['mail_suffix'];
			}
			if (!isset($prefs['email']['mail_server'])) {
				$prefs['email']['mail_server'] = $GLOBALS['phpgw_info']['server']['mail_server'];
			}
			if (!isset($prefs['email']['mail_server_type'])) {
				$prefs['email']['mail_server_type'] = $GLOBALS['phpgw_info']['server']['mail_server_type'];
			}
			if (!isset($prefs['email']['imap_server_type'])) {
				$prefs['email']['imap_server_type'] = $GLOBALS['phpgw_info']['server']['imap_server_type'];
			}
			// These sets the mail_port server variable
			if ($prefs['email']['mail_server_type']=='imap') {
				$prefs['email']['mail_port'] = '143';
			} elseif ($prefs['email']['mail_server_type']=='pop3') {
				$prefs['email']['mail_port'] = '110';
			} elseif ($prefs['email']['mail_server_type']=='imaps') {
 				$prefs['email']['mail_port'] = '993';
 			} elseif ($prefs['email']['mail_server_type']=='pop3s') {
 				$prefs['email']['mail_port'] = '995';
 			}
			// This is going to be used to switch to the nntp class
			if (isset($phpgw_info['flags']['newsmode']) && $GLOBALS['phpgw_info']['flags']['newsmode']) {
				$prefs['email']['mail_server_type'] = 'nntp';
			}
			return $prefs;
		}
		*/

		/**
		* Convert application code to HTML text message
		*
		* @param integer $code Code number to convert into HTML string
		* @return string HTML string with code check result message
		* @internal This will be moved into the applications area
		*/
		function check_code($code) {
			$s = '<br />';
			switch ($code) {
				case 13:	$s .= lang('Your message has been sent');break;
				case 14:	$s .= lang('New entry added sucessfully');break;
				case 15:	$s .= lang('Entry updated sucessfully');	break;
				case 16:	$s .= lang('Entry has been deleted sucessfully'); break;
				case 18:	$s .= lang('Password has been updated');	break;
				case 38:	$s .= lang('Password could not be changed');	break;
				case 19:	$s .= lang('Session has been killed');	break;
				case 27:	$s .= lang('Account has been updated');	break;
				case 28:	$s .= lang('Account has been created');	break;
				case 29:	$s .= lang('Account has been deleted');	break;
				case 30:	$s .= lang('Your settings have been updated'); break;
				case 31:	$s .= lang('Group has been added');	break;
				case 32:	$s .= lang('Group has been deleted');	break;
				case 33:	$s .= lang('Group has been updated');	break;
				case 34:	$s .= lang('Account has been deleted') . '<p>'
						. lang('Error deleting %1 %2 directory',lang('users'),' '.lang('private').' ') 
						. ',<br />' . lang('Please %1 by hand',lang('delete')) . '<br /><br />'
						. lang('To correct this error for the future you will need to properly set the')
						. '<br />' . lang('permissions to the files/users directory')
						. '<br />' . lang('On *nix systems please type: %1','chmod 770 '
						. $GLOBALS['phpgw_info']['server']['files_dir'] . '/users/'); 
					break;
				case 35:	$s .= lang('Account has been updated') . '<p>'
						. lang('Error renaming %1 %2 directory',lang('users'),
						' '.lang('private').' ') 
						. ',<br />' . lang('Please %1 by hand',
						lang('rename')) . '<br /><br />'
						. lang('To correct this error for the future you will need to properly set the')
						. '<br>' . lang('permissions to the files/users directory')
						. '<br>' . lang('On *nix systems please type: %1','chmod 770 '
						. $GLOBALS['phpgw_info']['server']['files_dir'] . '/users/'); 
					break;
				case 36:	$s .= lang('Account has been created') . '<p>'
						. lang('Error creating %1 %2 directory',lang('users'),
						' '.lang('private').' ') 
						. ',<br />' . lang('Please %1 by hand',
						lang('create')) . '<br /><br />'
						. lang('To correct this error for the future you will need to properly set the')
						. '<br />' . lang('permissions to the files/users directory')
						. '<br />' . lang('On *nix systems please type: %1','chmod 770 '
						. $GLOBALS['phpgw_info']['server']['files_dir'] . '/users/'); 
					break;
				case 37:	$s .= lang('Group has been added') . '<p>'
						. lang('Error creating %1 %2 directory',lang('groups'),' ')
						. ',<br />' . lang('Please %1 by hand',
						lang('create')) . '<br /><br />'
						. lang('To correct this error for the future you will need to properly set the')
						. '<br />' . lang('permissions to the files/users directory')
						. '<br />' . lang('On *nix systems please type: %1','chmod 770 '
						. $GLOBALS['phpgw_info']['server']['files_dir'] . '/groups/'); 
					break;
				case 38:	$s .= lang('Group has been deleted') . '<p>'
						. lang('Error deleting %1 %2 directory',lang('groups'),' ')
						. ',<br />' . lang('Please %1 by hand',
						lang('delete')) . '<br /><br />'
						. lang('To correct this error for the future you will need to properly set the')
						. '<br />' . lang('permissions to the files/users directory')
						. '<br />' . lang('On *nix systems please type: %1','chmod 770 '
						. $GLOBALS['phpgw_info']['server']['files_dir'] . '/groups/'); 
					break;
				case 39:	$s .= lang('Group has been updated') . '<p>'
						. lang('Error renaming %1 %2 directory',lang('groups'),' ')
						. ',<br />' . lang('Please %1 by hand',
						lang('rename')) . '<br /><br />'
						. lang('To correct this error for the future you will need to properly set the')
						. '<br />' . lang('permissions to the files/users directory')
						. '<br />' . lang('On *nix systems please type: %1','chmod 770 '
						. $GLOBALS['phpgw_info']['server']['files_dir'] . '/groups/'); 
					break;
				case 40: $s .= lang('You have not entered a title').'.';
					break;
				case 41: $s .= lang('You have not entered a valid time of day').'.';
					break;
				case 42: $s .= lang('You have not entered a valid date').'.';
					break;
				case 43: $s .= lang('You have not entered participants').'.';
					break;
				default:	return '';
			}
			return $s;
		}

		/**
		* Process error message
		*
		* @param string $error Error message 
		* @param integer $line Line number of error
		* @param string $file Filename in which the error occured
		*/
		function phpgw_error($error,$line = '', $file = '') {
			echo '<p><strong>phpGroupWare internal error:</strong><p>'.$error;
			if ($line) {
				echo 'Line: '.$line;
			}
			if ($file) {
				echo 'File: '.$file;
			}
			echo '<p>Your session has been halted.';
			exit;
		}

		/**
		* Create $phpgw_info[] code from an array
		*
		* @param array $array Array with 1-4 key/value pairs for $phpgw_info[]
		* @return string String with generated $phpgw_info[] code 
		*/
		function create_phpcode_from_array($array) {
			while (list($key, $val) = each($array)) {
				if (is_array($val)) {
					while (list($key2, $val2) = each($val)) {
						if (is_array($val2)) {
							while (list($key3, $val3) = each ($val2)) {
								if (is_array($val3)) {
									while (list($key4, $val4) = each ($val3)) {
										$s .= '$phpgw_info["' . $key . '"]["' . $key2 . '"]["' . $key3 . '"]["' .$key4 . '"]="' . $val4 . '";';
										$s .= "\n";
									}
								} else {
									$s .= '$phpgw_info["' . $key . '"]["' . $key2 . '"]["' . $key3 . '"]="' . $val3 . '";';
									$s .= "\n";
								}
							}
						} else {
							$s .= '$phpgw_info["' . $key .'"]["' . $key2 . '"]="' . $val2 . '";';
							$s .= "\n";
						}
					}
				} else {
					$s .= '$phpgw_info["' . $key . '"]="' . $val . '";';
					$s .= "\n";
				}
			}
			return $s;
		}

		/**
		* Display the full phpgw_info array for debugging
		*
		* @param array $array phpgw_info[]
		*/
		function debug_list_array_contents($array) {
			while (list($key, $val) = each($array)) {
				if (is_array($val)) {
					while (list($key2, $val2) = each($val)) {
						if (is_array($val2)) {
							while (list($key3, $val3) = each ($val2)) {
								if (is_array($val3)) {
									while (list($key4, $val4) = each ($val3)) {
										echo $$array . "[$key][$key2][$key3][$key4]=$val4<br />";
									}
								} else {
									echo $$array . "[$key][$key2][$key3]=$val3<br />";
								}
							}
						} else {
							echo $$array . "[$key][$key2]=$val2<br />";
						}
					}
				} else {
					echo $$array . "[$key]=$val<br />";
				}
			}
		}

		/**
		* Display a list of core functions in the API
		*
		* @internal Works on systems with grep only
		*/
		function debug_list_core_functions() {
			echo '<br /><strong>core functions</strong><br />';
			echo '<pre>';
			chdir(PHPGW_INCLUDE_ROOT . '/phpgwapi');
			system("grep -r '^[ \t]*function' *");
			echo '</pre>';
		}

		/**
		* Get the next higher value for an integer and increment it in the database
		*
		* @param string $appname Application name to get an id for
		* @param integer $min Minimum of id range
		* @param integer $max Maximum of id range
		* @return integer|boolean Next available id or false
		*/
		function next_id($appname,$min=0,$max=0) {
			if (!$appname) {
				return -1;
			}

			$GLOBALS['phpgw']->db->query("SELECT id FROM phpgw_nextid WHERE appname='".$appname."'",__LINE__,__FILE__);
			while( $GLOBALS['phpgw']->db->next_record() ) {
				$id = $GLOBALS['phpgw']->db->f('id');
			}

			if (empty($id) || !$id) {
				$id = 1;
				$GLOBALS['phpgw']->db->query("INSERT INTO phpgw_nextid (appname,id) VALUES ('".$appname."',".$id.")",__LINE__,__FILE__);
			} elseif($id<$min) {
				$id = $min;
				$GLOBALS['phpgw']->db->query("UPDATE phpgw_nextid SET id=".$id." WHERE appname='".$appname."'",__LINE__,__FILE__);
			} elseif ($max && ($id > $max)) {
				return False;
			} else {
				$id = $id + 1;
				$GLOBALS['phpgw']->db->query("UPDATE phpgw_nextid SET id=".$id." WHERE appname='".$appname."'",__LINE__,__FILE__);
			}
			return intval($id);
		}

		/**
		* Get the current id in the next_id table for a particular application/class
		*
		* @param string $appname Application name to get the id for
		* @param integer $min Minimum of id range
		* @param integer $max Maximum of id range
		* @return integer|boolean Last used id or false
		*/
		function last_id($appname,$min=0,$max=0) {
			if (!$appname) {
				return -1;
			}

			$GLOBALS['phpgw']->db->query("SELECT id FROM phpgw_nextid WHERE appname='".$appname."'",__LINE__,__FILE__);
			while( $GLOBALS['phpgw']->db->next_record() ) {
				$id = $GLOBALS['phpgw']->db->f('id');
			}

			if (empty($id) || !$id) {
				if($min) {
					$id = $min;
				} else {
					$id = 1;
				}
				$GLOBALS['phpgw']->db->query("INSERT INTO phpgw_nextid (appname,id) VALUES ('".$appname."',".$id.")",__LINE__,__FILE__);
			} elseif($id<$min) {
				$id = $min;
				$GLOBALS['phpgw']->db->query("UPDATE phpgw_nextid SET id=".$id." WHERE appname='".$appname."'",__LINE__,__FILE__);
			} elseif ($max && ($id > $max)) {
				return False;
			} else {
				return intval($id);
			}
		}
	}
?>