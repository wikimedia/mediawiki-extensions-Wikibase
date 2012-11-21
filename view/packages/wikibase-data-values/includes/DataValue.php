<?php

namespace DataValues;
use InvalidArgumentException;

/**
 * Interface for objects that represent a single data value.
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
interface DataValue extends \Hashable, \Comparable, \Serializable, \Immutable, \Copyable {

	/**
	 * Returns the identifier of the datavalues type.
	 * This should be the same value as the key in $wgDataValues.
	 *
	 * This is not to be confused with the DataType provided by the DataTypes extension.
	 *
	 * @since 0.1
	 *
	 * @return string
	 */
	public function getType();

	/**
	 * Returns a key that can be used to sort the data value with.
	 * It can be either numeric or a string.
	 *
	 * @since 0.1
	 *
	 * @return string|float|int
	 */
	public function getSortKey();

	/**
	 * Returns the value contained by the DataValue. If this value is not simple and
	 * does not have it's own type that represents it, the DataValue itself will be returned.
	 * In essence, this method returns the "simplest" representation of the value.
	 *
	 * Example:
	 * - NumberDataValue returns a float or integer
	 * - MediaWikiTitleValue returns a Title object
	 * - QuantityValue returns itself
	 *
	 * @since 0.1
	 *
	 * @return mixed
	 */
	public function getValue();

	/**
	 * Returns the value in array form.
	 *
	 * For simple values (ie a string) the return value will be equal to that of @see getValue.
	 *
	 * Complex DataValues can provide a nicer implementation though, for instance a
	 * geographical coordinate value could provide an array with keys latitude,
	 * longitude and altitude, each pointing to a simple float value.
	 *
	 * @since 0.1
	 *
	 * @return mixed
	 */
	public function getArrayValue();

	/**
	 * Returns the whole DataValue in array form.
	 *
	 * The array contains:
	 * - value: mixed, same as the result of @see getArrayValue
	 * - type: string, same as the result of @see getType
	 *
	 * This is sufficient for unserialization in a factory.
	 *
	 * @since 0.1
	 *
	 * @return array
	 */
	public function toArray();

}
