<?php

namespace Wikibase\DataModel\Entity;

/**
 * Interface for objects that can parse strings into EntityIds
 *
 * @since 4.2
 *
 * @license GPL-2.0-or-later
 * @author Addshore
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
