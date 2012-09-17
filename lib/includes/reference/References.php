<?php

namespace Wikibase;

/**
 * List of Reference objects.
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License along
 * with this program; if not, write to the Free Software Foundation, Inc.,
 * 51 Franklin Street, Fifth Floor, Boston, MA 02110-1301, USA.
 * http://www.gnu.org/copyleft/gpl.html
 *
 * @since 0.1
 *
 * @file
 * @ingroup Wikibase
 *
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
interface References extends \Traversable, \Countable, \Serializable, Hashable {

	/**
	 * Adds the provided reference to the list.
	 *
	 * @since 0.1
	 *
	 * @param Reference $reference
	 */
	public function addReference( Reference $reference );

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
	 * Removes the reference with the same hash as the provided reference if such a reference exists in the list.
	 *
	 * @since 0.1
	 *
	 * @param Reference $reference
	 */
	public function removeReference( Reference $reference );

}

//interface HashedReferences extends References {
//
//	/**
//	 * Retruns if the list contains a reference with the provided hash.
//	 *
//	 * @since 0.1
//	 *
//	 * @param string $referenceHash
//	 *
//	 * @return boolean
//	 */
//	public function hasReferenceHash( $referenceHash );
//
//	/**
//	 * Removes the reference with the provided hash if it exists in the list.
//	 *
//	 * @since 0.1
//	 *
//	 * @param string $referenceHash
//	 */
//	public function removeReferenceHash( $referenceHash );
//
//	/**
//	 * Returns the reference with the provided hash, or false if there is no such reference in the list.
//	 *
//	 * @since 0.1
//	 *
//	 * @param string $referenceHash
//	 *
//	 * @return Reference|false
//	 */
//	public function getReference( $referenceHash );
//
//}