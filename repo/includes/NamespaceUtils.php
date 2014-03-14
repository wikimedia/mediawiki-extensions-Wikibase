<?php

namespace Wikibase;

use Title;
use Wikibase\Repo\WikibaseRepo;

/**
 * Utility functions for Wikibase namespaces.
 *
 * @since 0.4
 *
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 * @author Tobias Gritschacher
 * @author Jens Ohlig < jens.ohlig@wikimedia.de >
 */
final class NamespaceUtils {

	/**
	 * @var array
	 */
	private $entityNamespaces;

	/**
	 * @param array $entityNamespaces
	 */
	public function __construct( array $entityNamespaces = null ) {
		$this->entityNamespaces = is_array( $entityNamespaces )
			? $entityNamespaces
			: WikibaseRepo::getDefaultInstance()->getSettings()->getSetting( 'entityNamespaces' );

		if ( !is_array( $this->entityNamespaces ) ) {
			throw new UnexpectedValueException( 'entityNamespaces must be an array' );
		}
	}

	/**
	 * @param Title $title
	 *
	 * @return boolean
	 */
	public function isTitleInEntityNamespace( Title $title ) {
		$entityNamespaces = array_flip( $this->entityNamespaces );
		$namespace = $title->getNamespace();

		return array_key_exists( $namespace, $entityNamespaces );
	}

	/**
	 * Returns a list of entity content model ids pointing to the ids of the namespaces in which they reside.
	 *
	 * @since 0.4
	 *
	 * @return array [ content model id (string) -> namespace id (integer) ]
	 */
	public static function getEntityNamespaces() {
		$namespaces = WikibaseRepo::getDefaultInstance()->
			getSettings()->getSetting( 'entityNamespaces' );

		if ( !is_array( $namespaces ) ) {
			return array();
		}

		return $namespaces;
	}

	/**
	 * Returns the namespace ID for the given entity content model, or false if the content model
	 * is not a known entity model.
	 *
	 * The return value is based on getEntityNamespaces(), which is configured via $wgWBRepoSettings['entityNamespaces'].
	 *
	 * @since 0.4
	 *
	 * @param String $model the model ID
	 *
	 * @return int|bool the namespace associated with the given content model (or false if there is none)
	 */
	public static function getEntityNamespace( $model ) {
		$namespaces = self::getEntityNamespaces();
		return isset( $namespaces[$model] ) ? $namespaces[$model] : false;
	}

	/**
	 * Determines whether the given namespace is designated to hold some kind of Wikibase entity.
	 * Shorthand for in_array( $ns, self::getEntityNamespaces() );
	 *
	 * @since 0.4
	 *
	 * @param int $ns the namespace ID
	 *
	 * @return bool true iff $ns is an entity namespace
	 */
	public static function isEntityNamespace( $ns ) {
		return in_array( $ns, self::getEntityNamespaces() );
	}

	/**
	 * Determines whether the given namespace is a core namespace, i.e. a namespace pre-defined by MediaWiki core.
	 *
	 * The present implementation just checks whether the namespace ID is smaller than 100, relying on the
	 * convention that namespace IDs smaller than 100 are reserved for use by MediaWiki core.
	 *
	 * @since 0.4
	 *
	 * @param int $ns the namespace ID
	 *
	 * @return bool true iff $ns is a core namespace
	 */
	public static function isCoreNamespace( $ns ) {
		return $ns < 100;
	}

}
