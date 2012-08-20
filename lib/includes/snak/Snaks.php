<?php

namespace Wikibase;

/**
 * List of Snak objects.
 * Indexes the snaks by hash and ensures no more the one snak with the same hash are in the list.
 *
 * @since 0.1
 *
 * @file
 * @ingroup Wikibase
 *
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
interface Snaks extends \Traversable, \ArrayAccess, \Countable, \Serializable, Hashable {

	/**
	 * Retruns if the list contains a snak with the provided hash.
	 *
	 * @since 0.1
	 *
	 * @param string $snakHash
	 *
	 * @return boolean
	 */
	public function hasSnakHash( $snakHash );

	/**
	 * Removes the snak with the provided hash if it exists in the list.
	 *
	 * @since 0.1
	 *
	 * @param string $snakHash
	 */
	public function removeSnakHash( $snakHash );

	/**
	 * Adds the provided snak to the list, unless a snak with the same hash is already in it.
	 *
	 * @since 0.1
	 *
	 * @param Snak $snak
	 *
	 * @return boolean Indicates if the snak was added or not.
	 */
	public function addSnak( Snak $snak );

	/**
	 * Retruns if the list contains a snak with the same hash as the provided snak.
	 *
	 * @since 0.1
	 *
	 * @param Snak $snak
	 *
	 * @return boolean
	 */
	public function hasSnak( Snak $snak );

	/**
	 * Removes the snak with the same hash as the provided snak if such a snak exists in the list.
	 *
	 * @since 0.1
	 *
	 * @param Snak $snak
	 */
	public function removeSnak( Snak $snak );

	/**
	 * Returns the snak with the provided hash, or false if there is no such snak in the list.
	 *
	 * @since 0.1
	 *
	 * @param string $snakHash
	 *
	 * @return Snak|false
	 */
	public function getSnak( $snakHash );

}
