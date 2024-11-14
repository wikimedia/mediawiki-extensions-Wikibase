<?php

namespace Wikibase\DataModel\Services\Lookup;

use MediaWiki\Parser\Parser;
use WeakMap;

/**
 * Factory class for creating RestrictedEntityLookup instances
 * associated with a given Parser object. Each Parser will have its
 * own corresponding RestrictedEntityLookup instance, which enforces
 * an access limit on entity lookups.
 *
 * This factory maintains a separate RestrictedEntityLookup instance
 * for each Parser, tracking entity access counts independently.
 *
 * @license GPL-2.0-or-later
 * @author Sean Leong < sean.leong@wikimedia.de >
 */
class RestrictedEntityLookupFactory {

	private EntityLookup $entityLookup;

	private int $entityAccessLimit;

	/**
	 * @var WeakMap<Parser, EntityLookup>
	 */
	private WeakMap $restrictedEntityLookupMap;

	public function __construct( EntityLookup $entityLookup, int $entityAccessLimit ) {
		$this->entityLookup = $entityLookup;
		$this->entityAccessLimit = $entityAccessLimit;
		$this->restrictedEntityLookupMap = new WeakMap();
	}

	public function getRestrictedEntityLookup( Parser $parser ): RestrictedEntityLookup {
		$this->restrictedEntityLookupMap[$parser] ??= new RestrictedEntityLookup( $this->entityLookup, $this->entityAccessLimit );

		return $this->restrictedEntityLookupMap[$parser];
	}
}
