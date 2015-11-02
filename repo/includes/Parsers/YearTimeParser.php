<?php

namespace Wikibase\Repo\Parsers;

use DataValues\TimeValue;
use ValueParsers\CalendarModelParser;
use ValueParsers\EraParser;
use ValueParsers\IsoTimestampParser;
use ValueParsers\ParseException;
use ValueParsers\ParserOptions;
use ValueParsers\StringValueParser;
use ValueParsers\ValueParser;

/**
 * @since 0.5
 *
 * @licence GNU GPL v2+
 * @author Adam Shorland
 * @author Thiemo Mättig
 *
 * @todo move me to DataValues-time
 */
class YearTimeParser extends StringValueParser {

	const FORMAT_NAME = 'year';

	const OPT_DIGIT_GROUP_SEPARATOR = 'digitGroupSeparator';
	const CANONICAL_DIGIT_GROUP_SEPARATOR = ',';

	/**
	 * @var ValueParser
	 */
	private $eraParser;

	/**
	 * @var ValueParser
	 */
	private $isoTimestampParser;

	/**
	 * @param ValueParser|null $eraParser
	 * @param ParserOptions|null $options
	 */
	public function __construct( ValueParser $eraParser = null, ParserOptions $options = null ) {
		parent::__construct( $options );

		$this->defaultOption(
			self::OPT_DIGIT_GROUP_SEPARATOR,
			self::CANONICAL_DIGIT_GROUP_SEPARATOR
		);

		$this->eraParser = $eraParser ?: new EraParser( $this->options );
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
		list( $sign, $year ) = $this->eraParser->parse( $value );

		// Negative dates usually don't have a month, assume non-digits are thousands separators
		if ( $sign === '-' ) {
			$separator = $this->getOption( self::OPT_DIGIT_GROUP_SEPARATOR );
			// Always accept ISO (e.g. "1 000 BC") as well as programming style (e.g. "-1_000")
			$year = preg_replace(
				'/(?<=\d)[' . preg_quote( $separator, '/' ) . '\s_](?=\d)/',
				'',
				$year
			);
		}

		if ( !preg_match( '/^\d+$/', $year ) ) {
			throw new ParseException( 'Failed to parse year', $value, self::FORMAT_NAME );
		}

		return $this->isoTimestampParser->parse( $sign . $year . '-00-00T00:00:00Z' );
	}

}
