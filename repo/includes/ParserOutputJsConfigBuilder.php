<?php

namespace Wikibase;

use FormatJson;
use Wikibase\DataModel\Entity\Entity;
use Wikibase\Lib\Serializers\SerializationOptions;
use Wikibase\Lib\Serializers\SerializerFactory;

/**
 * @since 0.5
 *
 * @licence GNU GPL v2+
 *
 * @author H. Snater < mediawiki at snater.com >
 * @author Daniel Werner
 * @author Daniel Kinzler
 * @author Katie Filbert < aude.wiki@gmail.com >
 */
class ParserOutputJsConfigBuilder {

	/**
	 * @var SerializationOptions
	 */
	private $serializationOptions;

	/**
	 * @var SerializerFactory
	 */
	private $serializerFactory;

	/**
	 * @param SerializationOptions $serializationOptions
	 */
	public function __construct(
		SerializationOptions $serializationOptions
	) {
		$this->serializationOptions = $serializationOptions;
		$this->serializerFactory = new SerializerFactory();
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
		$serializer = $this->serializerFactory->newSerializerForEntity(
			$entity->getType(),
			$this->serializationOptions
		);

		return $serializer->getSerialized( $entity );
	}

}
