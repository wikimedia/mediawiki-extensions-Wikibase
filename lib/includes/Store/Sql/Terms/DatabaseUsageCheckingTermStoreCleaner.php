<?php

declare( strict_types=1 );
namespace Wikibase\Lib\Store\Sql\Terms;

use Wikibase\Lib\Rdbms\RepoDomainDb;

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
	 * @var RepoDomainDb
	 */
	private $repoDomainDb;

	public function __construct( RepoDomainDb $repoDomainDb, DatabaseInnerTermStoreCleaner $innerCleaner ) {
		$this->repoDomainDb = $repoDomainDb;
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

		$dbw = $this->repoDomainDb->connections()->getWriteConnection();
		$dbr = $this->repoDomainDb->connections()->getReadConnection();

		$dbw->startAtomic( __METHOD__ );
		$unusedTermInLangIds = $this->findActuallyUnusedTermInLangIds( $termInLangIds, $dbw );
		$this->innerCleaner->cleanTermInLangIds( $dbw, $dbr, $unusedTermInLangIds );
		$dbw->endAtomic( __METHOD__ );
	}
}
