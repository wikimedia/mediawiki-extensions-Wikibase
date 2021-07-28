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
	 * @var ServiceBySourceAndTypeDispatcher
	 */
	private $serviceBySourceAndTypeDispatcher;

	public function __construct(
		EntitySourceLookup $entitySourceLookup,
		ServiceBySourceAndTypeDispatcher $serviceBySourceAndTypeDispatcher
	) {
		$this->entitySourceLookup = $entitySourceLookup;
		$this->serviceBySourceAndTypeDispatcher = $serviceBySourceAndTypeDispatcher;
	}

	/**
	 * @param PropertyId $propertyId
	 *
	 * @return string
	 */
	public function getDataTypeIdForProperty( PropertyId $propertyId ): string {
		$entitySource = $this->entitySourceLookup->getEntitySourceById( $propertyId );

		return $this->serviceBySourceAndTypeDispatcher->getServiceForSourceAndType(
			$entitySource->getSourceName(),
			$propertyId->getEntityType()
		)->getDataTypeIdForProperty( $propertyId );
	}

}
