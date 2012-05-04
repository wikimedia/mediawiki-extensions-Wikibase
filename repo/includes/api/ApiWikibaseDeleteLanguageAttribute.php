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
	 * Check the rights
	 * 
	 * @param $title Title
	 * @param $user User doing the action
	 * @param $token String
	 * @return array
	 */
	protected function getPermissionsErrorInternal( $title, $user, array $params, $mod=null, $op=null ) {
		return parent::getPermissionsError( $title, $user, 'lang-attr', 'delete' );
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
		foreach ($params['attribute'] as $attr) {
			switch ($attr) {
				case 'label':
					if ( !isset($labels[$params['language']]) ) {
						$this->dieUsage( wfMsg( 'wikibase-api-label-not-found' ), 'label-not-found' );
					}
					$item->removeLabel( $params['language'] );
					$success = $success || true;
					break;
				case 'description':
					if ( !isset($descriptions[$params['language']]) ) {
						$this->dieUsage( wfMsg( 'wikibase-api-description-not-found' ), 'description-not-found' );
					}
					$item->removeDescription( $params['language'] );
					$success = $success || true;
					break;
				default:
					$this->dieUsage( wfMsg( 'wikibase-api-not-recognized' ), 'not-recognized' );
			}
		}
		return $success;
	}

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

	public function getParamDescription() {
		return array_merge( parent::getParamDescription(), array(
			'language' => 'Language the description is in',
			'attribute' => array('The type of attribute to delete',
					'One of ("label", "description")')
		) );
	}

	public function getDescription() {
		return array(
			'API module to set a label and a description for a Wikibase item.'
		);
	}

	public function getPossibleErrors() {
		return array_merge( parent::getPossibleErrors(), array(
			array( 'code' => 'label-not-found', 'info' => wfMsg( 'wikibase-api-label-not-found' ) ),
			array( 'code' => 'description-not-found', 'info' =>  wfMsg( 'wikibase-api-description-not-found' ) ),
			array( 'code' => 'not-recognized', 'info' => wfMsg( 'wikibase-api-not-recognized' ) ),
			) );
	}

	protected function getExamples() {
		return array(
			'api.php?action=wbdeletelanguageattribute&id=42&language=en&attribute=label'
				=> 'Delete whatever is stored in the attribute "label" in english language.',
		);
	}
	
   	public function getHelpUrls() {
		return 'https://www.mediawiki.org/wiki/Extension:Wikibase/API#wbdeletelanguageattribute';
	}
	

	public function getVersion() {
		return __CLASS__ . ': $Id$';
	}

}
