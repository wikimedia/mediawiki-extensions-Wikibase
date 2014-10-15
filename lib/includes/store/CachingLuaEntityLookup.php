<?php

namespace Wikibase\Lib\Store;

use BagOStuff;
use ScribuntoException;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\Client\Scribunto\WikibaseLuaBindings;

/**
 * @since 0.5
 *
 * @licence GNU GPL v2+
 * @author Katie Filbert < aude.wiki@gmail.com >
 * @author Jens Ohlig < jens.ohlig@wikimedia.de >
 */
class CachingLuaEntityLookup {

	/**
	 * @since 0.5
	 */
	public function __construct(
		WikibaseLuaBindings $wbLibrary,
		BagOStuff $cache,
		$cacheKeyPrefix,
		$cacheDuration = 86400
	) {
		$this->wbLibrary = $wbLibrary;
		$this->cache = $cache;
		$this->cacheKeyPrefix = $cacheKeyPrefix;
		$this->cacheDuration = $cacheDuration;
	}

	/**
	 * Wrapper for getEntity in Scribunto_LuaWikibaseLibraryImplementation
	 *
	 * @since 0.5
	 *
	 * @param string $prefixedEntityId
	 * @param bool $legacyStyle Whether to return a legacy style entity
	 *
	 * @throws ScribuntoException
	 * @return array
	 */
	public function getEntity( $prefixedEntityId, $legacyStyle ) {
		$key = $this->getCacheKey( $prefixedEntityId, $legacyStyle );
		$entity = $this->cache->get( $key );

		if ( !$entity ) {
			$entity = $this->fetchEntity( $prefixedEntityId, $legacyStyle );
			$this->cache->set( $key, $entity );
		}

		return $entity;
	}

	/**
	 * @param string $prefixedEntityId
	 * @param bool $legacyStyle
	 *
	 * @return string
	 */
	private function getCacheKey( $prefixedEntityId, $legacyStyle ) {
		$cacheKey = $this->cacheKeyPrefix . ":CachingLuaEntityLookup:$prefixedEntityId";

		if ( $legacyStyle ) {
			$cacheKey . ':legacy';
		}

		return $cacheKey;
	}

	/**
	 * @param EntityId $entityId
	 */
	public function invalidateCacheEntry( EntityId $entityId ) {
		$key = $this->getCacheKey( $entityId->getSerialization() );

		$this->cache->delete( $key );
		$this->cache->delete( $key . ':legacy' );
	}

	/**
	 * @param string $prefixedEntityId
	 * @param boolean $legacyStyle
	 *
	 * @throws ScribuntoException
	 * @return array
	 */
	private function fetchEntity( $prefixedEntityId, $legacyStyle ) {
		try {
			$entityArr = $this->wbLibrary->getEntity( $prefixedEntityId, $legacyStyle );
			return array( $entityArr );
		}
		catch ( EntityIdParsingException $e ) {
			throw new ScribuntoException( 'wikibase-error-invalid-entity-id' );
		}
		catch ( \Exception $e ) {
			throw new ScribuntoException( 'wikibase-error-serialize-error' );
		}
	}

}
