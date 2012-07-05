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
	 * @see  Api::getRequiredPermissions()
	 */
	protected function getRequiredPermissions( Item $item, array $params ) {
		$permissions = parent::getRequiredPermissions( $item, $params );

		$permissions[] = 'edit';
		return $permissions;
	}

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
		if ( !( isset( $params['id'] ) XOR ( isset( $params['site'] ) && isset( $params['title'] ) ) )
			&& !( isset( $params['item'] ) && $params['item'] === 'add' ) ) {

			$this->dieUsage( wfMsg( 'wikibase-api-id-xor-wikititle' ), 'id-xor-wikititle' );
		}

		if ( isset( $params['id'] ) && $params['item'] === 'add' ) {
			$this->dieUsage( wfMsg( 'wikibase-api-add-with-id' ), 'add-with-id' );
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

		// This is really already done with needsToken()
		if ( $this->needsToken() && !$user->matchEditToken( $params['token'] ) ) {
			$this->dieUsage( wfMsg( 'wikibase-api-session-failure' ), 'session-failure' );
		}

		$normTitle = '';
		$hasLink = false;
		if ( isset( $params['site'] ) && $params['title'] ) {
			$normTitle = Utils::squashToNFC( $params['title'] );
			$hasLink = true;
		}
		$itemContent = null;

		$this->validateParameters( $params );

		//if ( !isset($params['summary']) ) {
		//	$params['summary'] = 'dummy';
		//}

		if ( $params['item'] === 'update' && !isset( $params['id'] ) && !$hasLink ) {
			$this->dieUsage( wfMsg( 'wikibase-api-update-without-id' ), 'update-without-id' );
		}

		if ( isset( $params['id'] ) ) {
			$itemContent = ItemContent::getFromId( $params['id'] );

			if ( is_null( $itemContent ) ) {
				$this->dieUsage( wfMsg( 'wikibase-api-no-such-item-id' ), 'no-such-item-id' );
			}
		}
		elseif ( $hasLink ) {
			$itemContent = ItemContent::getFromSiteLink( $params['site'], $normTitle );

			if ( is_null( $itemContent ) && $params['item'] === 'update' ) {
				$this->dieUsage( wfMsg( 'wikibase-api-no-such-item-link' ), 'no-such-item-link' );
			}
		}

		if ( !is_null( $itemContent ) && !( $itemContent instanceof ItemContent ) ) {
			$this->dieUsage( wfMsg( 'wikibase-api-wrong-class' ), 'wrong-class' );
		}

		if ( !is_null( $itemContent ) && $params['item'] === 'add' ) {
			$this->dieUsage( wfMsg( 'wikibase-api-add-exists' ), 'add-exists', 0, array( 'item' => array( 'id' => $params['id'] ) ) );
		}

		if ( is_null( $itemContent ) ) {
			$itemContent = ItemContent::newEmpty();

			if ( $hasLink ) {
				$itemContent->getItem()->addSiteLink( $params['site'], $params['title'] );
			}
		}

		$status = $this->checkPermissions( $itemContent, $user, $params );

		if ( !$status->isOK() ) {
			$this->dieUsage( $status->getWikiText( 'wikibase-api-cant-edit', 'wikibase-api-cant-edit' ), 'cant-edit' );
		}

		$this->setUsekeys( $params );
		$success = $this->modifyItem( $itemContent, $params );

		if ( !$success ) {
			$this->dieUsage( wfMsg( 'wikibase-api-modify-failed' ), 'modify-failed' );
		}

		// Do the actual save, or if it don't exist yet create it.
		$status = $itemContent->save();
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
			//'create' => array(
			//	ApiBase::PARAM_TYPE => 'boolean',
			//),
			'id' => array(
				ApiBase::PARAM_TYPE => 'integer',
			),
			'site' => array(
				ApiBase::PARAM_TYPE => Sites::singleton()->getGlobalIdentifiers(),
			),
			'title' => array(
				ApiBase::PARAM_TYPE => 'string',
			),
			//'summary' => array(
			//	ApiBase::PARAM_TYPE => 'string',
			//	ApiBase::PARAM_DFLT => __CLASS__, // TODO
			//),
			'item' => array(
				ApiBase::PARAM_TYPE => array( 'add', 'update', 'set' ),
				ApiBase::PARAM_DFLT => 'update',
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
			'id' => array( 'The ID of the item.',
				"Use either 'id' or 'site' and 'title' together."
			),
			'site' => array( 'An identifier for the site on which the page resides.',
				"Use together with 'title'."
			),
			'title' => array( 'Title of the page to associate.',
				"Use together with 'site'."
			),
			'item' => array( 'Indicates if you are changing the content of the item.',
				"add - the item should not exist before the call or an error will be reported.",
				"update - the item shuld exist before the call or an error will be reported.",
				"set - the item could exist or not before the call.",
			),
			// 'summary' => 'Summary for the edit.',
			'token' => 'A "setitem" token previously obtained through the gettoken parameter', // or prop=info,
		) );
	}

}
