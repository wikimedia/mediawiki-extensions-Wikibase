<?php

namespace Wikibase\DataAccess;

use Wikibase\DataModel\Entity\EntityId;

/**
 * PrefetchingTermLookup implementation that does nothing.
 *
 * Intended to be used with some services that require a default PrefetchingTermLookup in
 * situations that a default does not make sense.
 *
 * @author Addshore
 *
 * @license GPL-2.0-or-later
 */
class NullPrefetchingTermLookup implements PrefetchingTermLookup {

	/**
	 * @inheritDoc
	 */
	public function prefetchTerms( array $entityIds, array $termTypes, array $languageCodes ) {
	}

	/**
	 * @inheritDoc
	 */
	public function getPrefetchedTerm( EntityId $entityId, $termType, $languageCode ) {
		return false;
	}

	/**
	 * @inheritDoc
	 */
	public function getLabel( EntityId $entityId, $languageCode ) {
		return null;
	}

	/**
	 * @inheritDoc
	 */
	public function getLabels( EntityId $entityId, array $languageCodes ) {
		return [];
	}

	/**
	 * @inheritDoc
	 */
	public function getDescription( EntityId $entityId, $languageCode ) {
		return null;
	}

	/**
	 * @inheritDoc
	 */
	public function getDescriptions( EntityId $entityId, array $languageCodes ) {
		return [];
	}

	/**
	 * @inheritDoc
	 */
	public function getPrefetchedAliases( EntityId $entityId, $languageCode ) {
		return [];
	}
}
