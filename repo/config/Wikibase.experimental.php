<?php

/**
 * This file holds registration of experimental features part of the Wikibase Repo extension.
 *
 * This file is NOT an entry point the Wikibase extension. Use Wikibase.php.
 * It should furthermore not be included from outside the extension.
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
 * @since 0.4
 *
 * @file
 * @ingroup WikibaseRepo
 *
 * @licence GNU GPL v2+
 */

if ( !defined( 'WB_VERSION' ) || !defined( 'WB_EXPERIMENTAL_FEATURES' ) ) {
	die( 'Not an entry point.' );
}

// Include the Database component if that hasn't been done yet.
if ( !defined( 'WIKIBASE_DATABASE' ) ) {
	@include_once( __DIR__ . '/../../Database/Database.php' );
}

$dir = __DIR__ . '/../';

$wgAutoloadClasses['Wikibase\Api\RemoveQualifiers'] 	= $dir . 'includes/api/RemoveQualifiers.php';
$wgAutoloadClasses['Wikibase\Api\SetQualifier'] 		= $dir . 'includes/api/SetQualifier.php';
$wgAutoloadClasses['Wikibase\Api\SetStatementRank']		= $dir . 'includes/api/SetStatementRank.php';

$wgAutoloadClasses['SpecialEntityData'] 				= $dir . 'includes/specials/SpecialEntityData.php';

$wgAutoloadClasses['Wikibase\QueryContent'] 			= $dir . 'includes/content/QueryContent.php';
$wgAutoloadClasses['Wikibase\QueryHandler'] 			= $dir . 'includes/content/QueryHandler.php';


$classes = array(
	'Wikibase\Repo\Query\QueryEngine',
	'Wikibase\Repo\Query\QueryEngineResult',
	'Wikibase\Repo\Query\QueryResult',
	'Wikibase\Repo\Query\QueryStore',
	'Wikibase\Repo\Query\QueryStoreUpdater',

	'Wikibase\Repo\Query\SQLStore\DVHandler\BooleanHandler',
	'Wikibase\Repo\Query\SQLStore\DVHandler\EntityIdHandler',
	'Wikibase\Repo\Query\SQLStore\DVHandler\GeoCoordinateHandler',
	'Wikibase\Repo\Query\SQLStore\DVHandler\IriHandler',
	'Wikibase\Repo\Query\SQLStore\DVHandler\MonolingualTextHandler',
	'Wikibase\Repo\Query\SQLStore\DVHandler\NumberHandler',
	'Wikibase\Repo\Query\SQLStore\DVHandler\StringHandler',

	'Wikibase\Repo\Query\SQLStore\DataValueHandlers',
	'Wikibase\Repo\Query\SQLStore\DataValueHandler',
	'Wikibase\Repo\Query\SQLStore\DataValueTable',
	'Wikibase\Repo\Query\SQLStore\Engine',
	'Wikibase\Repo\Query\SQLStore\Schema',
	'Wikibase\Repo\Query\SQLStore\Setup',
	'Wikibase\Repo\Query\SQLStore\Store',
	'Wikibase\Repo\Query\SQLStore\StoreConfig',
	'Wikibase\Repo\Query\SQLStore\Updater',
);

foreach ( $classes as $class ) {
	// This enforces partial PSR-0 compliance
	$wgAutoloadClasses[$class] = $dir . 'includes' . str_replace( '\\', '/', substr( $class, 13 ) ) . '.php';
}

unset( $classes );

if ( defined( 'MW_PHPUNIT_TEST' ) ) {
	$wgAutoloadClasses['Wikibase\Repo\Test\Query\SQLStore\DataValueHandlerTest']
		= $dir . 'tests/phpunit/includes/Query/SQLStore/DataValueHandlerTest.php';

	$wgAutoloadClasses['Wikibase\Repo\Test\Query\QueryEngineTest']
		= $dir . 'tests/phpunit/includes/Query/QueryEngineTest.php';

	$wgAutoloadClasses['Wikibase\Repo\Test\Query\QueryStoreTest']
		= $dir . 'tests/phpunit/includes/Query/QueryStoreTest.php';

	$wgAutoloadClasses['Wikibase\Repo\Test\Query\QueryStoreUpdaterTest']
		= $dir . 'tests/phpunit/includes/Query/QueryStoreUpdaterTest.php';
}

unset( $dir );

$wgAPIModules['wbremovequalifiers'] 				= 'Wikibase\Api\RemoveQualifiers';
$wgAPIModules['wbsetqualifier'] 					= 'Wikibase\Api\SetQualifier';
$wgAPIModules['wbsetstatementrank'] 				= 'Wikibase\Api\SetStatementRank';

$wgSpecialPages['EntityData'] 						= 'SpecialEntityData';

$wgContentHandlers[CONTENT_MODEL_WIKIBASE_QUERY] 	= '\Wikibase\QueryHandler';

/**
 * Hook to add PHPUnit test cases.
 * @see https://www.mediawiki.org/wiki/Manual:Hooks/UnitTestsList
 *
 * @since 0.3
 *
 * @param array &$files
 *
 * @return boolean
 */
$wgHooks['UnitTestsList'][] = function( array &$files ) {
	// @codeCoverageIgnoreStart
	$testFiles = array(
		'api/RemoveQualifiers',
		'api/SetStatementRank',
		'api/SetQualifier',

		'content/QueryContent',
		'content/QueryHandler',

		'Query/QueryEngineResult',

		'Query/SQLStore/DVHandler/BooleanHandler',
		'Query/SQLStore/DVHandler/EntityIdHandler',
		'Query/SQLStore/DVHandler/GeoCoordinateHandler',
		'Query/SQLStore/DVHandler/IriHandler',
		'Query/SQLStore/DVHandler/MonolingualTextHandler',
		'Query/SQLStore/DVHandler/NumberHandler',
		'Query/SQLStore/DVHandler/StringHandler',

		'Query/SQLStore/DataValueHandlers',
		'Query/SQLStore/DataValueHandler',
		'Query/SQLStore/Engine',
		'Query/SQLStore/Schema',
		'Query/SQLStore/Setup',
		'Query/SQLStore/Store',
		'Query/SQLStore/StoreConfig',
		'Query/SQLStore/Updater',

		'specials/SpecialEntityData',

	);

	foreach ( $testFiles as $file ) {
		$files[] = __DIR__ . '/../tests/phpunit/includes/' . $file . 'Test.php';
	}

	return true;
	// @codeCoverageIgnoreEnd
};
