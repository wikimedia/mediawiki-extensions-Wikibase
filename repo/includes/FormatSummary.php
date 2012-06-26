<?php

namespace Wikibase;


/**
 * Collection of methods for handling a formatted summary.
 *
 * @since 0.1
 *
 * @file
 * @ingroup Wikibase
 *
 * @licence GNU GPL v2+
 * @author John Erling Blad < jeblad@gmail.com >
 */
final class FormatSummary {

	/**
	 * Limited string stuffing for just a few characters, forward version
	 * @param $str string to clean for occurences of configured characters
	 * @return string
	 */
	public static function stringStuffingForward( $str ) {
		// This is an ordinary forward stuffing where url-escaping is used for a few characters that is
		// used in this specific context, that is the encoding of the summary.

		static $initialized = false;

		static $summary_continuation_pattern = null;
		static $summary_grouping_pattern = null;
		static $summary_subgrouping_pattern = null;
		static $summary_stuffing_pattern = null;

		static $summary_continuation_replacement = null;
		static $summary_grouping_replacement = null;
		static $summary_subgrouping_replacement = null;
		static $summary_stuffing_replacement = null;

		if ( !$initialized ) {
			$summary_continuation_pattern = '/' . SUMMARY_CONTINUATION_STR . '/';
			$summary_grouping_pattern = '/\\' . SUMMARY_GROUPING_STR . '/';
			$summary_subgrouping_pattern = '/' . SUMMARY_SUBGROUPING_STR . '/';
			$summary_stuffing_pattern = '/' . SUMMARY_STUFFING_STR . '/';

			$summary_continuation_replacement = wfUrlencode( SUMMARY_CONTINUATION_STR );
			$summary_grouping_replacement = wfUrlencode( SUMMARY_GROUPING_STR );
			$summary_subgrouping_replacement = wfUrlencode( SUMMARY_SUBGROUPING_STR );
			$summary_stuffing_replacement = wfUrlencode( SUMMARY_STUFFING_STR );

			$initialized = true;
		}

		$str = preg_replace( $summary_stuffing_pattern, $summary_stuffing_replacement, $str );
		$str = preg_replace( $summary_grouping_pattern, $summary_grouping_replacement, $str );
		$str = preg_replace( $summary_subgrouping_pattern, $summary_subgrouping_replacement, $str );
		$str = preg_replace( $summary_continuation_pattern, $summary_continuation_replacement, $str );

		return $str;
	}

	/**
	 * Limited string stuffing for just a few characters, reverse version
	 * @param $str string to backport to the original form
	 * @return string that is cleansed
	 */
	public static function stringStuffingReverse( $str ) {
		// This is an ordinary forward stuffing where url-escaping is used for a few characters that is
		// used in this specific context, that is the encoding of the summary.

		static $initialized = false;

		static $summary_continuation_pattern = null;
		static $summary_grouping_pattern = null;
		static $summary_subgrouping_pattern = null;
		static $summary_stuffing_pattern = null;

		static $summary_continuation_replacement = null;
		static $summary_grouping_replacement = null;
		static $summary_subgrouping_replacement = null;
		static $summary_stuffing_replacement = null;

		if ( !$initialized ) {
			$summary_continuation_pattern = '/' . wfUrlencode( SUMMARY_CONTINUATION_STR ) . '/';
			$summary_grouping_pattern = '/' . wfUrlencode( SUMMARY_GROUPING_STR ) . '/';
			$summary_subgrouping_pattern = '/' . wfUrlencode( SUMMARY_SUBGROUPING_STR ) . '/';
			$summary_stuffing_pattern = '/' . wfUrlencode( SUMMARY_STUFFING_STR ) . '/';

			$summary_continuation_replacement = SUMMARY_CONTINUATION_ALT;
			$summary_grouping_replacement = SUMMARY_GROUPING_ALT;
			$summary_subgrouping_replacement = SUMMARY_SUBGROUPING_ALT;
			$summary_stuffing_replacement = SUMMARY_STUFFING_ALT;

			$initialized = true;
		}

		$str = preg_replace( $summary_continuation_pattern, $summary_continuation_replacement, $str );
		$str = preg_replace( $summary_subgrouping_pattern, $summary_subgrouping_replacement, $str );
		$str = preg_replace( $summary_grouping_pattern, $summary_grouping_replacement, $str );
		$str = preg_replace( $summary_stuffing_pattern, $summary_stuffing_replacement, $str );
		return $str;
	}


	/**
	 * Pick values from the params array and string them together
	 *
	 * @since 0.1
	 *
	 * @param $params array with parameters from the call to the module
	 * @param $max_length integer is the byte length of the available space
	 */
	public static function pickValuesFromParams( array $params, $max_length ) {

		// the keys of the values that shall be extracted
		$keys = func_get_args();

		// shift out the args that is handled
		array_shift( $keys );
		array_shift( $keys );

		// accumulator for the length
		$overallLength = 0;

		// the continuation marker length as is written into the comment
		// and the length must be known before it can be made room for it
		$contLength = strlen( SUMMARY_CONTINUATION_STR );

		// check if there is any space at all
		if ( $max_length <= 0 ) {
			return '';
		}

		// collect the values
		// note that we must figure out how many of the args we can find and what they contains
		// before we can chop the remaining string to the correct length
		$arr = array();
		foreach ( array_intersect_key( $params, array_flip( $keys ) ) as $k => $v ) {
			if ( is_string( $v ) ) {
				//$value = Sanitizer::escapeHtmlAllowEntities(
				//	self::pickValuesFromParamsCleaner( $v )
				//);
				$value = self::stringStuffingForward( $v );
				// note that we use previous overall length and fix overflow later
				if ( $max_length < $overallLength ) {
					array_push( $arr, SUMMARY_CONTINUATION_STR );
					$overallLength += ( $overallLength ? 1 + $contLength : $contLength );
				}
				else {
					array_push( $arr, $value );
					$overallLength += ( $overallLength ? 1 + strlen( $value ) : strlen( $value ) );
				}
			}
			elseif ( is_array( $v ) ) {
				//$value = Sanitizer::escapeHtmlAllowEntities(
				//	join(
				//		SUMMARY_SUBGROUPING,
				//		array_map( self::pickValuesFromParamsCleaner, $v )
				//	)
				//);
				$value = 
					join(
						SUMMARY_SUBGROUPING,
						array_map( self::stringStuffingForward, $v )
					);
				// note that we use previous overall length and fix overflow later
				if ( $max_length < $overallLength ) {
					array_push( $arr, SUMMARY_CONTINUATION_STR );
					$overallLength += ( $overallLength ? 1 + $contLength : $contLength );
				}
				else{
					array_push( $arr, $value );
					$overallLength += ( $overallLength ? 1 + strlen( $value ) : strlen( $value ) );
				}
			}
			else {
				array_push( $arr, SUMMARY_CONTINUATION_STR );
				$overallLength += ( $overallLength ? 1 + $contLength : $contLength );
			}
		}
		// if the overall length of the collected string is to long the values needs to be shortened
		// that is the elements with overflow from the previous must be identified and shortened
		if ( $overallLength > $max_length ) {
			for ( $i = count( $arr )-1; 0 <= $i; $i-- ) {
				// can't shorten?
				if ( $arr[$i] === "" || $arr[$i] === SUMMARY_CONTINUATION_STR ) {
					continue;
				}
				// still too long?
				elseif ( $overallLength > $max_length ) {
					$lengthOfElement = strlen( $arr[$i] );
					$diffLength = $max_length - $overallLength;
					// to little space left?
					if ($diffLength < 0) {
						$diffLength -= $contLength;
						$matchLength = $lengthOfElement + $diffLength;
						$chopped = mb_strcut( $arr[$i], 0, $matchLength );
						$chopped = preg_replace('/(%[c-f][0-9a-f])(%[89ab][0-9a-f])*(%[89ab]?)?$/', '', $chopped );
						$chopped .= SUMMARY_CONTINUATION_STR;
						$overallLength += strlen( $chopped ) - $lengthOfElement;
						$arr[$i] = $chopped;
					}
				}
			}
		}
		return implode('|', $arr);
	}


	/**
	 * Pretty formating of autocomments.
	 *
	 * @param string $comment reference to the finalized autocomment
	 * @param string $pre the string before the autocomment
	 * @param string $auto the autocomment unformatted
	 * @param string $post the string after the autocomment
	 * @param Titel $title use for further information
	 * @param boolean $local shall links be genreted locally or globally
	 */
	public static function doFormatAutocomments( $comment, $pre, $auto, $post, $title, $local ) {
		global $wgLang;

		// Note that it can be necessary to check the title object and/or item before any
		// other code is run in this callback. If it is possible to avoid loading the whole
		// page then the code will be lighter on the server. Present code formats autocomment
		// after detecting a legal message key, and without using the title or page.

		// our first prerequisite to process this comment is to have the following form
		$matches = array(); //not really needed, just to show whats happening
		if ( preg_match( '/^([\-\w]+):(.*)$/', $auto, $matches ) ) {
			// then we check each initial part if it is a key in the array
			// that is we loop over an array with only two elements
			$messages = \Wikibase\Settings::get( 'apiFormatMessages' );
			foreach ( $messages as $key => $msgs ) {

				// if it matches one key we can procede
				if ( isset( $msgs[$matches[1]] ) ) {

					// keep the replacement message name for later..
					$msg = $msgs[$matches[1]];

					// and the messages used as wrappers and joiners for the head part
					$headWrapper = wfMessage( 'wikibase-api-summary-wrapper-' . $key );

					// and the messages used as wrappers and joiners for the tail part
					$tailWrapper = wfMessage( 'wikibase-api-summary-wrapper' );

					// turn our args into an array
					$args = explode( SUMMARY_GROUPING_STR, $matches[2] );

					// and pop the head and format each element
					$f = function( $v ) use ( $headWrapper ) {
						$headmsg = clone $headWrapper;
						return $headmsg->params( trim(FormatSummary::stringStuffingReverse($v)) )->text();
					};
					$head = array_map( $f, explode( SUMMARY_SUBGROUPING_STR, $args[0] ) );

					// make a unique list of the remaining args
					array_shift( $args );
					$tail = array();
					$g = function( $v ) use ( $tailWrapper ) {
						$tailMessage = clone $tailWrapper;
						$v = trim( FormatSummary::stringStuffingReverse($v) );
						// mb_ereg can't be anchored, so this is easier
						$str = null;
						if ( $v !== "" ) {
							$char = mb_substr( $v, mb_strlen( $v ) -1, 1 );
							if ( $char === SUMMARY_CONTINUATION_STR ) {
								$str = $tailMessage->params( mb_substr( $v, 0, mb_strlen( $v ) - 1 ), $char )->text();
							}
							else {
								$str =  $tailMessage->params( $v, '' )->text();
							}
						}
						return $str ? $str : $tailMessage->params( '', '' )->text();
					};
					foreach ( $args as $arg ) {
						$tail = array_merge( $tail, array_map( $g, explode( SUMMARY_SUBGROUPING_STR, $arg ) ) );
					}

					// build the core message
					$auto = wfMessage( $msg,
						count( $head ),
						$wgLang->commaList( $head ),
						count( $tail ),
						$wgLang->commaList( $tail )
					)->escaped(); // to show where the parenthesis are

					if ( $pre ) {
						# written summary $presep autocomment (summary /* section */)
						$pre .= wfMessage( 'autocomment-prefix', array( 'escapenoentities', 'content' ) )->escaped();
					}

					if ( $post ) {
						# autocomment $postsep written summary (/* section */ summary)
						$auto .= wfMessage( 'colon-separator', array( 'escapenoentities', 'content' ) )->escaped();
					}

					$auto = '<span class="autocomment">' . $auto . '</span>';
					$comment = $pre . $wgLang->getDirMark() . '<span dir="auto">' . $auto . $post . '</span>';
				}
			}
		}
		return true;
	}

}