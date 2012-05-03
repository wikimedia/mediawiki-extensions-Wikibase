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
	 * Check the rights
	 * 
	 * @param $title Title
	 * @param $user User doing the action
	 * @param $token String
	 * @return array
	 */
	protected function getPermissionsErrorInternal( $title, $user, array $params, $mod=null, $op=null ) {
		return parent::getPermissionsError( $title, $user, 'lang-attr', $params['item'] );
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
					$this->setLabel( $item, $params['language'], $params['label'] );
				}
				if ( isset($params['description']) ) {
					if ( !isset($descriptions[$params['language']]) ) {
						$this->dieUsage( wfMsg( 'wikibase-api-description-not-found' ), 'description-not-found' );
					}
					$this->setDescription( $item, $params['language'], $params['description'] );
				}
				$success = true;
				break;
			case 'add':
				if ( isset($params['label']) ) {
					if ( isset($labels[$params['language']]) ) {
						$this->dieUsage( wfMsg( 'wikibase-api-label-found' ), 'label-found' );
					}
					$this->setLabel( $item, $params['language'], $params['label'] );
				}
				if ( isset($params['description']) ) {
					if ( isset($descriptions[$params['language']]) ) {
						$this->dieUsage( wfMsg( 'wikibase-api-description-found' ), 'description-found' );
					}
					$this->setDescription( $item, $params['language'], $params['description'] );
				}
				$success = true;
				break;
			case 'set':
				if (isset($params['label'])) {
					$this->setLabel( $item, $params['language'], $params['label'] );
				}
				if (isset($params['description'])) {
					$this->setDescription( $item, $params['language'], $params['description'] );
				}
				$success = true;
				break;
			default:
				$this->dieUsage( wfMsg( 'wikibase-api-not-recognized' ), 'not-recognized' );
		}
		return $success;
	}
	
	protected function setLabel( WikibaseItem &$item, $language, $label ) {
		// TODO: Normalize
		$item->setLabel( $language, $label );
		$this->getResult()->addValue(
			null,
			'labels',
			array( $language => $label )
		);
		return ;
	}
	
	protected function setDescription( WikibaseItem &$item, $language, $description ) {
		// TODO: Normalize
		$item->setDescription( $language, $description );
		$this->getResult()->addValue(
			null,
			'descriptions',
			array( $language => $description )
		);
		return ;
	}

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

	public function getParamDescription() {
		return array_merge( parent::getParamDescription(), array(
			'language' => 'Language the description is in',
			'label' => 'The value to set for the label',
			'description' => 'The value to set for the description',
		) );
	}

	public function getDescription() {
		return array(
			'API module to set a label and a description for a Wikibase item.'
		);
	}

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
	
   	public function getHelpUrls() {
		return 'https://www.mediawiki.org/wiki/Extension:Wikibase/API#wbsetlanguageattribute';
	}
	

	public function getVersion() {
		return __CLASS__ . ': $Id$';
	}

}
