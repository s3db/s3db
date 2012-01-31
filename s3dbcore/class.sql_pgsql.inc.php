<?php
	/**
	* SQL Generator for PostrgreSQL
	* @author Edgar Antonio Luna Diaz <eald@co.com.mx>
	* @author Alejadro Borges
	* @author Jonathan Alberto Rivera Gomez
	* @copyright Copyright (C) 2003,2004 Free Software Foundation, Inc. http://www.fsf.org/
	* @license http://www.fsf.org/licenses/gpl.html GNU General Public License
	* @package phpgwapi
	* @subpackage database
	* @version $Id: class.sql_pgsql.inc.php,v 1.1.2.6 2004/02/10 13:51:19 ceb Exp $
	* @internal Development of this application was funded by http://www.sogrp.com
	* @link http://www.sogrp.com/
	*/

	/**
	* SQL Generator for PostrgreSQL
	*
	* @package phpgwapi
	* @subpackage database
	*/
	class sql extends sql_
	{
		function sql_()
		{
		}

		function concat($elements)
		{
			$str = implode(' || ', $elements);
			return ($str)? '('.$str.')' : '';
		}

		function concat_null($elements)
		{
			$str = implode(' || ', sql::safe_null($elements));
			return ($str)? '('.$str.')' : '';
		}
		function has($field, $value)
		{
			return sql_criteria::upper($field).' ILIKE '."'%$value%'";
		}
	}
?>
