<?php

/**
 * Class registration file for the DataModel component of Wikibase.
 *
 * @since 0.4
 *
 * @file
 * @ingroup WikibaseDataModel
 *
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
return call_user_func( function() {

	$classes = array(
		'Wikibase\Claim' => 'DataModel/Claim/Claim.php',
		'Wikibase\ClaimAggregate' => 'DataModel/Claim/ClaimAggregate.php',
		'Wikibase\ClaimListAccess' => 'DataModel/Claim/ClaimListAccess.php',
		'Wikibase\Claims' => 'DataModel/Claim/Claims.php',
		'Wikibase\Statement' => 'DataModel/Claim/Statement.php',
		'Wikibase\StatementObject' => 'DataModel/Claim/Statement.php', // Deprecated

		'Wikibase\Entity' => 'DataModel/Entity/Entity.php',
		'Wikibase\Item' => 'DataModel/Entity/Item.php',
		'Wikibase\ItemObject' => 'DataModel/Entity/Item.php',
		'Wikibase\Property' => 'DataModel/Entity/Property.php',

		'Wikibase\PropertyNoValueSnak' => 'DataModel/Snak/PropertyNoValueSnak.php',
		'Wikibase\PropertySnak' => 'DataModel/Snak/PropertySnak.php',
		'Wikibase\PropertyValueSnak' => 'DataModel/Snak/PropertyValueSnak.php',
		'Wikibase\PropertySomeValueSnak' => 'DataModel/Snak/PropertySomeValueSnak.php',
		'Wikibase\Snak' => 'DataModel/Snak/Snak.php',
		'Wikibase\SnakList' => 'DataModel/Snak/SnakList.php',
		'Wikibase\SnakObject' => 'DataModel/Snak/SnakObject.php',
		'Wikibase\SnakRole' => 'DataModel/Snak/SnakRole.php',
		'Wikibase\Snaks' => 'DataModel/Snak/Snaks.php',

		'Wikibase\ByPropertyIdArray' => 'DataModel/ByPropertyIdArray.php',
		'Wikibase\EntityDiff' => 'DataModel/EntityDiff.php',
		'Wikibase\HashableObjectStorage' => 'DataModel/HashableObjectStorage.php',
		'Wikibase\HashArray' => 'DataModel/HashArray.php',
		'Wikibase\ItemDiff' => 'DataModel/ItemDiff.php',
		'Wikibase\MapHasher' => 'DataModel/MapHasher.php',
		'Wikibase\MapValueHasher' => 'DataModel/MapValueHasher.php',
		'Wikibase\Reference' => 'DataModel/Reference.php',
		'Wikibase\ReferenceObject' => 'DataModel/Reference.php', // Deprecated
		'Wikibase\ReferenceList' => 'DataModel/ReferenceList.php',
		'Wikibase\References' => 'DataModel/References.php',
		'Wikibase\Parser' => 'DataModel/Parser.php',
	);

	return $classes;

} );
