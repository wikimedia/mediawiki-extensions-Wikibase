<?php

namespace Wikibase\DataAccess;

use DataValues\Serializers\DataValueSerializer;
use Serializers\DispatchingSerializer;
use Serializers\Serializer;
use Wikibase\DataModel\SerializerFactory;
use Wikibase\Lib\EntityTypeDefinitions;

/**
 * A container/factory of services which don't rely/require repository-specific configuration.
 *
 * @license GPL-2.0-or-later
 */
class GenericServices {

	/**
	 * @var EntityTypeDefinitions
	 */
	private $entityTypeDefinitions;

	/**
	 * @var Serializer|null
	 */
	private $compactEntitySerializer;

	/**
	 * @param EntityTypeDefinitions $entityTypeDefinitions
	 */
	public function __construct(
		EntityTypeDefinitions $entityTypeDefinitions
	) {
		$this->entityTypeDefinitions = $entityTypeDefinitions;
	}

	/**
	 * @return Serializer Entity serializer that that generates the most compact serialization
	 */
	public function getCompactEntitySerializer() {
		if ( !isset( $this->compactEntitySerializer ) ) {
			$serializerFactoryCallbacks = $this->entityTypeDefinitions->get( EntityTypeDefinitions::SERIALIZER_FACTORY_CALLBACK );
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
	 * @return SerializerFactory Factory creating serializers that generate the most compact serialization.
	 * The factory returned has the knowledge about items, properties, and the elements they are made of,
	 * but not about other entity types.
	 */
	public function getCompactBaseDataModelSerializerFactory() {
		return new SerializerFactory(
			new DataValueSerializer(),
			// FIXME: Hard coded constant values, to not fail phan
			// SerializerFactory::OPTION_SERIALIZE_MAIN_SNAKS_WITHOUT_HASH +
			// SerializerFactory::OPTION_SERIALIZE_REFERENCE_SNAKS_WITHOUT_HASH
			2 + 8
		);
	}

}
