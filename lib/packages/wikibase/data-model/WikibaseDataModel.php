<?php

/**
 * Entry point for the Wikibase DataModel component.
 *
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */

if ( defined( 'WIKIBASE_DATAMODEL_VERSION' ) ) {
	// Do not initialize more than once.
	return 1;
}

define( 'WIKIBASE_DATAMODEL_VERSION', '1.1' );

if ( defined( 'MEDIAWIKI' ) ) {
	call_user_func( function() {
		require_once __DIR__ . '/WikibaseDataModel.mw.php';
	} );
}

// Aliasing of classes that got renamed.
// For more details, see Aliases.php.

// Aliases introduced in 0.6
class_alias( 'Wikibase\DataModel\SiteLink', 'Wikibase\DataModel\SimpleSiteLink' );

// Aliases introduced in 0.8.2
class_alias( 'Wikibase\DataModel\LegacyIdInterpreter', 'Wikibase\DataModel\Internal\LegacyIdInterpreter' );

// Aliases introduced in 1.0
class_alias( 'Wikibase\DataModel\Statement\Statement', 'Wikibase\DataModel\Claim\Statement' );
