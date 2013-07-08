<?php

namespace Wikibase;
/**
 * File defining the handler for autocomments and additional utility functions
 *
 * @since 0.1
 *
 * @file
 * @ingroup WikibaseRepo
 *
 * @licence GNU GPL v2+
 * @author John Erling Blad
 *
 * @deprecated use the Summary class to build summaries instead
 * @note before removing this class once it's phased out, make sure the doFormat hook handler
 *       gets rescued to a new location.
 */
final class Autocomment {

	/**
	 * @see Summary::formatAutoComment
	 *
	 * @since 0.1
	 * @deprecated this is a B/C stub for Summary::formatAutoComment
	 *
	 * @return string
	 */
	public static function formatAutoComment( $messageKey, array $parts ) {
		return Summary::formatAutoComment( $messageKey, $parts );
	}

	/**
	 * @see Summary::formatAutoSummary
	 *
	 * @since 0.1
	 * @deprecated this is a B/C stub for Summary::formatAutoSummary
	 *
	 * @return array
	 */
	public static function formatAutoSummary( $parts, \Language $lang = null ) {
		global $wgContLang;

		return array(
			count( $parts ),
			Summary::formatAutoSummary( $parts ),
			$lang ? $lang : $wgContLang );
	}

	/**
	 * @see Summary::formatTotalSummary
	 *
	 * @since 0.1
	 * @deprecated this is a B/C stub for Summary::formatTotalSummary
	 *
	 * @return string
	 */
	public static function formatTotalSummary( $comment, $summary, $lang = false, $length = SUMMARY_MAX_LENGTH ) {
		return Summary::formatTotalSummary( $comment, $summary, $length );
	}

	/**
	 * Pretty formatting of autocomments.
	 *
	 * Note that this function does _not_ use $title and $local but
	 * could use them if links should be created that points to something.
	 * Typically this could be links that moves to and highlight some
	 * section within the item itself.
	 *
	 * @todo: move this elsewhere, it doesn't really belong in this class
	 *
	 * @param $data
	 * @param string $comment reference to the finalized autocomment
	 * @param string $pre the string before the autocomment
	 * @param string $auto the autocomment unformatted
	 * @param string $post the string after the autocomment
	 * @param \Title $title use for further information
	 * @param boolean $local shall links be generated locally or globally
	 *
	 * @return boolean
	 */
	public static function onFormat( $data, &$comment, $pre, $auto, $post, $title, $local ) {
		global $wgLang, $wgTitle;

		list( $model, $root ) = $data;

		// If it is possible to avoid loading the whole page then the code will be lighter on the server.
		$title = $title === null ? $wgTitle : $title;

		if ( $title->getContentModel() !== $model ) {
			return true;
		}

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
					// written summary $presep autocomment (summary /* section */)
					$pre .= wfMessage( 'autocomment-prefix' )->escaped();
				}
				if ( $post ) {
					// autocomment $postsep written summary (/* section */ summary)
					$auto .= wfMessage( 'colon-separator' )->escaped();
				}

				$auto = '<span class="autocomment">' . $auto . '</span>';
				$comment = $pre . $wgLang->getDirMark() . '<span dir="auto">' . $auto . $post . '</span>';
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
		foreach ( $filtered as $v ) {
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
	 * Build the summary by call to the module
	 *
	 * If this is used for other classes than api modules it could be necessary to change
	 * its internal logic
	 *
	 * @since 0.1
	 *
	 * @param \Wikibase\Api\IAutocomment $module an api module that support IAutocomment
	 *
	 * @param null|array $params
	 * @param null|EntityContent $entityContent
	 * @return string to be used for the summary
	 */
	public static function buildApiSummary( $module, $params = null, $entityContent = null ) {
		// check if we must pull in the request params
		if ( !isset( $params ) ) {
			$params = $module->extractRequestParams();
		}

		// Is there a user supplied summary, then use it but get the hits first
		if ( isset( $params['summary'] ) ) {
			list( $hits, $summary, $lang ) = $module->getTextForSummary( $params );
			$summary = $params['summary'];
		}

		// otherwise try to construct something
		else {
			list( $hits, $summary, $lang ) = $module->getTextForSummary( $params );
			if ( !is_string( $summary ) ) {
				if ( isset( $entityContent ) ) {
					$summary = $entityContent->getTextForSummary( $params );
				}
				else {
					$summary = '';
				}
			}
		}

		// Comments are newer user supplied
		$comment = $module->getTextForComment( $params, $hits );

		// format the overall string and return it
		return self::formatTotalSummary( $comment, $summary, $lang );
	}
}
