<?php

namespace Wikibase;

use DataValues\Serializers\DataValueSerializer;
use FormatJson;
use Wikibase\DataModel\Entity\Entity;
use Wikibase\DataModel\SerializerFactory;

/**
 * @since 0.5
 *
 * @licence GNU GPL v2+
 *
 * @author H. Snater < mediawiki at snater.com >
 * @author Daniel Werner
 * @author Daniel Kinzler
 * @author Katie Filbert < aude.wiki@gmail.com >
 * @author Adam Shorland
 */
class ParserOutputJsConfigBuilder {

	/**
	 * @var SerializerFactory
	 */
	private $serializerFactory;

	public function __construct() {
		$this->serializerFactory = new SerializerFactory(
			new DataValueSerializer(),
			SerializerFactory::OPTION_SERIALIZE_MAIN_SNAKS_WITHOUT_HASH +
			SerializerFactory::OPTION_SERIALIZE_REFERENCE_SNAKS_WITHOUT_HASH
		);
	}

	/**
	 * @param Entity $entity
	 *
	 * @return array
	 */
	public function build( Entity $entity ) {
		$entityId = $entity->getId();

		if ( !$entityId ) {
			$entityId = ''; //XXX: should probably throw an exception
		} else {
			$entityId = $entityId->getSerialization();
		}

		$configVars = array(
			'wbEntityId' => $entityId,
			'wbEntity' => FormatJson::encode( $this->getSerializedEntity( $entity ) )
		);

		return $configVars;
	}

	/**
	 * @param Entity $entity
	 *
	 * @return string
	 */
	private function getSerializedEntity( Entity $entity ) {
		$serializer = $this->serializerFactory->newEntitySerializer();

		$serialization = $serializer->serialize( $entity );

		// Remove empty parts of the serialization (Added when Lib Serializers were removed)
		// We could allow parts if we are sure it would not break anything
		foreach ( $serialization as $key => $serializationPart ) {
			if ( is_array( $serializationPart ) && empty( $serializationPart ) ) {
				unset( $serialization[$key] );
			}
		}

		return $serialization;
	}

}
