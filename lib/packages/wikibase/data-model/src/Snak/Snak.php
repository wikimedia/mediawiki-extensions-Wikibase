<?php

namespace Wikibase\DataModel\Snak;

use Serializable;
use Wikibase\DataModel\PropertyIdProvider;

/**
 * Interface for objects that represent a single Wikibase snak.
 * See https://www.mediawiki.org/wiki/Wikibase/DataModel#Snaks
 *
 * @since 0.1
 *
 * @license GPL-2.0-or-later
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
interface Snak extends Serializable, PropertyIdProvider {

	/**
	 * Returns a string that can be used to identify the type of snak.
	 *
	 * @since 0.2
	 *
	 * @return string
	 */
	public function getType();

	/**
	 *
	 * @return string
	 */
	public function getHash();

	/**
	 * @param mixed $value
	 * @return boolean
	 */
	public function equals( $value );

	public function __serialize(): array;

	public function __unserialize( array $serialized ): void;

}
