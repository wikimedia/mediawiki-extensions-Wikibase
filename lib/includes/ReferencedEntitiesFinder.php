<?php

namespace Wikibase;

use Wikibase\DataModel\Entity\EntityIdValue;

/**
 * Finds linked entities given a list of entities or a list of claims.
 *
 * @since 0.4
 *
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 * @author Daniel Werner < daniel.a.r.werner@gmail.com >
 * @author Katie Filbert
 * @author Daniel Kinzler
 * @author Bene* < benestar.wikimedia@gmail.com >
 */
class ReferencedEntitiesFinder {

	/**
	 * Finds linked entities within a set of snaks.
	 *
	 * @since 0.4
	 *
	 * @param Snak[] $snaks
	 *
	 * @return EntityId[] Entity id strings pointing to EntityId objects.
	 */
	public function findSnakLinks( array $snaks ) {
		$entityIds = array();

		foreach ( $snaks as $snak ) {
			$propertyId = $snak->getPropertyId();
			$entityIds[$propertyId->getSerialization()] = $propertyId;

			if ( $snak instanceof PropertyValueSnak ) {
				$dataValue = $snak->getDataValue();

				if ( $dataValue instanceof EntityIdValue ) {
					$entityId = $dataValue->getEntityId();
					$entityIds[$entityId->getSerialization()] = $entityId;
				}
			}
		}

		return $entityIds;
	}

}
