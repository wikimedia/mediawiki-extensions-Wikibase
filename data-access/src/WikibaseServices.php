<?php

namespace Wikibase\DataAccess;

use Serializers\Serializer;
use Wikibase\DataModel\SerializerFactory;
use Wikibase\DataModel\Services\Entity\EntityPrefetcher;
use Wikibase\DataModel\Services\Term\TermBuffer;
use Wikibase\LanguageFallbackChainFactory;
use Wikibase\Lib\Interactors\TermSearchInteractorFactory;
use Wikibase\Lib\Store\EntityInfoBuilder;
use Wikibase\Lib\Store\EntityNamespaceLookup;
use Wikibase\Lib\Store\EntityRevisionLookup;
use Wikibase\Lib\Store\EntityStoreWatcher;
use Wikibase\Lib\Store\PropertyInfoLookup;
use Wikibase\StringNormalizer;

/**
 * Interface of the top-level container/factory of data access services.
 *
 * @license GPL-2.0-or-later
 */
interface WikibaseServices {

	/**
	 * @return EntityInfoBuilder
	 */
	public function getEntityInfoBuilder();

	/**
	 * @return EntityNamespaceLookup
	 */
	public function getEntityNamespaceLookup();

	/**
	 * @return EntityPrefetcher
	 */
	public function getEntityPrefetcher();

	/**
	 * Note: Instance returned is not guaranteed to be a caching decorator.
	 * Callers should take care of caching themselves.
	 *
	 * @return EntityRevisionLookup
	 */
	public function getEntityRevisionLookup();

	/**
	 * Returns the entity serializer instance that generates the full (expanded) serialization.
	 *
	 * @return Serializer
	 */
	public function getFullEntitySerializer();

	/**
	 * Returns the entity serializer instance that generates the most compact serialization.
	 *
	 * @return Serializer
	 */
	public function getCompactEntitySerializer();

	/**
	 * Returns the entity serializer that generates serialization that is used in the storage layer.
	 *
	 * @return Serializer
	 */
	public function getStorageEntitySerializer();

	/**
	 * Returns a service that can be registered as a watcher to changes to entity data.
	 * Such watcher gets notified when entity is updated or deleted, or when the entity
	 * redirect is updated.
	 *
	 * @return EntityStoreWatcher
	 */
	public function getEntityStoreWatcher();

	/**
	 * @return LanguageFallbackChainFactory
	 */
	public function getLanguageFallbackChainFactory();

	/**
	 * Note: Instance returned is not guaranteed to be a caching decorator.
	 * Callers should take care of caching themselves.
	 *
	 * @return PropertyInfoLookup
	 */
	public function getPropertyInfoLookup();

	/**
	 * @return SerializerFactory A factory with knowledge about items, properties, and the elements
	 *  they are made of, but no other entity types. Returns serializers that generate the full
	 *  (expanded) serialization.
	 */
	public function getBaseDataModelSerializerFactory();

	/**
	 * @return SerializerFactory A factory with knowledge about items, properties, and the elements
	 *  they are made of, but no other entity types. Returns serializers that generate the most
	 *  compact serialization.
	 */
	public function getCompactBaseDataModelSerializerFactory();

	/**
	 * @return StringNormalizer
	 */
	public function getStringNormalizer();

	/**
	 * @return TermBuffer
	 */
	public function getTermBuffer();

	/**
	 * @return TermSearchInteractorFactory
	 */
	public function getTermSearchInteractorFactory();

}
