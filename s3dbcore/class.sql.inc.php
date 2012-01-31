<?php
	/**
	* SQL Generator OPERANDS & TYPES - help to create criterias for common queries
	* @author Edgar Antonio Luna Diaz <eald@co.com.mx>
	* @author Alejadro Borges
	* @author Jonathan Alberto Rivera Gomez
	* @copyright Copyright (C) 2003,2004 Free Software Foundation, Inc. http://www.fsf.org/
	* @license http://www.fsf.org/licenses/gpl.html GNU General Public License
	* @package phpgwapi
	* @subpackage database
	* @version $Id: class.sql.inc.php,v 1.1.2.10 2004/02/10 13:51:19 ceb Exp $
	* @internal Development of this application was funded by http://www.sogrp.com
	* @link http://www.sogrp.com/
	*/

	if (empty($GLOBALS['s3db_info']['server']['db_type']))
	{
		$GLOBALS['s3db_info']['server']['db_type'] = 'pgsql';
	}

	/**
	* Include concrete database class
	*/
	include(S3DB_SERVER_ROOT.'/s3dbapi/inc/class.sql_'.$GLOBALS['s3db_info']['server']['db_type'].'.inc.php');


	/**
	* SQL Generator OPERANDS & TYPES - help to create criterias for common queries
	*
	* This class provide common methods to set, mantain, an retrive the queries 
	* to use in a query (for the where clause).
	* @package phpgwapi
	* @subpackage database
	* @abstract
	*/
	class sql_
	{
		function sql_()
		{
		}

		/*************************************************************\
		* Usefull low level functions to create queries logically   *
		\*************************************************************/

		/**
		* Genarete equal criteria for sql
		*
		* @param string $left The left operand of the staement
		* @param string $right The right operand of the statement
		* @return string with an equal criteria formated.
		*/
		function equal($field, $value)
		{
			return $field.' = '.$value;
		}

		/**
		* Generate a critieria for non equal comparission for sql.
		*
		* @param string $left Left operand.
		* @param string $right Right operand.
		* @return string with criteria.
		*/
		function not_equal($field, $value)
		{
			return $field.' <> '.$value;
		}

		/**
		* Generate greater than criteria for sql
		*
		* @param string $left The left operand of the staement
  		* @param string $right The right operand of the statement
		* @return string with an greater than criteria formated.
		*/
		function greater($field, $value)
		{
			return $field.' > '.$value;
		}
		
		/**
		* Generate less than criteria for sql (in string)
		*
		* @param string $left The left operand of the staement
		* @param string $right The right operand of the statement
		* @return string with an less than criteria formated.
		*/
		function less($field, $value)
		{
			return $field.' < '.$value;
		}
		
		/**
		* Generate greater-equal than criteria for sql
		*
		* @param string $left The left operand of the staement
  		* @param string $right The right operand of the statement
		* @return string with an greater-equal than criteria formated.
		*/
		function greater_equal($field, $value)
		{
			return $field.' >= '.$value;
		}

		/**
		* Generate less-equal than criteria for sql (in string)
		*
		* @param string $left The left operand of the staement
		* @param string $right The right operand of the statement
		* @return string with an less-equal than criteria formated.
		*/
		function less_equal($field, $value)
		{
			return $field.' <= '.$value;
		}

		/**
		* Generate a criteria for search in the content of a field a value for sql.
		*
		* @param string $field For search in.
		* @param string $value That will search.
		* @return string that use LIKE to search in field.
		*/
		function has($field, $value)
		{
			return sql_criteria::upper($field).' LIKE '."'%$value%'";
		}

		/**
		* Generate a criteria to search in the beginning of a field for sql.
		*
		* @param string $field For search in.
		* @param string $value That will search.
		* @return string that use LIKE to search in field.
		*/
		function begin_with($field, $value)
		{
			return sql_criteria::upper($field).' LIKE '."'$value%'";
		}

		/**
		* Generate a critieria to search in the end of a field for sql.
		*
		* @param string $field For search in.
		* @param string $value That will search.
		* @return string that use LIKE to search in field.
		*/
		function end_with($field, $value)
		{
			return sql_criteria::upper($field).' LIKE '."'%$value'";
		}

		/**
		* Generate an AND conjuction for sql criterias.
		*
		* Always return with brackets. I have more confidence in DBMS speed than the code that I will need to analize it in php.
		* @param string $left Left operand.
		* @param string $right Right operand.
		* @return string with (right) and (left)
		*/
		function and_($left, $right)
		{
			return '('.$left.' AND '.$right.')';
		}

		/**
		* Generate an OR conjuction for sql criterias.
		*
		* @param string $left Left operand.
		* @param string $right Right operand.
		* @return string with (right) or (left)
		*/
		function or_($left, $right)
		{
			return ' ('.$left.' OR '.$right.') ';
		}

		/**
		* Generate a is null critieria for sql.
		*
		* @param string $data A field.
		* @return string with criteria.
		*/
		function is_null($data)
		{
			return $data.' IS NULL';
		}

		/**
		* Generate a is not null critieria for sql.
		*
		* @param string $data A field.
		* @return string with criteria.
		*/		
		function not_null($data)
		{
			return $data.' IS NOT NULL';
		}

		function upper($value)
		{
			return 'UPPER('.$value.')';
		}

		function lower($value)
		{
			return 'LOWER('.$value.')';
		}

		/**
		* Generate a IN sql operator
		*
		* @param string $field String with the field which you can filter.
		* @param string $values Array with posible values
		* @return string with criteria.
		*/
		function in($field, $values, $type='integer')
		{
			// This must be changed by anything
			if(count($values) > 1)
			{
				if($type != 'integer' && $type != '')
				{
					return str_replace(',\'', '\',\'', $field.' IN (\''.implode(",'",$values)."')");
				}
				else 
				{
					return $field.' IN ('.implode(",", $values) .')';
				}
			}
			else
			{
				$type = $type ? $type : 'integer';
				if (is_array($values))
				{
					return sql::equal($field, sql::$type(current($values)));
				}
				else
				{
					return sql::equal($field, sql::$type($values));
				}	
			}
		}

		/**
		* Act like a lisp and(one, two, three,...infinity) adding clause with and.
		*
		* All and's are in same level, (without parenethesis).
		* @param string $and Array with the list of operators for and.
		* @return string with many and conjuntions at same level.
		*/
		function append_and($clause)
		{
			if(is_array($clause))
			{
				$value = array_shift($clause);
				$return_value = $value;
				foreach($clause as $element)
				{
					$return_value .= empty($element)?'':' AND '.$element;
				}
				return '('. $return_value .')';
			}
		}
		
		/**
		* Append many criterias with `or' conjuntion
		*
		* @param string $and Array with the list of operators for or.
		* @return string with many or conjuntions at same level.
		* @see append_and
		*/
		function append_or($clause)
		{
			if(is_array($clause))
			{
				$value = array_shift($clause);
				$return_value = $value;
				foreach($clause as $element)
				{
					$return_value .= empty($element)?'':' OR '.$element;
				}
				return '('. $return_value.')';
			}
		}
		/*************************************************************\
		* SQL Standard Types                                         *
		\*************************************************************/

		/**
		* @param str string the value that will be casted for sql type
		* @return string ready for using for a value with CHARACTER sql type
		*/
		function string($str)
		{
			$str = db::db_addslashes($str);
			return "'$str'";
		}

		function character($str)
		{
			return sql::string($str);
		}

		/**
		* @param integer string the value that will be casted for sql type
		* @return string ready for using for a value with INTEGER sql type		
		*/
		function integer($integer)
		{
			return intval($integer);
		}

		/**
		* Generate a string with date
		*/
		function date_($date, $format=False)
		{
			switch(gettype($date))
			{
			case 'integer':
				return sql::int_date2str($date, $format);
			default:
				return sql::str_date2int($date, $format);
			}
		}

		/**
		* return a string with time
		*/
		function time_($time, $format=False)
		{
			switch(gettype($time))
			{
			case 'integer':
				return sql::int_time2str($time, $format);
			default:
				return sql::str_time2int($time, $format);
			}
		}

		/*************************************************************\
		* Data types conversion                                      *
		\*************************************************************/

		function int_date2str($int, $format=False)
		{
			$format = $format ? $format : $GLOBALS['phpgw_info']['user']['preferences']['common']['dateformat'];
			return date($format, intval($int));
		}

		function int_time2str($int, $format=False)
		{
			$format = $format ? $format : $GLOBALS['phpgw_info']['user']['preferences']['common']['timeformat'];
			return date($format, intval($int));
		}
		//note this is not 100% reliable, but close enough
		function str_date2int($date, $format=False)
		{
			$format = $format ? $format : $GLOBALS['phpgw_info']['user']['preferences']['common']['dateformat'];
			return date($format, intval(strtotime($date)));
		}

		function str_time2int($time)
		{
			return intval(sql::str_date2int($time));
		}

		/*************************************************************\
		* Constants                                                   *
		\*************************************************************/

		/**
		* Return a NULL value
		*/
		function null()
		{
			return ' NULL ';
		}
		/*************************************************************\
		* Functions                                                   *
		\*************************************************************/
		/**
		* Return the function that concatenate fields on $elements
		*
		* @param array $elements array with the elemnts that want to concatenate
		* @return string with $elements concatenated
		*/
		function concat($elements)
		{
		}

		/**
		* Return the function that concatenate fields, when any returned value<br />
		* is null, it changet it for empty string.
		*
		* @param array $elements array with the elemnts that want to concatenate
		* @return string with $elements concatenated
		*/
		function concat_null($elements)
		{
		}

		/**
		* This function change to empty string, a NULL value for select.
		*
		* When data retrieved from database is NULL it allow change it to empty<br />
		* string. use it in SELECT development.
		* @param string $value Field or expresion to make safe.
		*/
		function safe_null($value)
		{
			if(empty($value) || !is_array($value))
			{
				return array();
			}
			foreach($value as $data)
			{
				$return_value[] = '(CASE WHEN '.$data.' IS NULL THEN \'\' ELSE '.$data.' END)';
			}
			return $return_value;
		}
	}
?>
