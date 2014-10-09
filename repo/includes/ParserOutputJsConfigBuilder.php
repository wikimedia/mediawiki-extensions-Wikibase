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
	 * @var EntityInfoBuilderFactory
	 */
	protected $entityInfoBuilderFactory;

	/**
	 * @var EntityIdParser
	 */
	protected $entityIdParser;

	/**
	 * @var EntityTitleLookup
	 */
	protected $entityTitleLookup;

	/**
	 * @var ReferencedEntitiesFinder
	 */
	protected $refFinder;

	/**
	 * @var string
	 */
	protected $langCode;

	/**
	 * @var SerializerFactory
	 */
	protected $serializerFactory;

	/**
	 * @param EntityInfoBuilderFactory $entityInfoBuilderFactory
	 * @param EntityIdParser $entityIdParser
	 * @param EntityTitleLookup $entityTitleLookup
	 * @param ReferencedEntitiesFinder $refFinder
	 * @param string $langCode
	 */
	public function __construct( EntityInfoBuilderFactory $entityInfoBuilderFactory,
		EntityIdParser $entityIdParser, EntityTitleLookup $entityTitleLookup,
		ReferencedEntitiesFinder $refFinder, $langCode
	) {
		$this->entityInfoBuilderFactory = $entityInfoBuilderFactory;
		$this->entityIdParser = $entityIdParser;
		$this->entityTitleLookup = $entityTitleLookup;
		$this->refFinder = $refFinder;
		$this->langCode = $langCode;

		$this->serializerFactory = new SerializerFactory();
	}

	/**
	 * @param Entity $entity
	 * @param SerializationOptions $options
	 *
	 * @return array
	 */
	public function build( Entity $entity, SerializationOptions $options ) {
		$configVars = $this->getEntityVars( $entity, $options );

		return $configVars;
	}

	/**
	 * @param Entity $entity
	 * @param SerializationOptions $options
	 *
	 * @return array
	 */
	protected function getEntityVars( Entity $entity, SerializationOptions $options ) {
		$entityId = $entity->getId();

		if ( !$entityId ) {
			$entityId = ''; //XXX: should probably throw an exception
		} else {
			$entityId = $entityId->getSerialization();
		}

		$configVars = array(
			'wbEntityId' => $entityId,
			'wbUsedEntities' => FormatJson::encode( $this->getBasicEntityInfo( $entity ) ),
			'wbEntity' => FormatJson::encode( $this->getSerializedEntity( $entity, $options ) )
		);

		return $configVars;
	}

	/**
	 * Fetches some basic entity information required for the entity view in JavaScript from a
	 * set of entity IDs.
	 * @since 0.4
	 *
	 * @param Entity $entity
	 * @return string
	 */
	protected function getBasicEntityInfo( Entity $entity ) {
		wfProfileIn( __METHOD__ );

		$entityIds = $this->refFinder->findSnakLinks( $entity->getAllSnaks() );

		// TODO: apply language fallback!
		$entityInfoBuilder = $this->entityInfoBuilderFactory->newEntityInfoBuilder( $entityIds );

		$entityInfoBuilder->resolveRedirects();
		$entityInfoBuilder->removeMissing();
		$entityInfoBuilder->collectTerms( array( 'label', 'description' ), array( $this->langCode ) );
		$entityInfoBuilder->collectDataTypes();
		$entityInfoBuilder->retainEntityInfo( $entityIds );

		$entityInfo = $entityInfoBuilder->getEntityInfo();
		$revisionInfo = $this->attachRevisionInfo( $entityInfo );

		wfProfileOut( __METHOD__ );
		return $revisionInfo;
	}

	/**
	 * Wraps each record in $entities with revision info.
	 *
	 * @todo: perhaps move this into EntityInfoBuilder; Note however that it is useful to be
	 * able to pick which information is actually needed in which context. E.g. we are skipping the
	 * actual revision ID here, and thereby avoiding any database access.
	 *
	 * @param array $entities A list of entity records
	 *
	 * @return array A list of revision records
	 */
	private function attachRevisionInfo( array $entities ) {
		$idParser = $this->entityIdParser;
		$titleLookup = $this->entityTitleLookup;

		return array_map( function( $entity ) use ( $idParser, $titleLookup ) {
				$id = $idParser->parse( $entity['id'] );

				// If the title lookup needs DB access, we really need a better way to do this!
				$title = $titleLookup->getTitleForId( $id );

				return array(
					'content' => $entity,
					'title' => $title->getPrefixedText(),
					//'revision' => 0,
				);
			},
			$entities
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
