<?php

namespace Wikibase\Repo\Store;

use RuntimeException;

/**
 * Generates a new unique numeric id for the provided type.
 * Ids are only unique per type.
 *
 * @license GPL-2.0-or-later
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
interface IdGenerator {

	/**
	 * @param string $type
	 *
	 * @return int
	 *
	 * @throws RuntimeException
	 */
	public function getNewId( $type );

}
