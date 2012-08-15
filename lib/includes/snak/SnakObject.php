<?php

namespace Wikibase;

/**
 * Base class for snaks.
 * See https://meta.wikimedia.org/wiki/Wikidata/Data_model#Snaks
 *
 * @since 0.1
 *
 * @file
 * @ingroup Wikibase
 *
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
abstract class SnakObject implements Snak {

	/**
	 * @see Snak::getHash
	 *
	 * @since 0.1
	 *
	 * @return string
	 */
	public function getHash() {
		return md5( serialize( $this ) );
	}

}