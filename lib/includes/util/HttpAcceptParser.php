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
 * Utility for parsing a HTTP Accept header value into a weight map. May also be used with
 * other, similar headers like Accept-Language, Accept-Encoding, etc.
 *
 * Class HttpAcceptNegotiator
 * @package Wikibase
 */

class HttpAcceptParser {

	/**
	 * Parses an HTTP header into a weight map, that is an associative array
	 * mapping values to their respective weights. Any header name preceding
	 * weight spec is ignored for convenience.
	 *
	 * This implementation is partially based on the code at
	 * http://www.thefutureoftheweb.com/blog/use-accept-language-header
	 *
	 * Note that type parameters and accept extension like the "level" parameter
	 * are not supported, weights are derived from "q" values only.
	 *
	 * @todo: If additional type parameters are present, ignore them cleanly.
	 *        At present, they often confuse the result.
	 *
	 * See HTTP/1.1 section 14 for details.
	 *
	 * @param $rawHeader
	 *
	 * @return array
	 */
	public function parseWeights( $rawHeader ) {

		//FIXME: The code below was copied and adapted from WebRequest::getAcceptLang.
		//       Move this utility class into core for reuse!

		// first, strip header name
		$rawHeader = preg_replace( '/^[-\w]+:\s*/', '', $rawHeader );

		// Return values in lower case
		$rawHeader = strtolower( $rawHeader );

		// Break up string into pieces (values and q factors)
		$value_parse = null;
		preg_match_all( '@([a-z0-9*]+([-+/.][a-z0-9*]+)*)\s*(;\s*q\s*=\s*(1(\.0{0,3})?|0(\.[0-9]{0,3})?)?)?@',
			$rawHeader, $value_parse );

		if ( !count( $value_parse[1] ) ) {
			return array();
		}

		$values = $value_parse[1];
		$qvalues = $value_parse[4];
		$indices = range( 0, count( $value_parse[1] ) - 1 );

		// Set default q factor to 1
		foreach ( $indices as $index ) {
			if ( $qvalues[$index] === '' ) {
				$qvalues[$index] = 1;
			} elseif ( $qvalues[$index] == 0 ) {
				unset( $values[$index], $qvalues[$index], $indices[$index] );
			} else {
				$qvalues[$index] = (float)$qvalues[$index];
			}
		}

		// Sort list. First by $qvalues, then by order. Reorder $values the same way
		array_multisort( $qvalues, SORT_DESC, SORT_NUMERIC, $indices, $values );

		// Create a list like "en" => 0.8
		$weights = array_combine( $values, $qvalues );

		return $weights;
	}

}