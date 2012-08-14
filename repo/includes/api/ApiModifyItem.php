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
	 * @since 0.1
	 *
	 * @param ItemContent $item
	 * @param array       $params
	 *
	 * @internal param \Wikibase\ItemContent $itemContent
	 * @return bool Success indicator
	 */
	protected abstract function modifyItem( ItemContent &$item, array $params );

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
	 * is no ordinary summary. If this call fails an autosummary from the item itself will
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
			$this->dieUsage( wfMsg( 'wikibase-api-id-xor-wikititle' ), 'id-xor-wikititle' );
		}
	}

	/**
	 * @see ApiBase::execute()
	 */
	public function execute() {
		global $wgContLang;

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

			if ( is_null( $itemContent ) ) {
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
					// Give an error message
					$this->dieUsage( $status->getWikiText( 'wikibase-api-not-implemented' ), 'not-implemented' );
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
			// This is similar to ApiEditPage.php and what it uses at line 314
			$this->flags = ($user->isAllowed( 'bot' ) && $params['bot'] ) ? EDIT_FORCE_BOT : 0;

			// Lets define this just in case
			$hits =0;
			$summary = '';
			$lang = $wgContLang;

			// Is there a user supplied summary, then use it but get the hits first
			if ( isset( $params['summary'] ) ) {
				list( $hits, $summary, $lang ) = $this->getTextForSummary( $params );
				$summary = $params['summary'];
			}
			// otherwise try to construct something
			else {
				list( $hits, $summary, $lang ) = $this->getTextForSummary( $params );
				if ( !is_string( $summary ) ) {
					$summary = $itemContent->getTextForSummary( $params );
				}
			}

			// Comments are newer user supplied
			$comment = $this->getTextForComment( $params, $hits );

			// Do the actual save, or if it don't exist yet create it.
			$status = $itemContent->save( Autocomment::formatTotalSummary( $comment, $summary, $lang ), $user, $this->flags  );
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
				$page = $itemContent->getWikiPage();
				if ( $page->exists() ) {
					$revision = $page->getRevision();
					if ( $revision !== null ) {
						$this->getResult()->addValue(
							'item',
							'lastrevid', intval( $revision->getId() )
						);
					}
				}
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
	 * @see ApiBase::getPossibleErrors()
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
		return array_merge( parent::getAllowedParams(), array(
			'id' => array(
				ApiBase::PARAM_TYPE => 'integer',
			),
			'site' => array(
				ApiBase::PARAM_TYPE => Sites::singleton()->getGroup( SITE_GROUP_WIKIPEDIA )->getGlobalIdentifiers(),
			),
			'title' => array(
				ApiBase::PARAM_TYPE => 'string',
			),
			'summary' => array(
				ApiBase::PARAM_TYPE => 'string',
			),
			'token' => null,
			'bot' => false,
		) );
	}

	/**
	 * @see ApiBase::getParamDescription()
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
			'summary' => array( 'Summary for the edit.',
				"Will be prepended by an automatically generated comment."
			),
			'token' => array( 'A "wbitemtoken" token previously obtained through the gettoken parameter.', // or prop=info,
				'During a normal reply a token can be returned spontaneously and the requester should',
				'then start using the new token from the next request, possibly when repeating a failed',
				'request.'
			),
			// This is similar to ApiEditPage.php and what it uses at line 527
			'bot' => array( 'Mark this edit as bot',
				'This URL flag will only be respected if the user belongs to the group "bot".'
			),
		) );
	}

}
