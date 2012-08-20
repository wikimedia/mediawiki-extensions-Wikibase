<?php

namespace Wikibase;

/**
 * List of Reference objects.
 * Indexes the references by hash and ensures no more the one reference with the same hash are in the list.
 *
 * @since 0.1
 *
 * @file
 * @ingroup Wikibase
 *
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
interface References extends \Traversable, \ArrayAccess, \Countable, \Serializable, Hashable {

	/**
	 * Retruns if the list contains a reference with the provided hash.
	 *
	 * @since 0.1
	 *
	 * @param string $referenceHash
	 *
	 * @return boolean
	 */
	public function hasReferenceHash( $referenceHash );

	/**
	 * Removes the reference with the provided hash if it exists in the list.
	 *
	 * @since 0.1
	 *
	 * @param string $referenceHash
	 */
	public function removeReferenceHash( $referenceHash );

	/**
	 * Adds the provided reference to the list, unless a reference with the same hash is already in it.
	 *
	 * @since 0.1
	 *
	 * @param Reference $snak
	 *
	 * @return boolean Indicates if the reference was added or not.
	 */
	public function addReference( Reference $reference );

	/**
	 * Retruns if the list contains a reference with the same hash as the provided reference.
	 *
	 * @since 0.1
	 *
	 * @param Reference $snak
	 *
	 * @return boolean
	 */
	public function hasReference( Reference $reference );

	/**
	 * Removes the reference with the same hash as the provided reference if such a snak exists in the list.
	 *
	 * @since 0.1
	 *
	 * @param Reference $snak
	 */
	public function removeReference( Reference $reference );

	/**
	 * Returns the reference with the provided hash, or false if there is no such reference in the list.
	 *
	 * @since 0.1
	 *
	 * @param string $referenceHash
	 *
	 * @return Reference|false
	 */
	public function getReference( $referenceHash );

}
