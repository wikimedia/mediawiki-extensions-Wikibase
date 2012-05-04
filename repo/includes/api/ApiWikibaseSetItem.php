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
 * @author John Erling Blad < jeblad@gmail.com >
 */
class ApiWikibaseSetItem extends ApiBase {

	public function __construct( $main, $action ) {
		parent::__construct( $main, $action );
	}

	/**
	 * Check the rights
	 * 
	 * @param $title Title
	 * @param $user User doing the action
	 * @param $action String
	 * @param $params String|Array|Null
	 * @return array
	 */
	protected static function getPermissionsError( $title, $user, $mod='item', $op='add' ) {
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
	 * Main method. Does the actual work and sets the result.
	 *
	 * @since 0.1
	 */
	public function execute() {
		$params = $this->extractRequestParams();
		$user = $this->getUser();

		if ( $params['gettoken'] ) {
			$res['setitemtoken'] = $user->getEditToken();
			$this->getResult()->addValue( null, $this->getModuleName(), $res );
			return;
		}
		
		// This is really already done with needTokens()
		if ( $this->needsToken() && !$user->matchEditToken( $params['token'] ) ) {
			$this->dieUsage( wfMsg( 'wikibase-api-no-token' ), 'no-token' );
		}
		
		if ( !$params['data'] ) {
			$this->dieUsage( wfMsg( 'wikibase-api-no-data' ), 'no-data' );
		}

		if ( !$user->isAllowed( 'edit' ) ) {
			$this->dieUsage( wfMsg( 'wikibase-api-cant-edit' ), 'cant-edit' );
		}
		
		$success = false;
		
		if ( !isset($params['summary']) ) {
			$params['summary'] = 'dummy';
		}
		
		// lacks error checking
		$ch = new WikibaseContentHandler();
		$item = $ch->unserializeContent( $params['data'],'application/json' );
		//$item->cleanStructure();
		$success = $item->save();
		
		if ( $success ) {
			$page = $item->getWikiPage();
				
			$errors = self::getPermissionsError( $page->getTitle(), $this->getUser() );
			if ( count( $errors ) ) {
				// this could be redesigned into something more usefull
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
		
		$languages = WikibaseUtils::getLanguageCodes();
		
		// because this is serialized and cleansed we can simply go for known values
		$this->getResult()->addValue(
			NULL,
			'item',
			array(
				'id' => $item->getId(),
				'sitelinks' => $item->getSiteLinks(),
				'descriptions' => $item->getDescriptions($languages),
				'labels' => $item->getLabels($languages)
			)
		);
		$this->getResult()->addValue(
			null,
			'success',
			(int)$success
		);
	}
	
	public function getPossibleErrors() {
		return array_merge( parent::getPossibleErrors(), array(
			array( 'code' => 'no-token', 'info' => wfMsg( 'wikibase-api-no-token' ) ),
			array( 'code' => 'no-data', 'info' => wfMsg( 'wikibase-api-no-data' ) ),
			array( 'code' => 'cant-edit', 'info' => wfMsg( 'wikibase-api-cant-edit' ) ),
			array( 'code' => 'no-permissions', 'info' => wfMsg( 'wikibase-api-no-permissions' ) ),
		) );
	}

	public function needsToken() {
		return WBSettings::get( 'apiInDebug' ) ? WBSettings::get( 'apiDebugWithTokens', false ) : true ;
	}

	public function mustBePosted() {
		return WBSettings::get( 'apiInDebug' ) ? WBSettings::get( 'apiDebugWithPost', false ) : true ;
	}

	public function isWriteMode() {
		return WBSettings::get( 'apiInDebug' ) ? WBSettings::get( 'apiDebugWithWrite', false ) : true ;
	}
	
	public function getAllowedParams() {
		return array(
			'data' => array(
				ApiBase::PARAM_TYPE => 'string',
				//ApiBase::PARAM_REQUIRED => true,
			),
			'summary' => array(
				ApiBase::PARAM_TYPE => 'string',
				ApiBase::PARAM_DFLT => __CLASS__, // TODO
			),
			'item' => array(
				ApiBase::PARAM_TYPE => array( 'add' ),
				ApiBase::PARAM_DFLT => 'add',
			),
			'token' => null,
			'gettoken' => false,
		);
	}

	public function getParamDescription() {
		return array(
			'data' => array( 'The serialized object that is used as the data source.',
				"The newly created item will be assigned an item 'id'."
			),
			'item' => 'Indicates if you are changing the content of the item',
			'summary' => 'Summary for the edit.',
			'token' => 'A "setitem" token previously obtained through the gettoken parameter', // or prop=info,
			'gettoken' => 'If set, a "setitem" token will be returned, and no other action will be taken',
		);
	}

	public function getDescription() {
		return array(
			'API module to create a new Wikibase item and modify it with serialised information.'
		);
	}

	protected function getExamples() {
		return array(
			'api.php?action=wbsetitem&data={}'
			=> 'Set an empty JSON structure for the item, it will be extended with an item id and the structure cleansed and completed',
			'api.php?action=wbsetitem&data={"label":{"de":{"language":"de","value":"de-value"},"en":{"language":"en","value":"en-value"}}}'
			=> 'Set a more complete JSON structure for the item.',
		);
	}

	public function getHelpUrls() {
		return 'https://www.mediawiki.org/wiki/Extension:Wikibase/API#wbsetitem';
	}


	public function getVersion() {
		return __CLASS__ . ': $Id$';
	}
	
}