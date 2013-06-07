<?php

namespace Wikibase;

use Traversable;
use Hashable;
use MWException;

/**
 * Generates hashes for associative arrays based on the values of their elements.
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
class MapValueHasher implements MapHasher {

	/**
	 * @since 0.1
	 *
	 * @var boolean
	 */
	protected $ordered;

	/**
	 * Constructor.
	 *
	 * @since 0.1
	 *
	 * @param boolean $ordered
	 */
	public function __construct( $ordered = false ) {
		$this->ordered = $ordered;
	}

	/**
	 * @see MapHasher::hash
	 *
	 * @since 0.1
	 *
	 * @param Traversable|Hashable[] $map
	 *
	 * @return string
	 * @throws MWException
	 */
	public function hash( $map ) {
		if ( !is_array( $map ) && !( $map instanceof Traversable ) ) {
			throw new MWException( 'MapHasher::hash only accepts Traversable objects (including arrays)' );
		}

		$hashes = array();

		/**
		 * @var Hashable $hashable
		 */
		foreach ( $map as $hashable ) {
			$hashes[] = $hashable->getHash();
		}

		if ( !$this->ordered ) {
			sort( $hashes );
		}

		return sha1( implode( '|', $hashes ) );
	}

}
