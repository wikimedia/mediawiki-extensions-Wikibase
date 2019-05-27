<?php

namespace Wikibase\TermStore\MediaWiki\Tests\Util;

use InvalidArgumentException;
use Wikimedia\Rdbms\IDatabase;
use Wikimedia\Rdbms\ILoadBalancer;
use Wikimedia\Rdbms\LoadBalancer;

/**
 * @license GPL-2.0-or-later
 */
class FakeLoadBalancer extends LoadBalancer {

	/** @var IDatabase */
	private $dbr;

	/** @var IDatabase */
	private $dbw;

	/**
	 * @param array $params should contain 'dbr' and optionally 'dbw' IDatabase instances
	 */
	public function __construct( array $params ) {
		// no parent constructor call, we only use the LoadBalancer class so we don’t have to
		// override every ILoadBalancer method – they’ll just crash if someone tries to use them
		$this->dbr = $params['dbr'];
		$this->dbw = $params['dbw'] ?? $this->dbr;
	}

	public function getConnection( $i, $groups = [], $domain = false, $flags = 0 ) {
		switch ( $i ) {
			case ILoadBalancer::DB_REPLICA:
				return $this->dbr;
			case ILoadBalancer::DB_MASTER:
				return $this->dbw;
			default:
				throw new InvalidArgumentException( 'only DB_REPLICA and DB_MASTER supported' );
		}
	}

	public function beginMasterChanges( $fname = __METHOD__ ) {
		// no-op
	}

	public function commitMasterChanges( $fname = __METHOD__ ) {
		// no-op
	}

	public function rollbackMasterChanges( $fname = __METHOD__ ) {
		// no-op
	}

	public function __destruct() {
		// no-op
	}

}
