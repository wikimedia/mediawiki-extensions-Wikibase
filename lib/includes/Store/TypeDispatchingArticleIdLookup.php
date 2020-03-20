<?php

namespace Wikibase\Lib\Store;

use Wikibase\DataModel\Entity\EntityId;

/**
 * @license GPL-2.0-or-later
 */
class TypeDispatchingArticleIdLookup implements EntityArticleIdLookup {

	/**
	 * @var array
	 */
	private $callbacks;

	/**
	 * @var EntityArticleIdLookup
	 */
	private $defaultLookup;

	/**
	 * @var EntityArticleIdLookup[]
	 */
	private $lookups;

	public function __construct( array $callbacks, EntityArticleIdLookup $defaultLookup ) {
		$this->callbacks = $callbacks;
		$this->defaultLookup = $defaultLookup;
	}

	public function getArticleId( EntityId $id ): ?int {
		return $this->getLookupForType( $id )->getArticleId( $id );
	}

	private function getLookupForType( EntityId $id ): EntityArticleIdLookup {
		$entityType = $id->getEntityType();
		if ( !array_key_exists( $entityType, $this->callbacks ) ) {
			return $this->defaultLookup;
		}

		return $this->lookups[$entityType] ?? $this->createLookup( $entityType );
	}

	private function createLookup( string $entityType ): EntityArticleIdLookup {
		$this->lookups[$entityType] = $this->callbacks[$entityType]();

		return $this->lookups[$entityType];
	}

}
