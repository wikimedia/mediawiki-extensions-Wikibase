<?php

/**
 * Entry point for the Wikibase DataModel component.
 *
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */

if ( defined( 'WIKIBASE_DATAMODEL_VERSION' ) ) {
	// Do not initialize more then once.
	return 1;
}

define( 'WIKIBASE_DATAMODEL_VERSION', '0.6 alpha'  );

// Aliasing of classes that got renamed.
// For more details, see Aliases.php.
class_alias( 'Wikibase\Item', 'Wikibase\ItemObject' );
class_alias( 'Wikibase\Reference', 'Wikibase\ReferenceObject' );
class_alias( 'Wikibase\Claim', 'Wikibase\ClaimObject' );
class_alias( 'Wikibase\Statement', 'Wikibase\StatementObject' );
class_alias( 'Wikibase\DataModel\Entity\EntityId', 'Wikibase\EntityId' );

// Temporary global to migrate away from DataValueFactory::singleton.
// It should not be used outside this component and should not be used
// for any code that did not before use DataValueFactory::singleton.
$GLOBALS['evilDataValueDeserializer'] = new \DataValues\Deserializers\DataValueDeserializer(
	array(
		'string' => 'DataValues\StringValue',
		'number' => 'DataValues\NumberValue',
		// TODO
	)
);

if ( defined( 'MEDIAWIKI' ) ) {
	call_user_func( function() {
		require_once __DIR__ . '/WikibaseDataModel.mw.php';
	} );
}
