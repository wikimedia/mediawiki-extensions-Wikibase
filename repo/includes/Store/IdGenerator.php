<?php

namespace Wikibase;

/**
 * Generates a new unique numeric id for the provided type.
 * Ids are only unique per type.
 *
 * @license GPL-2.0+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
interface IdGenerator {

	/**
	 * @param string $type
	 *
	 * @return int
	 */
	public function getNewId( $type );

}
