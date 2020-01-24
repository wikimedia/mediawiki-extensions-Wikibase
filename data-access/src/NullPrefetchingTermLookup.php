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
	 * @todo $termTypes and $languageCodes can not be null with data-model-service ~5.0
	 * Code calling this already always passes array here and the defaults should be removed soon
	 * Leaving the defaults in this method allows us to stay compatible with ~4.0 and ~5.0
	 * for a short period during migration and updates.
	 *
	 * @inheritDoc
	 */
	public function prefetchTerms( array $entityIds, array $termTypes = null, array $languageCodes = null ) {
		if ( $termTypes === null || $languageCodes === null ) {
			throw new \InvalidArgumentException( '$termTypes and $languageCodes can not be null' );
		}
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
