<?php

/*
ArrayMySQL 
Version: 0.4 Beta
Developer: Shubham Gupta
Licence: MIT
Last Updated: 28 April, 2021 at 12:59 PM UTC +5:30
*/

class ArrayMySQL
{
	private const ERROR_MYSQLI_QUERY_MSG = 'Error in mysqli query';
	private const ERROR_MYSQLI_CONNECT_MSG = 'Error in mysqli connection';
	public const ERROR_CODE = 20;

	public function __construct(mysqli $db)
	{
		if ($db->connect_errno) {
			throw new Exception(self::ERROR_MYSQLI_CONNECT_MSG, self::ERROR_CODE);
		}
		$db->options(MYSQLI_OPT_INT_AND_FLOAT_NATIVE, TRUE);
		$this->db = $db;
	}

	public function isConnected()
	{
		if ($this->db->isConnected()) {
			return true;
		} else {
			return false;
		}
	}

	public function insertSQL(string $table, array $valueArr)
	{
		$columns = "";
		$values = "";
		$array = [];
		foreach ($valueArr as $key => $value) {
			// $key = $this->escapeColonSQL($key);
			$columns .= "$key, ";
			$values .= "?, ";
			$array[] = $value;
		}
		$columns = substr($columns, 0, -2);
		$values = substr($values, 0, -2);
		$query = "INSERT INTO $table ($columns) VALUES ($values)";
		$this->staticSQL($query, $array);
	}

	public function deleteSQL(string $table, string $extras, array $array = null)
	{
		$query = `DELETE FROM $table $extras`;
		$this->staticSQL($query, $array);
	}

	public function updateSQL(string $table, array $newArr, string $extras, array $array = null)
	{
		$str = "";
		foreach ($newArr as $key => $value) {
			if (gettype($value) == 'string')
				$value = "'" . $this->real_escape($value) . "'";
			else
				$value = $this->real_escape($value);
			$str = "$str $key = $value,";
		}
		$str = substr($str, 0, -1);
		$query = "UPDATE $table SET $str $extras";
		$this->staticSQL($query, $array);
	}

	public function selectSQL(string $table, string $columns, string $extras, array $array = null)
	{
		return $this->arraySQL("SELECT $columns FROM $table $extras", $array);
	}

	public function selectPageSQL(string $table, string $columns, string $extras, array $array = null, int $itemPerPage = 20, int $page = 1)
	{
		return $this->arrayPageSQL("SELECT $columns FROM $table $extras", $array, $itemPerPage, $page);
	}

	public function countSQL(string $table, string $extras, array $array)
	{
		$result_pages = $this->arraySQL("SELECT count(*) FROM $table $extras", $array);
		return (int)  ($result_pages != null) ? $result_pages[0] : 0 ;
	}

	public function getPagesCount(int $count, int $itemPerPage = 20)
	{
		$total_pages = ceil($count / $itemPerPage);
		return (int) $total_pages;
	}

	public function staticSQL(string $baseQuery, array $array = null)
	{
		if ($array == null) {
			if (!$this->db->query($baseQuery)) {
				throw new Exception(self::ERROR_MYSQLI_QUERY_MSG, self::ERROR_CODE);
			}
		} else {
			$stmt = $this->_prepare_stmt($baseQuery, $array);
			$stmt->execute();
		}
	}

	public function arrayPageSQL(string $baseQuery, array $array = null, int $itemPerPage = 20, int $page = 1)
	{
		$offset = ($page - 1) * $itemPerPage;
		$baseQuery .= " LIMIT $offset, $itemPerPage";
		return $this->arraySQL($baseQuery, $array);
	}

	public function arraySQL(string $baseQuery, array $array = null)
	{
		$res = null;
		if ($array == null) {
			$res = $this->db->query($baseQuery);
			if (!$res) {
				throw new Exception(self::ERROR_MYSQLI_QUERY_MSG, self::ERROR_CODE);
				
			}
		} else {
			$stmt = $this->_prepare_stmt($baseQuery, $array);
			$stmt->execute();
			$res = $stmt->get_result();
		}
		$arr = array();
		while ($row = $res->fetch_assoc()) {
			$arr[] = $row;
		}
		$res->free_result();
		return $arr;
	}

	private function _prepare_stmt(string $baseQuery, array $array = null)
	{
		$count = substr_count($baseQuery, "?");
		if ($count > 0 && $array == null) {
			throw new Exception(self::ERROR_MYSQLI_QUERY_MSG, self::ERROR_CODE);
		}
		$ps = '';
		if ($array != null) {
			if ($count != sizeof($array)) {
				throw new Exception(self::ERROR_MYSQLI_QUERY_MSG, self::ERROR_CODE);
			}
			foreach ($array as $value) {
				$ps .= 's';
				// $array[$key] = $value;
			}
		}
		$stmt = $this->db->prepare($baseQuery);
		if (!$stmt) {
			throw new Exception(self::ERROR_MYSQLI_QUERY_MSG, self::ERROR_CODE);
		}
		if ($array != null) {
			$stmt->bind_param($ps, ...$array);
		}
		return $stmt;
	}

	// todo
	public function real_escape($value)
	{
		return $this->db->real_escape_string($value);
	}
}
