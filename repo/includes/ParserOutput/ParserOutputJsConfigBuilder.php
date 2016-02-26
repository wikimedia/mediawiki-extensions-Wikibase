<?php

namespace Wikibase\Repo\ParserOutput;

use DataValues\Serializers\DataValueSerializer;
use FormatJson;
use Wikibase\DataModel\Entity\EntityDocument;
use Wikibase\DataModel\SerializerFactory;

/**
 * @since 0.5
 *
 * @license GPL-2.0+
 * @author H. Snater < mediawiki at snater.com >
 * @author Daniel Werner
 * @author Daniel Kinzler
 * @author Katie Filbert < aude.wiki@gmail.com >
 * @author Addshore
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
	 * @param EntityDocument $entity
	 *
	 * @return array
	 */
	public function build( EntityDocument $entity ) {
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
	 * @param EntityDocument $entity
	 *
	 * @return string
	 */
	private function getSerializedEntity( EntityDocument $entity ) {
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
