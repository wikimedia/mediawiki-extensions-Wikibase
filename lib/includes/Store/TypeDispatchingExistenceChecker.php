<?php

declare( strict_types=1 );

namespace Wikibase\Lib\Store;

use Wikibase\DataModel\Entity\EntityId;
use Wikibase\Lib\ServiceByTypeDispatcher;

/**
 * @license GPL-2.0-or-later
 */
class TypeDispatchingExistenceChecker implements EntityExistenceChecker {

	/**
	 * @var ServiceByTypeDispatcher
	 */
	private $serviceDispatcher;

	public function __construct( array $callbacks, EntityExistenceChecker $defaultExistenceChecker ) {
		$this->serviceDispatcher = new ServiceByTypeDispatcher( EntityExistenceChecker::class, $callbacks, $defaultExistenceChecker );
	}

	public function exists( EntityId $id ): bool {
		return $this->serviceDispatcher->getServiceForType( $id->getEntityType() )
			->exists( $id );
	}

	public function existsBatch( array $ids ): array {
		$idsByType = [];
		foreach ( $ids as $id ) {
			$idsByType[$id->getEntityType()][] = $id;
		}

		$ret = [];
		foreach ( $idsByType as $type => $idsForType ) {
			$ret += $this->serviceDispatcher->getServiceForType( $type )
				->existsBatch( $idsForType );
		}
		return $ret;
	}
}
