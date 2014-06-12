<?php

// This is a IDE helper to understand class aliasing.
// It should not be included anywhere.
// Actual aliasing happens in the entry point using class_alias.

namespace { throw new Exception( 'This code is not meant to be executed' ); }

namespace Wikibase {

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

	/**
	 * @deprecated since 0.5, use the base class instead.
	 */
	class EntityId extends \Wikibase\DataModel\Entity\EntityId {}

	/**
	 * @deprecated since 0.6, use the base class instead.
	 */
	class Reference extends \Wikibase\DataModel\Reference {}

	/**
	 * @deprecated since 0.6, use the base class instead.
	 */
	class ReferenceList extends \Wikibase\DataModel\ReferenceList {}

	/**
	 * @deprecated since 0.6, use the base class instead.
	 */
	interface References extends \Wikibase\DataModel\References {}

	/**
	 * @deprecated since 0.6, use the base class instead.
	 */
	class HashableObjectStorage extends \Wikibase\DataModel\HashableObjectStorage {}

	/**
	 * @deprecated since 0.6, use the base class instead.
	 */
	abstract class HashArray extends \Wikibase\DataModel\HashArray {}

	/**
	 * @deprecated since 0.6, use the base class instead.
	 */
	interface MapHasher extends \Wikibase\DataModel\MapHasher {}

	/**
	 * @deprecated since 0.6, use the base class instead.
	 */
	class MapValueHasher extends \Wikibase\DataModel\MapValueHasher {}

	/**
	 * @deprecated since 0.6, use the base class instead.
	 */
	class ByPropertyIdArray extends \Wikibase\DataModel\ByPropertyIdArray {}

	/**
	 * @deprecated since 0.6, use the base class instead.
	 */
	class Claim extends \Wikibase\DataModel\Claim\Claim {}
	/**
	 * @deprecated since 0.6, use the base class instead.
	 */
	interface ClaimAggregate extends \Wikibase\DataModel\Claim\ClaimAggregate {}
	/**
	 * @deprecated since 0.6, use the base class instead.
	 */
	interface ClaimListAccess extends \Wikibase\DataModel\Claim\ClaimListAccess {}
	/**
	 * @deprecated since 0.6, use the base class instead.
	 */
	class Claims extends \Wikibase\DataModel\Claim\Claims {}
	/**
	 * @deprecated since 0.6, use the base class instead.
	 */
	class Statement extends \Wikibase\DataModel\Claim\Statement {}
	/**
	 * @deprecated since 0.6, use the base class instead.
	 */
	abstract class Entity extends \Wikibase\DataModel\Entity\Entity {}
	/**
	 * @deprecated since 0.6, use the base class instead.
	 */
	class Item extends \Wikibase\DataModel\Entity\Item {}
	/**
	 * @deprecated since 0.6, use the base class instead.
	 */
	class Property extends \Wikibase\DataModel\Entity\Property {}
	/**
	 * @deprecated since 0.6, use the base class instead.
	 */
	class PropertyNoValueSnak extends \Wikibase\DataModel\Snak\PropertyNoValueSnak {}
	/**
	 * @deprecated since 0.6, use the base class instead.
	 */
	class PropertySomeValueSnak extends \Wikibase\DataModel\Snak\PropertySomeValueSnak {}
	/**
	 * @deprecated since 0.6, use the base class instead.
	 */
	class PropertyValueSnak extends \Wikibase\DataModel\Snak\PropertyValueSnak {}
	/**
	 * @deprecated since 0.6, use the base class instead.
	 */
	interface Snak extends \Wikibase\DataModel\Snak\Snak {}
	/**
	 * @deprecated since 0.6, use the base class instead.
	 */
	class SnakList extends \Wikibase\DataModel\Snak\SnakList {}
	/**
	 * @deprecated since 0.6, use the base class instead.
	 */
	abstract class SnakObject extends \Wikibase\DataModel\Snak\SnakObject {}
	/**
	 * @deprecated since 0.6, use the base class instead.
	 */
	interface SnakRole extends \Wikibase\DataModel\Snak\SnakRole {}
	/**
	 * @deprecated since 0.6, use the base class instead.
	 */
	interface Snaks extends \Wikibase\DataModel\Snak\Snaks {}
	/**
	 * @deprecated since 0.6, use the base class instead.
	 */
	class ItemDiff extends \Wikibase\DataModel\Entity\Diff\ItemDiff {}
	/**
	 * @deprecated since 0.6, use the base class instead.
	 */
	class EntityDiff extends \Wikibase\DataModel\Entity\Diff\EntityDiff {}

}

namespace Wikibase\DataModel {

	/**
	 * @deprecated since 0.6, use the base class instead.
	 */
	class SimpleSiteLink extends SiteLink {}

}

namespace Wikibase\DataModel\Entity {

	/**
	 * @deprecated since 1.0, use the base class instead.
	 */
	class ItemDiff extends \Wikibase\DataModel\Entity\Diff\ItemDiff {}
	/**
	 * @deprecated since 1.0, use the base class instead.
	 */
	class EntityDiff extends \Wikibase\DataModel\Entity\Diff\EntityDiff {}

}
