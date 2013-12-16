<?php

namespace Wikibase;

use FormatJson;
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
class EntityViewJsConfig {

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
	 * @param LanguageFallbackChain $languageFallbackChain
	 * @param EntityInfoBuilder $entityInfoBuilder
	 * @param EntityIdParser $entityIdParser
	 * @param EntityTitleLookup $entityTitleLookup
	 */
	public function __construct( LanguageFallbackChain $languageFallbackChain,
		EntityInfoBuilder $entityInfoBuilder, EntityIdParser $entityIdParser,
		EntityTitleLookup $entityTitleLookup
	) {
		$this->languageFallbackChain = $languageFallbackChain;
		$this->entityInfoBuilder = $entityInfoBuilder;
		$this->entityIdParser = $entityIdParser;
		$this->entityTitleLookup = $entityTitleLookup;
	}

	/**
	 * @param EntityRevision $entityRevision the entity for which we want to add the JS config
	 * @param Entity $entity
	 * @param Title $title
	 * @param User $user
	 * @param string $langCode the language used for showing the entity.
	 * @param bool $editableView whether entities on this page should be editable.
	 *	This is independent of user permissions.
	 */
	public function getJsConfigVars( EntityRevision $entityRevision, Entity $entity, Title $title,
		User $user, $langCode, $editableView = false
	) {
		wfProfileIn( __METHOD__ );

		$configVars = array();

		// TODO: replace wbUserIsBlocked this with more useful info (which groups would be
		// required to edit? compare wgRestrictionEdit and wgRestrictionCreate)
		$configVars['wbUserIsBlocked'] = $user->isBlockedFrom( $title ); //NOTE: deprecated

		// tell JS whether the user can edit
		// TODO: make this a per-entity info
		$configVars['wbUserCanEdit'] = $title->userCan( 'edit', $user, false );

		// NOTE: page-wide property, independent of user permissions
		$configVars['wbIsEditView'] = $editableView;

		$configVars['wbEntityType'] = $entity->getType();
		$configVars['wbDataLangName'] = Utils::fetchLanguageName( $langCode );

		// entity specific data
		$configVars['wbEntityId'] = $this->getFormattedIdForEntity( $entity );

		// copyright warning message
		$configVars['wbCopyright'] = array(
			'version' => Utils::getCopyrightMessageVersion(),
			'messageHtml' => Utils::getCopyrightMessage()->parse(),
		);

		$experimental = defined( 'WB_EXPERIMENTAL_FEATURES' ) && WB_EXPERIMENTAL_FEATURES;
		$configVars['wbExperimentalFeatures'] = $experimental;

		// make information about other entities used in this entity available in JavaScript view:
		$refFinder = new ReferencedEntitiesFinder();

		$usedEntityIds = $refFinder->findSnakLinks( $entity->getAllSnaks() );
		$basicEntityInfo = $this->getBasicEntityInfo( $usedEntityIds, $langCode );

		$configVars['wbUsedEntities'] = FormatJson::encode( $basicEntityInfo );

		wfProfileOut( __METHOD__ );
		return $configVars;
	}

	/**
	 * Fetches some basic entity information required for the entity view in JavaScript from a
	 * set of entity IDs.
	 * @since 0.4
	 *
	 * @param EntityId[] $entityIds
	 * @param string $langCode For the entity labels which will be included in one language only.
	 * @return array
	 */
	protected function getBasicEntityInfo( array $entityIds, $langCode ) {
		wfProfileIn( __METHOD__ );

		// TODO: apply language fallback! Restore fallback test case in
		// EntityViewTest::provideRegisterJsConfigVars()
		$entities = $this->entityInfoBuilder->buildEntityInfo( $entityIds );

		$this->entityInfoBuilder->removeMissing( $entities );
		$this->entityInfoBuilder->addTerms( $entities, array( 'label', 'description' ), array( $langCode ) );
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
			$entities );
	}

	protected function getFormattedIdForEntity( Entity $entity ) {
		if ( !$entity->getId() ) {
			return ''; //XXX: should probably throw an exception
		}

		return $entity->getId()->getPrefixedId();
	}

}
