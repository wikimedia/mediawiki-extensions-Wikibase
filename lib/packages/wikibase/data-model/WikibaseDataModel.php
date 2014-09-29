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

// Aliases introduced in 0.5
class_alias( 'Wikibase\DataModel\Entity\EntityId', 'Wikibase\EntityId' );

// Aliases introduced in 0.6
class_alias( 'Wikibase\DataModel\Reference', 'Wikibase\Reference' );
class_alias( 'Wikibase\DataModel\References', 'Wikibase\References' );
class_alias( 'Wikibase\DataModel\ReferenceList', 'Wikibase\ReferenceList' );
class_alias( 'Wikibase\DataModel\HashableObjectStorage', 'Wikibase\HashableObjectStorage' );
class_alias( 'Wikibase\DataModel\HashArray', 'Wikibase\HashArray' );
class_alias( 'Wikibase\DataModel\Internal\MapHasher', 'Wikibase\MapHasher' );
class_alias( 'Wikibase\DataModel\ByPropertyIdArray', 'Wikibase\ByPropertyIdArray' );
class_alias( 'Wikibase\DataModel\Claim\Claim', 'Wikibase\Claim' );
class_alias( 'Wikibase\DataModel\Claim\ClaimListAccess', 'Wikibase\ClaimListAccess' );
class_alias( 'Wikibase\DataModel\Claim\Claims', 'Wikibase\Claims' );
class_alias( 'Wikibase\DataModel\Statement\Statement', 'Wikibase\Statement' );
class_alias( 'Wikibase\DataModel\Entity\Entity', 'Wikibase\Entity' );
class_alias( 'Wikibase\DataModel\Entity\Item', 'Wikibase\Item' );
class_alias( 'Wikibase\DataModel\Entity\Property', 'Wikibase\Property' );
class_alias( 'Wikibase\DataModel\Snak\PropertyNoValueSnak', 'Wikibase\PropertyNoValueSnak' );
class_alias( 'Wikibase\DataModel\Snak\PropertySomeValueSnak', 'Wikibase\PropertySomeValueSnak' );
class_alias( 'Wikibase\DataModel\Snak\PropertyValueSnak', 'Wikibase\PropertyValueSnak' );
class_alias( 'Wikibase\DataModel\Snak\Snak', 'Wikibase\Snak' );
class_alias( 'Wikibase\DataModel\Snak\SnakList', 'Wikibase\SnakList' );
class_alias( 'Wikibase\DataModel\Snak\SnakObject', 'Wikibase\SnakObject' );
class_alias( 'Wikibase\DataModel\Snak\SnakRole', 'Wikibase\SnakRole' );
class_alias( 'Wikibase\DataModel\Snak\Snaks', 'Wikibase\Snaks' );
class_alias( 'Wikibase\DataModel\Entity\Diff\ItemDiff', 'Wikibase\ItemDiff' );
class_alias( 'Wikibase\DataModel\Entity\Diff\EntityDiff', 'Wikibase\EntityDiff' );
class_alias( 'Wikibase\DataModel\SiteLink', 'Wikibase\DataModel\SimpleSiteLink' );

// Aliases introduced in 0.8.2
class_alias( 'Wikibase\DataModel\LegacyIdInterpreter', 'Wikibase\DataModel\Internal\LegacyIdInterpreter' );

// Aliases introduced in 1.0
class_alias( 'Wikibase\DataModel\Entity\Diff\EntityDiff', 'Wikibase\DataModel\Entity\EntityDiff' );
class_alias( 'Wikibase\DataModel\Entity\Diff\ItemDiff', 'Wikibase\DataModel\Entity\ItemDiff' );
class_alias( 'Wikibase\DataModel\Statement\Statement', 'Wikibase\DataModel\Claim\Statement' );
