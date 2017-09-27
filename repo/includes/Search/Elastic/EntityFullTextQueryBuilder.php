<?php
namespace Wikibase\Repo\Search\Elastic;

use CirrusSearch\Query\FullTextQueryBuilder;
use CirrusSearch\Search\SearchContext;
use CirrusSearch\SearchConfig;
use Elastica\Query\BoolQuery;
use Elastica\Query\DisMax;
use Elastica\Query\Match;
use Elastica\Query\Term;
use RuntimeException;
use Wikibase\Repo\WikibaseRepo;

/**
 * Builder for entity fulltext queries
 * @package Wikibase\Repo\Search\Elastic
 */
class EntityFullTextQueryBuilder implements FullTextQueryBuilder {

	/**
	 * @var array
	 */
	private $settings;
	/**
	 * This is regular fulltext builder which we'll use
	 * if we can't use the main one.
	 * @var FullTextQueryBuilder
	 */
	private $delegate;
	/**
	 * @var WikibaseRepo
	 */
	private $repo;
	/**
	 * Repository 'entitySearch' settings
	 * @var array
	 */
	private $searchSettings;

	/**
	 * @param SearchConfig $config
	 * @param array $feature List of feature keyword handlers
	 * @param array $settings Settings from EntitySearchProfiles.php
	 */
	public function __construct( SearchConfig $config, array $feature, array $settings ) {
		// Unfortunately, we can't get repo in the ctor since CirrusSearch controls construction
		$this->repo = WikibaseRepo::getDefaultInstance();
		$repoSettings = $this->repo->getSettings();

		$this->searchSettings = $repoSettings->getSetting( 'entitySearch' );
		if ( empty( $this->searchSettings['originalSearchProfile'] ) ) {
			// This should have been set by hooks when we set up the profile!
			throw new RuntimeException( "Somehow originalSearchProfile is not set" );
		}
		$originalProfile = $config->getElement( 'CirrusSearchFullTextQueryBuilderProfiles',
				$this->searchSettings['originalSearchProfile'] );
		if ( !$originalProfile ) {
			throw new RuntimeException( 'Cannot find originalSearchProfile profile' );
		}
		$this->delegate = new $originalProfile['builder_class']( $config, $feature,
			$originalProfile['settings'] );
		if ( !( $this->delegate instanceof FullTextQueryBuilder ) ) {
			throw new RuntimeException( "Bad builder class configured: {$originalProfile['builder_class']}" );
		}
		$this->settings = $settings;
	}

	/**
	 * Search articles with provided term.
	 *
	 * @param SearchContext $searchContext
	 * @param string $term term to search
	 * @param bool $showSuggestion should this search suggest alternative
	 * searches that might be better?
	 */
	public function build( SearchContext $searchContext, $term, $showSuggestion ) {
		$lookup = $this->repo->getEntityNamespaceLookup();

		$entityNs = [];
		$articleNs = [];
		foreach ( $searchContext->getNamespaces() as $ns ) {
			if ( $lookup->isEntityNamespace( (int)$ns ) ) {
				$entityNs[] = $ns;
			} else {
				$articleNs[] = $ns;
			}
		}
		if ( empty( $entityNs ) ) {
			// searching only article namespaces - use parent
			$this->delegate->build( $searchContext, $term, $showSuggestion );
			return;
		}
		// FIXME: eventually we should deal with combined namespaces, probably running
		// a union of entity query for entity namespaces and delegate query for article namespaces
		$this->buildEntitySearch( $this->repo, $searchContext, $term );
	}

	/**
	 * Set up entity search query
	 * @param WikibaseRepo $repo
	 * @param SearchContext $searchContext
	 * @param $term
	 */
	protected function buildEntitySearch( WikibaseRepo $repo, SearchContext $searchContext, $term ) {
		$searchContext->addSyntaxUsed( 'entity_full_text', 10 );

		$settings = $repo->getSettings();
		$searchSettings = $settings->getSetting( 'entitySearch' );

		$lang = $repo->getUserLanguage();
		$languageCode = $lang->getCode();
		$languageChainFactory = $repo->getLanguageFallbackChainFactory();

		$dismax = new DisMax();
		$dismax->setTieBreaker( 0 );

		$profile = $this->settings;
		// $fields is collecting all the fields for dismax query to be used in
		// scoring match
		$fields = [
			[ "labels.{$languageCode}.near_match", $profile['lang-exact'] ],
			[ "labels.{$languageCode}.near_match_folded", $profile['lang-folded'] ],
		];

		if( empty( $this->searchSettings['useStemming'][$languageCode]['query']) ) {
			$fieldsTokenized = [
				[ "labels.{$languageCode}.plain", $profile['lang-partial'] ],
				[ "descriptions.{$languageCode}.plain", $profile['lang-partial'] ],
			];
		} else {
			$fieldsTokenized = [
				[ "descriptions.{$languageCode}", $profile['lang-partial'] ],
				[ "labels.{$languageCode}.plain", $profile['lang-partial'] ],
				[ "descriptions.{$languageCode}.plain", $profile['any'] ],
			];
		}


		$langChain = $languageChainFactory->newFromLanguageCode( $languageCode );
		$searchLanguageCodes = $langChain->getFetchLanguageCodes();

		$discount = $profile['fallback-discount'];
		foreach ( $searchLanguageCodes as $fallbackCode ) {
			if ( $fallbackCode === $languageCode ) {
				continue;
			}

			$weight = $profile['fallback-exact'] * $discount;
			$fields[] = [ "labels.{$fallbackCode}.near_match", $weight ];

			$weight = $profile['fallback-folded'] * $discount;
			$fields[] = [ "labels.{$fallbackCode}.near_match_folded", $weight ];

			$weight = $profile['fallback-partial'] * $discount;
			$fieldsTokenized[] = [ "labels.{$fallbackCode}.plain", $weight ];
			$fieldsTokenized[] = [ "descriptions.{$fallbackCode}.plain", $weight ];

			$discount *= $profile['fallback-discount'];
		}

		foreach ( $fields as $field ) {
			$dismax->addQuery( EntitySearchUtils::makeConstScoreQuery( $field[0], $field[1], $term ) );
		}

		$labelsQuery = new BoolQuery();
		$labelsQuery->addFilter( $this->buildSimpleAllFilter( $term ) );
		$labelsQuery->addMust( $dismax );

		$titleMatch = new Term( [ 'title.keyword' => $term ] );

		$dismaxTokenised = new DisMax();
		$dismaxTokenised->setTieBreaker( 1 );
		foreach ( $fieldsTokenized as $field ) {
			$dismaxTokenised->addQuery( EntitySearchUtils::makeConstScoreQuery( $field[0], $field[1], $term ) );
		}

		$tokenizedQuery = new BoolQuery();
		$tokenizedQuery->addShould( EntitySearchUtils::makeConstScoreQuery( 'all', $profile['any'],
			$term ) );
		$tokenizedQuery->addShould( EntitySearchUtils::makeConstScoreQuery( 'all.plain', $profile['any'],
			$term ) );
		$tokenizedQuery->addShould( $dismaxTokenised );

		// Main query
		$query = new BoolQuery();
		$query->setParam( 'disable_coord', true );

		// Match either labels or exact match to title
		$query->addShould( $labelsQuery );
		$query->addShould( $tokenizedQuery );
		$query->addShould( $titleMatch );
		$query->setMinimumShouldMatch( 1 );

		$searchContext->setMainQuery( $query );
		$searchContext->setRescoreProfile( EntitySearchUtils::getRescoreProfile( $searchSettings,
			'fulltextSearchProfile' ) );
		// setup results type
		$searchContext->setResultsType( new EntityResultType( $languageCode, $langChain ) );
	}

	public function buildDegraded( SearchContext $searchContext ) {
		$this->delegate->buildDegraded( $searchContext );
	}

	/**
	 * Builds a simple filter on all and all.plain when all terms must match
	 *
	 * @param string $query
	 * @return \Elastica\Query\AbstractQuery
	 */
	private function buildSimpleAllFilter( $query ) {
		$filter = new BoolQuery();
		// FIXME: We can't use solely the stem field here
		// - Depending on languages it may lack stopwords,
		// A dedicated field used for filtering would be nice
		foreach ( [ 'all', 'all.plain' ] as $field ) {
			$m = new Match();
			$m->setFieldQuery( $field, $query );
			$m->setFieldOperator( $field, 'AND' );
			$filter->addShould( $m );
		}
		return $filter;
	}

}
