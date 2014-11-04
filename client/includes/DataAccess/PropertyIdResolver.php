<?php

namespace Wikibase\DataAccess;

use InvalidArgumentException;
use Wikibase\DataModel\Entity\PropertyId;
use Wikibase\Lib\PropertyLabelNotResolvedException;
use Wikibase\Lib\Store\EntityLookup;
use Wikibase\PropertyLabelResolver;

/**
 * Resolves the PropertyId for the input, which might be a property label or prefixed id.
 *
 * @fixme see what code can be shared with Lua handling code.
 *
 * @since 0.5
 *
 * @licence GNU GPL v2+
 * @author Katie Filbert < aude.wiki@gmail.com >
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 * @author Daniel Kinzler
 * @author Liangent < liangent@gmail.com >
 */
class PropertyIdResolver {

	private $entityLookup;

	private $propertyLabelResolver;

	public function __construct(
		EntityLookup $entityLookup,
		PropertyLabelResolver $propertyLabelResolver
	) {
		$this->entityLookup = $entityLookup;
		$this->propertyLabelResolver = $propertyLabelResolver;
	}

	/**
	 * @param string $propertyLabelOrId
	 * @param string $languageCode
	 *
	 * @throws PropertyLabelNotResolvedException
	 * @return PropertyId
	 */
	public function resolvePropertyId( $propertyLabelOrId, $languageCode ) {
		try {
			$propertyId = new PropertyId( $propertyLabelOrId );

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
	 * @return PropertyId
	 * @throws PropertyLabelNotResolvedException
	 */
	private function findPropertyByLabel( $propertyLabel, $languageCode ) {
		$propertyIds = $this->propertyLabelResolver->getPropertyIdsForLabels(
			array( $propertyLabel )
		);

		if ( empty( $propertyIds ) ) {
			throw new PropertyLabelNotResolvedException( $propertyLabel, $languageCode );
		}

		$propertyId = $propertyIds[$propertyLabel];

		return $propertyId;
	}
}
