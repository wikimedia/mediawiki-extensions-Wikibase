<?php

namespace Wikibase\Lib;

use DataValues\TimeValue;
use Html;
use InvalidArgumentException;
use ValueFormatters\FormatterOptions;
use ValueFormatters\TimeFormatter;
use ValueFormatters\ValueFormatter;
use ValueFormatters\ValueFormatterBase;

/**
 * Formatter for rendering the details of a TimeValue (most useful for diffs) in HTML.
 *
 * @since 0.5
 *
 * @licence GNU GPL v2+
 * @author Daniel Kinzler
 * @author Thiemo Mättig
 */
class TimeDetailsFormatter extends ValueFormatterBase {

	/**
	 * @var TimeFormatter
	 */
	private $timeFormatter;

	/**
	 * @param FormatterOptions|null $options
	 */
	public function __construct( FormatterOptions $options = null ) {
		parent::__construct( $options );

		$this->defaultOption(
			TimeFormatter::OPT_TIME_ISO_FORMATTER,
			new MwTimeIsoFormatter( $this->options )
		);

		$this->timeFormatter = new TimeFormatter( $this->options );
	}

	/**
	 * Generates HTML representing the details of a TimeValue,
	 * as an itemized list.
	 *
	 * @since 0.5
	 *
	 * @param TimeValue $value
	 *
	 * @throws InvalidArgumentException
	 * @return string HTML
	 */
	public function format( $value ) {
		if ( !( $value instanceof TimeValue ) ) {
			throw new InvalidArgumentException( 'Data value type mismatch. Expected an TimeValue.' );
		}

		$html = '';
		$html .= Html::element(
			'h4',
			array( 'class' => 'wb-details wb-time-details wb-time-rendered' ),
			$this->timeFormatter->format( $value )
		);
		$html .= Html::openElement( 'table', array( 'class' => 'wb-details wb-time-details' ) );

		$html .= $this->getFieldHtml(
			'isotime',
			$this->getTimeHtml( $value->getTime() )
		);
		$html .= $this->getFieldHtml(
			'timezone',
			$this->getTimezoneHtml( $value->getTimezone() )
		);
		$html .= $this->getFieldHtml(
			'calendar',
			$this->getCalendarModelHtml( $value->getCalendarModel() )
		);
		// TODO: Provide "nice" rendering of precision, etc.
		$html .= $this->getFieldHtml(
			'precision',
			$this->getAmountAndPrecisionHtml( $value->getPrecision() )
		);
		$html .= $this->getFieldHtml(
			'before',
			$this->getAmountAndPrecisionHtml( $value->getPrecision(), $value->getBefore() )
		);
		$html .= $this->getFieldHtml(
			'after',
			$this->getAmountAndPrecisionHtml( $value->getPrecision(), $value->getAfter() )
		);

		$html .= Html::closeElement( 'table' );

		return $html;
	}

	/**
	 * @param string $time
	 *
	 * @return string HTML
	 */
	private function getTimeHtml( $time ) {
		// Loose check if the ISO-like string contains at least year, month, day and hour.
		if ( !preg_match( '/^([-+]?)(\d+)(-\d+-\d+T\d+(?::\d+)*)Z?$/i', $time, $matches ) ) {
			return htmlspecialchars( $time );
		}

		// Actual MINUS SIGN (U+2212) instead of HYPHEN-MINUS (U+002D)
		$sign = $matches[1] === '-' ? "\xE2\x88\x92" : '+';
		// Warning, never cast the year to integer to not run into 32-bit integer overflows!
		$year = ltrim( $matches[2], '0' );
		// Keep the sign. Pad the year. Keep month, day, and time. Drop the trailing "Z".
		return htmlspecialchars( $sign . str_pad( $year, 4, '0', STR_PAD_LEFT ) . $matches[3] );
	}

	/**
	 * @param int $timezone
	 *
	 * @return string HTML
	 */
	private function getTimezoneHtml( $timezone ) {
		// Actual MINUS SIGN (U+2212) instead of HYPHEN-MINUS (U+002D)
		$sign = $timezone < 0 ? "\xE2\x88\x92" : '+';
		$hour = floor( abs( $timezone ) / 60 );
		$minute = abs( $timezone ) - $hour * 60;
		return $sign . sprintf( '%02d:%02d', $hour, $minute );
	}

	/**
	 * @param string $calendarModel
	 *
	 * @return string HTML
	 */
	private function getCalendarModelHtml( $calendarModel ) {
		switch ( $calendarModel ) {
			case TimeFormatter::CALENDAR_GREGORIAN:
				$key = 'valueview-expert-timevalue-calendar-gregorian';
				break;
			case TimeFormatter::CALENDAR_JULIAN:
				$key = 'valueview-expert-timevalue-calendar-julian';
				break;
			default:
				return htmlspecialchars( $calendarModel );
		}

		return htmlspecialchars( $this->msg( $key ) );
	}

	/**
	 * @param int $precision
	 * @param int $amount
	 *
	 * @return string HTML
	 */
	private function getAmountAndPrecisionHtml( $precision, $amount = 1 ) {
		if ( !is_int( $precision ) ) {
			// Fail-safe, either return precision or amount (if specified) as it is
			return htmlspecialchars( $amount === 1 ? $precision : $amount );
		}

		$key = 'years';

		switch ( $precision ) {
			case TimeValue::PRECISION_MONTH: $key = 'months'; break;
			case TimeValue::PRECISION_DAY: $key = 'days'; break;
			case TimeValue::PRECISION_HOUR: $key = 'hours'; break;
			case TimeValue::PRECISION_MINUTE: $key = 'minutes'; break;
			case TimeValue::PRECISION_SECOND: $key = 'seconds'; break;
		}

		if ( $precision < TimeValue::PRECISION_YEAR ) {
			// PRECISION_10a becomes 10 years, PRECISION_100a becomes 100 years, and so on.
			$precisionInYears = pow( 10, TimeValue::PRECISION_YEAR - $precision );
			$amount *= $precisionInYears;
		} elseif ( $precision > TimeValue::PRECISION_SECOND ) {
			// Sub-second precisions become 0.1 seconds, 0.01 seconds, and so on.
			$precisionInSeconds = pow( 10, $precision - TimeValue::PRECISION_SECOND );
			$amount /= $precisionInSeconds;
		}

		$lang = $this->getOption( ValueFormatter::OPT_LANG );
		$msg = wfMessage( $key, $amount )->inLanguage( $lang );
		return htmlspecialchars( $msg->text() );
	}

	/**
	 * @param string $fieldName
	 * @param string $valueHtml
	 *
	 * @return string HTML
	 */
	private function getFieldHtml( $fieldName, $valueHtml ) {
		// Messages:
		// wikibase-timedetails-isotime
		// wikibase-timedetails-timezone
		// wikibase-timedetails-calendar
		// wikibase-timedetails-precision
		// wikibase-timedetails-before
		// wikibase-timedetails-after
		$key = 'wikibase-timedetails-' . strtolower( $fieldName );

		$html = Html::openElement( 'tr' );

		$html .= Html::element( 'th', array( 'class' => 'wb-time-' . $fieldName ),
			$this->msg( $key ) );
		$html .= Html::rawElement( 'td', array( 'class' => 'wb-time-' . $fieldName ),
			$valueHtml );

		$html .= Html::closeElement( 'tr' );

		return $html;
	}

	/**
	 * @param string $key
	 *
	 * @return string
	 */
	private function msg( $key ) {
		$lang = $this->getOption( ValueFormatter::OPT_LANG );
		$msg = wfMessage( $key )->inLanguage( $lang );
		return $msg->text();
	}

}
