<?php

namespace Wikibase\Lib;

use DataValues\TimeValue;
use InvalidArgumentException;
use Language;
use Message;
use RangeException;
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

		if ( ( intval( $fullYear ) === 0 && $precision < TimeValue::PRECISION_YEAR )
			|| ( intval( $month ) === 0 && $precision >= TimeValue::PRECISION_MONTH )
			|| ( intval( $day ) === 0 && $precision >= TimeValue::PRECISION_DAY )
		) {
			return $isoTime;
		}

		$mwTimestamp = sprintf(
			'%04d%02d%02d%02d%02d%02d',
			substr( $fullYear, -4 ),
			max( 1, $month ),
			max( 1, $day ),
			$hour, $minute, $second
		);
		$year = substr( $mwTimestamp, 0, 4 );

		$localisedDate = $this->language->sprintfDate(
			$this->getDateFormat( $precision ),
			$mwTimestamp
		);

		// We do not handle parsing arabic, farsi, etc. digits (bug 63732)
		$normalisedDate = $this->normaliseDigits( $localisedDate );

		try {
			$formatedYear = $this->formatYear( $sign, $fullYear, $precision );

			if ( $formatedYear !== $year ) {
				// If we can't reliably fix the year, return the full timestamp.
				// This should never happen as sprintfDate should always return a 4 digit year.
				if ( substr_count( $normalisedDate, $year ) !== 1 ) {
					return $isoTime;
				}

				$normalisedDate = str_replace( $year, $formatedYear, $normalisedDate );
			}
		} catch ( RangeException $ex ) {
			return $isoTime;
		}

		return $normalisedDate;
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
	 * @param string $sign
	 * @param string $year
	 * @param int $precision
	 *
	 * @return string the formatted year
	 */
	private function formatYear( $sign, $year, $precision ) {
		$isBCE = $sign === '-';
		$shift = 1e-0;
		$round = 0;

		switch ( $precision ) {
			case TimeValue::PRECISION_Ga:
				$msg = 'Gannum';
				$shift = 1e-9;
				break;
			case TimeValue::PRECISION_100Ma:
				$msg = 'Mannum';
				$shift = 1e-6;
				$round = -2;
				break;
			case TimeValue::PRECISION_10Ma:
				$msg = 'Mannum';
				$shift = 1e-6;
				$round = -1;
				break;
			case TimeValue::PRECISION_Ma:
				$msg = 'Mannum';
				$shift = 1e-6;
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
				$shift = 1e-3;
				break;
			case TimeValue::PRECISION_100a:
				$msg = 'century';
				$shift = 1e-2;
				break;
			case TimeValue::PRECISION_10a:
				$msg = '10annum';
				$round = -1;
				break;
		}

		$roundedYear = round( $year * $shift, $round );
		if ( empty( $roundedYear )
			&& ( $precision < TimeValue::PRECISION_YEAR
				|| ( $precision === TimeValue::PRECISION_YEAR && $isBCE )
			)
		) {
			throw new RangeException( 'Year to small for precision.' );
		}

		if ( empty( $msg ) ) {
			// TODO: This needs a message.
			return $roundedYear . ( $isBCE ? ' BCE' : '' );
		}

		return $this->getMessage(
			'wikibase-time-precision-' . ( $isBCE ? 'BCE-' : '' ) . $msg,
			$roundedYear
		);
	}

	/**
	 * @param string $key
	 * @param string $param
	 *
	 * @return string
	 */
	private function getMessage( $key, $param ) {
		$message = new Message( $key );
		// FIXME: As the frontend can not parse the translated precisions we only want to present
		// the English for now. Once the frontend is using backend parsers we can turn the
		// translation on. See the FIXME in MwTimeIsoParser::reconvertOutputString.
		// $message->inLanguage( $this->language );
		$message->inLanguage( new Language() );
		$message->params( array( $param ) );
		return $message->text();
	}

}
