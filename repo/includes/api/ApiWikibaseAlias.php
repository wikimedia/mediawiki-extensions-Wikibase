<?php

/**
 * API module to associate an alias with a label from a Wikibase item or remove an already made such association.
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
class ApiWikibaseAlias extends ApiWikibaseModifyItem {

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
		if ( $params['change'] === 'remove') {
			return $item->removeAlias( $params['alias'], $params['language'] );
		}
		else {
			return $item->addAlias( $params['alias'], $params['language'], $params['change'] );
		}
	}

	public function getPossibleErrors() {
		return array_merge( parent::getPossibleErrors(), array(
			// is this in use?
			array( 'code' => 'alias-exists', 'info' => 'An aalias is already defined' ),
		) );
	}

	public function getAllowedParams() {
		return array_merge( parent::getAllowedParams(), array(
			'badge' => array(
				ApiBase::PARAM_TYPE => 'string', // TODO: list? integer? how will badges be represented?
			),
			'alias' => array(
				ApiBase::PARAM_TYPE => 'string',
				ApiBase::PARAM_REQUIRED => true,
			),
			'change' => array(
				ApiBase::PARAM_TYPE => array( 'add', 'update', 'set', 'remove' ),
				ApiBase::PARAM_REQUIRED => true,
			),
		) );
	}

	public function getParamDescription() {
		return array_merge( parent::getParamDescription(), array(
			'alias' => 'The identifier of the site on which the article to link resides',
			'change' => 'Indicates if you are adding or removing the link, and in case of adding, if it can or should already exist',
		) );
	}

	public function getDescription() {
		return array(
		//TODO: update these
			'API module to associate an artcile on a wiki with a Wikibase item or remove an already made such association.'
		);
	}

	protected function getExamples() {
		return array(
		//TODO: update these
			'api.php?action=wbalias&id=42&site=en&title=Wikimedia'
			=> 'Set title "Wikimedia" for English page with id "42"',
			'api.php?action=wbalias&id=42&site=en&title=Wikimedia&summary=World%20domination%20will%20be%20mine%20soon!'
			=> 'Set title "Wikimedia" for English page with id "42" with an edit summary',
			'api.php?action=wbalias&id=42&site=en&title=Wikimedia&badge='
			=> 'Set title "Wikimedia" for English page with id "42" and with a badge',
			'api.php?action=wbalias&id=42&site=en&title=Wikimedia'
			=> 'Set title "Wikimedia" for English page with id "42"',
		);
	}

	public function getHelpUrls() {
		return 'https://www.mediawiki.org/wiki/Extension:Wikidata/API#wbalias';
	}


	public function getVersion() {
		return __CLASS__ . ': $Id$';
	}

}
