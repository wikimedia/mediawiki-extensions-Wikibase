<?php

namespace Wikibase;

use ApiBase;
use ApiQuery;
use ApiQueryBase;
use ApiResult;
use Title;
use Wikibase\Client\Store\EntityIdLookup;
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
class PageTerms extends ApiQueryBase {

	/**
	 * @var TermIndex
	 */
	private $termIndex;

	/**
	 * @var EntityIdLookup
	 */
	private $idLookup;

	/**
	 * @param TermIndex $termIndex
	 * @param EntityIdLookup $idLookup
	 * @param ApiQuery $query
	 * @param string $moduleName
	 */
	public function __construct(
		TermIndex $termIndex,
		EntityIdLookup $idLookup,
		ApiQuery $query,
		$moduleName
	) {
		parent::__construct( $query, $moduleName, 'pt' );
		$this->termIndex = $termIndex;
		$this->idLookup = $idLookup;
	}

	public function execute() {
		$languageCode = $this->getLanguage()->getCode();
		$params = $this->extractRequestParams();

		# Only operate on existing pages
		$titles = $this->getPageSet()->getGoodTitles();
		if ( !count( $titles ) ) {
			# Nothing to do
			return;
		}

		// NOTE: continuation relies on $titles being sorted by page ID.
		$pagesToEntityIds = $this->getEntityIdsForTitles( $titles, (int)$params['continue'] );
		$entityToPageMap = $this->getEntityToPageMap( $pagesToEntityIds );

		//FIXME: crashes hard got on non-items!
		//TODO: use an (extended) LabelLookup, so we can apply language fallback here.
		$terms = $this->termIndex->getTermsOfEntities( $pagesToEntityIds, Item::ENTITY_TYPE, $languageCode );

		if ( $params['terms'] ) {
			$terms = $this->filterTerms( $terms, $params['terms'] );
		}

		$termGroups = $this->makeTermGroups( $entityToPageMap, $terms );

		$this->addTermsToResult( $pagesToEntityIds, $termGroups );
	}

	/**
	 * @param Title[] $titles
	 * @param int|null $continue
	 *
	 * @return array
	 */
	private function getEntityIdsForTitles( array $titles, $continue = 0 ) {
		$entityIds = $this->idLookup->getEntityIds( $titles );

		// Re-sort, so the order of page IDs matches the order in which $titles
		// were given. This is essential for paging to work properly.
		// This also skips all page IDs up to $continue.
		$sortedEntityId = array();
		foreach ( $titles as $pid => $title ) {
			if ( $pid >= $continue ) {
				$sortedEntityId[$pid] = $entityIds[$pid];
			}
		}

		return $sortedEntityId;
	}

	/**
	 * @param EntityId[] $entityIds
	 *
	 * @return int[]
	 */
	private function getEntityToPageMap( array $entityIds ) {
		$entityIdsStrings = array_map(
			function ( EntityId $entityId ) {
				return $entityId->getSerialization();
			},
			$entityIds
		);

		return array_flip( $entityIdsStrings );
	}

	/**
	 * @param Term[] $terms
	 * @param string[] $types
	 *
	 * @return Term[]
	 */
	private function filterTerms( array $terms, array $types = null ) {
		if ( !$types ) {
			return $terms;
		}

		$types = array_flip( $types );

		return array_filter(
			$terms,
			function( Term $term ) use ( $types ) {
				$key = $term->getType();
				return isset( $types[$key] );
			}
		);
	}

	/**
	 * @param int[] $entityToPageMap
	 * @param Term[] $terms
	 *
	 * @return array[][] An associative array, mapping pageId + entity type to a list of strings.
	 */
	private function makeTermGroups( array $entityToPageMap, array $terms ) {
		$termsPerPage = array();

		foreach ( $terms as $term ) {
			// Since we construct $terms and $entityToPageMap from the same set of page IDs,
			// the entry $entityToPageMap[$key] should really always be set.
			$type = $term->getType();
			$key = $term->getEntityId()->getSerialization();
			$pageId = $entityToPageMap[$key];

			// for each page ID, record a list of terms for each term type.
			$termsPerPage[$pageId][$type][] = $term->getText();
		}

		return $termsPerPage;
	}

	/**
	 * @param EntityId[] $pagesToEntityIds
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
	 * @param array[] $terms
	 *
	 * @return bool True if it fits in the result
	 */
	private function addPageTerms( ApiResult $result, $pageId, array $terms ) {
		$fit = $result->addValue( array( 'query', 'pages', $pageId ), 'terms', $terms );

		if ( !$fit ) {
			$this->setContinueEnumParameter( 'continue', $pageId );
		}

		return $fit;
	}

	public function getAllowedParams() {
		return array(
			'continue' => array(
				ApiBase::PARAM_HELP_MSG => 'api-help-param-continue',
			),
			'terms' => array(
				ApiBase::PARAM_ISMULTI => true,
			),
		);
	}

	protected function getExamplesMessages() {
		return array(
			'action=query&prop=pageterms&titles=London'
			=> 'apihelp-query+pageterms-example-simple',
		);
	}

	public function getHelpUrls() {
		return 'https://www.mediawiki.org/wiki/Extension:Wikibase/API:Properties#pageterms_.2F_pt';
	}

}
