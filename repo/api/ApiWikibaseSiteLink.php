<?php

/**
 * API module to associate a page on a site with a Wikibase item or remove an already made such association.
 * Requires API write mode to be enabled.
 *
 * TODO: putting add and remove in one module did not make sense after all, so pull appart again...
 *
 * @since 0.1
 *
 * @file ApiWikibaseSiteLink.php
 * @ingroup Wikibase
 * @ingroup API
 *
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 * @author John Erling Blad < jeblad@gmail.com >
 */
class ApiWikibaseSiteLink extends ApiWikibaseModifyItem {

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
		return $item->addSiteLink( $params['site'], $params['title'], !$params['noupdate'] );
	}

	public function getPossibleErrors() {
		return array_merge( parent::getPossibleErrors(), array(
			array( 'code' => 'link-exists', 'info' => 'An article on the specified wiki is already linked' ),
		) );
	}

	public function getAllowedParams() {
		return array_merge( parent::getAllowedParams(), array(
			'badge' => array(
				ApiBase::PARAM_TYPE => 'string', // TODO: list? integer? how will badges be represented?
			),
			'summary' => array(
				ApiBase::PARAM_TYPE => 'string',
				ApiBase::PARAM_DFLT => __CLASS__, // TODO
			),
			'noupdate' => array(
				ApiBase::PARAM_TYPE => 'boolean',
				ApiBase::PARAM_DFLT => false,
			),
			'change' => array(
				ApiBase::PARAM_TYPE => array( 'add', 'remove' ),
				ApiBase::PARAM_REQUIRED => true,
			),
		) );
	}

	public function getParamDescription() {
		return array_merge( parent::getAllowedParams(), array(
			'id' => 'The ID of the item to associate the page with',
			'badge' => 'Badge to give to the page, ie "good" or "featured"',
			'summary' => 'Summary for the edit',
			'noupdate' => 'Indicates that if a link to the specified site already exists, it should not be updated to use the provided page',
			'change' => 'Inidcates if you are adding or removing the link',
		) );
	}

	public function getDescription() {
		return array(
			'API module to associate an artcile on a wiki with a Wikibase item or remove an already made such association.'
		);
	}

	protected function getExamples() {
		return array(
			'api.php?action=wbsitelink&id=42&site=en&title=Wikimedia'
			=> 'Set title "Wikimedia" for English page with id "42"',
			'api.php?action=wbsitelink&id=42&site=en&title=Wikimedia&summary=World domination will be mine soon!'
			=> 'Set title "Wikimedia" for English page with id "42" with an edit summary',
			'api.php?action=wbsitelink&id=42&site=en&title=Wikimedia&badge='
			=> 'Set title "Wikimedia" for English page with id "42" and with a badge',
			'api.php?action=wbsitelink&id=42&site=en&title=Wikimedia'
			=> 'Set title "Wikimedia" for English page with id "42"',
		);
	}

	public function getHelpUrls() {
		return 'https://www.mediawiki.org/wiki/Extension:Wikidata/API#wbsitelink';
	}


	public function getVersion() {
		return __CLASS__ . ': $Id$';
	}

}
