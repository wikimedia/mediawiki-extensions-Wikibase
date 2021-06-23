<?php

declare( strict_types=1 );

namespace Wikibase\Lib\Store;

use Wikibase\DataAccess\EntitySourceLookup;
use Wikibase\DataModel\Entity\EntityId;

/**
 * @license GPL-2.0-or-later
 */
class TypeDispatchingExistenceChecker implements EntityExistenceChecker {

	/**
	 * @var callable[][]
	 */
	private $callbacks;

	/**
	 * @var EntityExistenceChecker
	 */
	private $defaultExistenceChecker;

	/**
	 * @var EntitySourceLookup
	 */
	private $entitySourceLookup;

	public function __construct(
		array $callbacks,
		EntityExistenceChecker $defaultExistenceChecker,
		EntitySourceLookup $entitySourceLookup
	) {
		$this->callbacks = $callbacks;
		$this->defaultExistenceChecker = $defaultExistenceChecker;
		$this->entitySourceLookup = $entitySourceLookup;
	}

	public function exists( EntityId $id ): bool {
		$entitySource = $this->entitySourceLookup->getEntitySourceById( $id );

		return $this->getServiceForSourceAndType( $entitySource->getSourceName(), $id->getEntityType() )
			->exists( $id );
	}

	public function existsBatch( array $ids ): array {
		$idsBySourceNameAndType = [];
		foreach ( $ids as $id ) {
			$idsBySourceNameAndType[$this->entitySourceLookup->getEntitySourceById( $id )->getSourceName()][$id->getEntityType()][] = $id;
		}

		$ret = [];
		foreach ( $idsBySourceNameAndType as $sourceName => $idsForSource ) {
			foreach ( $idsForSource as $type => $idsForType ) {
				$ret += $this->getServiceForSourceAndType( $sourceName, $type )
					->existsBatch( $idsForType );
			}
		}
		return $ret;
	}

	private function getServiceForSourceAndType( string $sourceName, string $entityType ) {
		return isset( $this->callbacks[$sourceName][$entityType] ) ?
			$this->callbacks[$sourceName][$entityType]() :
			$this->defaultExistenceChecker;
	}

}
