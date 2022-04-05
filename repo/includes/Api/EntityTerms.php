<?php

declare( strict_types = 1 );

namespace Wikibase\Repo\Api;

use ApiQuery;
use ApiQueryBase;
use ApiResult;
use InvalidArgumentException;
use Title;
use Wikibase\DataAccess\AliasTermBuffer;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Services\Term\TermBuffer;
use Wikibase\Lib\ContentLanguages;
use Wikibase\Lib\Store\EntityIdLookup;
use Wikibase\Lib\TermIndexEntry;
use Wikimedia\ParamValidator\ParamValidator;

/**
 * Provides wikibase terms (labels, descriptions, aliases) for entity pages.
 * For example, if data item Q61 has the label "Washington" and the description
 * "capital city of the US", calling entityterms with titles=Q61 would include
 * that label and description in the response.
 *
 * @note This closely mirrors the Client pageterms API, except for the services injected.
 *
 * @license GPL-2.0-or-later
 */
class EntityTerms extends ApiQueryBase {

	/** @var AliasTermBuffer */
	private $aliasTermBuffer;

	/**
	 * @todo Use LabelDescriptionLookup for labels/descriptions, so we can apply language fallback.
	 * @var TermBuffer
	 */
	private $termBuffer;

	/**
	 * @var EntityIdLookup
	 */
	private $idLookup;

	/**
	 * @var ContentLanguages
	 */
	private $termsLanguages;

	public function __construct(
		ApiQuery $query,
		string $moduleName,
		AliasTermBuffer $aliasTermBuffer,
		EntityIdLookup $idLookup,
		TermBuffer $termBuffer,
		ContentLanguages $termsLanguages
	) {
		parent::__construct( $query, $moduleName, 'wbet' );
		$this->aliasTermBuffer = $aliasTermBuffer;
		$this->termBuffer = $termBuffer;
		$this->idLookup = $idLookup;
		$this->termsLanguages = $termsLanguages;
	}

	public function execute(): void {
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
		$termTypes = $params['terms'] ?? TermIndexEntry::$validTermTypes;
		$languageCode = $params['language'] === 'uselang' ? $this->getLanguage()->getCode() : $params['language'];

		$pagesToEntityIds = $this->getEntityIdsForTitles( $titles, $continue );
		$entityToPageMap = $this->getEntityToPageMap( $pagesToEntityIds );

		$terms = $this->getTermsOfEntities( $pagesToEntityIds, $termTypes, $languageCode );

		$termGroups = $this->groupTermsByPageAndType( $entityToPageMap, $terms );

		$this->addTermsToResult( $pagesToEntityIds, $termGroups );
	}

	/**
	 * @param EntityId[] $entityIds
	 * @param string[] $termTypes
	 * @param string $languageCode
	 *
	 * @return TermIndexEntry[]
	 */
	private function getTermsOfEntities( array $entityIds, array $termTypes, string $languageCode ): array {
		$this->termBuffer->prefetchTerms( $entityIds, $termTypes, [ $languageCode ] );

		$terms = [];
		foreach ( $entityIds as $entityId ) {
			foreach ( $termTypes as $termType ) {
				if ( $termType !== 'alias' ) {
					$termText = $this->termBuffer->getPrefetchedTerm( $entityId, $termType, $languageCode );
					if ( $termText !== false && $termText !== null ) {
						$terms[] = new TermIndexEntry( [
							TermIndexEntry::FIELD_ENTITY => $entityId,
							TermIndexEntry::FIELD_TYPE => $termType,
							TermIndexEntry::FIELD_LANGUAGE => $languageCode,
							TermIndexEntry::FIELD_TEXT => $termText,
						] );
					}
				} else {
					$termTexts = $this->aliasTermBuffer->getPrefetchedAliases( $entityId, $languageCode ) ?: [];
					foreach ( $termTexts as $termText ) {
						$terms[] = new TermIndexEntry( [
							TermIndexEntry::FIELD_ENTITY => $entityId,
							TermIndexEntry::FIELD_TYPE => $termType,
							TermIndexEntry::FIELD_LANGUAGE => $languageCode,
							TermIndexEntry::FIELD_TEXT => $termText,
						] );
					}
				}

			}
		}

		return $terms;
	}

	/**
	 * @param Title[] $titles
	 * @param int|null $continue
	 *
	 * @return array
	 */
	private function getEntityIdsForTitles( array $titles, $continue = 0 ): array {
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
	private function getEntityToPageMap( array $entityIds ): array {
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
	private function groupTermsByPageAndType( array $entityToPageMap, array $terms ): array {
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
	private function addTermsToResult( array $pagesToEntityIds, array $termGroups ): void {
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
	private function addTermsForPage( ApiResult $result, int $pageId, array $termsByType ): bool {
		ApiResult::setIndexedTagNameRecursive( $termsByType, 'term' );

		$fit = $result->addValue( [ 'query', 'pages', $pageId ], 'entityterms', $termsByType );

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
	public function getCacheMode( $params ): string {
		return 'public';
	}

	/**
	 * @inheritDoc
	 */
	protected function getAllowedParams(): array {
		return [
			'continue' => [
				self::PARAM_HELP_MSG => 'api-help-param-continue',
				ParamValidator::PARAM_TYPE => 'integer',
			],
			'language' => [
				self::PARAM_HELP_MSG => 'apihelp-query+entityterms-param-language',
				ParamValidator::PARAM_DEFAULT => 'uselang',
				ParamValidator::PARAM_TYPE => array_merge( [ 'uselang' ], $this->termsLanguages->getLanguages() ),
			],
			'terms' => [
				ParamValidator::PARAM_TYPE => TermIndexEntry::$validTermTypes,
				ParamValidator::PARAM_DEFAULT => implode( '|',  TermIndexEntry::$validTermTypes ),
				ParamValidator::PARAM_ISMULTI => true,
				self::PARAM_HELP_MSG => 'apihelp-query+entityterms-param-terms',
			],
		];
	}

	/**
	 * @inheritDoc
	 */
	protected function getExamplesMessages(): array {
		return [
			'action=query&prop=entityterms&titles=Q84'
				=> 'apihelp-query+entityterms-example-item',
		];
	}

}
