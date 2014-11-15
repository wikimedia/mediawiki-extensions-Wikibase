<?php

namespace Wikibase;

use ApiBase;
use ApiQuery;
use ApiQueryBase;
use ApiResult;
use InvalidArgumentException;
use Title;
use Wikibase\Client\Store\EntityIdLookup;
use Wikibase\DataModel\Entity\EntityId;

/**
 * Provides wikibase terms (labels, descriptions, aliases, etc.) for local pages.
 * For example, if a data item has the label "Washington" and the description "capitol
 * city of the US", and has a sitelink to the local page called "Washington DC", calling
 * pageterms with titles=Waschington_DC would include that label and description
 * in the response.
 *
 * @since 0.5
 *
 * @licence GNU GPL v2+
 * @author Daniel Kinzler
 */
class PageTerms extends ApiQueryBase {

	/**
	 * @todo: use an (extended) LabelLookup, so we can apply language fallback.
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
		parent::__construct( $query, $moduleName, 'wbpt' );
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
		ksort( $titles );

		$continue = 0;
		if ( $params['continue'] !== null ) {
			$continue = (int)$params['continue'];
			$this->dieContinueUsageIf( $params['continue'] !== (string)$continue );
		}

		$pagesToEntityIds = $this->getEntityIdsForTitles( $titles, $continue );
		$entityToPageMap = $this->getEntityToPageMap( $pagesToEntityIds );

		$terms = $this->getTermsOfEntities( $pagesToEntityIds, $languageCode );

		if ( $params['terms'] ) {
			$terms = $this->filterTerms( $terms, $params['terms'] );
		}

		$termGroups = $this->groupTermsByPageAndType( $entityToPageMap, $terms );

		$this->addTermsToResult( $pagesToEntityIds, $termGroups );
	}

	/**
	 * @param EntityId[] $pagesToEntityIds
	 *
	 * @return array[]
	 */
	private function splitPageEntitiyMapByType( array $pagesToEntityIds ) {
		$groups = array();

		foreach ( $pagesToEntityIds as $pageId => $entityId ) {
			$type = $entityId->getEntityType();
			$groups[$type][$pageId] = $entityId;
		}

		return $groups;
	}

	/**
	 * @param EntityID[] $entityIds
	 * @param string $languageCode
	 *
	 * @return Term[]
	 */
	private function getTermsOfEntities( array $entityIds, $languageCode ) {
		$entityIdGroups = $this->splitPageEntitiyMapByType( $entityIds );
		$terms = array();

		foreach ( $entityIdGroups as $type => $entityIds ) {
			$terms = array_merge(
				$terms,
				$this->termIndex->getTermsOfEntities( $entityIds, $type, $languageCode )
			);
		}

		return $terms;
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
	private function groupTermsByPageAndType( array $entityToPageMap, array $terms ) {
		$termsPerPage = array();

		foreach ( $terms as $term ) {
			// Since we construct $terms and $entityToPageMap from the same set of page IDs,
			// the entry $entityToPageMap[$key] should really always be set.
			$type = $term->getType();
			$key = $term->getEntityId()->getSerialization();
			$pageId = $entityToPageMap[$key];
			$text = $term->getText();

			if ( $text !== null ) {
				// For each page ID, record a list of terms for each term type.
				$termsPerPage[$pageId][$type][] = $text;
			} else {
				// $text should never be null, but let's be vigilant.
				wfWarn( __METHOD__ . ': Encountered null text in Term object!' );
			}
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
			if ( !isset( $termGroups[$currentPage] ) ) {
				// No entity for page, or no terms for entity.
				continue;
			}

			$group = $termGroups[$currentPage];

			if ( !$this->addTermsForPage( $result, $currentPage, $group ) ) {
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
	 * @param array[] $termsByType
	 *
	 * @throws InvalidArgumentException
	 * @return bool True if it fits in the result
	 */
	private function addTermsForPage( ApiResult $result, $pageId, $termsByType ) {
		if ( !is_array( $termsByType ) ) {
			throw new InvalidArgumentException( '$termsByType must be an array, got ' . gettype( $termsByType ) );
		}

		$result->setIndexedTagName_recursive( $termsByType, 'term' );

		$fit = $result->addValue( array( 'query', 'pages', $pageId ), 'terms', $termsByType );

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
				ApiBase::PARAM_HELP_MSG => 'apihelp-query+pageterms-param-terms',
			),
		);
	}

	protected function getExamplesMessages() {
		return array(
			'action=query&prop=pageterms&titles=London'
				=> 'apihelp-query+pageterms-example-simple',
			'action=query&prop=pageterms&titles=London&wbptterms=label|alias&uselang=en'
				=> 'apihelp-query+pageterms-example-label-en',
		);
	}

	public function getHelpUrls() {
		return 'https://www.mediawiki.org/wiki/Extension:Wikibase_Client/API';
	}

}
