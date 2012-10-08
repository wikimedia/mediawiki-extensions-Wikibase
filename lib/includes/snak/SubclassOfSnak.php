<?php

namespace Wikibase;

/**
 * Class representing a "subclass of" snak.
 * See https://meta.wikimedia.org/wiki/Wikidata/Data_model#SubclassOfSnak
 *
 * @since 0.1
 *
 * @file
 * @ingroup Wikibase
 *
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
class SubclassOfSnak extends InstanceOfSnak {

	/**
	 * @see Snak::getPropertyId
	 *
	 * @since 0.2
	 *
	 * @return integer
	 */
	public function getPropertyId() {
		return -2;
	}

	/**
	 * @see Snak::getType
	 *
	 * @since 0.2
	 *
	 * @return string
	 */
	public function getType() {
		return 'subclass';
	}

}