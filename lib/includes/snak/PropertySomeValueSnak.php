<?php

namespace Wikibase;

/**
 * Class representing a property some value snak.
 * See https://meta.wikimedia.org/wiki/Wikidata/Data_model#PropertyNoValueSnak
 *
 * @since 0.1
 *
 * @file
 * @ingroup Wikibase
 *
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
class PropertySomeValueSnak extends PropertySnakObject {

	/**
	 * @see Snak::getType
	 *
	 * @since 0.2
	 *
	 * @return string
	 */
	public function getType() {
		return 'somevalue';
	}

}