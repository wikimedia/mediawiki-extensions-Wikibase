<?php

namespace Wikibase\DataAccess;

use Serializers\Serializer;
use Wikibase\DataModel\SerializerFactory;
use Wikibase\Lib\LanguageFallbackChainFactory;
use Wikibase\Lib\Store\EntityNamespaceLookup;
use Wikibase\Lib\Store\EntityStoreWatcher;
use Wikibase\Lib\StringNormalizer;

/**
 * Interface of the top-level container/factory of data access services.
 *
 * This is made up of DataAccessServices (which are repo or entity source specific),
 * and GenericServices (that doesn't currently have it's own interface)
 *
 * @license GPL-2.0-or-later
 */
interface WikibaseServices extends DataAccessServices {

	/**
	 * @return EntityNamespaceLookup
	 */
	public function getEntityNamespaceLookup();

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
	 * @return PrefetchingTermLookup
	 */
	public function getPrefetchingTermLookup();

}
