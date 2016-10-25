<?php

namespace Wikibase\Lib\Store;

use Wikimedia\Assert\Assert;

/**
 * Utility functions for Wikibase namespaces.
 *
 * @since 0.4
 *
 * @license GPL-2.0+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 * @author Tobias Gritschacher
 * @author Jens Ohlig < jens.ohlig@wikimedia.de >
 * @author Katie Filbert < aude.wiki@gmail.com >
 * @author Adrian Heine <adrian.heine@wikimedia.de>
 * @author Thiemo Mättig
 */
final class EntityNamespaceLookup {

	/**
	 * @var int[]
	 */
	private $entityNamespaces;

	/**
	 * @param int[] $entityNamespaces
	 */
	public function __construct( array $entityNamespaces ) {
		Assert::parameterElementType( 'integer', $entityNamespaces, '$entityNamespaces' );
		$this->entityNamespaces = $entityNamespaces;
	}

	/**
	 * Returns a list of entity types pointing to the ids of the namespaces
	 * in which they reside.
	 *
	 * @since 0.4
	 * @deprecated since 0.5, use self::getEntityType instead
	 *
	 * @return array [ entity type (string) -> namespace id (integer) ]
	 */
	public function getEntityNamespaces() {
		return $this->entityNamespaces;
	}

	/**
	 * @since 0.5
	 *
	 * @param string $entityType
	 *
	 * @return int|null The namespace ID number associated with the given entity type, or null if
	 *  $entityType is not a know entity type identifier.
	 */
	public function getEntityNamespace( $entityType ) {
		return isset( $this->entityNamespaces[$entityType] )
			? $this->entityNamespaces[$entityType]
			: null;
	}

	/**
	 * Determines whether the given namespace is designated to hold some kind of Wikibase entity.
	 * Shorthand for in_array( $ns, self::getEntityNamespaces() );
	 *
	 * @since 0.4
	 *
	 * @param int $ns the namespace ID
	 *
	 * @return bool true if $ns is an entity namespace
	 */
	public function isEntityNamespace( $ns ) {
		return in_array( $ns, $this->entityNamespaces, true );
	}

	/**
	 * @since 0.5
	 *
	 * @param int $ns
	 *
	 * @return string|null
	 */
	public function getEntityType( $ns ) {
		return array_search( $ns, $this->entityNamespaces, true ) ?: null;
	}

}
