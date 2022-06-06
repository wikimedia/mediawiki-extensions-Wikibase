<?php

namespace Wikibase\Lib\Tests\Store\Sql\Terms\Util;

use InvalidArgumentException;
use Wikimedia\Rdbms\ILoadBalancer;
use Wikimedia\Rdbms\ILoadBalancerForOwner;
use Wikimedia\Rdbms\LBFactory;

/**
 * @license GPL-2.0-or-later
 */
class FakeLBFactory extends LBFactory {

	/** @var ILoadBalancerForOwner */
	private $lb;

	/**
	 * @param array $params should contain 'lb' ILoadBalancerForOwner instance
	 */
	public function __construct( array $params ) {
		// no parent constructor call, we only use the LBFactory class so we don’t have to
		// override every ILBFactory method – they’ll just crash if someone tries to use them
		$this->lb = $params['lb'];
	}

	public function newMainLB( $domain = false ): ILoadBalancerForOwner {
		if ( $domain === false || $domain === $this->getLocalDomainID() ) {
			return $this->lb;
		} else {
			throw new InvalidArgumentException( 'only local domain supported' );
		}
	}

	public function getMainLB( $domain = false ): ILoadBalancer {
		return $this->newMainLB( $domain );
	}

	public function waitForReplication( array $ops = [] ) {
		// no-op
	}

	public function newExternalLB( $cluster ): ILoadBalancerForOwner {
		throw new InvalidArgumentException( 'no external cluster supported' );
	}

	public function getExternalLB( $cluster ): ILoadBalancer {
		return $this->newExternalLB( $cluster );
	}

	public function forEachLB( $callback, array $params = [] ) {
		( $callback )( $this->lb, ...$params );
	}

	public function getAllMainLBs(): array {
		return [ $this->lb ];
	}

	public function getAllExternalLBs(): array {
		return [];
	}

	public function getLocalDomainID() {
		return $this->lb->getLocalDomainID();
	}

	public function __destruct() {
		// no-op
	}

	protected function getLBsForOwner() {
		yield $this->lb;
	}
}
