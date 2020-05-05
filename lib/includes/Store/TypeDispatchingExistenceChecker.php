<?php

declare( strict_types=1 );

namespace Wikibase\Lib\Store;

use Wikibase\DataModel\Entity\EntityId;

/**
 * @license GPL-2.0-or-later
 */
class TypeDispatchingExistenceChecker implements EntityExistenceChecker {

	/**
	 * @var array
	 */
	private $callbacks;

	/**
	 * @var EntityExistenceChecker
	 */
	private $defaultExistenceChecker;

	/**
	 * @var EntityExistenceChecker[]
	 */
	private $existenceCheckers;

	public function __construct( array $callbacks, EntityExistenceChecker $defaultExistenceChecker ) {
		$this->callbacks = $callbacks;
		$this->defaultExistenceChecker = $defaultExistenceChecker;
	}

	public function isDeleted( EntityId $id ): bool {
		return $this->getExistenceCheckerForType( $id )->isDeleted( $id );
	}

	private function getExistenceCheckerForType( EntityId $id ): EntityExistenceChecker {
		$entityType = $id->getEntityType();
		if ( !array_key_exists( $entityType, $this->callbacks ) ) {
			return $this->defaultExistenceChecker;
		}

		return $this->existenceCheckers[$entityType] ?? $this->createExistenceChecker( $entityType );
	}

	private function createExistenceChecker( string $entityType ): EntityExistenceChecker {
		$this->existenceCheckers[$entityType] = $this->callbacks[$entityType]();

		return $this->existenceCheckers[$entityType];
	}

}
