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
	 * Make sure the required parameters are provided and that they are valid.
	 * This overrides the base class
	 *
	 * @since 0.1
	 *
	 * @param array $params
	 */
	protected function validateParameters( array $params ) {
		parent::validateParameters( $params );

		if ( ( isset( $params['add'] ) || isset( $params['remove'] ) ) XOR isset( $params['set'] ) ) {
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
			$item->setAliases( $params['set'] );
		}

		if ( isset( $params['remove'] ) ) {
			$item->removeAliases( $params['remove'] );
		}

		if ( isset( $params['add'] ) ) {
			$item->addAliases( $params['add'] );
		}

		return true;
	}

	public function getPossibleErrors() {
		return array_merge( parent::getPossibleErrors(), array(
			array( 'code' => 'alias-incomplete', 'info' => 'Can not find a definition of the alias for the item' ),
			array( 'code' => 'alias-not-found', 'info' => 'Can not find any previous alias in the item' ),
			array( 'code' => 'alias-found', 'info' => 'Found a previous alias in the item' ),
			array( 'code' => 'not-recognized', 'info' => 'Directive is not recognized' ),
		) );
	}

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

	public function getParamDescription() {
		return array_merge( parent::getParamDescription(), array(
			'add' => 'List of aliases to add',
			'remove' => 'List of aliases to remove',
			'set' => 'A list of aliases that will replace the current list',
			'language' => 'The language of which to set the aliases',
		) );
	}

	public function getDescription() {
		return array(
			'API module to set the aliases for a Wikibase item.'
		);
	}

	protected function getExamples() {
		return array(
		);
	}

	public function getHelpUrls() {
		return 'https://www.mediawiki.org/wiki/Extension:Wikibase/API#wbsetaliases';
	}


	public function getVersion() {
		return __CLASS__ . ': $Id$';
	}

}
