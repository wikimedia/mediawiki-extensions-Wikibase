<?php

namespace Wikibase;

/**
 * Implementation of the Statements interface.
 * @see Statements
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
 * @since 0.2
 *
 * @file
 * @ingroup Wikibase
 *
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
class StatementList extends \SplObjectStorage implements Statements {

	/**
	 * @see Statements::addStatement
	 *
	 * @since 0.2
	 *
	 * @param Statement $statement
	 */
	public function addStatement( Statement $statement ) {
		$this->attach( $statement );
	}

	/**
	 * @see Statements::hasStatement
	 *
	 * @since 0.2
	 *
	 * @param Statement $statement
	 *
	 * @return boolean
	 */
	public function hasStatement( Statement $statement ) {
		return $this->contains( $statement );
	}

	/**
	 * @see Statements::removeStatement
	 *
	 * @since 0.2
	 *
	 * @param Statement $statement
	 */
	public function removeStatement( Statement $statement ) {
		$this->detach( $statement );
	}

	/**
	 * @see Hashable::getHash
	 *
	 * @since 0.2
	 *
	 * @param MapHasher $mapHasher
	 *
	 * @return string
	 */
	public function getHash() {
		// We cannot have this as optional arg, because then we're no longer
		// implementing the Hashable interface properly according to PHP...
		$args = func_get_args();

		/**
		 * @var MapHasher $hasher
		 */
		$hasher = array_key_exists( 0, $args ) ? $args[0] : new MapValueHasher();

		return $hasher->hash( iterator_to_array( $this ) );
	}

}
