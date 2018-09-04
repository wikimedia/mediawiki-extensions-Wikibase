<?php

namespace Wikibase\Lib\Store;

use InvalidArgumentException;
use Psr\SimpleCache\CacheInterface;
use Wikibase\DataModel\Entity\EntityDocument;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Services\Entity\EntityPrefetcher;
use Wikibase\DataModel\Services\Lookup\EntityLookup;
use Wikibase\DataModel\Services\Lookup\EntityLookupException;
use Wikibase\DataModel\Services\Lookup\EntityRetrievingTermLookup;
use Wikibase\DataModel\Services\Lookup\LabelDescriptionLookup;
use Wikibase\LanguageFallbackChain;
use Wikibase\LanguageWithConversion;
use Wikimedia\Assert\Assert;

/**
 * @license GPL-2.0-or-later
 */
class CacheBasedEntityInfoBuilder implements EntityInfoBuilder {

	/**
	 * @var EntityPrefetcher
	 */
	private $entityPrefetcher;

	/**
	 * @var EntityRevisionLookup
	 */
	private $entityRevisionLookup;

	/**
	 * @var CacheInterface
	 */
	private $cache;

	/**
	 * @var int
	 */
	private $cacheTtlInSeconds;

	public function __construct() {
		$wikibaseRepo = \Wikibase\Repo\WikibaseRepo::getDefaultInstance();
		$this->entityPrefetcher = $wikibaseRepo->getStore()->getEntityPrefetcher();
		$this->entityRevisionLookup = $wikibaseRepo->getEntityRevisionLookup();
		$this->cache = $wikibaseRepo->getFormatterCache();

		$settings = $wikibaseRepo->getSettings();
		$this->cacheTtlInSeconds = $settings->getSetting( 'sharedCacheDuration' );
	}

	/**
	 * @param EntityId[] $entityIds
	 * @param LanguageFallbackChain $languageFallbackChain
	 *
	 * @return EntityInfo
	 */
	public function collectEntityInfo( array $entityIds, LanguageFallbackChain $languageFallbackChain ) {
		$this->entityPrefetcher->prefetch( $entityIds );

		$entityRevisions = $this->getEntityRevisionsFromEntityIds( $entityIds, true );

		$entityRetrievingTermLookup = new EntityRetrievingTermLookup(
			$this->createEntityLookup( $entityRevisions )
		);

		$nonCachingLookup = new LanguageFallbackLabelDescriptionLookup(
			$entityRetrievingTermLookup,
			$languageFallbackChain
		);

		$labelDescriptionLookup = new CachingFallbackLabelDescriptionLookup(
			$this->cache,
			$this->entityRevisionLookup,
			$nonCachingLookup,
			$languageFallbackChain,
			$this->cacheTtlInSeconds
		);

		$result = [];
		foreach ( $entityIds as $entityId ) {
			$result[ $entityId->getSerialization() ] = $this->createInfoForSingleEntity(
				$labelDescriptionLookup,
				$entityId,
				$languageFallbackChain
			);
		}

		return new EntityInfo( $result );
	}

	/**
	 * @param EntityId[] $entityIds
	 * @param bool $resolveRedirects
	 *
	 * @return EntityRevision[]
	 */
	private function getEntityRevisionsFromEntityIds(
		array $entityIds,
		$resolveRedirects = false
	) {
		$revisionArray = [];

		$this->entityPrefetcher->prefetch( $entityIds );

		foreach ( $entityIds as $entityId ) {
			$sourceEntityId = $entityId->getSerialization();
			$entityRevision = $this->getEntityRevision( $entityId, $resolveRedirects );

			$revisionArray[ $sourceEntityId ] = $entityRevision;
		}

		return $revisionArray;
	}

	/**
	 * @param EntityId $entityId
	 * @param bool $resolveRedirects
	 *
	 * @return null|EntityRevision
	 */
	private function getEntityRevision( EntityId $entityId, $resolveRedirects = false ) {
		$entityRevision = null;

		try {
			$entityRevision = $this->entityRevisionLookup->getEntityRevision( $entityId );
		} catch ( RevisionedUnresolvedRedirectException $ex ) {
			if ( $resolveRedirects ) {
				$entityId = $ex->getRedirectTargetId();
				$entityRevision = $this->getEntityRevision( $entityId, false );
			}
		} catch ( InvalidArgumentException $ex ) {
			// InvalidArgumentException is thrown when the repository $entityId is from other
			// repository than the entityRevisionLookup was configured to read from.
			// Such cases are input errors (e.g. specifying non-existent repository prefix)
			// and should be ignored and treated as non-existing entities.
		}

		return $entityRevision;
	}

	/**
	 * @param EntityRevision[]|null[] $entityRevisions
	 * @return EntityLookup
	 */
	private function createEntityLookup( array $entityRevisions ): EntityLookup {

		return new class ( $entityRevisions ) implements EntityLookup {

			/**
			 * @var null[]|EntityRevision[]
			 */
			private $entityRevisions;

			/**
			 * @param EntityRevision[]|null[] $entityRevisions
			 */
			public function __construct( $entityRevisions ) {
				$this->entityRevisions = $entityRevisions;
			}

			/**
			 * @param EntityId $entityId
			 * @return null|EntityDocument
			 */
			public function getEntity( EntityId $entityId ) {
				if ( !$this->hasEntity( $entityId ) ) {
					return null;
				}

				return $this->entityRevisions[ $entityId->getSerialization() ]->getEntity();
			}

			/**
			 * @param EntityId $entityId
			 * @return bool
			 */
			public function hasEntity( EntityId $entityId ) {
				return array_key_exists( $entityId->getSerialization(), $this->entityRevisions ) &&
					$this->entityRevisions[ $entityId->getSerialization() ] !== null;
			}
		};
	}

	/**
	 * @param $labelDescriptionLookup
	 * @param $entityId
	 * @param $languageFallbackChain
	 * @return array
	 */
	private function createInfoForSingleEntity(
		LabelDescriptionLookup $labelDescriptionLookup,
		EntityId $entityId,
		LanguageFallbackChain $languageFallbackChain
	) {
		$labels = [];
		$descriptions = [];
		foreach ( $languageFallbackChain->getFetchLanguageCodes() as $languageCode ) {
			$labelTerm = $labelDescriptionLookup->getLabel( $entityId );
			if ($labelTerm) {
				$labels[ $languageCode ] = [
					'language' => $languageCode,
					'value' => $labelTerm->getText(),
				];
			}

			$descriptionTerm = $labelDescriptionLookup->getDescription( $entityId );
			if ($descriptionTerm) {
				$descriptions[ $languageCode ] = [
					'language' => $languageCode,
					'value' => $descriptionTerm,
				];
			}
		}

		return [
			'labels' => $labels,
			'descriptions' => $descriptions,
		];
	}

}
