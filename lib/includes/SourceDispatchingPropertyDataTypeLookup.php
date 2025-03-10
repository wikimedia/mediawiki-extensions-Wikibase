<?php

declare( strict_types=1 );

namespace Wikibase\Lib;

use Wikibase\DataAccess\EntitySourceLookup;
use Wikibase\DataModel\Entity\PropertyId;
use Wikibase\DataModel\Services\Lookup\PropertyDataTypeLookup;

/**
 * @license GPL-2.0-or-later
 */
class SourceDispatchingPropertyDataTypeLookup implements PropertyDataTypeLookup {

	/**
	 * @var EntitySourceLookup
	 */
	private $entitySourceLookup;

	/**
	 * @var callable[]
	 */
	private $lookupsCallbacks;

	/** @var PropertyDataTypeLookup[] */
	private array $lookups = [];

	/**
	 * @param EntitySourceLookup $entitySourceLookup
	 * @param callable[] $lookupsCallbacks keyed by source name
	 */
	public function __construct(
		EntitySourceLookup $entitySourceLookup,
		array $lookupsCallbacks
	) {
		$this->entitySourceLookup = $entitySourceLookup;
		$this->lookupsCallbacks = $lookupsCallbacks;
	}

	public function getDataTypeIdForProperty( PropertyId $propertyId ): string {
		$entitySource = $this->entitySourceLookup->getEntitySourceById( $propertyId );
		$sourceName = $entitySource->getSourceName();
		$lookup = ( $this->lookups[$sourceName] ??= $this->lookupsCallbacks[$sourceName]() );

		return $lookup->getDataTypeIdForProperty( $propertyId );
	}

}
