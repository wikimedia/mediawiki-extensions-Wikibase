<?php

namespace Wikibase;
use ApiBase, User;

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
 * @author Daniel Kinzler
 */
class ApiSetItem extends ApiModifyItem {

	/**
	 * @see  Api::getRequiredPermissions()
	 */
	protected function getRequiredPermissions( Item $item, array $params ) {
		$permissions = parent::getRequiredPermissions( $item, $params );

		$permissions[] = 'edit';
		$permissions[] = 'item-' . ( $item->getId() ? 'override' : 'create' );
		return $permissions;
	}

	/**
	 * @see  ApiModifyItem::getTextForComment()
	 */
	protected function getTextForComment( array $params, $plural = 'none' ) {
		return Autocomment::formatAutoComment(
			'wbsetitem',
			array()
		);
	}

	/**
	 * @see  ApiModifyItem::getTextForSummary()
	 */
	protected function getTextForSummary( array $params ) {
		return Autocomment::formatAutoSummary(
			Autocomment::pickValuesFromParams( $params, 'data' )
		);
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
	protected function createItem( array $params ) {
		if ( isset( $params['data'] ) ) {
			$this->flags |= EDIT_NEW;
			return ItemContent::newEmpty();
		}
		$this->dieUsage( wfMsg( 'wikibase-api-no-such-item' ), 'no-such-item' );
	}

	/**
	 * Make sure the required parameters are provided and that they are valid.
	 *
	 * @since 0.1
	 *
	 * @param array $params
	 */
	protected function validateParameters( array $params ) {
		// note that this is changed back and could fail
		if ( !( isset( $params['data'] ) OR  isset( $params['id'] ) XOR ( isset( $params['site'] ) && isset( $params['title'] ) ) ) ) {
			$this->dieUsage( wfMsg( 'wikibase-api-data-or-id-xor-wikititle' ), 'data-or-id-xor-wikititle' );
		}
	}

	/**
	 * @see ApiModifyItem::modifyItem()
	 *
	 * @since 0.1
	 *
	 * @param ItemContent $itemContent
	 * @param array $params
	 *
	 * @return boolean Success indicator
	 */
	protected function modifyItem( ItemContent &$itemContent, array $params ) {
		if ( isset( $params['data'] ) ) {
			$data = json_decode( $params['data'], true );
			if ( is_null( $data ) ) {
				$this->dieUsage( wfMsg( 'wikibase-api-json-invalid' ), 'json-invalid' );
			}
			$languages = array_flip( Utils::getLanguageCodes() );
			foreach ( $data as $props => $list ) {
				switch ($props) {
				case 'labels':
					foreach ( $list as $langCode => $value ) {
						if ( !is_string( $value ) ) {
							$this->dieUsage( wfMsg( 'wikibase-api-not-recognized-string' ), 'not-recognized-string' );
						}
						if ( !array_key_exists( $langCode, $languages ) ) {
							$this->dieUsage( wfMsg( 'wikibase-api-not-recognized-language' ), 'not-recognized-language' );
						}
						if ( $value === "" ) {
							$itemContent->getItem()->removeLabel( $langCode );
						}
						else {
							$itemContent->getItem()->setLabel( $langCode, Utils::squashToNFC( $value ) );
						}
					}
					break;
				case 'descriptions':
					foreach ( $list as $langCode => $value ) {
						if ( !is_string( $value ) ) {
							$this->dieUsage( wfMsg( 'wikibase-api-not-recognized-string' ), 'not-recognized-string' );
						}
						if ( !array_key_exists( $langCode, $languages ) ) {
							$this->dieUsage( wfMsg( 'wikibase-api-not-recognized-language' ), 'not-recognized-language' );
						}
						if ( $value === "" ) {
							$itemContent->getItem()->removeDescription( $langCode );
						}
						else {
							$itemContent->getItem()->setDescription( $langCode, Utils::squashToNFC( $value ) );
						}
					}
					break;
				case 'aliases':
					foreach ( $list as $langCode => $aliases ) {
						if ( !is_array( $aliases ) ) {
							$this->dieUsage( wfMsg( 'wikibase-api-not-recognized-array' ), 'not-recognized-array' );
						}
						if ( !array_key_exists( $langCode, $languages ) ) {
							$this->dieUsage( wfMsg( 'wikibase-api-not-recognized-language' ), 'not-recognized-language' );
						}
						$newAliases = array();
						foreach ( $aliases as $alias ) {
							if ( !is_string( $alias ) ) {
								$this->dieUsage( wfMsg( 'wikibase-api-not-recognized-string' ), 'not-recognized-string' );
							}
							$newAliases[] = Utils::squashToNFC( $alias );
						}
						$itemContent->getItem()->setAliases( $langCode, $newAliases );
					}
					break;
				case 'sitelinks':
					$group = Sites::singleton()->getGroup( SITE_GROUP_WIKIPEDIA );
					foreach ( $list as $siteId => $pageName ) {
						if ( !is_string( $pageName ) ) {
							$this->dieUsage( wfMsg( 'wikibase-api-not-recognized-string' ), 'add-sitelink-failed' );
						}

						if ( !$group->hasGlobalId( $siteId ) ) {
							$this->dieUsage( wfMsg( 'wikibase-api-not-recognized-siteid' ), 'add-sitelink-failed' );
						}

						$site = $group->getSiteByGlobalId( $siteId );
						$page = $site->normalizePageName( $pageName );

						if ( $page === false ) {
							$this->dieUsage( wfMsg( 'wikibase-api-no-external-page' ), 'add-sitelink-failed' );
						}

						$link = new SiteLink( $site, $page );
						$ret = $itemContent->getItem()->addSiteLink( $link, 'set' );

						if ( $ret === false ) {
							$this->dieUsage( wfMsg( 'wikibase-api-add-sitelink-failed' ), 'add-sitelink-failed' );
						}
					}
					break;
				default:
					$this->dieUsage( wfMsg( 'wikibase-api-not-recognized' ), 'not-recognized' );
				}
			}
		}

		$item = $itemContent->getItem();

		$res = $this->getResult();

		$this->setUsekeys( $params );
		$this->addLabelsToResult( $item->getLabels(), 'item' );
		$this->addDescriptionsToResult( $item->getDescriptions(), 'item' );
		$this->addAliasesToResult( $item->getAllAliases(), 'item' );
		$this->addSiteLinksToResult( $item->getSiteLinks(), 'item' );

		return true;
	}

	/**
	 * Returns a list of all possible errors returned by the module
	 * @return array in the format of array( key, param1, param2, ... ) or array( 'code' => ..., 'info' => ... )
	 */
	public function getPossibleErrors() {
		return array_merge( parent::getPossibleErrors(), array(
			array( 'code' => 'no-data', 'info' => wfMsg( 'wikibase-api-no-data' ) ),
			array( 'code' => 'wrong-class', 'info' => wfMsg( 'wikibase-api-wrong-class' ) ),
			array( 'code' => 'cant-edit', 'info' => wfMsg( 'wikibase-api-cant-edit' ) ),
			array( 'code' => 'no-permissions', 'info' => wfMsg( 'wikibase-api-no-permissions' ) ),
			array( 'code' => 'save-failed', 'info' => wfMsg( 'wikibase-api-save-failed' ) ),
			array( 'code' => 'add-sitelink-failed', 'info' => wfMsg( 'wikibase-api-add-sitelink-failed' ) ),
		) );
	}

	/**
	 * Returns whether this module requires a Token to execute
	 * @return bool
	 */
	public function needsToken() {
		return Settings::get( 'apiInDebug' ) ? Settings::get( 'apiDebugWithTokens', false ) : true ;
	}

	/**
	 * Indicates whether this module must be called with a POST request
	 * @return bool
	 */
	public function mustBePosted() {
		return Settings::get( 'apiInDebug' ) ? Settings::get( 'apiDebugWithPost', false ) : true ;
	}

	/**
	 * Indicates whether this module requires write mode
	 * @return bool
	 */
	public function isWriteMode() {
		return Settings::get( 'apiInDebug' ) ? Settings::get( 'apiDebugWithWrite', false ) : true ;
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
			'data' => array(
				ApiBase::PARAM_TYPE => 'string',
			),
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
			'data' => array( 'The serialized object that is used as the data source.',
				"The newly created item will be assigned an item 'id'."
			),
		) );
	}

	/**
	 * Returns the description string for this module
	 * @return mixed string or array of strings
	 */
	public function getDescription() {
		return array(
			'API module to create a single new Wikibase item and modify it with serialised information.'
		);
	}

	/**
	 * Returns usage examples for this module. Return false if no examples are available.
	 * @return bool|string|array
	 */
	protected function getExamples() {
		return array(
			'api.php?action=wbsetitem&data={}&format=jsonfm'
			=> 'Set an empty JSON structure for the item, it will be extended with an item id and the structure cleansed and completed. Report it as pretty printed json format.',
			'api.php?action=wbsetitem&data={"label":{"de":{"language":"de","value":"de-value"},"en":{"language":"en","value":"en-value"}}}'
			=> 'Set a more complete JSON structure for the item, it will be extended with an item id and the structure cleansed and completed.',
		);
	}

	/**
	 * @return bool|string|array Returns a false if the module has no help url, else returns a (array of) string
	 */
	public function getHelpUrls() {
		return 'https://www.mediawiki.org/wiki/Extension:Wikibase/API#wbsetitem';
	}

	/**
	 * Returns a string that identifies the version of this class.
	 * @return string
	 */
	public function getVersion() {
		return __CLASS__ . ': $Id$';
	}

}
