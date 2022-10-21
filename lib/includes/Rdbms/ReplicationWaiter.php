<?php

declare( strict_types=1 );

namespace Wikibase\Lib\Rdbms;

use Wikimedia\Rdbms\ILBFactory;

/**
 * @author Addshore
 * @license GPL-2.0-or-later
 */
class ReplicationWaiter {

	/**
	 * @var ILBFactory
	 */
	private $lbFactory;

	/**
	 * @var string
	 */
	private $domainId;

	public function __construct(
		ILBFactory $lbFactory,
		string $domainId
	) {
		$this->lbFactory = $lbFactory;
		$this->domainId = $domainId;
	}

	public function wait( ?int $timeout = null ): void {
		$this->lbFactory->waitForReplication( array_filter( [
			'domain' => $this->domainId,
			'timeout' => $timeout,
		] ) );
	}

	public function waitForAllAffectedClusters( ?int $timeout = null ): void {
		$this->lbFactory->waitForReplication( array_filter( [
			'timeout' => $timeout,
		] ) );
	}

	public function getMaxLag(): array {
		return $this->lbFactory->getMainLB( $this->domainId )->getMaxLag();
	}

}
