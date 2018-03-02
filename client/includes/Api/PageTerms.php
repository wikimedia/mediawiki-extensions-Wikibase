<?php

namespace Wikibase\Client\Api;

use ApiQuery;
use ApiQueryBase;
use ApiResult;
use InvalidArgumentException;
use Title;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\Store\EntityIdLookup;
use Wikibase\TermIndex;
use Wikibase\TermIndexEntry;
use Wikibase\WikibaseSettings;

/**
 * Provides wikibase terms (labels, descriptions, aliases, etc.) for local pages.
 * For example, if a data item has the label "Washington" and the description "capital
 * city of the US", and has a sitelink to the local page called "Washington DC", calling
 * pageterms with titles=Washington_DC would include that label and description
 * in the response.
 *
 * @license GPL-2.0-or-later
 * @author Daniel Kinzler
 */
class PageTerms extends ApiQueryBase {

	/**
	 * @todo: Use LabelDescriptionLookup for labels/descriptions, so we can apply language fallback.
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

		$continue = $params['continue'];

		$pagesToEntityIds = $this->getEntityIdsForTitles( $titles, $continue );
		$entityToPageMap = $this->getEntityToPageMap( $pagesToEntityIds );

		$terms = $this->getTermsOfEntities( $pagesToEntityIds, $params['terms'], [ $languageCode ] );

		$termGroups = $this->groupTermsByPageAndType( $entityToPageMap, $terms );

		$this->addTermsToResult( $pagesToEntityIds, $termGroups );
	}

	/**
	 * @param EntityId[] $pagesToEntityIds
	 *
	 * @return array[]
	 */
	private function splitPageEntityMapByType( array $pagesToEntityIds ) {
		$groups = [];

		foreach ( $pagesToEntityIds as $pageId => $entityId ) {
			$type = $entityId->getEntityType();
			$groups[$type][$pageId] = $entityId;
		}

		return $groups;
	}

	/**
	 * @param EntityId[] $entityIds
	 * @param string[]|null $termTypes
	 * @param string[]|null $languageCodes
	 *
	 * @return TermIndexEntry[]
	 */
	private function getTermsOfEntities( array $entityIds, array $termTypes = null, array $languageCodes = null ) {
		$entityIdGroups = $this->splitPageEntityMapByType( $entityIds );
		$terms = [];

		foreach ( $entityIdGroups as $entityIds ) {
			$terms = array_merge(
				$terms,
				$this->termIndex->getTermsOfEntities( $entityIds, $termTypes, $languageCodes )
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
		$sortedEntityId = [];
		foreach ( $titles as $pid => $title ) {
			if ( $pid >= $continue && isset( $entityIds[$pid] ) ) {
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
			function( EntityId $id ) {
				return $id->getSerialization();
			},
			$entityIds
		);

		return array_flip( $entityIdsStrings );
	}

	/**
	 * @param int[] $entityToPageMap
	 * @param TermIndexEntry[] $terms
	 *
	 * @return array[] An associative array, mapping pageId + entity type to a list of strings.
	 */
	private function groupTermsByPageAndType( array $entityToPageMap, array $terms ) {
		$termsPerPage = [];

		foreach ( $terms as $term ) {
			// Since we construct $terms and $entityToPageMap from the same set of page IDs,
			// the entry $entityToPageMap[$key] should really always be set.
			$type = $term->getTermType();
			$key = $term->getEntityId()->getSerialization();
			$pageId = $entityToPageMap[$key];
			$text = $term->getText();

			if ( $text !== null ) {
				// For each page ID, record a list of terms for each term type.
				$termsPerPage[$pageId][$type][] = $text;
			} else {
				// $text should never be null, but let's be vigilant.
				wfWarn( __METHOD__ . ': Encountered null text in TermIndexEntry object!' );
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
	private function addTermsForPage( ApiResult $result, $pageId, array $termsByType ) {
		ApiResult::setIndexedTagNameRecursive( $termsByType, 'term' );

		$fit = $result->addValue( [ 'query', 'pages', $pageId ], 'terms', $termsByType );

		if ( !$fit ) {
			$this->setContinueEnumParameter( 'continue', $pageId );
		}

		return $fit;
	}

	/**
	 * @see ApiQueryBase::getCacheMode
	 *
	 * @param array $params
	 * @return string
	 */
	public function getCacheMode( $params ) {
		return 'public';
	}

	/**
	 * @see ApiBase::getAllowedParams
	 */
	protected function getAllowedParams() {
		return [
			'continue' => [
				self::PARAM_HELP_MSG => 'api-help-param-continue',
				self::PARAM_TYPE => 'integer',
			],
			'terms' => [
				// XXX: Ought to get this list from Wikibase\TermIndexEntry, its setType() also hardcodes it.
				self::PARAM_TYPE => [
					TermIndexEntry::TYPE_ALIAS,
					TermIndexEntry::TYPE_DESCRIPTION,
					TermIndexEntry::TYPE_LABEL
				],
				self::PARAM_ISMULTI => true,
				self::PARAM_HELP_MSG => 'apihelp-query+pageterms-param-terms',
			],
		];
	}

	/**
	 * @see ApiBase::getExamplesMessages
	 */
	protected function getExamplesMessages() {
		if ( WikibaseSettings::isRepoEnabled() ) {
			return [
				'action=query&prop=pageterms&titles=Q84'
					=> 'apihelp-query+pageterms-example-item',
			];
		} else {
			return [
				'action=query&prop=pageterms&titles=London'
					=> 'apihelp-query+pageterms-example-simple',
				'action=query&prop=pageterms&titles=London&wbptterms=label|alias&uselang=en'
					=> 'apihelp-query+pageterms-example-label-en',
			];
		}
	}

}
