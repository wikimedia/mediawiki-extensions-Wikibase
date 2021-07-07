<?php

declare( strict_types = 1 );

namespace Wikibase\DataAccess;

use Wikibase\DataModel\Entity\EntityId;
use Wikibase\Lib\ServiceBySourceAndTypeDispatcher;
use Wikibase\Lib\Store\EntityTermLookupBase;

/**
 * @license GPL-2.0-or-later
 */
class SourceAndTypeDispatchingPrefetchingTermLookup extends EntityTermLookupBase implements PrefetchingTermLookup {

	private $dispatcher;

	private $sourceLookup;

	public function __construct(
		ServiceBySourceAndTypeDispatcher $dispatcher,
		EntitySourceLookup $sourceLookup
	) {
		$this->dispatcher = $dispatcher;
		$this->sourceLookup = $sourceLookup;
	}

	/**
	 * @param EntityId[] $entityIds
	 * @param string[]|null $termTypes
	 * @param string[]|null $languageCodes
	 */
	public function prefetchTerms( array $entityIds, array $termTypes = null, array $languageCodes = null ) {
		$entityIdsGroupedBySourceAndType = $this->groupEntityIdsBySourceAndType( $entityIds );

		foreach ( $entityIdsGroupedBySourceAndType as $entityIdsGroupedByType ) {
			foreach ( $entityIdsGroupedByType as $entityType => $ids ) {
				$this->getLookupForEntitySourceAndType(
					$this->sourceLookup->getEntitySourceById( $ids[0] ), // $ids[0] is ok because they're already grouped by source
					$entityType
				)->prefetchTerms( $ids, $termTypes, $languageCodes );
			}
		}
	}

	private function groupEntityIdsBySourceAndType( array $entityIds ): array {
		$entityIdsGroupedBySourceAndType = [];

		foreach ( $entityIds as $id ) {
			$entitySource = $this->sourceLookup->getEntitySourceById( $id );
			$entityIdsGroupedBySourceAndType[$entitySource->getSourceName()][$id->getEntityType()][] = $id;
		}

		return $entityIdsGroupedBySourceAndType;
	}

	public function getPrefetchedTerm( EntityId $entityId, $termType, $languageCode ) {
		return $this->getLookupForEntitySourceAndType(
			$this->sourceLookup->getEntitySourceById( $entityId ),
			$entityId->getEntityType()
		)->getPrefetchedTerm( $entityId, $termType, $languageCode );
	}

	private function getLookupForEntitySourceAndType( EntitySource $source, string $type ) {
		return $this->dispatcher->getServiceForSourceAndType( $source->getSourceName(), $type, [ $source ] );
	}

	protected function getTermsOfType( EntityId $entityId, $termType, array $languageCodes ) {
		$this->prefetchTerms( [ $entityId ], [ $termType ], $languageCodes );

		$terms = [];
		foreach ( $languageCodes as $lang ) {
			$terms[$lang] = $this->getPrefetchedTerm( $entityId, $termType, $lang );
		}

		return array_filter( $terms, 'is_string' );
	}

	public function getPrefetchedAliases( EntityId $entityId, $languageCode ) {
		return $this->getLookupForEntitySourceAndType(
			$this->sourceLookup->getEntitySourceById( $entityId ),
			$entityId->getEntityType()
		)->getPrefetchedAliases( $entityId, $languageCode );
	}
}
