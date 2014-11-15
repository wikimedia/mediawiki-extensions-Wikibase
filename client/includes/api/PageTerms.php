<?php

namespace Wikibase;

use ApiBase;
use ApiQuery;
use ApiQueryBase;
use ApiResult;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Entity\Item;

/**
 * Provides wikibase terms for local pages.
 *
 * @since 0.5
 *
 * @licence GNU GPL v2+
 * @author Daniel Kinzler
 */
class ApiQueryPageProps extends ApiQueryBase {

	/**
	 * @var TermIndex
	 */
	private $termIndex;

	public function __construct( ApiQuery $query, $moduleName ) {
		parent::__construct( $query, $moduleName, 'pt' );
	}

	public function execute() {
		$languageCode = $this->getLanguage()->getCode();
		$params = $this->extractRequestParams();

		# Only operate on existing pages
		$pages = $this->getPageSet()->getGoodTitles();
		if ( !count( $pages ) ) {
			# Nothing to do
			return;
		}

		$pagesToEntityIds = $this->getEntityIdsForPages( $pages );

		//FIXME: crashes hard got on non-items!
		//TODO: use an (extended) LabelLookup, so we can apply language fallback here.
		$terms = $this->termIndex->getTermsOfEntities( $pagesToEntityIds, Item::ENTITY_TYPE, $languageCode );

		if ( $params['prop'] ) {
			$terms = $this->filterTerms( $terms, $params['termtype'] );
		}

		$termGroups = $this->makeTermGroups( $pagesToEntityIds, $terms );

		$this->addTermsToResult( $pagesToEntityIds, $terms );
	}

	/**
	 * @param EntityId[] $pagesToEntityIds
	 * @param Term[] $terms
	 *
	 * @return array
	 */
	private function makeTermGroups( array $pagesToEntityIds, array $terms ) {
		$termsPerPage = array();

		foreach ( $terms as $term ) {

			$termsPerPage[]
		}

		foreach ( $pagesToEntityIds as $pageId => $entityId ) {
			$groups[]
		}

		return $groups;
	}

	/**
	 * @param array[] $termGroups
	 */
	private function addTermsToResult( array $pagesToEntityIds, array $termGroups ) {
		$result = $this->getResult();

		foreach ( $pagesToEntityIds as $currentPage => $entityId ) {
			$group = $termGroups[$currentPage];

			if ( !$this->addPageTerms( $result, $currentPage, $group ) ) {
				break;
			}
		}
	}

	/**
	 * Add page term to an ApiResult, adding a continue
	 * parameter if it doesn't fit.
	 *
	 * @param ApiResult $result
	 * @param int $pageId
	 * @param string[] $terms
	 * @return bool True if it fits in the result
	 */
	private function addPageTerms( $result, $pageId, $terms ) {
		$fit = $result->addValue( array( 'query', 'pages', $pageId ), 'terms', $terms );

		if ( !$fit ) {
			$this->setContinueEnumParameter( 'continue', $pageId );
		}

		return $fit;
	}

	public function getCacheMode( $params ) {
		return 'public';
	}

	public function getAllowedParams() {
		return array(
			'continue' => array(
				ApiBase::PARAM_HELP_MSG => 'api-help-param-continue',
			),
			'prop' => array(
				ApiBase::PARAM_ISMULTI => true,
			),
		);
	}

	protected function getExamplesMessages() {
		return array(
			'action=query&prop=pageprops&titles=Category:Foo'
			=> 'apihelp-query+pageprops-example-simple',
		);
	}

	public function getHelpUrls() {
		return 'https://www.mediawiki.org/wiki/API:Properties#pageprops_.2F_pp';
	}
}
