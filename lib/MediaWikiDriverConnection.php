<?php

namespace Wikibase\Lib;

use Doctrine\DBAL\Driver\Connection;
use Doctrine\DBAL\Driver\Mysqli\MysqliConnection;
use Doctrine\DBAL\Driver\Mysqli\MysqliException;
use Doctrine\DBAL\Driver\Mysqli\MysqliStatement;

class MediaWikiDriverConnection extends MysqliConnection {

	/**
	 * Name of the option to set connection flags
	 */
	const OPTION_FLAGS = 'flags';

	/**
	 * @var \mysqli
	 */
	private $_conn;

	/**
	 * @param array  $params
	 * @param string $username
	 * @param string $password
	 * @param array  $driverOptions
	 *
	 * @throws \Doctrine\DBAL\Driver\Mysqli\MysqliException
	 */
	public function __construct(\mysqli $conn)
	{
		$this->_conn = $conn;
	}

	/**
	 * Retrieves mysqli native resource handle.
	 *
	 * Could be used if part of your application is not using DBAL.
	 *
	 * @return \mysqli
	 */
	public function getWrappedResourceHandle()
	{
		return $this->_conn;
	}

	/**
	 * {@inheritdoc}
	 */
	public function getServerVersion()
	{
		$majorVersion = floor($this->_conn->server_version / 10000);
		$minorVersion = floor(($this->_conn->server_version - $majorVersion * 10000) / 100);
		$patchVersion = floor($this->_conn->server_version - $majorVersion * 10000 - $minorVersion * 100);

		return $majorVersion . '.' . $minorVersion . '.' . $patchVersion;
	}

	/**
	 * {@inheritdoc}
	 */
	public function requiresQueryForServerVersion()
	{
		return false;
	}

	/**
	 * {@inheritdoc}
	 */
	public function prepare($prepareString)
	{
		return new MysqliStatement($this->_conn, $prepareString);
	}

	/**
	 * {@inheritdoc}
	 */
	public function query()
	{
		$args = func_get_args();
		$sql = $args[0];
		$stmt = $this->prepare($sql);
		$stmt->execute();

		return $stmt;
	}

	/**
	 * {@inheritdoc}
	 */
	public function quote($input, $type=\PDO::PARAM_STR)
	{
		return "'". $this->_conn->escape_string($input) ."'";
	}

	/**
	 * {@inheritdoc}
	 */
	public function exec($statement)
	{
		if (false === $this->_conn->query($statement)) {
			throw new MysqliException($this->_conn->error, $this->_conn->sqlstate, $this->_conn->errno);
		}

		return $this->_conn->affected_rows;
	}

	/**
	 * {@inheritdoc}
	 */
	public function lastInsertId($name = null)
	{
		return $this->_conn->insert_id;
	}

	/**
	 * {@inheritdoc}
	 */
	public function beginTransaction()
	{
		$this->_conn->query('START TRANSACTION');

		return true;
	}

	/**
	 * {@inheritdoc}
	 */
	public function commit()
	{
		return $this->_conn->commit();
	}

	/**
	 * {@inheritdoc}non-PHPdoc)
	 */
	public function rollBack()
	{
		return $this->_conn->rollback();
	}

	/**
	 * {@inheritdoc}
	 */
	public function errorCode()
	{
		return $this->_conn->errno;
	}

	/**
	 * {@inheritdoc}
	 */
	public function errorInfo()
	{
		return $this->_conn->error;
	}

	/**
	 * Pings the server and re-connects when `mysqli.reconnect = 1`
	 *
	 * @return bool
	 */
	public function ping()
	{
		return $this->_conn->ping();
	}

}
