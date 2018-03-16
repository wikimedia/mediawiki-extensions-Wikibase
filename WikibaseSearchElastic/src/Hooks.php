<?php

namespace WikibaseSearchElastic;

use CirrusSearch\Maintenance\AnalysisConfigBuilder;
use CirrusSearch\Profile\SearchProfileService;
use CirrusSearch\Query\FullTextQueryBuilder;
use CirrusSearch\Search\SearchContext;
use RequestContext;
use Wikibase\Repo\WikibaseRepo;

class Hooks {

	public static function onSetupAfterCache() {
		$request = RequestContext::getMain()->getRequest();

		$wikibaseRepo = WikibaseRepo::getDefaultInstance();
		$settings = $wikibaseRepo->getSettings();
		$searchSettings = $settings->getSetting( 'entitySearch' );
		$useCirrus = $request->getVal( 'useCirrus' );
		if ( $useCirrus !== null ) {
			// if we have request one, use it
			$searchSettings['useCirrus'] =
				// This really should be global utility function
				( $useCirrus === 'on' || $useCirrus === 'true' || $useCirrus === 'yes' ||
					$useCirrus === '1' );
			$settings->setSetting( 'entitySearch', $searchSettings );
		}
		if ( $searchSettings['useCirrus'] ) {
			global $wgCirrusSearchExtraIndexSettings;
			// Bump max fields so that labels/descriptions fields fit in.
			$wgCirrusSearchExtraIndexSettings['index.mapping.total_fields.limit'] = 5000;

		}

		return true;
	}

	/**
	 * Add Wikibase-specific ElasticSearch analyzer configurations.
	 * @param array &$config
	 * @param AnalysisConfigBuilder $builder
	 */
	public static function onCirrusSearchAnalysisConfig( &$config, AnalysisConfigBuilder $builder ) {
		static $inHook;
		if ( $inHook ) {
			// Do not call this hook repeatedly, since ConfigBuilder calls AnalysisConfigBuilder
			// FIXME: this is not a very nice hack, but we need it because we want AnalysisConfigBuilder
			// to call the hook, since other extensions may make relevant changes to config.
			// We just don't want to run this specific hook again, but Mediawiki API does not have
			// the means to exclude one hook temporarily.
			return;
		}

		// Analyzer for splitting statements and extracting properties:
		// P31:Q1234 => P31
		$config['analyzer']['extract_wb_property'] = [
			'type' => 'custom',
			'tokenizer' => 'split_wb_statements',
			'filter' => [ 'first_token' ],
		];
		$config['tokenizer']['split_wb_statements'] = [
			'type' => 'pattern',
			'pattern' => StatementsField::STATEMENT_SEPARATOR,
		];
		$config['filter']['first_token'] = [
			'type' => 'limit',
			'max_token_count' => 1
		];

		// Language analyzers for descriptions
		$repo = WikibaseRepo::getDefaultInstance();
		$wbBuilder = new ConfigBuilder( $repo->getTermsLanguages()->getLanguages(),
			$repo->getSettings()->getSetting( 'entitySearch' ),
			$builder
		);
		$inHook = true;
		try {
			$wbBuilder->buildConfig( $config );
		} finally {
			$inHook = false;
		}
	}

	/**
	 * Wikibase-specific rescore builders for CirrusSearch.
	 *
	 * @param array $func Builder parameters
	 * @param SearchContext $context
	 * @param FunctionScoreBuilder|null &$builder Output parameter for score builder.
	 */
	public static function onCirrusSearchScoreBuilder(
		array $func,
		SearchContext $context,
		FunctionScoreBuilder &$builder = null
	) {
		if ( $func['type'] === 'statement_boost' ) {
			$searchSettings = WikibaseRepo::getDefaultInstance()->getSettings()->getSetting( 'entitySearch' );
			$builder = new StatementBoostScoreBuilder(
				$context,
				$func['weight'],
				$searchSettings['statementBoost']
			);
		}
	}

	/**
	 * Register our cirrus profiles.
	 *
	 * @param SearchProfileService $service
	 */
	public static function onCirrusSearchProfileService( SearchProfileService $service ) {
		// register base profiles available on all wikibase installs
		$service->registerFileRepository( SearchProfileService::RESCORE,
			'wikibase_base', __DIR__ . '/config/ElasticSearchRescoreProfiles.php' );
		$service->registerFileRepository( SearchProfileService::RESCORE_FUNCTION_CHAINS,
			'wikibase_base', __DIR__ . '/config/ElasticSearchRescoreFunctions.php' );
		$service->registerFileRepository( EntitySearchElastic::WIKIBASE_PREFIX_QUERY_BUILDER,
			'wikibase_base', __DIR__ . '/config/EntityPrefixSearchProfiles.php' );
		$service->registerFileRepository( SearchProfileService::FT_QUERY_BUILDER,
			'wikibase_base', __DIR__ . '/config/EntitySearchProfiles.php' );

		// register custom profiles provided in the wikibase config
		$settings = WikibaseRepo::getDefaultInstance()->getSettings();
		$entitySearchConfig = $settings->getSetting( 'entitySearch' );
		if ( isset( $entitySearchConfig['rescoreProfiles'] ) ) {
			$service->registerArrayRepository( SearchProfileService::RESCORE,
				'wikibase_config', $entitySearchConfig['rescoreProfiles'] );
		}
		if ( isset( $entitySearchConfig['prefixSearchProfiles'] ) ) {
			$service->registerArrayRepository( EntitySearchElastic::WIKIBASE_PREFIX_QUERY_BUILDER,
				'wikibase_config', $entitySearchConfig['prefixSearchProfiles'] );
		}
		if ( isset( $entitySearchConfig['fulltextSearchProfiles'] ) ) {
			$service->registerArrayRepository( SearchProfileService::FT_QUERY_BUILDER,
				'wikibase_config', $entitySearchConfig['fulltextSearchProfiles'] );
		}

		// Determine the default rescore profile to use for entity autocomplete search
		$defaultRescore = EntitySearchElastic::DEFAULT_RESCORE_PROFILE;
		if ( isset( $entitySearchConfig['defaultPrefixRescoreProfile'] ) ) {
			// If set in config use it
			$defaultRescore = $entitySearchConfig['defaultPrefixRescoreProfile'];
		}
		$service->registerDefaultProfile( SearchProfileService::RESCORE,
			EntitySearchElastic::CONTEXT_WIKIBASE_PREFIX, $defaultRescore );
		// add the possibility to override the profile by setting the URI param cirrusRescoreProfile
		$service->registerUriParamOverride( SearchProfileService::RESCORE,
			EntitySearchElastic::CONTEXT_WIKIBASE_PREFIX, 'cirrusRescoreProfile' );

		// Determine the default query builder profile to use for entity autocomplete search
		$defaultQB = EntitySearchElastic::DEFAULT_QUERY_BUILDER_PROFILE;
		if ( isset( $entitySearchConfig['prefixSearchProfile'] ) ) {
			$defaultQB = $entitySearchConfig['prefixSearchProfile'];
		}
		$service->registerDefaultProfile( EntitySearchElastic::WIKIBASE_PREFIX_QUERY_BUILDER,
			EntitySearchElastic::CONTEXT_WIKIBASE_PREFIX, $defaultQB );
		$service->registerUriParamOverride( EntitySearchElastic::WIKIBASE_PREFIX_QUERY_BUILDER,
			EntitySearchElastic::CONTEXT_WIKIBASE_PREFIX, 'cirrusWBProfile' );

		// Determine query builder profile for fulltext search
		$defaultFQB = EntitySearchElastic::DEFAULT_QUERY_BUILDER_PROFILE;
		if ( isset( $entitySearchConfig['fulltextSearchProfile'] ) ) {
			$defaultFQB = $entitySearchConfig['fulltextSearchProfile'];
		}
		$service->registerDefaultProfile( SearchProfileService::FT_QUERY_BUILDER,
			EntitySearchElastic::CONTEXT_WIKIBASE_FULLTEXT, $defaultFQB );
		$service->registerUriParamOverride( SearchProfileService::FT_QUERY_BUILDER,
			EntitySearchElastic::CONTEXT_WIKIBASE_FULLTEXT, 'cirrusWBProfile' );

		// Determine the default rescore profile to use for fulltext search
		$defaultFTRescore = EntitySearchElastic::DEFAULT_RESCORE_PROFILE;
		if ( isset( $entitySearchConfig['defaultFulltextRescoreProfile'] ) ) {
			// If set in config use it
			$defaultFTRescore = $entitySearchConfig['defaultFulltextRescoreProfile'];
		}
		$service->registerDefaultProfile( SearchProfileService::RESCORE,
			EntitySearchElastic::CONTEXT_WIKIBASE_FULLTEXT, $defaultFTRescore );
		// add the possibility to override the profile by setting the URI param cirrusRescoreProfile
		$service->registerUriParamOverride( SearchProfileService::RESCORE,
			EntitySearchElastic::CONTEXT_WIKIBASE_FULLTEXT, 'cirrusRescoreProfile' );
	}

	/**
	 * @param FullTextQueryBuilder $builder
	 * @param SearchContext $context
	 */
	public static function onCirrusSearchFulltextQueryBuilder(
		FullTextQueryBuilder &$builder,
		SearchContext $context
	) {
		$qbSettings = $context->getConfig()->getProfileService()
			->loadProfile( SearchProfileService::FT_QUERY_BUILDER,
				EntitySearchElastic::CONTEXT_WIKIBASE_FULLTEXT );
		$repo = WikibaseRepo::getDefaultInstance();
		$builder = new $qbSettings['builder_class'](
			$builder,
			$repo->getSettings()->getSetting( 'entitySearch' ),
			$qbSettings['settings'],
			$repo->getEntityNamespaceLookup(),
			$repo->getLanguageFallbackChainFactory(),
			$repo->getEntityIdParser(),
			$repo->getUserLanguage()->getCode()
		);
	}

}
