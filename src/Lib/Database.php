<?php

namespace Lib;

if(!defined('NOT_DIRECT_ACCESS'))
	die('its not defined');

use PDO;

class Database extends PDO
{
	/**
	 * @var string
	 */
	private static $contentEncryptionKey = '';

	/**
	 * Database constructor.
	 * @param $host
	 * @param $name
	 * @param $user
	 * @param $password
	 * @param $encryptionKey
	 */
	public function __construct($host, $name, $user, $password, $encryptionKey)
	{
		self::$contentEncryptionKey = $encryptionKey;
		parent::__construct('mysql:host='.$host.';dbname='.$name, $user, $password);
//		 parent::setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
	}

	/**
	 * @return string
	 */
	public static function getEncryptionKey() {
		return self::$contentEncryptionKey;
	}

	/**
	 * @param $sql
	 * @param array $array
	 * @param int $fetchMode
	 * @return array
	 */
	public function select($sql, $array = array(),  $fetchMode = PDO::FETCH_ASSOC)
	{
		$sth = $this->prepare($sql);
		foreach ($array as $key => $value) {
			if(!empty($array[$key]))
			{
				$sth->bindValue($key, $value);
			}

		}
		$sth->execute();
		return $sth->fetchAll($fetchMode);
	}

	/**
	 * @param $table
	 * @param array $data
	 * @return int
	 */
	public function insert($table, array $data)
	{
		ksort($data);
		$fieldNames = implode('`, `', array_keys($data));
		$fieldValues = ':' . implode(', :', array_keys($data));
		$query="INSERT INTO $table (`$fieldNames`) VALUES ($fieldValues)";
		$sth = $this->prepare($query);
		foreach ($data as $key => $value) {
			$sth->bindValue(":$key", $value);
		}
		if($sth->execute())
		{
			return 1;
		}
		else
		{
			return 0;
		}

	}

	/**
	 * @param $query
	 * @return int
	 */
	public function ExecuteOther($query)
	{
		return $this->exec($query);
	}

	/**
	 * @param $table
	 * @param array $data
	 * @param array $where
	 * @param int $limit
	 * @return int
	 */
	public function update($table, array $data, array $where,$limit=0)
	{
		ksort($data);

		$fieldDetails = NULL;
		foreach($data as $key=> $value) {
			$fieldDetails .= "`$key`=:$key,";
		}
		$fieldDetails = rtrim($fieldDetails, ',');

		$where_details = NULL;
		foreach($where as $key=> $value)
		{
			$where_details .= " `$key`=:$key and";
		}
		$where_details = trim($where_details, 'and');
		$query="UPDATE $table SET $fieldDetails WHERE $where_details ";
		$sth = $this->prepare($query.($limit==0?"":" limit $limit"));
		foreach ($data as $key => $value)
		{
			$sth->bindValue(":$key", $value);
		}
		foreach ($where as $key => $value)
		{
			$sth->bindValue(":$key", $value);
		}
		if($sth->execute())
		{
			return 1;
		}
		else
		{
			return 0;
		}
	}

	/**
	 * @param $table
	 * @param $where
	 * @param int $limit
	 * @return int
	 */
	public function delete($table,$where,$limit=0)
	{
		ksort($where);

		$where_details = NULL;
		foreach($where as $key=> $value)
		{
			$where_details .= " `$key`=:$key and";
		}
		$where_details = trim($where_details, 'and');
		$query="DELETE FROM  $table WHERE $where_details ";
		$sth = $this->prepare($query.($limit==0?"":" limit $limit"));
		foreach ($where as $key => $value)
		{
			$sth->bindValue(":$key", $value);
		}
		if($sth->execute())
		{
			return 1;
		}
		else
		{
			return 0;
		}
	}

}
