<?php

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
abstract class ApiWikibaseModifyItem extends ApiBase {

	/**
	 * Actually modify the item.
	 *
	 * @since 0.1
	 *
	 * @param WikibaseItem $item
	 * @param array $params
	 *
	 * @return boolean Success indicator
	 */
	protected abstract function modifyItem( WikibaseItem &$item, array $params );
	
	/**
	 * Check the rights for the user accessing the module, that is a subclass of this one.
	 * 
	 * @param $title Title object where the item is stored
	 * @param $user User doing the action
	 * @param $params array of arguments for the module, passed for ModifyItem
	 * @param $mod null|String name of the module, usually not set
	 * @param $op null|String operation that is about to be done, usually not set
	 * @return array of errors reported from the static getPermissionsError
	 */
	protected abstract function getPermissionsErrorInternal( $title, $user, array $params, $module=null, $op=null );

	/**
	 * Check the rights for the user accessing the module, module name and operation comes from the actual subclass.
	 * 
	 * @param $title Title object where the item is stored
	 * @param $user User doing the action
	 * @param $mod null|String name of the module, usually not set
	 * @param $op null|String operation that is about to be done, usually not set
	 * @return array of errors reported from the static getPermissionsError
	 */
	protected static function getPermissionsError( $title, $user, $mod=null, $op=null ) {
		if ( WBSettings::get( 'apiInDebug' ) ? !WBSettings::get( 'apiDebugWithRights', false ) : false ) {
			return null;
		}
		
		// Check permissions
		return $title->getUserPermissionsErrors(
			is_string($mod) ? "{$mod}-{$op}" : $op,
			$user
		);
	}
	
	/**
	 * Make sure the required parameters are provided and that they are valid.
	 *
	 * @since 0.1
	 *
	 * @param array $params
	 */
	protected function validateParameters( array $params ) {
		if ( !( isset( $params['id'] ) XOR ( isset( $params['site'] ) && isset( $params['title'] ) ) ) ) {
			$this->dieUsage( wfMsg( 'wikibase-api-id-xor-wikititle' ), 'id-xor-wikititle' );
		}
/*
		if ( isset( $params['id'] ) && $params['item'] === 'add' ) {
			$this->dieUsage( wfMsg( 'wikibase-api-add-with-id' ), 'add-with-id' );
		}
*/
	}

	/**
	 * Main method. Does the actual work and sets the result.
	 *
	 * @since 0.1
	 */
	public function execute() {
		$params = $this->extractRequestParams();
		$user = $this->getUser();

		$success = false;

		// This is really already done with needsToken()
		if ( $this->needsToken() && !$user->matchEditToken( $params['token'] ) ) {
			$this->dieUsageMsg( 'sessionfailure' );
		}
		
		if ( !$user->isAllowed( 'edit' ) ) {
			$this->dieUsageMsg( 'cantedit' );
		}

		$success = false;

		$this->validateParameters( $params );
		
		if ( !isset($params['summary']) ) {
			$params['summary'] = 'dummy';
		}
		
		if ( !isset( $params['id'] ) ) { // create is for development
			$params['id'] = WikibaseItem::getIdForSiteLink( $params['site'], $params['title'] );

			if ( $params['id'] === false && $params['item'] === 'update' ) {
				$this->dieUsage( wfMsg( 'wikibase-api-no-such-item-link' ), 'no-such-item-link' );
			}
		}
/*
		if ( $params['id'] !== false && $params['item'] === 'add' ) {
			$this->dieUsage( wfMsg( 'wikibase-api-add-exists' ), 'add-exists', 0, array( 'item' => array( 'id' => $params['id'] ) ) );
		}
*/
		if ( isset( $params['id'] ) && $params['id'] !== false ) {
			$page = WikibaseItem::getWikiPageForId( $params['id'] );

			if ( $page->exists() ) {
				$item = $page->getContent();
			}
			else {
				$this->dieUsage( wfMsg( 'wikibase-api-no-such-item-id' ), 'no-such-item-id' );
			}
		}
		else {
			// now we should never be here
			// TODO: find good way to do this. Seems like we need a WikiPage::setContent
			$item = WikibaseItem::newEmpty();
			$success = $item->save();

			if ( $success ) {
				if ( isset( $params['site'] ) && isset( $params['title'] ) ) {
					$item->addSiteLink( $params['site'], $params['title'] );
				}
			}
			else {
				$this->dieUsage( wfMsg( 'wikibase-api-create-failed' ), 'create-failed-1' );
			}
		}

		if ( $item->getModelName() === CONTENT_MODEL_WIKIBASE ) {
			$success = $this->modifyItem( $item, $params );

			if ( $success ) {
				$page = $item->getWikiPage();
				
				$errors = $this->getPermissionsErrorInternal( $page->getTitle(), $this->getUser(), $params );
				if ( count( $errors ) ) {
					// this could be redesigned into something more usefull
					//print_r($errors);
					$this->dieUsage( wfMsg( 'wikibase-api-no-permissions' ), 'no-permissions' );
				}
				
				$status = $page->doEditContent(
					$item,
					$params['summary'],
					EDIT_AUTOSUMMARY,
					false,
					$this->getUser(),
					'application/json' // TODO: this should not be needed here? (w/o it stuff is stored as wikitext...)
				);

				$success = $status->isOk();
			}
		}
		else {
			$this->dieUsage( wfMsg( 'wikibase-api-invalid-contentmodel' ), 'invalid-contentmodel' );
		}

		// this saves unconditionally if we had a success so far
		// it could be interesting to avoid storing if the item is in fact not changed
		// or if the saves could be queued somehow
		if ($success) {
			$success = $item->save();
		}
		
		$this->getResult()->addValue(
			null,
			'success',
			(int)$success
		);

		if ( $success ) {
			$this->getResult()->addValue(
				null,
				'item',
				array(
					'id' => $item->getId()
				)
			);
		}
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
			array( 'code' => 'no-such-item-link', 'info' => wfMsg( 'wikibase-api-no-such-item-link' ) ),
			array( 'code' => 'no-such-item-id', 'info' => wfMsg( 'wikibase-api-no-such-item-id' ) ),
			array( 'code' => 'create-failed', 'info' => wfMsg( 'wikibase-api-create-failed' ) ),
			array( 'code' => 'invalid-contentmodel', 'info' => wfMsg( 'wikibase-api-invalid-contentmodel' ) ),
			array( 'code' => 'no-permissions', 'info' => wfMsg( 'wikibase-api-no-permissions' ) ),
		) );
	}

	/**
	 * Returns whether this module requires a Token to execute
	 * @return bool
	 */
	public function needsToken() {
		return WBSettings::get( 'apiInDebug' ) ? WBSettings::get( 'apiDebugWithTokens' ) : true ;
	}

	/**
	 * Indicates whether this module must be called with a POST request
	 * @return bool
	 */
	public function mustBePosted() {
		return WBSettings::get( 'apiInDebug' ) ? WBSettings::get( 'apiDebugWithPost' ) : true ;
	}

	/**
	 * Indicates whether this module requires write mode
	 * @return bool
	 */
	public function isWriteMode() {
		return WBSettings::get( 'apiInDebug' ) ? WBSettings::get( 'apiDebugWithWrite' ) : true ;
	}
	
	/**
	 * Returns an array of allowed parameters (parameter name) => (default
	 * value) or (parameter name) => (array with PARAM_* constants as keys)
	 * Don't call this function directly: use getFinalParams() to allow
	 * hooks to modify parameters as needed.
	 * @return array|bool
	 */
	public function getAllowedParams() {
		return array(
			'create' => array(
				ApiBase::PARAM_TYPE => 'boolean',
			),
			'id' => array(
				ApiBase::PARAM_TYPE => 'integer',
			),
			'site' => array(
				ApiBase::PARAM_TYPE => WikibaseUtils::getSiteIdentifiers(),
			),
			'title' => array(
				ApiBase::PARAM_TYPE => 'string',
			),
			'summary' => array(
				ApiBase::PARAM_TYPE => 'string',
				ApiBase::PARAM_DFLT => __CLASS__, // TODO
			),
			'item' => array(
				ApiBase::PARAM_TYPE => array( 'add', 'update', 'set' ),
				ApiBase::PARAM_DFLT => 'update',
			),
			'token' => null,
		);
	}

	/**
	 * Get final parameter descriptions, after hooks have had a chance to tweak it as
	 * needed.
	 *
	 * @return array|bool False on no parameter descriptions
	 */
	public function getParamDescription() {
		return array(
			'id' => array( 'The ID of the item.',
				"Use either 'id' or 'site' and 'title' together."
			),
			'site' => array( 'An identifier for the site on which the page resides.',
				"Use together with 'title'."
			),
			'title' => array( 'Title of the page to associate.',
				"Use together with 'site'."
			),
			'item' => 'Indicates if you are changing the content of the item',
			'summary' => 'Summary for the edit.',
			'token' => 'A "setitem" token previously obtained through the gettoken parameter', // or prop=info,
		);
	}

}
