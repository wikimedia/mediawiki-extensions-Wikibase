<?php

namespace Wikibase\Lib\Store\Sql\Terms;

use InvalidArgumentException;
use MediaWiki\MediaWikiServices;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Wikibase\DataAccess\DataAccessSettings;
use Wikibase\DataAccess\EntitySource;
use Wikibase\DataModel\Entity\Property;
use Wikibase\DataModel\Entity\PropertyId;
use Wikibase\DataModel\Term\Fingerprint;
use Wikibase\StringNormalizer;
use Wikibase\TermStore\PropertyTermStore;
use Wikimedia\Rdbms\IDatabase;
use Wikimedia\Rdbms\ILoadBalancer;

/**
 * PropertyTermStore implementation for the 2019 SQL based secondary property term storage
 *
 * @see @ref md_docs_storage_terms
 * @license GPL-2.0-or-later
 */
class DatabasePropertyTermStore implements PropertyTermStore {

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

	/** @var EntitySource */
	private $entitySource;

	/** @var DataAccessSettings */
	private $dataAccessSettings;

	public function __construct(
		ILoadBalancer $loadBalancer,
		TermIdsAcquirer $acquirer,
		TermIdsResolver $resolver,
		TermIdsCleaner $cleaner,
		StringNormalizer $stringNormalizer,
		EntitySource $entitySource,
		DataAccessSettings $dataAccessSettings,
		LoggerInterface $logger = null
	) {
		$this->loadBalancer = $loadBalancer;
		$this->acquirer = $acquirer;
		$this->resolver = $resolver;
		$this->cleaner = $cleaner;
		$this->stringNormalizer = $stringNormalizer;
		$this->entitySource = $entitySource;
		$this->dataAccessSettings = $dataAccessSettings;
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

	public function storeTerms( PropertyId $propertyId, Fingerprint $fingerprint ) {
		MediaWikiServices::getInstance()->getStatsdDataFactory()->increment(
			'wikibase.repo.term_store.PropertyTermStore_storeTerms'
		);
		if ( $this->dataAccessSettings->useEntitySourceBasedFederation() ) {
			$this->assertPropertiesAreLocal();
		}
		$this->assertCanHandlePropertyId( $propertyId );

		$termIdsToClean = $this->acquireAndInsertTerms( $propertyId, $fingerprint );
		if ( $termIdsToClean !== [] ) {
			$this->cleanTermsIfUnused( $termIdsToClean );
		}
	}

	/**
	 * Acquire term IDs for the given Fingerprint,
	 * store them in wbt_property_terms for the given property ID,
	 * and return term IDs that are no longer referenced
	 * and might now need to be cleaned up.
	 *
	 * @param PropertyId $propertyId
	 * @param Fingerprint $fingerprint
	 *
	 * @return int[] wbpt_term_in_lang_ids to that are no longer used by $propertyId
	 * The returned term IDs might still be used in wbt_property_terms rows
	 * for other property IDs or elsewhere, and this should be checked just before cleanup.
	 * However, that may happen in a different transaction than this call.
	 */
	private function acquireAndInsertTerms( PropertyId $propertyId, Fingerprint $fingerprint ): array {
		// Find term entries that already exist for the property
		$oldTermIds = $this->getDbw()->selectFieldValues(
			'wbt_property_terms',
			'wbpt_term_in_lang_id',
			[ 'wbpt_property_id' => $propertyId->getNumericId() ],
			__METHOD__,
			[ 'FOR UPDATE' ]
		);

		$termsArray = $this->termsArrayFromFingerprint( $fingerprint, $this->stringNormalizer );
		$termIdsToClean = [];
		$fname = __METHOD__;

		// Acquire all of the Term Ids needed for the wbt_property_terms table
		$this->acquirer->acquireTermIds(
			$termsArray,
			function ( array $newTermIds ) use ( $propertyId, $oldTermIds, &$termIdsToClean, $fname ) {
				$termIdsToInsert = array_diff( $newTermIds, $oldTermIds );
				$termIdsToClean = array_diff( $oldTermIds, $newTermIds );
				$rowsToInsert = [];
				foreach ( $termIdsToInsert as $termIdToInsert ) {
					$rowsToInsert[] = [
						'wbpt_property_id' => $propertyId->getNumericId(),
						'wbpt_term_in_lang_id' => $termIdToInsert,
					];
				}

				$this->getDbw()->insert(
					'wbt_property_terms',
					$rowsToInsert,
					$fname
				);
			}
		);

		if ( $termIdsToClean !== [] ) {
			// Delete entries in wbt_property_terms that are no longer needed
			// Further cleanup should then done by the caller of this method
			$this->getDbw()->delete(
				'wbt_property_terms',
				[
					'wbpt_property_id' => $propertyId->getNumericId(),
					'wbpt_term_in_lang_id' => $termIdsToClean,
				],
				__METHOD__
			);
		}

		return $termIdsToClean;
	}

	public function deleteTerms( PropertyId $propertyId ) {
		MediaWikiServices::getInstance()->getStatsdDataFactory()->increment(
			'wikibase.repo.term_store.PropertyTermStore_deleteTerms'
		);
		if ( $this->dataAccessSettings->useEntitySourceBasedFederation() ) {
			$this->assertPropertiesAreLocal();
		}
		$this->assertCanHandlePropertyId( $propertyId );

		$termIdsToClean = $this->deleteTermsWithoutClean( $propertyId );
		if ( $termIdsToClean !== [] ) {
			$this->cleanTermsIfUnused( $termIdsToClean );
		}
	}

	/**
	 * Delete wbt_property_terms rows for the given property ID,
	 * and return term IDs that are no longer referenced
	 * and might now need to be cleaned up.
	 *
	 * (The returned term IDs might still be used in wbt_property_terms rows
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
		$termIdsToCleanUp = [];
		foreach ( $res as $row ) {
			$rowIdsToDelete[] = $row->wbpt_id;
			$termIdsToCleanUp[] = $row->wbpt_term_in_lang_id;
		}

		if ( $rowIdsToDelete !== [] ) {
			$this->getDbw()->delete(
				'wbt_property_terms',
				[ 'wbpt_id' => $rowIdsToDelete ],
				__METHOD__
			);
		}

		return array_values( array_unique( $termIdsToCleanUp ) );
	}

	/**
	 * Of the given term IDs, delete those that aren’t used by any other items or properties.TermIdsResolver
	 *
	 * @param int[] $termIds (wbtl_id)
	 */
	private function cleanTermsIfUnused( array $termIds ) {
		$this->cleaner->cleanTermIds(
			$this->findActuallyUnusedTermIds( $termIds, $this->getDbw() )
		);
	}

	public function getTerms( PropertyId $propertyId ): Fingerprint {
		MediaWikiServices::getInstance()->getStatsdDataFactory()->increment(
			'wikibase.repo.term_store.PropertyTermStore_getTerms'
		);
		$this->assertCanHandlePropertyId( $propertyId );

		$termIds = $this->getDbr()->selectFieldValues(
			'wbt_property_terms',
			'wbpt_term_in_lang_id',
			[ 'wbpt_property_id' => $propertyId->getNumericId() ],
			__METHOD__
		);

		return $this->resolveTermIdsResultToFingerprint(
			$this->resolver->resolveTermIds( $termIds )
		);
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

	private function assertCanHandlePropertyId( PropertyId $id ) {
		if ( $this->dataAccessSettings->useEntitySourceBasedFederation() ) {
			$this->assertUsingPropertySource();
			return;
		}

		$this->disallowForeignEntityId( $id );
	}

	private function disallowForeignEntityId( PropertyId $id ) {
		if ( $id->isForeign() ) {
			throw new InvalidArgumentException(
				'This implementation cannot be used with foreign IDs!'
			);
		}
	}

	private function assertUsingPropertySource() {
		if ( !in_array( Property::ENTITY_TYPE, $this->entitySource->getEntityTypes() ) ) {
			throw new InvalidArgumentException(
				$this->entitySource->getSourceName() . ' does not provided properties'
			);
		}
	}

}
