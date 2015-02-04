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

	/**
	 * @var MwTimeIsoFormatter
	 */
	protected $isoTimeFormatter;

	/**
	 * @var TimeFormatter
	 */
	protected $timeFormatter;

	/**
	 * @param FormatterOptions $options
	 */
	public function __construct( FormatterOptions $options ) {
		parent::__construct( $options );

		if ( $options->hasOption( TimeFormatter::OPT_TIME_ISO_FORMATTER ) ) {
			$this->isoTimeFormatter = $options->getOption( TimeFormatter::OPT_TIME_ISO_FORMATTER );
		} else {
			$this->isoTimeFormatter = new MwTimeIsoFormatter( $options );
			$options->setOption( TimeFormatter::OPT_TIME_ISO_FORMATTER, $this->isoTimeFormatter );
		}

		$this->timeFormatter = new TimeFormatter( $options );
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
		$html .= Html::element( 'h4',
			array( 'class' => 'wb-details wb-time-details wb-time-rendered' ),
			$this->timeFormatter->format( $value )
		);

		$html .= Html::openElement( 'table',
			array( 'class' => 'wb-details wb-time-details' ) );
		$html .= $this->renderLabelValuePair( 'isotime', htmlspecialchars( $value->getTime() ) );

		//TODO: provide "nice" rendering of timezone, calendar, precision, etc.
		$html .= $this->renderLabelValuePair( 'timezone',
			htmlspecialchars( $value->getTimezone() ) );
		$html .= $this->renderLabelValuePair( 'calendar',
			htmlspecialchars( $value->getCalendarModel() ) );
		$html .= $this->renderLabelValuePair( 'precision',
			htmlspecialchars( $value->getPrecision() ) );

		$html .= $this->renderLabelValuePair( 'before', htmlspecialchars( $value->getBefore() ) );
		$html .= $this->renderLabelValuePair( 'after', htmlspecialchars( $value->getAfter() ) );

		$html .= Html::closeElement( 'table' );

		return $html;
	}

	/**
	 * @param string $fieldName
	 * @param string $valueHtml
	 *
	 * @return string HTML for the label/value pair
	 */
	protected function renderLabelValuePair( $fieldName, $valueHtml ) {
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
	protected function getFieldLabel( $fieldName ) {
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
