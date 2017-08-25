<?php

namespace Wikibase\DataAccess;

use DataValues\Serializers\DataValueSerializer;
use Serializers\DispatchingSerializer;
use Serializers\Serializer;
use Wikibase\DataModel\SerializerFactory;
use Wikibase\LanguageFallbackChainFactory;
use Wikibase\Lib\EntityTypeDefinitions;
use Wikibase\Lib\Store\EntityNamespaceLookup;

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
	 * @var Serializer[]
	 */
	private $entitySerializers;

	/**
	 * @var LanguageFallbackChainFactory
	 */
	private $languageFallbackChainFactory;

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
	 * @param int $options bitwise combination of the SerializerFactory::OPTION_ flags
	 *
	 * @return Serializer
	 */
	public function getEntitySerializer( $options = SerializerFactory::OPTION_DEFAULT ) {
		if ( !isset( $this->entitySerializers[$options] ) ) {
			$serializerFactoryCallbacks = $this->entityTypeDefinitions->getSerializerFactoryCallbacks();
			$baseSerializerFactory = $this->getSerializerFactory( $options );
			$serializers = [];

			foreach ( $serializerFactoryCallbacks as $callback ) {
				$serializers[] = call_user_func( $callback, $baseSerializerFactory );
			}

			$this->entitySerializers[$options] = new DispatchingSerializer( $serializers );
		}

		return $this->entitySerializers[$options];
	}

	/**
	 * @param int $options bitwise combination of the SerializerFactory::OPTION_ flags
	 * @return SerializerFactory
	 */
	private function getSerializerFactory( $options ) {
		return new SerializerFactory( new DataValueSerializer(), $options );
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

}
