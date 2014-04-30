<?php

namespace Wikibase\Lib;

use DataValues\TimeValue;
use InvalidArgumentException;
use Language;
use Message;
use ValueFormatters\FormatterOptions;
use ValueFormatters\ValueFormatter;
use ValueFormatters\ValueFormatterBase;

/**
 * @since 0.4
 *
 * @licence GNU GPL v2+
 * @author H. Snater < mediawiki@snater.com >
 * @author Adam Shorland
 * @author Thiemo Mättig
 *
 * @todo move me to DataValues-time
 */
class MwTimeIsoFormatter extends ValueFormatterBase {

	/**
	 * MediaWiki language object.
	 * @var Language
	 */
	private $language;

	/**
	 * @param FormatterOptions $options
	 */
	public function __construct( FormatterOptions $options ) {
		$this->options = $options;

		$this->options->defaultOption( ValueFormatter::OPT_LANG, 'en' );

		$this->language = Language::factory(
			$this->options->getOption( ValueFormatter::OPT_LANG )
		);
	}

	/**
	 * @see ValueFormatter::format
	 */
	public function format( $value ) {
		if ( !( $value instanceof TimeValue ) ) {
			throw new InvalidArgumentException( 'Value is not a TimeValue.' );
		}

		return $this->formatTimeValue( $value );
	}

	private function formatTimeValue( TimeValue $timeValue ) {
		$isoTime = $timeValue->getTime();
		$precision = $timeValue->getPrecision();

		if ( !preg_match(
			'/^([-+]?)(\d+)\D+(\d+)\D+(\d+)\D+(\d+)\D+(\d+)\D+(\d+)/',
			$isoTime,
			$matches
		) ) {
			return $isoTime;
		}
		list( , $sign, $fullYear, $month, $day, $hour, $minute, $second ) = $matches;

		if ( intval( $fullYear ) === 0
			|| ( intval( $month ) === 0 && $precision >= TimeValue::PRECISION_MONTH )
			|| ( intval( $day ) === 0 && $precision >= TimeValue::PRECISION_DAY )
		) {
			return $isoTime;
		}

		$year = substr( $fullYear, -4 );
		$mwTimestamp = sprintf(
			'%04d%02d%02d%02d%02d%02d',
			$year,
			max( 1, $month ),
			max( 1, $day ),
			$hour, $minute, $second
		);

		$localisedDate = $this->language->sprintfDate(
			$this->getDateFormat( $precision ),
			$mwTimestamp
		);

		// We do not handle parsing arabic, farsi, etc. digits (bug 63732)
		$normalisedDate = $this->normaliseDigits( $localisedDate );

		// If we can't reliably fix the year, return the full timestamp.
		// This should never happen as sprintfDate should always return a 4 digit year.
		if ( !$this->canFormatYear( $normalisedDate, $year ) ) {
			return $isoTime;
		}

		$isBCE = $sign === '-';
		$formattedDate = str_replace(
			$year,
			$this->formatYear( $fullYear, $precision, $isBCE ),
			$normalisedDate
		);

		return $formattedDate;
	}

	/**
	 * @param string $date
	 *
	 * @return string
	 */
	private function normaliseDigits( $date ) {
		return $this->language->parseFormattedNumber( $date );
	}

	/**
	 * @param string $date
	 * @param string $year
	 *
	 * @return bool
	 */
	private function canFormatYear( $date, $year ) {
		return substr_count( $date, $year ) === 1;
	}

	/**
	 * Get the dateformat string for the given precision to be used by sprintfDate
	 *
	 * @param int $precision
	 *
	 * @return string dateFormat to be used by sprintfDate
	 */
	private function getDateFormat( $precision ) {
		if ( $precision <= TimeValue::PRECISION_YEAR ) {
			return 'Y';
		}

		if ( $precision === TimeValue::PRECISION_MONTH ) {
			return 'F Y';
		}

		return 'j F Y';
	}

	/**
	 * @param string $year
	 * @param int $precision
	 * @param bool $isBCE
	 *
	 * @return string the formatted year
	 */
	private function formatYear( $year, $precision, $isBCE ) {
		$round = null;
		$splice = 0;
		if ( $isBCE ) {
			$msgPrefix = 'wikibase-time-precision-BCE';
		} else {
			$msgPrefix = 'wikibase-time-precision';
		}

		switch ( $precision ) {
			case TimeValue::PRECISION_Ga:
				$msg = 'Gannum';
				$round = -9;
				$splice = -9;
				break;
			case TimeValue::PRECISION_100Ma:
				$msg = 'Mannum';
				$round = -8;
				$splice = -6;
				break;
			case TimeValue::PRECISION_10Ma:
				$msg = 'Mannum';
				$round = -7;
				$splice = -6;
				break;
			case TimeValue::PRECISION_Ma:
				$msg = 'Mannum';
				$round = -6;
				$splice = -6;
				break;
			case TimeValue::PRECISION_100ka:
				$msg = 'annum';
				$round = -5;
				break;
			case TimeValue::PRECISION_10ka:
				$msg = 'annum';
				$round = -4;
				break;
			case TimeValue::PRECISION_ka:
				$msg = 'millennium';
				$round = -3;
				$splice = -3;
				break;
			case TimeValue::PRECISION_100a:
				$msg = 'century';
				$round = -2;
				$splice = -2;
				break;
			case TimeValue::PRECISION_10a:
				$msg = '10annum';
				$round = -1;
				break;
			default:
				$year = ltrim( $year, '0' );
				if ( $isBCE ) {
					$year .= ' BCE';
				}
				return $year;
		}

		if ( $round !== null ) {
			$year = round( $year, $round );
		}
		if ( $splice < 0 ) {
			$year = substr( $year, 0, $splice );
		}
		return $this->getMessage( 'wikibase-time-precision-' . ( $isBCE ? 'BCE-' : '' ) . $msg, $year );
	}

	/**
	 * @param string $key
	 * @param string $fullYear
	 * @return String
	 */
	private function getMessage( $key, $fullYear ) {
		$message = new Message( $key );
		//FIXME: as the frontend can not parse the translated precisions we only want to present the ENGLISH for now
		//once the frontend is using backend parsers we can switch the translation on
		//See the fix me in: MwTimeIsoParser::reconvertOutputString
		//$message->inLanguage( $this->language );
		$message->inLanguage( new Language() );
		$message->params( array( $fullYear ) );
		return $message->text();
	}

}
