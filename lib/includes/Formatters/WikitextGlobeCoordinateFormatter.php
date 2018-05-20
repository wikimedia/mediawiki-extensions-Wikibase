<?php

namespace Wikibase\Lib\Formatters;

use DataValues\Geo\Values\GlobeCoordinateValue;
use Html;
use InvalidArgumentException;
use ValueFormatters\FormatterOptions;
use ValueFormatters\ValueFormatter;
use ValueFormatters\ValueFormatterBase;

/**
 * @license GPL-2.0-or-later
 * @author Thomas Pellissier Tanon
 */
class WikitextGlobeCoordinateFormatter extends ValueFormatterBase {

	/**
	 * Use the Kartographer extension for rendering
	 */
	const OPT_ENABLE_KARTOGRAPHER = 'enable-kartographer';

	/**
	 * @var ValueFormatter
	 */
	private $coordinateFormatter;

	/**
	 * @param ValueFormatter $globeCoordinateFormatter
	 * @param FormatterOptions|null $options
	 */
	public function __construct(
		ValueFormatter $globeCoordinateFormatter,
		FormatterOptions $options = null
	) {
		parent::__construct( $options );

		$this->coordinateFormatter = $globeCoordinateFormatter;

		$this->defaultOption( self::OPT_ENABLE_KARTOGRAPHER, false );
	}

	/**
	 * @see ValueFormatter::format
	 *
	 * @param GlobeCoordinateValue $value
	 *
	 * @throws InvalidArgumentException
	 * @return string Wikitext
	 */
	public function format( $value ) {
		if ( !( $value instanceof GlobeCoordinateValue ) ) {
			throw new InvalidArgumentException( 'Data value type mismatch. Expected a GlobeCoordinateValue.' );
		}

		if ( $value->getGlobe() === GlobeCoordinateValue::GLOBE_EARTH ) {
			return $this->formatEarthCoordinate( $value );
		}

		return wfEscapeWikiText( $this->coordinateFormatter->format( $value ) );
	}

	private function formatEarthCoordinate( GlobeCoordinateValue $value ) {
		if ( $this->getOption( self::OPT_ENABLE_KARTOGRAPHER ) ) {
			return $this->formatEarthCoordinateWithKartographer( $value );
		} else {
			return wfEscapeWikiText( $this->coordinateFormatter->format( $value ) );
		}
	}

	private function formatEarthCoordinateWithKartographer( GlobeCoordinateValue $value ) {
		return Html::element( 'maplink', [
			'latitude' => $value->getLatitude(),
			'longitude' => $value->getLongitude()
		], json_encode( [
			'type' => 'Feature',
			'geometry' => [ 'type' => 'Point', 'coordinates' => [ $value->getLongitude(), $value->getLatitude() ] ]
		] ) );
	}

}
