<?php
namespace Wikibase;

/**
 * Helper class for custom conversion between objects to array structures. This is useful to
 * allow custom serialization of objects in array based formats such as JSON.
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
 * @since 0.4
 *
 * @file
 * @ingroup WikibaseLib
 *
 * @licence GNU GPL v2+
 * @author Daniel Kinzler
 */
class Arrayalizer {

	/**
	 * Converts the given value into a structure of nested arrays, of possible. If the value
	 * is primitive, it remains primitive. If $data is an object, $convert is called
	 * on it, which may turn the object into an array.
	 *
	 * If at this point $data is an array, arrayalize() is called for each of its entries
	 * recursively.
	 *
	 * @param callable $convert The conversion function to call when encountering objects
	 *        in the input structure. Must take an object as a parameter, and return
	 *        either an array structure representing the object, or the object itself.
	 *
	 * @param mixed &$data The data to convert.
	 *        This will be modified, the conversion is applied in-place!
	 */
	public static function arrayalize( $convert, &$data ) {
		if ( is_object( $data ) ) {
			try {
				$data = call_user_func( $convert, $data );
			} catch ( \Exception $ex ) {
				try {
					trigger_error( "arrayalize: Callable " . self::callableToString( $convert )
						. " caused an exception: " . $ex->getMessage(), E_USER_WARNING );
				} catch ( \Exception $exx ) {
					// Argh! We *can't* throw an exception!
					$exx = (object)$exx; // this is just a breakpoint anchor.
				}

				$data = false;
			}
		}

		if ( is_array( $data ) || $data instanceof \ArrayObject ) {
			foreach ( $data as $key => &$value ) {
				self::arrayalize( $convert, $value );
			}
		}
	}

	/**
	 * Converts a given array structure into an object, if possible. If $data is an object or
	 * primitive, it is returned unchanged. If $data is an array, the conversion callback
	 * is called on it to turn it into an object.
	 *
	 * If after this $data is still an array, it is traversed, calling objectify for every value
	 * recursively.
	 *
	 * @param callable $convert The conversion function to call when encountering arrays
	 *        in the input structure. Must take an array and optionally a context marker
	 *        as a parameter, and return either an object created from the array structure,
	 *        or the object itself.
	 *
	 * @param mixed &$data The data structure to objectify
	 *              This will be modified, the conversion is applied in-place!
	 *
	 * @param null|string|int $role Some hint as to the role of $data in its parent structure;
	 *        If $data comes from an array, $role is typically set to the array key $data is
	 *        assigned to.
	 */
	public static function objectify( $convert, &$data, $role = null ) {
		if ( is_array( $data ) ) {
			try {
				$data = call_user_func( $convert, $data, $role );
			} catch ( \Exception $ex ) {
				try {
					trigger_error( "objectify: Callable " . self::callableToString( $convert )
						. " caused an exception: " . $ex->getMessage(), E_USER_WARNING );
				} catch ( \Exception $exx ) {
					// Argh! We *can't* throw an exception!
					$exx = (object)$exx; // this is just a breakpoint anchor.
				}

				$data = false;
			}
		}

		if ( is_array( $data ) || $data instanceof \ArrayObject ) {
			foreach ( $data as $key => &$value ) {
				self::objectify( $convert, $value, $key );
			}
		}
	}

	/**
	 * Returns a terse string representation of a callable.
	 *
	 * @param callable $callable
	 *
	 * @return string
	 *
	 * @todo This is really generic, move it somewhere else
	 */
	public static function callableToString( $callable ) {
		if ( is_array( $callable ) && count( $callable ) === 1 ) {
			$callable = array_pop( $callable );
		}

		if ( is_string( $callable ) ) {
			return $callable;
		} elseif ( is_object( $callable ) ) {
			return get_class( $callable ) . '->__invoke';
		} elseif ( is_array( $callable ) ) {
			$target = $callable[0];
			$method = $callable[1];

			if ( is_string( $target ) ) {
				return "$target::$method";
			} elseif ( is_object( $target ) ) {
				$class = get_class( $target );
				return "$class->$method";
			} else {
				return '(array)';
			}
		}

		return '(' . var_export( $callable, true ) . ')';
	}
}
