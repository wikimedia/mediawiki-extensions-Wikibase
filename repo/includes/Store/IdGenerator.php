<?php

namespace Wikibase;

/**
 * Generates a new unique numeric id for the provided type.
 * Ids are only unique per type.
 *
 * @since 0.1
 *
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
interface IdGenerator {

	/**
	 * @since 0.1
	 *
	 * @param string $type
	 *
	 * @return int
	 */
	public function getNewId( $type );

}
