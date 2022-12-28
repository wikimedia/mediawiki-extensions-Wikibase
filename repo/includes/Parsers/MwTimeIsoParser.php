<?php

namespace Wikibase\Repo\Parsers;

use DataValues\TimeValue;
use Language;
use MediaWiki\MediaWikiServices;
use RuntimeException;
use ValueParsers\CalendarModelParser;
use ValueParsers\IsoTimestampParser;
use ValueParsers\ParseException;
use ValueParsers\ParserOptions;
use ValueParsers\StringValueParser;
use ValueParsers\ValueParser;

/**
 * Class to parse values that can be formatted by MWTimeIsoFormatter
 * This includes parsing of localized values
 *
 * @license GPL-2.0-or-later
 * @author Addshore
 * @author Marius Hoch
 *
 * @todo move me to DataValues-time
 */
class MwTimeIsoParser extends StringValueParser {

	private const FORMAT_NAME = 'mw-time-iso';

	/**
	 * @var array message keys showing the number of 0s that need to be appended to years when
	 *      parsed with the given message keys
	 */
	private const PRECISION_MSG_KEYS = [
		TimeValue::PRECISION_YEAR1G => [
			'wikibase-time-precision-Gannum',
			'wikibase-time-precision-BCE-Gannum',
		],
		TimeValue::PRECISION_YEAR1M => [
			'wikibase-time-precision-Mannum',
			'wikibase-time-precision-BCE-Mannum',
		],
		TimeValue::PRECISION_YEAR1K => [
			'wikibase-time-precision-millennium',
			'wikibase-time-precision-BCE-millennium',
		],
		TimeValue::PRECISION_YEAR100 => [
			'wikibase-time-precision-century',
			'wikibase-time-precision-BCE-century',
		],
		TimeValue::PRECISION_YEAR10 => [
			'wikibase-time-precision-annum',
			'wikibase-time-precision-BCE-annum',
			'wikibase-time-precision-10annum',
			'wikibase-time-precision-BCE-10annum',
		],
	];

	private const PADDED_ZEROS = [
		TimeValue::PRECISION_YEAR1G => 9,
		TimeValue::PRECISION_YEAR1M => 6,
		TimeValue::PRECISION_YEAR1K => 3,
		TimeValue::PRECISION_YEAR100 => 2,
		TimeValue::PRECISION_YEAR10 => 0,
	];

	/**
	 * @var Language
	 */
	private $lang;

	/**
	 * @var ValueParser
	 */
	private $isoTimestampParser;

	/**
	 * @see StringValueParser::__construct
	 *
	 * @param ParserOptions|null $options
	 */
	public function __construct( ParserOptions $options = null ) {
		parent::__construct( $options );

		$this->lang = MediaWikiServices::getInstance()->getLanguageFactory()->getLanguage( $this->getOption( ValueParser::OPT_LANG ) );
		$this->isoTimestampParser = new IsoTimestampParser(
			new CalendarModelParser( $this->options ),
			$this->options
		);
	}

	/**
	 * Parses the provided string and returns the result.
	 *
	 * @param string $value
	 *
	 * @throws ParseException
	 * @return TimeValue
	 */
	protected function stringParse( $value ) {
		$reconverted = $this->reconvertOutputString( $value, $this->lang );
		if ( $reconverted === false && $this->lang->getCode() !== 'en' ) {
			// Also try English
			$enLang = MediaWikiServices::getInstance()->getLanguageFactory()->getLanguage( 'en' );
			$reconverted = $this->reconvertOutputString( $value, $enLang );
		}
		if ( $reconverted !== false ) {
			return $reconverted;
		}

		throw new ParseException( 'Failed to parse', $value, self::FORMAT_NAME );
	}

	/**
	 * Analyzes a string if it is a time value that has been specified in one of the output
	 * precision formats specified in the settings. If so, this method re-converts such an output
	 * string to an object that can be used to instantiate a time.Time object.
	 *
	 * @param string $value
	 * @param Language $lang
	 *
	 * @throws RuntimeException
	 * @return TimeValue|bool
	 */
	private function reconvertOutputString( $value, Language $lang ) {
		foreach ( self::PRECISION_MSG_KEYS as $precision => $msgKeysGroup ) {
			foreach ( $msgKeysGroup as $msgKey ) {
				$res = $this->parseFromOutputString(
					$lang,
					$value,
					$precision,
					$msgKey
				);
				if ( $res !== null ) {
					return $res;
				}
			}
		}

		return false;
	}

	/**
	 * @param Language $lang
	 * @param string $value
	 * @param int $precision
	 * @param string $msgKey
	 *
	 * @return TimeValue|bool|null
	 */
	private function parseFromOutputString( Language $lang, $value, $precision, $msgKey ) {
		$msgText = $lang->getMessage( $msgKey );

		if ( strpos( $msgText, '$1' ) === false || $msgText === '$1' ) {
			return null;
		}

		$isBceMsg = $this->isBceMsg( $msgKey );
		$msgRegexps = $this->getRegexpsFromMessageText( $msgText );

		foreach ( $msgRegexps as $msgRegexp ) {
			if ( preg_match(
				'@^\s*' . $msgRegexp . '\s*$@i',
				$value,
				$matches
			) ) {
				return $this->chooseAndParseNumber(
					$lang,
					array_slice( $matches, 1 ),
					$precision,
					$isBceMsg
				);
			}
		}

		return null;
	}

	/**
	 * Creates regular expression snippets from a given message.
	 * This replaces $1 with (.+?) and also expands PLURAL clauses
	 * so that we can match for every combination of these.
	 * Callers should try each returned regex in turn.
	 *
	 * @param string $msgText
	 * @return string[]
	 */
	private function getRegexpsFromMessageText( string $msgText ): array {
		static $pluralRegex = null;
		if ( $pluralRegex === null ) {
			// We need to match on a preg_quoted string here, so double quote
			$pluralRegex = '@' . preg_quote( preg_quote( '{{PLURAL:$1|' ) ) .
				'.*?' . preg_quote( preg_quote( '}}' ) ) . '@';
		}

		$regexes = [ $msgText ];

		foreach ( $regexes as $regex ) {
			// If the msg string contains tags, also try parsing without them
			$regexWithoutTags = strip_tags( $regex );
			if ( $regexWithoutTags !== $regex ) {
				$regexes[] = $regexWithoutTags;
			}
		}

		foreach ( $regexes as &$regex ) {
			// Quote regexp
			$regex = preg_quote( $regex, '@' );

			// Expand the PLURAL cases
			$regex = preg_replace_callback(
				$pluralRegex,
				function ( $matches ) {
					// Change "{{PLURAL:$1" to "(?:" and "}}" to ")"
					$replace = str_replace( '\{\{PLURAL\:\$1\|', '(?:', $matches[0] );
					$replace = str_replace( '\}\}', ')', $replace );

					// Unescape the pipes within the PLURAL clauses
					return str_replace( '\|', '|', $replace );
				},
				$regex
			);

			// Make sure we match for all $1s
			$regex = str_replace( '\$1', '(.+?)', $regex );
		}

		unset( $regex ); // remove referenceness

		foreach ( $regexes as $regex ) {
			// If the msg string ends with BCE also check for BC
			if ( substr_compare( $regex, 'BCE', -3 ) === 0 ) {
				$regexes[] = substr( $regex, 0, -1 );
			}
		}

		return $regexes;
	}

	/**
	 * Tries to find the number from the given matches and parses it.
	 * This naively assumes the first parseable number to be the best match.
	 *
	 * @param Language $lang
	 * @param string[] $matches
	 * @param int $precision
	 * @param boolean $isBceMsg
	 *
	 * @return TimeValue|bool
	 */
	private function chooseAndParseNumber( Language $lang, $matches, $precision, $isBceMsg ) {
		$year = null;
		foreach ( $matches as $number ) {
			if ( $number === '' ) {
				continue;
			}
			$number = $lang->parseFormattedNumber( $number );
			$year = $number . str_repeat( '0', self::PADDED_ZEROS[$precision] );

			if ( ctype_digit( $year ) ) {
				// IsoTimestampParser works only with digit only years (it uses \d{1,16} to match)
				break;
			}
			$year = null;
		}

		if ( $year === null ) {
			return false;
		}

		$this->setPrecision( $precision );

		return $this->getTimeFromYear( $year, $isBceMsg );
	}

	/**
	 * @param string $msgKey
	 *
	 * @return boolean
	 */
	private function isBceMsg( $msgKey ) {
		return strstr( $msgKey, '-BCE-' );
	}

	/**
	 * @param string $year
	 * @param bool $isBce
	 *
	 * @return TimeValue
	 */
	private function getTimeFromYear( $year, $isBce ) {
		$sign = $isBce ? '-' : '+';
		$timeString = $sign . $year . '-00-00T00:00:00Z';
		return $this->isoTimestampParser->parse( $timeString );
	}

	/**
	 * @param int $precision
	 */
	private function setPrecision( $precision ) {
		$this->isoTimestampParser->getOptions()->setOption(
			IsoTimestampParser::OPT_PRECISION,
			$precision
		);
	}

}
