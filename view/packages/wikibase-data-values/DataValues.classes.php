<?php

/**
 * Class registration file for the DataValues library.
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
 * @ingroup DataValues
 *
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
return array(
	'DataValues\BooleanValue' => 'includes/values/BooleanValue.php',
	'DataValues\GeoCoordinateValue' => 'includes/values/GeoCoordinateValue.php',
	'DataValues\IriValue' => 'includes/values/IriValue.php',
	'DataValues\MonolingualTextValue' => 'includes/values/MonolingualTextValue.php',
	'DataValues\MultilingualTextValue' => 'includes/values/MultilingualTextValue.php',
	'DataValues\MediaWikiTitleValue' => 'includes/values/MediaWikiTitleValue.php',
	'DataValues\NumberValue' => 'includes/values/NumberValue.php',
	'DataValues\QuantityValue' => 'includes/values/QuantityValue.php',
	'DataValues\StringValue' => 'includes/values/StringValue.php',
	'DataValues\TimeValue' => 'includes/values/TimeValue.php',
	'DataValues\UnknownValue' => 'includes/values/UnknownValue.php',

	'DataValues\DataValue' => 'includes/DataValue.php',
	'DataValues\DataValueFactory' => 'includes/DataValueFactory.php',
	'DataValues\DataValueObject' => 'includes/DataValueObject.php',

	'Comparable' => 'includes/Comparable.php',
	'Copyable' => 'includes/Copyable.php',
	'Hashable' => 'includes/Hashable.php',
	'Immutable' => 'includes/Immutable.php',

	'DataValues\Test\DataValueTest' => 'tests/includes/DataValueTest.php',
);
