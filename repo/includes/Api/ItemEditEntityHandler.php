<?php

namespace Wikibase\Api;

use InvalidArgumentException;
use Wikibase\DataModel\Entity\EntityDocument;
use Wikibase\DataModel\Entity\Item;

/**
 * Class to handle items for the wbeditentites api module.
 *
 * @license GPL-2.0+
 * @author Bene* < benestar.wikimedia@gmail.com >
 */
class ItemEditEntityHandler implements EditEntityHandler{

	/**
	 * @param array $data
	 *
	 * @return EntityDocument
	 */
	public function createEntityFromData( array $data ) {
		return new Item();
	}

	/**
	 * @param EntityDocument $entity
	 *
	 * @throws InvalidArgumentException
	 * @return EntityDocument
	 */
	public function createEmptyEntity( EntityDocument $entity ) {
		if ( !( $entity instanceof Item ) ) {
			throw new InvalidArgumentException( 'This handler can only handle items.' );
		}

		return new Item( $entity->getId() );
	}

}
