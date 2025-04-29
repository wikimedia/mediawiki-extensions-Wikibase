<?php

declare( strict_types=1 );
namespace Wikibase\Lib\Store\Sql\Terms;

use MediaWiki\JobQueue\JobQueueGroup;
use Wikibase\DataModel\Entity\Int32EntityId;
use Wikibase\DataModel\Term\Fingerprint;
use Wikibase\Lib\Rdbms\TermsDomainDb;
use Wikibase\Lib\Store\Sql\Terms\Util\StatsMonitoring;
use Wikibase\Lib\StringNormalizer;
use Wikimedia\Rdbms\DBReadOnlyError;
use Wikimedia\Rdbms\IDatabase;

/**
 * Base class for item/property TermStoreWriters.
 *
 * @see @ref docs_storage_terms
 * @license GPL-2.0-or-later
 */
abstract class DatabaseTermStoreWriterBase {

	use NormalizedTermStorageMappingTrait;
	use FingerprintableEntityTermStoreTrait;
	use StatsMonitoring;

	/** @var TermsDomainDb */
	private $termsDb;

	/** @var TermInLangIdsAcquirer */
	private $termInLangIdsAcquirer;

	/** @var TermInLangIdsResolver */
	private $termInLangIdsResolver;

	/** @var StringNormalizer */
	private $stringNormalizer;

	/** @var JobQueueGroup */
	private $jobQueueGroup;

	public function __construct(
		TermsDomainDb $termsDb, JobQueueGroup $jobQueueGroup, TermInLangIdsAcquirer $termInLangIdsAcquirer,
		TermInLangIdsResolver $termInLangIdsResolver, StringNormalizer $stringNormalizer
	) {
		$this->termsDb = $termsDb;
		$this->jobQueueGroup = $jobQueueGroup;
		$this->termInLangIdsAcquirer = $termInLangIdsAcquirer;
		$this->termInLangIdsResolver = $termInLangIdsResolver;
		$this->stringNormalizer = $stringNormalizer;
	}

	private function getDbw(): IDatabase {
		return $this->termsDb->getWriteConnection();
	}

	protected function delete( Int32EntityId $entityId ): void {
		try {
			$termInLangIdsToClean = $this->deleteTermsWithoutClean( $entityId );
			$this->submitJobToCleanTermStorageRowsIfUnused( $termInLangIdsToClean );
		} catch ( DBReadOnlyError $e ) {
			// The terms DB may be different from the repo wiki's main DB, and an external DB's read-only state is not handled as
			// gracefully. There is nothing to do here, though. The secondary terms storage will fix itself eventually.
		}
	}

	protected function store( Int32EntityId $entityId, Fingerprint $fingerprint ): void {
		try {
			$termInLangIdsToClean = $this->acquireAndInsertTerms( $entityId, $fingerprint );
			$this->submitJobToCleanTermStorageRowsIfUnused( $termInLangIdsToClean );
		} catch ( DBReadOnlyError $e ) {
			// The terms DB may be different from the repo wiki's main DB, and an external DB's read-only state is not handled as
			// gracefully. There is nothing to do here, though. The secondary terms storage will fix itself eventually.
		}
	}

	private function submitJobToCleanTermStorageRowsIfUnused( array $termInLangIdsToClean ): void {
		if ( $termInLangIdsToClean === [] ) {
			return;
		}

		$this->getDbw()->onTransactionCommitOrIdle( function() use ( $termInLangIdsToClean ) {
			foreach ( $termInLangIdsToClean as $termInLangId ) {
				$this->jobQueueGroup->push(
					CleanTermsIfUnusedJob::getJobSpecificationNoTitle( [
						CleanTermsIfUnusedJob::TERM_IN_LANG_IDS => [ $termInLangId ],
					] )
				);
			}
		}, __METHOD__ );
	}

	/**
	 * Acquire term in lang IDs for the given Fingerprint,
	 * store them in the table for the given entity ID,
	 * and return term in lang IDs that are no longer referenced
	 * and might now need to be cleaned up.
	 *
	 * Updating the current state is done on a best effort basis.
	 * Because this method is called as a secondary data update, not in the primary transaction,
	 * (nearly) simultaneous edits to the same entity can race on the term store update,
	 * and the final state does not necessarily correspond to the terms of the latest revision.
	 * (In theory, it may even be a mixture of the terms of several revisions, without exactly matching any of them.)
	 * However, any future terms update should fix such a situation
	 * (any out-of-sync state should not survive through later updates).
	 * Additionally, all removed <prefix>_term_in_lang_ids will always be accurately reported:
	 * we will never silently remove a reference to the wbt_term_in_lang table.
	 *
	 * @param Int32EntityId $entityId
	 * @param Fingerprint $fingerprint
	 *
	 * @return int[] <prefix>_term_in_lang_ids that we removed (references to the
	 * wbt_term_in_lang table).
	 * The returned term in lang IDs might still be used in other wbt_<entity>_terms rows,
	 * in case of a race condition potentially even on the same entity. This should be checked
	 * just before cleanup. However, that may happen in a different transaction than this call.
	 */
	private function acquireAndInsertTerms( Int32EntityId $entityId, Fingerprint $fingerprint ): array {
		$entityNumericId = $entityId->getNumericId();

		$dbw = $this->getDbw();
		$queryBuilder = $dbw->newSelectQueryBuilder()
			->select( $this->getMapping()->getTermInLangIdColumn() ) // select term_in_lang_id
			->from( $this->getMapping()->getTableName() )
			->where( [ $this->getMapping()->getEntityIdColumn() => $entityNumericId ] ) // of this entity
			->caller( __METHOD__ );

		// Find term entries that already exist for the entity
		$oldTermInLangIds = ( clone $queryBuilder )->fetchFieldValues();

		$termsArray = $this->termsArrayFromFingerprint( $fingerprint, $this->stringNormalizer );
		$termInLangIdsToClean = [];
		$fname = __METHOD__;

		// Acquire all of the Term in lang Ids needed for the wbt_<entity>_terms table
		$this->termInLangIdsAcquirer->acquireTermInLangIds(
			$termsArray,
			function ( array $newTermInLangIds ) use (
				$entityId,
				$oldTermInLangIds,
				&$termInLangIdsToClean,
				$fname,
				$dbw,
				$entityNumericId
			) {
				$termInLangIdsToInsert = array_diff( $newTermInLangIds, $oldTermInLangIds );
				$termInLangIdsToClean = array_diff( $oldTermInLangIds, $newTermInLangIds );
				if ( $termInLangIdsToInsert === [] ) {
					return;
				}

				$rowsToInsert = [];
				$entityIdColumnName = $this->getMapping()->getEntityIdColumn();
				$termInLangColumnName = $this->getMapping()->getTermInLangIdColumn();
				foreach ( $termInLangIdsToInsert as $termInLangIdToInsert ) {
					$rowsToInsert[] = [
						 $entityIdColumnName => $entityNumericId, // entity id
						 $termInLangColumnName => $termInLangIdToInsert, // term_in_lang_id
					];
				}

				$dbw->onTransactionPreCommitOrIdle( function () use ( $dbw, $rowsToInsert, $fname ) {
					$dbw->newInsertQueryBuilder()
						->insertInto( $this->getMapping()->getTableName() )
						->ignore()
						->rows( $rowsToInsert )
						->caller( $fname )->execute();
				}, $fname );
			}
		);

		if ( $termInLangIdsToClean !== [] ) {
			// Delete entries in the table that are no longer needed
			// Further cleanup should then done by the caller of this method
			$dbw->newDeleteQueryBuilder()
				->deleteFrom( $this->getMapping()->getTableName() )
				->where( [
					$this->getMapping()->getEntityIdColumn() => $entityNumericId,
					$this->getMapping()->getTermInLangIdColumn() => $termInLangIdsToClean,
				] )
				->caller( __METHOD__ )->execute();
		}

		return $termInLangIdsToClean;
	}

	/**
	 * Delete rows for the given Int32EntityId,
	 * and return term in lang IDs that are no longer referenced
	 * and might now need to be cleaned up.
	 *
	 * (The returned term in lang IDs might still be used in wbt_<entity>_terms rows
	 * for other entity IDs, and this should be checked just before cleanup.
	 * However, that may happen in a different transaction than this call.)
	 *
	 * @param Int32EntityId $entityId
	 * @return int[]
	 */
	private function deleteTermsWithoutClean( Int32EntityId $entityId ): array {
		$dbw = $this->getDbw();
		$res = $dbw->newSelectQueryBuilder()
			->select( [
				'id' => $this->getMapping()->getRowIdColumn(),
				'term_in_lang_id' => $this->getMapping()->getTermInLangIdColumn(),
			] )
			->forUpdate()
			->from( $this->getMapping()->getTableName() )
			->where( [ $this->getMapping()->getEntityIdColumn() => $entityId->getNumericId() ] )
			->caller( __METHOD__ )->fetchResultSet();

		$rowIdsToDelete = [];
		$termInLangIdsToCleanUp = [];
		foreach ( $res as $row ) {
			$rowIdsToDelete[] = $row->id;
			$termInLangIdsToCleanUp[] = $row->term_in_lang_id;
		}

		if ( $rowIdsToDelete !== [] ) {
			$dbw->newDeleteQueryBuilder()
				->deleteFrom( $this->getMapping()->getTableName() )
				->where( [ $this->getMapping()->getRowIdColumn() => $rowIdsToDelete ] )
				->caller( __METHOD__ )->execute();
		}

		return array_values( array_unique( $termInLangIdsToCleanUp ) );
	}
}
