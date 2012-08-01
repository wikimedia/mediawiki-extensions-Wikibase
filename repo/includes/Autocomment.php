<?php

namespace Wikibase;
/**
 * File defining the handler for autocomments and additional utility functions
 *
 * @since 0.1
 *
 * @file Autocomment.php
 * @ingroup Wikibase
 *
 * @licence GNU GPL v2+
 * @author John Erling Blad
 */
final class Autocomment {

	/**
	 * Pick values from a params array and collect them in a array
	 *
	 * This takes a call with a vararg list and reduce that list to the
	 * entries that has values in the params array, possibly also flattening
	 * any arrays.
	 *
	 * @since 0.1
	 *
	 * @param $params array with parameters from the call to the module
	 * @param array|string... $sequence array or variant number of strings
	 * @return array of found items
	 */
	public static function pickValuesFromParams( array $params ) {

		$sequence = func_get_args();
		array_shift( $sequence );

		if ( 1 === count( $sequence ) && is_array( $sequence[0] ) ) {
			$sequence = $sequence[0];
		}

		$common = array_intersect_key( array_flip( $sequence ), $params );
		$filtered = array_merge( $common, array_intersect_key( $params, $common ) );

		$values = array();
		foreach ( $filtered as $k => $v ) {
			if ( is_string( $v ) && $v !== '' ) {
				$values[] = $v;
			}
			elseif ( is_array( $v ) && $v !== array() ) {
				$values = array_merge( $values, $v );
			}
		}
		return array_unique( $values );
	}

	/**
	 * Pick keys from a params array and string them together
	 *
	 * This takes a call with a vararg list and reduce that list to the
	 * entries that is also keys in the params array.
	 *
	 * @since 0.1
	 *
	 * @param array $params parameters from the call to the containg module
	 * @param array|string... $sequence array or variant number of strings
	 * @return array of found items
	 */
	public static function pickKeysFromParams( array $params ) {
		$sequence = func_get_args();
		array_shift( $sequence );

		if ( 1 === count( $sequence ) && is_array( $sequence[0] ) ) {
			$sequence = $sequence[0];
		}

		$common = array_filter(
			$sequence,
			function( $key ) use ( $params ) { return isset( $params[$key] ); }
		);
		return $common;
	}

	/**
	 * Format the autocomment part of a full summary
	 *
	 * @since 0.1
	 *
	 * @param string $messageKey the message key
	 * @param array $parts parts to be stringed together
	 * @return string with a formatted comment, or possibly an empty string
	 */
	public static function formatAutoComment( $messageKey, array $parts ) {
		$joinedParts = implode( '|', $parts );
		$composite = ( 0 < strlen($joinedParts) )
			? implode( ':', array( $messageKey, $joinedParts ) )
			: $messageKey;
		return $composite;
	}

	/**
	 * Format the autosummary part of a full summary
	 *
	 * This creates a comma list of entries, and to make the comma form
	 * it is necessary to have a language. This can be a real problem as
	 * guessing it will often fail.
	 *
	 * @since 0.1
	 *
	 * @param array $parts parts to be stringed together
	 * @param Language $lang fallback for the language if its not set
	 * @return array of counts, an escaped string and the identified language
	 */
	public static function formatAutoSummary( array $parts, $lang = false ) {
		global $wgContLang;

		if ( $lang === false ) {
			$lang = $wgContLang;
		}

		$count = count( $parts );

		if ( $count === 0 ) {
			return array( 0, '', $lang );
		}
		elseif ( $count === 1 ) {
			return array( 1, $parts[0], $lang );
		}
		else {
			$composite = $lang->commaList( $parts );
			return array( count( $parts ), $composite, $lang );
		}
	}

	/**
	 * Merge the total summary
	 *
	 * @since 0.1
	 *
	 * @param string $comment initial part to go in a comment
	 * @param string $summary final part that is a easilly trucable string
	 * @param int $length total length of the string
	 * @return string to be used for the summary
	 */
	public static function formatTotalSummary( $comment, $summary, $lang = false, $length = SUMMARY_MAX_LENGTH ) {
		global $wgContLang;
		if ( $lang === null || $lang === false) {
			$lang = $wgContLang;
		}
		$comment = Utils::squashToNFC( $comment );
		$summary = Utils::squashToNFC( $summary );
		$mergedString = '';
		if ( $comment !== '' ) {
			$mergedString .=  "/* $comment */";
		}
		if ( $summary !== "" ) {
			$mergedString .= ($mergedString === "" ? "" : " ") . $lang->truncate( $summary, $length - strlen( $mergedString ) );
		}
		// leftover entities should be removed, but its not clear how this shall be done
		return $mergedString;
	}

}