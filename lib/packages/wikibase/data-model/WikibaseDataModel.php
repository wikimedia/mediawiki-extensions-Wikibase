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

define( 'WIKIBASE_DATAMODEL_VERSION', '4.4.0' );

if ( defined( 'MEDIAWIKI' ) ) {
	call_user_func( function() {
		require_once __DIR__ . '/WikibaseDataModel.mw.php';
	} );
}

// Aliases introduced in 3.0.0
class_alias( 'Wikibase\DataModel\Statement\Statement', 'Wikibase\DataModel\Claim\Claim' );
class_alias( 'Wikibase\DataModel\Statement\StatementGuid', 'Wikibase\DataModel\Claim\ClaimGuid' );
class_alias( 'Wikibase\DataModel\Statement\StatementListProvider', 'Wikibase\DataModel\StatementListProvider' );
