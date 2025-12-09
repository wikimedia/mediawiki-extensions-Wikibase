<?php declare( strict_types=1 );

use MediaWiki\MediaWikiServices;
use Wikibase\Repo\Domains\Reuse\Application\UseCases\BatchGetItemDescriptions\BatchGetItemDescriptions;
use Wikibase\Repo\Domains\Reuse\Application\UseCases\BatchGetItemLabels\BatchGetItemLabels;
use Wikibase\Repo\Domains\Reuse\Application\UseCases\BatchGetItems\BatchGetItems;
use Wikibase\Repo\Domains\Reuse\Application\UseCases\BatchGetPropertyLabels\BatchGetPropertyLabels;
use Wikibase\Repo\Domains\Reuse\Application\UseCases\FacetedItemSearch\FacetedItemSearch;
use Wikibase\Repo\Domains\Reuse\Domain\Services\FacetedItemSearchEngine;
use Wikibase\Repo\Domains\Reuse\Domain\Services\StatementReadModelConverter;
use Wikibase\Repo\Domains\Reuse\Infrastructure\DataAccess\EntityLookupItemsBatchRetriever;
use Wikibase\Repo\Domains\Reuse\Infrastructure\DataAccess\PrefetchingTermLookupBatchLabelsDescriptionsRetriever;
use Wikibase\Repo\Domains\Reuse\Infrastructure\GraphQL\GraphQLService;
use Wikibase\Repo\Domains\Reuse\Infrastructure\GraphQL\Resolvers\ItemDescriptionsResolver;
use Wikibase\Repo\Domains\Reuse\Infrastructure\GraphQL\Resolvers\ItemLabelsResolver;
use Wikibase\Repo\Domains\Reuse\Infrastructure\GraphQL\Resolvers\ItemResolver;
use Wikibase\Repo\Domains\Reuse\Infrastructure\GraphQL\Resolvers\PropertyLabelsResolver;
use Wikibase\Repo\Domains\Reuse\Infrastructure\GraphQL\Resolvers\SearchItemsResolver;
use Wikibase\Repo\Domains\Reuse\Infrastructure\GraphQL\Schema\Schema;
use Wikibase\Repo\Domains\Reuse\Infrastructure\GraphQL\Schema\Types;
use Wikibase\Repo\Domains\Reuse\Infrastructure\Search\CirrusSearchFacetedSearchEngine;
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
		return new Schema(
			new ItemResolver(
				new BatchGetItems( new EntityLookupItemsBatchRetriever(
					WikibaseRepo::getEntityLookup( $services ),
					$services->getSiteLookup(),
					new StatementReadModelConverter(
						WikibaseRepo::getStatementGuidParser( $services ),
						WikibaseRepo::getPropertyDataTypeLookup( $services )
					)
				) )
			),
			new SearchItemsResolver(
				new FacetedItemSearch( WbReuse::getFacetedItemSearchEngine( $services ) ),
				$services->getExtensionRegistry()
			),
			WbReuse::getGraphQLTypes( $services ),
		);
	},
	'WbReuse.GraphQLService' => function( MediaWikiServices $services ): GraphQLService {
		return new GraphQLService(
			WbReuse::getGraphQLSchema( $services ),
			$services->getMainConfig(),
		);
	},
	'WbReuse.GraphQLTypes' => function( MediaWikiServices $services ): Types {
		return new Types(
			WikibaseRepo::getTermsLanguages( $services )->getLanguages(),
			WikibaseRepo::getSiteLinkGlobalIdentifiersProvider( $services ),
			WbReuse::getPropertyLabelsResolver( $services ),
			WikibaseRepo::getDataTypeDefinitions( $services ),
			new ItemDescriptionsResolver( new BatchGetItemDescriptions(
				new PrefetchingTermLookupBatchLabelsDescriptionsRetriever( WikibaseRepo::getPrefetchingTermLookup( $services ) )
			) ),
			WbReuse::getItemLabelsResolver( $services ),
		);
	},
	'WbReuse.ItemLabelsResolver' => function( MediaWikiServices $services ): ItemLabelsResolver {
		return new ItemLabelsResolver(
			new BatchGetItemLabels(
				new PrefetchingTermLookupBatchLabelsDescriptionsRetriever( WikibaseRepo::getPrefetchingTermLookup( $services ) )
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
];
