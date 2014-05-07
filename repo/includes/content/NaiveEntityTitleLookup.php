<?php

namespace Wikibase;

use MWException;
use Title;

/**
 * EntityTitleLookup implementation based on constructing MediaWiki page titles programmatically
 * by mapping entity types to namespaces.
 *
 * @since 0.2
 *
 * @licence GNU GPL v2+
 * @author Daniel Kinzler
 */
class NaiveEntityTitleLookup implements EntityTitleLookup {

	/**
	 * @since 0.5
	 *
	 * @var array
	 */
	protected $namespaceMap;

	/**
	 * @param array $namespaceMap Entity type -> namespace mapping
	 */
	public function __construct( array $namespaceMap ) {
		$this->namespaceMap = $namespaceMap;
	}

	/**
	 * Determines whether the given namespace is designated to hold some kind of Wikibase entity.
	 * Shorthand for in_array( $ns, self::getEntityNamespaces() );
	 *
	 * @since 0.5
	 *
	 * @param int $namespaceId
	 *
	 * @return bool True iff $namespaceId is an entity namespace
	 */
	public function isEntityNamespace( $namespaceId ) {
		return in_array( $namespaceId, $this->getEntityNamespaces() );
	}

	/**
	 * Returns a list of namespace IDs that are used to represent Wikibase entities.
	 *
	 * @since 0.5
	 *
	 * @return int[]
	 */
	public function getEntityNamespaces() {
		return $this->namespaceMap;
	}

	/**
	 * Returns the Title object for the item with provided id.
	 *
	 * @since 0.3
	 *
	 * @param EntityId $id
	 *
	 * @throws MWException
	 * @return Title
	 */
	public function getTitleForId( EntityId $id ) {
		return Title::newFromText(
			$id->getSerialization(),
			$this->getNamespaceForType( $id->getEntityType() )
		);
	}

	/**
	 * Determines what namespace is suitable for the given type of entities.
	 *
	 * @since 0.5
	 *
	 * @param int $type
	 *
	 * @throws \OutOfBoundsException if the given entity type isn't associated with any namespace
	 * @return int
	 */
	public function getNamespaceForType( $type ) {
		if ( !isset( $this->namespaceMap[$type] ) ) {
			throw new \OutOfBoundsException( "No namespace registered for entity type $type" );
		}

		return $this->namespaceMap[$type];
	}
}
