<?php

declare( strict_types = 1 );

namespace Wikibase\Lib\Formatters;

use DataValues\TimeValue;
use ValueFormatters\FormatterOptions;

/**
 * A helper class for time value formatters,
 * deciding whether the calendar model should be shown or not.
 *
 * When the 'showcalendar' formatter option is set to 'auto',
 * the calendar model is shown if it's not obvious
 * (e.g. a date in 1800 could be Julian or Gregorian)
 * or if it's different from what the parsers would detect
 * (where 1582 and before is Julian, and 1583 and later is Gregorian).
 *
 * @license GPL-2.0-or-later
 */
class ShowCalendarModelDecider {

	/**
	 * A {@link FormatterOptions formatter option} that determines
	 * whether the calendar model will be shown or not.
	 *
	 * If true or false, always show or don’t show the calendar model;
	 * if 'auto', show the calendar model if it is needed.
	 */
	public const OPT_SHOW_CALENDAR = 'showcalendar';

	/**
	 * Decide whether the calendar model should be shown or not.
	 *
	 * @param TimeValue $value The time value to which the decision applies.
	 * @param FormatterOptions $options The formatter options.
	 * The caller *must* have set a default for {@link ShowCalendarModelDecider::OPT_SHOW_CALENDAR}.
	 * @return bool
	 */
	public function showCalendarModel( TimeValue $value, FormatterOptions $options ): bool {
		$options->requireOption( self::OPT_SHOW_CALENDAR );
		$show = $options->getOption( self::OPT_SHOW_CALENDAR );
		if ( $show === 'auto' ) {
			$show = $this->calendarNameNeeded( $value );
		}
		return $show;
	}

	private function calendarNameNeeded( TimeValue $value ): bool {
		// Do not care about possibly wrong calendar models with precision 10 years and more.
		if ( $value->getPrecision() <= TimeValue::PRECISION_YEAR10 ) {
			return false;
		}

		// Loose check if the timestamp string is ISO-ish and starts with a year.
		if ( !preg_match( '/^[-+]?\d+\b/', $value->getTime(), $matches ) ) {
			return true;
		}

		// NOTE: PHP limits overly large values to PHP_INT_MAX. No overflow or wrap-around occurs.
		$year = (int)$matches[0];
		$guessedCalendar = $this->getDefaultCalendar( $year );

		// Always show the calendar if it's different from the "guessed" default.
		if ( $value->getCalendarModel() !== $guessedCalendar ) {
			return true;
		}

		// If precision is year or less precise, don't show the calendar.
		if ( $value->getPrecision() <= TimeValue::PRECISION_YEAR ) {
			return false;
		}

		// If the date is inside the "critical" range where Julian and Gregorian were used
		// in parallel, always show the calendar. Gregorian was made "official" in October 1582 but
		// may already be used earlier. Julian continued to be official until the 1920s in Russia
		// and Greece, see https://en.wikipedia.org/wiki/Julian_calendar.
		if ( $year > 1580 && $year < 1930 ) {
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
	private function getDefaultCalendar( int $year ): string {
		// The Gregorian calendar was introduced in October 1582,
		// so we'll default to Julian for all years before 1583.
		return $year <= 1582 ? TimeValue::CALENDAR_JULIAN : TimeValue::CALENDAR_GREGORIAN;
	}

}
