<?php
/*
** Zabbix
** Copyright (C) 2001-2020 Zabbix SIA
**
** This program is free software; you can redistribute it and/or modify
** it under the terms of the GNU General Public License as published by
** the Free Software Foundation; either version 2 of the License, or
** (at your option) any later version.
**
** This program is distributed in the hope that it will be useful,
** but WITHOUT ANY WARRANTY; without even the implied warranty of
** MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
** GNU General Public License for more details.
**
** You should have received a copy of the GNU General Public License
** along with this program; if not, write to the Free Software
** Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
**/


/**
 * Database backend class for Oracle.
 */
class OracleDbBackend extends DbBackend {

	/**
	 * Check if 'dbversion' table exists.
	 *
	 * @return boolean
	 */
	protected function checkDbVersionTable() {
		$tableExists = DBfetch(DBselect("SELECT table_name FROM user_tables WHERE table_name='DBVERSION'"));

		if (!$tableExists) {
			$this->setError(_('The frontend does not match Zabbix database.'));
			return false;
		}

		return true;
	}

	/**
	 * Create INSERT SQL query.
	 * Creation example:
	 *	BEGIN
	 *	INSERT INTO usrgrp (usrgrpid, name, gui_access, users_status, debug_mode)
	 *		VALUES ('20', 'admins', '1', '0', '1');
	 *	INSERT INTO usrgrp (usrgrpid, name, gui_access, users_status, debug_mode)
	 *		VALUES ('21', 'users', '0', '0', '0');
	 *  END;
	 */
	public function createInsertQuery($table, array $fields, array $values) {
		$sql = 'BEGIN';
		$fields = '('.implode(',', $fields).')';
		foreach ($values as $row) {
			$sql .= ' INSERT INTO '.$table.' '.$fields.' VALUES ('.implode(',', array_values($row)).');';
		}
		$sql .= ' END;';

		return $sql;
	}

	public function checkEncoding() {
		global $DB;

		return $this->checkDatabaseEncoding();
	}

	protected function checkDatabaseEncoding() {
		$db_params = DBfetch(DBselect('SELECT value, parameter FROM NLS_DATABASE_PARAMETERS'.
			' WHERE '.dbConditionString('parameter', ['NLS_CHARACTERSET', 'NLS_NCHAR_CHARACTERSET']).
				' AND value!='.zbx_dbstr(ZBX_DB_DEFAULT_CHARSET)
		));

		foreach ($db_params as $db_param) {
			if ($db_param['value'] != ZBX_DB_DEFAULT_CHARSET) {
				$this->setWarning((_s('Incorrect parameter "%1$s" value: %2$s.',
					$db_param['parameter'], _s('"%1$s" instead "%2$s"', $db_param['value'], ZBX_DB_DEFAULT_CHARSET)
				)));
				return false;
			}
		}

		return true;
	}
}
