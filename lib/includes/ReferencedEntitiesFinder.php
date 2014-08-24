<?php

namespace Wikibase;

use DataValues\DataValue;
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
	 * Finds linked Entities within a set of Snaks.
	 *
	 * @param Snak[] $snaks
	 *
	 * @return EntityId[] Entity ID strings pointing to Entity ID objects.
	 */
	public function findSnakLinks( array $snaks ) {
		$entityIds = array();

		foreach ( $snaks as $snak ) {
			$this->addEntityIds( $entityIds, $snak );
		}

		return $entityIds;
	}

	/**
	 * @param EntityId[] $entityIds
	 * @param Snak $snak
	 */
	private function addEntityIds( array &$entityIds, Snak $snak ) {
		$this->addEntityId( $entityIds, $snak->getPropertyId() );

		if ( $snak instanceof PropertyValueSnak ) {
			$dataValue = $snak->getDataValue();

			if ( $dataValue instanceof EntityIdValue ) {
				$this->addEntityId( $entityIds, $dataValue->getEntityId() );
			}
		}
	}

	/**
	 * @param EntityId[] $entityIds
	 * @param EntityId $entityId
	 */
	private function addEntityId( array &$entityIds, EntityId $entityId ) {
		$entityIds[$entityId->getSerialization()] = $entityId;
	}

}
