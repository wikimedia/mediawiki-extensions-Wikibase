<?php

namespace Wikibase\Lib;

use Wikibase\DataModel\Snak\PropertyValueSnak;

/**
 * SnakUrlExpander expands the value of a Snak to a URL (or URI) or some sort.
 * The mechanism of expansion and the meaning of the URL are not defined by the interface.
 *
 * @since 0.5
 *
 * @licence GNU GPL v2+
 * @author Daniel Kinzler
 */
interface SnakUrlExpander {

	/**
	 * @param PropertyValueSnak $snak
	 *
	 * @return string|null A URL or URI derived from the Snak, or null if no such URL
	 *         could be determined.
	 */
	public function expandUrl( PropertyValueSnak $snak );

}
