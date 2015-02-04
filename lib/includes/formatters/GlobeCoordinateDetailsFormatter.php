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
 * @licence GNU GPL v2+
 * @author Daniel Kinzler
 */
class GlobeCoordinateDetailsFormatter extends ValueFormatterBase {

	/**
	 * @var GlobeCoordinateFormatter
	 */
	protected $coordinateFormatter;

	/**
	 * @param FormatterOptions $options
	 */
	public function __construct( FormatterOptions $options ) {
		parent::__construct( $options );

		if ( !$options->hasOption( GeoCoordinateFormatter::OPT_FORMAT ) ) {
			//TODO: what'S a good default? Should this be locale dependant? Configurable?
			$options->setOption( GeoCoordinateFormatter::OPT_FORMAT, GeoCoordinateFormatter::TYPE_DMS );
		}

		$this->coordinateFormatter = new GlobeCoordinateFormatter( $options );
	}

	/**
	 * Generates HTML representing the details of a GlobeCoordinateValue,
	 * as an itemized list.
	 *
	 * @since 0.5
	 *
	 * @param GlobeCoordinateValue $value The ID to format
	 *
	 * @throws InvalidArgumentException
	 * @return string
	 */
	public function format( $value ) {
		if ( !( $value instanceof GlobeCoordinateValue ) ) {
			throw new InvalidArgumentException( 'Data value type mismatch. Expected an GlobeCoordinateValue.' );
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
	 * @param string $valueHtml
	 *
	 * @return string HTML for the label/value pair
	 */
	protected function renderLabelValuePair( $fieldName, $valueHtml ) {
		$html = Html::openElement( 'tr' );

		$html .= Html::element( 'th', array( 'class' => 'wb-globe-' . $fieldName ),
			$this->getFieldLabel( $fieldName )->text() );
		$html .= Html::element( 'td', array( 'class' => 'wb-globe-' . $fieldName ),
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
