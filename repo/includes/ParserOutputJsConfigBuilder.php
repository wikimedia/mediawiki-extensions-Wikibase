<?php

namespace Wikibase;

use FormatJson;
use Wikibase\DataModel\Entity\Entity;
use Wikibase\DataModel\Entity\EntityIdParser;
use Wikibase\Lib\Serializers\EntitySerializer;
use Wikibase\Lib\Serializers\SerializationOptions;
use Wikibase\Lib\Serializers\SerializerFactory;
use Wikibase\Lib\Store\EntityInfoBuilderFactory;
use Wikibase\Lib\Store\EntityTitleLookup;

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
	 * @var EntityIdParser
	 */
	protected $entityIdParser;

	/**
	 * @var EntityTitleLookup
	 */
	protected $entityTitleLookup;

	/**
	 * @var string
	 */
	protected $langCode;

	/**
	 * @var SerializerFactory
	 */
	protected $serializerFactory;

	/**
	 * @param EntityIdParser $entityIdParser
	 * @param EntityTitleLookup $entityTitleLookup
	 * @param string $langCode
	 */
	public function __construct(
		EntityIdParser $entityIdParser,
		EntityTitleLookup $entityTitleLookup,
		$langCode
	) {
		$this->entityIdParser = $entityIdParser;
		$this->entityTitleLookup = $entityTitleLookup;
		$this->langCode = $langCode;

		$this->serializerFactory = new SerializerFactory();
	}

	/**
	 * @param Entity $entity
	 * @param array $entityInfo
	 * @param SerializationOptions $options
	 *
	 * @return array
	 */
	public function build( Entity $entity, array $entityInfo, SerializationOptions $options ) {
		$entityId = $entity->getId();

		if ( !$entityId ) {
			$entityId = ''; //XXX: should probably throw an exception
		} else {
			$entityId = $entityId->getSerialization();
		}

		$revisionInfo = $this->attachRevisionInfo( $entityInfo );

		$configVars = array(
			'wbEntityId' => $entityId,
			'wbUsedEntities' => FormatJson::encode( $revisionInfo ),
			'wbEntity' => FormatJson::encode( $this->getSerializedEntity( $entity, $options ) )
		);

		return $configVars;
	}

	/**
	 * Wraps each record in $entities with revision info.
	 *
	 * @todo: perhaps move this into EntityInfoBuilder; Note however that it is useful to be
	 * able to pick which information is actually needed in which context. E.g. we are skipping the
	 * actual revision ID here, and thereby avoiding any database access.
	 *
	 * @param array $entities A list of entity records from EntityInfoBuilder::getEntityInfo
	 *
	 * @return array A list of revision records
	 */
	private function attachRevisionInfo( array $entityInfoRecords ) {
		$idParser = $this->entityIdParser;
		$titleLookup = $this->entityTitleLookup;

		return array_map( function( $entityInfoRecord ) use ( $idParser, $titleLookup ) {
				$entityId = $idParser->parse( $entityInfoRecord['id'] );

				// If the title lookup needs DB access, we really need a better way to do this!
				$title = $titleLookup->getTitleForId( $entityId );

				return array(
					'content' => $entityInfoRecord,
					'title' => $title->getPrefixedText(),
					//'revision' => 0,
				);
			},
			$entityInfoRecords
		);
	}

	/**
	 * @param Entity $entity
	 * @param SerializationOptions $options
	 *
	 * @return string
	 */
	protected function getSerializedEntity( Entity $entity, SerializationOptions $options ) {
		$serializer = $this->getEntitySerializer( $entity->getType(), $options );
		return $serializer->getSerialized( $entity );
	}

	/**
	 * @param string $entityType
	 * @param SerializationOptions $options
	 *
	 * @return EntitySerializer
	 */
	protected function getEntitySerializer( $entityType, SerializationOptions $options ) {
		return $this->serializerFactory->newSerializerForEntity( $entityType, $options );
	}

}
