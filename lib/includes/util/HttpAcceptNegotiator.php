<?php
 /**
 *
 * Copyright © 17.05.13 by the authors listed below.
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
 * @license GPL 2+
 * @file
 *
 * @author Daniel Kinzler
 */


namespace Wikibase;


/**
 * Utility for negotiating a value from a set of supported values using a preference list.
 * This is intended for use with HTTP headers like Accept, Accept-Language, Accept-Encoding, etc.
 *
 * To use this with a request header, first parse the header value into an array of weights
 * using HttpAcceptParser, then call getBestSupportedKey.
 *
 * Class HttpAcceptNegotiator
 * @package Wikibase
 */

class HttpAcceptNegotiator {

	/**
	 * @var array
	 */
	protected $supportedValues;

	/**
	 * @var mixed
	 */
	protected $defaultValue;

	/**
	 * @param array $supported A list of supported values.
	 */
	public function __construct( array $supported ) {
		$this->supportedValues = $supported;
		$this->defaultValue = reset( $supported );
	}

	/**
	 * Returns a supported value to be used as a default.
	 */
	public function getDefaultSupportedValue() {
		return $this->defaultValue;
	}

	/**
	 * Returns the best supported key from the given weight map. Of the keys from the
	 * $weights parameter that are also in the list of supported values supplied to
	 * the constructor, this returns the key that has the highest value associated
	 * with it. Keys that map to 0 or false are ignored. If no such key is found,
	 * $default is returned.
	 *
	 * @param array $weights An associative array mapping accepted values to their
	 *              respective weights.
	 *
	 * @param null|string $default The value to return if non of the keys in $weights
	 *              is supported (null per default).
	 *
	 * @return null|string The best supported key from the $weights parameter.
	 */
	public function getBestSupportedKey( array $weights, $default = null ) {
		// it's an associative list. Sort by value and...
		asort( $weights );

		// remove any keys with values equal to 0 or false (HTTP/1.1 section 3.9)
		$weights = array_filter( $weights );

		// ...use the ordered list of keys
		$preferences = array_reverse( array_keys( $weights ) );

		$value = $this->getFirstSupportedValue( $preferences, $default );
		return $value;
	}

	/**
	 * Returns the first supported value from the given preference list. Of the values from
	 * the $preferences parameter that are also in the list of supported values supplied
	 * to the constructor, this returns the value that has the lowest index in the list.
	 * If no such value is found, $default is returned.
	 *
	 * @param array $preferences A list of acceptable values, in order of preference.
	 *
	 * @param null|string $default The value to return if non of the keys in $weights
	 *              is supported (null per default).
	 *
	 * @return null|string The best supported key from the $weights parameter.
	 */
	public function getFirstSupportedValue( array $preferences, $default = null ) {
		foreach ( $preferences as $value ) {
			foreach ( $this->supportedValues as $supported ) {
				if ( $this->valueMatches( $value, $supported ) ) {
					return $supported;
				}
			}
		}

		return $default;
	}

	/**
	 * Returns true if the given acceptable value matches the given supported value,
	 * according to the HTTP specification. The following rules are used:
	 *
	 * - comparison is case-insensitive
	 * - if $accepted and $supported are equal, they match
	 * - if $accepted is `*` or `*` followed by `/*`, it matches any $supported value.
	 * - if both $accepted and $supported contain a `/`, and $accepted ends with `/*`,
	 *   they match if the part before the first `/` is equal.
	 *
	 * @param $accepted   An accepted value (may contain wildcards)
	 * @param $supported  A supported value.
	 *
	 * @return bool Whether the given supported value matches the given accepted value.
	 */
	public function valueMatches( $accepted, $supported ) {
		// RDF 2045: MIME types are case insensitive.
		$accepted = strtolower( $accepted );
		$supported = strtolower( $supported );

		// full match
		if ( $accepted === $supported ) {
			return true;
		}

		// wildcard match (HTTP/1.1 section 14.1, 14.2, 14.3)
		if ( $accepted === '*' || $accepted === '*/*' ) {
			return true;
		}

		// wildcard match (HTTP/1.1 section 14.1)
		if ( preg_match( '!^(\w+?)/(\*|\w+)!', $accepted, $acceptedParts )
			&& preg_match( '!^(\w+?)/(\w+)!', $supported, $supportedParts ) ) {

			if ( $acceptedParts[2] === '*'
				&& $acceptedParts[1] === $supportedParts[1] ) {
				return true;
			}
		}

		return false;
	}
}