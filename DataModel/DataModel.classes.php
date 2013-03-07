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

	$wgAutoloadClasses['Wikibase\Claim'] 				= $dir . 'includes/claim/Claim.php';
	$wgAutoloadClasses['Wikibase\ClaimAggregate'] 		= $dir . 'includes/claim/ClaimAggregate.php';
	$wgAutoloadClasses['Wikibase\ClaimListAccess'] 		= $dir . 'includes/claim/ClaimListAccess.php';
	$wgAutoloadClasses['Wikibase\Claims'] 				= $dir . 'includes/claim/Claims.php';

	$wgAutoloadClasses['Wikibase\Item'] 				= $dir . 'includes/item/Item.php';
	$wgAutoloadClasses['Wikibase\ItemObject'] 			= $dir . 'includes/item/Item.php';

	$wgAutoloadClasses['Wikibase\Entity'] 				= $dir . 'includes/entity/Entity.php';
	$wgAutoloadClasses['Wikibase\EntityId'] 			= $dir . 'includes/entity/EntityId.php';

	$wgAutoloadClasses['Wikibase\Property'] 				= $dir . 'includes/property/Property.php';
	$wgAutoloadClasses['Wikibase\Reference'] 				= $dir . 'includes/reference/Reference.php';
	$wgAutoloadClasses['Wikibase\ReferenceList'] 			= $dir . 'includes/reference/ReferenceList.php';
	$wgAutoloadClasses['Wikibase\ReferenceObject'] 			= $dir . 'includes/reference/Reference.php';
	$wgAutoloadClasses['Wikibase\References'] 				= $dir . 'includes/reference/References.php';

	$wgAutoloadClasses['Wikibase\Query'] = __DIR__ . '/../includes/query/Query.php';

	$wgAutoloadClasses['Wikibase\SiteLink'] 				= $dir . 'includes/SiteLink.php';

	$wgAutoloadClasses['Wikibase\Statement'] 				= $dir . 'includes/statement/Statement.php';
	$wgAutoloadClasses['Wikibase\StatementObject'] 			= $dir . 'includes/statement/Statement.php';

	$wgAutoloadClasses['Wikibase\PropertyNoValueSnak'] 		= $dir . 'includes/snak/PropertyNoValueSnak.php';
	$wgAutoloadClasses['Wikibase\PropertySnak'] 			= $dir . 'includes/snak/PropertySnak.php';
	$wgAutoloadClasses['Wikibase\PropertyValueSnak'] 		= $dir . 'includes/snak/PropertyValueSnak.php';
	$wgAutoloadClasses['Wikibase\PropertySomeValueSnak'] 	= $dir . 'includes/snak/PropertySomeValueSnak.php';
	$wgAutoloadClasses['Wikibase\Snak'] 					= $dir . 'includes/snak/Snak.php';
	$wgAutoloadClasses['Wikibase\SnakFactory'] 				= $dir . 'includes/SnakFactory.php';
	$wgAutoloadClasses['Wikibase\SnakList'] 				= $dir . 'includes/snak/SnakList.php';
	$wgAutoloadClasses['Wikibase\SnakObject'] 				= $dir . 'includes/snak/SnakObject.php';
	$wgAutoloadClasses['Wikibase\Snaks'] 					= $dir . 'includes/snak/Snaks.php';
	$wgAutoloadClasses['Wikibase\SnakFactory'] 				= $dir . 'includes/SnakFactory.php';

	$classes = array(

	);

	return $classes;

} );
