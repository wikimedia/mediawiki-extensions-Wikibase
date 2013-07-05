<?php

namespace Wikibase;

use Language;
use DataValues\StringValue;
use Wikibase\Repo\WikibaseRepo;

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
 * @author Daniel Kinzler
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
	const USE_ALL = 6;

	/**
	 * Constructs a new Summary
	 *
	 * @since 0.4
	 *
	 * @param string     $moduleName  the module part of the autocomment
	 * @param string     $actionName  the action part of the autocomment
	 * @param string     $language    the language to use as the second autocomment argument
	 * @param array      $commentArgs the arguments to the autocomment
	 * @param array|bool $summaryArgs the arguments to the autosummary
	 */
	public function __construct( $moduleName = null, $actionName = null, $language = null, $commentArgs = array(), $summaryArgs = false ) {
		$this->moduleName = $moduleName;
		$this->actionName = $actionName;
		$this->language = $language === null ? null : (string)$language;
		$this->commentArgs = $commentArgs;
		$this->summaryArgs = $summaryArgs;
	}

	/**
	 * Set the language code to use as the second autocomment argument
	 *
	 * @since 0.4
	 *
	 * @param string $lang the language code
	 */
	public function setLanguage( $lang = null ) {
		$this->language = $lang === null ? null : (string)$lang;
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
		$this->actionName = $name === null ? null : (string)$name;
	}

	/**
	 * Get the action part of the autocomment
	 *
	 * @since 0.4
	 *
	 * @return string|null
	 */
	public function getActionName() {
		return $this->actionName;
	}

	/**
	 * Get the language part of the autocomment
	 *
	 * @since 0.4
	 *
	 * @return string|null
	 */
	public function getLanguageCode() {
		return $this->language;
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
	 * @since 0.4
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
	 * Add autocomment arguments. Note that the first argument is always the
	 * number of summary arguments supplied via addAutoSummaryArgs() (or the constructor),
	 * and the second one is always the language code supplied via setLanguage()
	 * (or the constructor). Any values added using addAutoCommentArgs() will be added#
	 * to the canonical two arguments.
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
	 * @since 0.4
	 *
	 * @param array $parts parts to be stringed together
	 *
	 * @return string The $parts concatenated
	 */
	public static function formatAutoSummary( $parts ) {
		global $wgContLang;

		if ( $parts === false ) {
			return '';
		}
		elseif ( is_array( $parts ) ) {
			$count = count( $parts );

			if ( $count === 0 ) {
				return '';
			}
			else {
				// @todo have some sort of key value formatter
				return $wgContLang->commaList( $parts );
			}
		}
		else {
			throw new \MWException( 'wrong type' );
		}
	}

	/**
	 * Add to the summary part
	 *
	 * @since 0.4
	 *
	 * @param array|strings... parts to be stringed together
	 */
	public function addAutoSummaryArgs( /*...*/ ) {
		$args = is_array( func_get_arg( 0 ) ) ? func_get_arg( 0 ) : func_get_args();
		$strings = array();

		foreach ( $args as $arg ) {
			$strings[] = $this->formatArg( $arg );
		}

        $this->summaryArgs = array_merge(
            $this->summaryArgs === false ? array() : $this->summaryArgs,
            array_filter( $strings, function ( $str ) { return 0 < strlen( $str ); } )
        );
	}

	/**
	 * Format an autosummary argument
	 *
	 * @since 0.4
	 *
	 * @param mixed $arg
	 *
	 * @return string
	 */
	protected function formatArg( $arg ) {
		global $wgContLang;

		$string = '';

		if ( is_string( $arg ) ) {
			$entityId = EntityId::newFromPrefixedId( $arg );
			if ( $entityId instanceof EntityId ) {
				$arg = $entityId;
			}
		}

		// if we find that any arg is an object we shall not display them
		switch ( true ) {
			case is_array( $arg ):
				$string = '';

				$key = key( $arg );
				$value = $arg[$key];

				if ( !is_int( $key ) ) {
					// @todo i18n for colon in onFormat
					$string .= $this->formatArg( $key ) . ': ';
				}

				// @todo this is crufty!
				$dataValueStrings = array();

				if ( is_array( $value ) ) {
					foreach( $value as $i ) {
						$dataValueStrings[] = $this->formatArg( $i );
					}

					if ( $dataValueStrings !== array() ) {
						$string .= $wgContLang->commaList( $dataValueStrings );
					}
				} else if ( is_string( $value ) ) {
					$string .= $this->formatArg( $value );

				}
				break;
			case is_string( $arg ):
				$string = $arg;
				break;
			case is_object( $arg ) && ($arg instanceof EntityId):
				$title = \Wikibase\EntityContentFactory::singleton()->getTitleForId( $arg );
				$string = '[[' . $title->getFullText() . ']]';
				break;
			case is_object( $arg ) && ( $arg instanceof StringValue ):
				$string = htmlspecialchars( $arg->getValue() );
				break;
			case is_object( $arg ) && method_exists( $arg, '__toString' ):
				$string = (string)$arg;
				break;
			case is_object( $arg ) && !method_exists( $arg, '__toString' ):
				$string = '';
				break;
			case is_int( $arg ):
				$string = (string) $arg;
				break;
			case $arg === false || $arg === null:
				break;
			default:
				$string = '';
		}

		return $string;
	}

	/**
	 * Get the preformatted autosummary using the object-specific values
	 *
	 * @since 0.4
	 *
	 * @return string the summary part, without the autocomment
	 */
	public function getAutoSummary() {
		return self::formatAutoSummary( $this->summaryArgs );
	}

	/**
	 * Merge the total summary
	 *
	 * @since 0.4
	 *
	 * @param string $comment autocomment part, will be placed in a block comment
	 * @param string $summary human readable string to be appended after the autocomment part
	 * @param int $length max length of the string
	 *
	 * @return string to be used for the summary
	 */
	public static function formatTotalSummary( $comment, $summary, $length = SUMMARY_MAX_LENGTH ) {
		global $wgContLang;
		$normalizer = WikibaseRepo::getDefaultInstance()->getStringNormalizer();

		$comment = $normalizer->trimToNFC( $comment );
		$summary = $normalizer->trimToNFC( $summary );
		$mergedString = '';
		if ( $comment !== '' ) {
			$mergedString .=  "/* $comment */";
		}
		if ( $summary !== "" ) {
			$mergedString .= ($mergedString === "" ? "" : " ") . $wgContLang->truncate( $summary, $length - strlen( $mergedString ) );
		}

		// leftover entities should be removed, but its not clear how this shall be done
		return $mergedString;
	}

	/**
	 * Merge the total summary using the object specific values
	 *
	 * @since 0.4
	 *
	 * @param int $length max length of the summary
	 * @param int $format bitset indicating what to include, see the USE_XXX constants.
	 *
	 * @return string to be used for the summary
	 */
	public function toString( $length = SUMMARY_MAX_LENGTH, $format = self::USE_ALL ) {
		$count = $this->summaryArgs ? count( $this->summaryArgs ) : 0;
		$summary = self::formatAutoSummary( $this->summaryArgs );

		$comment = Summary::formatAutoComment(
			$this->getMessageKey(),
			array_merge(
				array( $count, (string)$this->language ),
				$this->commentArgs
			)
		);

		$normalizer = WikibaseRepo::getDefaultInstance()->getStringNormalizer();

		$comment = ( $format & self::USE_COMMENT) ? $normalizer->trimToNFC( $comment ) : '';
		$summary = ( $format & self::USE_SUMMARY) ? $normalizer->trimToNFC( $summary ) : '';

		$totalSummary = self::formatTotalSummary( $comment, $summary, $length );

		return $totalSummary;
	}

}
