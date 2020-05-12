<?php

namespace Wikibase\Lib\Store;

use Wikibase\DataModel\Entity\EntityDocument;
use Wikibase\DataModel\Entity\EntityId;

/**
 * TermIndex implementation that does nothing.
 *
 * @deprecated As wb_terms is going away, See https://phabricator.wikimedia.org/T208425
 *
 * This will for example be used in the MediaInfo entity handler which currently gets a real
 * TermIndex implementation despite not needing to use it at all.
 */
class NullTermIndex implements TermIndex {

	/**
	 * @inheritDoc
	 */
	public function saveTermsOfEntity( EntityDocument $entity ) {
		return false;
	}

	/**
	 * @inheritDoc
	 */
	public function deleteTermsOfEntity( EntityId $entityId ) {
		return false;
	}

	/**
	 * @inheritDoc
	 */
	public function getTermsOfEntity( EntityId $entityId, array $termTypes = null, array $languageCodes = null ) {
		return [];
	}

	/**
	 * @inheritDoc
	 */
	public function getTermsOfEntities( array $entityIds, array $termTypes = null, array $languageCodes = null ) {
		return [];
	}

	/**
	 * @inheritDoc
	 */
	public function getMatchingTerms( array $criteria, $termType = null, $entityType = null, array $options = [] ) {
		return [];
	}

	/**
	 * @inheritDoc
	 */
	public function getTopMatchingTerms( array $criteria, $termType = null, $entityType = null, array $options = [] ) {
		return [];
	}

	/**
	 * @inheritDoc
	 */
	public function clear() {
		return false;
	}
}
