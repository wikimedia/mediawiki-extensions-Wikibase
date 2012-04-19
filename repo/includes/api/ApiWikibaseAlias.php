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
class ApiWikibaseSetAlias extends ApiWikibaseModifyItem {

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
		/*
		 * // TODO: Set as remove if there is no input value
		if ( !isset($params['alias']) || ) {
			$params['alias'] = $params['item'];
		}
		*/
		if ( !isset($params['alias']) ) {
			$params['alias'] = $params['item'];
		}
		// TODO: this should really set up an alternate access method
		/*
		if ( $params['change'] === 'remove') {
			// TODO: Check if this is going to be defined
			return $item->removeAlias( $params['label'], $params['language'] );
		}
		else {
			// TODO: Check if this is going to be defined
			return $item->addAlias( $params['label'], $params['language'], $params['alias'] );
		}
		*/
		return false;
	}

	public function getPossibleErrors() {
		return array_merge( parent::getPossibleErrors(), array(
			// is this in use?
			array( 'code' => 'alias-exists', 'info' => 'An alias is already defined' ),
		) );
	}

	public function getAllowedParams() {
		return array_merge( parent::getAllowedParams(), array(
			'label' => array(
				ApiBase::PARAM_TYPE => 'string',
				ApiBase::PARAM_REQUIRED => true,
			),
			'alias' => array(
				ApiBase::PARAM_TYPE => array( 'add', 'update', 'set', 'remove' ),
				ApiBase::PARAM_REQUIRED => true,
			),
		) );
	}

	public function getParamDescription() {
		return array_merge( parent::getParamDescription(), array(
			'label' => 'The label to use as an alternate name for the page in Wikidata',
			'alias' => array('Indicates if you are adding or removing the link, and in case of adding, if it can or should already exist',
				'The argument "item" works as an alias for "item".',
			),
		) );
	}

	public function getDescription() {
		return array(
			'API module to associate an alias with a Wikibase item or remove an already made such association.'
		);
	}

	protected function getExamples() {
		return array(
			'api.php?action=wbalias&id=42&language=en&label=Wikimedia'
			=> 'Set title "Wikimedia" for English page with id "42"',
			'api.php?action=wbalias&id=42&language=en&label=Wikimedia&summary=World%20domination%20will%20be%20mine%20soon!'
			=> 'Set title "Wikimedia" for English page with id "42" with an edit summary',
			'api.php?action=wbalias&id=42&language=en&label=Wikimedia'
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
