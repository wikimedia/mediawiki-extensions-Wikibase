<?php declare( strict_types=1 );

use MediaWiki\MediaWikiServices;
use Wikibase\Repo\Domains\Reuse\Application\UseCases\BatchGetItemDescriptions\BatchGetItemDescriptions;
use Wikibase\Repo\Domains\Reuse\Application\UseCases\BatchGetItemLabels\BatchGetItemLabels;
use Wikibase\Repo\Domains\Reuse\Application\UseCases\BatchGetItemLabelsWithLanguageFallback\BatchGetItemLabelsWithLanguageFallback;
use Wikibase\Repo\Domains\Reuse\Application\UseCases\BatchGetItems\BatchGetItems;
use Wikibase\Repo\Domains\Reuse\Application\UseCases\BatchGetPropertyLabels\BatchGetPropertyLabels;
use Wikibase\Repo\Domains\Reuse\Application\UseCases\BatchGetPropertyLabelsWithLanguageFallback\BatchGetPropertyLabelsWithLanguageFallback;
use Wikibase\Repo\Domains\Reuse\Application\UseCases\FacetedItemSearch\FacetedItemSearch;
use Wikibase\Repo\Domains\Reuse\Application\UseCases\FacetedItemSearch\FacetedItemSearchValidator;
use Wikibase\Repo\Domains\Reuse\Application\UseCases\LookUpItemByExternalId\LookUpItemByExternalId;
use Wikibase\Repo\Domains\Reuse\Application\UseCases\LookUpItemByExternalId\LookUpItemByExternalIdValidator;
use Wikibase\Repo\Domains\Reuse\Application\UseCases\LookUpItemBySitelink\LookUpItemBySitelink;
use Wikibase\Repo\Domains\Reuse\Domain\Services\FacetedItemSearchEngine;
use Wikibase\Repo\Domains\Reuse\Domain\Services\ItemByExternalIdLookup;
use Wikibase\Repo\Domains\Reuse\Domain\Services\ItemLabelsWithLanguageFallbackBatchRetriever;
use Wikibase\Repo\Domains\Reuse\Domain\Services\LanguageFallbackLabelSelector;
use Wikibase\Repo\Domains\Reuse\Domain\Services\PropertyLabelsWithLanguageFallbackBatchRetriever;
use Wikibase\Repo\Domains\Reuse\Domain\Services\StatementReadModelConverter;
use Wikibase\Repo\Domains\Reuse\Infrastructure\DataAccess\EntityLookupItemsBatchRetriever;
use Wikibase\Repo\Domains\Reuse\Infrastructure\DataAccess\EntityRevisionLookupItemRedirectResolver;
use Wikibase\Repo\Domains\Reuse\Infrastructure\DataAccess\LanguageFallbackChainFactoryFallbackLanguagesProvider;
use Wikibase\Repo\Domains\Reuse\Infrastructure\DataAccess\PrefetchingTermLookupBatchLabelsDescriptionsRetriever;
use Wikibase\Repo\Domains\Reuse\Infrastructure\DataAccess\SiteLinkLookupItemBySitelinkLookup;
use Wikibase\Repo\Domains\Reuse\Infrastructure\GraphQL\GraphQLErrorLogger;
use Wikibase\Repo\Domains\Reuse\Infrastructure\GraphQL\GraphQLFieldCollector;
use Wikibase\Repo\Domains\Reuse\Infrastructure\GraphQL\GraphQLService;
use Wikibase\Repo\Domains\Reuse\Infrastructure\GraphQL\GraphQLTracking;
use Wikibase\Repo\Domains\Reuse\Infrastructure\GraphQL\Resolvers\ItemByExternalIdResolver;
use Wikibase\Repo\Domains\Reuse\Infrastructure\GraphQL\Resolvers\ItemBySitelinkResolver;
use Wikibase\Repo\Domains\Reuse\Infrastructure\GraphQL\Resolvers\ItemDescriptionsResolver;
use Wikibase\Repo\Domains\Reuse\Infrastructure\GraphQL\Resolvers\ItemLabelsResolver;
use Wikibase\Repo\Domains\Reuse\Infrastructure\GraphQL\Resolvers\ItemLabelsWithLanguageFallbackResolver;
use Wikibase\Repo\Domains\Reuse\Infrastructure\GraphQL\Resolvers\ItemResolver;
use Wikibase\Repo\Domains\Reuse\Infrastructure\GraphQL\Resolvers\PropertyLabelsResolver;
use Wikibase\Repo\Domains\Reuse\Infrastructure\GraphQL\Resolvers\PropertyLabelsWithLanguageFallbackResolver;
use Wikibase\Repo\Domains\Reuse\Infrastructure\GraphQL\Resolvers\SearchItemsResolver;
use Wikibase\Repo\Domains\Reuse\Infrastructure\GraphQL\Schema\Schema;
use Wikibase\Repo\Domains\Reuse\Infrastructure\GraphQL\Schema\Types;
use Wikibase\Repo\Domains\Reuse\Infrastructure\Search\CirrusSearchFacetedSearchEngine;
use Wikibase\Repo\Domains\Reuse\Infrastructure\Search\SearchEngineItemByExternalIdLookup;
use Wikibase\Repo\Domains\Reuse\WbReuse;
use Wikibase\Repo\WikibaseRepo;

/** @phpcs-require-sorted-array */
return [
	'WbReuse.FacetedItemSearchEngine' => function( MediaWikiServices $services ): FacetedItemSearchEngine {
		return new CirrusSearchFacetedSearchEngine(
			$services->getSearchEngineFactory(),
			WikibaseRepo::getEntityNamespaceLookup()
		);
	},
	'WbReuse.GraphQLSchema' => function( MediaWikiServices $services ): Schema {
		$dataTypeLookup = WikibaseRepo::getPropertyDataTypeLookup( $services );
		$itemResolver = new ItemResolver(
			new BatchGetItems( new EntityLookupItemsBatchRetriever(
				WikibaseRepo::getEntityLookup( $services ),
				$services->getSiteLookup(),
				new StatementReadModelConverter(
					WikibaseRepo::getStatementGuidParser( $services ),
					$dataTypeLookup
				)
			) )
		);
		return new Schema(
			$itemResolver,
			new SearchItemsResolver(
				new FacetedItemSearch(
					new FacetedItemSearchValidator(
						$dataTypeLookup,
						WikibaseRepo::getDataTypeDefinitions( $services )->getValueTypes(),
					),
					WbReuse::getFacetedItemSearchEngine( $services ),
				),
				$itemResolver
			),
			new ItemBySitelinkResolver(
				new LookUpItemBySitelink(
					new SiteLinkLookupItemBySitelinkLookup(
						WikibaseRepo::getStore()->newSiteLinkStore()
					)
				),
				$itemResolver
			),
			new ItemByExternalIdResolver(
				new LookUpItemByExternalId(
					new LookUpItemByExternalIdValidator( WikibaseRepo::getPropertyDataTypeLookup( $services ) ),
					WbReuse::getItemByExternalIdLookup( $services )
				),
				$itemResolver
			),
			WbReuse::getGraphQLTypes( $services ),
		);
	},
	'WbReuse.GraphQLService' => function( MediaWikiServices $services ): GraphQLService {
		return new GraphQLService(
			WbReuse::getGraphQLSchema( $services ),
			$services->getMainConfig(),
			new GraphQLErrorLogger( WikibaseRepo::getLogger( $services ) ),
			WbReuse::getGraphQLTracking( $services ),
		);
	},
	'WbReuse.GraphQLTracking' => function( MediaWikiServices $services ): GraphQLTracking {
		return new GraphQLTracking( WbReuse::getGraphQLSchema( $services ), $services->getStatsFactory(), new GraphQLFieldCollector() );
	},
	'WbReuse.GraphQLTypes' => function( MediaWikiServices $services ): Types {
		return new Types(
			WikibaseRepo::getTermsLanguages( $services )->getLanguages(),
			WikibaseRepo::getSiteLinkGlobalIdentifiersProvider( $services ),
			WbReuse::getPropertyLabelsResolver( $services ),
			WbReuse::getPropertyLabelsWithLanguageFallbackResolver( $services ),
			WikibaseRepo::getDataTypeDefinitions( $services ),
			WbReuse::getItemDescriptionsResolver( $services ),
			WbReuse::getItemLabelsResolver( $services ),
			WbReuse::getItemLabelsWithLanguageFallbackResolver( $services ),
			WikibaseRepo::getPropertyInfoLookup( $services ),
			WikibaseRepo::getSettings( $services ),
			WbReuse::getLanguageFallbackLabelSelector( $services )
		);
	},
	'WbReuse.ItemByExternalIdLookup' => function( MediaWikiServices $services ): ItemByExternalIdLookup {
		return new SearchEngineItemByExternalIdLookup(
			$services->getSearchEngineFactory(),
			WikibaseRepo::getEntityNamespaceLookup( $services )
		);
	},
	'WbReuse.ItemDescriptionsResolver' => function( MediaWikiServices $services ): ItemDescriptionsResolver {
		return new ItemDescriptionsResolver(
			new BatchGetItemDescriptions(
				new PrefetchingTermLookupBatchLabelsDescriptionsRetriever( WikibaseRepo::getPrefetchingTermLookup( $services ) ),
				new EntityRevisionLookupItemRedirectResolver( WikibaseRepo::getEntityRevisionLookup( $services ) ),
			)
		);
	},
	'WbReuse.ItemLabelsResolver' => function( MediaWikiServices $services ): ItemLabelsResolver {
		return new ItemLabelsResolver(
			new BatchGetItemLabels(
				new PrefetchingTermLookupBatchLabelsDescriptionsRetriever( WikibaseRepo::getPrefetchingTermLookup( $services ) ),
				new EntityRevisionLookupItemRedirectResolver( WikibaseRepo::getEntityRevisionLookup( $services ) ),
			)
		);
	},
	'WbReuse.ItemLabelsWithLanguageFallbackResolver' => function(
		MediaWikiServices $services
	): ItemLabelsWithLanguageFallbackResolver {
		$languageFallbackChainProvider = new LanguageFallbackChainFactoryFallbackLanguagesProvider(
			WikibaseRepo::getLanguageFallbackChainFactory( $services )
		);
		return new ItemLabelsWithLanguageFallbackResolver(
			new BatchGetItemLabelsWithLanguageFallback( new ItemLabelsWithLanguageFallbackBatchRetriever(
				new PrefetchingTermLookupBatchLabelsDescriptionsRetriever( WikibaseRepo::getPrefetchingTermLookup( $services ) ),
				$languageFallbackChainProvider,
				WbReuse::getLanguageFallbackLabelSelector( $services ),
			) ),
		);
	},
	'WbReuse.LanguageFallbackLabelSelector' => function( MediaWikiServices $services ): LanguageFallbackLabelSelector {
		return new LanguageFallbackLabelSelector(
			new LanguageFallbackChainFactoryFallbackLanguagesProvider(
				WikibaseRepo::getLanguageFallbackChainFactory( $services )
			)
		);
	},
	'WbReuse.PropertyLabelsResolver' => function( MediaWikiServices $services ): PropertyLabelsResolver {
		return new PropertyLabelsResolver(
			new BatchGetPropertyLabels(
				new PrefetchingTermLookupBatchLabelsDescriptionsRetriever( WikibaseRepo::getPrefetchingTermLookup( $services ) )
			)
		);
	},
	'WbReuse.PropertyLabelsWithLanguageFallbackResolver' => function(
		MediaWikiServices $services
	): PropertyLabelsWithLanguageFallbackResolver {
		$languageFallbackChainProvider = new LanguageFallbackChainFactoryFallbackLanguagesProvider(
			WikibaseRepo::getLanguageFallbackChainFactory( $services )
		);
		return new PropertyLabelsWithLanguageFallbackResolver(
			new BatchGetPropertyLabelsWithLanguageFallback( new PropertyLabelsWithLanguageFallbackBatchRetriever(
				new PrefetchingTermLookupBatchLabelsDescriptionsRetriever( WikibaseRepo::getPrefetchingTermLookup( $services ) ),
				$languageFallbackChainProvider,
				WbReuse::getLanguageFallbackLabelSelector( $services )
			) ),
		);
	},
];
