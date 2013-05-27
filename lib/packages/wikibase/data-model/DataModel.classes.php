<?php

/**
 * Class registration file for the DataModel component of Wikibase.
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
return call_user_func( function() {

	$classes = array(
		'Wikibase\Claim' => 'DataModel/Claim/Claim.php',
		'Wikibase\ClaimAggregate' => 'DataModel/Claim/ClaimAggregate.php',
		'Wikibase\ClaimListAccess' => 'DataModel/Claim/ClaimListAccess.php',
		'Wikibase\Claims' => 'DataModel/Claim/Claims.php',
		'Wikibase\Statement' => 'DataModel/Claim/Statement.php',
		'Wikibase\StatementObject' => 'DataModel/Claim/Statement.php', // Deprecated

		'Wikibase\Entity' => 'DataModel/Entity/Entity.php',
		'Wikibase\EntityId' => 'DataModel/Entity/EntityId.php',
		'Wikibase\Item' => 'DataModel/Entity/Item.php',
		'Wikibase\ItemObject' => 'DataModel/Entity/Item.php',
		'Wikibase\Property' => 'DataModel/Entity/Property.php',

		'Wikibase\PropertyNoValueSnak' => 'DataModel/Snak/PropertyNoValueSnak.php',
		'Wikibase\PropertySnak' => 'DataModel/Snak/PropertySnak.php',
		'Wikibase\PropertyValueSnak' => 'DataModel/Snak/PropertyValueSnak.php',
		'Wikibase\PropertySomeValueSnak' => 'DataModel/Snak/PropertySomeValueSnak.php',
		'Wikibase\Snak' => 'DataModel/Snak/Snak.php',
		'Wikibase\SnakFactory' => 'DataModel/Snak/SnakFactory.php',
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

		'Wikibase\SiteLink' => 'DataModel/SiteLink.php',
	);

	return $classes;

} );
