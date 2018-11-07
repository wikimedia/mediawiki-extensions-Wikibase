<?php

namespace Wikibase\DataModel;

use Wikibase\DataModel\Entity\PropertyId;

/**
 * Interface for objects containing a property id.
 *
 * @since 1.1
 *
 * @license GPL-2.0-or-later
 * @author Bene* < benestar.wikimedia@gmail.com >
 */
interface PropertyIdProvider {

	/**
	 * @since 1.1
	 *
	 * @return PropertyId
	 */
	public function getPropertyId();

}
