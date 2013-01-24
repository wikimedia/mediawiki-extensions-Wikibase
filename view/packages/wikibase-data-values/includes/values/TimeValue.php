<?php

namespace DataValues;
use InvalidArgumentException, OutOfBoundsException;

/**
 * Class representing a time value.
 * @see https://meta.wikimedia.org/wiki/Wikidata/Data_model#Dates_and_times
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License along
 * with this program; if not, write to the Free Software Foundation, Inc.,
 * 51 Franklin Street, Fifth Floor, Boston, MA 02110-1301, USA.
 * http://www.gnu.org/copyleft/gpl.html
 *
 * @since 0.1
 *
 * @file
 * @ingroup DataValue
 *
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
class TimeValue extends DataValueObject {

	const PRECISION_Ga = 28; // Gigayear
	const PRECISION_100Ma = 29; // 100 Megayears
	const PRECISION_10Ma = 30; // 10 Megayears
	const PRECISION_Ma = 31; // Megayear
	const PRECISION_100ka = 32; // 100 Kiloyears
	const PRECISION_10ka = 33; // 10 Kiloyears
	const PRECISION_ka = 34; // Kiloyear
	const PRECISION_100a = 35; // 100 years
	const PRECISION_10a = 36; // 10 years
	const PRECISION_YEAR = 37;
	const PRECISION_MONTH = 38;
	const PRECISION_DAY = 39;
	const PRECISION_HOUR = 40;
	const PRECISION_MINUTE = 41;
	const PRECISION_SECOND = 42;

	/**
	 * Point in time, represented per ISO8601.
	 * The year always having 11 digits, the date always be signed, in the format +00000002013-01-01T00:00:00Z
	 *
	 * @since 0.1
	 *
	 * @var string
	 */
	protected $time;

	/**
	 * Unit used for the $after and $before values.
	 *
	 * @since 0.1
	 *
	 * @var integer
	 */
	protected $precision;

	/**
	 * If the date is uncertain, how many units after the given time could it be?
	 * The unit is given by the precision.
	 *
	 * @since 0.1
	 *
	 * @var integer
	 */
	protected $after;

	/**
	 * If the date is uncertain, how many units before the given time could it be?
	 * The unit is given by the precision.
	 *
	 * @since 0.1
	 *
	 * @var integer
	 */
	protected $before;

	/**
	 * Timezone information as an offset from UTC in minutes.
	 *
	 * @since 0.1
	 *
	 * @var integer
	 */
	protected $timezone;

	/**
	 * URI identifying the calendar model that should be used to display this time value.
	 * Note that time is always saved in proleptic Gregorian, this URI states how the value should be displayed.
	 *
	 * @since 0.1
	 *
	 * @var string
	 */
	protected $calendarModel;

	/**
	 * Constructor.
	 *
	 * @since 0.1
	 *
	 * @param string $time
	 * @param integer $timezone
	 * @param integer $before
	 * @param integer $precision
	 * @param string $calendarModel
	 *
	 * @throws InvalidArgumentException
	 * @throws OutOfBoundsException
	 */
	public function __construct( $time, $timezone, $before, $after, $precision, $calendarModel ) {
		if ( !is_string( $time ) ) {
			throw new InvalidArgumentException( '$time needs to be a string' );
		}

		if ( !is_integer( $timezone ) ) {
			throw new InvalidArgumentException( '$timezone needs to be an integer' );
		}

		if ( $timezone < -12 * 3600 || $timezone > 14 * 3600 ) {
			throw new OutOfBoundsException( '$timezone out of allowed bounds' );
		}

		if ( !is_integer( $before ) || $before < 0 ) {
			throw new InvalidArgumentException( '$before needs to be an unsigned integer' );
		}

		if ( !is_integer( $after ) || $after < 0 ) {
			throw new InvalidArgumentException( '$after needs to be an unsigned integer' );
		}

		if ( !is_integer( $precision ) ) {
			throw new InvalidArgumentException( '$precision needs to be an integer' );
		}

		if ( $precision < self::PRECISION_Ga || $precision > self::PRECISION_SECOND ) {
			throw new OutOfBoundsException( '$precision out of allowed bounds' );
		}

		if ( !is_string( $calendarModel ) ) {
			throw new InvalidArgumentException( '$calendarModel needs to be a string' );
		}

		// Can haz scalar type hints plox? ^^

		$this->time = $time;
		$this->timezone = $timezone;
		$this->before = $before;
		$this->after = $after;
		$this->precision = $precision;
		$this->calendarModel = $calendarModel;
	}

	/**
	 * @see $time
	 *
	 * @since 0.1
	 *
	 * @return string
	 */
	public function getTime() {
		return $this->time;
	}

	/**
	 * @see $calendarModel
	 *
	 * @since 0.1
	 *
	 * @return string
	 */
	public function getCalendarModel() {
		return $this->calendarModel;
	}

	/**
	 * @see $before
	 *
	 * @since 0.1
	 *
	 * @return integer
	 */
	public function getBefore() {
		return $this->before;
	}

	/**
	 * @see $after
	 *
	 * @since 0.1
	 *
	 * @return integer
	 */
	public function getAfter() {
		return $this->after;
	}

	/**
	 * @see $precision
	 *
	 * @since 0.1
	 *
	 * @return integer
	 */
	public function getPrecision() {
		return $this->precision;
	}

	/**
	 * @see $timezone
	 *
	 * @since 0.1
	 *
	 * @return integer
	 */
	public function getTimezone() {
		return $this->timezone;
	}

	/**
	 * @see DataValue::getType
	 *
	 * @since 0.1
	 *
	 * @return string
	 */
	public function getType() {
		return 'time';
	}

	/**
	 * @see DataValue::getSortKey
	 *
	 * @since 0.1
	 *
	 * @return string|float|int
	 */
	public function getSortKey() {
		return $this->time;
	}

	/**
	 * Returns the text.
	 * @see DataValue::getValue
	 *
	 * @since 0.1
	 *
	 * @return TimeValue
	 */
	public function getValue() {
		return $this;
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
	 * @return MonolingualTextValue
	 * @throws InvalidArgumentException
	 */
	public function unserialize( $value ) {
		list( $time, $timezone, $before, $after, $precision, $calendarModel ) = json_decode( $value );
		$this->__construct( $time, $timezone, $before, $after, $precision, $calendarModel );
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
			'time' => $this->time,
			'timezone' => $this->timezone,
			'before' => $this->before,
			'after' => $this->after,
			'precision' => $this->precision,
			'calendarmodel' => $this->calendarModel,
		);
	}

	/**
	 * Constructs a new instance of the DataValue from the provided data.
	 * This can round-trip with @see getArrayValue
	 *
	 * @since 0.1
	 *
	 * @param array $data
	 *
	 * @return DataValue
	 */
	public static function newFromArray( array $data ) {
		return new static(
			$data['time'],
			$data['timezone'],
			$data['before'],
			$data['after'],
			$data['precision'],
			$data['calendarmodel']
		);
	}

}
