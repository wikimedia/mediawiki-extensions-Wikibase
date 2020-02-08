<?php

namespace Wikibase\Lib\Store\Sql\Terms;

use InvalidArgumentException;
use JobQueueGroup;
use MediaWiki\MediaWikiServices;
use Wikibase\DataAccess\EntitySource;
use Wikibase\DataModel\Entity\Property;
use Wikibase\DataModel\Entity\PropertyId;
use Wikibase\DataModel\Services\Term\PropertyTermStoreWriter;
use Wikibase\DataModel\Term\Fingerprint;
use Wikibase\StringNormalizer;
use Wikimedia\Rdbms\IDatabase;
use Wikimedia\Rdbms\ILoadBalancer;

/**
 * PropertyTermStoreWriter implementation for the 2019 SQL based secondary property term storage
 *
 * @see @ref md_docs_storage_terms
 * @license GPL-2.0-or-later
 */
class DatabasePropertyTermStoreWriter implements PropertyTermStoreWriter {

	use FingerprintableEntityTermStoreTrait;

	/** @var ILoadBalancer */
	private $loadBalancer;

	/** @var TermInLangIdsAcquirer */
	private $termInLangIdsAcquirer;

	/** @var TermInLangIdsResolver */
	private $termInLangIdsResolver;

	/** @var StringNormalizer */
	private $stringNormalizer;

	/** @var IDatabase|null */
	private $dbw = null;

	/** @var JobQueueGroup */
	private $jobQueueGroup;

	/** @var EntitySource */
	private $entitySource;

	public function __construct(
		ILoadBalancer $loadBalancer, JobQueueGroup $jobQueueGroup, TermInLangIdsAcquirer $termInLangIdsAcquirer,
		TermInLangIdsResolver $termInLangIdsResolver, StringNormalizer $stringNormalizer, EntitySource $entitySource
	) {
		$this->loadBalancer = $loadBalancer;
		$this->jobQueueGroup = $jobQueueGroup;
		$this->termInLangIdsAcquirer = $termInLangIdsAcquirer;
		$this->termInLangIdsResolver = $termInLangIdsResolver;
		$this->stringNormalizer = $stringNormalizer;
		$this->entitySource = $entitySource;
	}

	private function getDbw(): IDatabase {
		if ( $this->dbw === null ) {
			$this->dbw = $this->loadBalancer->getConnection( ILoadBalancer::DB_MASTER );
		}
		return $this->dbw;
	}

	public function storeTerms( PropertyId $propertyId, Fingerprint $fingerprint ) {
		MediaWikiServices::getInstance()->getStatsdDataFactory()->increment(
			'wikibase.repo.term_store.PropertyTermStore_storeTerms'
		);
		$this->assertCanWritePropertyTerms();

		$termInLangIdsToClean = $this->acquireAndInsertTerms( $propertyId, $fingerprint );
		$this->submitJobToCleanTermStorageRowsIfUnused( $termInLangIdsToClean );
	}

	private function submitJobToCleanTermStorageRowsIfUnused( array $termInLangIdsToClean ): void {
		if ( $termInLangIdsToClean === [] ) {
			return;
		}
		$jobParams = [ CleanTermsIfUnusedJob::TERM_IN_LANG_IDS => $termInLangIdsToClean ];
		$job = CleanTermsIfUnusedJob::getJobSpecificationNoTitle( $jobParams );
		$this->getDbw()->onTransactionCommitOrIdle( function() use ( $job ) {
			$this->jobQueueGroup->push( $job );
		} );
	}

	/**
	 * Acquire term in lang IDs for the given Fingerprint,
	 * store them in wbt_property_terms for the given property ID,
	 * and return term in lang IDs that are no longer referenced
	 * and might now need to be cleaned up.
	 *
	 * @param PropertyId $propertyId
	 * @param Fingerprint $fingerprint
	 *
	 * @return int[] wbpt_term_in_lang_ids to that are no longer used by $propertyId
	 * The returned term in lang IDs might still be used in wbt_property_terms rows
	 * for other property IDs or elsewhere, and this should be checked just before cleanup.
	 * However, that may happen in a different transaction than this call.
	 */
	private function acquireAndInsertTerms( PropertyId $propertyId, Fingerprint $fingerprint ): array {
		// Find term entries that already exist for the property
		$oldTermInLangIds = $this->getDbw()->selectFieldValues(
			'wbt_property_terms',
			'wbpt_term_in_lang_id',
			[ 'wbpt_property_id' => $propertyId->getNumericId() ],
			__METHOD__,
			[ 'FOR UPDATE' ]
		);

		$termsArray = $this->termsArrayFromFingerprint( $fingerprint, $this->stringNormalizer );
		$termInLangIdsToClean = [];
		$fname = __METHOD__;

		// Acquire all of the Term in lang Ids needed for the wbt_property_terms table
		$this->termInLangIdsAcquirer->acquireTermInLangIds(
			$termsArray,
			function ( array $newTermInLangIds ) use ( $propertyId, $oldTermInLangIds, &$termInLangIdsToClean, $fname ) {
				$termInLangIdsToInsert = array_diff( $newTermInLangIds, $oldTermInLangIds );
				$termInLangIdsToClean = array_diff( $oldTermInLangIds, $newTermInLangIds );
				$rowsToInsert = [];
				foreach ( $termInLangIdsToInsert as $termInLangIdToInsert ) {
					$rowsToInsert[] = [
						'wbpt_property_id' => $propertyId->getNumericId(),
						'wbpt_term_in_lang_id' => $termInLangIdToInsert,
					];
				}

				$this->getDbw()->insert(
					'wbt_property_terms',
					$rowsToInsert,
					$fname
				);
			}
		);

		if ( $termInLangIdsToClean !== [] ) {
			// Delete entries in wbt_property_terms that are no longer needed
			// Further cleanup should then done by the caller of this method
			$this->getDbw()->delete(
				'wbt_property_terms',
				[
					'wbpt_property_id' => $propertyId->getNumericId(),
					'wbpt_term_in_lang_id' => $termInLangIdsToClean,
				],
				__METHOD__
			);
		}

		return $termInLangIdsToClean;
	}

	public function deleteTerms( PropertyId $propertyId ) {
		MediaWikiServices::getInstance()->getStatsdDataFactory()->increment(
			'wikibase.repo.term_store.PropertyTermStore_deleteTerms'
		);
		$this->assertCanWritePropertyTerms();

		$termInLangIdsToClean = $this->deleteTermsWithoutClean( $propertyId );
		$this->submitJobToCleanTermStorageRowsIfUnused( $termInLangIdsToClean );
	}

	/**
	 * Delete wbt_property_terms rows for the given property ID,
	 * and return term in lang IDs that are no longer referenced
	 * and might now need to be cleaned up.
	 *
	 * (The returned term in lang IDs might still be used in wbt_property_terms rows
	 * for other property IDs, and this should be checked just before cleanup.
	 * However, that may happen in a different transaction than this call.)
	 *
	 * @param PropertyId $propertyId
	 * @return int[]
	 */
	private function deleteTermsWithoutClean( PropertyId $propertyId ): array {
		$res = $this->getDbw()->select(
			'wbt_property_terms',
			[ 'wbpt_id', 'wbpt_term_in_lang_id' ],
			[ 'wbpt_property_id' => $propertyId->getNumericId() ],
			__METHOD__,
			[ 'FOR UPDATE' ]
		);

		$rowIdsToDelete = [];
		$termInLangIdsToCleanUp = [];
		foreach ( $res as $row ) {
			$rowIdsToDelete[] = $row->wbpt_id;
			$termInLangIdsToCleanUp[] = $row->wbpt_term_in_lang_id;
		}

		if ( $rowIdsToDelete !== [] ) {
			$this->getDbw()->delete(
				'wbt_property_terms',
				[ 'wbpt_id' => $rowIdsToDelete ],
				__METHOD__
			);
		}

		return array_values( array_unique( $termInLangIdsToCleanUp ) );
	}

	private function shouldWriteToProperties() : bool {
		return $this->entitySource->getDatabaseName() === false;
	}

	private function assertPropertiesAreLocal() : void {
		if ( !$this->shouldWriteToProperties() ) {
			throw new InvalidArgumentException(
				'This implementation cannot be used with remote entity sources!'
			);
		}
	}

	private function assertCanWritePropertyTerms() {
		$this->assertUsingPropertySource();
		$this->assertPropertiesAreLocal();
	}

	private function assertUsingPropertySource() {
		if ( !in_array( Property::ENTITY_TYPE, $this->entitySource->getEntityTypes() ) ) {
			throw new InvalidArgumentException(
				$this->entitySource->getSourceName() . ' does not provided properties'
			);
		}
	}

}
