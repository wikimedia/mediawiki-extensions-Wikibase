<?php

namespace Wikibase;
use User, Title, ApiBase;

/**
 * Base class for API modules modifying a single item identified based on id xor a combination of site and page title.
 *
 * @since 0.1
 *
 * @file ApiWikibaseModifyItem.php
 * @ingroup Wikibase
 * @ingroup API
 *
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
abstract class ApiModifyItem extends Api {

	/**
	 * When saving a number of flags should be set
	 * @var integer $flags how flags should be set
	 */
	protected $flags;

	/**
	 * @see  Api::getRequiredPermissions()
	 */
	protected function getRequiredPermissions( Item $item, array $params ) {
		$permissions = parent::getRequiredPermissions( $item, $params );

		$permissions[] = 'edit';
		return $permissions;
	}

	/**
	 * Create the item if its missing.
	 *
	 * @since    0.1
	 *
	 * @param array       $params
	 *
	 * @internal param \Wikibase\ItemContent $itemContent
	 * @return ItemContent Newly created item
	 */
	protected abstract function createItem( array $params );

	/**
	 * Actually modify the item.
	 *
	 * @since    0.1
	 *
	 * @param ItemContent $item
	 * @param array       $params
	 *
	 * @internal param \Wikibase\ItemContent $itemContent
	 * @return bool Success indicator
	 */
	protected abstract function modifyItem( ItemContent &$item, array $params );

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
			$this->dieUsage( wfMsg( 'wikibase-api-id-xor-wikititle' ), 'id-xor-wikititle' );
		}
	}

	/**
	 * Main method. Does the actual work and sets the result.
	 *
	 * @since 0.1
	 */
	public function execute() {
		$params = $this->extractRequestParams();
		$user = $this->getUser();
		$this->flags = 0;

		if ( $params['gettoken'] ) {
			$this->addTokenToResult( $user->getEditToken() );
			$this->getResult()->addValue( null, 'success', (int)true );
			return;
		}

		// This is really already done with needsToken()
		if ( $this->needsToken() && !$user->matchEditToken( $params['token'] ) ) {
			$this->dieUsage( wfMsg( 'wikibase-api-session-failure' ), 'session-failure' );
		}

		// this is really peeking into a subclass, which is not good
		$hasData = isset( $params['data'] ) ? strlen( preg_replace( '/(^\s*|\s*$)/s', '', $params['data'] ) ) : false;

		$normTitle = '';
		$hasLink = false;
		if ( isset( $params['site'] ) && $params['title'] ) {
			$normTitle = Utils::squashToNFC( $params['title'] );
			$hasLink = true;
		}
		$itemContent = null;

		$this->validateParameters( $params );

		if ( isset( $params['id'] ) ) {
			$itemContent = ItemHandler::singleton()->getFromId( $params['id'] );

			if ( is_null( $itemContent ) ) {
				$this->dieUsage( wfMsg( 'wikibase-api-no-such-item-id' ), 'no-such-item-id' );
			}
		}
		elseif ( $hasLink ) {
			$itemContent = ItemHandler::singleton()->getFromSiteLink( $params['site'], $normTitle );

			if ( is_null( $itemContent ) && $params['item'] === 'update' ) {
				$this->dieUsage( wfMsg( 'wikibase-api-no-such-item-link' ), 'no-such-item-link' );
			}
		}

		if ( !is_null( $itemContent ) && !( $itemContent instanceof ItemContent ) ) {
			$this->dieUsage( wfMsg( 'wikibase-api-wrong-class' ), 'wrong-class' );
		}

		if ( is_null( $itemContent ) ) {
			$itemContent = $this->createItem( $params );
		}

		if ( is_null( $itemContent ) ) {
			$this->dieUsage( wfMsg( 'wikibase-api-no-such-item-id' ), 'no-such-item-id' );
		}

		// At this point only change/edit rights should be checked
		$status = $this->checkPermissions( $itemContent, $user, $params );

		if ( !$status->isOK() ) {
			$this->dieUsage( $status->getWikiText( 'wikibase-api-cant-edit', 'wikibase-api-cant-edit' ), 'cant-edit' );
		}

		// FIXME: we can (?) do this before we do permission checks as long as we don't save
		$this->setUsekeys( $params );
		$success = $this->modifyItem( $itemContent, $params );

		if ( !$success ) {
			$this->dieUsage( wfMsg( 'wikibase-api-modify-failed' ), 'modify-failed' );
		}

		if ( Settings::get( 'apiDeleteEmpty' ) && $itemContent->isEmpty() ) {
			if ( $itemContent->isNew() ) {
				// Delete the object if the user holds enough rights.
				$allowed = $itemContent->userCan( 'delete' );
				if ( $allowed ) {
					// TODO: Delete an existing object
					$this->getResult()->addValue( 'item', 'deleted', "" );
				}
				else {
					// Give an error message
					$this->dieUsage( $status->getWikiText( 'wikibase-api-delete-failed' ), 'delete-failed' );
				}
			}
			else {
				// Just give a message that it was newer created
				$this->getResult()->addValue( 'item', 'newercreated', "" );
			}
		}
		else {
			// Allow bots to exempt some edits from bot flagging
			// Also the EDIT_AUTOSUMMARY should be handled
			if ( $this->flags & EDIT_NEW) {
				$this->flags |= EDIT_UPDATE;
			}
			$this->flags = ($user->isAllowed( 'bot' ) ) ? EDIT_FORCE_BOT : 0;
			$summary = '';
			// Do the actual save, or if it don't exist yet create it.
			$status = $itemContent->save( $summary, $user, $this->flags );
			$success = $status->isOK();

			if ( !$success ) {
				if ( $itemContent->isNew() ) {
					$this->dieUsage( $status->getWikiText( 'wikibase-api-create-failed' ), 'create-failed' );
				}
				else {
					$this->dieUsage( $status->getWikiText( 'wikibase-api-save-failed' ), 'save-failed' );
				}
			}

			if ( $success ) {
				$this->getResult()->addValue(
					'item',
					'id', $itemContent->getItem()->getId()
				);

				if ( $hasLink ) {
					$normalized = array();

					if ( $normTitle !== $params['title'] ) {
						$normalized['from'] = $params['title'];
						$normalized['to'] = $normTitle;
					}

					if ( $normalized !== array() ) {
						$this->getResult()->addValue(
							'item',
							'normalized', $normalized
						);
					}
				}
			}
		}

		$this->getResult()->addValue(
			null,
			'success',
			(int)$success
		);

	}

	/**
	 * Returns a list of all possible errors returned by the module
	 * @return array in the format of array( key, param1, param2, ... ) or array( 'code' => ..., 'info' => ... )
	 */
	public function getPossibleErrors() {
		return array_merge( parent::getPossibleErrors(), array(
			array( 'code' => 'id-xor-wikititle', 'info' => wfMsg( 'wikibase-api-id-xor-wikititle' ) ),
			array( 'code' => 'add-with-id', 'info' => wfMsg( 'wikibase-api-add-with-id' ) ),
			array( 'code' => 'add-exists', 'info' => wfMsg( 'wikibase-api-add-exists' ) ),
			array( 'code' => 'update-without-id', 'info' => wfMsg( 'wikibase-api-update-without-id' ) ),
			array( 'code' => 'no-such-item-link', 'info' => wfMsg( 'wikibase-api-no-such-item-link' ) ),
			array( 'code' => 'no-such-item-id', 'info' => wfMsg( 'wikibase-api-no-such-item-id' ) ),
			array( 'code' => 'create-failed', 'info' => wfMsg( 'wikibase-api-create-failed' ) ),
			array( 'code' => 'modify-failed', 'info' => wfMsg( 'wikibase-api-modify-failed' ) ),
			array( 'code' => 'wrong-class', 'info' => wfMsg( 'wikibase-api-wrong-class' ) ),
			array( 'code' => 'save-failed', 'info' => wfMsg( 'wikibase-api-save-failed' ) ),
			array( 'code' => 'invalid-contentmodel', 'info' => wfMsg( 'wikibase-api-invalid-contentmodel' ) ),
			array( 'code' => 'no-permissions', 'info' => wfMsg( 'wikibase-api-no-permissions' ) ),
			array( 'code' => 'session-failure', 'info' => wfMsg( 'wikibase-api-session-failure' ) ),
		) );
	}

	/**
	 * Returns whether this module requires a Token to execute
	 * @return bool
	 */
	public function needsToken() {
		return Settings::get( 'apiInDebug' ) ? Settings::get( 'apiDebugWithTokens' ) : true ;
	}

	/**
	 * Indicates whether this module must be called with a POST request
	 * @return bool
	 */
	public function mustBePosted() {
		return Settings::get( 'apiInDebug' ) ? Settings::get( 'apiDebugWithPost' ) : true ;
	}

	/**
	 * Indicates whether this module requires write mode
	 * @return bool
	 */
	public function isWriteMode() {
		return Settings::get( 'apiInDebug' ) ? Settings::get( 'apiDebugWithWrite' ) : true ;
	}

	/**
	 * Returns an array of allowed parameters (parameter name) => (default
	 * value) or (parameter name) => (array with PARAM_* constants as keys)
	 * Don't call this function directly: use getFinalParams() to allow
	 * hooks to modify parameters as needed.
	 * @return array|bool
	 */
	public function getAllowedParams() {
		return array_merge( parent::getAllowedParams(), array(
			'id' => array(
				ApiBase::PARAM_TYPE => 'integer',
			),
			'site' => array(
				ApiBase::PARAM_TYPE => Sites::singleton()->getGlobalIdentifiers(),
			),
			'title' => array(
				ApiBase::PARAM_TYPE => 'string',
			),
			'token' => null,
		) );
	}

	/**
	 * Get final parameter descriptions, after hooks have had a chance to tweak it as
	 * needed.
	 *
	 * @return array|bool False on no parameter descriptions
	 */
	public function getParamDescription() {
		return array_merge( parent::getParamDescription(), array(
			'id' => array( 'The numeric identifier for the item.',
				"Use either 'id' or 'site' and 'title' together."
			),
			'site' => array( 'An identifier for the site on which the page resides.',
				"Use together with 'title' to make a complete sitelink."
			),
			'title' => array( 'Title of the page to associate.',
				"Use together with 'site' to make a complete sitelink."
			),
			'token' => array( 'A "wbitemtoken" token previously obtained through the gettoken parameter.', // or prop=info,
				'During a normal reply a token can be returned spontaneously and the requester should',
				'then start using the new token from the next request, possibly when repeating a failed',
				'request.'
			),
		) );
	}

}
