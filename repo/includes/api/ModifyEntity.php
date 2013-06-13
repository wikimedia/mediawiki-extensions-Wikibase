<?php

namespace Wikibase\Api;

use User, Title, ApiBase;

use Wikibase\EntityId;
use Wikibase\Entity;
use Wikibase\EntityContent;
use Wikibase\EntityContentFactory;
use Wikibase\ItemHandler;
use Wikibase\Summary;
use Wikibase\Utils;
use Wikibase\Settings;

/**
 * Base class for API modules modifying a single entity identified based on id xor a combination of site and page title.
 *
 * @since 0.1
 *
 * @file
 * @ingroup WikibaseRepo
 * @ingroup API
 *
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 * @author John Erling Blad < jeblad@gmail.com >
 * @author Daniel Kinzler
 */
abstract class ModifyEntity extends ApiWikibase {

	/**
	 * Flags to pass to EditEntity::attemptSave; use with the EDIT_XXX constants.
	 *
	 * @see \EditEntity::attemptSave
	 * @see \WikiPage::doEditContent
	 *
	 * @var integer $flags
	 */
	protected $flags;

	/**
	 * @see  \Api::getRequiredPermissions()
	 */
	protected function getRequiredPermissions( Entity $entity, array $params ) {
		$permissions = parent::getRequiredPermissions( $entity, $params );

		$permissions[] = 'edit';
		return $permissions;
	}

	/**
	 * Find the entity.
	 *
	 * @since 0.1
	 *
	 * @param array $params
	 *
	 * @return \Wikibase\EntityContent Found existing entity
	 */
	protected function findEntity( array $params ) {
		$entityContent = null;

		// If we have an id try that first. If the id isn't prefixed, assume it refers to an item.
		if ( isset( $params['id'] ) ) {
			if ( isset( $params['new'] ) ) {
				$this->dieUsage( "Parameter 'id' and 'new' are not allowed to be both set in the same request" );
			}
			$id = $params['id'];

			$entityContentFactory = EntityContentFactory::singleton();

			//NOTE: $id is user-supplied and may be invalid!
			$entityId = EntityId::newFromPrefixedId( $id );
			$entityTitle = $entityId ? $entityContentFactory->getTitleForId( $entityId, \Revision::FOR_THIS_USER ) : null;

			if ( is_null( $entityTitle ) ) {
				$this->dieUsage( "No entity found matching ID $id", 'no-such-entity-id' );
			}
		}
		// Otherwise check if we have a link and try that.
		// This will always result in an item, because only items have sitelinks.
		elseif ( isset( $params['site'] ) && isset( $params['title'] ) ) {
			$itemHandler = new ItemHandler();

			$entityTitle = $itemHandler->getTitleFromSiteLink(
				$params['site'],
				Utils::trimToNFC( $params['title'] )
			);

			if ( is_null( $entityTitle ) ) {
				$this->dieUsage( $this->msg( "No entity found matching site link " .
					$params['site'] . ":" . $params['title'] )->text(),
					'no-such-entity-link' );
			}
		} else {
			return null;
		}

		$baseRevisionId = isset( $params['baserevid'] ) ? intval( $params['baserevid'] ) : null;
		$entityContent = $this->loadEntityContent( $entityTitle, $baseRevisionId );

		if ( is_null( $entityContent ) ) {
			$this->dieUsage( "Can't access item content of " .
				$entityTitle->getPrefixedDBkey() .
				", revision may have been deleted.", 'no-such-entity' );
		}

		return $entityContent;
	}

	/**
	 * Create the entity.
	 *
	 * @since    0.1
	 *
	 * @param array       $params
	 *
	 * @internal param \Wikibase\EntityContent $entityContent
	 * @return \Wikibase\EntityContent Newly created entity
	 */
	protected function createEntity( array $params ) {
		$this->dieUsage( $this->msg( 'wikibase-api-no-such-entity' )->text(), 'no-such-entity' );
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
		return $summary;
	}

	/**
	 * Actually modify the entity.
	 *
	 * @since 0.1
	 *
	 * @param EntityContent $entity
	 * @param array       $params
	 *
	 * @internal param \Wikibase\EntityContent $entityContent
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
			$this->dieUsage( $this->msg( 'wikibase-api-id-xor-wikititle' )->text(), 'id-xor-wikititle' );
		}
	}

	/**
	 * @see \ApiBase::execute()
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
		$entityContent = $this->findEntity( $params );
		if ( is_null( $entityContent ) ) {
			$entityContent = $this->createEntity( $params );
		}

		// At this point only change/edit rights should be checked
		$status = $this->checkPermissions( $entityContent, $user, $params );

		if ( !$status->isOK() ) {
			wfProfileOut( __METHOD__ );
			$this->dieUsage( $status->getWikiText( 'wikibase-api-cant-edit', 'wikibase-api-cant-edit' ), 'cant-edit' );
		}

		$summary = $this->modifyEntity( $entityContent, $params );

		if ( !$summary ) {
			wfProfileOut( __METHOD__ );
			$this->dieUsage( $this->msg( 'wikibase-api-modify-failed' )->text(), 'modify-failed' );
		}

		if ( $summary === true ) { // B/C, for implementations of modifyEntity that return true on success.
			$summary = new Summary( $this->getModuleName() );
		}

		// if the entity is not up for creation, set the EDIT_UPDATE flags
		if ( !$entityContent->isNew() && ( $this->flags & EDIT_NEW ) === 0 ) {
			$this->flags |= EDIT_UPDATE;
		}

		$this->flags |= ( $user->isAllowed( 'bot' ) && $params['bot'] ) ? EDIT_FORCE_BOT : 0;

		//NOTE: EDIT_NEW will not be set automatically. If the entity doesn't exist, and EDIT_NEW was
		//      not added to $this->flags explicitly, the save will fail.

		// collect information and create an EditEntity
		$status = $this->attemptSaveEntity( $entityContent,
			$summary->toString(),
			$this->flags );

		$this->getResult()->addValue(
			'entity',
			'id', $entityContent->getEntity()->getPrefixedId()
		);

		$this->getResult()->addValue(
			'entity',
			'type', $entityContent->getEntity()->getType()
		);

		$this->addRevisionIdFromStatusToResult( 'entity', 'lastrevid', $status );

		if ( isset( $params['site'] ) && isset( $params['title'] ) ) {
			$normalized = array();

			$normTitle = Utils::trimToNFC( $params['title'] );
			if ( $normTitle !== $params['title'] ) {
				$normalized['from'] = $params['title'];
				$normalized['to'] = $normTitle;
			}

			if ( $normalized !== array() ) {
				$this->getResult()->addValue(
					'entity',
					'normalized', $normalized
				);
			}
		}

		$this->getResult()->addValue(
			null,
			'success',
			1
		);

		wfProfileOut( __METHOD__ );
	}

	/**
	 * @see ApiBase::getPossibleErrors()
	 */
	public function getPossibleErrors() {
		return array_merge( parent::getPossibleErrors(), array(
			array( 'code' => 'id-xor-wikititle', 'info' => $this->msg( 'wikibase-api-id-xor-wikititle' )->text() ),
			array( 'code' => 'add-with-id', 'info' => $this->msg( 'wikibase-api-add-with-id' )->text() ),
			array( 'code' => 'add-exists', 'info' => $this->msg( 'wikibase-api-add-exists' )->text() ),
			array( 'code' => 'update-without-id', 'info' => $this->msg( 'wikibase-api-update-without-id' )->text() ),
			array( 'code' => 'no-such-entity-link', 'info' => 'No item found with the given sitelink' ),
			array( 'code' => 'no-such-entity-id', 'info' => 'No item found with the given ID' ),
			array( 'code' => 'create-failed', 'info' => $this->msg( 'wikibase-api-create-failed' )->text() ),
			array( 'code' => 'modify-failed', 'info' => $this->msg( 'wikibase-api-modify-failed' )->text() ),
			array( 'code' => 'save-failed', 'info' => $this->msg( 'wikibase-api-save-failed' )->text() ),
			array( 'code' => 'invalid-contentmodel', 'info' => $this->msg( 'wikibase-api-invalid-contentmodel' )->text() ),
			array( 'code' => 'no-permissions', 'info' => $this->msg( 'wikibase-api-no-permissions' )->text() ),
			array( 'code' => 'session-failure', 'info' => $this->msg( 'wikibase-api-session-failure' )->text() ),
			array( 'code' => 'patch-empty', 'info' => $this->msg( 'wikibase-api-patch-empty' )->text() ),
			array( 'code' => 'not-recognized', 'info' => $this->msg( 'wikibase-api-not-recognized' )->text() ),
		) );
	}

	/**
	 * @see \ApiBase::isWriteMode()
	 */
	public function isWriteMode() {
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
		return array(
			'site' => array(
				ApiBase::PARAM_TYPE => $this->getSiteLinkTargetSites()->getGlobalIdentifiers(),
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
				"Will be prepended by an automatically generated comment."
			),
			'type' => array( 'A specific type of entity.',
				"Will default to 'item' as this will be the most common type."
			),
			'token' => array( 'A "edittoken" token previously obtained through the token module (prop=info).',
				//'Later it can be implemented a mechanism where a token can be returned spontaneously',
				//'and the requester should then start using the new token from the next request, possibly when',
				//'repeating a failed request.'
			),
			'bot' => array( 'Mark this edit as bot',
				'This URL flag will only be respected if the user belongs to the group "bot".'
			),
		);
	}

}
