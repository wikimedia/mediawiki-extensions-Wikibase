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

	public function wait(): void {
		$this->lbFactory->waitForReplication( [ 'domain' => $this->domainId ] );
	}

	public function waitForAllAffectedClusters(): void {
		$this->lbFactory->waitForReplication();
	}

}
