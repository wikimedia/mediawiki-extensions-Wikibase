<?php

namespace Wikibase\DataAccess;

use DataValues\Serializers\DataValueSerializer;
use Serializers\DispatchingSerializer;
use Serializers\Serializer;
use Wikibase\DataModel\SerializerFactory;
use Wikibase\LanguageFallbackChainFactory;
use Wikibase\Lib\EntityTypeDefinitions;
use Wikibase\Lib\Store\EntityNamespaceLookup;
use Wikibase\StringNormalizer;

/**
 * A container/factory of services which don't rely/require repository-specific configuration.
 *
 * @license GPL-2.0+
 */
class GenericServices {

	/**
	 * @var EntityNamespaceLookup
	 */
	private $entityNamespaceLookup;

	/**
	 * @var EntityTypeDefinitions
	 */
	private $entityTypeDefinitions;

	/**
	 * @var Serializer|null
	 */
	private $entitySerializer;

	/**
	 * @var Serializer|null
	 */
	private $compactEntitySerializer;

	/**
	 * @var LanguageFallbackChainFactory
	 */
	private $languageFallbackChainFactory;

	/**
	 * @var StringNormalizer
	 */
	private $stringNormalizer;

	public function __construct(
		EntityNamespaceLookup $entityNamespaceLookup,
		EntityTypeDefinitions $entityTypeDefinitions
	) {
		$this->entityNamespaceLookup = $entityNamespaceLookup;
		$this->entityTypeDefinitions = $entityTypeDefinitions;
	}

	/**
	 * @return EntityNamespaceLookup
	 */
	public function getEntityNamespaceLookup() {
		return $this->entityNamespaceLookup;
	}

	/**
	 * @return Serializer Entity serializer that generates the full (expanded) serialization.
	 */
	public function getFullEntitySerializer() {
		if ( !isset( $this->entitySerializer ) ) {
			$serializerFactoryCallbacks = $this->entityTypeDefinitions->getSerializerFactoryCallbacks();
			$baseSerializerFactory = $this->getBaseDataModelSerializerFactory();
			$serializers = [];

			foreach ( $serializerFactoryCallbacks as $callback ) {
				$serializers[] = call_user_func( $callback, $baseSerializerFactory );
			}

			$this->entitySerializer = new DispatchingSerializer( $serializers );
		}

		return $this->entitySerializer;
	}

	/**
	 * @return Serializer Entity serializer that that generates the most compact serialization
	 */
	public function getCompactEntitySerializer() {
		if ( !isset( $this->compactEntitySerializer ) ) {
			$serializerFactoryCallbacks = $this->entityTypeDefinitions->getSerializerFactoryCallbacks();
			$baseSerializerFactory = $this->getCompactBaseDataModelSerializerFactory();
			$serializers = [];

			foreach ( $serializerFactoryCallbacks as $callback ) {
				$serializers[] = call_user_func( $callback, $baseSerializerFactory );
			}

			$this->compactEntitySerializer = new DispatchingSerializer( $serializers );
		}

		return $this->compactEntitySerializer;
	}

	/**
	 * @return SerializerFactory Factory creating serializers that generate the full (expanded) serialization.
	 * The factory returned has the knowledge about items, properties, and the elements they are made of,
	 * but not about other entity types.
	 */
	public function getBaseDataModelSerializerFactory() {
		return new SerializerFactory( new DataValueSerializer(), SerializerFactory::OPTION_DEFAULT );
	}

	/**
	 * @return SerializerFactory Factory creating serializers that generate the most compact serialization.
	 * The factory returned has the knowledge about items, properties, and the elements they are made of,
	 * but not about other entity types.
	 */
	public function getCompactBaseDataModelSerializerFactory() {
		return new SerializerFactory(
			new DataValueSerializer(),
			SerializerFactory::OPTION_SERIALIZE_MAIN_SNAKS_WITHOUT_HASH +
				SerializerFactory::OPTION_SERIALIZE_REFERENCE_SNAKS_WITHOUT_HASH
		);
	}

	/**
	 * @return LanguageFallbackChainFactory
	 */
	public function getLanguageFallbackChainFactory() {
		if ( $this->languageFallbackChainFactory === null ) {
			$this->languageFallbackChainFactory = new LanguageFallbackChainFactory();
		}

		return $this->languageFallbackChainFactory;
	}

	/**
	 * @return StringNormalizer
	 */
	public function getStringNormalizer() {
		if ( $this->stringNormalizer === null ) {
			$this->stringNormalizer = new StringNormalizer();
		}

		return $this->stringNormalizer;
	}

}
