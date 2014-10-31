<?php

namespace Wikibase\DataModel\Snak;

/**
 * Class representing a property no value snak.
 * See https://www.mediawiki.org/wiki/Wikibase/DataModel#PropertyNoValueSnak
 *
 * @since 0.1
 *
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
class PropertyNoValueSnak extends SnakObject {

	/**
	 * @see Snak::getType
	 *
	 * @since 0.2
	 *
	 * @return string
	 */
	public function getType() {
		return 'novalue';
	}

}