<?php

/**
 * Test class autoloader for the Wikibase DataModel component.
 *
 * @since 0.1
 *
 * @file
 * @ingroup DataModel
 *
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */

spl_autoload_register( function ( $className ) {
	$testClasses = array(
		'Wikibase\Test\ClaimTest' => 'Claim/ClaimTest.php',
		'Wikibase\Test\EntityTest' => 'Entity/EntityTest.php',
		'Wikibase\Test\TestItems' => 'Entity/TestItems.php',
		'Wikibase\Test\SnakObjectTest' => 'Snak/SnakObjectTest.php',

		'Wikibase\Test\HashArrayTest' => 'hasharray/HashArrayTest.php',
		'Wikibase\Test\HashArrayElement' => 'hasharray/HashArrayElement.php',
		'Wikibase\Test\EntityDiffOldTest' => 'EntityDiffOldTest.php',
	);

	if ( array_key_exists( $className, $testClasses ) ) {
		include_once __DIR__ . '/phpunit/' . $testClasses[$className];
	}
} );
