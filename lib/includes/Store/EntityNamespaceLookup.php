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
	 *
	 * @return array [ entity type (string) -> namespace id (integer) ]
	 */
	public function getEntityNamespaces() {
		return $this->entityNamespaces;
	}

	/**
	 * Returns the namespace ID for the given entity type, or false if the parameter
	 * is not a known entity type.
	 *
	 * The return value is based on getEntityNamespaces(), which is configured via
	 * $wgWBRepoSettings['entityNamespaces'].
	 *
	 * @since 0.4
	 *
	 * @param string $entityType the entity type
	 *
	 * @return int|bool the namespace associated with the given entity type (or false if there is none)
	 */
	public function getEntityNamespace( $entityType ) {
		return isset( $this->entityNamespaces[$entityType] ) ? $this->entityNamespaces[$entityType] : false;
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
		return in_array( $ns, $this->entityNamespaces );
	}

}
