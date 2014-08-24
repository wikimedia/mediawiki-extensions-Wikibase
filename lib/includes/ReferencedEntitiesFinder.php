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
	 * @var EntityId[] serialized entity ids pointing to entity id objects
	 */
	private $foundEntityIds;

	/**
	 * Finds linked entities within a set of snaks.
	 *
	 * @param Snak[] $snaks
	 *
	 * @return EntityId[]
	 */
	public function findSnakLinks( array $snaks ) {
		$this->foundEntityIds = array();

		foreach ( $snaks as $snak ) {
			$this->handleSnak( $snak );
		}

		return $this->foundEntityIds;
	}

	private function handleSnak( Snak $snak ) {
		// all of the Snak's properties are referenced entities
		$this->addEntityId( $snak->getPropertyId() );

		// PropertyValueSnaks might have a value referencing an Entity
		if( $snak instanceof PropertyValueSnak ) {
			$this->handleDataValue( $snak->getDataValue() );
		}
	}

	private function handleDataValue( DataValue $dataValue ) {
		if ( $dataValue instanceof EntityIdValue ) {
			$this->addEntityId( $dataValue->getEntityId() );
		}
	}

	private function addEntityId( EntityId $entityId ) {
		$this->foundEntityIds[$entityId->getSerialization()] = $entityId;
	}

}
