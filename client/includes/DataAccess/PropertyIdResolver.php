<?php

namespace Wikibase\Client\DataAccess;

use InvalidArgumentException;
use Wikibase\Client\PropertyLabelNotResolvedException;
use Wikibase\Client\Usage\UsageAccumulator;
use Wikibase\DataModel\Entity\NumericPropertyId;
use Wikibase\DataModel\Services\Lookup\EntityLookup;
use Wikibase\DataModel\Services\Term\PropertyLabelResolver;

/**
 * Resolves the NumericPropertyId for the input, which might be a property label or prefixed id.
 *
 * @license GPL-2.0-or-later
 */
class PropertyIdResolver {

	/**
	 * @var EntityLookup
	 */
	private $entityLookup;

	/**
	 * @var PropertyLabelResolver
	 */
	private $propertyLabelResolver;

	/**
	 * @var UsageAccumulator
	 */
	private $usageAccumulator;

	public function __construct(
		EntityLookup $entityLookup,
		PropertyLabelResolver $propertyLabelResolver,
		UsageAccumulator $usageAccumulator
	) {
		$this->entityLookup = $entityLookup;
		$this->propertyLabelResolver = $propertyLabelResolver;
		$this->usageAccumulator = $usageAccumulator;
	}

	/**
	 * @param string $propertyLabelOrId
	 * @param string $languageCode
	 *
	 * @throws PropertyLabelNotResolvedException
	 * @return NumericPropertyId
	 */
	public function resolvePropertyId( $propertyLabelOrId, $languageCode ) {
		try {
			$propertyId = new NumericPropertyId( $propertyLabelOrId );

			if ( !$this->entityLookup->hasEntity( $propertyId ) ) {
				throw new PropertyLabelNotResolvedException( $propertyLabelOrId, $languageCode );
			}
		} catch ( InvalidArgumentException $ex ) {
			$propertyId = $this->findPropertyByLabel( $propertyLabelOrId, $languageCode );
		}

		return $propertyId;
	}

	/**
	 * XXX: It might become useful to give the PropertyLabelResolver a hint as to which
	 *      properties may become relevant during the present request, namely the ones
	 *      used by the Item linked to the current page. This could be done with
	 *      something like this:
	 *      $this->propertyLabelResolver->preloadLabelsFor( $propertiesUsedByItem );
	 *
	 * @param string $propertyLabel
	 * @param string $languageCode
	 *
	 * @return NumericPropertyId
	 * @throws PropertyLabelNotResolvedException
	 */
	private function findPropertyByLabel( $propertyLabel, $languageCode ) {
		$propertyIds = $this->propertyLabelResolver->getPropertyIdsForLabels(
			[ $propertyLabel ]
		);

		if ( empty( $propertyIds ) ) {
			throw new PropertyLabelNotResolvedException( $propertyLabel, $languageCode );
		}

		$propertyId = $propertyIds[$propertyLabel];
		$this->usageAccumulator->addLabelUsage( $propertyId, $languageCode );

		return $propertyId;
	}

}
