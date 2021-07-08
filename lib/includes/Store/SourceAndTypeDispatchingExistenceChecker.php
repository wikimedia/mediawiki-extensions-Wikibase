<?php

declare( strict_types=1 );

namespace Wikibase\Lib\Store;

use Wikibase\DataAccess\EntitySourceLookup;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\Lib\ServiceBySourceAndTypeDispatcher;

/**
 * @license GPL-2.0-or-later
 */
class SourceAndTypeDispatchingExistenceChecker implements EntityExistenceChecker {

	/**
	 * @var EntitySourceLookup
	 */
	private $entitySourceLookup;

	/**
	 * @var ServiceBySourceAndTypeDispatcher
	 */
	private $serviceBySourceAndTypeDispatcher;

	public function __construct(
		EntitySourceLookup $entitySourceLookup,
		ServiceBySourceAndTypeDispatcher $serviceBySourceAndTypeDispatcher
	) {
		$this->entitySourceLookup = $entitySourceLookup;
		$this->serviceBySourceAndTypeDispatcher = $serviceBySourceAndTypeDispatcher;
	}

	public function exists( EntityId $id ): bool {
		$entitySource = $this->entitySourceLookup->getEntitySourceById( $id );

		return $this->serviceBySourceAndTypeDispatcher->getServiceForSourceAndType(
			$entitySource->getSourceName(),
			$id->getEntityType()
		)->exists( $id );
	}

	public function existsBatch( array $ids ): array {
		$idsBySourceNameAndType = [];
		foreach ( $ids as $id ) {
			$idsBySourceNameAndType[$this->entitySourceLookup->getEntitySourceById( $id )->getSourceName()][$id->getEntityType()][] = $id;
		}

		$ret = [];
		foreach ( $idsBySourceNameAndType as $sourceName => $idsForSource ) {
			foreach ( $idsForSource as $type => $idsForType ) {
				$ret += $this->serviceBySourceAndTypeDispatcher->getServiceForSourceAndType( $sourceName, $type )
					->existsBatch( $idsForType );
			}
		}
		return $ret;
	}

}
