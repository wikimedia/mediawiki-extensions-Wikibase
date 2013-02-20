<?php

namespace Wikibase;

use Language;

/**
 * File defining the handler for autocomments and additional utility functions
 *
 * @since 0.1, major refactoring in 0.4
 *
 * @file
 * @ingroup WikibaseRepo
 *
 * @licence GNU GPL v2+
 * @author John Erling Blad
 */
class Summary {

	/**
	 * @var string
	 */
	protected $moduleName;

	/**
	 * @var string
	 */
	protected $actionName;

	/**
	 * @var Language
	 */
	protected $language;

	/**
	 * @var array
	 */
	protected $commentArgs;

	/**
	 * @var array
	 */
	protected $summaryArgs;

	/**
	 * @var int
	 */
	protected $summaryType;

	/**
	 * indicates a specific type of formatting
	 */
	const USE_COMMENT = 2;
	const USE_SUMMARY = 4;

	/**
	 * Constructs a new Summary
	 *
	 * @since 0.4
	 *
	 * @param string     $moduleName  the module part of the autocomment
	 * @param string     $actionName  the action part of the autocomment
	 * @param Language   $language    the language to use for the autosummary (like list separators)
	 * @param array      $commentArgs the arguments to the autocomment
	 * @param array|bool $summaryArgs the arguments to the autosummary
	 */
	public function __construct( $moduleName = null, $actionName = null, Language $language = null, $commentArgs = array(), $summaryArgs = false ) {
		//global $wgContLang;

		$this->moduleName = $moduleName;
		$this->actionName = $actionName;
		$this->language = isset( $language ) ? $language : null;
		$this->commentArgs = $commentArgs;
		$this->summaryArgs = $summaryArgs;
		$this->formatType = self::USE_COMMENT | self::USE_SUMMARY;
	}

	/**
	 * Set the language for the summary part
	 *
	 * @since 0.4
	 *
	 * @param \Language $lang
	 */
	public function setLanguage( \Language $lang = null ) {
		$this->language = $lang;
	}

	/**
	 * Set the module part of the autocomment
	 *
	 * @since 0.4
	 *
	 * @param string $name
	 */
	public function setModuleName( $name ) {
		$this->moduleName = (string)$name;
	}

	/**
	 * Get the module part of the autocomment
	 *
	 * @since 0.4
	 *
	 * @return string
	 */
	public function getModuleName() {
		return $this->moduleName;
	}

	/**
	 * Set the action part of the autocomment
	 *
	 * @since 0.4
	 *
	 * @param string $name
	 */
	public function setAction( $name ) {
		$this->actionName = (string)$name;
	}

	/**
	 * Get the action part of the autocomment
	 *
	 * @since 0.4
	 *
	 * @return string
	 */
	public function getActionName() {
		return $this->actionName;
	}

	/**
	 * Set the format flags
	 *
	 * @since 0.4
	 *
	 * @param int $flag
	 */
	public function setFormat( $flag ) {
		$this->formatType |= (int)$flag;
	}

	/**
	 * Remove the format flags
	 *
	 * @since 0.4
	 *
	 * @param int $flag
	 */
	public function removeFormat( $flag ) {
		$this->formatType &= ~ (int)$flag;
	}

	/**
	 * Get the formatting
	 *
	 * @since 0.4
	 *
	 * @return string
	 */
	public function getFormat() {
		return $this->formatType;
	}

	/**
	 * Pretty formatting of autocomments.
	 *
	 * See docs/summaries.txt for a brief overview of the format.
	 *
	 * Note that this function does _not_ use $title and $local but
	 * could use them if links should be created that points to something.
	 * Typically this could be links that moves to and highlight some
	 * section within the item itself.
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
	 * Format the message key for an autocomment
	 *
	 * @since 0.3
	 *
	 * @param array $parts parts to be stringed together
	 *
	 * @return string with a message key, or possibly an empty string
	 */
	public static function formatMessageKey( array $parts ) {
		return implode('-', $parts);
	}

	/**
	 * Format the message key using the object-specific values
	 *
	 * @since 0.3
	 *
	 * @return string with a message key, or possibly an empty string
	 */
	public function getMessageKey() {
		return self::formatMessageKey(
			$this->actionName === null
				? array( $this->moduleName )
				: array( $this->moduleName, $this->actionName )
		);
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
	 * Set the autocomment arguments using the object-specific values
	 *
	 * @since 0.4
	 *
	 * @param array|strings... parts to be stringed together
	 */
	public function addAutoCommentArgs( /*...*/ ) {
		$args = is_array( func_get_arg( 0 ) ) ? func_get_arg( 0 ) : func_get_args();
		$this->commentArgs = array_merge(
			$this->commentArgs,
			array_filter( $args, function ( $str ) { return 0 < strlen( $str ); } )
		);
	}

	/**
	 * Get the formatted autocomment using the object-specific values
	 *
	 * @since 0.4
	 *
	 * @return string with a formatted autocomment, or possibly an empty string
	 */
	public function getAutoComment() {
		return self::formatAutoComment( $this->getMessageKey(), $this->commentArgs );
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
	 * @param \Language|null $lang fallback for the language if its not set
	 *
	 * @return array of counts, an escaped string and the used language
	 */
	public static function formatAutoSummary( $parts, $lang = null ) {
		global $wgContLang;

		if ( !isset( $lang ) ) {
			$lang = $wgContLang;
		}

		if ( $parts === false ) {
			$count = $parts;

			return array( 0, '', $lang );
		}
		elseif ( is_array( $parts ) ) {
			$count = count( $parts );

			if ( $count === 0 ) {
				return array( 0, '', $lang );
			}
			else {
				return array( count( $parts ), $lang->commaList( $parts ), $lang );
			}
		}
		else {
			throw new \MWException( 'wrong type' );
		}
	}

	/**
	 * Set the autosummary arguments using the object-specific values
	 *
	 * @since 0.4
	 *
	 * @param array|strings... parts to be stringed together
	 */
	public function addAutoSummaryArgs( /*...*/ ) {
		$args = is_array( func_get_arg( 0 ) ) ? func_get_arg( 0 ) : func_get_args();
		$strings = array();

		foreach ( $args as $arg ) {
			// if we find that any arg is an object we shall not display them
			switch ( true ) {
			case is_string( $arg ):
				$strings[] = $arg;
				break;
			case is_object( $arg ) && ($arg instanceof EntityId):
				$title = \Wikibase\EntityContentFactory::singleton()->getTitleForId( $arg );
				$strings[] = '[[' . $title->getFullText() . ']]';
				break;
			case is_object( $arg ) && method_exists( $arg, '__toString' ):
				$strings[] = (string)$arg;
				break;
			case is_object( $arg ) && !method_exists( $arg, '__toString' ):
				$strings[] = '';
				$this->removeFormat( self::USE_SUMMARY );
				break;
			default:
				$strings[] = '';
				$this->removeFormat( self::USE_SUMMARY );
			}
		}

		$this->summaryArgs = array_merge(
			$this->summaryArgs === false ? array() : $this->summaryArgs,
			array_filter( $strings, function ( $str ) { return 0 < strlen( $str ); } )
		);
	}

	/**
	 * Get the preformatted autosummary using the object-specific values
	 *
	 * @since 0.4
	 *
	 * @param array $parts parts to be stringed together
	 *
	 * @return array of counts, an escaped string and the used language
	 */
	public function getAutoSummary( array $parts ) {
		return self::formatAutoSummary( $this->summaryArgs, $this->language );
	}

	/**
	 * Merge the total summary
	 *
	 * @since 0.1
	 *
	 * @param string $comment initial part to go in a comment
	 * @param string $summary final part that is a easilly trucable string
	 * @param bool|string $lang language to use when truncating the string
	 * @param int $length total length of the string
	 *
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

	/**
	 * Merge the total summary using the object specific values
	 *
	 * @since 0.4
	 *
	 * @return string to be used for the summary
	 */
	public function toString( $length = SUMMARY_MAX_LENGTH ) {
		list( $counts, $summary, $lang) = self::formatAutoSummary(
			$this->summaryArgs,
			$this->language
		);
		$comment = Summary::formatAutoComment(
			$this->getMessageKey(),
			array_merge(
				$this->language === null
					? array( $counts, '' )
					: array( $counts, $this->language->getCode() ),
				$this->commentArgs
			)
		);
		$comment = ( $this->formatType & self::USE_COMMENT) ? Utils::squashToNFC( $comment ) : '';
		$summary = ( $this->formatType & self::USE_SUMMARY) ? Utils::squashToNFC( $summary ) : '';
		return self::formatTotalSummary( $comment, $summary, $lang, $length );
	}

}
