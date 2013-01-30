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

$dir = __DIR__ . '/../';

$wgAutoloadClasses['Wikibase\Api\RemoveQualifiers'] 	= $dir . 'includes/api/RemoveQualifiers.php';
$wgAutoloadClasses['Wikibase\Api\SetQualifier'] 		= $dir . 'includes/api/SetQualifier.php';
$wgAutoloadClasses['Wikibase\Api\SetStatementRank']			= $dir . 'includes/api/SetStatementRank.php';
$wgAutoloadClasses['Wikibase\Api\SetClaim']			= $dir . 'includes/api/SetClaim.php';

$wgAutoloadClasses['SpecialEntityData'] 					= $dir . 'includes/specials/SpecialEntityData.php';

$wgAutoloadClasses['Wikibase\QueryContent'] 			= $dir . 'includes/content/QueryContent.php';
$wgAutoloadClasses['Wikibase\QueryHandler'] 			= $dir . 'includes/content/QueryHandler.php';



foreach ( array(
			  'Wikibase\Repo\Database\FieldDefinition',
			  'Wikibase\Repo\Database\MediaWikiQueryInterface',
			  'Wikibase\Repo\Database\QueryInterface',
			  'Wikibase\Repo\Database\TableBuilder',
			  'Wikibase\Repo\Database\TableDefinition',
		  ) as $class ) {

	$wgAutoloadClasses[$class] = $dir . 'includes' . str_replace( '\\', '/', substr( $class, 13 ) ) . '.php';
}

if ( !class_exists( 'MessageReporter' ) ) {
	$wgAutoloadClasses['MessageReporter'] = $dir . 'includes/MessageReporter.php';
	$wgAutoloadClasses['ObservableMessageReporter'] = $dir . 'includes/MessageReporter.php';
}


$wgAPIModules['wbremovequalifiers'] 				= 'Wikibase\Api\RemoveQualifiers';
$wgAPIModules['wbsetqualifier'] 					= 'Wikibase\Api\SetQualifier';
$wgAPIModules['wbsetstatementrank'] 				= 'Wikibase\Api\SetStatementRank';
$wgAPIModules['wbsetclaim'] 						= 'Wikibase\Api\SetClaim';

$wgSpecialPages['EntityData'] 						= 'SpecialEntityData';

$wgContentHandlers[CONTENT_MODEL_WIKIBASE_QUERY] = '\Wikibase\QueryHandler';

unset( $dir );

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
		'api/SetClaim',

		'content/QueryContent',
		'content/QueryHandler',

		'Database/FieldDefinition',
		'Database/TableBuilder',
		'Database/TableDefinition',

		'specials/SpecialEntityData',

	);

	foreach ( $testFiles as $file ) {
		$files[] = __DIR__ . '/../tests/phpunit/includes/' . $file . 'Test.php';
	}

	return true;
	// @codeCoverageIgnoreEnd
};
