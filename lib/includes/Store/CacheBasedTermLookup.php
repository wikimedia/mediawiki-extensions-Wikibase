<?php

namespace Wikibase\Lib\Store;

use InvalidArgumentException;
use Psr\SimpleCache\CacheInterface;
use Wikibase\DataModel\Entity\EntityDocument;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Services\Lookup\TermLookup;
use Wikibase\DataModel\Services\Lookup\TermLookupException;
use Wikibase\TermIndex;
use Wikibase\TermIndexEntry;

class CacheBasedTermLookup implements TermLookup {

	/**
	 * @var CacheInterface
	 */
	private $cache;

	/**
	 * @var EntityRevisionLookup
	 */
	private $revisionLookup;

	public function __construct( CacheInterface $cache, EntityRevisionLookup $revisionLookup ) {
		$this->cache = $cache;
		$this->revisionLookup = $revisionLookup;
	}

	public function getLabel(EntityId $entityId, $languageCode) {
		$revisionId = $this->revisionLookup->getLatestRevisionId( $entityId );
		if ( $revisionId === false ) {
			return null;
		}
		$key = $this->makeKey( $entityId, $revisionId, $languageCode, 'label' );
		return $this->cache->get( $key );
	}

	public function getLabels(EntityId $entityId, array $languageCodes) {
		// TODO: Implement getLabels() method.
	}

	public function getDescription(EntityId $entityId, $languageCode) {
		// TODO: Implement getDescription() method.
	}

	public function getDescriptions(EntityId $entityId, array $languageCodes) {
		// TODO: Implement getDescriptions() method.
	}

	private function makeKey( EntityId $entityId, $revisionId, $languageCode, $labelOrDescription) {
		// As in CachingFallbackLabelDescriptionLookup (If16a711ace4ddf01ddf58bc948c71d4df06127ff)
		// TODO: Should be abstracted? (LabelDescriptionCache?)
		return "{$entityId->getSerialization()}_{$revisionId}_{$languageCode}_{$labelOrDescription}";
	}

	/*
	public function saveTermsOfEntity(EntityDocument $entity) {
		// TODO: this would be some kind of setMultiple probably, but currently only seem
		// to be called from EntityHandler::getEntityModificationUpdates, after saving the entity

		// TODO: Implement saveTermsOfEntity() method.
	}

	public function deleteTermsOfEntity(EntityId $entityId) {
		// TODO: same remark + also used in EntityHandler::getEntityDeletionUpdates

		// TODO: Implement deleteTermsOfEntity() method.
	}

	public function getTermsOfEntity(
		EntityId $entityId,
		array $termTypes = null,
		array $languageCodes = null
	) {
		// Prefetching + EntityTermLookup

		// TODO: Implement getTermsOfEntity() method.
	}

	public function getTermsOfEntities(
		array $entityIds,
		array $termTypes = null,
		array $languageCodes = null
	) {
		// Prefetching + client APIs
		// TODO: Implement getTermsOfEntities() method.
	}

	public function getMatchingTerms(
		array $criteria,
		$termType = null,
		$entityType = null,
		array $options = []
	) {
		// Used for label/term -> ID matching?
		// TODO: Implement getMatchingTerms() method.
	}

	public function getTopMatchingTerms(
		array $criteria,
		$termType = null,
		$entityType = null,
		array $options = []
	) {
		// Used in search (TermIndexSearchInteractor)
		// Should go to own interface?

		// TODO: Implement getTopMatchingTerms() method.
	}

	public function clear() {
		// TODO: is this ever used?
		// TODO: Implement clear() method.
	}*/

}