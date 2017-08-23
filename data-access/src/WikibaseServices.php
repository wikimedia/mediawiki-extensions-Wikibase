<?php

namespace Wikibase\DataAccess;

use Serializers\Serializer;
use Wikibase\DataModel\SerializerFactory;
use Wikibase\DataModel\Services\Entity\EntityPrefetcher;
use Wikibase\DataModel\Services\Term\TermBuffer;
use Wikibase\LanguageFallbackChainFactory;
use Wikibase\Lib\Interactors\TermSearchInteractorFactory;
use Wikibase\Lib\Store\EntityInfoBuilderFactory;
use Wikibase\Lib\Store\EntityNamespaceLookup;
use Wikibase\Lib\Store\EntityRevisionLookup;
use Wikibase\Lib\Store\EntityStoreWatcher;
use Wikibase\Lib\Store\PropertyInfoLookup;
use Wikibase\StringNormalizer;

/**
 * Interface of the top-level container/factory of data access services.
 *
 * @license GPL-2.0+
 */
interface WikibaseServices {

	/**
	 * @return EntityInfoBuilderFactory
	 */
	public function getEntityInfoBuilderFactory();

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
	 * @param int $options bitwise combination of the SerializerFactory::OPTION_ flags
	 *
	 * @return Serializer
	 */
	public function getEntitySerializer( $options = SerializerFactory::OPTION_DEFAULT );

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
	 * TODO: is getBaseDataModelSerializerFactory a better name for this method?
	 * @param int $options bitwise combination of the SerializerFactory::OPTION_ flags
	 *
	 * @return SerializerFactory A factory with knowledge about items, properties, and the elements
	 *  they are made of, but no other entity types.
	 */
	public function getSerializerFactory( $options = SerializerFactory::OPTION_DEFAULT );

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
