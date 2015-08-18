<?php

namespace Wikibase\DataModel\Services\EntityId;

use Wikibase\DataModel\Entity\EntityId;

/**
 * Interface for objects that can parse strings into EntityIds
 *
 * @since 1.0
 *
 * @licence GNU GPL v2+
 * @author Adam Shorland
 */
interface EntityIdParser {

	/**
	 * @since 1.0
	 *
	 * @param string $idSerialization
	 *
	 * @return EntityId
	 * @throws EntityIdParsingException
	 */
	public function parse( $idSerialization );

}
