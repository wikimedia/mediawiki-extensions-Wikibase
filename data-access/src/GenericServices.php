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
	 * @var int[]
	 */
	private $entityNamespaces;

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

	/**
	 * @param EntityTypeDefinitions $entityTypeDefinitions
	 * @param int[] $entityNamespaces
	 */
	public function __construct(
		EntityTypeDefinitions $entityTypeDefinitions,
		array $entityNamespaces
	) {
		$this->entityTypeDefinitions = $entityTypeDefinitions;
		$this->entityNamespaces = $entityNamespaces;
	}

	/**
	 * @return EntityNamespaceLookup
	 */
	public function getEntityNamespaceLookup() {
		if ( $this->entityNamespaceLookup === null ) {
			$this->entityNamespaceLookup = new EntityNamespaceLookup( $this->entityNamespaces );
		}

		return $this->entityNamespaceLookup;
	}

	/**
	 * @return Serializer Entity serializer that generates the full (expanded) serialization.
	 */
	public function getEntitySerializer() {
		if ( !isset( $this->entitySerializer ) ) {
			$serializerFactoryCallbacks = $this->entityTypeDefinitions->getSerializerFactoryCallbacks();
			$baseSerializerFactory = $this->getSerializerFactory();
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
			$baseSerializerFactory = $this->getCompactSerializerFactory();
			$serializers = [];

			foreach ( $serializerFactoryCallbacks as $callback ) {
				$serializers[] = call_user_func( $callback, $baseSerializerFactory );
			}

			$this->compactEntitySerializer = new DispatchingSerializer( $serializers );
		}

		return $this->compactEntitySerializer;
	}

	/**
	 * @return SerializerFactory Factory creating serializers that include snak hashes in the serialization.
	 */
	public function getSerializerFactory() {
		return new SerializerFactory( new DataValueSerializer(), SerializerFactory::OPTION_DEFAULT );
	}

	/**
	 * @return SerializerFactory Factory creating serializers that omits snak hashes in the serialization.
	 */
	public function getCompactSerializerFactory() {
		return new SerializerFactory( new DataValueSerializer(), SerializerFactory::OPTION_SERIALIZE_SNAKS_WITHOUT_HASH );
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
