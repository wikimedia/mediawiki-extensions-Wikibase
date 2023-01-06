<?php

namespace Wikibase\Lib\Store;

use MediaWiki\Revision\SlotRecord;
use Wikimedia\Assert\Assert;

/**
 * Utility functions for Wikibase namespaces.
 *
 * @license GPL-2.0-or-later
 */
class EntityNamespaceLookup {

	/**
	 * @var int[]
	 */
	private $entityNamespaces;

	/**
	 * @var string[]
	 */
	private $entitySlots;

	/**
	 * @param int[] $entityNamespaces
	 * @param string[] $entitySlots
	 */
	public function __construct( array $entityNamespaces, array $entitySlots = [] ) {
		Assert::parameterElementType( 'integer', $entityNamespaces, '$entityNamespaces' );
		Assert::parameterElementType( 'string', $entitySlots, '$entitySlots' );

		$this->entityNamespaces = $entityNamespaces;
		$this->entitySlots = $entitySlots;
	}

	/**
	 * Returns a list of entity types pointing to the ids of the namespaces
	 * in which they reside.
	 *
	 * @return int[] Array mapping entity type strings to namespace IDs
	 */
	public function getEntityNamespaces(): array {
		return $this->entityNamespaces;
	}

	/**
	 * @return string[]
	 */
	public function getEntitySlots(): array {
		return $this->entitySlots;
	}

	/**
	 * @param string $entityType
	 *
	 * @return int|null The namespace ID number associated with the given entity type, or null if
	 *  $entityType is not a know entity type identifier.
	 */
	public function getEntityNamespace( $entityType ) {
		return $this->entityNamespaces[$entityType] ?? null;
	}

	/**
	 * @param string $entityType
	 *
	 * @return string The role name of the slot that this kind of entity is stored in.
	 *         In dedicated entity namespaces, this will be the "main" slot, but
	 *         other slots may be used when entities are "attached" to other kinds of
	 *         pages.
	 */
	public function getEntitySlotRole( $entityType ) {
		return $this->entitySlots[$entityType] ?? SlotRecord::MAIN;
	}

	/**
	 * Determines whether the given namespace contains some kind of Wikibase entity.
	 * This will return true if pages in this namespace may contain entities in any slot.
	 *
	 * @see isEntityNamespace()
	 *
	 * @param int $ns the namespace ID
	 *
	 * @return bool true if $ns is an entity namespace
	 */
	public function isNamespaceWithEntities( $ns ) {
		return in_array( $ns, $this->entityNamespaces, true );
	}

	/**
	 * Determines whether the given namespace is reserved for holding some kind of Wikibase entity.
	 * Note that this will return only if the namespace contains entities in the page's main slots.
	 * When other slots are used to "attach" entities to other kind of content, this returns false.
	 *
	 * @see isNamespaceWithEntities()
	 *
	 * @param int $ns the namespace ID
	 *
	 * @return bool true if $ns is an entity namespace
	 */
	public function isEntityNamespace( $ns ) {
		$entityType = array_search( $ns, $this->entityNamespaces, true );

		return $entityType !== false && $this->getEntitySlotRole( $entityType ) === SlotRecord::MAIN;
	}

	/**
	 * @param int $ns
	 *
	 * @return string|null
	 */
	public function getEntityType( $ns ) {
		return array_search( $ns, $this->entityNamespaces, true ) ?: null;
	}

	/**
	 * @param EntityNamespaceLookup $nsLookup
	 * @return EntityNamespaceLookup
	 */
	public function merge( EntityNamespaceLookup $nsLookup ): EntityNamespaceLookup {
		return new self(
			array_merge( $this->entityNamespaces, $nsLookup->getEntityNamespaces() ),
			array_merge( $this->entitySlots, $nsLookup->getEntitySlots() )
		);
	}
}
