<?php

namespace Wikibase\Lib;

use Doctrine\DBAL\Driver\AbstractMySQLDriver;

class MediaWikiDoctrineDBALDriver extends AbstractMySQLDriver {

	private $conn;

	public function __construct(\mysqli $conn) {
		$this->conn = $conn;
	}

	public function connect(array $params, $username = null, $password = null, array $driverOptions = array()) {
		return new MediaWikiDriverConnection( $this->conn );
	}

	public function getName()
	{
		return 'MW-mysqli';
	}
}
