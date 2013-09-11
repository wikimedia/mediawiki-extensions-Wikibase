<?php

namespace Wikibase;

use FormatJson;
use Language;
use Message;
use RuntimeException;
use Title;
use User;
use Wikibase\DataModel\Entity\EntityIdParser;
use Wikibase\Lib\Serializers\SerializerFactory;
use Wikibase\Lib\Serializers\SerializationOptions;

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
class EntityViewConfigBuilder {

	/**
	 * @var LanguageFallbackChain
	 */
	protected $languageFallbackChain;

	/**
	 * @var EntityInfoBuilder
	 */
	protected $entityInfoBuilder;

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
	 * @var array
	 */
	protected $configVars = array();

	/**
	 * @param LanguageFallbackChain $languageFallbackChain
	 * @param EntityInfoBuilder $entityInfoBuilder
	 * @param EntityIdParser $entityIdParser
	 * @param EntityTitleLookup $entityTitleLookup
	 * @param string $langCode
	 */
	public function __construct( LanguageFallbackChain $languageFallbackChain,
		EntityInfoBuilder $entityInfoBuilder, EntityIdParser $entityIdParser,
		EntityTitleLookup $entityTitleLookup, $langCode
	) {
		$this->languageFallbackChain = $languageFallbackChain;
		$this->entityInfoBuilder = $entityInfoBuilder;
		$this->entityIdParser = $entityIdParser;
		$this->entityTitleLookup = $entityTitleLookup;
		$this->langCode = $langCode;
	}

	/**
	 * @param Entity $entity
	 *
	 * @return array
	 */
	public function buildJsConfigVars( Entity $entity, $copyrightMessage, $isExperimental ) {
		$this->setCopyrightMessage( $copyrightMessage );

		// @todo inject!
        $this->configVars['wbDataLangName'] = Utils::fetchLanguageName( $this->langCode );

		$this->configVars['wbExperimentalFeatures'] = $isExperimental;

		$this->setEntityVars( $entity );

		return $this->configVars;
	}

	/**
	 * @param Message $copyrightMessage
	 */
	private function setCopyrightMessage( $copyrightMessage ) {
		// non-translated message
		$versionMessage = new Message( 'wikibase-shortcopyrightwarning-version' );
		$lang = Language::factory( $this->langCode );

		$this->configVars['wbCopyright'] = array(
			'version' => $versionMessage->parse(),
			'messageHtml' => $copyrightMessage->inLanguage( $lang )->parse()
		);
	}

	/**
	 * @param Entity $entity
	 *
	 * @throws RuntimeException
	 */
	protected function setEntityVars( Entity $entity ) {
		$entityId = $entity->getId();

		if ( !$entityId ) {
			throw new RuntimeException( '$entity has no entity id' );
		}

        $this->configVars['wbEntityType'] = $entity->getType();
		$this->configVars['wbEntityId'] = $entityId->getSerialization();
		$this->configVars['wbUsedEntities'] = FormatJson::encode( $this->getBasicEntityInfo( $entity ) );
		$this->configVars['wbEntity'] = FormatJson::encode( $this->getSerializedEntity( $entity ) );
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

		// make information about other entities used in this entity available in JavaScript view:
		$refFinder = new ReferencedEntitiesFinder();

		$entityIds = $refFinder->findSnakLinks( $entity->getAllSnaks() );

		// TODO: apply language fallback! Restore fallback test case in
		// EntityViewTest::provideRegisterJsConfigVars()
		$entities = $this->entityInfoBuilder->buildEntityInfo( $entityIds );

		$this->entityInfoBuilder->removeMissing( $entities );
		$this->entityInfoBuilder->addTerms( $entities, array( 'label', 'description' ), array( $this->langCode ) );
		$this->entityInfoBuilder->addDataTypes( $entities );

		$revisions = $this->attachRevisionInfo( $entities );

		wfProfileOut( __METHOD__ );
		return $revisions;
	}

	/**
	 * Wraps each record in $entities with revision info, similar to how EntityRevisionSerializer
	 * does this.
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
	 *
	 * @return string
	 */
	protected function getSerializedEntity( Entity $entity ) {
		$serializer = $this->getEntitySerializer( $entity->getType() );
		return $serializer->getSerialized( $entity );
	}

	/**
	 * @return SerializationOptions
	 */
	protected function getSerializationOptions() {
		// @fixme inject language codes
		$langCodes = Utils::getLanguageCodes() + array( $this->langCode => $this->languageFallbackChain );

		$options = new SerializationOptions();
		$options->setLanguages( $langCodes );

		return $options;
	}

	/**
	 * @param string $entityType
	 *
	 * @return EntitySerializer
	 */
	protected function getEntityUnserializer( $entityType ) {
		$serializerFactory = new SerializerFactory();
		$options = $this->getSerializationOptions();

		return $serializerFactory->newUnserializerForEntity( $entityType, $options );
	}

	/**
	 * @param string $entityType
	 *
	 * @return EntitySerializer
	 */
	protected function getEntitySerializer( $entityType ) {
		$serializerFactory = new SerializerFactory();
		$options = $this->getSerializationOptions();

		return $serializerFactory->newSerializerForEntity( $entityType, $options );
	}

}
