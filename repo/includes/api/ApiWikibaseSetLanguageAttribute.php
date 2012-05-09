<?php

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
class ApiWikibaseSetLanguageAttribute extends ApiWikibaseModifyItem {

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
		return parent::getPermissionsError( $user, 'lang-attr', $params['item'] );
	}
	
	/**
	 * Report the new values so they can be added to the result array
	 * @return array conaining the values for the result
	 */
	protected function reportNewValues( WikibaseItem &$item, array $params ) {
		$languages = WikibaseUtils::getLanguageCodes();
		$labels = $item->getLabels();
		$descriptions = $item->getDescriptions();
		$res = array();
		// TODO: Get this to report the changed values
		/*
		switch ($params['item']) {
			case 'update':
			case 'add':
			case 'set':
				if ( isset($params['label']) ) {
					$res['labels'] = array( $item->getLabel( $params['language'] ) );
				}
				if ( isset($params['description']) ) {
					$res['descriptions'] = array( $item->getDescription( $params['language'] ) );
				}
				break;
			default:
				$this->dieUsage( wfMsg( 'wikibase-api-not-recognized' ), 'not-recognized' );
		}
		*/
		return $res;
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
		$languages = WikibaseUtils::getLanguageCodes();
		$labels = $item->getLabels();
		$descriptions = $item->getDescriptions();
		$success = false;
		switch ($params['item']) {
			case 'update':
				if ( isset($params['label']) ) {
					if ( !isset($labels[$params['language']]) ) {
						$this->dieUsage( wfMsg( 'wikibase-api-label-not-found' ), 'label-not-found' );
					}
					$this->setItemLabel( $item, $params['language'], $params['label'] );
				}
				if ( isset($params['description']) ) {
					if ( !isset($descriptions[$params['language']]) ) {
						$this->dieUsage( wfMsg( 'wikibase-api-description-not-found' ), 'description-not-found' );
					}
					$this->setItemDescription( $item, $params['language'], $params['description'] );
				}
				$success = true;
				break;
			case 'add':
				if ( isset($params['label']) ) {
					if ( isset($labels[$params['language']]) ) {
						$this->dieUsage( wfMsg( 'wikibase-api-label-found' ), 'label-found' );
					}
					$this->setItemLabel( $item, $params['language'], $params['label'] );
				}
				if ( isset($params['description']) ) {
					if ( isset($descriptions[$params['language']]) ) {
						$this->dieUsage( wfMsg( 'wikibase-api-description-found' ), 'description-found' );
					}
					$this->setItemDescription( $item, $params['language'], $params['description'] );
				}
				$success = true;
				break;
			case 'set':
				if (isset($params['label'])) {
					$this->setItemLabel( $item, $params['language'], $params['label'] );
				}
				if (isset($params['description'])) {
					$this->setItemDescription( $item, $params['language'], $params['description'] );
				}
				$success = true;
				break;
			default:
				$this->dieUsage( wfMsg( 'wikibase-api-not-recognized' ), 'not-recognized' );
		}
		return $success;
	}
	
	/**
	 * Sets the label in the item and reports the new value.
	 * This method does not handle a label in multiple languages.
	 * 
	 * @param WikibaseItem $item
	 * @param string $language
	 * @param string $label
	 */
	protected function setItemLabel( WikibaseItem &$item, $language, $label ) {
		// TODO: Normalize
		$value = $item->setLabel( $language, $label );
		if ( $label !== $value ) {
			$this->getResult()->addValue(
				'item',
				'normalized',
				array( array( 'from' => $label, 'to' => $value ) )
			);
		}
		$this->getResult()->addValue(
			'item',
			'labels',
			array( $language => $value )
		);
		return ;
	}
	
	/**
	 * Sets the description in the item and reports the new value.
	 * This method does not handle a description in multiple languages.
	 * 
	 * @param WikibaseItem $item
	 * @param string $language
	 * @param string $description
	 */
	protected function setItemDescription( WikibaseItem &$item, $language, $description ) {
		// TODO: Normalize
		$value = $item->setDescription( $language, $description );
		if ( $description !== $value ) {
			$this->getResult()->addValue(
				null,
				'normalized',
				array( 'from' => $description, 'to' => $value )
			);
		}
		$this->getResult()->addValue(
			null,
			'descriptions',
			array( $language => $value )
		);
		return ;
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
			'language' => 'Language the description is in',
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
			'API module to set a label and a description for a Wikibase item.'
		);
	}

	/**
	 * Returns a list of all possible errors returned by the module
	 * @return array in the format of array( key, param1, param2, ... ) or array( 'code' => ..., 'info' => ... )
	 */
	public function getPossibleErrors() {
		return array_merge( parent::getPossibleErrors(), array(
			array( 'code' => 'label-or-description', 'info' => wfMsg( 'wikibase-api-label-or-description' ) ),
			array( 'code' => 'label-not-found', 'info' => wfMsg( 'wikibase-api-label-not-found' ) ),
			array( 'code' => 'description-not-found', 'info' => wfMsg( 'wikibase-api-description-not-found' ) ),
			array( 'code' => 'label-found', 'info' => wfMsg( 'wikibase-api-label-found' ) ),
			array( 'code' => 'description-found', 'info' => wfMsg( 'wikibase-api-description-found' ) ),
			array( 'code' => 'not-recognized', 'info' => wfMsg( 'wikibase-api-not-recognized' ) ),
			) );
	}

	/**
	 * Returns usage examples for this module. Return false if no examples are available.
	 * @return bool|string|array
	 */
	protected function getExamples() {
		return array(
			'api.php?action=wbsetlanguageattribute&id=42&language=en&label=Wikimedia'
				=> 'Set the string "Wikimedia" for page with id "42" as a label in English language',
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
