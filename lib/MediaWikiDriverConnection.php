<?php

namespace Wikibase\Lib;

use Doctrine\DBAL\Driver\Mysqli\MysqliConnection;
use Doctrine\DBAL\Driver\Mysqli\MysqliException;
use Doctrine\DBAL\Driver\Mysqli\MysqliStatement;
use mysqli;
use PDO;

/**
 * This is a modified version of DBALs MysqliConnection that allows construction from
 * an already existing mysqli resource rather than from parameters that are used to
 * create a new one. The only logic changes are the much simplified constructor,
 * all other code is the same as in MysqliConnection.
 */
class MediaWikiDriverConnection extends MysqliConnection {

	private $mysqli;

	public function __construct( mysqli $mysqliConnection ) {
		$this->mysqli = $mysqliConnection;
	}

	public function getWrappedResourceHandle() {
		return $this->mysqli;
	}

	public function getServerVersion() {
		$majorVersion = floor( $this->mysqli->server_version / 10000 );
		$minorVersion = floor( ( $this->mysqli->server_version - $majorVersion * 10000 ) / 100 );
		$patchVersion = floor( $this->mysqli->server_version - $majorVersion * 10000 - $minorVersion * 100 );

		return $majorVersion . '.' . $minorVersion . '.' . $patchVersion;
	}

	public function requiresQueryForServerVersion() {
		return false;
	}

	public function query() {
		$args = func_get_args();
		$sql = $args[0];
		$stmt = $this->prepare( $sql );
		$stmt->execute();

		return $stmt;
	}

	public function prepare( $prepareString ) {
		return new MysqliStatement( $this->mysqli, $prepareString );
	}

	public function quote( $input, $type = PDO::PARAM_STR ) {
		return "'" . $this->mysqli->escape_string( $input ) . "'";
	}

	public function exec( $statement ) {
		if ( false === $this->mysqli->query( $statement ) ) {
			throw new MysqliException( $this->mysqli->error, $this->mysqli->sqlstate, $this->mysqli->errno );
		}

		return $this->mysqli->affected_rows;
	}

	public function lastInsertId( $name = null ) {
		return $this->mysqli->insert_id;
	}

	public function beginTransaction() {
		$this->mysqli->query( 'START TRANSACTION' );

		return true;
	}

	public function commit() {
		return $this->mysqli->commit();
	}

	public function rollBack() {
		return $this->mysqli->rollback();
	}

	public function errorCode() {
		return $this->mysqli->errno;
	}

	public function errorInfo() {
		return $this->mysqli->error;
	}

	public function ping() {
		return $this->mysqli->ping();
	}

}
