<?php

namespace Wikibase\Api;

use InvalidArgumentException;
use Wikibase\DataModel\Entity\EntityDocument;
use Wikibase\DataModel\Entity\Property;

/**
 * Class to handle properties for the wbeditentites api module.
 *
 * @license GPL-2.0+
 * @author Bene* < benestar.wikimedia@gmail.com >
 */
class PropertyEditEntityHandler implements EditEntityHandler {

	/**
	 * @param array $data
	 *
	 * @return EntityDocument
	 */
	public function createEntityFromData( array $data ) {
		if ( !isset( $data['datatype'] ) ) {
			throw new InvalidArgumentException( 'No datatype given' );
		}

		return Property::newFromType( $data['datatype'] );
	}

	/**
	 * @param EntityDocument $entity
	 *
	 * @throws InvalidArgumentException
	 * @return EntityDocument
	 */
	public function createEmptyEntity( EntityDocument $entity ) {
		if ( !( $entity instanceof Property ) ) {
			throw new InvalidArgumentException( 'This handler can only handle properties.' );
		}

		return new Property( $entity->getId(), null, $entity->getDataTypeId() );
	}
}
