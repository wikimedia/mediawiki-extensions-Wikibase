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
		$this->dieUsage( $this->msg( 'wikibase-api-no-such-item' )->text(), 'no-such-item' );
	}

	/**
	 * @see ApiModifyItem::validateParameters()
	 */
	protected function validateParameters( array $params ) {
		// note that this is changed back and could fail
		if ( !( isset( $params['data'] ) OR  isset( $params['id'] ) XOR ( isset( $params['site'] ) && isset( $params['title'] ) ) ) ) {
			$this->dieUsage( $this->msg( 'wikibase-api-data-or-id-xor-wikititle' )->text(), 'data-or-id-xor-wikititle' );
		}
	}

	/**
	 * @see ApiModifyItem::modifyItem()
	 */
	protected function modifyItem( ItemContent &$itemContent, array $params ) {
		$status = \Status::newGood();
		if ( isset( $params['data'] ) ) {
			$data = json_decode( $params['data'], true );
			if ( is_null( $data ) ) {
				$this->dieUsage( $this->msg( 'wikibase-api-json-invalid' )->text(), 'json-invalid' );
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
						$this->dieUsage( $this->msg( 'wikibase-api-illegal-field', 'pageid' )->text(), 'illegal-field' );
					}
					break;
				case 'ns':
					if ( isset( $data[$props] ) && isset( $title ) && $title->getNamespace() !== $data[$props]) {
						$this->dieUsage( $this->msg( 'wikibase-api-illegal-field', 'namespace' )->text(), 'illegal-field' );
					}
					break;
				case 'title':
					if ( isset( $data[$props] ) && isset( $title ) && $title->getPrefixedText() !== $data[$props]) {
						$this->dieUsage( $this->msg( 'wikibase-api-illegal-field', 'title' )->text(), 'illegal-field' );
					}
					break;
				case 'lastrevid':
					if ( isset( $data[$props] ) && isset( $revision ) && $revision->getId() !== $data[$props]) {
						$this->dieUsage( $this->msg( 'wikibase-api-illegal-field', 'lastrevid' )->text(), 'illegal-field' );
					}
					break;

				// ordinary entries
				case 'labels':
					if ( !is_array( $list ) ) {
						$this->dieUsage( "Key 'labels' must refer to an array", 'not-recognized-array' );
					}

					foreach ( $list as $langCode => $arg ) {
						$status->merge( $this->checkMultilangTexts( $arg, $langCode, $languages ) );
						if ( array_key_exists( 'remove', $arg ) || $arg['value'] === "" ) {
							$itemContent->getItem()->removeLabel( $arg['language'] );
						}
						else {
							$itemContent->getItem()->setLabel( $arg['language'], Utils::squashToNFC( $arg['value'] ) );
						}
					}

					if ( !$status->isOk() ) {
						$this->dieUsage( "whatever", 'whatever' );
					}

					break;

				case 'descriptions':
					if ( !is_array( $list ) ) {
						$this->dieUsage( "Key 'descriptions' must refer to an array", 'not-recognized-array' );
					}

					foreach ( $list as $langCode => $arg ) {
						$status->merge( $this->checkMultilangTexts( $arg, $langCode, $languages ) );
						if ( array_key_exists( 'remove', $arg ) || $arg['value'] === "" ) {
							$itemContent->getItem()->removeDescription( $arg['language'] );
						}
						else {
							$itemContent->getItem()->setDescription( $arg['language'], Utils::squashToNFC( $arg['value'] ) );
						}
					}

					if ( !$status->isOk() ) {
						$this->dieUsage( "whatever", 'whatever' );
					}

					break;

				case 'aliases':
					if ( !is_array( $list ) ) {
						$this->dieUsage( "Key 'aliases' must refer to an array", 'not-recognized-array' );
					}

					foreach ( $list as $langCode => $arg ) {
						$status->merge( $this->checkMultilangAliases( $arg, $langCode, $languages ) );
						$aliases = array();
						foreach ( $arg['values'] as $alias ) {
							if ( !is_string( $alias ) ) {
								$this->dieUsage( $this->msg( 'wikibase-api-not-recognized-string' )->text(), 'not-recognized-string' );
							}
							$aliases[] = Utils::squashToNFC( $alias );
						}
						if ( array_key_exists( 'remove', $arg ) ) {
							$itemContent->getItem()->removeAliases( $langCode, $aliases );
						}
						elseif ( array_key_exists( 'add', $arg ) ) {
							$itemContent->getItem()->addAliases( $langCode, $aliases );
						}
						else {
							$itemContent->getItem()->setAliases( $langCode, $aliases );
						}
					}

					if ( !$status->isOk() ) {
						$this->dieUsage( "whatever", 'whatever' );
					}

					break;

				case 'sitelinks':
					if ( !is_array( $list ) ) {
						$this->dieUsage( "Key 'sitelinks' must refer to an array", 'not-recognized-array' );
					}

					$group = Sites::singleton()->getGroup( SITE_GROUP_WIKIPEDIA );
					foreach ( $list as $siteCode => $arg ) {
						$status->merge( $this->checkSiteLinks( $arg, $siteCode, $group ) );
						if ( array_key_exists( 'remove', $arg ) || $arg['page'] === "" ) {
							$itemContent->getItem()->removeSiteLink( $arg['site'] );
						}
						else {
							$site = $group->getSiteByGlobalId( $arg['site'] );
							$page = $site->normalizePageName( $arg['page'] );

							if ( $page === false ) {
								$this->dieUsage( $this->msg( 'wikibase-api-no-external-page' )->text(), 'add-sitelink-failed' );
							}

							$link = new SiteLink( $site, $page );
							$ret = $itemContent->getItem()->addSiteLink( $link, 'set' );

							if ( $ret === false ) {
								$this->dieUsage( $this->msg( 'wikibase-api-add-sitelink-failed' )->text(), 'add-sitelink-failed' );
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
			array( 'code' => 'no-data', 'info' => $this->msg( 'wikibase-api-no-data' )->text() ),
			array( 'code' => 'wrong-class', 'info' => $this->msg( 'wikibase-api-wrong-class' )->text() ),
			array( 'code' => 'cant-edit', 'info' => $this->msg( 'wikibase-api-cant-edit' )->text() ),
			array( 'code' => 'no-permissions', 'info' => $this->msg( 'wikibase-api-no-permissions' )->text() ),
			array( 'code' => 'save-failed', 'info' => $this->msg( 'wikibase-api-save-failed' )->text() ),
			array( 'code' => 'add-sitelink-failed', 'info' => $this->msg( 'wikibase-api-add-sitelink-failed' )->text() ),
			array( 'code' => 'illegal-field', 'info' => $this->msg( 'wikibase-api-illegal-field' )->text() ),
			array( 'code' => 'not-recognized', 'info' => $this->msg( 'wikibase-api-not-recognized' )->text() ),
			array( 'code' => 'not-recognized-string', 'info' => $this->msg( 'wikibase-api-not-recognized-string' )->text() ),
			array( 'code' => 'not-recognized-array', 'info' => $this->msg( 'wikibase-api-not-recognized-array' )->text() ),
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
				ApiBase::PARAM_TYPE => array( 'pageid', 'ns', 'title', 'lastrevid', 'touched', 'sitelinks', 'aliases', 'labels', 'descriptions' ),
				ApiBase::PARAM_DFLT => '',
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

	/**
	 * Check language code for texts
	 *
	 * @param $keyCode string: The language code used as key for lookup
	 * @param $langCode string: The language code used in the value part
	 * @param &$languages array: The valid language codes as an assoc array
	 *
	 * @return Status: The result from the comparison (always true)
	 *
	 * @throws UsageException
	 */
	public function checkMultilangTexts( $arg, $langCode, &$languages = null ) {
		$status = \Status::newGood();
		if ( !is_array( $arg ) ) {
			$status->fatal( $this->msg( 'wikibase-api-not-recognized-array' )->text(), 'not-recognized-array' );
		}
		if ( !is_string( $arg['language'] ) ) {
			$status->fatal( $this->msg( 'wikibase-api-not-recognized-string' )->text(), 'not-recognized-string' );
		}
		if ( !is_numeric( $langCode ) ) {
			if ( $langCode !== $arg['language'] ) {
				$status->fatal( "inconsistent language: {$langCode} is not equal to {$arg['language']}", 'inconsistent-language' );
			}
		}
		if ( isset( $languages ) && !array_key_exists( $arg['language'], $languages ) ) {
			$status->fatal( "unknown language: {$arg['language']}", 'not-recognized-language' );
		}
		if ( !is_string( $arg['value'] ) ) {
			$status->fatal( $this->msg( 'wikibase-api-not-recognized-string' )->text(), 'not-recognized-string' );
		}
		return $status;
	}

	/**
	 * Check language code for aliases
	 *
	 * @param $keyCode string: The language code used as key for lookup
	 * @param $langCode string: The language code used in the value part
	 * @param &$languages array: The valid language codes as an assoc array
	 *
	 * @return Status: The result from the comparison (always true)
	 *
	 * @throws UsageException
	 */
	public function checkMultilangAliases( $arg, $langCode, &$languages = null ) {
		$status = \Status::newGood();
		if ( !is_array( $arg ) ) {
			$status->fatal( $this->msg( 'wikibase-api-not-recognized-array' )->text(), 'not-recognized-array' );
		}
		if ( !is_string( $arg['language'] ) ) {
			$status->fatal( $this->msg( 'wikibase-api-not-recognized-string' )->text(), 'not-recognized-string' );
		}
		if ( !is_numeric( $langCode ) ) {
			if ( $langCode !== $arg['language'] ) {
				$status->fatal( "inconsistent language: {$langCode} is not equal to {$arg['language']}", 'inconsistent-language' );
			}
		}
		if ( isset( $languages ) && !array_key_exists( $arg['language'], $languages ) ) {
			$status->fatal( "unknown language: {$arg['language']}", 'not-recognized-language' );
		}
		if ( !is_array( $arg['values'] ) ) {
			$status->fatal( $this->msg( 'wikibase-api-not-recognized-string' )->text(), 'not-recognized-string' );
		}
		return $status;
	}

	/**
	 * Check language code for aliases
	 *
	 * @param $keyCode string: The language code used as key for lookup
	 * @param $langCode string: The language code used in the value part
	 * @param &$languages array: The valid language codes as an assoc array
	 *
	 * @return Status: The result from the comparison (always true)
	 *
	 * @throws UsageException
	 */
	public function checkSiteLinks( $arg, $siteCode, &$group = null ) {
		$status = \Status::newGood();
		if ( !is_array( $arg ) ) {
			$status->fatal( $this->msg( 'wikibase-api-not-recognized-array' )->text(), 'not-recognized-array' );
		}
		if ( !is_string( $arg['site'] ) ) {
			$status->fatal( $this->msg( 'wikibase-api-not-recognized-string' )->text(), 'not-recognized-string' );
		}
		if ( !is_numeric( $siteCode ) ) {
			if ( $siteCode !== $arg['site'] ) {
				$status->fatal( "inconsistent site: {$siteCode} is not equal to {$arg['site']}", 'inconsistent-site' );
			}
		}
		if ( isset( $group ) && !$group->hasGlobalId( $arg['site'] ) ) {
			$status->fatal( "unknown site: {$arg['site']}", 'not-recognized-site' );
		}
		if ( !is_string( $arg['page'] ) ) {
			$status->fatal( $this->msg( 'wikibase-api-not-recognized-string' )->text(), 'not-recognized-string' );
		}
		return $status;
	}

}
