<?php
 /**
 *
 * Copyright © 10.06.13 by the authors listed below.
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


namespace Wikibase\Validators;


use ValueValidators\Error;
use ValueValidators\Result;
use ValueValidators\ValueValidator;

/**
 * UrlValidator checks URLs based on sub-validators for each scheme.
 *
 * @package Wikibase\Validators
 */
class UrlValidator implements ValueValidator {

	/**
	 * @var ValueValidator[]
	 */
	protected $schemes;

	/**
	 * Constructs a UrlValidator that checks the given URL schemes.
	 *
	 * @param array $schemes a map of scheme names (e.g. 'html') to ValueValidators.
	 *        The special scheme name "*" can be used to specify a validator for
	 *        other schemes. You may want to use UrlSchemaValidators to create the
	 *        respective validators conveniently.
	 *
	 * @throws \InvalidArgumentException
	 */
	public function __construct( array $schemes ) {
		foreach ( $schemes as $scheme => $validator ) {
			if ( !is_string( $scheme ) ) {
				throw new \InvalidArgumentException( 'The keys in $scheme must be strings (scheme names).' );
			}

			if ( !is_object( $validator ) || !( $validator instanceof ValueValidator ) ) {
				throw new \InvalidArgumentException( 'The values in $scheme must be instances of ValueValidator.' );
			}
		}

		$this->schemes = $schemes;
	}

	/**
	 * @see ValueValidator::validate()
	 *
	 * @param mixed $value The value to validate
	 *
	 * @return \ValueValidators\Result
	 * @throws \InvalidArgumentException
	 */
	public function validate( $value ) {
		// See RFC 3986, section-3.1.
		if ( !preg_match( '/^([-+.a-zA-Z0-9]+):(.*)$/', $value, $m ) ) {
			return Result::newError( array(
				Error::newError( 'Malformed URL, can\'t find scheme name.', null, 'bad-url', array( $value ) )
			) );
		}

		// Should we also check for and fail on whitespace in $value?

		$scheme = strtolower( $m[1] );

		if ( isset( $this->schemes[$scheme] ) ) {
			$validator = $this->schemes[$scheme];
		} elseif ( isset( $this->schemes['*'] ) ) {
			$validator = $this->schemes['*'];
		} else {
			return Result::newError( array(
				Error::newError( 'Unsupported URL scheme', null, 'bad-url-scheme', array( $scheme ) )
			) );
		}

		return $validator->validate( $value );
	}

	/**
	 * @see ValueValidator::setOptions()
	 *
	 * @param array $options
	 */
	public function setOptions( array $options ) {
		// Do nothing. This method shouldn't even be in the interface.
	}
}