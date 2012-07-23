<?php

namespace Wikibase;
use ApiBase;

/**
 * API module to set the language attributes for a Wikibase item.
 * Requires API write mode to be enabled.
 *
 * @since 0.1
 *
 * @file ApiWikibaseSetDescription.php
 * @ingroup Wikibase
 * @ingroup API
 *
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 * @author John Erling Blad < jeblad@gmail.com >
 */
class ApiSetLanguageAttribute extends ApiModifyItem {

	/**
	 * @see  ApiModifyItem::getRequiredPermissions()
	 */
	protected function getRequiredPermissions( Item $item, array $params ) {
		$permissions = parent::getRequiredPermissions( $item, $params );

		if ( isset( $params['label'] ) ) {
			$permissions[] = 'label-' . ( strlen( $params['label'] ) ? 'update' : 'remove' );
		}

		if ( isset( $params['description'] ) ) {
			$permissions[] = 'description-' . ( strlen( $params['label'] ) ? 'update' : 'remove' );
		}

		return $permissions;
	}

	/**
	 * Make sure the required parameters are provided and that they are valid.
	 *
	 * @since 0.1
	 *
	 * @param array $params
	 */
	protected function validateParameters( array $params ) {
		parent::validateParameters( $params );

		if ( !isset( $params['label'] ) && !isset( $params['description'] ) ) {
			$this->dieUsage( wfMsg( 'wikibase-api-label-or-description' ), 'label-or-description' );
		}
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
		$this->dieUsage( wfMsg( 'wikibase-api-no-such-item' ), 'no-such-item' );
		return null;
	}

	/**
	 * Actually modify the item.
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
		$language = $params['language'];

		if ( isset( $params['label'] ) ) {
			$label = Utils::squashToNFC( $params['label'] );
			if ( 0 < strlen( $label ) ) {
				$labels = array( $language => $itemContent->getItem()->setLabel( $language, $label ) );
			}
			else {
				// TODO: should probably be some kind of status from the remove operation
				$itemContent->getItem()->removeLabel( $language );
				$labels = array( $language => '' );
			}
			$this->addLabelsToResult( $labels, 'item' );
		}

		if ( isset( $params['description'] ) ) {
			$description = Utils::squashToNFC( $params['description'] );
			if ( 0 < strlen( $description ) ) {
				$descriptions = array( $language => $itemContent->getItem()->setDescription( $language, $description ) );
			}
			else {
				// TODO: should probably be some kind of status from the remove operation
				$itemContent->getItem()->removeDescription( $language );
				$descriptions = array( $language => '' );
			}
			$this->addDescriptionsToResult( $descriptions, 'item' );
		}

		// Because we can't fail?
		return true;
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
			'language' => array(
				ApiBase::PARAM_TYPE => Utils::getLanguageCodes(),
				ApiBase::PARAM_REQUIRED => true,
			),
			'label' => array(
				ApiBase::PARAM_TYPE => 'string',
			),
			'description' => array(
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
			'language' => 'Language for the label and description',
			'label' => 'The value to set for the label',
			'description' => 'The value to set for the description',
		) );
	}

	/**
	 * Returns a list of all possible errors returned by the module
	 * @return array in the format of array( key, param1, param2, ... ) or array( 'code' => ..., 'info' => ... )
	 */
	public function getDescription() {
		return array(
			'API module to set a label or description for a single Wikibase item.'
		);
	}

	/**
	 * Returns a list of all possible errors returned by the module
	 * @return array in the format of array( key, param1, param2, ... ) or array( 'code' => ..., 'info' => ... )
	 */
	public function getPossibleErrors() {
		return array_merge( parent::getPossibleErrors(), array(
			array( 'code' => 'label-or-description', 'info' => wfMsg( 'wikibase-api-label-or-description' ) ),
			) );
	}

	/**
	 * Returns usage examples for this module. Return false if no examples are available.
	 * @return bool|string|array
	 */
	protected function getExamples() {
		return array(
			'api.php?action=wbsetlanguageattribute&id=42&language=en&label=Wikimedia&format=jsonfm'
				=> 'Set the string "Wikimedia" for page with id "42" as a label in English language and report it as pretty printed json',
			'api.php?action=wbsetlanguageattribute&id=42&language=en&description=An%20encyclopedia%20that%20everyone%20can%20edit'
				=> 'Set the string "An encyclopedia that everyone can edit" for page with id "42" as a decription in English language',
			'api.php?action=wbsetlanguageattribute&id=42&language=en&label=Wikimedia&description=An%20encyclopedia%20that%20everyone%20can%20edit'
				=> 'Set the string "Wikimedia" for page with id "42" as a label, and the string "An encyclopedia that everyone can edit" as a decription in English language',
		);
	}

	/**
	 * @return bool|string|array Returns a false if the module has no help url, else returns a (array of) string
	 */
	public function getHelpUrls() {
		return 'https://www.mediawiki.org/wiki/Extension:Wikibase/API#wbsetlanguageattribute';
	}

	/**
	 * Returns a string that identifies the version of this class.
	 * @return string
	 */
	public function getVersion() {
		return __CLASS__ . ': $Id$';
	}

}
