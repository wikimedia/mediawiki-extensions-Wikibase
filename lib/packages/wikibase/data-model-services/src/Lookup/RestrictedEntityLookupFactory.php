<?php

namespace Wikibase\DataModel\Services\Lookup;

use MediaWiki\Parser\Parser;

/**
 * Factory class for creating RestrictedEntityLookup instances
 * associated with a given Parser object. Each Parser will have its
 * own corresponding RestrictedEntityLookup instance, which enforces
 * an access limit on entity lookups.
 *
 * This factory maintains a separate RestrictedEntityLookup instance
 * for each Parser, tracking entity access counts independently.
 *
 * Note: WeakMap should be used once we drop PHP 7.4 support. An
 * array implementation is used as of now.
 *
 * @license GPL-2.0-or-later
 * @author Sean Leong < sean.leong@wikimedia.de >
 */
class RestrictedEntityLookupFactory {

	private EntityLookup $entityLookup;

	private int $entityAccessLimit;

	/**
	 * @var array<string, EntityLookup>
	 */
	private array $restrictedEntityLookupArray = [];

	/**
	 * @param EntityLookup $entityLookup
	 * @param int $entityAccessLimit
	 */
	public function __construct( EntityLookup $entityLookup, int $entityAccessLimit ) {
		$this->entityLookup = $entityLookup;
		$this->entityAccessLimit = $entityAccessLimit;
	}

	public function getRestrictedEntityLookup( Parser $parser ): RestrictedEntityLookup {
		$id = spl_object_hash( $parser );
		if ( !isset( $this->restrictedEntityLookupArray[$id] ) ) {
			$this->restrictedEntityLookupArray[ $id ] = new RestrictedEntityLookup( $this->entityLookup, $this->entityAccessLimit );
		}

		return $this->restrictedEntityLookupArray[ $id ];
	}
}
