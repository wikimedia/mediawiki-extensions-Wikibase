<?php

namespace Wikibase;
use ApiBase, User;

/**
 * Base class for API modules modifying a single item identified based on id xor a combination of site and page title.
 *
 * @since 0.1
 *
 * @file
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
			array()
		);
	}

	/**
	 * @see ApiModifyItem::createItem()
	 */
	protected function createItem( array $params ) {
		if ( isset( $params['data'] ) ) {
			$this->flags |= EDIT_NEW;
			return ItemContent::newEmpty();
		}
		$this->dieUsage( wfMsg( 'wikibase-api-no-such-item' ), 'no-such-item' );
	}

	/**
	 * @see ApiModifyItem::validateParameters()
	 */
	protected function validateParameters( array $params ) {
		// note that this is changed back and could fail
		if ( !( isset( $params['data'] ) OR  isset( $params['id'] ) XOR ( isset( $params['site'] ) && isset( $params['title'] ) ) ) ) {
			$this->dieUsage( wfMsg( 'wikibase-api-data-or-id-xor-wikititle' ), 'data-or-id-xor-wikititle' );
		}
	}

	/**
	 * @see ApiModifyItem::modifyItem()
	 */
	protected function modifyItem( ItemContent &$itemContent, array $params ) {
		if ( isset( $params['data'] ) ) {
			$data = json_decode( $params['data'], true );
			if ( is_null( $data ) ) {
				$this->dieUsage( wfMsg( 'wikibase-api-json-invalid' ), 'json-invalid' );
			}
			if ( !is_array( $data ) ) { // NOTE: json_decode will decode any JS literal or structure, not just objects!
				$this->dieUsage( 'Top level structure must be a JSON object', 'not-recognized-array' );
			}
			$languages = array_flip( Utils::getLanguageCodes() );

			if ( isset( $params['clear'] ) && $params['clear'] ) {
				$itemContent->getItem()->clear();
			}

			$page = $itemContent->getWikiPage();
			if ( $page ) {
				$title = $page->getTitle();
				$revision = $page->getRevision();
			}

			foreach ( $data as $props => $list ) {
				if ( !is_string( $props ) ) { // NOTE: catch json_decode returning an indexed array (list)
					$this->dieUsage( 'Top level structure must be a JSON object', 'not-recognized-string' );
				}
				// unconditional no-ops
				if ( in_array( $props, array( 'length', 'count', 'touched' ) ) ) {
					continue;
				}
				// conditional no-ops
				if ( isset( $params['exclude'] ) && in_array( $props, $params['exclude'] ) ) {
					continue;
				}

				switch ($props) {

				// conditional processing
				case 'pageid':
					if ( isset( $data[$props] ) && ($page) && $page->getId() !== $data[$props]) {
						$this->dieUsage( wfMsg( 'wikibase-api-illegal-field', 'pageid' ), 'illegal-field' );
					}
					break;
				case 'ns':
					if ( isset( $data[$props] ) && isset( $title ) && $title->getNamespace() !== $data[$props]) {
						$this->dieUsage( wfMsg( 'wikibase-api-illegal-field', 'namespace' ), 'illegal-field' );
					}
					break;
				case 'title':
					if ( isset( $data[$props] ) && isset( $title ) && $title->getPrefixedText() !== $data[$props]) {
						$this->dieUsage( wfMsg( 'wikibase-api-illegal-field', 'title' ), 'illegal-field' );
					}
					break;
				case 'lastrevid':
					if ( isset( $data[$props] ) && isset( $revision ) && $revision->getId() !== $data[$props]) {
						$this->dieUsage( wfMsg( 'wikibase-api-illegal-field', 'lastrevid' ), 'illegal-field' );
					}
					break;

				// ordinary entries
				case 'labels':
					if ( !is_array( $list ) ) {
						$this->dieUsage( "Key 'labels' must refer to an array", 'not-recognized-array' );
					}

					foreach ( $list as $langCode => $value ) {
						if ( !is_string( $value ) ) {
							$this->dieUsage( wfMsg( 'wikibase-api-not-recognized-string' ), 'not-recognized-string' );
						}
						if ( !array_key_exists( $langCode, $languages ) ) {
							$this->dieUsage( "unknown language: $langCode", 'not-recognized-language' );
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
					if ( !is_array( $list ) ) {
						$this->dieUsage( "Key 'descriptions' must refer to an array", 'not-recognized-array' );
					}

					foreach ( $list as $langCode => $value ) {
						if ( !is_string( $value ) ) {
							$this->dieUsage( wfMsg( 'wikibase-api-not-recognized-string' ), 'not-recognized-string' );
						}
						if ( !array_key_exists( $langCode, $languages ) ) {
							$this->dieUsage( "unknown language: $langCode", 'not-recognized-language' );
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
					if ( !is_array( $list ) ) {
						$this->dieUsage( "Key 'aliases' must refer to an array", 'not-recognized-array' );
					}

					foreach ( $list as $langCode => $aliases ) {
						if ( !is_array( $aliases ) ) {
							$this->dieUsage( wfMsg( 'wikibase-api-not-recognized-array' ), 'not-recognized-array' );
						}
						if ( !array_key_exists( $langCode, $languages ) ) {
							$this->dieUsage( "unknown language: $langCode", 'not-recognized-language' );
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
					if ( !is_array( $list ) ) {
						$this->dieUsage( "Key 'sitelinks' must refer to an array", 'not-recognized-array' );
					}

					$group = Sites::singleton()->getGroup( SITE_GROUP_WIKIPEDIA );
					foreach ( $list as $siteId => $pageName ) {
						if ( !is_string( $pageName ) ) {
							$this->dieUsage( wfMsg( 'wikibase-api-not-recognized-string' ), 'add-sitelink-failed' );
						}

						if ( !$group->hasGlobalId( $siteId ) ) {
							$this->dieUsage( "unknown site: $siteId", 'add-sitelink-failed' );
						}

						if ( $pageName === '' ) {
							$itemContent->getItem()->removeSiteLink( $siteId );
						} else {
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
					}
					break;
				default:
					$this->dieUsage( "unknown key: $props", 'not-recognized' );
				}
			}
		}

		$item = $itemContent->getItem();

		$this->addLabelsToResult( $item->getLabels(), 'item' );
		$this->addDescriptionsToResult( $item->getDescriptions(), 'item' );
		$this->addAliasesToResult( $item->getAllAliases(), 'item' );
		$this->addSiteLinksToResult( $item->getSiteLinks(), 'item' );

		return true;
	}

	/**
	 * @see ApiBase::getPossibleErrors()
	 */
	public function getPossibleErrors() {
		return array_merge( parent::getPossibleErrors(), array(
			array( 'code' => 'no-data', 'info' => wfMsg( 'wikibase-api-no-data' ) ),
			array( 'code' => 'wrong-class', 'info' => wfMsg( 'wikibase-api-wrong-class' ) ),
			array( 'code' => 'cant-edit', 'info' => wfMsg( 'wikibase-api-cant-edit' ) ),
			array( 'code' => 'no-permissions', 'info' => wfMsg( 'wikibase-api-no-permissions' ) ),
			array( 'code' => 'save-failed', 'info' => wfMsg( 'wikibase-api-save-failed' ) ),
			array( 'code' => 'add-sitelink-failed', 'info' => wfMsg( 'wikibase-api-add-sitelink-failed' ) ),
			array( 'code' => 'illegal-field', 'info' => wfMsg( 'wikibase-api-illegal-field' ) ),
		) );
	}

	/**
	 * @see ApiBase::needsToken()
	 */
	public function needsToken() {
		return Settings::get( 'apiInDebug' ) ? Settings::get( 'apiDebugWithTokens', false ) : true ;
	}

	/**
	 * @see ApiBase::mustBePosted()
	 */
	public function mustBePosted() {
		return Settings::get( 'apiInDebug' ) ? Settings::get( 'apiDebugWithPost', false ) : true ;
	}

	/**
	 * @see ApiBase::isWriteMode()
	 */
	public function isWriteMode() {
		return Settings::get( 'apiInDebug' ) ? Settings::get( 'apiDebugWithWrite', false ) : true ;
	}

	/**
	 * @see ApiBase::getAllowedParams()
	 */
	public function getAllowedParams() {
		return array_merge( parent::getAllowedParams(), array(
			'data' => array(
				ApiBase::PARAM_TYPE => 'string',
			),
			'exclude' => array(
				ApiBase::PARAM_TYPE => array( 'pageid', 'ns', 'title', 'lastrevid', 'sitelinks', 'aliases', 'labels', 'descriptions' ),
				ApiBase::PARAM_DFLT => '', // per default, all fields are processed
				ApiBase::PARAM_ISMULTI => true,
			),
			'clear' => array(
				ApiBase::PARAM_TYPE => 'boolean',
				ApiBase::PARAM_DFLT => false
			),
		) );
	}

	/**
	 * @see ApiBase::getParamDescription()
	 */
	public function getParamDescription() {
		return array_merge( parent::getParamDescription(), array(
			'data' => array( 'The serialized object that is used as the data source.',
				"The newly created item will be assigned an item 'id'."
			),
			'exclude' => array( 'List of substructures to neglect during the processing.',
				"In addition 'length', 'touched' and 'count' is always excluded."
			),
			'clear' => array( 'If set, the complete item is emptied before proceeding.',
				'The item will not be saved before the item is filled with the "data", possibly with parts excluded.'
			),
		) );
	}

	/**
	 * @see ApiBase::getDescription()
	 */
	public function getDescription() {
		return array(
			'API module to create a single new Wikibase item and modify it with serialised information.'
		);
	}

	/**
	 * @see ApiBase::getExamples()
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
	 * @see ApiBase::getHelpUrls()
	 */
	public function getHelpUrls() {
		return 'https://www.mediawiki.org/wiki/Extension:Wikibase/API#wbsetitem';
	}

	/**
	 * @see ApiBase::getVersion()
	 */
	public function getVersion() {
		return __CLASS__ . ': $Id$';
	}

}
