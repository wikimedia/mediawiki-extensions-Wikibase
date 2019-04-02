<?php

namespace Wikibase\Lib;

use Doctrine\DBAL\Driver\AbstractMySQLDriver;
use mysqli;

class MediaWikiDoctrineDBALDriver extends AbstractMySQLDriver {

	private $mysqli;

	public function __construct( mysqli $mysqliConnection ) {
		$this->mysqli = $mysqliConnection;
	}

	public function connect( array $params, $username = null, $password = null, array $driverOptions = [] ) {
		return new MediaWikiDriverConnection( $this->mysqli );
	}

	public function getName() {
		return 'MW-mysqli';
	}
}
