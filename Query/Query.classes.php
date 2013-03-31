<?php

/**
 * Class registration file for the Query component of Wikibase.
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
 * @ingroup WikibaseQuery
 *
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
return call_user_func( function() {

	$classes = array(
		'Wikibase\Query\QueryEngine',
		'Wikibase\Query\QueryEngineResult',
		'Wikibase\Query\QueryResult',
		'Wikibase\Query\QueryStore',
		'Wikibase\Query\QueryStoreUpdater',

		'Wikibase\Query\SQLStore\DVHandler\BooleanHandler',
		'Wikibase\Query\SQLStore\DVHandler\EntityIdHandler',
		'Wikibase\Query\SQLStore\DVHandler\GeoCoordinateHandler',
		'Wikibase\Query\SQLStore\DVHandler\IriHandler',
		'Wikibase\Query\SQLStore\DVHandler\MonolingualTextHandler',
		'Wikibase\Query\SQLStore\DVHandler\NumberHandler',
		'Wikibase\Query\SQLStore\DVHandler\StringHandler',

		'Wikibase\Query\SQLStore\DataValueHandlers',
		'Wikibase\Query\SQLStore\DataValueHandler',
		'Wikibase\Query\SQLStore\DataValueTable',
		'Wikibase\Query\SQLStore\Engine',
		'Wikibase\Query\SQLStore\Schema',
		'Wikibase\Query\SQLStore\Setup',
		'Wikibase\Query\SQLStore\Store',
		'Wikibase\Query\SQLStore\StoreConfig',
		'Wikibase\Query\SQLStore\Updater',
	);

	$paths = array();

	foreach ( $classes as $class ) {
		$path = str_replace( '\\', '/', substr( $class, 9 ) ) . '.php';

		$paths[$class] = $path;
	}

	return $paths;

} );
