<?php

namespace Wikibase\Lib\Store;

use Wikibase\DataModel\Entity\EntityId;

/**
 * @license GPL-2.0-or-later
 */
class TypeDispatchingTitleTextLookup implements EntityTitleTextLookup {

	/**
	 * @var array
	 */
	private $callbacks;

	/**
	 * @var EntityTitleTextLookup
	 */
	private $defaultLookup;

	/**
	 * @var EntityTitleTextLookup[]
	 */
	private $lookups;

	public function __construct( array $callbacks, EntityTitleTextLookup $defaultLookup ) {
		$this->callbacks = $callbacks;
		$this->defaultLookup = $defaultLookup;
	}

	public function getPrefixedText( EntityId $id ): ?string {
		return $this->getLookupForType( $id )->getPrefixedText( $id );
	}

	private function getLookupForType( EntityId $id ): EntityTitleTextLookup {
		$entityType = $id->getEntityType();
		if ( !array_key_exists( $entityType, $this->callbacks ) ) {
			return $this->defaultLookup;
		}

		return $this->lookups[$entityType] ?? $this->createLookup( $entityType );
	}

	private function createLookup( string $entityType ): EntityTitleTextLookup {
		$this->lookups[$entityType] = $this->callbacks[$entityType]();

		return $this->lookups[$entityType];
	}

}
