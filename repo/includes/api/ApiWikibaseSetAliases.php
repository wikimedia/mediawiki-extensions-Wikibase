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
		) );
	}

	public function getParamDescription() {
		return array_merge( parent::getParamDescription(), array(
		) );
	}

	public function getDescription() {
		return array(
			'API module to associate an alias with a Wikibase item or remove an already made such association.'
		);
	}

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

	public function getHelpUrls() {
		return 'https://www.mediawiki.org/wiki/Extension:Wikibase/API#wbsetalias';
	}


	public function getVersion() {
		return __CLASS__ . ': $Id$';
	}

}
