<?php

namespace Wikibase\Lib\Store\Sql\Terms;

use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Term\Fingerprint;
use Wikibase\StringNormalizer;
use Wikibase\TermStore\ItemTermStore;
use Wikimedia\Rdbms\IDatabase;
use Wikimedia\Rdbms\ILoadBalancer;

/**
 * ItemTermStore implementation for the 2019 SQL based secondary item term storage
 *
 * @see @ref md_docs_storage_terms
 * @license GPL-2.0-or-later
 */
class DatabaseItemTermStore implements ItemTermStore {

	use FingerprintableEntityTermStoreTrait;

	/** @var ILoadBalancer */
	private $loadBalancer;

	/** @var TermIdsAcquirer */
	private $acquirer;

	/** @var TermIdsResolver */
	private $resolver;

	/** @var TermIdsCleaner */
	private $cleaner;

	/** @var StringNormalizer */
	private $stringNormalizer;

	/** @var LoggerInterface */
	private $logger;

	/** @var IDatabase|null */
	private $dbr = null;

	/** @var IDatabase|null */
	private $dbw = null;

	public function __construct(
		ILoadBalancer $loadBalancer,
		TermIdsAcquirer $acquirer,
		TermIdsResolver $resolver,
		TermIdsCleaner $cleaner,
		StringNormalizer $stringNormalizer,
		LoggerInterface $logger = null
	) {
		$this->loadBalancer = $loadBalancer;
		$this->acquirer = $acquirer;
		$this->resolver = $resolver;
		$this->cleaner = $cleaner;
		$this->stringNormalizer = $stringNormalizer;
		$this->logger = $logger ?: new NullLogger();
	}

	private function getDbr(): IDatabase {
		if ( $this->dbr === null ) {
			$this->dbr = $this->loadBalancer->getConnection( ILoadBalancer::DB_REPLICA );
		}
		return $this->dbr;
	}

	private function getDbw(): IDatabase {
		if ( $this->dbw === null ) {
			$this->dbw = $this->loadBalancer->getConnection( ILoadBalancer::DB_MASTER );
		}
		return $this->dbw;
	}

	public function storeTerms( ItemId $itemId, Fingerprint $fingerprint ) {
		$this->disallowForeignEntityIds( $itemId );

		$termIdsToClean = $this->acquireAndInsertTerms( $itemId, $fingerprint );
		if ( $termIdsToClean !== [] ) {
			$this->cleanTermsIfUnused( $termIdsToClean );
		}
	}

	/**
	 * Acquire term IDs for the given Fingerprint,
	 * store them in wbt_item_terms for the given item ID,
	 * and return term IDs that are no longer referenced
	 * and might now need to be cleaned up.
	 *
	 * @param ItemId $itemId
	 * @param Fingerprint $fingerprint
	 *
	 * @return int[] wbit_term_in_lang_ids to that are no longer used by $itemId
	 * The returned term IDs might still be used in wbt_item_terms rows
	 * for other item IDs or elsewhere, and this should be checked just before cleanup.
	 * However, that may happen in a different transaction than this call.
	 */
	private function acquireAndInsertTerms( ItemId $itemId, Fingerprint $fingerprint ): array {
		// Find term entries that already exist for the item
		$oldTermIds = $this->getDbw()->selectFieldValues(
			'wbt_item_terms',
			'wbit_term_in_lang_id',
			[ 'wbit_item_id' => $itemId->getNumericId() ],
			__METHOD__,
			[ 'FOR UPDATE' ]
		);

		$termsArray = $this->termsArrayFromFingerprint( $fingerprint, $this->stringNormalizer );
		$termIdsToClean = [];

		// Acquire all of the Term Ids needed for the wbt_item_terms table
		$this->acquirer->acquireTermIds(
			$termsArray,
			function ( array $newTermIds ) use ( $itemId, $oldTermIds, &$termIdsToClean ) {
				$termIdsToInsert = array_diff( $newTermIds, $oldTermIds );
				$termIdsToClean = array_diff( $oldTermIds, $newTermIds );
				$rowsToInsert = [];
				foreach ( $termIdsToInsert as $termIdToInsert ) {
					$rowsToInsert[] = [
						'wbit_item_id' => $itemId->getNumericId(),
						'wbit_term_in_lang_id' => $termIdToInsert,
					];
				}

				$this->getDbw()->insert(
					'wbt_item_terms',
					$rowsToInsert,
					__METHOD__
				);
			}
		);

		if ( $termIdsToClean !== [] ) {
			// Delete entries in wbt_item_terms that are no longer needed
			// Further cleanup should then done by the caller of this method
			$this->getDbw()->delete(
				'wbt_item_terms',
				[
					'wbit_item_id' => $itemId->getNumericId(),
					'wbit_term_in_lang_id' => $termIdsToClean,
				],
				__METHOD__
			);
		}

		return $termIdsToClean;
	}

	public function deleteTerms( ItemId $itemId ) {
		$this->disallowForeignEntityIds( $itemId );

		$termIdsToClean = $this->deleteTermsWithoutClean( $itemId );
		if ( $termIdsToClean !== [] ) {
			$this->cleanTermsIfUnused( $termIdsToClean );
		}
	}

	/**
	 * Delete wbt_item_terms rows for the given item ID,
	 * and return term IDs that are no longer referenced
	 * and might now need to be cleaned up.
	 *
	 * (The returned term IDs might still be used in wbt_item_terms rows
	 * for other item IDs or elsewhere, and this should be checked just before cleanup.
	 * However, that may happen in a different transaction than this call.)
	 *
	 * @param ItemId $itemId
	 * @return int[]
	 */
	private function deleteTermsWithoutClean( ItemId $itemId ): array {
		$res = $this->getDbw()->select(
			'wbt_item_terms',
			[ 'wbit_id', 'wbit_term_in_lang_id' ],
			[ 'wbit_item_id' => $itemId->getNumericId() ],
			__METHOD__,
			[ 'FOR UPDATE' ]
		);

		$rowIdsToDelete = [];
		$termIdsToCleanUp = [];
		foreach ( $res as $row ) {
			$rowIdsToDelete[] = $row->wbit_id;
			$termIdsToCleanUp[] = $row->wbit_term_in_lang_id;
		}

		if ( $rowIdsToDelete !== [] ) {
			$this->getDbw()->delete(
				'wbt_item_terms',
				[ 'wbit_id' => $rowIdsToDelete ],
				__METHOD__
			);
		}

		return array_values( array_unique( $termIdsToCleanUp ) );
	}

	/**
	 * Of the given term IDs, delete those that aren’t used by any other items or properties.
	 *
	 * @param int[] $termIds (wbtl_id)
	 */
	private function cleanTermsIfUnused( array $termIds ) {
		$this->cleaner->cleanTermIds(
			$this->findActuallyUnusedTermIds( $termIds, $this->getDbw() )
		);
	}

	public function getTerms( ItemId $itemId ): Fingerprint {
		$this->disallowForeignEntityIds( $itemId );

		$termIds = $this->getDbr()->selectFieldValues(
			'wbt_item_terms',
			'wbit_term_in_lang_id',
			[ 'wbit_item_id' => $itemId->getNumericId() ],
			__METHOD__
		);

		return $this->resolveTermIdsResultToFingerprint(
			$this->resolver->resolveTermIds( $termIds )
		);
	}

}
