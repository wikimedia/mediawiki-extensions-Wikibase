<?php

/**
 * MediaWiki setup for the Query component of Wikibase.
 * The component should be included via the main entry point, Database.php.
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

global $wgExtensionCredits, $wgExtensionMessagesFiles, $wgAutoloadClasses, $wgHooks;

//$wgExtensionCredits['other'][] = include( __DIR__ . '/DataModel.credits.php' );

//$wgExtensionMessagesFiles['WikibaseDataModel'] = __DIR__ . '/DataModel.i18n.php';

if ( defined( 'MW_PHPUNIT_TEST' ) ) {
	require_once __DIR__ . '/tests/testLoader.php';
}

/**
 * Hook to add PHPUnit test cases.
 * @see https://www.mediawiki.org/wiki/Manual:Hooks/UnitTestsList
 *
 * @since 0.1
 *
 * @param array $files
 *
 * @return boolean
 */
$wgHooks['UnitTestsList'][]	= function( array &$files ) {
	// @codeCoverageIgnoreStart
	$testFiles = array(
		'QueryEngineResult',

		'SQLStore/ClaimStore/ClaimInserter',
		'SQLStore/ClaimStore/ClaimRowBuilder',
		'SQLStore/ClaimStore/ClaimRow',
		'SQLStore/ClaimStore/ClaimsTable',

		'SQLStore/DVHandler/BooleanHandler',
		'SQLStore/DVHandler/EntityIdHandler',
		'SQLStore/DVHandler/GeoCoordinateHandler',
		'SQLStore/DVHandler/IriHandler',
		'SQLStore/DVHandler/MonolingualTextHandler',
		'SQLStore/DVHandler/NumberHandler',
		'SQLStore/DVHandler/StringHandler',

		'SQLStore/Engine/Engine',

		'SQLStore/SnakStore/SnakInserter',
		'SQLStore/SnakStore/SnakRowBuilder',
		'SQLStore/SnakStore/ValuelessSnakStore',
		'SQLStore/SnakStore/ValueSnakRow',
		'SQLStore/SnakStore/ValuelessSnakRow',
		'SQLStore/SnakStore/ValueSnakStore',

		'SQLStore/DataValueHandlers',
		'SQLStore/DataValueHandler',
		'SQLStore/EntityIdTransformer',
		'SQLStore/EntityInserter',
		'SQLStore/Factory',
		'SQLStore/Schema',
		'SQLStore/Setup',
		'SQLStore/Store',
		'SQLStore/StoreConfig',
		'SQLStore/Writer',
	);

	foreach ( $testFiles as $file ) {
		$files[] = __DIR__ . '/tests/phpunit/' . $file . 'Test.php';
	}

	$testFiles = array(
		'SQLStore/Engine/DescriptionMatchFinderIntegrationTest',
	);

	foreach ( $testFiles as $file ) {
		$files[] = __DIR__ . '/tests/integration/' . $file . '.php';
	}

	return true;
	// @codeCoverageIgnoreEnd
};
