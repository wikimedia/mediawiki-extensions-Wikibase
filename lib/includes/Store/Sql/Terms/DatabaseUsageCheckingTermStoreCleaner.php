<?php

declare( strict_types=1 );
namespace Wikibase\Lib\Store\Sql\Terms;

use Wikimedia\Rdbms\ILoadBalancer;

/**
 * @license GPL-2.0-or-later
 */
class DatabaseUsageCheckingTermStoreCleaner implements TermStoreCleaner {

	/** @see FindUnusedTermTrait::findActuallyUnusedTermInLangIds */
	use FindUnusedTermTrait;

	/**
	 * @var DatabaseInnerTermStoreCleaner
	 */
	private $innerCleaner;
	/**
	 * @var ILoadBalancer
	 */
	private $loadBalancer;

	public function __construct( ILoadBalancer $loadbalancer, DatabaseInnerTermStoreCleaner $innerCleaner ) {
		$this->loadBalancer = $loadbalancer;
		$this->innerCleaner = $innerCleaner;
	}

	/**
	 * Checks the provided TermInLangIds for existence and usage in either
	 * on both Items and Properties.
	 *
	 * Those that do actually exist and are unused are passed to an inner cleaner.
	 *
	 * These steps are all wrapped in a transaction.
	 *
	 * @param array $termInLangIds
	 */
	public function cleanTermInLangIds( array $termInLangIds ): void {
		$dbw = $this->loadBalancer->getConnection( ILoadBalancer::DB_MASTER );
		$dbr = $this->loadBalancer->getConnection( ILoadBalancer::DB_REPLICA );

		$dbw->startAtomic( __METHOD__ );
		$unusedTermInLangIds = $this->findActuallyUnusedTermInLangIds( $termInLangIds, $dbw );
		$this->innerCleaner->cleanTermInLangIds( $dbw, $dbr, $unusedTermInLangIds );
		$dbw->endAtomic( __METHOD__ );
	}
}
