<?php

/**
 * API module to set the aliases for a Wikibase item.
 * Requires API write mode to be enabled.
 *
 * @since 0.1
 *
 * @file ApiWikibaseAliases.php
 * @ingroup Wikibase
 * @ingroup API
 *
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 * @author John Erling Blad < jeblad@gmail.com >
 */
class ApiWikibaseSetAliases extends ApiWikibaseModifyItem {

	/**
	 * Check the rights
	 * 
	 * @param $user User doing the action
	 * @param $token String
	 * @return array
	 */
	protected function getPermissionsErrorInternal( $user, array $params, $mod=null, $op=null ) {
		return parent::getPermissionsError( $user, 'alias', $params['item'] );
	}
	
	/**
	 * Report the new values so they can be added to the result array
	 * @return array conaining the values for the result
	 */
	protected function reportNewValues( WikibaseItem &$item, array $params ) {
		// TODO: Return the correct values
		return array();
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
		parent::validateParameters( $params );

		if ( !( ( isset( $params['add'] ) || isset( $params['remove'] ) ) XOR isset( $params['set'] ) ) ) {
			$this->dieUsage( wfMsg( 'wikibase-api-aliases-invalid-list' ), 'aliases-invalid-list' );
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
		if ( isset( $params['set'] ) ) {
			$item->setAliases( $params['language'], $params['set'] );
		}

		if ( isset( $params['remove'] ) ) {
			$item->removeAliases( $params['language'], $params['remove'] );
		}

		if ( isset( $params['add'] ) ) {
			$item->addAliases( $params['language'], $params['add'] );
		}

		return true;
	}

	/**
	 * Returns a list of all possible errors returned by the module
	 * @return array in the format of array( key, param1, param2, ... ) or array( 'code' => ..., 'info' => ... )
	 */
	public function getPossibleErrors() {
		return array_merge( parent::getPossibleErrors(), array(
			array( 'code' => 'aliases-invalid-list', 'info' => wfMsg( 'wikibase-api-aliases-invalid-list' ) ),
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
			'add' => array(
				ApiBase::PARAM_TYPE => 'string',
				ApiBase::PARAM_ISMULTI => true,
			),
			'remove' => array(
				ApiBase::PARAM_TYPE => 'string',
				ApiBase::PARAM_ISMULTI => true,
			),
			'set' => array(
				ApiBase::PARAM_TYPE => 'string',
				ApiBase::PARAM_ISMULTI => true,
			),
			'language' => array(
				ApiBase::PARAM_TYPE => WikibaseUtils::getLanguageCodes(),
				ApiBase::PARAM_REQUIRED => true,
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
			'add' => 'List of aliases to add',
			'remove' => 'List of aliases to remove',
			'set' => 'A list of aliases that will replace the current list',
			'language' => 'The language of which to set the aliases',
		) );
	}

	/**
	 * Returns a list of all possible errors returned by the module
	 * @return array in the format of array( key, param1, param2, ... ) or array( 'code' => ..., 'info' => ... )
	 */
	public function getDescription() {
		return array(
			'API module to set the aliases for a Wikibase item.'
		);
	}

	/**
	 * Returns usage examples for this module. Return false if no examples are available.
	 * @return bool|string|array
	 */
	protected function getExamples() {
		return array(
			'api.php?action=wbsetaliases&language=en&id=1&set=Foo|Bar'
				=> 'Set the English labels for the item with id 1 to Foo and Bar',

			'api.php?action=wbsetaliases&language=en&id=1&add=Foo|Bar'
				=> 'Add Foo and Bar to the list of English labels for the item with id 1',

			'api.php?action=wbsetaliases&language=en&id=1&set=Foo|Bar'
				=> 'Remove Foo and Bar from the list of English labels for the item with id 1',
		);
	}

	/**
	 * @return bool|string|array Returns a false if the module has no help url, else returns a (array of) string
	 */
	public function getHelpUrls() {
		return 'https://www.mediawiki.org/wiki/Extension:Wikibase/API#wbsetaliases';
	}


	/**
	 * Returns a string that identifies the version of this class.
	 * @return string
	 */
	public function getVersion() {
		return __CLASS__ . ': $Id$';
	}

}
