<?php

namespace Wikibase\DataModel\Entity;

/**
 * Interface for objects that can parse strings into EntityIds
 *
 * @since 4.2
 *
 * @licence GNU GPL v2+
 * @author Adam Shorland
 */
interface EntityIdParser {

	/**
	 * @param string $idSerialization
	 *
	 * @return EntityId
	 * @throws EntityIdParsingException
	 */
	public function parse( $idSerialization );

}
