<?php

namespace Wikibase\Lib\Store;

use InvalidArgumentException;
use Psr\SimpleCache\CacheInterface;
use Wikibase\DataModel\Entity\EntityDocument;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\TermIndex;
use Wikibase\TermIndexEntry;

class CacheBasedTermIndex implements TermIndex {

	/**
	 * @var CacheInterface
	 */
	private $cache;

	public function __construct( CacheInterface $cache ) {
		$this->cache = $cache;
	}

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
	}

}