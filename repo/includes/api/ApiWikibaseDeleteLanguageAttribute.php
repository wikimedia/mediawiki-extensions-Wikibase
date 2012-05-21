<?php

/**
 * API module to delete the language attributes for a Wikibase item.
 * Requires API write mode to be enabled.
 *
 * @since 0.1
 *
 * @file ApiWikibaseSetDescription.php
 * @ingroup Wikibase
 * @ingroup API
 *
 * @licence GNU GPL v2+
 * @author John Erling Blad < jeblad@gmail.com >
 */
class ApiWikibaseDeleteLanguageAttribute extends ApiWikibaseModifyItem {

	/**
	 * Check the rights for the user accessing this module.
	 * This is called from ModifyItem.
	 * 
	 * @param $user User doing the action
	 * @param $params array of arguments for the module, passed for ModifyItem
	 * @param $mod null|String name of the module, usually not set
	 * @param $op null|String operation that is about to be done, usually not set
	 * @return array of errors reported from the static getPermissionsError
	 */
	protected function getPermissionsErrorInternal( $user, array $params, $mod=null, $op=null ) {
		return parent::getPermissionsError( $user, 'lang-attr', 'remove' );
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
		$language = $params['language'];
		$labels = $item->getLabels( array( $language ) );
		$descriptions = $item->getDescriptions( array( $language ) );
		
		$success = false;
		
		foreach ($params['attribute'] as $attr) {
			
			switch ($attr) {
				case 'label':
					if ( !count($labels) ) {
						$this->dieUsage( wfMsg( 'wikibase-api-label-not-found' ), 'label-not-found' );
					}
					$item->removeLabel( $language );
					$success = $success || true;
					break;
					
				case 'description':
					if ( !count($descriptions) ) {
						$this->dieUsage( wfMsg( 'wikibase-api-description-not-found' ), 'description-not-found' );
					}
					$item->removeDescription( $language );
					$success = $success || true;
					break;
					
				default:
					// should never be here
					$this->dieUsage( wfMsg( 'wikibase-api-not-recognized' ), 'not-recognized' );
			}
			
		}
		
		return $success;
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
				ApiBase::PARAM_TYPE => WikibaseUtils::getLanguageCodes(),
				ApiBase::PARAM_REQUIRED => true,
			),
			'attribute' => array(
				ApiBase::PARAM_TYPE => array( 'label', 'description'),
				ApiBase::PARAM_REQUIRED => true,
				ApiBase::PARAM_ISMULTI => true,
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
			'language' => 'Language the description is in',
			'attribute' => array('The type of attribute to delete',
					'One of ("label", "description")')
		) );
	}

	/**
	 * Returns the description string for this module
	 * @return mixed string or array of strings
	 */
	public function getDescription() {
		return array(
			'API module to delete a label or description for a single Wikibase item.'
		);
	}

	/**
	 * Returns a list of all possible errors returned by the module
	 * @return array in the format of array( key, param1, param2, ... ) or array( 'code' => ..., 'info' => ... )
	 */
	public function getPossibleErrors() {
		return array_merge( parent::getPossibleErrors(), array(
			array( 'code' => 'label-not-found', 'info' => wfMsg( 'wikibase-api-label-not-found' ) ),
			array( 'code' => 'description-not-found', 'info' =>  wfMsg( 'wikibase-api-description-not-found' ) ),
			array( 'code' => 'not-recognized', 'info' => wfMsg( 'wikibase-api-not-recognized' ) ),
			) );
	}

	/**
	 * Returns usage examples for this module. Return false if no examples are available.
	 * @return bool|string|array
	 */
	protected function getExamples() {
		return array(
			'api.php?action=wbdeletelanguageattribute&id=42&language=en&attribute=label'
				=> 'Delete whatever is stored in the attribute "label" in english language.',
		);
	}
	
	/**
	 * @return bool|string|array Returns a false if the module has no help url, else returns a (array of) string
	 */
	public function getHelpUrls() {
		return 'https://www.mediawiki.org/wiki/Extension:Wikibase/API#wbdeletelanguageattribute';
	}
	

	/**
	 * Returns a string that identifies the version of this class.
	 * @return string
	 */
	public function getVersion() {
		return __CLASS__ . ': $Id$';
	}

}
