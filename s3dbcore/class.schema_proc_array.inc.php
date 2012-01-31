<?php
	/**
	* Database schema abstraction class for array
	* @author Michael Dean <mdean@users.sourceforge.net>
	* @author Miles Lott <milosch@phpgroupware.org>
	* @copyright Copyright (C) ? Michael Dean, Miles Lott
	* @copyright Portions Copyright (C) 2003,2004 Free Software Foundation, Inc. http://www.fsf.org/
	* @license http://www.fsf.org/licenses/gpl.html GNU General Public License
	* @package phpgwapi
	* @subpackage database
	* @version $Id: class.schema_proc_array.inc.php,v 1.2.2.5 2004/02/10 13:51:18 ceb Exp $
	*/

	/**
	* Database schema abstraction class for array
	* 
	* @package phpgwapi
	* @subpackage database
	*/
	class schema_proc_array
	{
		var $m_sStatementTerminator;

		function schema_proc_array()
		{
			$this->m_sStatementTerminator = ';';
		}

		/* Return a type suitable for DDL abstracted array */
		function TranslateType($sType, $iPrecision = 0, $iScale = 0)
		{
			$sTranslated = $sType;
			return $sTranslated;
		}

		function TranslateDefault($sDefault)
		{
			return "'" . $sDefault . "'";
		}

		function GetPKSQL($sFields)
		{
			return '';
		}

		function GetUCSQL($sFields)
		{
			return '';
		}

		function _GetColumns($oProc, &$aTables, $sTableName, &$sColumns, $sDropColumn='')
		{
			$sColumns = '';
			while(list($sName, $aJunk) = each($aTables[$sTableName]['fd']))
			{
				if($sColumns != '')
				{
					$sColumns .= ',';
				}
				$sColumns .= $sName;
			}

			return True;
		}

		function DropTable($oProc, &$aTables, $sTableName)
		{
			if(isset($aTables[$sTableName]))
			{
				unset($aTables[$sTableName]);
			}

			return True;
		}

		function DropColumn($oProc, &$aTables, $sTableName, $aNewTableDef, $sColumnName, $bCopyData=True)
		{
			if(isset($aTables[$sTableName]))
			{
				if(isset($aTables[$sTableName]['fd'][$sColumnName]))
				{
					unset($aTables[$sTableName]['fd'][$sColumnName]);
				}
			}

			return True;
		}

		function RenameTable($oProc, &$aTables, $sOldTableName, $sNewTableName)
		{
			$aNewTables = array();
			while(list($sTableName, $aTableDef) = each($aTables))
			{
				if($sTableName == $sOldTableName)
				{
					$aNewTables[$sNewTableName] = $aTableDef;
				}
				else
				{
					$aNewTables[$sTableName] = $aTableDef;
				}
			}

			$aTables = $aNewTables;

			return True;
		}

		function RenameColumn($oProc, &$aTables, $sTableName, $sOldColumnName, $sNewColumnName, $bCopyData=True)
		{
			if (isset($aTables[$sTableName]))
			{
				$aNewTableDef = array();
				reset($aTables[$sTableName]['fd']);
				while(list($sColumnName, $aColumnDef) = each($aTables[$sTableName]['fd']))
				{
					if($sColumnName == $sOldColumnName)
					{
						$aNewTableDef[$sNewColumnName] = $aColumnDef;
					}
					else
					{
						$aNewTableDef[$sColumnName] = $aColumnDef;
					}
				}

				$aTables[$sTableName]['fd'] = $aNewTableDef;

				reset($aTables[$sTableName]['pk']);
				while(list($key, $sColumnName) = each($aTables[$sTableName]['pk']))
				{
					if($sColumnName == $sOldColumnName)
					{
						$aTables[$sTableName]['pk'][$key] = $sNewColumnName;
					}
				}

				reset($aTables[$sTableName]['uc']);
				while(list($key, $sColumnName) = each($aTables[$sTableName]['uc']))
				{
					if($sColumnName == $sOldColumnName)
					{
						$aTables[$sTableName]['uc'][$key] = $sNewColumnName;
					}
				}
			}

			return True;
		}

		function AlterColumn($oProc, &$aTables, $sTableName, $sColumnName, &$aColumnDef, $bCopyData=True)
		{
			if(isset($aTables[$sTableName]))
			{
				if(isset($aTables[$sTableName]['fd'][$sColumnName]))
				{
					$aTables[$sTableName]['fd'][$sColumnName] = $aColumnDef;
				}
			}

			return True;
		}

		function AddColumn($oProc, &$aTables, $sTableName, $sColumnName, &$aColumnDef)
		{
			if(isset($aTables[$sTableName]))
			{
				if(!isset($aTables[$sTableName]['fd'][$sColumnName]))
				{
					$aTables[$sTableName]['fd'][$sColumnName] = $aColumnDef;
				}
			}

			return True;
		}

		function CreateTable($oProc, &$aTables, $sTableName, $aTableDef)
		{
			if(!isset($aTables[$sTableName]))
			{
				$aTables[$sTableName] = $aTableDef;
			}

			return True;
		}
	}
?>
