<?php

/**
 * API module to set an alias with a label for a Wikibase item or remove an already made such association.
 * Requires API write mode to be enabled.
 *
 * @since 0.1
 *
 * @file ApiWikibaseAlias.php
 * @ingroup Wikibase
 * @ingroup API
 *
 * @licence GNU GPL v2+
 * @author John Erling Blad < jeblad@gmail.com >
 */
class ApiWikibaseSetAlias extends ApiWikibaseModifyItem {

	/**
	 * Check the rights for the user accessing this module.
	 * This is called from ModifyItem.
	 * 
	 * @param $title Title object where the item is stored
	 * @param $user User doing the action
	 * @param $params array of arguments for the module, passed for ModifyItem
	 * @param $mod null|String name of the module, usually not set
	 * @param $op null|String operation that is about to be done, usually not set
	 * @return array of errors reported from the static getPermissionsError
	 */
	protected function getPermissionsErrorInternal( $title, $user, array $params, $mod=null, $op=null ) {
		return parent::getPermissionsError( $title, $user, 'alias', $params['item'] );
	}
	
	/**
	 * Make sure the required parameters are provided and that they are valid.
	 * This overrides the base class
	 *
	 * @since 0.1
	 *
	 * @param array $params
	 */
	protected function validateParameters( array $params ) {
		if ( !isset( $params['site'] ) && !isset( $params['title'] ) ) {
			$this->dieUsage( wfMsg( 'wikibase-api-alias-incomplete' ), 'alias-incomplete' );
		}

		if ( isset( $params['id'] ) && $params['item'] === 'add' ) {
			$this->dieUsage( wfMsg( 'wikibase-api-add-with-id' ), 'add-with-id' );
		}
	}
	
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
	protected function modifyItem( WikibaseItem &$item, array $params ) {
		$success = false;
		if ($params['item'] === 'remove') {
			$success = $item->removeAlias( $params['site'], $params['title'] );
		}
		else {
			$success = $item->addSiteLink( $params['site'], $params['title'], $params['item'] );
			if (!$success) {
				switch ($params['item']) {
					case 'update':
						$this->dieUsage( wfMsg( 'wikibase-api-alias-not-found' ), 'alias-not-found' );
						break;
					case 'add':
						$this->dieUsage( wfMsg( 'wikibase-api-alias-found' ), 'alias-found' );
						break;
					default:
						$this->dieUsage( wfMsg( 'wikibase-api-not-recognized' ), 'not-recognized' );
				}
			}
		}
		return $success;
	}

	/**
	 * Returns a list of all possible errors returned by the module
	 * @return array in the format of array( key, param1, param2, ... ) or array( 'code' => ..., 'info' => ... )
	 */
	public function getPossibleErrors() {
		return array_merge( parent::getPossibleErrors(), array(
			array( 'code' => 'alias-incomplete', 'info' => wfMsg( 'wikibase-api-alias-incomplete' ) ),
			array( 'code' => 'alias-not-found', 'info' => wfMsg( 'wikibase-api-alias-not-found' ) ),
			array( 'code' => 'alias-found', 'info' => wfMsg( 'wikibase-api-alias-found' ) ),
			array( 'code' => 'not-recognized', 'info' => wfMsg( 'wikibase-api-not-recognized' ) ),
		) );
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
		) );
	}

	/**
	 * Returns the description string for this module
	 * @return mixed string or array of strings
	 */
	public function getDescription() {
		return array(
			'API module to associate an alias with a Wikibase item or remove an already made such association.'
		);
	}

	/**
	 * Returns usage examples for this module. Return false if no examples are available.
	 * @return bool|string|array
	 */
	protected function getExamples() {
		return array(
			'api.php?action=wbsetalias&id=42&language=en&label=Wikimedia'
			=> 'Set title "Wikimedia" for English page with id "42"',
			'api.php?action=wbsetalias&id=42&language=en&label=Wikimedia&summary=World%20domination%20will%20be%20mine%20soon!'
			=> 'Set title "Wikimedia" for English page with id "42" with an edit summary',
			'api.php?action=wbsetalias&id=42&language=en&label=Wikimedia'
			=> 'Set title "Wikimedia" for English page with id "42"',
		);
	}

	/**
	 * @return bool|string|array Returns a false if the module has no help url, else returns a (array of) string
	 */
	public function getHelpUrls() {
		return 'https://www.mediawiki.org/wiki/Extension:Wikibase/API#wbsetalias';
	}

	/**
	 * Returns a string that identifies the version of this class.
	 * @return string
	 */
	public function getVersion() {
		return __CLASS__ . ': $Id$';
	}

}
