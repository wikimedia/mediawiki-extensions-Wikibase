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
		switch ($params['item']) {
			case 'update':
				if ( isset($params['label']) ) {
					if (!$num_labels) {
						$this->dieUsage( wfMsg( 'wikibase-api-label-not-found' ), 'label-not-found' );
					}
					$item->setLabel( $params['language'], $params['label'] );
				}
				if (isset($params['description'])) {
					if (!$num_descriptions) {
						$this->dieUsage( wfMsg( 'wikibase-api-description-not-found' ), 'description-not-found' );
					}
					$item->setDescription( $params['language'], $params['description'] );
				}
				break;
			case 'add':
				if (isset($params['label'])) {
					if ($num_labels) {
						$this->dieUsage( wfMsg( 'wikibase-api-label-found' ), 'label-found' );
					}
					$item->setLabel( $params['language'], $params['label'] );
				}
				if (isset($params['description'])) {
					if ($num_descriptions) {
						$this->dieUsage( wfMsg( 'wikibase-api-description-found' ), 'description-found' );
					}
					$item->setDescription( $params['language'], $params['description'] );
				}
				break;
			case 'set':
				if (isset($params['label'])) {
					$item->setLabel( $params['language'], $params['label'] );
				}
				if (isset($params['description'])) {
					$item->setDescription( $params['language'], $params['description'] );
				}
				break;
			default:
				$this->dieUsage( wfMsg( 'wikibase-api-not-recognized' ), 'not-recognized' );
		}
		return true;
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
			'api.php?action=wbsetlanguageattribute&id=42&language=en&label=Wikimedia'
				=> 'Set the string "Wikimedia" for page with id "42" as a label in English language',
			'api.php?action=wbsetlanguageattribute&id=42&language=en&description=An%20encyclopedia%20that%20everyone%20can%20edit'
				=> 'Set the string "An encyclopedia that everyone can edit" for page with id "42" as a decription in English language',
			'api.php?action=wbsetlanguageattribute&id=42&language=en&label=Wikimedia&description=An%20encyclopedia%20that%20everyone%20can%20edit'
				=> 'Set the string "Wikimedia" for page with id "42" as a label, and the string "An encyclopedia that everyone can edit" as a decription in English language',
		);
	}
	
   	public function getHelpUrls() {
		return 'https://www.mediawiki.org/wiki/Extension:Wikidata/API#wbsetlanguageattribute';
	}
	

	public function getVersion() {
		return __CLASS__ . ': $Id$';
	}

}
