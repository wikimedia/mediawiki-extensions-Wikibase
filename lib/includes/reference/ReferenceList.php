<?php

namespace Wikibase;
use Hashable;

/**
 * Implementation of the References interface.
 * @see References
 *
 * Note that this implementation is based on SplObjectStorage and
 * is not enforcing the type of objects set via it's native methods.
 * Therefore one can add non-Reference-implementing objects when
 * not sticking to the methods of the References interface.
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
 * @ingroup WikibaseLib
 *
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
class ReferenceList extends HashableObjectStorage implements References {

	/**
	 * @see References::addReference
	 *
	 * @since 0.1
	 *
	 * @param Reference $reference
	 */
	public function addReference( Reference $reference ) {
		$this->attach( $reference );
	}

	/**
	 * @see References::hasReference
	 *
	 * @since 0.1
	 *
	 * @param Reference $reference
	 *
	 * @return boolean
	 */
	public function hasReference( Reference $reference ) {
		return $this->contains( $reference )
			|| $this->hasReferenceHash( $reference->getHash() );
	}

	/**
	 * @see References::removeReference
	 *
	 * @since 0.1
	 *
	 * @param Reference $reference
	 */
	public function removeReference( Reference $reference ) {
		$this->removeReferenceHash( $reference->getHash() );
	}

	/**
	 * @see References::hasReferenceHash
	 *
	 * @since 0.3
	 *
	 * @param string $referenceHash
	 *
	 * @return boolean
	 */
	public function hasReferenceHash( $referenceHash ) {
		return $this->getReference( $referenceHash ) !== null;
	}

	/**
	 * @see References::removeReferenceHash
	 *
	 * @since 0.3
	 *
	 * @param string $referenceHash	`
	 */
	public function removeReferenceHash( $referenceHash ) {
		$reference = $this->getReference( $referenceHash );

		if ( $reference !== null ) {
			$this->detach( $reference );
		}
	}

	/**
	 * @see References::getReference
	 *
	 * @since 0.3
	 *
	 * @param string $referenceHash
	 *
	 * @return Reference|null
	 */
	public function getReference( $referenceHash ) {
		/**
		 * @var Hashable $hashable
		 */
		foreach ( $this as $hashable ) {
			if ( $hashable->getHash() === $referenceHash ) {
				return $hashable;
			}
		}

		return null;
	}

	/**
	 * @see References::toArray
	 *
	 * @since 0.3
	 *
	 * @return array
	 */
	public function toArray() {
		$references = array();

		/**
		 * @var Reference $reference
		 */
		foreach ( $this as $reference ) {
			$references[] = $reference->getSnaks()->toArray();
		}

		return $references;
	}

	/**
	 * Factory for constructing a ReferenceList from its array representation.
	 *
	 * @since 0.3
	 *
	 * @param array $data
	 *
	 * @return References
	 */
	public static function newFromArray( array $data ) {
		$references = array();

		foreach ( $data as $snaks ) {
			$references[] = new Reference( SnakList::newFromArray( $snaks ) );
		}

		return new static( $references );
	}

}
