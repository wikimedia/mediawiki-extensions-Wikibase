<?php

/**
 * Minimal set of classes necessary to fulfill needs of parts of Wikibase relying on
 * the CirrusSearch extension.
 * @codingStandardsIgnoreFile
 */

namespace {
	class CirrusSearch {
	}
}

namespace CirrusSearch {
	class CirrusDebugOptions {
		public static function fromRequest( \WebRequest $request ) {
		}
	}

	class Connection {
	}

	class ElasticsearchIntermediary {
		/**
		 * @param mixed|null
		 * @return Status
		 */
		public function success( $result = null ) {
		}

		/**
		 * @return Status
		 */
		public function failure( \Elastica\Exception\ExceptionInterface $exception = null ) {
		}

		/**
		 * @param string $description
		 * @param string $queryType
		 * @param string[] $extra
		 * @return RequestLog
		 */
		protected function startNewLog( $description, $queryType, array $extra = [] ) {
		}
	}

	class SearchConfig {
		const INDEX_BASE_NAME = 'CirrusSearchIndexBaseName';

		/**
		 * @return bool true
		 */
		public function isLocalWiki() {
		}

		/**
		 * @return Profile\SearchProfileService
		 */
		public function getProfileService() {
		}
	}

	class SearchRequestLog {
	}

	class Searcher {
		const HIGHLIGHT_PRE_MARKER = '';
		const HIGHLIGHT_PRE = '<span class="searchmatch">';
		const HIGHLIGHT_POST_MARKER = '';
		const HIGHLIGHT_POST = '</span>';

		/**
		 * @return Search\SearchContext
		 */
		public function getSearchContext() {
		}

		/**
		 * @param Query $query
		 * @return Query
		 */
		public function applyDebugOptionsToQuery( Query $query ) {
		}
	}

	interface WarningCollector {
		/**
		 * @param string $message
		 * @param string|null $param1
		 * @param string|null $param2
		 * @param string|null $param3
		 */
		function addWarning( $message, $param1 = null, $param2 = null, $param3 = null );
	}
}

namespace CirrusSearch\Extra\Query {
	class TermFreq {
		/**
		 * @param string $field
		 * @param string $term
		 * @param string $operator
		 * @param int $number
		 */
		public function __construct( $field, $term, $operator, $number ) {
		}
	}
}

namespace CirrusSearch\Maintenance {
	class AnalysisConfigBuilder {
		/**
		 * @param array &$config
		 * @param string[] $languages
		 * @param string[] $analyzers
		 */
		public function buildLanguageConfigs( array &$config, array $languages, array $analyzers ) {
		}
	}
}

namespace CirrusSearch\Parser\AST {
	class KeywordFeatureNode {

		/**
		 * @return array|null
		 */
		public function getParsedValue() {
		}
	}
}

namespace CirrusSearch\Profile {
	class SearchProfileService {
		const RESCORE = 'rescore';
		const RESCORE_FUNCTION_CHAINS = 'rescore_function_chains';
		const FT_QUERY_BUILDER = 'ft_query_builder';

		/**
		 * @param string $type
		 * @param string $profileContext
		 * @param string $profileName
		 */
		public function registerDefaultProfile( $type, $profileContext, $profileName ) {
		}

		public function registerRepository( SearchProfileRepository $repository ) {
		}

		/**
		 * @param string $type
		 * @param string $name
		 * @param string $phpFile
		 */
		public function registerFileRepository( $type, $name, $phpFile ) {
		}

		/**
		 * @param string $repoType
		 * @param string $repoName
		 * @param array $profiles
		 */
		public function registerArrayRepository( $repoType, $repoName, array $profiles ) {
		}

		/**
		 * @param string $type
		 * @param string|string[] $profileContext one or multiple contexts
		 * @param string $uriParam
		 */
		public function registerUriParamOverride( $type, $profileContext, $uriParam ) {
		}
	}

	interface SearchProfileRepository {
	}

	class SearchProfileRepositoryTransformer {
	}

	class ArrayProfileRepository {
		/**
		 * @param string $repoType
		 * @param string $repoName
		 * @param array $profiles
		 * @return ArrayProfileRepository
		 */
		public static function fromArray( $repoType, $repoName, array $profiles ) {
		}

		/**
		 * @param string $repoType
		 * @param string $repoName
		 * @param string $phpFile
		 * @return ArrayProfileRepository
		 */
		public static function fromFile( $repoType, $repoName, $phpFile ) {
		}
	}
}

namespace CirrusSearch\Query {
	interface FilterQueryFeature {
	}

	interface FullTextQueryBuilder {
	}

	class SimpleKeywordFeature {

	}
}

namespace CirrusSearch\Query\Builder {
	interface QueryBuildingContext {
	}
}

namespace CirrusSearch\Search {
	class CirrusIndexField {
		const NOOP_HINT = 'noop';
	}

	class Filters {
		/**
		 * @param AbstractQuery[] $queries
		 * @param bool $matchAll
		 * @return AbstractQuery|null
		 */
		public static function booleanOr( array $queries, $matchAll = true ) {
		}
	}

	class TextIndexField {
		const POSITION_INCREMENT_GAP = 10;

		/**
		 * @param SearchConfig $config
		 * @param string $field
		 * @param string|null $analyzer
		 * @return string
		 */
		public static function getSimilarity( SearchConfig $config, $field, $analyzer = null ) {
		}
	}
	class Result {
	}

	class ResultSet {
	}

	interface ResultsType {
		/**
		 * @return false|string|array corresponding to Elasticsearch source filtering syntax
		 */
		public function getSourceFiltering();
	}

	abstract class BaseResultsType implements ResultsType {
	}

	class SearchContext {
		/**
		 * @param string $message
		 * @param string|null $param1
		 * @param string|null $param2
		 * @param string|null $param3
		 */
		public function addWarning( $message, $param1 = null,  $param2 = null, $param3 = null ) {
		}

		/**
		 * @return bool
		 */
		public function areResultsPossible() {
		}

		/**
		 * @param bool $possible
		 */
		public function setResultsPossible( $possible ) {
		}

		/**
		 * @return \CirrusSearch\SearchConfig
		 */
		public function getConfig() {
		}

		/**
		 * @return int[]|null
		 */
		public function getNamespaces() {
		}

		/**
		 * @param int[]|null $namespaces
		 */
		public function setNamespaces( $namespaces ) {
		}

		/**
		 * @return bool
		 */
		public function isSpecialKeywordUsed() {
		}

		/**
		 * @param string $feature
		 * @param int|null $weight
		 */
		public function addSyntaxUsed( $feature, $weight = null ) {
		}

		public function setResultsType( ResultsType $resultsType ) {
		}

		/*
		 * @param string $profileContext
		 */
		public function setProfileContext( $profileContext ) {
		}

		public function setMainQuery( AbstractQuery $query ) {
		}

		/**
		 * @param string $term
		 */
		public function setOriginalSearchTerm( $term ) {
		}
	}
}
