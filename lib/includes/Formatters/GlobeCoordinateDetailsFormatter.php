<?php

namespace Wikibase\Lib;

use DataValues\Geo\Formatters\GeoCoordinateFormatter;
use DataValues\Geo\Formatters\GlobeCoordinateFormatter;
use DataValues\Geo\Values\GlobeCoordinateValue;
use Html;
use InvalidArgumentException;
use Message;
use ValueFormatters\FormatterOptions;
use ValueFormatters\ValueFormatter;
use ValueFormatters\ValueFormatterBase;

/**
 * Formatter for rendering the details of a GlobeCoordinateValue (most useful for diffs) in HTML.
 *
 * @since 0.5
 *
 * @license GPL-2.0+
 * @author Daniel Kinzler
 */
class GlobeCoordinateDetailsFormatter extends ValueFormatterBase {

	/**
	 * @var GlobeCoordinateFormatter
	 */
	protected $coordinateFormatter;

	/**
	 * @param FormatterOptions|null $options
	 */
	public function __construct( FormatterOptions $options = null ) {
		parent::__construct( $options );

		// TODO: What's a good default? Should this be locale dependant? Configurable?
		$this->defaultOption( GeoCoordinateFormatter::OPT_FORMAT, GeoCoordinateFormatter::TYPE_DMS );

		$this->coordinateFormatter = new GlobeCoordinateFormatter( $this->options );
	}

	/**
	 * @see ValueFormatter::format
	 *
	 * Generates HTML representing the details of a GlobeCoordinateValue,
	 * as an itemized list.
	 *
	 * @param GlobeCoordinateValue $value
	 *
	 * @throws InvalidArgumentException
	 * @return string HTML
	 */
	public function format( $value ) {
		if ( !( $value instanceof GlobeCoordinateValue ) ) {
			throw new InvalidArgumentException( 'Data value type mismatch. Expected a GlobeCoordinateValue.' );
		}

		$html = '';
		$html .= Html::element( 'h4',
			array( 'class' => 'wb-details wb-globe-details wb-globe-rendered' ),
			$this->coordinateFormatter->format( $value )
		);

		$html .= Html::openElement( 'table',
			array( 'class' => 'wb-details wb-globe-details' ) );

		//TODO: nicer formatting and localization of numbers.
		$html .= $this->renderLabelValuePair( 'latitude',
			htmlspecialchars( $value->getLatitude() ) );
		$html .= $this->renderLabelValuePair( 'longitude',
			htmlspecialchars( $value->getLongitude() ) );
		$html .= $this->renderLabelValuePair( 'precision',
			htmlspecialchars( $value->getPrecision() ) );
		$html .= $this->renderLabelValuePair( 'globe',
			htmlspecialchars( $value->getGlobe() ) );

		$html .= Html::closeElement( 'table' );

		return $html;
	}

	/**
	 * @param string $fieldName
	 * @param string $valueHtml HTML
	 *
	 * @return string HTML for the label/value pair
	 */
	protected function renderLabelValuePair( $fieldName, $valueHtml ) {
		$html = Html::openElement( 'tr' );

		$html .= Html::element( 'th', array( 'class' => 'wb-globe-' . $fieldName ),
			$this->getFieldLabel( $fieldName )->text() );
		$html .= Html::rawElement( 'td', array( 'class' => 'wb-globe-' . $fieldName ),
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
		// wikibase-globedetails-latitude
		// wikibase-globedetails-longitude
		// wikibase-globedetails-precision
		// wikibase-globedetails-globe
		$key = 'wikibase-globedetails-' . strtolower( $fieldName );
		$msg = wfMessage( $key )->inLanguage( $lang );

		return $msg;
	}

}
