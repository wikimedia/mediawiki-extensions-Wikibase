<?php

namespace Wikibase\DataAccess;

use DataValues\Serializers\DataValueSerializer;
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
	 * @param EntityTypeDefinitions $entityTypeDefinitions
	 */
	public function __construct(
		EntityTypeDefinitions $entityTypeDefinitions
	) {
		$this->entityTypeDefinitions = $entityTypeDefinitions;
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
