<?php

namespace Wikibase\Api;

use ApiMain;
use MWException;
use Revision;
use SiteSQLStore;
use Status;
use ApiBase;
use Title;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Entity\EntityIdParsingException;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\EntityContent;
use Wikibase\ItemHandler;
use Wikibase\Repo\WikibaseRepo;
use Wikibase\StringNormalizer;
use Wikibase\Summary;

/**
 * Base class for API modules modifying a single entity identified based on id xor a combination of site and page title.
 *
 * @since 0.1
 *
 * @licence GNU GPL v2+
 * @author John Erling Blad < jeblad@gmail.com >
 * @author Daniel Kinzler
 * @author Michał Łazowik
 */
abstract class ModifyEntity extends ApiWikibase {

	/**
	 * @var StringNormalizer
	 */
	protected $stringNormalizer;

	/**
	 * @var SiteLinkTargetProvider
	 */
	protected $siteLinkTargetProvider;

	/**
	 * @since 0.5
	 *
	 * @var array
	 */
	protected $siteLinkGroups;

	public function __construct( ApiMain $main, $name, $prefix = '' ) {
		parent::__construct( $main, $name, $prefix );

		$this->stringNormalizer = WikibaseRepo::getDefaultInstance()->getStringNormalizer();
		$this->siteLinkTargetProvider = new SiteLinkTargetProvider( SiteSQLStore::newInstance() );
		$this->siteLinkGroups = WikibaseRepo::getDefaultInstance()->
			getSettings()->getSetting( 'siteLinkGroups' );
	}

	/**
	 * Flags to pass to EditEntity::attemptSave; use with the EDIT_XXX constants.
	 *
	 * @see EditEntity::attemptSave
	 * @see WikiPage::doEditContent
	 *
	 * @var integer $flags
	 */
	protected $flags;

	/**
	 * @see ApiWikibase::getRequiredPermissions()
	 */
	protected function getRequiredPermissions( EntityContent $entityContent, array $params ) {
		$permissions = parent::getRequiredPermissions( $entityContent, $params );
		$permissions[] = 'edit';
		return $permissions;
	}

	/**
	 * Get the entity using the id, site and title params passed to the api
	 *
	 * @since 0.1
	 *
	 * @param array $params
	 *
	 * @return EntityContent|null Found existing entity
	 */
	protected function getEntityContentFromApiParams( array $params ) {
		if ( isset( $params['id'] ) ) {
			$id = $params['id'];
			$entityId = $this->getEntityIdFromString( $id );
			$entityTitle = $this->getTitleFromEntityId( $entityId );
		}
		elseif ( isset( $params['site'] ) && isset( $params['title'] ) ) {
			$entityTitle = $this->getTitleFromSiteTitleCombination( $params['site'],  $params['title'] );
		} else {
			//Things that use this method assume null means we want a new entity
			return null;
		}

		/** @var Title $entityTitle */
		$baseRevisionId = isset( $params['baserevid'] ) ? intval( $params['baserevid'] ) : null;
		$entityContent = $this->loadEntityContent( $entityTitle, $baseRevisionId );

		if ( is_null( $entityContent ) ) {
			$this->dieUsage( "Can't access item content of " . $entityTitle->getPrefixedDBkey() . ", revision may have been deleted.", 'no-such-entity' );
		}

		return $entityContent;
	}

	/**
	 * @param string $id
	 * @return EntityId
	 */
	private function getEntityIdFromString( $id ) {
		$entityIdParser = WikibaseRepo::getDefaultInstance()->getEntityIdParser();
		try{
			return $entityIdParser->parse( $id );
		} catch( EntityIdParsingException $e ){
			$this->dieUsage( "Could not parse {$id}, No entity found", 'no-such-entity-id' );
		}
	}

	/**
	 * @param EntityId $entityId
	 * @return Title
	 */
	private function getTitleFromEntityId( EntityId $entityId ) {
		$entityContentFactory = WikibaseRepo::getDefaultInstance()->getEntityContentFactory();
		try{
			//This could return either null or a MWException (be ready for them both!)
			$title = $entityContentFactory->getTitleForId( $entityId, Revision::FOR_THIS_USER );
			if( $title === null ){
				throw new MWException( "No entity found matching ID " . $entityId->getSerialization() );
			}
			return $title;
		} catch( MWException $e ){
			$this->dieUsage( "No entity found matching ID " . $entityId->getSerialization(), 'no-such-entity-id' );
		}
	}

	/**
	 * @param string $site
	 * @param string $title
	 * @return Title
	 */
	private function getTitleFromSiteTitleCombination( $site, $title ) {
		$itemHandler = new ItemHandler();
		$entityTitle = $itemHandler->getTitleFromSiteLink(
			$site,
			$this->stringNormalizer->trimToNFC( $title )
		);
		if ( is_null( $entityTitle ) ) {
			$this->dieUsage( 'No entity found matching site link ' . $site . ':' . $title , 'no-such-entity-link' );
		}
		return $entityTitle;
	}

	/**
	 * Validates badges from params and turns them into an array of ItemIds.
	 *
	 * @param array $badgesParams
	 *
	 * @return ItemId[]
	 */
	protected function parseSiteLinkBadges( array $badgesParams ) {
		$repo = WikibaseRepo::getDefaultInstance();

		$entityContentFactory = $repo->getEntityContentFactory();
		$entityIdParser = $repo->getEntityIdParser();

		$badges = array();

		foreach ( $badgesParams as $badgeSerialization ) {
			try {
				$badgeId = $entityIdParser->parse( $badgeSerialization );
			} catch( EntityIdParsingException $e ) {
				$this->dieUsage( "Badges: could not parse '{$badgeSerialization}', the id is invalid", 'no-such-entity-id' );
			}

			if ( !( $badgeId instanceof ItemId ) ) {
				$this->dieUsage( "Badges: entity with id '{$badgeSerialization}' is not an item", 'not-item' );
			}

			$badgeItems = WikibaseRepo::getDefaultInstance()->
				getSettings()->getSetting( 'badgeItems' );

			if ( !in_array( $badgeId->getPrefixedId(), array_keys( $badgeItems ) ) ) {
				$this->dieUsage( "Badges: item '{$badgeSerialization}' is not a badge", 'not-badge' );
			}

			$itemTitle = $entityContentFactory->getTitleForId( $badgeId, Revision::FOR_THIS_USER );

			if ( is_null( $itemTitle ) || !$itemTitle->exists() ) {
				$this->dieUsage( "Badges: no item found matching id '{$badgeSerialization}'", 'no-such-entity' );
			}

			$badges[] = $badgeId;
		}

		return $badges;
	}

	/**
	 * Create the entity.
	 *
	 * @since 0.1
	 *
	 * @param array $params
	 * @return EntityContent Newly created entity
	 */
	protected function createEntity( array $params ) {
		$this->dieUsage( 'Could not find an existing entity' , 'no-such-entity' );
	}

	/**
	 * Create a new Summary instance suitable for representing the action performed by this module.
	 *
	 * @param array $params
	 *
	 * @return Summary
	 */
	protected function createSummary( array $params ) {
		$summary = new Summary( $this->getModuleName() );
		if ( !is_null( $params['summary'] ) ) {
			$summary->setUserSummary( $params['summary'] );
		}
		return $summary;
	}

	/**
	 * Actually modify the entity.
	 *
	 * @since 0.1
	 *
	 * @param EntityContent $entity
	 * @param array $params
	 *
	 * @return Summary|null a summary of the modification, or null to indicate failure.
	 */
	protected abstract function modifyEntity( EntityContent &$entity, array $params );

	/**
	 * Make sure the required parameters are provided and that they are valid.
	 *
	 * @since 0.1
	 *
	 * @param array $params
	 */
	protected function validateParameters( array $params ) {
		// note that this is changed back and could fail
		if ( !( isset( $params['id'] ) XOR ( isset( $params['site'] ) && isset( $params['title'] ) ) ) ) {
			$this->dieUsage( 'Either provide the item "id" or pairs of "site" and "title" for a corresponding page' , 'param-illegal' );
		}
	}

	/**
	 * @see ApiBase::execute()
	 *
	 * @since 0.1
	 */
	public function execute() {
		wfProfileIn( __METHOD__ );

		$params = $this->extractRequestParams();
		$user = $this->getUser();
		$this->flags = 0;

		$this->validateParameters( $params );

		// Try to find the entity or fail and create it, or die in the process
		$entityContent = $this->getEntityContentFromApiParams( $params );
		if ( is_null( $entityContent ) ) {
			$entityContent = $this->createEntity( $params );
		}

		// At this point only change/edit rights should be checked
		$status = $this->checkPermissions( $entityContent, $user, $params );

		if ( !$status->isOK() ) {
			wfProfileOut( __METHOD__ );
			$this->dieUsage( 'You do not have sufficient permissions' , 'permissiondenied' );
		}

		$summary = $this->modifyEntity( $entityContent, $params );

		if ( !$summary ) {
			//XXX: This could rather be used for "silent" failure, i.e. in cases where
			//     there was simply nothing to do.
			wfProfileOut( __METHOD__ );
			$this->dieUsage( 'Attempted modification of the item failed' , 'failed-modify' );
		}

		if ( $summary === true ) { // B/C, for implementations of modifyEntity that return true on success.
			$summary = new Summary( $this->getModuleName() );
		}

		$this->addFlags( $entityContent->isNew() );

		//NOTE: EDIT_NEW will not be set automatically. If the entity doesn't exist, and EDIT_NEW was
		//      not added to $this->flags explicitly, the save will fail.
		$status = $this->attemptSaveEntity(
			$entityContent,
			$summary,
			$this->flags
		);

		$this->addToOutput( $entityContent, $status );

		wfProfileOut( __METHOD__ );
	}

	protected function addFlags( $entityContentIsNew ) {
		// if the entity is not up for creation, set the EDIT_UPDATE flags
		if ( !$entityContentIsNew && ( $this->flags & EDIT_NEW ) === 0 ) {
			$this->flags |= EDIT_UPDATE;
		}

		$params = $this->extractRequestParams();
		$this->flags |= ( $this->getUser()->isAllowed( 'bot' ) && $params['bot'] ) ? EDIT_FORCE_BOT : 0;
	}

	protected function addToOutput( EntityContent $entityContent, Status $status ) {
		$this->getResultBuilder()->addBasicEntityInformation( $entityContent->getEntity()->getId(), 'entity' );
		$this->getResultBuilder()->addRevisionIdFromStatusToResult( $status, 'entity' );

		$params = $this->extractRequestParams();

		if ( isset( $params['site'] ) && isset( $params['title'] ) ) {
			$normTitle = $this->stringNormalizer->trimToNFC( $params['title'] );
			if ( $normTitle !== $params['title'] ) {
				$this->getResultBuilder()->addNormalizedTitle( $params['title'], $normTitle, 'normalized' );
			}
		}

		$this->getResultBuilder()->markSuccess( 1 );
	}

	/**
	 * @see ApiBase::getPossibleErrors()
	 */
	public function getPossibleErrors() {
		return array_merge( parent::getPossibleErrors(), array(
			array( 'code' => 'no-such-entity-id', 'info' => $this->msg( 'wikibase-api-no-such-entity-id' )->text() ),
			array( 'code' => 'no-such-entity-link', 'info' => $this->msg( 'wikibase-api-no-such-entity-link' )->text() ),
			array( 'code' => 'no-such-entity', 'info' => $this->msg( 'wikibase-api-no-such-entity' )->text() ),
			array( 'code' => 'param-illegal', 'info' => $this->msg( 'wikibase-api-param-illegal' )->text() ),
			array( 'code' => 'permissiondenied', 'info' => $this->msg( 'wikibase-api-permissiondenied' )->text() ),
			array( 'code' => 'failed-modify', 'info' => $this->msg( 'wikibase-api-failed-modify' )->text() ),
		) );
	}

	/**
	 * @see ApiBase::isWriteMode()
	 */
	public function isWriteMode() {
		return true;
	}

	/**
	 * @see ApiBase::mustBePosted
	 */
	public function mustBePosted() {
		return true;
	}

	/**
	 * Get allowed params for the identification of the entity
	 * Lookup through an id is common for all entities
	 *
	 * @since 0.1
	 *
	 * @return array the allowed params
	 */
	public function getAllowedParamsForId() {
		return array(
			'id' => array(
				ApiBase::PARAM_TYPE => 'string',
			),
		);
	}

	/**
	 * Get allowed params for the identification by a sitelink pair
	 * Lookup through the sitelink object is not used in every subclasses
	 *
	 * @since 0.1
	 *
	 * @return array the allowed params
	 */
	public function getAllowedParamsForSiteLink() {
		$sites = $this->siteLinkTargetProvider->getSiteList( $this->siteLinkGroups );
		return array(
			'site' => array(
				ApiBase::PARAM_TYPE => $sites->getGlobalIdentifiers(),
			),
			'title' => array(
				ApiBase::PARAM_TYPE => 'string',
			),
		);
	}

	/**
	 * Get allowed params for the entity in general
	 *
	 * @since 0.1
	 *
	 * @return array the allowed params
	 */
	public function getAllowedParamsForEntity() {
		return array(
			'baserevid' => array(
				ApiBase::PARAM_TYPE => 'integer',
			),
			'summary' => array(
				ApiBase::PARAM_TYPE => 'string',
			),
			'token' => null,
			'bot' => false,
		);
	}

	/**
	 * Get param descriptions for identification of the entity
	 * Lookup through an id is common for all entities
	 *
	 * @since 0.1
	 *
	 * @return array the param descriptions
	 */
	protected function getParamDescriptionForId() {
		return array(
			'id' => array( 'The identifier for the entity, including the prefix.',
				"Use either 'id' or 'site' and 'title' together."
			),
		);
	}

	/**
	 * Get param descriptions for identification by a sitelink pair
	 * Lookup through the sitelink object is not used in every subclasses
	 *
	 * @since 0.1
	 *
	 * @return array the param descriptions
	 */
	protected function getParamDescriptionForSiteLink() {
		return array(
			'site' => array( 'An identifier for the site on which the page resides.',
				"Use together with 'title' to make a complete sitelink."
			),
			'title' => array( 'Title of the page to associate.',
				"Use together with 'site' to make a complete sitelink."
			),
		);
	}

	/**
	 * Get param descriptions for the entity in general
	 *
	 * @since 0.1
	 *
	 * @return array the param descriptions
	 */
	protected function getParamDescriptionForEntity() {
		return array(
			'baserevid' => array( 'The numeric identifier for the revision to base the modification on.',
				"This is used for detecting conflicts during save."
			),
			'summary' => array( 'Summary for the edit.',
				"Will be prepended by an automatically generated comment. The length limit of the
				autocomment together with the summary is 260 characters. Be aware that everything above that
				limit will be cut off."
			),
			'type' => array( 'A specific type of entity.',
				"Will default to 'item' as this will be the most common type."
			),
			'token' => array( 'A "edittoken" token previously obtained through the token module (prop=info).',
			),
			'bot' => array( 'Mark this edit as bot',
				'This URL flag will only be respected if the user belongs to the group "bot".'
			),
		);
	}

}
