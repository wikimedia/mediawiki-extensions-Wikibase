<?php

namespace Wikibase\DataAccess;

use Wikibase\DataModel\Entity\EntityId;
use Wikibase\Lib\Store\EntityTermLookupBase;
use Wikibase\Lib\Store\PrefetchingTermLookup;
use Wikimedia\Assert\Assert;

/**
 * TODO: PrefetchingTermLookup is an odd interface, it describes two different resposbilities really
 *
 * @license GPL-2.0-or-later
 */
class ByTypeDispatchingPrefetchingTermLookup extends EntityTermLookupBase implements PrefetchingTermLookup {

	/**
	 * @var PrefetchingTermLookup[]
	 */
	private $lookups;

	/**
	 * @var PrefetchingTermLookup|null
	 */
	private $defaultLookup;

	/**
	 * @param PrefetchingTermLookup[] $lookups
	 * @param PrefetchingTermLookup|null $defaultLookup
	 */
	public function __construct(
		array $lookups,
		PrefetchingTermLookup $defaultLookup = null
	) {
		Assert::parameterElementType( PrefetchingTermLookup::class, $lookups, '$lookups' );
		Assert::parameterElementType( 'string', array_keys( $lookups ), 'keys of $lookups' );

		$this->lookups = $lookups;
		$this->defaultLookup = $defaultLookup;
	}

	/**
	 * @param EntityId[] $entityIds
	 * @param string[]|null $termTypes
	 * @param string[]|null $languageCodes
	 */
	public function prefetchTerms( array $entityIds, array $termTypes = null, array $languageCodes = null ) {
		$entityIdsGroupedByType = $this->groupEntityIdsByType( $entityIds );

		foreach ( $entityIdsGroupedByType as $type => $ids ) {
			$lookup = $this->getLookupForEntityType( $type );
			if ( $lookup !== null ) {
				$lookup->prefetchTerms( $ids, $termTypes, $languageCodes );
			}
		}
	}

	private function groupEntityIdsByType( array $entityIds ) {
		$entityIdsGroupedByType = [];
		foreach ( $entityIds as $id ) {
			$entityIdsGroupedByType[$id->getEntityType()][] = $id;
		}
		return $entityIdsGroupedByType;
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

		return $this->defaultLookup;
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
