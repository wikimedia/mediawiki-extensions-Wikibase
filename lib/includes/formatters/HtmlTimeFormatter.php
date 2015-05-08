<?php

namespace Wikibase\Lib;

use DataValues\TimeValue;
use InvalidArgumentException;
use ValueFormatters\FormatterOptions;
use ValueFormatters\TimeFormatter;
use ValueFormatters\ValueFormatter;
use ValueFormatters\ValueFormatterBase;

/**
 * @since 0.5
 *
 * @license GNU GPL v2+
 * @author Adrian Lang < adrian.lang@wikimedia.de >
 * @author Thiemo Mättig
 * @author Daniel Kinzler
 */
class HtmlTimeFormatter extends ValueFormatterBase {

	private static $calendarKeys = array(
		TimeFormatter::CALENDAR_GREGORIAN => 'valueview-expert-timevalue-calendar-gregorian',
		TimeFormatter::CALENDAR_JULIAN => 'valueview-expert-timevalue-calendar-julian',
	);

	/**
	 * @var ValueFormatter
	 */
	private $dateTimeFormatter;

	/**
	 * @param FormatterOptions|null $options
	 * @param ValueFormatter $dateTimeFormatter
	 */
	public function __construct( FormatterOptions $options = null, ValueFormatter $dateTimeFormatter ) {
		parent::__construct( $options );

		$this->dateTimeFormatter = $dateTimeFormatter;
	}

	/**
	 * Format a time data value
	 *
	 * @since 0.5
	 *
	 * @param TimeValue $value The time to format
	 *
	 * @return string HTML
	 * @throws InvalidArgumentException
	 */
	public function format( $value ) {
		if ( !( $value instanceof TimeValue ) ) {
			throw new InvalidArgumentException( 'Data value type mismatch. Expected a TimeValue.' );
		}

		$formatted = $this->dateTimeFormatter->format( $value );

		if ( $this->calendarNameNeeded( $value ) ) {
			$formatted .= '<sup class="wb-calendar-name">'
				. $this->formatCalendarName( $value->getCalendarModel() )
				. '</sup>';
		}

		return $formatted;
	}

	/**
	 * @param TimeValue $value
	 *
	 * @return bool
	 */
	private function calendarNameNeeded( TimeValue $value ) {
		// We assume this is an ISO-ish timestamp.
		preg_match( '/^[-+]\d+\D/', $value->getTime(), $m );

		// NOTE: PHP will limit overly large values to PHP_INT_MAX. No overflow or wrap-around occurs.
		$year = (int)$m[0];
		$guessedCalendar = $this->getDefaultCalendar( $year );

		// Always show the calendar if it's different from the "guessed" default.
		if ( $value->getCalendarModel() !== $guessedCalendar ) {
			return true;
		}

		// Otherwise, the calendar is "unsurprising", so don't show it.
		return false;
	}

	/**
	 * This guesses the most likely calendar model based on the given TimeValue,
	 * ignoring the calendar given in the TimeValue. This should always implement the
	 * exact same heuristic as IsoTimestampParser::getCalendarModel().
	 *
	 * @see IsoTimestampParser::getCalendarModel()
	 *
	 * @param int $year
	 *
	 * @return string Calendar URI
	 */
	private function getDefaultCalendar( $year ) {
		// The Gregorian calendar was introduced in October 1582,
		// so we'll default to Julian for all years before 1583.
		return $year <= 1582 ? TimeFormatter::CALENDAR_JULIAN : TimeFormatter::CALENDAR_GREGORIAN;
	}

	/**
	 * @param string $calendarModel
	 *
	 * @return string HTML
	 */
	private function formatCalendarName( $calendarModel ) {
		if ( array_key_exists( $calendarModel, self::$calendarKeys ) ) {
			$key = self::$calendarKeys[$calendarModel];
			$lang = $this->getOption( ValueFormatter::OPT_LANG );
			$msg = wfMessage( $key )->inLanguage( $lang );

			if ( $msg->exists() ) {
				return htmlspecialchars( $msg->text() );
			}
		}

		return htmlspecialchars( $calendarModel );
	}

}
