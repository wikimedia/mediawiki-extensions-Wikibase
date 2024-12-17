<?php

declare( strict_types=1 );
namespace Wikibase\Lib\Store\Sql\Terms;

use Wikibase\Lib\Rdbms\TermsDomainDb;
use Wikimedia\Rdbms\DBReadOnlyError;

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
	 * @var TermsDomainDb
	 */
	private $termsDb;

	public function __construct( TermsDomainDb $termsDb, DatabaseInnerTermStoreCleaner $innerCleaner ) {
		$this->termsDb = $termsDb;
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
		$dbw = $this->termsDb->getWriteConnection();
		$dbr = $this->termsDb->getReadConnection();

		try {
			$dbw->startAtomic( __METHOD__ );
			$unusedTermInLangIds = $this->findActuallyUnusedTermInLangIds( $termInLangIds, $dbw );
			$this->innerCleaner->cleanTermInLangIds( $dbw, $dbr, $unusedTermInLangIds );
			$dbw->endAtomic( __METHOD__ );
		} catch ( DBReadOnlyError $error ) {
			// The terms DB may be different from the repo wiki's main DB, and an external DB's read-only state is not handled as
			// gracefully. There is nothing to do here, though. The secondary terms storage will fix itself eventually.
		}
	}
}
