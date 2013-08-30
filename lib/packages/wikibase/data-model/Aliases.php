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

}