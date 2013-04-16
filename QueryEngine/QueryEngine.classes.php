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
 * @ingroup WikibaseQueryEngine
 *
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
return call_user_func( function() {

	$classes = array(
		'Wikibase\QueryEngine\QueryEngine',
		'Wikibase\QueryEngine\QueryEngineResult',
		'Wikibase\QueryEngine\QueryResult',
		'Wikibase\QueryEngine\QueryStore',
		'Wikibase\QueryEngine\QueryStoreWriter',

		'Wikibase\QueryEngine\SQLStore\ClaimStore\ClaimInserter',
		'Wikibase\QueryEngine\SQLStore\ClaimStore\ClaimRowBuilder',
		'Wikibase\QueryEngine\SQLStore\ClaimStore\ClaimRow',
		'Wikibase\QueryEngine\SQLStore\ClaimStore\ClaimsTable',

		'Wikibase\QueryEngine\SQLStore\DVHandler\BooleanHandler',
		'Wikibase\QueryEngine\SQLStore\DVHandler\EntityIdHandler',
		'Wikibase\QueryEngine\SQLStore\DVHandler\GeoCoordinateHandler',
		'Wikibase\QueryEngine\SQLStore\DVHandler\IriHandler',
		'Wikibase\QueryEngine\SQLStore\DVHandler\MonolingualTextHandler',
		'Wikibase\QueryEngine\SQLStore\DVHandler\NumberHandler',
		'Wikibase\QueryEngine\SQLStore\DVHandler\StringHandler',

		'Wikibase\QueryEngine\SQLStore\SnakStore\SnakInserter',
		'Wikibase\QueryEngine\SQLStore\SnakStore\SnakRow',
		'Wikibase\QueryEngine\SQLStore\SnakStore\SnakRowBuilder',
		'Wikibase\QueryEngine\SQLStore\SnakStore\SnakStore',
		'Wikibase\QueryEngine\SQLStore\SnakStore\ValuelessSnakRow',
		'Wikibase\QueryEngine\SQLStore\SnakStore\ValuelessSnakStore',
		'Wikibase\QueryEngine\SQLStore\SnakStore\ValueSnakRow',
		'Wikibase\QueryEngine\SQLStore\SnakStore\ValueSnakStore',

		'Wikibase\QueryEngine\SQLStore\DataValueHandlers',
		'Wikibase\QueryEngine\SQLStore\DataValueHandler',
		'Wikibase\QueryEngine\SQLStore\DataValueTable',
		'Wikibase\QueryEngine\SQLStore\Engine',
		'Wikibase\QueryEngine\SQLStore\EntityIdMap',
		'Wikibase\QueryEngine\SQLStore\InternalEntityIdFinder',
		'Wikibase\QueryEngine\SQLStore\Schema',
		'Wikibase\QueryEngine\SQLStore\Setup',
		'Wikibase\QueryEngine\SQLStore\Store',
		'Wikibase\QueryEngine\SQLStore\StoreConfig',
		'Wikibase\QueryEngine\SQLStore\Writer',
	);

	$paths = array();

	foreach ( $classes as $class ) {
		$path = str_replace( '\\', '/', substr( $class, 9 ) ) . '.php';

		$paths[$class] = $path;
	}

	return $paths;

} );
