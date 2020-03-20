<?php

namespace Wikibase\Lib\Store;

use Wikibase\DataModel\Entity\EntityId;

/**
 * @license GPL-2.0-or-later
 */
class TypeDispatchingUrlLookup implements EntityUrlLookup {

	/**
	 * @var array
	 */
	private $callbacks;

	/**
	 * @var EntityUrlLookup
	 */
	private $defaultLookup;

	/**
	 * @var EntityUrlLookup[]
	 */
	private $lookups;

	public function __construct( array $callbacks, EntityUrlLookup $defaultLookup ) {
		$this->callbacks = $callbacks;
		$this->defaultLookup = $defaultLookup;
	}

	public function getFullUrl( EntityId $id ): string {
		return $this->getLookupForType( $id )->getFullUrl( $id );
	}

	private function getLookupForType( EntityId $id ): EntityUrlLookup {
		$entityType = $id->getEntityType();
		if ( !array_key_exists( $entityType, $this->callbacks ) ) {
			return $this->defaultLookup;
		}

		return $this->lookups[$entityType] ?? $this->createLookup( $entityType );
	}

	private function createLookup( string $entityType ): EntityUrlLookup {
		$this->lookups[$entityType] = $this->callbacks[$entityType]();

		return $this->lookups[$entityType];
	}

}
