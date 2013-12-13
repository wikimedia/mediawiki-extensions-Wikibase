<?php

// This is a IDE helper to understand class aliasing.
// It should not be included anywhere.
// Actual aliasing happens in the entry point using class_alias.

namespace { throw new Exception( 'This code is not meant to be executed' ); }

namespace Wikibase {

	/**
	 * @deprecated since 0.5, use the base class instead.
	 */
	class EntityId extends \Wikibase\DataModel\Entity\EntityId {}

	/**
	 * @deprecated since 0.4, use the base class instead.
	 */
	class ItemObject extends Item {}

	/**
	 * @deprecated since 0.4, use the base class instead.
	 */
	class ReferenceObject extends Reference {}

	/**
	 * @deprecated since 0.4, use the base class instead.
	 */
	class StatementObject extends Statement {}

	/**
	 * @deprecated since 0.4, use the base class instead.
	 */
	class ClaimObject extends Claim {}

	// Deprecated since 0.6, use the base class or interface instead.
	class Reference extends \Wikibase\DataModel\Reference {}
	class ReferenceList extends \Wikibase\DataModel\ReferenceList {}
	interface References extends \Wikibase\DataModel\References {}
	class HashableObjectStorage extends \Wikibase\DataModel\HashableObjectStorage {}
	abstract class HashArray extends \Wikibase\DataModel\HashArray {}
	interface MapHasher extends \Wikibase\DataModel\MapHasher {}
	class MapValueHasher extends \Wikibase\DataModel\MapValueHasher {}
	class ByPropertyIdArray extends \Wikibase\DataModel\ByPropertyIdArray {}
	class Claim extends \Wikibase\DataModel\Claim\Claim {}
	interface ClaimAggregate extends \Wikibase\DataModel\Claim\ClaimAggregate {}
	interface ClaimListAccess extends \Wikibase\DataModel\Claim\ClaimListAccess {}
	class Claims extends \Wikibase\DataModel\Claim\Claims {}
	class Statement extends \Wikibase\DataModel\Claim\Statement {}
	abstract class Entity extends \Wikibase\DataModel\Entity\Entity {}
	class Item extends \Wikibase\DataModel\Entity\Item {}
	class Property extends \Wikibase\DataModel\Entity\Property {}
	class PropertyNoValueSnak extends \Wikibase\DataModel\Snak\PropertyNoValueSnak {}
	interface PropertySnak extends \Wikibase\DataModel\Snak\PropertySnak {}
	class PropertySomeValueSnak extends \Wikibase\DataModel\Snak\PropertySomeValueSnak {}
	class PropertyValueSnak extends \Wikibase\DataModel\Snak\PropertyValueSnak {}
	interface Snak extends \Wikibase\DataModel\Snak\Snak {}
	class SnakList extends \Wikibase\DataModel\Snak\SnakList {}
	abstract class SnakObject extends \Wikibase\DataModel\Snak\SnakObject {}
	interface SnakRole extends \Wikibase\DataModel\Snak\SnakRole {}
	interface Snaks extends \Wikibase\DataModel\Snak\Snaks {}
	class ItemDiff extends \Wikibase\DataModel\Entity\ItemDiff {}
	class EntityDiff extends \Wikibase\DataModel\Entity\EntityDiff {}

}

namespace Wikibase\DataModel {

	// Deprecated since 0.6, use the base class or interface instead.
	class SimpleSiteLink extends SiteLink {}

}