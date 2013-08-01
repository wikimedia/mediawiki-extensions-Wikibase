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


use Parser;
use ValueValidators\ValueValidator;

/**
 * UrlSchemeValidators is a collection of validators for some commonly used URL schemes.
 * This is intended for conveniently supplying a map of validators to UrlValidator.
 *
 * @package Wikibase\Validators
 */
class UrlSchemeValidators {

	/**
	 * Returns a ValueValidator that will match any URL with a valid schema name.
	 *
	 * @return RegexValidator
	 */
	public function any() {
		return new RegexValidator( '!^([-+.a-zA-Z0-9]+):(' . Parser::EXT_LINK_URL_CLASS . ')+$!i', false, 'bad-url' );
	}

	/**
	 * @param string $scheme the scheme ('http' or 'https').
	 *
	 * @return RegexValidator
	 */
	private function httpish( $scheme = 'http' ) {
		return new RegexValidator( '!^' . $scheme . '://(' . Parser::EXT_LINK_URL_CLASS . ')+$!i', false, 'bad-http-url' );
	}

	/**
	 * Returns a ValueValidator that will match URLs using the HTTP scheme.
	 *
	 * @return RegexValidator
	 */
	public function http() {
		return $this->httpish( 'http' );
	}

	/**
	 * Returns a ValueValidator that will match URLs using the HTTPS scheme.
	 *
	 * @return RegexValidator
	 */
	public function https() {
		return $this->httpish( 'https' );
	}

	/**
	 * Returns a ValueValidator that will match URLS using the mailto scheme.
	 *
	 * @return RegexValidator
	 */
	public function mailto() {
		return new RegexValidator( '!^mailto:(' . Parser::EXT_LINK_URL_CLASS . ')+@(' . Parser::EXT_LINK_URL_CLASS . ')+$!i', false, 'bad-mailto-url' );
	}

	/**
	 * Returns a validator for the given URL scheme, or null if
	 * no validator is defined for that scheme.
	 *
	 * @param string $scheme
	 *
	 * @return ValueValidator|null
	 */
	public function getValidator( $scheme ) {
		if ( method_exists( $this, $scheme ) ) {
			$validator = $this->$scheme();
			return $validator;
		}

		return null;
	}

	/**
	 * Given a list of schemes, this function returns a mapping for each supported
	 * scheme to a corresponding ValueValidator. If the schema isn't supported,
	 * no mapping is created for it.
	 *
	 * @param array $schemes a list of scheme names.
	 *
	 * @return ValueValidator[] a map of scheme names to ValueValidator objects.
	 */
	public function getValidators( array $schemes ) {
		$validators = array();

		foreach ( $schemes as $scheme ) {
			$validator = $this->getValidator( $scheme );

			if ( $validator !== null ) {
				$validators[$scheme] = $validator;
			}
		}

		return $validators;
	}
}