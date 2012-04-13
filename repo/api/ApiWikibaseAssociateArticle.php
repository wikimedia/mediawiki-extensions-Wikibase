<?php

/**
 * API module to associate a page on a site with a Wikibase item.
 * Requires API write mode to be enabled.
 *
 * @since 0.1
 *
 * @file ApiWikibaseAssociateArticle.php
 * @ingroup Wikibase
 * @ingroup API
 *
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
class ApiWikibaseAssociateArticle extends ApiBase {

	public function __construct( $main, $action ) {
		parent::__construct( $main, $action );
	}

	/**
	 * Main method. Does the actual work and sets the result.
	 *
	 * @since 0.1
	 */
	public function execute() {
		$params = $this->extractRequestParams();

		$success = false;

		$page = new WikiPage( Title::newFromText( 'Data:Q' . $params['id'] ) ); // TODO
		$content = $page->getContent();

		if ( $content->getModelName() === CONTENT_MODEL_WIKIBASE ) {
			/* WikibaseItem */ $item = $content->getItem();
			$success = $item->addSiteLink( $params['site'], $params['title'] );

			if ( $success ) {
				$content->setItem( $item );
				$page->doEditContent( $content, $params['summary'] );
			}
			else {
				// TODO: error message
			}
		}
		else {
			// TODO: error message
		}

		$this->getResult()->addValue(
			null,
			'success',
			$success
		);
	}

	public function needsToken() {
		return !WBSettings::get( 'apiInDebug' );
	}

	public function mustBePosted() {
		return !WBSettings::get( 'apiInDebug' );
	}

	public function getAllowedParams() {
		return array(
			'id' => array(
				ApiBase::PARAM_TYPE => 'integer',
				ApiBase::PARAM_REQUIRED => true,
			),
			'site' => array(
				ApiBase::PARAM_TYPE => WikibaseUtils::getSiteIdentifiers(),
				ApiBase::PARAM_REQUIRED => true,
			),
			'title' => array(
				ApiBase::PARAM_TYPE => 'string',
				ApiBase::PARAM_REQUIRED => true,
			),
			'badge' => array(
				ApiBase::PARAM_TYPE => 'string', // TODO: list? integer? how will badges be represented?
			),
			'summary' => array(
				ApiBase::PARAM_TYPE => 'string',
				ApiBase::PARAM_DFLT => __CLASS__, // TODO
			),
		);
	}

	public function getParamDescription() {
		return array(
			'id' => 'The ID of the item to associate the page with',
			'site' => 'An identifier for the site on which the page resides',
			'title' => 'Title of the page to associate',
			'badge' => 'Badge to give to the page, ie "good" or "featured"',
			'summary' => 'Summary for the edit',
		);
	}

	public function getDescription() {
		return array(
			'API module to associate an artcile on a wiki with a Wikibase item.'
		);
	}

	public function getPossibleErrors() {
		return array_merge( parent::getPossibleErrors(), array(
		) );
	}

	protected function getExamples() {
		return array(
			'api.php?action=wbassociatearticle&id=42&site=en&title=Wikimedia'
				=> 'Set title "Wikimedia" for English page with id "42"',
			'api.php?action=wbassociatearticle&id=42&site=en&title=Wikimedia&summary=World domination will be mine soon!'
			=> 'Set title "Wikimedia" for English page with id "42" with an edit summary',
			'api.php?action=wbassociatearticle&id=42&site=en&title=Wikimedia&badge='
				=> 'Set title "Wikimedia" for English page with id "42" and with a badge',
		);
	}
	
	public function getHelpUrls() {
		return 'https://www.mediawiki.org/wiki/Extension:Wikidata/API#wbassociatearticle';
	}
	

	public function getVersion() {
		return __CLASS__ . ': $Id$';
	}

}
