<?php

namespace DataValues;

/**
 * Class representing a geographical coordinate value.
 *
 * @since 0.1
 *
 * @file
 * @ingroup DataValue
 *
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
class GlobeCoordinateValue extends DataValueObject {

	// TODO: Introduce some constants holding the different precisions and document. Sync with JS.
	// Take Time as an example how to do this.
	// Precision values also need to be documented.

	/**
	 * @since 0.1
	 *
	 * @var LatLongValue
	 */
	protected $latLang;

	/**
	 * The precision of the coordinate.
	 *
	 * @since 0.1
	 *
	 * @var float|int
	 */
	protected $precision;

	/**
	 * The globe on which the location resides.
	 *
	 * @since 0.1
	 *
	 * @var string
	 */
	protected $globe;

	const GLOBE_EARTH = 'http://www.wikidata.org/entity/Q2';

	/**
	 * @since 0.1
	 *
	 * @param LatLongValue $latLang
	 * @param float|int $precision
	 * @param string $globe
	 *
	 * @throws IllegalValueException
	 */
	public function __construct( LatLongValue $latLang, $precision, $globe = self::GLOBE_EARTH ) {
		$this->assertIsPrecision( $precision );
		$this->assertIsGlobe( $globe );

		$this->latLang = $latLang;
		$this->precision = $precision;
		$this->globe =  $globe;
	}

	protected function assertIsPrecision( $precision ) {
		if ( !is_float( $precision ) && !is_int( $precision ) ) {
			throw new IllegalValueException( 'Can only construct GlobeCoordinateValue with a numeric precision' );
		}
	}

	protected function assertIsGlobe( $globe ) {
		if ( !is_string( $globe ) ) {
			throw new IllegalValueException( 'Can only construct GlobeCoordinateValue with a string or null globe parameter' );
		}
	}

	/**
	 * @see Serializable::serialize
	 *
	 * @since 0.1
	 *
	 * @return string
	 */
	public function serialize() {
		return json_encode( array_values( $this->getArrayValue() ) );
	}

	/**
	 * @see Serializable::unserialize
	 *
	 * @since 0.1
	 *
	 * @param string $value
	 *
	 * @return GlobeCoordinateValue
	 * @throws IllegalValueException
	 */
	public function unserialize( $value ) {
		list( $latitude, $longitude, $altitude, $precision, $globe ) = json_decode( $value );

		$this->__construct(
			new LatLongValue( $latitude, $longitude ),
			$precision,
			$globe
		);
	}

	/**
	 * @see DataValue::getType
	 *
	 * @since 0.1
	 *
	 * @return string
	 */
	public static function getType() {
		return 'globecoordinate';
	}

	/**
	 * @see DataValue::getSortKey
	 *
	 * @since 0.1
	 *
	 * @return float
	 */
	public function getSortKey() {
		return $this->getLatitude();
	}

	/**
	 * Returns the latitude.
	 *
	 * @since 0.1
	 *
	 * @return float
	 */
	public function getLatitude() {
		return $this->latLang->getLatitude();
	}

	/**
	 * Returns the longitude.
	 *
	 * @since 0.1
	 *
	 * @return float
	 */
	public function getLongitude() {
		return $this->latLang->getLongitude();
	}

	/**
	 * Returns the text.
	 * @see DataValue::getValue
	 *
	 * @since 0.1
	 *
	 * @return GlobeCoordinateValue
	 */
	public function getValue() {
		return $this;
	}

	/**
	 * @since 0.1
	 *
	 * @return GlobeCoordinateValue
	 */
	public function getLatLong() {
		return $this->latLang;
	}

	/**
	 * Returns the precision of the coordinate.
	 *
	 * @since 0.1
	 *
	 * @return float|int
	 */
	public function getPrecision() {
		return $this->precision;
	}

	/**
	 * Returns the identifier of the globe on which the location resides.
	 *
	 * @since 0.1
	 *
	 * @return string
	 */
	public function getGlobe() {
		return $this->globe;
	}

	/**
	 * @see DataValue::getArrayValue
	 *
	 * @since 0.1
	 *
	 * @return mixed
	 */
	public function getArrayValue() {
		return array(
			'latitude' => $this->latLang->getLatitude(),
			'longitude' => $this->latLang->getLongitude(),

			// The altitude field is no longer used in this class.
			// It is kept here for compatibility reasons.
			'altitude' => null,

			'precision' => $this->precision,
			'globe' => $this->globe,
		);
	}

	/**
	 * Constructs a new instance of the DataValue from the provided data.
	 * This can round-trip with @see getArrayValue
	 *
	 * @since 0.1
	 *
	 * @param mixed $data
	 *
	 * @return GlobeCoordinateValue
	 * @throws IllegalValueException
	 */
	public static function newFromArray( $data ) {
		self::requireArrayFields( $data, array( 'latitude', 'longitude' ) );

		return new static(
			new LatLongValue(
				$data['latitude'],
				$data['longitude']
			),
			( isset( $data['precision'] ) ) ? $data['precision'] : null,
			( isset( $data['globe'] ) ) ? $data['globe'] : null
		);
	}

}
