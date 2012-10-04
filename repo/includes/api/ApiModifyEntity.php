<?php

namespace Wikibase;
use User, Title, ApiBase;

/**
 * Base class for API modules modifying a single entity identified based on id xor a combination of site and page title.
 *
 * @since 0.1
 *
 * @file
 * @ingroup Wikibase
 * @ingroup API
 *
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 * @author John Erling Blad < jeblad@gmail.com >
 */
abstract class ApiModifyEntity extends Api {

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
	 * @see  Api::getRequiredPermissions()
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
	 * @return EntityContent Found existing entity
	 */
	protected function findEntity( array $params ) {
		$entityContent = null;

		// If we have an id try that first
		if ( isset( $params['id'] ) ) {
			$entityContent = EntityContentFactory::singleton()->getFromId( $params['type'], $params['id'] );

			if ( is_null( $entityContent ) ) {
				$this->dieUsage( $this->msg( 'wikibase-api-no-such-entity-id' )->text(), 'no-such-entity-id' );
			}
		}
		// Otherwise check if we have a link and try that
		// note that this will not be run if the subclass doesn't allow the sitelink parameters
		// or if the validateParameters method rejects it
		elseif ( $params['type'] === 'item' && isset( $params['site'] ) && isset( $params['title'] ) ) {
			$entityContent = ItemHandler::singleton()->getFromSiteLink(
				$params['site'],
				Utils::squashToNFC( $params['title'] )
			);

			if ( is_null( $entityContent ) ) {
				$this->dieUsage( $this->msg( 'wikibase-api-no-such-entity-link' )->text(), 'no-such-entity-link' );
			}
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
	 * @return EntityContent Newly created entity
	 */
	protected function createEntity( array $params ) {
		$this->dieUsage( $this->msg( 'wikibase-api-no-such-entity' )->text(), 'no-such-entity' );
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
	 * @return bool Success indicator
	 */
	protected abstract function modifyEntity( EntityContent &$entity, array $params );

	/**
	 * Make a string for an autocomment, that can be replaced through system messages.
	 *
	 * The autocomment is the initial part of the total summary. It is used to
	 * explain the overall purpose with the change. If its later replaced by a
	 * system message then it should not use any user supplied text as arg.
	 *
	 * @since 0.1
	 *
	 * @param $params array with parameters from the call to the module
	 * @param $plural integer|string the number used for plural forms
	 * @return string that can be used as an autocomment
	 */
	protected abstract function getTextForComment( array $params, $plural = 'none' );

	/**
	 * Make a string for an autosummary, that can be replaced through system messages.
	 *
	 * The autosummary is the final part of the total summary. This call is used if there
	 * is no ordinary summary. If this call fails an autosummary from the entity itself will
	 * be used.
	 *
	 * The returned array has a count that can be used for plural forms in the messages,
	 * but exact interpretation is somewhat undefined.
	 *
	 * FIXME: How do we handle direction.
	 *
	 * @since 0.1
	 *
	 * @param $params array with parameters from the call to the module
	 * @return array where the array( int, false|string ) is a count and a string that can be used as an autosummary
	 */
	protected abstract function getTextForSummary( array $params );

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
	 * @see ApiBase::execute()
	 */
	public function execute() {
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
			$this->dieUsage( $status->getWikiText( 'wikibase-api-cant-edit', 'wikibase-api-cant-edit' ), 'cant-edit' );
		}

		$success = $this->modifyEntity( $entityContent, $params );

		if ( !$success ) {
			$this->dieUsage( $this->msg( 'wikibase-api-modify-failed' )->text(), 'modify-failed' );
		}

		// This is similar to ApiEditPage.php and what it uses at line 314
		$this->flags |= ( $user->isAllowed( 'bot' ) && $params['bot'] ) ? EDIT_FORCE_BOT : 0;

		// if the entity is not up for creation, set the EDIT_UPDATE flags
		if ( !$entityContent->isNew() && ( $this->flags & EDIT_NEW ) === 0 ) {
			$this->flags |= EDIT_UPDATE;
		}

		//NOTE: EDIT_NEW will not be set automatically. If the entity doesn't exist, and EDIT_NEW was
		//      not added to $this->flags explicitly, the save will fail.

		// Is there a user supplied summary, then use it but get the hits first
		if ( isset( $params['summary'] ) ) {
			list( $hits, $summary, $lang ) = $this->getTextForSummary( $params );
			$summary = $params['summary'];
		}
		// otherwise try to construct something
		else {
			list( $hits, $summary, $lang ) = $this->getTextForSummary( $params );
			if ( !is_string( $summary ) ) {
				$summary = $entityContent->getTextForSummary( $params );
			}
		}

		// Comments are newer user supplied
		$comment = $this->getTextForComment( $params, $hits );

		// collect information and create an EditEntity
		$baseRevisionId = isset( $params['baserevid'] ) ? intval( $params['baserevid'] ) : null;
		$baseRevisionId = $baseRevisionId > 0 ? $baseRevisionId : null;
		$editEntity = new EditEntity( $entityContent, $user, $baseRevisionId );

		// Do the actual save, or if it don't exist yet create it.
		// There will be exceptions but we just leak them out ;)
		$status = $editEntity->attemptSave(
			Autocomment::formatTotalSummary( $comment, $summary, $lang ),
			$this->flags,
			( $this->needsToken() ? $params['token'] : false )
		);

		if ( $editEntity->hasError( EditEntity::TOKEN_ERROR ) ) {
			$editEntity->reportApiErrors( $this, 'session-failure' );
		}
		elseif ( $editEntity->hasError( EditEntity::EDIT_CONFLICT_ERROR ) ) {
			$editEntity->reportApiErrors( $this, 'edit-conflict' );
		}
		elseif ( $editEntity->hasError() ) {
			$editEntity->reportApiErrors( $this, 'save-failed' );
		}

		$this->getResult()->addValue(
			'entity',
			'id', $entityContent->getEntity()->getId()
		);

		$this->getResult()->addValue(
			'entity',
			'type', $entityContent->getEntity()->getType()
		);

		$page = $entityContent->getWikiPage();
		$revision = $page->getRevision();

		if ( $revision !== null ) {
			$this->getResult()->addValue(
				'entity',
				'lastrevid', intval( $revision->getId() )
			);
		}

		if ( isset( $params['site'] ) && isset( $params['title'] ) ) {
			$normalized = array();

			$normTitle = Utils::squashToNFC( $params['title'] );
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
			(int)$success
		);
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
			array( 'code' => 'no-such-entity-link', 'info' => $this->msg( 'wikibase-api-no-such-entity-link' )->text() ),
			array( 'code' => 'no-such-entity-id', 'info' => $this->msg( 'wikibase-api-no-such-entity-id' )->text() ),
			array( 'code' => 'create-failed', 'info' => $this->msg( 'wikibase-api-create-failed' )->text() ),
			array( 'code' => 'modify-failed', 'info' => $this->msg( 'wikibase-api-modify-failed' )->text() ),
			array( 'code' => 'save-failed', 'info' => $this->msg( 'wikibase-api-save-failed' )->text() ),
			array( 'code' => 'invalid-contentmodel', 'info' => $this->msg( 'wikibase-api-invalid-contentmodel' )->text() ),
			array( 'code' => 'no-permissions', 'info' => $this->msg( 'wikibase-api-no-permissions' )->text() ),
			array( 'code' => 'session-failure', 'info' => $this->msg( 'wikibase-api-session-failure' )->text() ),
			array( 'code' => 'patch-empty', 'info' => $this->msg( 'wikibase-api-patch-empty' )->text() ),
		) );
	}

	/**
	 * @see ApiBase::needsToken()
	 */
	public function needsToken() {
		return Settings::get( 'apiInDebug' ) ? Settings::get( 'apiDebugWithTokens' ) : true ;
	}

	/**
	 * @see ApiBase::mustBePosted()
	 */
	public function mustBePosted() {
		return Settings::get( 'apiInDebug' ) ? Settings::get( 'apiDebugWithPost' ) : true ;
	}

	/**
	 * @see ApiBase::isWriteMode()
	 */
	public function isWriteMode() {
		return Settings::get( 'apiInDebug' ) ? Settings::get( 'apiDebugWithWrite' ) : true ;
	}

	/**
	 * @see ApiBase::getAllowedParams()
	 */
	public function getAllowedParams() {
		return parent::getAllowedParams();
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
				ApiBase::PARAM_TYPE => 'integer',
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
			'type' => array(
				ApiBase::PARAM_TYPE => array( 'item', 'property', 'query' ),
				ApiBase::PARAM_DFLT => 'item',
				ApiBase::PARAM_ISMULTI => false,
			),
			'token' => null,
			'bot' => false,
		);
	}

	/**
	 * @see ApiBase::getParamDescription()
	 */
	public function getParamDescription() {
		return parent::getParamDescription();
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
			'id' => array( 'The numeric identifier for the entity.',
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
			'baserevid' => array( 'The numeric identifier for the revision.',
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
