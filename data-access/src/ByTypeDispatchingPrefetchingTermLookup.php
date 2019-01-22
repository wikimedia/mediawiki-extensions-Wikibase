<?php

namespace Wikibase\DataAccess;

use Wikibase\DataModel\Entity\EntityId;
use Wikibase\Lib\Store\EntityTermLookupBase;
use Wikibase\Lib\Store\PrefetchingTermLookup;

/**
 * @license GPL-2.0-or-later
 */
class ByTypeDispatchingPrefetchingTermLookup extends EntityTermLookupBase implements PrefetchingTermLookup {

	/**
	 * @var PrefetchingTermLookup[]
	 */
	private $lookups;

	public function __construct( array $lookups ) {
		$this->lookups = $lookups;
	}

	/**
	 * @param EntityId[] $entityIds
	 * @param string[]|null $termTypes
	 * @param string[]|null $languageCodes
	 */
	public function prefetchTerms( array $entityIds, array $termTypes = null, array $languageCodes = null ) {
		$entityIdsGroupedByType = [];
		foreach ( $entityIds as $id ) {
			$entityIdsGroupedByType[$id->getEntityType()][] = $id;
		}

		foreach ( $entityIdsGroupedByType as $type => $ids ) {
			$lookup = $this->getLookupForEntityType( $type );
			if ( $lookup !== null ) {
				$lookup->prefetchTerms( $ids, $termTypes, $languageCodes );
			}
		}
	}

	public function getPrefetchedTerm( EntityId $entityId, $termType, $languageCode ) {
		$lookup = $this->getLookupForEntityType( $entityId->getEntityType() );

		if ( $lookup !== null ) {
			return $lookup->getPrefetchedTerm( $entityId, $termType, $languageCode );
		}

		return null;
	}

	/**
	 * @param string $entityType
	 * @return PrefetchingTermLookup|null
	 */
	private function getLookupForEntityType( $entityType ) {
		if ( array_key_exists( $entityType, $this->lookups ) ) {
			return $this->lookups[$entityType];
		}

		// TODO: throw exception on unhandled entity type?
		return null;
	}

	protected function getTermsOfType( EntityId $entityId, $termType, array $languageCodes ) {
		$this->prefetchTerms( [ $entityId ], [ $termType ], $languageCodes );

		$terms = [];
		foreach ( $languageCodes as $lang ) {
			$terms[$lang] = $this->getPrefetchedTerm( $entityId, $termType, $lang );
		}

		return array_filter( $terms, 'is_string' );
	}

}
