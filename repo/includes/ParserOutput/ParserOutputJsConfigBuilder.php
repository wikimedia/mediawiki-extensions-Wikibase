<?php

namespace Wikibase\Repo\ParserOutput;

use FormatJson;
use Serializers\Serializer;
use Wikibase\DataModel\Entity\EntityDocument;

/**
 * @license GPL-2.0+
 */
class ParserOutputJsConfigBuilder {

	/**
	 * @var Serializer
	 */
	private $entitySerializer;

	public function __construct( Serializer $entitySerializer ) {
		$this->entitySerializer = $entitySerializer;
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
		$serialization = $this->entitySerializer->serialize( $entity );

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
