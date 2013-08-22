<?php

namespace Wikibase;

use Wikibase\DataModel\Entity\EntityIdValue;

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
 * @author Daniel Werner < daniel.werner@wikimedia.de >
 * @author Daniel Kinzler
 */
class ReferencedEntitiesFinder {

	/**
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

				switch( $snakValue->getType() ) {
					case 'wikibase-entityid':
						if( $snakValue instanceof EntityIdValue ) {
							$foundEntities[] = $snakValue->getEntityId();
						}
						break;
					// TODO: handle values in other formats. E.g. in an earlier version the
					//  'wikibase-entity' data type has been using 'string' values to store its ID.

					// TODO: we might want to allow extensions to add handling for their custom
					//  data value types here. Either use a hook or a proper registration for that.
				}
			}
		}

		return array_unique( $foundEntities );
	}

}
