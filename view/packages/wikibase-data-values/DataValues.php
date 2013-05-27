<?php

/**
 * Entry point for the DataValues extension.
 *
 * Documentation:	 		https://www.mediawiki.org/wiki/Extension:DataValues
 * Support					https://www.mediawiki.org/wiki/Extension_talk:DataValues
 * Source code:				https://gerrit.wikimedia.org/r/gitweb?p=mediawiki/extensions/DataValues.git
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

/**
 * Files belonging to the DataValues extension.
 *
 * @defgroup DataValues DataValues
 */

/**
 * Tests part of the DataValues extension.
 *
 * @defgroup DataValuesTests DataValuesTests
 * @ingroup DataValues
 */

if ( defined( 'DataValues_VERSION' ) ) {
	// Do not initialize more then once.
	return;
}

define( 'DataValues_VERSION', '0.1 alpha' );

spl_autoload_register( function ( $className ) {
	// @codeCoverageIgnoreStart
	static $classes = false;

	if ( $classes === false ) {
		$classes = include( __DIR__ . '/' . 'DataValues.classes.php' );
	}

	if ( array_key_exists( $className, $classes ) ) {
		include_once __DIR__ . '/' . $classes[$className];
	}
	// @codeCoverageIgnoreEnd
} );

if ( defined( 'MEDIAWIKI' ) ) {
	include __DIR__ . '/DataValues.mw.php';
}

global $wgDataValues;
/**
 * @deprecated since 0.1 This is a global registry that provides no control over object lifecycle
 */
$wgDataValues = array();

$wgDataValues['boolean'] = 'DataValues\BooleanValue';
$wgDataValues['globecoordinate'] = 'DataValues\GeoCoordinateValue';
$wgDataValues['iri'] = 'DataValues\IriValue';
$wgDataValues['monolingualtext'] = 'DataValues\MonolingualTextValue';
$wgDataValues['multilingualtext'] = 'DataValues\MultilingualTextValue';
$wgDataValues['number'] = 'DataValues\NumberValue';
$wgDataValues['quantity'] = 'DataValues\QuantityValue';
$wgDataValues['string'] = 'DataValues\StringValue';
$wgDataValues['time'] = 'DataValues\TimeValue';
$wgDataValues['unknown'] = 'DataValues\UnknownValue';
