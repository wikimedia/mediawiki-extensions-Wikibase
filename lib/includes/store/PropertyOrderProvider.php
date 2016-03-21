<?php

namespace Wikibase\Lib\Store;

/**
 * Interface that contains method for the PropertyOrderProvider
 *
 * @license GNU GPL v2+
 * @author Lucie-Aimée Kaffee
 */
interface PropertyOrderProvider {

	/**
	 * Get order of properties in the form array( $propertyIdSerialization => $ordinalNumber )
	 * @return null|int[] null if no information exists
	 * @throws PropertyOrderProviderException
	 */
	public function getPropertyOrder();
}
