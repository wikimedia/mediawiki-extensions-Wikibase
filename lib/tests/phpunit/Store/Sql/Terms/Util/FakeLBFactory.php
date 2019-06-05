<?php

namespace Wikibase\TermStore\MediaWiki\Tests\Util;

use InvalidArgumentException;
use Wikimedia\Rdbms\IDatabase;
use Wikimedia\Rdbms\ILoadBalancer;
use Wikimedia\Rdbms\LBFactory;
use Wikimedia\Rdbms\LoadBalancer;

/**
 * @license GPL-2.0-or-later
 */
class FakeLBFactory extends LBFactory {

	/** @var ILoadBalancer */
	private $lb;

	/**
	 * @param array $params should contain 'lb' ILoadBalancer instance
	 */
	public function __construct( array $params ) {
		// no parent constructor call, we only use the LBFactory class so we don’t have to
		// override every ILBFactory method – they’ll just crash if someone tries to use them
		$this->lb = $params['lb'];
	}

	public function newMainLB( $domain = false ) {
		if ( $domain === false ) {
			return $this->lb;
		} else {
			throw new InvalidArgumentException( 'only local domain supported' );
		}
	}

	public function getMainLB( $domain = false ) {
		return $this->newMainLB( $domain );
	}

	public function newExternalLB( $cluster ) {
		throw new InvalidArgumentException( 'no external cluster supported' );
	}

	public function getExternalLB( $cluster ) {
		return $this->newExternalLB( $cluster );
	}

	public function forEachLB( $callback, array $params = [] ) {
		( $callback )( $this->lb, ...$params );
	}

	public function getAllMainLBs() {
		return [ $this->lb ];
	}

	public function getAllExternalLBs() {
		return [];
	}

	public function __destruct() {
		// no-op
	}
}
