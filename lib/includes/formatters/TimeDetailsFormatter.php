<?php

namespace Wikibase\Lib;

use DataValues\TimeValue;
use Html;
use InvalidArgumentException;
use Message;
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
 */
class TimeDetailsFormatter extends ValueFormatterBase {

	const OPT_CALENDARNAMES = 'calendars';

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

		$this->defaultOption( self::OPT_CALENDARNAMES, array(
			TimeFormatter::CALENDAR_GREGORIAN => 'Gregorian',
			TimeFormatter::CALENDAR_JULIAN => 'Julian',
		) );

		$this->timeFormatter = new TimeFormatter( $this->options );
	}

	/**
	 * Generates HTML representing the details of a TimeValue,
	 * as an itemized list.
	 *
	 * @since 0.5
	 *
	 * @param TimeValue $value The ID to format
	 *
	 * @throws InvalidArgumentException
	 * @return string
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

		$html .= $this->renderLabelValuePair(
			'isotime',
			htmlspecialchars( $value->getTime() )
		);
		$html .= $this->renderLabelValuePair(
			'timezone',
			$this->getTimezoneHtml( $value->getTimezone() )
		);
		$html .= $this->renderLabelValuePair(
			'calendar',
			$this->getCalendarModelHtml( $value->getCalendarModel() )
		);
		// TODO: Provide "nice" rendering of precision, etc.
		$html .= $this->renderLabelValuePair(
			'precision',
			htmlspecialchars( $value->getPrecision() )
		);
		$html .= $this->renderLabelValuePair(
			'before',
			htmlspecialchars( $value->getBefore() )
		);
		$html .= $this->renderLabelValuePair(
			'after',
			htmlspecialchars( $value->getAfter() )
		);

		$html .= Html::closeElement( 'table' );

		return $html;
	}

	/**
	 * @param int $timezone
	 *
	 * @return string
	 */
	private function getTimezoneHtml( $timezone ) {
		$sign = $timezone < 0 ? "\xE2\x88\x92" : '+';
		$hour = floor( abs( $timezone ) / 60 );
		$minute = abs( $timezone ) - $hour * 60;
		return sprintf( '%s%02d:%02d', $sign, $hour, $minute );
	}

	/**
	 * @param string $calendarModel
	 *
	 * @return string
	 */
	private function getCalendarModelHtml( $calendarModel ) {
		$calendarNames = $this->getOption( self::OPT_CALENDARNAMES );
		if ( array_key_exists( $calendarModel, $calendarNames ) ) {
			$calendarModel = $calendarNames[$calendarModel];
		}

		return htmlspecialchars( $calendarModel );
	}

	/**
	 * @param string $fieldName
	 * @param string $valueHtml
	 *
	 * @return string HTML for the label/value pair
	 */
	private function renderLabelValuePair( $fieldName, $valueHtml ) {
		$html = Html::openElement( 'tr' );

		$html .= Html::element( 'th', array( 'class' => 'wb-time-' . $fieldName ),
			$this->getFieldLabel( $fieldName )->text() );
		$html .= Html::element( 'td', array( 'class' => 'wb-time-' . $fieldName ),
			$valueHtml );

		$html .= Html::closeElement( 'tr' );
		return $html;
	}

	/**
	 * @param string $fieldName
	 *
	 * @return Message
	 */
	private function getFieldLabel( $fieldName ) {
		$lang = $this->getOption( ValueFormatter::OPT_LANG );

		// Messages:
		// wikibase-timedetails-isotime
		// wikibase-timedetails-timezone
		// wikibase-timedetails-calendar
		// wikibase-timedetails-precision
		// wikibase-timedetails-before
		// wikibase-timedetails-after
		$key = 'wikibase-timedetails-' . strtolower( $fieldName );
		$msg = wfMessage( $key )->inLanguage( $lang );

		return $msg;
	}

}
