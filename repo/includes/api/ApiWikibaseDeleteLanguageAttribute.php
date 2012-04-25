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
		$num_labels = count($item->getLabels($languages));
		$num_descriptions = count($item->getDescriptions($languages));
		return true;
	}

	public function getAllowedParams() {
		return array_merge( parent::getAllowedParams(), array(
			'language' => array(
				ApiBase::PARAM_TYPE => WikibaseUtils::getLanguageCodes(),
				ApiBase::PARAM_REQUIRED => true,
			),
			'attribute' => array(
				ApiBase::PARAM_TYPE => 'string',
				ApiBase::PARAM_REQUIRED => true,
				ApiBase::PARAM_ISMULTI => true,
			),
		) );
	}

	public function getParamDescription() {
		return array_merge( parent::getParamDescription(), array(
			'language' => 'Language the description is in',
			'attribute' => array('The type of attribute to delete',
					'One of ("label", "description")'
		) );
	}

	public function getDescription() {
		return array(
			'API module to set a label and a description for a Wikibase item.'
		);
	}

	public function getPossibleErrors() {
		return array_merge( parent::getPossibleErrors(), array(
			array( 'code' => 'label-or-description', 'info' => 'Use either or both of label and/or description, but not noen of them' ),
			array( 'code' => 'label-not-found', 'info' => 'Can not find any previous label in the item' ),
			array( 'code' => 'description-not-found', 'info' => 'Can not find any previous description in the item' ),
			array( 'code' => 'label-found', 'info' => 'Found a previous label in the item' ),
			array( 'code' => 'description-found', 'info' => 'Found a previous description in the item' ),
			array( 'code' => 'not-recognized', 'info' => 'Directive is not recognized' ),
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
