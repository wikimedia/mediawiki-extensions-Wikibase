<?php

namespace Wikibase\DataModel\Entity;

use InvalidArgumentException;

/**
 * A trivial EntityIdParser that only parses the serializations of ItemIds. This is particularly
 * useful in cases where URIs are used to refer to concepts in an external Wikibase repository,
 * e.g. when referencing globes in coordinate values, or units in quantity values.
 *
 * @since 4.4
 *
 * @license GPL-2.0-or-later
 * @author Thiemo Kreuz
 */
class ItemIdParser implements EntityIdParser {

	/**
	 * @param string $idSerialization
	 *
	 * @throws EntityIdParsingException
	 * @return ItemId
	 */
	public function parse( $idSerialization ) {
		try {
			return new ItemId( $idSerialization );
		} catch ( InvalidArgumentException $ex ) {
			throw new EntityIdParsingException( $ex->getMessage(), 0, $ex );
		}
	}

}
