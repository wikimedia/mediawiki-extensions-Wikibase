<?php

namespace Wikibase\DataModel;

/**
 * List of Reference objects.
 *
 * @since 0.1
 *
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
interface References extends \Traversable, \Countable, \Serializable, \Comparable {

	/**
	 * Adds the provided reference to the list.
	 *
	 * @since 0.1
	 *
	 * @param Reference $reference
	 * @param int|null $index
	 */
	public function addReference( Reference $reference, $index = null );

	/**
	 * Returns if the list contains a reference with the same hash as the provided reference.
	 *
	 * @since 0.1
	 *
	 * @param Reference $reference
	 *
	 * @return boolean
	 */
	public function hasReference( Reference $reference );

	/**
	 * Returns the index of a reference or false if the reference could not be found.
	 *
	 * @since 0.5
	 *
	 * @param Reference $reference
	 *
	 * @return int|boolean
	 */
	public function indexOf( Reference $reference );

	/**
	 * Removes the reference with the same hash as the provided reference if such a reference exists in the list.
	 *
	 * @since 0.1
	 *
	 * @param Reference $reference
	 */
	public function removeReference( Reference $reference );

	/**
	 * Returns an array representing the references.
	 * Roundtrips with ReferenceList::newFromArray
	 *
	 * @since 0.3
	 *
	 * @return array
	 */
	public function toArray();

	/**
	 * Removes duplicates bases on hash value.
	 *
	 * @since 0.3
	 */
	public function removeDuplicates();

	/**
	 * Returns if the list contains a reference with the provided hash.
	 *
	 * @since 0.3
	 *
	 * @param string $referenceHash
	 *
	 * @return boolean
	 */
	public function hasReferenceHash( $referenceHash );

	/**
	 * Removes the reference with the provided hash if it exists in the list.
	 *
	 * @since 0.3
	 *
	 * @param string $referenceHash
	 */
	public function removeReferenceHash( $referenceHash );

	/**
	 * Returns the reference with the provided hash, or null if there is no such reference in the list.
	 *
	 * @since 0.3
	 *
	 * @param string $referenceHash
	 *
	 * @return Reference|null
	 */
	public function getReference( $referenceHash );

}
