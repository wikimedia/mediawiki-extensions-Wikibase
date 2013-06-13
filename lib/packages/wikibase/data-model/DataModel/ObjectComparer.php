<?php

namespace Wikibase;

/**
 * Compares two objects and returns if they are equal.
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
 * @author Daniel Kinzler
 */
class ObjectComparer {

	/**
	 * Determines whether two data structures are equal. $a and $b can be
	 * of any type, but support for objects is limited. Arrays are compared
	 * recursively. When comparing indexed arrays, the order of element is
	 * relevant. When comparing associative arrays, the order is irrelevant.
	 *
	 * TODO: use of get_object_vars might lead to unexpected results.
	 *       Additional info is needed to correctly compare objects.
	 *       Could check if Comparable interface is implemented and then use equals method.
	 * TODO: $skip is not passed recursively, so we cannot skip non-top-level elements.
	 *       Intentional? If not, perhaps better set this in the constructor.
	 * TODO: this method contains much untested logic
	 *
	 * @since 0.1
	 *
	 * @param $a
	 * @param $b
	 * @param $skip array keys to skip
	 *
	 * @return bool
	 */
	public function dataEquals( &$a, &$b, $skip = null ) {
		if ( is_array( $a ) ) {
			if ( !is_array( $b ) ) {
				return false;
			}

			// check everything that is in $a
			foreach ( $a as $k => &$v ) {
				if ( $skip !== null && in_array( $k, $skip ) ) {
					continue;
				}

				if ( array_key_exists( $k, $b ) ) {
					// $k is in $a and in $b
					$w =& $b[$k];

					if ( !$this->dataEquals( $v, $w ) ) {
						// $k is in both arrays, but the value isn't equal
						return false;
					}
				} else { // $k is in $a but not in $b
					if ( !( is_array( $v ) && empty( $v ) ) && $v !== null ) {
						// $k is not in $b and $v is not an empty array or null
						return false;
					}
				}
			}

			$remaining = array_diff(
				array_keys( $b ),
				array_keys( $a )
			);

			// check everything that is in $b but not in $a
			foreach ( $remaining as $k ) {
				if ( $skip !== null && in_array( $k, $skip ) ) {
					continue;
				}

				$w =& $b[$k];

				if ( !( is_array( $w ) && empty( $w ) ) && $w !== null ) {
					// $k is not in $a and $w is not an empty array or null
					return false;
				}
			}

			return true;
		} else if ( is_array( $b ) ) {
			return false;
		} else if ( is_object( $a ) ) {
			if ( !is_object( $b ) ) {
				return false;
			}

			// special handling for some types of objects here
			return $this->dataEquals( get_object_vars( $a ), get_object_vars( $b ) );
		} else if ( is_object( $b ) ) {
			return false;
		} else {
			return $a === $b;
		}
	}

}
