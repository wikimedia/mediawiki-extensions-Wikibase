<?php

/**
 * API module to associate a page on a site with a Wikibase item or remove an already made such association.
 * Requires API write mode to be enabled.
 *
 * @since 0.1
 *
 * @file ApiWikibaseLinkSite.php
 * @ingroup Wikibase
 * @ingroup API
 *
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 * @author John Erling Blad < jeblad@gmail.com >
 */
class ApiWikibaseLinkSite extends ApiWikibaseModifyItem {

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
		if ( !isset($params['link']) ) {
			$params['link'] = $params['item'];
		}
		if ( $params['link'] === 'remove') {
			return $item->removeLinkSite( $params['linksite'], $params['linktitle'] );
		}
		else {
			return $item->addSiteLink( $params['linksite'], $params['linktitle'], $params['link'] );
		}
	}

	public function getPossibleErrors() {
		return array_merge( parent::getPossibleErrors(), array(
			// is this in use?
			array( 'code' => 'link-exists', 'info' => 'An article on the specified wiki is already linked' ),
		) );
	}

	public function getAllowedParams() {
		return array_merge( parent::getAllowedParams(), array(
			'badge' => array(
				ApiBase::PARAM_TYPE => 'string', // TODO: list? integer? how will badges be represented?
			),
			'linksite' => array(
				ApiBase::PARAM_TYPE => WikibaseUtils::getSiteIdentifiers(),
				ApiBase::PARAM_REQUIRED => true,
			),
			'linktitle' => array(
				ApiBase::PARAM_TYPE => 'string',
				ApiBase::PARAM_REQUIRED => true,
			),
			'link' => array(
				ApiBase::PARAM_TYPE => array( 'add', 'update', 'set', 'remove' ),
				ApiBase::PARAM_REQUIRED => true,
			),
		) );
	}

	public function getParamDescription() {
		return array_merge( parent::getParamDescription(), array(
			'linksite' => 'The identifier of the site on which the article to link resides',
			'linktitle' => 'The title of the article to link',
			'badge' => 'Badge to give to the page, ie "good" or "featured"',
			'link' => array('Indicates if you are adding or removing the link, and in case of adding, if it can or should already exist',
				'The argument "item" works as an alias for "item".',
			),
		) );
	}

	public function getDescription() {
		return array(
			'API module to associate an artcile on a wiki with a Wikibase item or remove an already made such association.'
		);
	}

	protected function getExamples() {
		return array(
			'api.php?action=wblinksite&id=42&site=en&title=Wikimedia'
			=> 'Set title "Wikimedia" for English page with id "42"',
			'api.php?action=wblinksite&id=42&site=en&title=Wikimedia&summary=World%20domination%20will%20be%20mine%20soon!'
			=> 'Set title "Wikimedia" for English page with id "42" with an edit summary',
			'api.php?action=wblinksite&id=42&site=en&title=Wikimedia&badge='
			=> 'Set title "Wikimedia" for English page with id "42" and with a badge',
			'api.php?action=wblinksite&id=42&site=en&title=Wikimedia'
			=> 'Set title "Wikimedia" for English page with id "42"',
		);
	}

	public function getHelpUrls() {
		return 'https://www.mediawiki.org/wiki/Extension:Wikibase/API#wblinksite';
	}


	public function getVersion() {
		return __CLASS__ . ': $Id$';
	}

}
