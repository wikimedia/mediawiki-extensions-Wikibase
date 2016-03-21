<?php

namespace Wikibase\Lib\Store;

/**
 * Interface for the MediaWikiPagePropertyOrderProvider
 *
 * @license GNU GPL v2+
 * @author Lucie-Aimée Kaffee
 */

interface PropertyOrderProvider {

	/**
	 * Get order of properties in the form [PropertyId] -> [Ordinal number]
	 * @return null|int[] null if no information exists
	 * @throws PropertyOrderProviderException
	 */
	public function getPropertyOrder();
}
