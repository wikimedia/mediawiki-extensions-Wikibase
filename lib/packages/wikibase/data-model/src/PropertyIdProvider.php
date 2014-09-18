<?php

namespace Wikibase\DataModel;

use Wikibase\DataModel\Entity\PropertyId;

/**
 * Interface for objects containing a property id.
 *
 * @since 1.1
 *
 * @license GNU GPL v2+
 * @author Bene* < benestar.wikimedia@gmail.com >
 */
interface PropertyIdProvider {

	/**
	 * Returns the property id of this object.
	 *
	 * @since 1.1
	 *
	 * @return PropertyId
	 */
	public function getPropertyId();

}
