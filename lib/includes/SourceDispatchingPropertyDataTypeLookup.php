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

	/**
	 * @param PropertyId $propertyId
	 *
	 * @return string
	 */
	public function getDataTypeIdForProperty( PropertyId $propertyId ): string {
		$entitySource = $this->entitySourceLookup->getEntitySourceById( $propertyId );

		return $this->lookupsCallbacks[$entitySource->getSourceName()]()
			->getDataTypeIdForProperty( $propertyId );
	}

}
