<?php

/**
 * MediaWiki setup for the DataValues component of Wikibase.
 * The component should be included via the main entry point, DataValues.php.
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
 * @ingroup WikibaseDataModel
 *
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */

if ( !defined( 'WIKIBASE_DATAMODEL_VERSION' ) ) {
	die( 'Not an entry point.' );
}

global $wgExtensionCredits, $wgExtensionMessagesFiles, $wgAutoloadClasses, $wgHooks;

$wgExtensionCredits['wikibase'][] = array(
	'path' => __DIR__,
	'name' => 'Wikibase DataModel',
	'version' => WIKIBASE_DATAMODEL_VERSION,
	'author' => array(
		'[https://www.mediawiki.org/wiki/User:Jeroen_De_Dauw Jeroen De Dauw]',
	),
	'url' => 'https://www.mediawiki.org/wiki/Extension:Wikibase_DataModel',
	'descriptionmsg' => 'wikibasedatamodel-desc'
);

$wgExtensionMessagesFiles['WikibaseDataModel'] = __DIR__ . '/DataModel.i18n.php';

// Autoloading
foreach ( include( __DIR__ . '/DataModel.classes.php' ) as $class => $file ) {
	$wgAutoloadClasses[$class] = __DIR__ . '/' . $file;
}

if ( defined( 'MW_PHPUNIT_TEST' ) ) {
	$wgAutoloadClasses['Wikibase\Test\ClaimTest'] = __DIR__ . '/tests/phpunit/Claim/ClaimTest.php';
	$wgAutoloadClasses['Wikibase\Test\EntityTest'] = __DIR__ . '/tests/phpunit/Entity/EntityTest.php';
	$wgAutoloadClasses['Wikibase\Test\TestItems'] = __DIR__  . '/tests/phpunit/Entity/TestItems.php';
	$wgAutoloadClasses['Wikibase\Test\SnakObjectTest'] = __DIR__  . '/tests/phpunit/Snak/SnakObjectTest.php';

	$wgAutoloadClasses['Wikibase\Test\HashArrayTest'] = __DIR__ . '/tests/phpunit/hasharray/HashArrayTest.php';
	$wgAutoloadClasses['Wikibase\Test\HashArrayElement'] = __DIR__ . '/tests/phpunit/hasharray/HashArrayElement.php';
}

/**
 * Hook to add PHPUnit test cases.
 * @see https://www.mediawiki.org/wiki/Manual:Hooks/UnitTestsList
 *
 * @since 0.4
 *
 * @param array $files
 *
 * @return boolean
 */
$wgHooks['UnitTestsList'][]	= function( array &$files ) {
	// @codeCoverageIgnoreStart
	$testFiles = array(
		'Claim/ClaimAggregate',
		'Claim/ClaimListAccess',
		'Claim/Claims',
		'Claim/Claim',
		'Claim/Statement',

		'Entity/EntityId',
		'Entity/ItemMultilangTexts',
		'Entity/ItemNewEmpty',
		'Entity/ItemNewFromArray',
		'Entity/Item',
		'Entity/Property',

		'Snak/PropertyValueSnak',
		'Snak/SnakList',
		'Snak/Snak',

		'HashableObjectStorage',
		'MapValueHasher',

		'ReferenceList',
		'Reference',

		'SiteLink',

		'hasharray/HashArrayWithoutDuplicates',
		'hasharray/HashArrayWithDuplicates',
	);

	foreach ( $testFiles as $file ) {
		$files[] = __DIR__ . '/tests/phpunit/' . $file . 'Test.php';
	}

	return true;
	// @codeCoverageIgnoreEnd
};
