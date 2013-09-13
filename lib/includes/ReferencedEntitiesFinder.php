<?php

namespace Wikibase;

use Wikibase\DataModel\Entity\EntityIdValue;
use DataValues\DataValue;

/**
 * Finds linked entities given a list of entities or a list of claims.
 *
 * @since 0.4
 *
 * @file
 * @ingroup WikibaseLib
 *
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 * @author Daniel Werner < daniel.a.r.werner@gmail.com >
 * @author Katie Filbert
 * @author Daniel Kinzler
 */
class ReferencedEntitiesFinder {

	/**
	 * Finds linked entities within a set of snaks.
	 *
	 * @param Snak[] $snaks
	 *
	 * @return EntityId[]
	 */
	public function findSnakLinks( array $snaks ) {
		$foundEntities = array();

		foreach ( $snaks as $snak ) {
			// all of the Snak's properties are referenced entities, add them:
			$foundEntities[] = $snak->getPropertyId();

			// PropertyValueSnaks might have a value referencing an Entity, find those as well:
			if( $snak instanceof PropertyValueSnak ) {
				$snakValue = $snak->getDataValue();

				if( $snakValue === null ) {
					// shouldn't ever run into this, but make sure!
					continue;
				}

				$entitiesInSnakDataValue = $this->findDataValueLinks( $snakValue );
				$foundEntities = array_merge( $foundEntities, $entitiesInSnakDataValue );
			}
		}

		return array_unique( $foundEntities );
	}

	/**
	 * Finds linked entities within a given data value.
	 *
	 * @since 0.5
	 *
	 * @param DataValue $dataValue
	 * @return EntityId[]
	 */
	public function findDataValueLinks( DataValue $dataValue ) {
		switch( $dataValue->getType() ) {
			case 'wikibase-entityid':
				if( $dataValue instanceof EntityIdValue ) {
					return array(
						$dataValue->getEntityId() );
				}
				break;

			// TODO: we might want to allow extensions to add handling for their custom
			//  data value types here. Either use a hook or a proper registration for that.
		}
		return array();
	}
}
