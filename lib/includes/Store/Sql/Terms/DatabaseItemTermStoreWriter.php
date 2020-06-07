<?php

namespace Wikibase\Lib\Store\Sql\Terms;

use JobQueueGroup;
use MediaWiki\MediaWikiServices;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Services\Term\ItemTermStoreWriter;
use Wikibase\DataModel\Term\Fingerprint;
use Wikibase\Lib\StringNormalizer;
use Wikimedia\Rdbms\IDatabase;
use Wikimedia\Rdbms\ILoadBalancer;

/**
 * ItemTermStoreWriter implementation for the 2019 SQL based secondary item term storage.
 *
 * This can only be used to write to Item term stores on the local database.
 *
 * @see @ref md_docs_storage_terms
 * @license GPL-2.0-or-later
 */
class DatabaseItemTermStoreWriter implements ItemTermStoreWriter {

	use FingerprintableEntityTermStoreTrait;

	/** @var ILoadBalancer */
	private $loadBalancer;

	/** @var TermInLangIdsAcquirer */
	private $termInLangIdsAcquirer;

	/** @var TermInLangIdsResolver */
	private $termInLangIdsResolver;

	/** @var StringNormalizer */
	private $stringNormalizer;

	/** @var JobQueueGroup */
	private $jobQueueGroup;

	public function __construct(
		ILoadBalancer $loadBalancer, JobQueueGroup $jobQueueGroup, TermInLangIdsAcquirer $termInLangIdsAcquirer,
		TermInLangIdsResolver $termInLangIdsResolver, StringNormalizer $stringNormalizer
	) {
		$this->loadBalancer = $loadBalancer;
		$this->jobQueueGroup = $jobQueueGroup;
		$this->termInLangIdsAcquirer = $termInLangIdsAcquirer;
		$this->termInLangIdsResolver = $termInLangIdsResolver;
		$this->stringNormalizer = $stringNormalizer;
	}

	private function getDbw(): IDatabase {
		return $this->loadBalancer->getConnection( ILoadBalancer::DB_MASTER );
	}

	public function storeTerms( ItemId $itemId, Fingerprint $fingerprint ) {
		MediaWikiServices::getInstance()->getStatsdDataFactory()->increment(
			'wikibase.repo.term_store.ItemTermStore_storeTerms'
		);

		$termInLangIdsToClean = $this->acquireAndInsertTerms( $itemId, $fingerprint );
		$this->submitJobToCleanTermStorageRowsIfUnused( $termInLangIdsToClean );
	}

	private function submitJobToCleanTermStorageRowsIfUnused( array $termInLangIdsToClean ): void {
		if ( $termInLangIdsToClean === [] ) {
			return;
		}

		$this->getDbw()->onTransactionCommitOrIdle( function() use ( $termInLangIdsToClean ) {
			foreach ( $termInLangIdsToClean as $termInLangId ) {
				$this->jobQueueGroup->push(
					CleanTermsIfUnusedJob::getJobSpecificationNoTitle(
						[ CleanTermsIfUnusedJob::TERM_IN_LANG_IDS => [ $termInLangId ] ]
					)
				);
			}
		}, __METHOD__ );
	}

	/**
	 * Acquire term in lang IDs for the given Fingerprint,
	 * store them in wbt_item_terms for the given item ID,
	 * and return term in lang IDs that are no longer referenced
	 * and might now need to be cleaned up.
	 *
	 * @param ItemId $itemId
	 * @param Fingerprint $fingerprint
	 *
	 * @return int[] wbit_term_in_lang_ids to that are no longer used by $itemId
	 * The returned term in lang IDs might still be used in wbt_item_terms rows
	 * for other item IDs or elsewhere, and this should be checked just before cleanup.
	 * However, that may happen in a different transaction than this call.
	 */
	private function acquireAndInsertTerms( ItemId $itemId, Fingerprint $fingerprint ): array {
		$dbw = $this->getDbw();

		// Find term entries that already exist for the item
		$oldTermInLangIds = $dbw->selectFieldValues(
			'wbt_item_terms',
			'wbit_term_in_lang_id',
			[ 'wbit_item_id' => $itemId->getNumericId() ],
			__METHOD__
		);

		if ( $oldTermInLangIds !== [] ) {
			// Lock them
			$oldTermInLangIds = $dbw->selectFieldValues(
				'wbt_item_terms',
				'wbit_term_in_lang_id',
				[ 'wbit_item_id' => $itemId->getNumericId() ],
				__METHOD__,
				[ 'FOR UPDATE' ]
			);
		}

		$termsArray = $this->termsArrayFromFingerprint( $fingerprint, $this->stringNormalizer );
		$termInLangIdsToClean = [];
		$fname = __METHOD__;

		// Acquire all of the Term in lang Ids needed for the wbt_item_terms table
		$this->termInLangIdsAcquirer->acquireTermInLangIds(
			$termsArray,
			function ( array $newTermInLangIds ) use ( $itemId, $oldTermInLangIds, &$termInLangIdsToClean, $fname, $dbw ) {
				$termInLangIdsToInsert = array_diff( $newTermInLangIds, $oldTermInLangIds );
				$termInLangIdsToClean = array_diff( $oldTermInLangIds, $newTermInLangIds );
				$rowsToInsert = [];
				foreach ( $termInLangIdsToInsert as $termInLangIdToInsert ) {
					$rowsToInsert[] = [
						'wbit_item_id' => $itemId->getNumericId(),
						'wbit_term_in_lang_id' => $termInLangIdToInsert,
					];
				}

				$dbw->onTransactionPreCommitOrIdle( function () use ( $dbw, $rowsToInsert, $fname ) {
					$dbw->insert(
						'wbt_item_terms',
						$rowsToInsert,
						$fname,
						[ 'IGNORE' ]
					);
				}, $fname );
			}
		);

		if ( $termInLangIdsToClean !== [] ) {
			// Delete entries in wbt_item_terms that are no longer needed
			// Further cleanup should then done by the caller of this method
			$dbw->delete(
				'wbt_item_terms',
				[
					'wbit_item_id' => $itemId->getNumericId(),
					'wbit_term_in_lang_id' => $termInLangIdsToClean,
				],
				__METHOD__
			);
		}

		return $termInLangIdsToClean;
	}

	public function deleteTerms( ItemId $itemId ) {
		MediaWikiServices::getInstance()->getStatsdDataFactory()->increment(
			'wikibase.repo.term_store.ItemTermStore_deleteTerms'
		);

		$termInLangIdsToClean = $this->deleteTermsWithoutClean( $itemId );
		$this->submitJobToCleanTermStorageRowsIfUnused( $termInLangIdsToClean );
	}

	/**
	 * Delete wbt_item_terms rows for the given item ID,
	 * and return term in lang IDs that are no longer referenced
	 * and might now need to be cleaned up.
	 *
	 * (The returned term in lang IDs might still be used in wbt_item_terms rows
	 * for other item IDs or elsewhere, and this should be checked just before cleanup.
	 * However, that may happen in a different transaction than this call.)
	 *
	 * @param ItemId $itemId
	 * @return int[]
	 */
	private function deleteTermsWithoutClean( ItemId $itemId ): array {
		$dbw = $this->getDbw();

		$res = $dbw->select(
			'wbt_item_terms',
			[ 'wbit_id', 'wbit_term_in_lang_id' ],
			[ 'wbit_item_id' => $itemId->getNumericId() ],
			__METHOD__,
			[ 'FOR UPDATE' ]
		);

		$itemTermRowIdsToDelete = [];
		$termInLangIdsToCleanUp = [];
		foreach ( $res as $row ) {
			$itemTermRowIdsToDelete[] = $row->wbit_id;
			$termInLangIdsToCleanUp[] = $row->wbit_term_in_lang_id;
		}

		if ( $itemTermRowIdsToDelete !== [] ) {
			$dbw->delete(
				'wbt_item_terms',
				[ 'wbit_id' => $itemTermRowIdsToDelete ],
				__METHOD__
			);
		}

		return array_values( array_unique( $termInLangIdsToCleanUp ) );
	}

}
