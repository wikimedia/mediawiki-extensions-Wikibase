<?php

namespace Wikibase;
/**
 * File defining the handler for autocomments and additional utility functions
 *
 * @since 0.1
 *
 * @file
 * @ingroup Wikibase
 *
 * @licence GNU GPL v2+
 * @author John Erling Blad
 */
final class Autocomment {

	/**
	 * Pretty formating of autocomments.
	 *
	 * Note that this function does _not_ use $title and $local but
	 * could use them if links should be created that points to something.
	 * Typically this could be links that moves to and highlight some
	 * section within the item itself.
	 *
	 * @param string $comment reference to the finalized autocomment
	 * @param string $pre the string before the autocomment
	 * @param string $auto the autocomment unformatted
	 * @param string $post the string after the autocomment
	 * @param Titel $title use for further information
	 * @param boolean $local shall links be generated locally or globally
	 *
	 * @return boolean
	 */
	public static function onFormat( $data, &$comment, $pre, $auto, $post, $title, $local ) {
		global $wgLang, $wgTitle;

		list( $model, $root ) = $data;

		// If it is possible to avoid loading the whole page then the code will be lighter on the server.
		$title = $title === null ? $wgTitle : $title;

		if ( $title->getContentModel() === $model ) {

			if ( preg_match( '/^([\-a-z]+?)\s*(:\s*(.*?))?\s*$/', $auto, $matches ) ) {

				// turn the args to the message into an array
				$args = ( 3 < count( $matches ) ) ? explode( '|', $matches[3] ) : array();

				// look up the message
				$msg = wfMessage( $root . '-summary-' . $matches[1] );
				if ( !$msg->isDisabled() ) {
					// parse the autocomment
					$auto = $msg->params( $args )->parse();

					// add pre and post fragments
					if ( $pre ) {
						# written summary $presep autocomment (summary /* section */)
						$pre .= wfMessage( 'autocomment-prefix' )->escaped();
					}
					if ( $post ) {
						# autocomment $postsep written summary (/* section */ summary)
						$auto .= wfMessage( 'colon-separator' )->escaped();
					}

					$auto = '<span class="autocomment">' . $auto . '</span>';
					$comment = $pre . $wgLang->getDirMark() . '<span dir="auto">' . $auto . $post . '</span>';
				}
			}
		}
		return true;
	}

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